import React from 'react';
import { registration, accreditation } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { parse_team_for_number, format_date_fe_short, is_valid, date_to_category, parse_net_error, 
        is_hod, is_organisation, is_sysop, is_organiser, is_accreditor, create_roleById } from "../../functions";

// the fencer-select-dialog displays all events a fencer can be a part of.
// We now only select roles for the overall event, or Athlete/Participant roles for
// the side-events. This means we only need to display the events the fencer can
// actually partake in and filter out the rest. No need to set a specific role in
// such a case either.

export default class FencerSelectDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
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

    findRegistration = (sideeventid, roleid) => {
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
        var regs = this.props.value.registrations.filter((reg) => {
            if(is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                return false;
            }
            if(!is_valid(reg.sideevent) && !is_valid(itm.sideevent) && reg.role == itm.role) {
                return false;
            }
            return true;
        });
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
        var reg = this.findRegistration(itm.sideevent, itm.role);        
        // only clear state if the item is still on the end-of-backend-call state
        if(reg && (reg.pending == "saved" || reg.pending=="deleted")) {
            reg.pending="";
            this.insertRegistration(reg);
        }
    }

    saveRegistration = (reg) => {
        // set the correct payment information:
        // For a HoD: follow the event restrictions
        var payment=this.props.value.defaultPayment;
        if(is_sysop()) {
            // system administrators can select any payment type at the top.
            // Just copy that
        }
        else if (is_organisation()) {
            // organisers can invite people and pay for their costs
            // payment is set to 'O' in that case.
            // However, only do this for non-athlete roles.
            // Competition selection is ruled out in this dialog anyway
            if(is_valid(reg.role)) {
                payment='O';
            }
            if(!['G','I','O'].includes(payment)) {
                payment='O';
            }
        }
        else {
            // for HoD or lesser, follow the event restrictions
            if(this.props.basic.event.payments == "group") {
                payment = 'G';
            }
            else if(this.props.basic.event.payments == "individual") {
                payment = 'I';
            }
            // else we can select, but it should be G or I
            if (payment != 'G' && payment != 'I') {
                payment = 'G'
            }
        }

        var se = parseInt(reg.sideevent);
        if(!is_valid(se)) {
            se=null;
        }
        registration('save', { 
            id: reg.id || -1,
            fencer: this.props.value.id, 
            event: this.props.basic.event.id, 
            sideevent: se,
            role: reg.role,
            team: reg.team,
            payment: payment,
            country: this.props.country.id
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
                    reg.id=-1;  // avoid reusing the now removed id
                    this.changePendingSituation(reg,"deleted");
                    var self = this;
                    setTimeout(() => { 
                        // keep the network interaction icons for a second
                        self.removeRegistration(reg); 
                    },1000);
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

    onCloseDialog = (event) => {
        // registration selection is done as the checkboxes are marked
        this.close();
    }

    onGenerate = (event) => {
        if (is_sysop() || is_organiser() || is_accreditor()) {
            // regenerate all accreditations for this fencer
            if(this.props.reloadAccreditations) this.props.reloadAccreditations();
            accreditation("generateone",{event: this.props.basic.event.id,fencer: this.props.value.id})
                .then((json) => {
                    if(json) {
                        if(this.props.reloadAccreditations) this.props.reloadAccreditations(this.props.basic.event.id, this.props.value.id);
                    }
                });
        }
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
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
            item.defaultPayment = value;
            break;
        case 'teamselect':
        case 'select':
            if(name === 'select') selectThisOne=event.checked;
            else selectThisOne = (value != "0"); 

            var selectedItem=this.findRegistration(id); // this is the side-event registration

            if(selectThisOne) {
                if(selectedItem === null) {
                    // create a new registration for this item
                    selectedItem = {
                        event: this.props.basic.event.id,
                        sideevent: id,
                        individual: this.props.payment,
                        //role: selectedEvent ? selectedEvent.default_role : 0 
                        role: 0 // only participate in an event, no event-specific roles
                        // date etc are all filled in during save
                    }
                }
                // check that value is a string
                if(typeof(event.value) == "string") {
                    selectedItem.team = event.value; // only valid for teams
                }

                selectedItem.pending = "save";                
                this.saveRegistration(selectedItem); // send to backend
            }
            else {
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
                    event: this.props.basic.event.id,
                    sideevent: -1,
                    individual: this.props.payment
                }
            }
            if(!value) {
                selectedItem.pending = "delete";
                this.deleteRegistration(selectedItem);
            }
            else {
                selectedItem.role=id;
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

    downloadAccreditation = (accr) => {
        if (is_sysop() || is_organiser() || is_accreditor()) {
            var href = evfranking.url + "&download=accreditation&id="+accr.id;
            href += "&mainevent=" + this.props.basic.event.id + "&nonce=" + evfranking.nonce;
            window.open(href);
        }
    }

    render() {
        if(!this.props.value) {
            return (null);
        }

        var genbutton=(null);
        if (is_sysop() || is_organiser() || is_accreditor()) {
            genbutton = (<Button label="Generate" icon="pi pi-check" className="p-button-raised" onClick={this.onGenerate} />);
        }
        var footer=(<div>
        {genbutton}
        <Button label="Close" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        var selectedevents = {};
        var overallroles = [];
        this.props.value.registrations.map((ev) => {
            if(!is_valid(ev.sideevent)) {
                overallroles.push(ev);
            }
            else {
                var key = "k" + ev.sideevent;
                selectedevents[key] = ev;
            }
        });

        var mycatname = date_to_category(this.props.value.birthday, this.props.basic.event.opens);

        var payments = (null);
        // if inviting as organisation for organisation tasks, allow only O or E
        if(is_organisation() && !is_valid(this.props.country.id)) {
            var payment = [
                { name: 'By Organisation', code: 'O' },
            ];
            if(is_sysop()) {
                // system administrators can also assign to the EVF
                payment.push({ name: 'By EVF', code: 'E' });
            }
            payments = (<div className='clearfix'>
                <label>Payment</label>
                <div className='input'>
                    <Dropdown appendTo={document.body} name='paysIndividual' optionLabel="name" optionValue="code" value={this.props.value.defaultPayment} options={payment} placeholder="Payment" onChange={this.onChangeEl} />
                </div>
            </div>);
        }
        else if (this.props.basic.event.payments == "all") {            
            // payments can be selected by the user, but only choose between I or G
            // this is also for event organisers that are registering for a specific country
            var payment = [{ name: 'Individual', code: 'I' }, { name: 'As group', code: 'G' }];
            payments = (<div className='clearfix'>
                <label>Payment</label>
                <div className='input'>
                    <Dropdown appendTo={document.body} name='paysIndividual' optionLabel="name" optionValue="code" value={this.props.value.defaultPayment} options={payment} placeholder="Payment" onChange={this.onChangeEl} />
                </div>
            </div>);
        }

        // create a list of valid team names based on all available teams
        // This list is specific for each competition sideevent of category team
        var cfg = this.props.basic.event.config;
        var allow_more_teams = (cfg && cfg.allow_more_teams) ? true : false;
        var validteams={};
        if(allow_more_teams) {
            this.props.events.map((ev) => {
                if(ev.category && ev.category.type == 'T') {
                    var key = "k" + ev.id;
                    var validoptions=[{value:"0", text:'No'}];
                    var highestnum=0;
                    if(this.props.teams && this.props.teams[key] && this.props.teams[key].length > 0) {
                        this.props.teams[key].map((teamkey) => {
                            validoptions.push({value: teamkey, text: teamkey});
                            highestnum = parse_team_for_number(teamkey);
                        });
                    }                    
                    highestnum+=1;
                    var leadname=ev.category.name;
                    validoptions.push({value: leadname + " " + highestnum, text: 'New team'});
                    validteams[key]=validoptions;
                }
            });
        }

        return (<Dialog baseZIndex={100000} header="Register Fencer" position="center" visible={this.props.display} className="fencer-select-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>{ this.props.value.name }, {this.props.value.firstname }</h5>
    {this.props.country.id > 0 && (<h5>
        Year of birth: { this.props.value.birthyear } Gender: {this.props.value.gender == 'M' ? 'M': 'W'} Category: {mycatname}
    </h5>)}
    {payments}
    {this.renderEvents(selectedevents, validteams, allow_more_teams)}
    {this.renderRoles(overallroles)}
    {this.renderAccreditation()}
</Dialog>
);
    }

    renderRoles(overallroles) {
        // filter out valid roles for the capabilities
        var as_organiser = !this.props.country || !is_valid(this.props.country.id);
        var roles = this.props.basic.roles.filter((itm) => {
            // if registering for a specific country, or if the user is a HoD, allow only Country roles
            if ((!as_organiser || is_hod()) && itm.org == 'Country') return true;
            // if registering as organiser, allow only Org roles for organisers
            if (as_organiser && is_organisation() && (itm.org == 'Org')) return true;
            // if registering as organiser, allow all non-Country roles
            if (as_organiser && is_sysop() && (itm.org != 'Country')) return true; // allow all organisation roles
            return false;
        });
        roles.sort((a, b) => {
            if (a.org == 'Country' && b.org != 'Country') return -1;
            if (b.org == 'Country' && a.org != 'Country') return 1;
            if (a.org == 'Org' && b.org != 'Org') return -1;
            if (b.org == 'Org' && a.org != 'Org') return 1;

            return a.name > b.name;
        });

        var roleById = create_roleById(roles);
        var allRolesById = this.props.basic.rolesById;

        var selectedRolesById = {};
        overallroles.map((reg) => {
            selectedRolesById['r' + reg.role] = reg;

            // copy static roles as well
            if (!roleById['r' + reg.role]) {
                roleById['r' + reg.role] = allRolesById['r' + reg.role];
            }
        });

        return (
        <div className='clearfix'>
            <label className='wide'>Support Roles</label>
            <div className='input'>
                <table className='fencer-select-events'>
                    <tbody>
                {roles.map((role,idx) => {
                    var isstatic = this.isStaticRole(role.id, roleById);
                    var reg = selectedRolesById['r' + role.id];
                    var name = role.name;

                    var is_registered = reg != undefined;
                    var is_error = reg && (reg.pending == "error1" || reg.pending == "error2");
                    var is_success = reg && (reg.pending == "saved" || reg.pending == "deleted");
                    var is_saving = reg && (reg.pending == "save" || reg.pending == "delete");
                    var id = "fullrole-" + role.id;
                    return (
                    <tr key={'rl-'+idx}>
                        <td>
                        {!isstatic && (
                            <Checkbox inputId={id} name={id} onChange={this.onChangeEl} checked={is_registered} disabled={is_saving}/>
                        )}
                        </td>
                        <td>
                            <label htmlFor={id}>{name}</label>
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
                        <td className='expand-cell'></td>
                    </tr>);
                })}
                    </tbody>                    
                </table>
            </div>
        </div>
        );
    }

    renderEvents (selectedevents, validteams, allow_more_teams) {
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

        if (events.length == 0) {
            return (null);
        }

        return (<div className='clearfix'>
        <label className='wide'>Competitions and Side Events</label>
        <div className='input'>
            <table className='fencer-select-events'>
            {events.map((ev,idx) => {
                var selected_event = selectedevents["k"+ev.id];
                
                var is_registered=false;
                var of_team = "0";
                if(  selected_event 
                  && selected_event.event 
                  && (  !selected_event.pending 
                     || goodstates.includes(selected_event.pending)
                     )
                ) {
                    is_registered=true;
                    of_team = selected_event.team ? selected_event.team : "0";
                }

                var ourteams = validteams["k"+ev.id] || [];
                var is_error = selected_event && (selected_event.pending == "error1" || selected_event.pending == "error2");
                var is_success = selected_event && (selected_event.pending == "saved" || selected_event.pending == "deleted");
                var is_saving = selected_event && (selected_event.pending == "save" || selected_event.pending == "delete");
                var id = 'select-' + ev.id;
                if (allow_more_teams) {
                    id = "teamselect-" + ev.id;
                }
                //console.log("allowing more teams ",allow_more_teams);
                return (
                <tbody key={idx}>
                <tr>
                    <td>
                        {!ev.is_team_event && (<Checkbox inputId={id} name={id} onChange={this.onChangeEl} checked={is_registered} disabled={is_saving}/>)}
                        {allow_more_teams && ev.is_team_event && (<Dropdown inputId={id} name={id} onChange={this.onChangeEl} appendTo={document.body} optionLabel="text" optionValue="value" value={of_team} options={ourteams} />)}
                        {!allow_more_teams && ev.is_team_event && (<Checkbox inputId={id} name={id} onChange={(e)=> this.onChangeEl({target:e.target, checked: e.checked, value: ev.category.name})} checked={is_registered}  disabled={is_saving}/>)}
                    </td>
                    <td><label htmlFor={id}>{format_date_fe_short(ev.starts)}</label></td>
                    <td className='sideevent-title'>
                        <label htmlFor={id}>
                            {ev.weapon ? ev.weapon.name : ev.title }
                        </label>
                    </td>
                    <td className='sideevent-title'>
                        <label htmlFor={id}>
                            {ev.category && ev.category.name}
                        </label>
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
                    <td className='expand-cell'></td>
                </tr>
                {ev.description.length > 0 && (
                <tr className='sideevent-description'>
                    <td colSpan={6}>
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

    renderAccreditation() {
        if(is_sysop() || is_organiser() || is_accreditor()) {
            return (<div className='clearfix'>
            <label>Badges</label>
            <div>
                <table className='compact'>
                  <tbody>
                {this.props.accreditations.map((a,idx)=> (
                    <tr key={idx}>
                        <td>{a.title}</td>
                        <td>&nbsp;&nbsp;
                            {a.has_file && (
                                <span className='pi pi-icon pi-file-pdf' onClick={()=>this.downloadAccreditation(a)}></span>
                            )}
                            {!a.has_file && a.is_dirty && (
                                <span className='pi pi-icon pi-clock'></span>
                            )}
                            {!a.has_file && !a.is_dirty && (
                                <span className='pi pi-icon pi-times-circle'></span>
                            )}                            
                        </td>
                    </tr>
                ))}
                  </tbody>
                </table>
            </div>
        </div>
            );
        }
        else {
            return (null);
        }
    }
}

