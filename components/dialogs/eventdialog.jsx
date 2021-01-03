import React from 'react';
import { singleevent, weapons, categories } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { TabView,TabPanel } from 'primereact/tabview';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputNumber } from 'primereact/inputnumber';
import { Calendar } from 'primereact/calendar';

function pad(number) {
    if (number < 10) {
        return '0' + number;
    }
    return number;
}
function format_date(dt) {
    return dt.getFullYear() +
        '-' + pad(dt.getMonth() + 1) +
        '-' + pad(dt.getDate());
};

function Competition(props) {
    var opens=props.cmp.opens.length ? new Date(props.cmp.opens) : null;
    var weapon_check=props.cmp.weapon_check.length ? new Date(props.cmp.weapon_check) : null;
    var ourstart=props.start;
    var ourend=props.end;

    if(opens != null && ourstart.getTime() > opens.getTime()) {
        ourstart=opens;
    }
    if(weapon_check != null && ourstart.getTime() > weapon_check.getTime()) {
        ourstart=weapon_check;
    }
    if(opens == null || weapon_check == null) {
        // allow for a 3 day head start on the original start
        ourstart = new Date(props.start);
        ourstart.setDate(ourstart.getDate()-3);
    }
    opens = opens === null ? '' : opens;
    weapon_check = weapon_check === null ? '' : weapon_check;
    var vdate1= opens == '' ? ourstart : opens;
    var vdate2 = weapon_check == '' ? ourstart : weapon_check;
    var nw=new Date();
    var range = ourstart.getFullYear() + ':' + (nw.getFullYear()+15);
    var numofmonths= (ourstart.getMonth() == ourend.getMonth()) ? 1 : 2;

    return (
        <div className='competition'>
      <Dropdown className='catdrop' autoWidth={false} name={'ccat-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="id" value={props.cmp.category} options={props.ddcats} placeholder="Category" />
      <Dropdown className='wpndrop' autoWidth={false} name={'cwpn-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="id" value={props.cmp.weapon} options={props.ddwpns} placeholder="Weapon" />
      <Calendar name={'copens-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} minDate={ourstart} maxDate={ourend} dateFormat="yy-mm-dd" value={opens} viewDate={vdate1} monthNavigator yearNavigator yearRange={range} numberOfMonths={numofmonths}></Calendar>
      <Calendar name={'ccheck-' + props.cmp.id} appendTo={document.body} onChange={props.onChangeEl} minDate={ourstart} maxDate={ourend} dateFormat="yy-mm-dd" value={weapon_check} viewDate={vdate2} monthNavigator yearNavigator yearRange={range} numberOfMonths={numofmonths}></Calendar>
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

        this.currencies = [
            {"name":"Euro","code":"EUR","symbol":"€"},
            {"name":"British Pound","code":"GBP","symbol":"£"},
            {"name":"Swiss Franc","code":"CHF","symbol":"CHF"},
            {"name":"Danish Krone","code":"DKK","symbol":"kr."},
            {"name":"Icelandic Króna","code":"ISK","symbol":"kr."},
            {"name":"Norwegian Krone","code":"NOK","symbol":"kr."},
            {"name":"Swedish Krona","code":"SEK","symbol":"kr."},
            {"name":"Hungarian Forint","code":"HUF","symbol":"Ft"},
            {"name":"Polish Złoty","code":"PLN","symbol":"zł"},
            {"name":"Unknown","code":"UNK","symbol":"-"},
        ];

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

    onChangeEl = (event) => {
        var item=this.props.value;        
        var name=event.target ? event.target.name : event.originalEvent.target.name;
        var value=event.target ? event.target.value : event.value;
        var els=name.split('-');
        var id = name;
        if(els.length > 1) {
            name = els[0];
            id=els[1];
        }
        console.log('adjusting value ' + name + "/" + id + "/" + value);
        switch (name) {
        case 'name':
        case 'type':
        case 'year':
        case 'duration':
        case 'email':
        case 'web':
        case 'location':
        case 'country':
        case 'registration_cost':
        case 'entry_cost':
        case 'dinner_cost':
        case 'note':
        case 'bank':
        case 'account':
        case 'address':
        case 'iban':
        case 'swift':
        case 'reference':
        case 'in_ranking':
            item[name] = value;
            break;
        case 'opens':
            try {
            console.log('setting opens to '+value);
            var dt=new Date(value);
            dt.setHours(12); // compensate for timezones
            console.log('dt is ',dt);
            item.opens = format_date(dt);
            //console.log('value is now '+ item.opens);
            //if(parseInt(item.year) < 2010) {
            //    item.year = dt.getFullYear();
            //}
            } catch(e) {console.log('caught ',e);}
            break;
        case 'currency':
            item.currency = value;
            for(var idx in this.currencies) {
                var cr=this.currencies[idx];
                if(cr.code == value) {
                    item.symbol = cr.symbol;
                    break;
                }
            }
            break;
        case 'ccat':
        case 'cwpn':
        case 'copens':
        case 'ccheck':
            console.log('adjusting competition value');
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
                    console.log('competition is now ',cmp);
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
            console.log("pushed competitions is "+JSON.stringify(allcombos));
            for(var i in this.state.weapons) {
                for(var j in this.state.categories) {
                    var key="w_" + this.state.weapons[i].id + "_c_" + this.state.categories[j].id;
                    console.log("testing key " + key);
                    if(!allcombos[key]) {
                        pushed.push({'id':'a'+cindex,'event_id': this.props.value.id,'category':this.state.categories[j].id,'weapon':this.state.weapons[i].id,'opens':this.props.value.opens,'weapon_check':this.props.value.opens})
                        cindex+=1;
                    }
                    else {
                        console.log("key for "+key+" already set");
                    }
                }
            }
        }
        item.competitions=pushed;
        if(this.props.onChange) this.props.onChange(item);
        this.setState({'compindex': cindex});
    }

    removeCompetition = (cmp) => {
        console.log("remove competition called with ",cmp);
        var item=this.props.value;
        var pushed=item.competitions;
        console.log('pushed contains '+pushed.length + " items");
        for(var i in pushed) {
            var c=pushed[i];
            console.log('comparing '+c.id + " vs " + cmp.id);
            if(c.id == cmp.id) {
                console.log('found competition, splicing');
                pushed.splice(i,1);
                console.log('pushed contains '+pushed.length + " items");
                break;
            }
        }
        console.log('adjusting item');
        item.competitions=pushed;
        if(this.props.onChange) this.props.onChange(item);
    }

    render() {
        var date_opens=new Date(this.props.value.opens);
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
        var start=new Date(this.props.value.opens);
        var end=new Date(start);       
        end.setDate(end.getDate() + (parseInt(this.props.value.duration) || 21));

        return (
<Dialog header="Edit Event" position="center" visible={this.props.display} style={{ width: '65vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
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
        <label>Opens</label>
        <div className='input'>
            <Calendar name="opens" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={date_opens}></Calendar>
        </div>
      </div>
      <div>
        <label>Year</label>
        <div className='input'>
            <InputNumber className='inputint' name='year' onChange={this.onChangeEl} min={2000} max={2100}  mode="decimal" useGrouping={false} 
             value={parseInt(this.props.value.year)}
             showButtons buttonLayout="horizontal" step={1} decrementButtonClassName="p-button-success" incrementButtonClassName="p-button-success" 
             incrementButtonIcon="pi pi-plus" decrementButtonIcon="pi pi-minus"></InputNumber>
        </div>
      </div>
      <div>
        <label>Duration</label>
        <div className='input'>
            <InputNumber className='inputint' name='duration' onChange={this.onChangeEl} min={1} max={21}  mode="decimal" useGrouping={false} 
             value={parseInt(this.props.value.duration)}
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

    </TabPanel>
    <TabPanel id='registration' header='Registration'>
    <div>
        <label>Currency</label>
        <div className='input'>
            <Dropdown name='currency' optionLabel="name" optionValue="code" value={this.props.value.currency} options={this.currencies} placeholder="Currency" onChange={this.onChangeEl} appendTo={document.body}/>
        </div>
      </div>
    <div>
        <label>Registration Costs</label>
        <div className='input'>
            <InputNumber className='inputint' name='registration_cost' onChange={this.onChangeEl} 
              mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={this.props.value.symbol+' '}
             value={parseFloat(this.props.value.registration_cost)}></InputNumber>
        </div>
      </div>
      <div>
        <label>Entry Costs</label>
        <div className='input'>
            <InputNumber className='inputint' name='entry_cost' onChange={this.onChangeEl} 
              mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={this.props.value.symbol+' '}
             value={parseFloat(this.props.value.entry_cost)}></InputNumber>
        </div>
      </div>
      <div>
        <label>Dinner Costs</label>
        <div className='input'>
            <InputNumber className='inputint' name='dinner_cost' onChange={this.onChangeEl} 
              mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={this.props.value.symbol+' '}
             value={parseFloat(this.props.value.dinner_cost)}></InputNumber>
        </div>
      </div>
      <div>
        <label>Dinner Note</label>
        <div className='input'>
            <InputText name='note' value={this.props.value.note} onChange={this.onChangeEl} placeholder='Dinner note'/>
        </div>
      </div>
      <div>
        <label>Bank</label>
        <div className='input'>
            <InputText name='bank' value={this.props.value.bank} onChange={this.onChangeEl} placeholder='Bank name'/>
        </div>
      </div>
      <div>
        <label>Account Name</label>
        <div className='input'>
            <InputText name='account' value={this.props.value.account} onChange={this.onChangeEl} placeholder='Account name'/>
        </div>
      </div>
      <div>
        <label>Account Address</label>
        <div className='input'>
            <InputText name='address' value={this.props.value.address} onChange={this.onChangeEl} placeholder='Account holder address'/>
        </div>
      </div>
      <div>
        <label>IBAN</label>
        <div className='input'>
            <InputText name='iban' value={this.props.value.iban} onChange={this.onChangeEl} placeholder='IBAN account nr'/>
        </div>
      </div>
      <div>
        <label>SWIFT</label>
        <div className='input'>
            <InputText name='swift' value={this.props.value.swift} onChange={this.onChangeEl} placeholder='SWIFT code'/>
        </div>
      </div>
      <div>
        <label>Reference</label>
        <div className='input'>
            <InputText name='reference' value={this.props.value.reference} onChange={this.onChangeEl} placeholder='Account reference'/>
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

