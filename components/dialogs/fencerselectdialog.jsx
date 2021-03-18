import React from 'react';
import { registration, fencer, upload_file } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { format_date_fe_short, date_to_category_num, date_to_category, jsonOutput } from "../functions";

export default class FencerSelectDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        // if the fencer is paying individual in any of the registrations,
        // set this marker to true
        var paysIndividual='N';
        if(this.props.value && this.props.value.registrations) {
            this.props.value.registrations.map((itm) => {
                if(itm.individual == 'Y') {
                    paysIndividual='Y';
                }
            });
        }

        this.state = {
            paysIndividual: paysIndividual,
            imageHash: Date.now()
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onChange) this.props.onChange(item);
    }

    eventsToObj = () => {
        var obj={};
        this.props.events.map((itm) => {
            obj["se_"+itm.id]=itm;
        });
        return obj;
    }

    findEvent = (sideeventid) => {
        for (var r in this.props.events) {
            var ev = this.props.events[r];
            if (ev.id == sideeventid) {
                return ev;
            }
        }
        return null;
    }

    findRegistration = (sideeventid) => {
        for(var r in this.props.value.registrations) {
            var reg = this.props.value.registrations[r];
            if(reg.sideevent == sideeventid) {
                return reg;
            }
        }
        return null;
    }

    insertRegistration = (itm) => {
        console.log("inserting or replacing registration ",itm);
        console.log("before insert, regs are ",this.props.value.registrations);
        var founditem = false;
        var regs = this.props.value.registrations.map((reg) => {
            if (reg.sideevent == itm.sideevent) {
                console.log("replacing ",reg);
                founditem = true;
                return itm;
            }
            else {
                return reg;
            }
        });
        if(!founditem) {
            console.log("inserting ",itm);
            regs.push(itm);
        }
        console.log("after insert regs are ",regs);
        this.props.value.registrations = regs;
        this.save(this.props.value);
    }

    removeRegistration = (itm) => {
        console.log("removing registration ",itm);
        var regs = this.props.value.registrations.filter((reg) => {
            return reg.sideevent != itm.sideevent;
        });
        console.log("setting registrations to ",regs);
        this.props.value.registrations = regs;
        this.save(this.props.value);
    }

    changePendingSituation = (itm, newstate) => {
        var founditem=false;
        var regs = this.props.value.registrations.map((reg) => {
            if(reg.sideevent == itm.sideevent) {
                reg.pending=newstate;
                founditem=true;
            }
            return reg;
        });
        console.log("new registrations after change to state "+ newstate,regs);
        this.props.value.registrations = regs;
        if(!founditem && ["saved","deleted"].includes(newstate) && itm.pending=="save") {
            // it was saved, but in the meantime it was removed again
            // or it was saved, then deleted and the delete call returned
            this.deleteRegistration(itm);
            this.removeRegistration(itm);
        }
        else {
            this.save(this.props.value);
        }
    }

    clearState = (itm) => {
        // find the current item
        var reg = this.findRegistration(itm.sideevent);
        // only clear state if the item is still on the end-of-backend-call state
        if(reg && reg.pending == "saved") {
            reg.pending="";
            this.insertRegistration(reg);
        }
    }

    saveRegistration = (reg) => {
        var eventObj=this.eventsToObj();
        var paysIndividual=this.state.paysIndividual;
        if(this.props.event.payments == "group") {
            paysIndividual = 'N';
        }
        else if(this.props.event.payments == "individual") {
            paysIndividual = 'Y';
        }
        if(eventObj["se_" + reg.sideevent]) {
            console.log("saving registration ",reg);
            registration('save', { 
                id: reg.id || -1,
                fencer: this.props.value.id, 
                event: this.props.event.id, 
                sideevent: reg.sideevent, 
                role: reg.role,
                individual: paysIndividual
            })
                .then((json) => {
                    console.log("registration saved, adjusting values");
                    this.loading(false);
                    var registration={};
                    if (json.data && json.data.model) {
                        console.log("merging saved model ",json.data.model);
                        registration = json.data.model;
                    }
                    var itm = Object.assign({}, registration, reg);
                    if(itm.fencer_data) {
                        delete itm.fencer_data;
                    }

                    // insert-or-replace the new registration
                    console.log("new registration is ",itm);
                    itm.pending="saved";
                    this.insertRegistration(itm); // insert and save new list

                    var self = this;
                    setTimeout(() => { self.clearState(itm); },2000);
            })
            .catch((err) => {
                this.changePendingSituation(reg,"error1");
                if (err.response && err.response.data && err.response.data.messages && err.response.data.messages.length) {
                    var txt = "";
                    for (var i = 0; i < err.response.data.messages.length; i++) {
                        txt += err.response.data.messages[i] + "\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
        }
    }

    deleteRegistration = (reg) => {
        if(reg.id) {
            console.log("calling delete api for registration ",reg);
            registration('delete', { id: reg.id })
                .then((json) => {
                    console.log("back from delete, setting pending situation and remove timeout");
                    this.loading(false);                    
                    this.changePendingSituation(reg,"deleted");
                    var self = this;
                    setTimeout(() => { self.removeRegistration(reg); },1000);
            })
            .catch((err) => {
                console.log("caught error on delete, setting error2 value",err);
                this.changePendingSituation(reg,"error2");
                if (  err.response 
                    && err.response.data
                    && err.response.data.messages 
                    && err.response.data.messages.length) {
                    var txt = "";
                    for (var i = 0; i < err.response.data.messages.length; i++) {
                        txt += err.response.data.messages[i] + "\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
        }
        else {
            // not saved yet, we can immediately remove it
            this.removeRegistration(reg);
        }
    }

    saveFencer = (item) => {
        fencer('save', {
            id: item.id,
            picture: item.picture
        })
            .then((json) => {
                var itm = Object.assign({}, this.props.value);
                if (json.data.model) {
                    itm = Object.assign({}, itm, json.data.model);
                }
                this.save(itm);
            })
            .catch ((err) => {
                if (err.response.data.messages && err.response.data.messages.length) {
                    var txt = "";
                    for (var i = 0; i < err.response.data.messages.length; i++) {
                        txt += err.response.data.messages[i] + "\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
    }

    onFileChange = (event) => {
        var selectedFile=event.target.files[0];
        upload_file("events",selectedFile,{
            fencer: this.props.value.id,
            event: this.props.event.id})
        .then((json) => {
            var itm = Object.assign({}, this.props.value);
            if (json.data.model) {
                itm = Object.assign({}, itm, json.data.model);
            }
            console.log("saving item ",itm);
            this.save(itm);
            this.setState({imageHash: Date.now()});
        })
        .catch((err) => {
            if (err.response.data.messages && err.response.data.messages.length) {
                var txt = "";
                for (var i = 0; i < err.response.data.messages.length; i++) {
                    txt += err.response.data.messages[i] + "\r\n";
                }
                alert(txt);
            }
            else {
                alert('Error storing the data. Please try again');
            }
        });
    }

    onCloseDialog = (event) => {
        // registration selection is done as the checkboxes are marked
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeFencer = (event) => {
        if (!event.target) return;
        var name = event.target.name;
        var value = event.value;
        switch (name) {
        case 'picture':
            // allow changes from Y->A, Y->R, A->R, R->A
            var oldstate=this.props.value.picture;
            if(  (oldstate=='Y' && (value=='A' || value=='R'))
              || (oldstate == 'A' && value=='R')
              || (oldstate == 'R' && value=='A')) {
                this.props.value.picture=value;
                this.saveFencer(this.props.value);
                this.save(this.props.value);
            }
            break;
        }
    }

    onChangeEl = (event) => {
        console.log("onchangeel ",event);
        if(!event.target) return;
        var els = event.target.name.split('-');
        var name=event.target.name;
        var value=event.value;
        if(event.checked) value=event.checked;
        var id = name;
        if (els.length > 1) {
            name = els[0];
            id = els[1];
        }

        var item=this.props.value;
        var selectThisOne=false;
        var changeDone=false;
        switch(name) {
        case 'paysIndividual':
            console.log("setting paysIndividual");
            this.setState({paysIndividual: value});
            break;
        case 'select':
            selectThisOne=event.checked;
            // selecting a role selects the event as well
            // so we fall through
        case 'role':
            if(name == "role") {
                selectThisOne=true; // selecting a role selects the event
            }
            console.log("selecting or setting role for id " + id);
            var selectedItem=this.findRegistration(id);
            var selectedEvent=this.findEvent(id);

            if(selectThisOne) {
                console.log("selecting event");
                if(selectedItem === null) {
                    // create a new registration for this item
                    console.log("creating new event");
                    selectedItem = {
                        event: this.props.event.id,
                        sideevent: id,
                        individual: this.state.paysIndividual,
                        role: selectedEvent ? selectedEvent.default_role : 0 
                        // date etc are all filled in during save
                    }
                }                
                if(name == "role") {
                    console.log("setting role of this event");
                    selectedItem.role = value;
                }
                selectedItem.pending = "save";                
                this.saveRegistration(selectedItem); // send to backend
            }
            else {
                console.log("not adding event, this is an uncheck");
                selectedItem.pending = "delete";
                this.deleteRegistration(selectedItem); // send to backend
            }
            this.insertRegistration(selectedItem); // add to list and change prop value
            changeDone = true;
            break;
        }
        this.save(item);
    }

    render() {
        if(!this.props.value) {
            return (null);
        }

        var footer=(<div>
        <Button label="Close" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        var rolesForAthlete = this.props.roles.slice();
        rolesForAthlete.splice(0,0,{name:'Athlete',id:"0",type:1});

        var rolesForSide = this.props.roles.slice();
        rolesForSide.splice(0, 0, { name: "Participant", id: "0", type: 1 });

        // HoD can only set HoD roles, the rest can set anything
        // SideRoles cannot be set by the HoD, only by the organisation
        var roles = this.props.roles.filter((itm) => {
            return evfranking.eventcap !== "hod" || parseInt(itm.type) == 1;
        });
        rolesForAthlete = rolesForAthlete.filter((itm) => {
            return evfranking.eventcap !== "hod" || parseInt(itm.type) == 1;
        });
        var roleById = {};
        rolesForAthlete.map((role) => {
            roleById["r" + role.id] = role;
        });

        var selectedevents={};
        this.props.value.registrations.map((ev) => {
            var key="k"+ev.sideevent;
            selectedevents[key]=ev;
        });
        
        var mycatname = date_to_category(this.props.value.birthday, this.props.event.opens);

        // these network states should still consider the event as selected
        var goodstates=["save","saved","error2",""];

        var payments = (null);
        if (this.props.event.payment == "all") {
            // payment method already set by the organiser
            var payment = [{ name: 'Individual', code: 'Y' }, { name: 'As group', code: 'N' }];
            payments = (<div>
                <label>Payment</label>
                <div className='input'>
                    <Dropdown appendTo={document.body} name='paysIndividual' optionLabel="name" optionValue="code" value={this.state.paysIndividual} options={payment} placeholder="Payment" onChange={this.onChangeEl} />
                </div>
            </div>);
        }

        return (<Dialog header="Register Fencer" position="center" visible={this.props.display} className="fencer-select-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>{ this.props.value.name }, {this.props.value.firstname }</h5>
    <h5>Birthyear: { this.props.value.birthyear } Gender: {this.props.value.gender == 'M' ? 'Man': 'Woman'} Category: {mycatname}</h5>
    {payments}
    <div>
        <label>Events</label>
        <div className='input'>
            <table className='fencer-select-events'>
            {this.props.events.map((ev,idx) => {
                console.log(ev);

                var selected_event = selectedevents["k"+ev.id];
                var is_registered=false;
                if(  selected_event 
                  && selected_event.event 
                  && (  !selected_event.pending 
                     || goodstates.includes(selected_event.pending)
                     )
                ) {
                    console.log("fencer is registered");
                    is_registered=true;
                }

                var registered_role=is_registered ? selected_event.role : -1;// -1 causes an empty selection
                var is_error = selected_event && (selected_event.pending == "error1" || selected_event.pending == "error2");
                var is_success = selected_event && (selected_event.pending == "saved" || selected_event.pending == "deleted");
                var is_saving = selected_event && (selected_event.pending == "save" || selected_event.pending == "delete");

                // it can happen that a person of a country is registered for an organiser
                // role by the organisation. In that case, we mark the person as 
                // unchangeable
                // This only applies to competition events, where we can actually select a role
                var canupdate=true;
                var actualRole=null;
                if(is_registered && ev.competition && !roleById["r" +selected_event.role]) {
                    canupdate=false;
                    this.props.roles.map((role) => {
                        if(role.id == selected_event.role) {
                            actualRole=role;
                        }
                    });
                }

                return (
                <tbody key={idx} className={ev.is_athlete_event ? "athlete" : "non-athlete"}>
                <tr>
                    <td className='sideevent-title'>
                        {ev.title}
                    </td>
                    <td>{format_date_fe_short(ev.starts)}</td>
                    <td>
                        {canupdate && <Checkbox name={"select-" + ev.id} onChange={this.onChangeEl} checked={is_registered}/>}
                        {!canupdate && (<span>X</span>)}
                    </td>
                    <td>
                    {ev.competition && !canupdate && (<span>
                        {actualRole && actualRole.name}
                        {!actualRole && "??"}
                    </span>)}
                    {canupdate && ev.is_athlete_event && (
                        <Dropdown name={'role-'+ev.id} appendTo={document.body} optionLabel="name" optionValue="id" value={registered_role} options={rolesForAthlete} onChange={this.onChangeEl} />
                    )}
                    {canupdate && !ev.is_athlete_event && !ev.is_sideevent && (
                        <Dropdown name={'role-' + ev.id} appendTo={document.body} optionLabel="name" optionValue="id" value={registered_role} options={roles} onChange={this.onChangeEl} />
                    )}
                    {canupdate && ev.is_sideevent && evfranking.eventcap!="hod" && (
                        <Dropdown name={'role-' + ev.id} appendTo={document.body} optionLabel="name" optionValue="id" value={registered_role} options={rolesForSide} onChange={this.onChangeEl} />
                    )}
                    </td>
                    <td className="state-icons">
                        {is_error && (
                            <i className='pi pi-times'></i>
                        )}
                        {is_success && (
                            <i className='pi pi-check'></i>
                        )}
                        {is_saving && (
                            <i className='pi pi-cloud-upload'></i>
                        )}
                        {!is_error && !is_success && !is_saving && (<span>&nbsp;</span>)}
                    </td>
                </tr>
                {ev.description.length > 0 && (
                <tr className='sideevent-description'>
                    <td colSpan={4}>
                        {ev.description}
                    </td>
                </tr>
                )}
                </tbody>
            ); })}
            </table>
        </div>
        {this.renderPicture()}
    </div>
</Dialog>
);
    }

    renderPicture () {
        console.log("rendering picture for ",this.props.value);
        // display the accreditation photo
        // anyone that can view this dialog can upload a better image
        var canapprove=["accreditor","organiser"].includes(evfranking.eventcap) && this.props.value.picture!='N';
        var approvestates=[{
            name: "Newly uploaded",
            id: "Y"
        },{
            name: "Approved",
            id: "A"
        },{
            name: "Request replacement",
            id: "R"
        },{
            name: "None available",
            id: "N"
        }];
        var picstate = this.props.value.picture;
        if(!['Y','N','R','A'].includes(picstate)) {
            picstate='N';
        }
        return (<div>
            <label className='header'>Accreditation Photo</label>
            <div>
            {['Y','A','R'].includes(this.props.value.picture) && (
                <div className='accreditation'>
                  <img src={evfranking.url + "&picture="+this.props.value.id + "&nonce=" + evfranking.nonce + "&event=" + this.props.event.id + '&hash='+this.state.imageHash}></img>
                </div>
            )}
            <div className='textcenter'>
              <input type="file" onChange={this.onFileChange} />
            </div>
            {canapprove && (
                <div>
                  <Dropdown name={'picture'} appendTo={document.body} optionLabel="name" optionValue="id" value={picstate} options={approvestates} onChange={this.onChangeFencer} />
                </div>
            )}
            </div>
        </div>);
    }
}

