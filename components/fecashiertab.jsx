import React from 'react';
import FEBase from './febase';
import {format_currency, is_hod, is_valid, parse_net_error, is_organisation } from './functions';
import { registration } from './api';
import { Checkbox } from 'primereact/checkbox';

export default class FECashierTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencerDetails: -1,
        });
    }

    eventsToObj = () => {
        var obj = {};
        this.state.sideevents.map((itm) => {
            obj["e" + itm.id] = itm;
        });
        return obj;
    }

    insertRegistration = (fencer,itm) => {
        var regs = fencer.registrations.map((reg) => {
            if (is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                return itm;
            }
            else if (!is_valid(reg.sideevent) && !is_valid(itm.sideevent)) {
                // only replace if the roles match
                if (reg.role == itm.role) {
                    return itm;
                }
            }
            return reg;
        });
        fencer.registrations = regs;
        this.changeSingleRegisteredFencer(fencer);
    }

    changePendingSituation = (fencer,itm, newstate) => {
        itm.pending=newstate;
        this.insertRegistration(fencer,itm);
    }

    clearState = (fencer,reg) => {
        // only clear state if the item is still on the end-of-backend-call state
        if (reg && reg.pending == "saved") {
            reg.pending = "";
            this.insertRegistration(fencer,reg);
        }
    }

    saveRegistration = (fencer,reg) => {
        var se = parseInt(reg.sideevent);
        if (se <= 0) {
            se = null;
        }

        registration('save', {
            id: reg.id || -1,
            paid: reg.paid,
            paid_hod: reg.paid_hod,
            event: this.props.item.id,
            sideevent: se,
            role: reg.role,
            fencer: fencer.id
        })
            .then((json) => {
                var registration = {};
                if (json.data && json.data.model) {
                    registration = json.data.model;
                }
                var itm = Object.assign({}, registration, reg);
                if (itm.fencer_data) {
                    delete itm.fencer_data;
                }

                // insert-or-replace the new registration
                itm.pending = "saved";
                this.insertRegistration(fencer,itm); // insert and save new list

                var self = this;
                setTimeout(() => { self.clearState(fencer,itm); }, 2000);
            })
            .catch((err) => parse_net_error(err));
    }

    openDetails = (fencer) => {
        if(this.state.fencerDetails === fencer.id) {
            this.setState({ fencerDetails: -1 });
        }
        else {
            this.setState({fencerDetails: fencer.id});
        }
    }

    redirectList = (type) => {
        var href = evfranking.url + "&download=" + type + "&event=" + this.props.item.id + "&nonce=" + evfranking.nonce;
        window.open(href);
    }

    onChangeEl = (event) => {
        if (!event.target) return;
        var els = event.target.name.split('-');
        var name = event.target.name;
        var value = event.value;
        if (event.checked) value = event.checked;
        var id = -1;
        var fid=-1;
        if (els.length > 1) {
            name = els[0];
            fid = els[1];
            if(els.length>2) {
                id=els[2];
            }
        }
        var fencer=null;
        if(this.state.registered["k"+fid]) {
            fencer = this.state.registered["k" + fid];
        }
        if(!fencer) {
            console.log("no such fencer");
            return;
        }
        var regs=fencer.registrations.slice();

        switch(name) {
        case 'paid':
            // mark this specific registration as paid
            var regtochange=null;
            regs.map((reg) => {
                if(reg.id == id) {
                    if (is_hod()) {
                        if(reg.paid_hod != 'Y') {
                            reg.paid_hod = 'Y';
                        }
                        else {
                            reg.paid_hod='N';
                        }
                    }
                    else {
                        if (reg.paid != 'Y') {
                            reg.paid = 'Y';
                        }
                        else {
                            reg.paid = 'N';
                        }
                    }
                    regtochange = reg;
                }
                return reg;
            });
            if(regtochange) {
                this.saveRegistration(fencer,regtochange);
            }
            break;
        case 'allpaid':
            // if there are any payments unchecked, check them all. Else uncheck them
            var allchecked=true;
            regs.map((reg) => {
                if(is_hod() && reg.paid_hod != 'Y') allchecked=false;
                if(!is_hod() && reg.paid != 'Y') allchecked=false;
            });
            // mark all registrations as paid
            regs.map((reg) => {
                var changed=false;
                if(is_hod()) {
                    if(!allchecked && reg.paid_hod != 'Y' ) {
                        reg.paid_hod = 'Y';
                        changed=true;
                    }
                    else if(allchecked && reg.paid_hod == 'Y') {
                        reg.paid_hod = 'N';
                        changed = true;
                    }
                }
                else {
                    if(!allchecked && reg.paid != 'Y') {
                        reg.paid='Y';
                        changed=true;
                    }
                    else if(allchecked && reg.paid == 'Y') {
                        reg.paid = 'N';
                        changed = true;
                    }
                }
                if(changed) {
                    this.saveRegistration(fencer, reg);
                }
            });
            break;
        }
    }

    renderContent() {
        var sideEventById=this.eventsToObj();
        var as_organiser = !is_valid(this.state.country_item.id) && is_organisation();

        var group_costs=0.0; // total of all group-payments
        var individual_costs=0.0; // total of all individual payments
        var open_group=0.0; // total group payments still not at organisation
        var open_individual=0.0; // total individual payments still not at organisation
        var remaining_group=0.0; // total group payments still not at HoD
        // sort all registered fencers in athletes and non-athletes
        var fencers=Object.keys(this.state.registered).map((key) => {
            var fencer = this.state.registered[key];
            fencer.is_athlete=-1;
            fencer.total_costs=0.0;
            fencer.has_paid=true; // start with all-events-paid, as the set of 'no-events' is always paid
            fencer.has_paid_org=true;
            fencer.costs_for=[];
            fencer.paysIndividual="?";
            fencer.state="ok";
            if(fencer.registrations) {
                var regs = fencer.registrations.map((reg) => {
                    // Requirement 2.1.5: only events with a fee are listed
                    if(!reg) return {filter_me: false };

                    var se = sideEventById["e" + reg.sideevent] || null;
                    var is_comp = se && is_valid(se.competition_id);
                    var costs=0.0;

                    if(is_comp && parseInt(reg.role) == 0) {
                        // Requirement 2.1.6: use the competition fee for competitions
                        costs = parseFloat(this.props.item.competition_fee);
                        // Requirement 2.1.7: use the base fee once per participant
                        if (fencer.is_athlete < 0) {
                            fencer.is_athlete = reg.id; // Requirement 2.1.7: check using base fee once only
                            costs += parseFloat(this.props.item.base_fee);
                        }
                    }
                    else if(!is_comp) {
                        // side events have their own specific costs for any participant
                        if(se) {
                            costs = parseFloat(se.costs);
                        }
                        // if no side-event, then there are no costs
                        // this occurs for event-wide roles like trainer, coach, team armourer
                    }

                    // Requirement 2.1.5: only list events that come with a price
                    reg.filter_me = (costs > 0.0);
                    if(reg.filter_me) {
                        if (as_organiser) {
                            // Requirement 2.1.11: display E and O types for organisation-invitees
                            // filter out any registrations that are not paid by the organisation
                            reg.filter_me = ['E', 'O'].includes(reg.payment);
                        }
                        else {
                            // Requirement 2.1.12: display I and G types for country-participants
                            // only display individual and group payments
                            reg.filter_me = ['I', 'G'].includes(reg.payment);
                        }
                    }

                    // if still listed, see if the overall payment can be displayed, or is
                    // 'complicated'
                    if(reg.filter_me) {
                        if (![reg.payment, '?'].includes(fencer.paysIndividual)) {
                            fencer.paysIndividual = 'C'; // complicated
                        }
                        else {
                            fencer.paysIndividual = reg.payment;
                        }
                    }

                    // Requirement 2.1.14: create a convenience total for individual and group payments
                    // keep track of all open group and individual costs
                    if (reg.payment == 'I') {
                        individual_costs+=costs;
                        // the open costs are based on the actual paid status, not on the HoD registration
                        if(reg.paid != 'Y') {
                            open_individual += costs;
                        }
                    }
                    else if(reg.payment == 'G') {
                        group_costs += costs;
                        // for group payments, calculate the amount that needs to be paid to the HoD
                        if(is_hod() && reg.paid_hod != 'Y') {
                            remaining_group += costs;
                        }
                        if(reg.paid != 'Y') {
                            open_group += costs;
                        }
                    }

                    if(reg.filter_me) {
                        // no sideevent, no costs, so se must be set here
                        fencer.costs_for.push([se, reg,costs]);
                        fencer.total_costs += costs;

                        // only take paid status of actual cost-registrations into account
                        // Requirement 2.1.15: HoD and Cashier manage different payments
                        if(is_hod()) {
                            if(reg.payment == 'G') {
                                // HoD's are only concerned with group payments
                                fencer.has_paid = fencer.has_paid && (reg.paid_hod == 'Y');
                            }
                            // mark payments that were received by the cashier
                            fencer.has_paid_org = fencer.has_paid_org && (reg.paid == 'Y');
                        }
                        else {
                            fencer.has_paid = fencer.has_paid && (reg.paid == 'Y');
                        }
                    }

                    // display an error if there was an error anywhere. Display saving if
                    // any of the items is currently saving. Display success only when there
                    // are no errors and nothing is saving.
                    if(reg.pending == "error1") {
                        fencer.state="error";
                    }
                    else if(reg.pending == "save" && fencer.state!="error") {
                        fencer.state="saving";
                    }
                    else if(reg.pending=="saved" && fencer.state=="ok") {
                        fencer.state="success";
                    }

                    return reg;
                }).filter((reg) => reg.filter_me);
                fencer.registrations=regs;
            }
            return fencer;
        })
            // Requirement 2.1.5: filter out non-paying participants (coaches, etc)
            .filter((fencer) => {
                // only show items that have an associated cost
                if(parseFloat(fencer.total_costs) <= 0.0) return false;
                return true;
            })
            // Requirement 2.1.18: sort athletes first, then by name
            .sort(function(a1,a2) {
                if(a1.is_athlete && !a2.is_athlete) return -1;
                if(a2.is_athlete && !a1.is_athlete) return 1;

                return a1.name > a2.name;
            });


        return (
            <div className='row'>
                <div className='col-12'>
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Name</th>
                            <th>First name</th>
                            {evfranking.caps == "hod" && (<th>Country</th>)}
                            <th>Fee</th>
                            <th>Events</th>
                            <th>Payment</th>
                            </tr>
                        </thead>
                        {fencers.map((fencer,idx) => (
                            <tbody key={'f'+fencer.id}>
                                <tr>
                                    <td>{fencer.name}</td>
                                    <td>{fencer.firstname}</td>
                                    <td>{this.props.item.symbol} {format_currency(fencer.total_costs)}</td>
                                    <td>{fencer.costs_for.map((e,idx) => (
                                        <span key={idx} className='event-costs-for'>
                                            {idx>0 && ", "}
                                            {e[0].title}
                                        </span>))}</td>
                                    <td>
                                        {fencer.paysIndividual == 'I' && "Individual"}
                                        {fencer.paysIndividual == 'G' && "Group"}
                                        {fencer.paysIndividual == 'E' && "EVF"}
                                        {fencer.paysIndividual == 'O' && "Organisation"}
                                    </td>
                                    <td>
                                        {(!is_hod() || fencer.paysIndividual=='G') && (
                                        <Checkbox name={"allpaid-" + fencer.id} onChange={this.onChangeEl} checked={fencer.has_paid} />
                                        )}
                                    </td>
                                    <td>
                                    {is_hod() && (<div>
                                        {fencer.has_paid_org && (<i className='pi pi-thumbs-up'></i>)}
                                        {!fencer.has_paid_org && (<span className='pi'></span>)}
                                        </div>)}
                                    </td>
                                    <td>
                                        {this.state.fencerDetails !== fencer.id  && (<i className="pi pi-chevron-right" onClick={(e) => this.openDetails(fencer)}></i>)}
                                        {this.state.fencerDetails === fencer.id && (<i className="pi pi-chevron-down" onClick={(e) => this.openDetails(fencer)}></i>)}
                                    </td>
                                    <td className="state-icons">
                                        {fencer.state=="error" && (
                                            <i className='pi pi-times'></i>
                                        )}
                                        {fencer.state=="success" && (
                                            <i className='pi pi-check'></i>
                                        )}
                                        {fencer.state=="saving" && (
                                            <i className='pi pi-cloud-upload'></i>
                                        )}
                                        {fencer.state == "ok" && (<span className='pi'></span>)}
                                    </td>
                                </tr>
                                {fencer.id === this.state.fencerDetails && (
                                <tr className='details details-open'>
                                    <td></td>
                                    <td colSpan='7'>
                                    <table>
                                        <tbody>
                                {fencer.costs_for.map((el) => {
                                    var se=el[0];
                                    var reg=el[1];
                                    var cost=el[2];

                                    var checked = reg.paid=='Y';
                                    if(is_hod()) {
                                        checked=reg.paid_hod=='Y';
                                    }

                                    return (<tr key={'r'+reg.id}>
                                    <td>{se.title}</td>
                                    <td>{this.props.item.symbol} {cost.toFixed(2)}</td>
                                    <td>
                                        {(!is_hod() || fencer.paysIndividual=='G') && (
                                        <Checkbox name={"paid-"  + fencer.id + '-'+reg.id} onChange={this.onChangeEl} checked={checked} />
                                        )}
                                    </td>
                                    <td>
                                    {is_hod() && (<div>
                                        {reg.paid=='Y' && (<i className='pi pi-thumbs-up'></i>)}
                                        {!reg.paid == 'Y' && (<span className='pi'></span>)}
                                        </div>)}
                                    {is_organisation() && (<div>
                                        {reg.payment == 'I' && "Ind."}
                                        {reg.payment == 'G' && "Grp"}
                                        {reg.payment == 'O' && "Org"}
                                        {reg.payment == 'E' && "EVF"}
                                    </div>)}
                                    </td>
                                    <td className="state-icons">
                                        {reg.pending=="error1" && (
                                            <i className='pi pi-times'></i>
                                        )}
                                        {reg.pending=="saved" && (
                                            <i className='pi pi-check'></i>
                                        )}
                                        {reg.pending =="save" && (
                                            <i className='pi pi-cloud-upload'></i>
                                        )}
                                        {!["error1","saved","save"].includes(reg.pending) && (<span className='pi'></span>)}
                                    </td>
                                    </tr>
                                    )
                                })}
                                            </tbody>
                                        </table>
                                    </td>
                                    <td></td>
                                </tr>
                                )}
                            </tbody>
                        ))}
                    </table>
                </div>
                {!as_organiser && (<div className='col-12'>
                    <table className='payment-details'>
                        <tbody>
                        <tr>
                            <td className='label'>Group costs:</td>
                            <td>{this.props.item.symbol} {format_currency(group_costs)}</td>
                        </tr>
                        {open_group > 0.0 && (<tr>
                            <td className='label'>Transferrable group costs:</td>
                            <td>{this.props.item.symbol} {format_currency(open_group)}</td>
                        </tr>)}
                        {remaining_group > 0.0 && (<tr>
                            <td className='label'>Receivable group costs:</td>
                            <td>{this.props.item.symbol} {format_currency(remaining_group)}</td>
                        </tr>)}
                        {individual_costs > 0.0 && (<tr>
                            <td className='label'>Individual costs:</td>
                            <td>{this.props.item.symbol} {format_currency(individual_costs)}</td>
                        </tr>)}
                        {open_individual > 0.0 && (<tr>
                            <td className='label'>Remaining individual costs:</td>
                            <td>{this.props.item.symbol} {format_currency(open_individual)}</td>
                        </tr>
                        )}
                        </tbody>
                    </table>
                </div>)}
                {is_hod() && (
                <div className='col-12'>
                    <table className='payment-details'>
                        <tbody>
                            <tr>
                                <td colSpan='2' className='textcenter'><h5>Payment Details</h5></td>
                            </tr>
                            <tr>
                                <td className='label'>Bank Account</td>
                                <td>{this.props.item.iban}</td>
                            </tr>
                            <tr>
                                <td className='label'>Reference</td>
                                <td>{this.props.item.reference}</td>
                            </tr>
                            <tr>
                                <td className='label'>Account Name</td>
                                <td>{this.props.item.account}</td>
                            </tr>
                            <tr>
                                <td className='label'>Account Address</td>
                                <td>{this.props.item.address}</td>
                            </tr>
                            <tr>
                                <td className='label'>Bank</td>
                                <td>{this.props.item.bank}</td>
                            </tr>
                            <tr>
                                <td className='label'>SWIFT/BIC</td>
                                <td>{this.props.item.swift}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                )}
                {is_organisation() && (
                <div className='col-12'>                    
                    <i className='pi pi-download' onClick={() => this.redirectList("cashier")}> Payment Spreadsheet</i>
                </div>
                )}
            </div>
        );
    }

}
