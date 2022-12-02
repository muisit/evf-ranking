import React from 'react';
import { registration } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { InputSwitch } from 'primereact/inputswitch';
import { Checkbox } from 'primereact/checkbox';
import { is_valid, parse_net_error, is_organiser, is_sysop, is_accreditor } from "../../functions";

// the fencer-select-dialog displays all events a fencer can be a part of.
// We now only select roles for the overall event, or Athlete/Participant roles for
// the side-events. This means we only need to display the events the fencer can
// actually partake in and filter out the rest. No need to set a specific role in
// such a case either.

export default class FencerAccreditationDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {}
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

    setConvenienceData = () => {
        this.roleById = this.props.basic.rolesById || {};
        this.wpnById = this.props.basic.weaponsById || {};
        this.catById = this.props.basic.categoriesById || {};
        this.cmpById = this.props.basic.competitionsById || {};
        this.eventById = {};
        this.props.events.map((se) => {
            var key = "s" + se.id;
            this.eventById[key] = se;
        });
    }


    changePendingSituation = (itm, newstate, oldstates) => {
        if(this.props.value && this.props.value.registration && this.props.value.registration.registrations) {
            var regs = this.props.value.registration.registrations.map((reg) => {
                if (reg.id==itm.id) {
                    if(!oldstates || !reg.pending || oldstates.includes(reg.pending)) {
                        reg.pending=newstate;
                    }
                }
                return reg;
            });
            this.save(regs);
        }
    }

    clearState = (itm) => {
        this.changePendingSituation(itm,"",["saved"]);
    }

    saveRegistration = (reg) => {
        this.changePendingSituation(reg, "save", [""]);
        registration('save', { 
            id: reg.id || -1,
            fencer: this.props.value.id, 
            event: this.props.basic.event.id, 
            sideevent: reg.sideevent,
            state: reg.state
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
                this.changePendingSituation(itm,"saved",["save"]);

                var self = this;
                setTimeout(() => { self.changePendingSituation(itm,"",["saved"]); },2000);
            })
            .catch((err) => {
                this.changePendingSituation(reg,"error1");
                parse_net_error(err);
            });
    }

    onCloseDialog = (event) => {
        // registration selection is done as the checkboxes are marked
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChange = (reg, state) => {
        reg.state=state;
        this.saveRegistration(reg);
    }

    render() {
        if(!this.props.value) return (null);
        if (!is_sysop() && !is_organiser() && !is_accreditor()) {
            return (null);
        }

        var footer=(<div>
        <Button label="Close" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        this.setConvenienceData();
        var eventroles = [];
        var overallroles = [];
        this.props.value.registration.registrations.map((ev) => {
            if(!is_valid(ev.sideevent)) {
                overallroles.push(ev);
            }
            else {
                eventroles.push(ev);
            }
        });

        return (<Dialog header={"Registrations of " + this.props.value.fullname} position="center" visible={this.props.display} className="fencer-select-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>{ this.props.value.fullname }</h5>
    <div className='container'>
        <div className='row'>
            <div className='col-12'>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Role</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th></th>
                        </tr>
                    </thead>
                    {this.renderRoles(eventroles)}
                    {this.renderRoles(overallroles)}
                </table>
            </div>
        </div>
    </div>
</Dialog>
);
    }

    renderRow(reg, key) {
        var role = this.roleById["r" + reg.role];
        var event=this.eventById["s"+reg.sideevent];

        var is_error = reg.pending == "error1" || reg.pending == "error2";
        var is_success = reg.pending == "saved" || reg.pending == "deleted";
        var is_saving = reg.pending == "save" || reg.pending == "delete";

        var isdisabled = true;
        var ischecked = true;
        switch (reg.state) {
        case 'C': ischecked = false; break;
        default:
        case 'R': isdisabled = false; ischecked = false; break;
        case 'P': isdisabled = false; break;
        }

        return (<tr key={key}>
            <td>{event && event.title}</td>
            <td>{role && role.name}</td>
        
            <td><InputSwitch disabled={isdisabled} checked={ischecked} onChange={(e) => this.onChange(reg, e.value ? "P" : "R")} /></td>
            <td><Checkbox value='C' name={'state-o'} onChange={(e) => this.onChange(reg, e.checked ? 'C' : 'R')} checked={isdisabled} /></td>
            <td className="state-icons">
            {is_error && (<i className='pi pi-times'></i>)}
            {is_success && (<i className='pi pi-check'></i>)}
            {is_saving && (<i className='pi pi-cloud-upload'></i>)}
            {!is_error && !is_success && !is_saving && (<span>&nbsp;</span>)}
            </td>
        </tr>);
    }

    renderRoles(overallroles) {
        return (
        <tbody>
            {overallroles.map((reg,idx) => {
                return this.renderRow(reg, "rl-"+idx);
            })}
        </tbody>);
    }
}

