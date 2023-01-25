import React from 'react';
import { singleevent, posts } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { TabView,TabPanel } from 'primereact/tabview';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { InputText } from 'primereact/inputtext';
import { InputNumber } from 'primereact/inputnumber';
import { Calendar } from 'primereact/calendar';
import { format_date, parse_date, parse_float, parse_int, is_valid } from '../../functions';

function hasComp(evt) {
    return is_valid(evt.competition_id);
}

function SideEvent(props) {
    var starts=props.event.starts.length ? parse_date(props.event.starts) : null;
    var ourstart=parse_date(props.start);
    var ourend=parse_date(props.end);

    if(starts != null && starts.isBefore(ourstart)) {
        ourstart=parse_date(starts);
        ourstart.add(-3,'d');
    }
    if(starts == null) {
        // allow for a 3 day head start on the original start
        ourstart = parse_date(props.start);
        ourstart.add(-3,'d');
    }

    var vdate1= starts == '' ? ourstart : starts;
    var nw=parse_date();
    var range = ourstart.year() + ':' + (nw.year()+15);
    var numofmonths= (ourstart.month() == ourend.month()) ? 1 : 2;

    var hascomp=hasComp(props.event);

    return (
        <div className='sideevent'>
          {!hascomp && (
          <div className='formelement'>
            <label>Title</label>
            <div className='input'>
              <InputText name={'etitle-'+ props.event.id} value={props.event.title} onChange={props.onChangeEl} placeholder='Title'/>
            </div>
          </div>)}
          {!hascomp && (<span className="p-input-icon-left add-button">
            <i className="pi pi-trash"onClick={() => props.onRemoveEvent(props.event)}></i>
          </span>)}
          {!hascomp && (<div className='formelement'>
            <label>Description</label>
            <div className='input'>
              <InputText name={'edescr-'+ props.event.id} value={props.event.description} onChange={props.onChangeEl} placeholder='Description'/>
            </div>
          </div>)}
          {!hascomp && (<div className='formelement'>
            <label>Start</label>
            <div className='input'>
              <Calendar name={'estarts-' + props.event.id} appendTo={document.body} onChange={props.onChangeEl} minDate={ourstart.toDate()} maxDate={ourend.toDate()} dateFormat="yy-mm-dd" value={starts.toDate()} viewDate={vdate1.toDate()} monthNavigator yearNavigator yearRange={range} numberOfMonths={numofmonths}></Calendar>
            </div>
          </div>)}
          <div className='formelement'>
            <label>Costs</label>
            <div className='input'>
              <InputNumber className='inputint' name={'ecosts-' + props.event.id} onChange={props.onChangeEl} 
                mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={props.symbol+' '}
                value={parseFloat(props.event.costs)}></InputNumber>
            </div>
          </div>
        </div>
    );
}

let roletypes = [
  { name: 'Organisation', code: 'organiser' },
  { name: 'Accreditation', code: 'accreditation' },
  { name: 'Cashier', code: 'cashier' },
  { name: 'Registrar', code: 'registrar' },
];

