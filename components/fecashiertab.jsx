import React from 'react';
import FEBase from './febase';
import {format_currency, jsonOutput } from './functions';
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
            if (reg.sideevent == itm.sideevent) {
                return itm;
            }
            else {
                return reg;
            }
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
        var eventObj = this.eventsToObj();
        if (eventObj["e" + reg.sideevent]) {
            registration('save', {
                id: reg.id || -1,
                paid: reg.paid,
                paid_hod: reg.paid_hod,
                event: this.props.item.id,
                sideevent: reg.sideevent,
                fencer: fencer.id
            })
                .then((json) => {
                    var registration = {};
                    if (json.data && json.data.model) {
                        console.log("merging saved model ", json.data.model);
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
                .catch((err) => {
                    console.log(err);
                    this.changePendingSituation(fencer,reg, "error1");
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

    openDetails = (fencer) => {
        if(this.state.fencerDetails == fencer.id) {
            this.setState({ fencerDetails: -1 });
        }
        else {
            this.setState({fencerDetails: fencer.id});
        }
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
                    if (evfranking.eventcap == "hod") {
                        if(reg.paid_hod != 'Y') {
                            reg.paid_hod = 'Y';
                        }
                        else {
                            reg.paid_hod='N';
                        }
                        console.log("setting paid status of " + reg.id +  " for HoD to "+reg.paid_hod)
                    }
                    else {
                        if (reg.paid != 'Y') {
                            reg.paid = 'Y';
                        }
                        else {
                            reg.paid = 'N';
                        }
                        console.log("setting paid status of " + reg.id + " for Cashier to " + reg.paid)
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
            console.log("allpaid event");
            // mark all registrations as paid
            // this does not allow unmarking
            regs.map((reg) => {
                var changed=false;
                if(evfranking.eventcap == "hod" && reg.paid_hod!='Y') {
                    console.log("setting paid status of " + reg.id +  " for HoD to Y")
                    reg.paid_hod = 'Y';
                    changed=true;
                }
                else if(evfranking.eventcap!=="hod" && reg.paid!='Y') {
                    console.log("setting paid status of "+reg.id +  " for Cashier to Y")
                    reg.paid='Y';
                    changed=true;
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

        var group_costs=0.0;
        var individual_costs=0.0;
        var open_group=0.0;
        var open_individual=0.0;
        // sort all registered fencers in athletes and non-athletes
        var fencers=Object.keys(this.state.registered).map((key) => {
            var fencer = this.state.registered[key];
            fencer.is_athlete=-1;
            fencer.total_costs=0.0;
            fencer.has_paid=true; // start with all-events-paid, as the set of 'no-events' is always paid
            fencer.has_paid_org=true;
            fencer.costs_for=[];
            fencer.paysIndividual=false;
            fencer.state="ok";
            if(fencer.registrations) {
                fencer.registrations.map((reg) => {
                    var se = sideEventById["e" + reg.sideevent];
                    var is_comp = parseInt(se.competition_id) > 0;
                    var costs=0.0;

                    if(is_comp && parseInt(reg.role) == 0) {
                        console.log("fencer "+fencer.fullname+ " has athlete registration for sideevent " + reg.sideevent + " with role "+ reg.role);
                        console.log("adding " + this.props.item.competition_fee + " to " + fencer.total_costs + " for event " + se.title);
                        costs = parseFloat(this.props.item.competition_fee);
                        if (fencer.is_athlete < 0) {
                            fencer.is_athlete = reg.id;
                            costs += parseFloat(this.props.item.base_fee);
                        }
                    }
                    else if(!is_comp) {
                        // side events have their own specific costs for any participant
                        if(se) {
                            console.log("fencer " + fencer.fullname + " has registration for sideevent " + reg.sideevent + " with role " + reg.role);
                            console.log("adding " + se.costs + " to " + fencer.total_costs + " for event " + se.title);
                            costs = parseFloat(se.costs);
                        }
                    }
                    if (reg.individual == 'Y') {
                        fencer.paysIndividual = true;
                        individual_costs+=costs;
                        // the open costs are based on the actual paid status, not on the HoD registration
                        if(reg.paid != 'Y') {
                            open_individual += costs;
                        }
                    }
                    else {
                        group_costs += costs;
                        if(reg.paid != 'Y') {
                            open_group += costs;
                        }
                    }

                    if(costs > 0.0) {
                        fencer.costs_for.push([se, reg,costs]);
                        fencer.total_costs += costs;

                        // only take paid status of actual cost-registrations into account
                        if(evfranking.eventcap=="hod") {
                            if(reg.individual != 'Y') {
                                // HoD's are only concerned with group payments
                                fencer.has_paid = fencer.has_paid && (reg.paid_hod == 'Y');
                                console.log("checking HoD status of " + reg.id + ": " + reg.paid_hod, fencer.has_paid);
                            }
                            // mark payments that were received by the cashier
                            fencer.has_paid_org = fencer.has_paid_org && (reg.paid == 'Y');
                        }
                        else {
                            fencer.has_paid = fencer.has_paid && (reg.paid == 'Y');
                            console.log("checking Cashier status of " + reg.id + ": " + reg.paid, fencer.has_paid);
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
                });
            }
            return fencer;
        })
            // filter out non-paying participants (coaches, etc)
            .filter((fencer) => {
                return parseFloat(fencer.total_costs) > 0.0;
            })
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
                                        {fencer.paysIndividual && "Individual"}
                                        {!fencer.paysIndividual && "Group"}
                                    </td>
                                    <td>
                                        {(evfranking.eventcap != "hod" || !fencer.paysIndividual) && (
                                        <Checkbox name={"allpaid-" + fencer.id} onChange={this.onChangeEl} checked={fencer.has_paid} />
                                        )}
                                    </td>
                                    <td>
                                    {evfranking.eventcap == "hod" && (<div>
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
                                <tr className={'details ' + (fencer.id == this.state.fencerDetails ? 'details-open' : 'details-close')}>
                                    <td></td>
                                    <td colSpan='7'>
                                    <table>
                                        <tbody>
                                {fencer.costs_for.map((el) => {
                                    var se=el[0];
                                    var reg=el[1];
                                    var cost=el[2];

                                    var checked = reg.paid=='Y';
                                    if(evfranking.eventcap == "hod") {
                                        checked=reg.paid_hod=='Y';
                                    }

                                    return (<tr key={'r'+reg.id}>
                                    <td>{se.title}</td>
                                    <td>{this.props.item.symbol} {cost.toFixed(2)}</td>
                                    <td>
                                        {(evfranking.eventcap != "hod" || !fencer.paysIndividual) && (
                                        <Checkbox name={"paid-"  + fencer.id + '-'+reg.id} onChange={this.onChangeEl} checked={checked} />
                                        )}
                                    </td>
                                    <td>
                                    {evfranking.eventcap == "hod" && (<div>
                                        {reg.paid=='Y' && (<i className='pi pi-thumbs-up'></i>)}
                                        {!reg.paid == 'Y' && (<span className='pi'></span>)}
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
                            </tbody>
                        ))}
                    </table>
                </div>
                <div className='col-12'>
                    <table className='payment-details'>
                        <tr>
                            <td className='label'>Group costs:</td>
                            <td>{this.props.item.symbol} {format_currency(group_costs)}</td>
                        </tr>
                        {open_group > 0.0 && (<tr>
                            <td className='label'>Remaining group costs:</td>
                            <td>{this.props.item.symbol} {format_currency(open_group)}</td>
                        </tr>)}
                        {individual_costs > 0.0 && (<tr>
                            <td className='label'>Individual costs:</td>
                            <td>{this.props.item.symbol} {format_currency(individual_costs)}</td>
                        </tr>)}
                        {open_individual > 0.0 && (<tr>
                            <td className='label'>Remaining individual costs:</td>
                            <td>{this.props.item.symbol} {format_currency(open_individual)}</td>
                        </tr>)}
                    </table>
                </div>
                {evfranking.eventcap == "hod" && (
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
            </div>
        );
    }

}
