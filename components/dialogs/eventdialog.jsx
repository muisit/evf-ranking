import React from 'react';
import { singleevent, weapons, categories } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { TabView,TabPanel } from 'primereact/tabview';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputNumber } from 'primereact/inputnumber';
import { Calendar } from 'primereact/calendar';
import { format_date, parse_date } from '../functions';

function Competition(props) {
    var opens=props.cmp.opens.length ? parse_date(props.cmp.opens) : null;
    var weapon_check=props.cmp.weapon_check.length ? parse_date(props.cmp.weapon_check) : null;
    var ourstart=props.start;
    var ourend=props.end;

    if(opens != null && ourstart.isAfter(opens)) {
        ourstart=opens;
    }
    if(weapon_check != null && ourstart.isAfter(weapon_check)) {
        ourstart=weapon_check;
    }
    if(opens == null || weapon_check == null) {
        // allow for a 3 day head start on the original start
        ourstart = parse_date(props.start);
        ourstart.add(-3,'d');
    }
    opens = opens === null ? '' : opens;
    weapon_check = weapon_check === null ? '' : weapon_check;
    var vdate1= opens == '' ? ourstart : opens;
    var vdate2 = weapon_check == '' ? ourstart : weapon_check;
    var nw=parse_date();
    var range = ourstart.year() + ':' + (nw.year()+15);
    var numofmonths= (ourstart.month() == ourend.month()) ? 1 : 2;

    return (
        <div className='competition'>
      <Dropdown className='catdrop' autoWidth={false} name={'ccat-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="id" value={props.cmp.category} options={props.ddcats} placeholder="Category" />
      <Dropdown className='wpndrop' autoWidth={false} name={'cwpn-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="id" value={props.cmp.weapon} options={props.ddwpns} placeholder="Weapon" />
      <Calendar name={'copens-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} minDate={ourstart.toDate()} maxDate={ourend.toDate()} dateFormat="yy-mm-dd" value={opens.toDate()} viewDate={vdate1.toDate()} monthNavigator yearNavigator yearRange={range} numberOfMonths={numofmonths}></Calendar>
      <Calendar name={'ccheck-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} minDate={ourstart.toDate()} maxDate={ourend.toDate()} dateFormat="yy-mm-dd" value={weapon_check.toDate()} viewDate={vdate2.toDate()} monthNavigator yearNavigator yearRange={range} numberOfMonths={numofmonths}></Calendar>
      <span className="p-input-icon-left add-button">
        <i className="pi pi-trash"onClick={() => props.onRemoveCompetition(props.cmp)}></i>
      </span>
        </div>
    );
}

export default class EventDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            compindex: 1,
            weapons: [],
            categories: []
        };

    }

    componentDidMount = () => {
        weapons().then((wpns) => { if(wpns) this.setState({'weapons':wpns.data.list }) });
        categories().then((cats) => { if (cats) this.setState({ 'categories': cats.data.list }) });
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onCloseDialog = (event) => {
        this.loading(true);

        singleevent('save',this.props.value)
            .then((json) => {
                if(json) {
                    this.save(this.props.value);
                }
            })
            .catch((err) => {
                if(err.response.data.messages && err.response.data.messages.length) {
                    var txt="";
                    for(var i=0;i<err.response.data.messages.length;i++) {
                        txt+=err.response.data.messages[i]+"\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete event '+ this.props.value.name + "? This action cannot be undone!")) {
            this.setState({loading:true});
            singleevent('delete',{ id: this.props.value.id})
            .then((json) => {
                if(json) {
                    this.close();
                }
            })
            .catch((err) => {
                if(err.response.data.messages && err.response.data.messages.length) {
                    var txt="";
                    for(var i=0;i<err.response.data.messages.length;i++) {
                        txt+=err.response.data.messages[i]+"\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error removing the data. Please try again');
                }
            })
        }
    }

    onChangeEl = (event,attrvalue) => {
        var item=this.props.value;
        var name=attrvalue ? event : (event.target ? event.target.name : event.originalEvent.target.name);
        var value=attrvalue ? attrvalue : (event.target ? event.target.value : event.value);
        var els=name.split('-');
        var id = name;
        if(els.length > 1) {
            name = els[0];
            id=els[1];
        }

        switch (name) {
        case 'name':
        case 'type':
        case 'year':
        case 'duration':
        case 'email':
        case 'web':
        case 'location':
        case 'country':
        case 'in_ranking':
        case 'feed':
            item[name] = value;
            break;
        case 'opens':
            var dt=parse_date(value);
            dt.hour(12); // compensate for timezones
            item.opens = format_date(dt);
            break;
        case 'ccat':
        case 'cwpn':
        case 'copens':
        case 'ccheck':
            var comps=this.props.value.competitions;
            for(var i in comps) {
                var cmp=comps[i];
                if(cmp.id == id) {
                    switch(name) {
                    case 'ccat':
                        cmp.category=value;
                        break;
                    case 'cwpn':
                        cmp.weapon=value;
                        break;
                    case 'copens':
                        cmp.opens=format_date(value);
                        break;
                    case 'ccheck':
                        cmp.weapon_check=format_date(value);
                        break;
                    }
                    comps[i]=cmp;
                    break;
                }
            }
            item.competitions = comps;
            break;
        }
        if(this.props.onChange) this.props.onChange(item);
    }

    addCompetition = (tp) => {
        var item=this.props.value;
        var pushed=item.competitions;
        var cindex=this.state.compindex;
        if(!pushed) pushed=[];
        if(tp == 'one') {
            pushed.push({'id':'a'+cindex,'event_id': this.props.value.id,'category':-1,'weapon':-1,'opens':this.props.value.opens,'weapon_check':this.props.value.opens})
            cindex+=1;
        }
        else if(tp == 'all') {
            // add all competitions of which weapon and category were not already matched
            var allcombos={};
            for(var i in pushed) {
                var comp = pushed[i];
                var key="w_" + comp.weapon + "_c_" + comp.category;
                allcombos[key]=true;
            }

            for(var i in this.state.weapons) {
                for(var j in this.state.categories) {
                    var key="w_" + this.state.weapons[i].id + "_c_" + this.state.categories[j].id;
                    if(!allcombos[key]) {
                        pushed.push({'id':'a'+cindex,'event_id': this.props.value.id,'category':this.state.categories[j].id,'weapon':this.state.weapons[i].id,'opens':this.props.value.opens,'weapon_check':this.props.value.opens})
                        cindex+=1;
                    }
                }
            }
        }
        item.competitions=pushed;
        if(this.props.onChange) this.props.onChange(item);
        this.setState({'compindex': cindex});
    }

    removeCompetition = (cmp) => {
        var item=this.props.value;
        var pushed=item.competitions;
        for(var i in pushed) {
            var c=pushed[i];
            if(c.id == cmp.id) {
                pushed.splice(i,1);
                break;
            }
        }
        item.competitions=pushed;
        if(this.props.onChange) this.props.onChange(item);
    }

    render() {
        var date_opens=parse_date(this.props.value.opens);
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
    </div>);
        if(this.props.value.id > 0) {
            footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label="Delete" icon="pi pi-times" className="p-button-danger p-button-raised p-button-text" onClick={this.onDeleteDialog} />
            <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
        </div>);    
        }

        var ddwpns=[];
        this.state.weapons.map(wpn => {
            ddwpns.push({'name': wpn.name,'id':wpn.id});
        });
        var ddcats=[];
        this.state.categories.map(cat => {
            ddcats.push({'name': cat.name + ' (' + cat.type + ')','id':cat.id});
        });
        var start=parse_date(this.props.value.opens);
        var end=parse_date(start);

        var year = parseInt(this.props.value.year);
        if(isNaN(year)) year = start.year();
        var duration = parseInt(this.props.value.duration);
        if (isNaN(duration)) duration = 2;
        end.add(duration, 'd');

        return (
<Dialog header="Edit Event" position="center" className="event-dialog" visible={this.props.display} style={{ width: '65vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
  <TabView id="eventdialog" animate={true} defaultSelectedTabId="general">
    <TabPanel id='general' header='General'>
      <div>
        <label>Name</label>
        <div className='input'>
            <InputText name='name' value={this.props.value.name} onChange={this.onChangeEl} placeholder='Name'/>
        </div>
      </div>
      <div>
        <label>Type</label>
        <div className='input'>
            <Dropdown optionLabel="name" optionValue="id" value={this.props.value.type} options={this.props.types} placeholder="Type" onChange={this.onChangeEl} name='type'/>
        </div>
      </div>
      <div>
        <label>Starts</label>
        <div className='input'>
            <Calendar name="opens" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={date_opens.toDate()}></Calendar>
        </div>
      </div>
      <div>
        <label>Year</label>
        <div className='input'>
            <InputNumber className='inputint' name='year' onChange={this.onChangeEl} min={2000} max={2100}  mode="decimal" useGrouping={false} 
             value={year}
             showButtons buttonLayout="horizontal" step={1} decrementButtonClassName="p-button-success" incrementButtonClassName="p-button-success" 
             incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus"></InputNumber>
        </div>
      </div>
      <div>
        <label>Duration</label>
        <div className='input'>
            <InputNumber className='inputint' name='duration' onValueChange={(e)=>this.onChangeEl('duration',e.value)} min={1} max={21}  mode="decimal" useGrouping={false} 
             value={duration}
             showButtons buttonLayout="horizontal" step={1} decrementButtonClassName="p-button-success" incrementButtonClassName="p-button-success" 
             incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus"></InputNumber>
        </div>
      </div>
      <div>
        <label>E-mail</label>
        <div className='input'>
            <InputText name='email' value={this.props.value.email} onChange={this.onChangeEl} placeholder='e-mail'/>
        </div>
      </div>
      <div>
        <label>Web</label>
        <div className='input'>
            <InputText name='web' value={this.props.value.web} onChange={this.onChangeEl} placeholder='web address'/>
        </div>
      </div>
      <div>
        <label>Location</label>
        <div className='input'>
            <InputText name='location' value={this.props.value.location} onChange={this.onChangeEl} placeholder='location'/>
        </div>
      </div>
      <div>
        <label>Country</label>
        <div className='input'>
            <Dropdown name='country' optionLabel="name" optionValue="id" value={this.props.value.country} options={this.props.countries} placeholder="Country" onChange={this.onChangeEl} />
        </div>
      </div>
      <div>
        <label>Live feed</label>
        <div className='input'>
            <InputText name='feed' value={this.props.value.feed} onChange={this.onChangeEl} placeholder='url'/>
        </div>
      </div>

    </TabPanel>

    <TabPanel id='competitions' header='Competitions'>
    <div className='competitions'>
      <span className="p-input-icon-left add-button">
        <i className="pi pi-plus-circle"></i>
        <a onClick={()=>this.addCompetition('one')}>Add</a>
      </span>
      <span className="p-input-icon-left add-button">
        <i className="pi pi-plus-circle"></i>
        <a onClick={() => this.addCompetition('all')}>Add All</a>
      </span>
      <div className='competition_list'>
        <div className='competition'>
          <span className='catdrop'><b>Category</b></span>
          <span className='wpndrop'><b>Weapon</b></span>
          <span className='p-calendar-span'><b>Opens</b></span>
          <span className='p-calendar-span'><b>Check</b></span>
        </div>
        {this.props.value && this.props.value.competitions && this.props.value.competitions.map((cmp,idx) => (
            <Competition cmp={cmp} ddwpns={ddwpns} ddcats={ddcats} start={start} end={end} key={idx} onChangeEl={this.onChangeEl} onRemoveCompetition={this.removeCompetition}/>))}
      </div>
    </div>

    </TabPanel>
  </TabView>
</Dialog>            
);
    }

}