function EventRole(props) {
  return (
    <div className='roletype'>
      <Dropdown className='userdrop' name={'ruser-' + props.role.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="id" value={props.role.user} options={props.users} placeholder="User" />
      <Dropdown className='roletypedrop' name={'rtype-' + props.role.id} appendTo={document.body} onChange={props.onChangeEl} optionLabel="name" optionValue="code" value={props.role.role_type} options={roletypes} placeholder="Role" />
      <span className="p-input-icon-left add-button">
        <i className="pi pi-trash" onClick={() => props.onRemoveRole(props.role)}></i>
      </span>
    </div>
  );
}

export default class EventRegistrationDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            evtindex: 1,
            roleindex:1,
            events: []
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
        posts(0,10000,null,"i",JSON.stringify({ events: true}))
          .then((cmp) => {
              this.setState({events: cmp.data.list});
          });
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

        switch (name) {
        case 'allow_registration_lower_age':
        case 'allow_more_teams':
            // checkbox configuration value
            var isset = event.checked;
            if(!item.config) {
              item.config={};
            }
            item.config[name]=isset;
            break;
        case 'base_fee':
        case 'competition_fee':
        case 'bank':
        case 'account':
        case 'address':
        case 'iban':
        case 'swift':
        case 'reference':
        case 'frontend':
        case 'payments':
            item[name] = value;
            break;
        case 'reg_open':
        case 'reg_close':
            item[name] = format_date(value);
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
        case 'estarts':
        case 'ecosts':
        case 'etitle':
        case 'edescr':
            var sides=this.props.value.sides;
            for(var i in sides) {
                var evt=sides[i];
                var hascomp=hasComp(evt);
                if(evt.id == id) {
                    switch(name) {
                    case 'estarts':
                        evt.starts=format_date(value);
                        break;
                    case 'ecosts':
                        evt.costs=parseFloat(value);
                        break;
                    case 'etitle':
                        if(!hascomp) evt.title = value;
                        break;
                    case 'edescr':
                        evt.description = value;
                        break;
                    }
                    sides[i]=evt;
                    break;
                }
            }
            item.sides = sides;
            break;
        case 'ruser':
        case 'rtype':
            var roles=this.props.value.roles;
            for(var i in roles) {
                var d=roles[i];
                if(d.id == id) {
                    switch(name) {
                    case 'ruser':
                        d.user=value;
                        break;
                    case 'rtype':
                        d.role_type=value;
                        break;
                    }
                    roles[i]=d;
                    break;
                }
            }
            item.roles = roles;
            break;
        }
        if(this.props.onChange) this.props.onChange(item);
    }

    addEvent = (tp) => {
        var item=this.props.value;
        var pushed=item.sides;
        var cindex=this.state.evtindex;
        if(!pushed) pushed=[];

        pushed.push({'id':'a'+cindex,'event_id': this.props.value.id,'starts':this.props.value.opens,'title':'','description':'','costs':0.0,'competition_id':null})
        cindex+=1;

        item.sides=pushed;
        if(this.props.onChange) this.props.onChange(item);
        this.setState({'evtindex': cindex});
    }

    removeEvent = (evt) => {
        var item=this.props.value;
        var pushed=item.sides;
        for(var i in pushed) {
            var c=pushed[i];
            if(c.id == evt.id) {
                pushed.splice(i,1);
                break;
            }
        }
        item.sides=pushed;
        if(this.props.onChange) this.props.onChange(item);
    }

    addEventRole = (tp) => {
        var item=this.props.value;
        var pushed=item.roles;
        var cindex=this.state.roleindex;
        if(!pushed) pushed=[];

        pushed.push({'id':'a'+cindex,'event_id': this.props.value.id,'user':-1,role_type:''});
        cindex+=1;

        item.roles=pushed;
        if(this.props.onChange) this.props.onChange(item);
        this.setState({'roleindex': cindex});
    }

    removeRole = (role) => {
        var item=this.props.value;
        var pushed=item.roles;
        for(var i in pushed) {
            var c=pushed[i];
            if(c.id == role.id) {
                pushed.splice(i,1);
                break;
            }
        }
        item.roles=pushed;
        if(this.props.onChange) this.props.onChange(item);
    }

    render() {
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
    </div>);
        var start=parse_date(this.props.value.opens);
        var end=parse_date(start);       
        var duration = parse_int(this.props.value.duration,2);
        end.add(duration,'d');

        var sidesSansComp = [];
        if(this.props.value.sides) {
            sidesSansComp=this.props.value.sides.filter((evt) => { return !hasComp(evt);});
        }

        var unselectevent = { id: null, title:"None"};
        var eventsWithUnselect=[unselectevent];
        if(this.state.events.length) {
            eventsWithUnselect = this.state.events.slice();
            eventsWithUnselect.splice(0,0,unselectevent);
        }

        var payments=[
          { id: "all", name:"Group or Individual"},
          { id: "group", name: "Group payments only"},
          { id: "individual", name: "Individual payments only" },
        ];

        var reg_open = parse_date(this.props.value.reg_open);
        var reg_close = parse_date(this.props.value.reg_close);

        var basefee = parse_float(this.props.value.base_fee,0);
        var compfee = parse_float(this.props.value.competition_fee,0);

        var cfg = this.props.value.config;
        var allow_lower_reg = (cfg && cfg.allow_registration_lower_age) ? true : false;
        var allow_more_teams = (cfg && cfg.allow_more_teams) ? true : false;

        return (
<Dialog header="Edit Event" position="center" className="event-dialog" visible={this.props.display} style={{ width: '65vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
<TabView id="eventdialog" animate={true} defaultSelectedTabId="general">
    <TabPanel id='general' header='General'>
      <div>
        <label>Name</label>
        <div className='input'>{ this.props.value.name }</div>
      </div>
    <div>
      <label>Frontend Event</label>
      <div className='input'>
          <Dropdown name='frontend' optionLabel="title" optionValue="id" value={this.props.value.frontend} options={eventsWithUnselect} placeholder="Event" onChange={this.onChangeEl} appendTo={document.body}/>
      </div>
    </div>
      <div>
        <label>Reg. Opens</label>
        <div className='input'>
            <Calendar name="reg_open" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={reg_open.toDate()}></Calendar>
        </div>
      </div>
      <div>
        <label>Reg. Closes</label>
        <div className='input'>
            <Calendar name="reg_close" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={reg_close.toDate()}></Calendar>
        </div>
      </div>
      <div>
        <label>Configuration</label>
        <div className='input'>
          <div className='config'>
            <Checkbox inputId='cfg1' name={'allow_registration_lower_age'} onChange={this.onChangeEl} checked={allow_lower_reg} /> 
            <label className='checkbox' htmlFor="cfg1">Allow registration in a lower age category</label>
          </div>
          <div className='config'>
            <Checkbox inputId='cfg2' name={'allow_more_teams'} onChange={this.onChangeEl} checked={allow_more_teams} /> 
            <label className='checkbox' htmlFor="cfg2">Allow registration of multiple teams</label>
          </div>
        </div>
      </div>
      </TabPanel>
      <TabPanel id='payment' header='Payment'>
      <div>
      <label>Payments</label>
      <div className='input'>
        <Dropdown name='payments' optionLabel="name" optionValue="id" value={this.props.value.payments} options={payments} placeholder="Payments" onChange={this.onChangeEl} appendTo={document.body} />
      </div>
    </div>
    <div>
      <label>Currency</label>
      <div className='input'>
        <Dropdown name='currency' optionLabel="name" optionValue="code" value={this.props.value.currency} options={this.currencies} placeholder="Currency" onChange={this.onChangeEl} appendTo={document.body}/>
      </div>
    </div>
    <div>
      <label>Base fee</label>
      <div className='input'>
        <InputNumber className='inputint' name='base_fee' onChange={this.onChangeEl} 
          mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={this.props.value.symbol+' '}
          value={basefee}></InputNumber>
        </div>
    </div>
    <div>
      <label>Competition fee</label>
      <div className='input'>
        <InputNumber className='inputint' name='competition_fee' onChange={this.onChangeEl} 
          mode="decimal" minFractionDigits={2} maxFractionDigits={2} min={0} useGrouping={false}  prefix={this.props.value.symbol+' '}
          value={compfee}></InputNumber>
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
      <TabPanel id='events' header='Side Events'>
    <div className='sideevents'>
      <span className="p-input-icon-left add-button">
        <i className="pi pi-plus-circle"></i>
        <a onClick={()=>this.addEvent('one')}>Add</a>
      </span>
        {sidesSansComp.length && sidesSansComp.map((evt,idx) => {
            return (
                <SideEvent event={evt} start={start} end={end} symbol={this.props.value.symbol} key={idx} onChangeEl={this.onChangeEl} onRemoveEvent={this.removeEvent} />
            );
        })}
    </div>

    </TabPanel>
      <TabPanel id='roles' header='Event Roles'>
    <div className='eventroles'>
      <span className="p-input-icon-left add-button">
        <i className="pi pi-plus-circle"></i>
        <a onClick={()=>this.addEventRole('one')}>Add</a>
      </span>
        {this.props.value.roles && this.props.value.roles.length && this.props.value.roles.map((role,idx) => {
            return (
                <EventRole key={idx} role={role} users={this.props.users} onChangeEl={this.onChangeEl} onRemoveRole={this.removeRole} />
            );
        })}
    </div>

    </TabPanel>
  </TabView>
</Dialog>            
);
    }

}

