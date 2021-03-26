import React from 'react';
import { registration, fencer, upload_file } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { format_date_fe_short, is_valid, date_to_category, parse_net_error, is_hod, is_organiser, is_sysop } from "../functions";

// the fencer-select-dialog displays all events a fencer can be a part of.
// We now only select roles for the overall event, or Athlete/Participant roles for
// the side-events. This means we only need to display the events the fencer can
// actually partake in and filter out the rest. No need to set a specific role in
// such a case either.

export default class FencerSelectDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        // if the fencer is paying individual in any of the registrations,
        // set this marker to true
        var paysIndividual='G';
        if (is_organiser() && (!this.props.country || !is_valid(this.props.country.id))) paysIndividual = 'O';
        if (is_sysop() && (!this.props.country || !is_valid(this.props.country.id))) paysIndividual='E';
        if(this.props.value && this.props.value.registrations) {
            this.props.value.registrations.map((itm) => {
                if(itm.individual == 'I') {
                    paysIndividual='I';
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

    findRegistration = (sideeventid, roleid) => {
        console.log("finding registration for "+sideeventid);
        for(var r in this.props.value.registrations) {
            var reg = this.props.value.registrations[r];
            if(is_valid(reg.sideevent) && reg.sideevent == sideeventid) {
                return reg;
            }
            // if either is non-existing, return this registration as well if the roles match
            if(!is_valid(sideeventid) && !is_valid(reg.sideevent)) {
                if(!roleid || roleid == reg.role) {
                    return reg;
                }
            }
        }
        return null;
    }

    insertRegistration = (itm) => {
        var founditem = false;
        var regs = this.props.value.registrations.map((reg) => {
            if (is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                founditem = true;
                return itm;
            }
            else if(!is_valid(reg.sideevent) && !is_valid(itm.sideevent)) {
                // only replace if the roles match
                if(reg.role == itm.role) {
                    founditem=true;
                    return itm;
                }
            }
            return reg;
        });
        if(!founditem) {
            regs.push(itm);
        }
        this.props.value.registrations = regs;
        this.save(this.props.value);
    }

    removeRegistration = (itm) => {
        console.log("removing registration ",itm);
        var regs = this.props.value.registrations.filter((reg) => {
            if(is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                return false;
            }
            if(!is_valid(reg.sideevent) && !is_valid(itm.sideevent) && reg.role == itm.role) {
                return false;
            }
            return true;
        });
        console.log("setting registrations to ",regs);
        this.props.value.registrations = regs;
        this.save(this.props.value);
    }

    changePendingSituation = (itm, newstate) => {
        var founditem=false;
        var regs = this.props.value.registrations.map((reg) => {
            if (is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                reg.pending=newstate;
                founditem=true;
            }
            if (!is_valid(reg.sideevent) && !is_valid(itm.sideevent) && reg.role == itm.role) {
                reg.pending = newstate;
                founditem = true;
            }
            return reg;
        });

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
        // set the correct payment information:
        // For a HoD: follow the event restrictions
        var payment=this.state.paysIndividual;
        if(is_sysop()) {
            // system administrators can select any payment type at the top.
            // Just copy that
        }
        else if (is_organiser()) {
            // organisers can invite people and pay for their costs
            // payment is set to 'O' in that case.
            // However, only do this for non-athlete roles.
            // Competition selection is ruled out in this dialog anyway
            if(is_valid(reg.role) && parseInt(reg.role) > 0) {
                payment='O';
            }
            if(!['G','I','O'].includes(payment)) {
                payment='O';
            }
        }
        else {
            // for HoD or lesser, follow the event restrictions
            if(this.props.event.payments == "group") {
                payment = 'G';
            }
            else if(this.props.event.payments == "individual") {
                payment = 'I';
            }
            // else we can select, but it should be G or I
            if (payment != 'G' || payment != 'I') {
                payment = 'G'
            }
        }

        var se = parseInt(reg.sideevent);
        if(se <= 0) {
            se=null;
        }
        registration('save', { 
            id: reg.id || -1,
            fencer: this.props.value.id, 
            event: this.props.event.id, 
            sideevent: se,
            role: reg.role,
            payment: payment
        })
            .then((json) => {
                this.loading(false);
                var registration={};
                if (json.data && json.data.model) {
                    registration = json.data.model;
                }
                var itm = Object.assign({}, registration, reg);
                if(itm.fencer_data) {
                    delete itm.fencer_data;
                }

                // insert-or-replace the new registration
                itm.pending="saved";
                console.log("insert-or-replace saved item ",itm);
                this.insertRegistration(itm); // insert and save new list

                var self = this;
                setTimeout(() => { self.clearState(itm); },2000);
            })
            .catch((err) => {
                this.changePendingSituation(reg,"error1");
                parse_net_error(err);
            });
    }

    deleteRegistration = (reg) => {
        if(reg.id) {
            registration('delete', { id: reg.id, fencer: this.props.value.id })
                .then((json) => {
                    this.loading(false);                    
                    this.changePendingSituation(reg,"deleted");
                    var self = this;
                    setTimeout(() => { self.removeRegistration(reg); },1000);
            })
            .catch((err) => {
                this.changePendingSituation(reg,"error2");
                parse_net_error(err);
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
            .catch ((err) => parse_net_error(err));
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
        .catch((err) => parse_net_error(err));
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
                // note: this is dead code, as we no longer select roles
                // for an event. You can only participate (role=0)
                selectThisOne=true; // selecting a role selects the event
            }
            console.log("selecting or setting role for id " + id);
            var selectedItem=this.findRegistration(id); // this is the side-event registration
            //var selectedEvent=this.findEvent(id); // used for the default role

            if(selectThisOne) {
                console.log("selecting event");
                if(selectedItem === null) {
                    // create a new registration for this item
                    console.log("creating new event");
                    selectedItem = {
                        event: this.props.event.id,
                        sideevent: id,
                        individual: this.state.paysIndividual,
                        //role: selectedEvent ? selectedEvent.default_role : 0 
                        role: 0 // only participate in an event, no event-specific roles
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
            break;
        case 'fullrole':
            // select a role for the entire event. Find the registration (if any) for 
            // a non-existing side-event with this specific role. The original role is
            // encoded as the 'id' value
            var selectedItem = this.findRegistration(-1,id);
            if (selectedItem === null) {
                // create a new registration for this item
                selectedItem = {
                    event: this.props.event.id,
                    sideevent: -1,
                    individual: this.state.paysIndividual
                }
            }
            if(value < 0) {
                selectedItem.pending = "delete";
                this.deleteRegistration(selectedItem);
            }
            else {
                selectedItem.role=value;
                selectedItem.pending = "save";
                this.saveRegistration(selectedItem);
            }
            this.insertRegistration(selectedItem); // add to list and change prop value
            break;
        }
        this.save(item);
    }

    isStaticRole = (selectedrole, roleById) => {
        return is_valid(selectedrole) && !roleById["r" + selectedrole];
    }

    render() {
        if(!this.props.value) {
            return (null);
        }

        var footer=(<div>
        <Button label="Close" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        var selectedevents = {};
        var overallroles = [];
        var foundEmpty=false;
        this.props.value.registrations.map((ev) => {
            console.log("looking at registration ",ev);
            if(ev.pending!="delete" && ev.pending!="deleted") {
                if(!is_valid(ev.sideevent)) {
                    overallroles.push(ev);
                    if(!is_valid(ev.role)) {
                        foundEmpty=true;
                    }
                }
                else {
                    var key = "k" + ev.sideevent;
                    selectedevents[key] = ev;
                }
            }
        });
        if(!foundEmpty) {
            // add an additional empty role to allow setting additional roles
            overallroles.push({
                id:-1,
                role: 0
            });
        }

        // filter out valid roles for the capabilities
        var roles = this.props.roles.filter((itm) => {
            if (is_hod() && parseInt(itm.type) == 1) return true;
            if (is_organiser() && parseInt(itm.type) == 2) return true;
            if (is_sysop()) return true;
        });

        var roleById = {};
        roles.map((role) => {
            roleById["r" + role.id] = role;
        });

        var allRolesById = {};
        this.props.roles.map((role) => {
            allRolesById["r" + role.id] = role;
        });

        // add a None role
        roles.splice(0, 0, { id: -1, name: "None" });

        var mycatname = date_to_category(this.props.value.birthday, this.props.event.opens);

        var payments = (null);
        if(is_organiser()) {
            var payment = [
                { name: 'Individual', code: 'I' },
                { name: 'As group', code: 'G' },
                { name: 'By Organisation', code: 'O' },
            ];
            if(is_sysop()) {
                // system administrators can also assign to the EVF
                payment.push({ name: 'By EVF', code: 'E' });
            }
            payments = (<div>
                <label>Payment</label>
                <div className='input'>
                    <Dropdown appendTo={document.body} name='paysIndividual' optionLabel="name" optionValue="code" value={this.state.paysIndividual} options={payment} placeholder="Payment" onChange={this.onChangeEl} />
                </div>
            </div>);
        }
        else if (this.props.event.payment == "all") {
            // payments can be selected by the user, but only choose between I or G
            var payment = [{ name: 'Individual', code: 'I' }, { name: 'As group', code: 'G' }];
            payments = (<div>
                <label>Payment</label>
                <div className='input'>
                    <Dropdown appendTo={document.body} name='paysIndividual' optionLabel="name" optionValue="code" value={this.state.paysIndividual} options={payment} placeholder="Payment" onChange={this.onChangeEl} />
                </div>
            </div>);
        }

        return (<Dialog header="Register Fencer" position="center" visible={this.props.display} className="fencer-select-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>{ this.props.value.name }, {this.props.value.firstname }</h5>
    {this.props.country.id > 0 && (<h5>
        Birthyear: { this.props.value.birthyear } Gender: {this.props.value.gender == 'M' ? 'Man': 'Woman'} Category: {mycatname}
    </h5>)}
    {payments}
    {this.renderEvents(selectedevents)}
    {this.renderRoles(overallroles, roles, roleById, allRolesById)}
    {this.renderPicture()}
</Dialog>
);
    }

    renderRoles(overallroles, roleoptions, roleById, allRolesById) {
        console.log("rendering support roles for registrations ",overallroles);
        return (
        <div>
            <label>Support Roles</label>
            <div className='input'>
                <table>
                    <tbody>
                {overallroles.map((reg,idx) => {
                    var isstatic = this.isStaticRole(reg.role, roleById);
                    var name="";
                    if(isstatic && allRolesById["r"+reg.role]) {
                        name = allRolesById["r"+reg.role].name;
                    }

                    var is_error = reg.pending == "error1" || reg.pending == "error2";
                    var is_success = reg.pending == "saved" || reg.pending == "deleted";
                    var is_saving = reg.pending == "save" || reg.pending == "delete";

                    return (
                    <tr key={'rl-'+idx}>
                        <td>
                        {isstatic && (
                            <span>{name}</span>
                        )}
                        {!isstatic && (
                            <Dropdown name={'fullrole-' + reg.role} appendTo={document.body} optionLabel="name" optionValue="id" value={reg.role} options={roleoptions} onChange={this.onChangeEl} />
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
                    </tr>);
                })}
                    </tbody>                    
                </table>
            </div>
        </div>
        );
    }

    renderEvents (selectedevents) {
        // these network states should still consider the event as selected
        var goodstates=["save","saved","error2",""];

        // filter out all events this fencer cannot participate in due to category and gender mismatch
        // If we are looking from an Organisation view, do not allow selection of events
        var events = this.props.events.filter((ev) => {
            if (!(ev.is_athlete_event || ev.is_sideevent)) return false;

            // if we are organisation, allow selecting the side-events, but not the competitions
            if(this.props.country.id <= 0 && !ev.is_sideevent) return false;

            return true;
        });

        return (<div>
        <label>Events</label>
        <div className='input'>
            <table className='fencer-select-events'>
            {events.map((ev,idx) => {
                var selected_event = selectedevents["k"+ev.id];
                
                var is_registered=false;
                if(  selected_event 
                  && selected_event.event 
                  && (  !selected_event.pending 
                     || goodstates.includes(selected_event.pending)
                     )
                ) {
                    is_registered=true;
                }

                var is_error = selected_event && (selected_event.pending == "error1" || selected_event.pending == "error2");
                var is_success = selected_event && (selected_event.pending == "saved" || selected_event.pending == "deleted");
                var is_saving = selected_event && (selected_event.pending == "save" || selected_event.pending == "delete");

                return (
                <tbody key={idx}>
                <tr>
                    <td className='sideevent-title'>
                        {ev.title}
                    </td>
                    <td>{format_date_fe_short(ev.starts)}</td>
                    <td>
                        <Checkbox name={"select-" + ev.id} onChange={this.onChangeEl} checked={is_registered}/>
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
            {events.length == 0 && (
                <tbody>
                    <tr>
                        <td>No eligible events found</td>
                    </tr>
                </tbody>
            )}
            </table>
        </div>
    </div>);
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

