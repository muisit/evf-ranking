import React from 'react';
import FEBase from './febase';
import {format_currency, is_hod, is_valid, parse_net_error, is_organisation } from './functions';
import { create_abbr, create_cmpById, create_roleById, create_wpnById, create_catById } from "./functions";
import { fencers, registration } from './api';
import { Checkbox } from 'primereact/checkbox';
import { EXITING } from 'react-transition-group/Transition';

export default class FECashierTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencerDetails: -1
        });
        this.abortType='cashier';
    }

    eventsToObj = () => {
        var obj = {};
        var wpnById = create_wpnById(this.props.weapons);
        var catById = create_catById(this.props.categories);
        var cmpById = create_cmpById(this.state.competitions, wpnById, catById);
        this.state.sideevents.map((itm) => {
            if(is_valid(itm.competition_id)) {
                itm.competition = cmpById["c" + itm.competition_id];
            }
            itm.abbr = create_abbr(itm, cmpById);
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
        reg.pending='save';
        this.insertRegistration(fencer,reg);
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
            .catch((err) => {
                reg.pending='error1';
                this.insertRegistration(fencer,reg);
                parse_net_error(err)
            });
    }

    openDetails = (fencer,isteam) => {
        if(this.state.fencerDetails === fencer.id) {
            this.setState({ fencerDetails: -1 });
        }
        else if(isteam == 'team') {
            this.setState({ fencerDetails: 't' + fencer.id });
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
        var regs=fencer ? fencer.registrations.slice() : [];

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
        case 'paidteam':
            // mark all registrations for this team as paid
            // fid is the sideevent id, id is the team name
            Object.keys(this.state.registered).map((key) => {
                const fencer=this.state.registered[key];
                var regtochange=null;
                fencer.registrations.map((reg) => {
                    if (reg.sideevent == fid && reg.team == id) {
                        if (is_hod()) {
                            if (reg.paid_hod != 'Y') {
                                reg.paid_hod = 'Y';
                            }
                            else {
                                reg.paid_hod = 'N';
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
                        regtochange=reg;
                    }
                });
                if(regtochange) {
                    this.saveRegistration(fencer, reg);
                }
            });
            break;
        case 'allpaid':
            // if there are any payments unchecked, check them all. Else uncheck them
            var allchecked=true;
            regs.map((reg) => {
                if(is_hod() && reg.paid_hod != 'Y') allchecked=false;
                if(!is_hod() && reg.paid != 'Y') allchecked=false;
            });
            console.log("allpaid, looping over all fencer registrations, marking them as paid");
            // mark all registrations as paid
            regs.map((reg) => {
                var changed=false;
                // only adjust all payments for non-team events
                if(!reg.is_team) {
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
                }
                if(changed) {
                    this.saveRegistration(fencer, reg);
                }
            });
            break;
        case 'allpaidteam':
            var allchecked=true;
            console.log("allpaidteam for sideevent ",fid);
            Object.keys(this.state.registered).map((key) => {
                const fencer = this.state.registered[key];
                fencer.registrations.map((reg) => {
                    if (reg.sideevent == fid) {
                        console.log("checking registration ",reg);
                        if (is_hod() && reg.paid_hod != 'Y') allchecked = false;
                        if (!is_hod() && reg.paid != 'Y') allchecked = false;
                    }
                });
            });
            console.log("allchecked is ",allchecked);
            // mark all registrations as paid
            Object.keys(this.state.registered).map((key) => {
                const fencer = this.state.registered[key];
                fencer.registrations.map((reg) => {
                    var changed = false;
                    if(reg.sideevent == fid) {
                        if (is_hod()) {
                            if (!allchecked && reg.paid_hod != 'Y') {
                                reg.paid_hod = 'Y';
                                changed = true;
                            }
                            else if (allchecked && reg.paid_hod == 'Y') {
                                reg.paid_hod = 'N';
                                changed = true;
                            }
                        }
                        else {
                            if (!allchecked && reg.paid != 'Y') {
                                reg.paid = 'Y';
                                changed = true;
                            }
                            else if (allchecked && reg.paid == 'Y') {
                                reg.paid = 'N';
                                changed = true;
                            }
                        }
                    }
                    if (changed) {
                        console.log("saving registration ",reg);
                        this.saveRegistration(fencer, reg);
                    }
                });
            });
            break;
        }
    }

    renderTeamList(comp,idx) {
        console.log("rendering team list, details set to ",this.state.fencerDetails);
        var cfg = this.props.item.config;
        var allow_more_teams = (cfg && cfg.allow_more_teams) ? true : false;
        var issaving=["error","success","saving"].includes(comp.state);
        var detailkey='t' + comp.sideevent.id;
        return (
            <tbody key={'f' + idx}>
                <tr>
                    <td>{comp.sideevent.title}</td>
                    <td>&nbsp;</td>
                    <td>{this.props.item.symbol} {format_currency(comp.total_costs)}</td>
                    <td>
                        <Checkbox disabled={issaving} name={"allpaidteam-" + comp.sideevent.id} onChange={this.onChangeEl} checked={comp.has_paid} />
                    </td>
                    <td>
                        {is_hod() && (<div>
                            {comp.has_paid_org && (<i className='pi pi-thumbs-up'></i>)}
                            {!comp.has_paid_org && (<span className='pi'></span>)}
                        </div>)}
                    </td>
                    <td>
                        {allow_more_teams && this.state.fencerDetails !== detailkey && (<i className="pi pi-chevron-right" onClick={(e) => this.openDetails(comp.sideevent, 'team')}></i>)}
                        {allow_more_teams && this.state.fencerDetails === detailkey && (<i className="pi pi-chevron-down" onClick={(e) => this.openDetails(comp.sideevent, 'team')}></i>)}
                    </td>
                    <td className="state-icons">
                        {comp.state == "error" && (
                            <i className='pi pi-times'></i>
                        )}
                        {comp.state == "success" && (
                            <i className='pi pi-check'></i>
                        )}
                        {comp.state == "saving" && (
                            <i className='pi pi-cloud-upload'></i>
                        )}
                        {!issaving && (<span className='pi'></span>)}
                    </td>
                </tr>
                {allow_more_teams && this.state.fencerDetails === detailkey && (
                    <tr className='details details-open'>
                        <td></td>
                        <td colSpan='6'>
                            <table>
                                <tbody>
                                    {comp.teams.map((team) => {
                                        var checked = team.paid == 'Y';
                                        if (is_hod()) {
                                            checked = team.paid_hod == 'Y';
                                        }
                                        var issaving2 = ["error", "saving", "success"].includes(team.state);
                                        return (<tr key={'r' + team.id}>
                                            <td>{team.name}</td>
                                            <td>{this.props.item.symbol} {team.total_cost.toFixed(2)}</td>
                                            <td>
                                                <Checkbox disabled={issaving || issaving2} name={"paidteam-" + comp.sideevent.id + '-' + team.name} onChange={this.onChangeEl} checked={checked} />
                                            </td>
                                            <td>
                                                {is_hod() && (<div>
                                                    {team.paid == 'Y' && (<i className='pi pi-thumbs-up'></i>)}
                                                    {!team.paid == 'Y' && (<span className='pi'></span>)}
                                                </div>)}
                                            </td>
                                            <td className="state-icons">
                                                {team.state == "error" && (
                                                    <i className='pi pi-times'></i>
                                                )}
                                                {team.state == "success" && (
                                                    <i className='pi pi-check'></i>
                                                )}
                                                {team.state == "sav" && (
                                                    <i className='pi pi-cloud-upload'></i>
                                                )}
                                                {!issaving2 && (<span className='pi'></span>)}
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
            </tbody>);
    }

    renderTeams(teamcompetitions) {
        console.log("rendering teams and competitions",teamcompetitions);
        return (
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Competition</th>
                            <th>Teams</th>
                            <th>Fee</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            </tr>
                        </thead>
                        {teamcompetitions.map((comp,idx) => this.renderTeamList(comp,idx)) }
                    </table>);
    }

    renderIndividual(fencer) {
        var issaving = ['error','success','saving'].includes(fencer.state);
        return (
            <tbody key={'f' + fencer.id}>
                <tr>
                    <td>{fencer.name}</td>
                    <td>{fencer.firstname}</td>
                    {evfranking.caps == "hod" && (<td>{fencer.country}</td>)}
                    <td>{this.props.item.symbol} {format_currency(fencer.total_costs)}</td>
                    <td>{fencer.costs_for.map((e, idx) => (
                        <span key={idx} className='event-costs-for'>
                            {idx > 0 && ", "}
                            {e[0].abbr}
                        </span>))}
                    </td>
                    <td>
                        {fencer.paysIndividual == 'I' && "Individual"}
                        {fencer.paysIndividual == 'G' && "Group"}
                        {fencer.paysIndividual == 'E' && "EVF"}
                        {fencer.paysIndividual == 'O' && "Organisation"}
                    </td>
                    <td>
                        {(!is_hod() || fencer.paysIndividual == 'G') && (
                            <Checkbox disabled={issaving} name={"allpaid-" + fencer.id} onChange={this.onChangeEl} checked={fencer.has_paid} />
                        )}
                    </td>
                    <td>
                        {is_hod() && (<div>
                            {fencer.has_paid_org && (<i className='pi pi-thumbs-up'></i>)}
                            {!fencer.has_paid_org && (<span className='pi'></span>)}
                        </div>)}
                    </td>
                    <td>
                        {this.state.fencerDetails !== fencer.id && (<i className="pi pi-chevron-right" onClick={(e) => this.openDetails(fencer)}></i>)}
                        {this.state.fencerDetails === fencer.id && (<i className="pi pi-chevron-down" onClick={(e) => this.openDetails(fencer)}></i>)}
                    </td>
                    <td className="state-icons">
                        {fencer.state == "error" && (
                            <i className='pi pi-times'></i>
                        )}
                        {fencer.state == "success" && (
                            <i className='pi pi-check'></i>
                        )}
                        {fencer.state == "saving" && (
                            <i className='pi pi-cloud-upload'></i>
                        )}
                        {!issaving && (<span className='pi'></span>)}
                    </td>
                </tr>
                {fencer.id === this.state.fencerDetails && (
                    <tr className='details details-open'>
                        <td></td>
                        <td colSpan='7'>
                            <table>
                                <tbody>
                                    {fencer.costs_for.map((el) => {
                                        var se = el[0];
                                        var reg = el[1];
                                        var cost = el[2];

                                        var checked = reg.paid == 'Y';
                                        if (is_hod()) {
                                            checked = reg.paid_hod == 'Y';
                                        }
                                        var issaving2=["error1","saved","save"].includes(reg.pending);
                                        return (<tr key={'r' + reg.id}>
                                            <td>{se.title}</td>
                                            <td>{this.props.item.symbol} {cost.toFixed(2)}</td>
                                            <td>
                                                {(!is_hod() || reg.payment == 'G') && (
                                                    <Checkbox disabled={issaving2 || issaving} name={"paid-" + fencer.id + '-' + reg.id} onChange={this.onChangeEl} checked={checked} />
                                                )}
                                            </td>
                                            <td>
                                                {is_hod() && (<div>
                                                    {reg.paid == 'Y' && (<i className='pi pi-thumbs-up'></i>)}
                                                    {!reg.paid == 'Y' && (<span className='pi'></span>)}
                                                </div>)}
                                                {is_organisation() && (<div>
                                                    {reg.payment == 'I' && "Individual"}
                                                    {reg.payment == 'G' && "Group"}
                                                    {reg.payment == 'O' && "Organisation"}
                                                    {reg.payment == 'E' && "EVF"}
                                                </div>)}
                                            </td>
                                            <td className="state-icons">
                                                {reg.pending == "error1" && (
                                                    <i className='pi pi-times'></i>
                                                )}
                                                {reg.pending == "saved" && (
                                                    <i className='pi pi-check'></i>
                                                )}
                                                {reg.pending == "save" && (
                                                    <i className='pi pi-cloud-upload'></i>
                                                )}
                                                {!issaving2 && (<span className='pi'></span>)}
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
            </tbody>);
    }

    renderIndividuals(fencers) {
        return (
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Name</th>
                            <th>First name</th>
                            {evfranking.caps == "hod" && (<th>Country</th>)}
                            <th>Fee</th>
                            <th>Competitions</th>
                            <th>Payment</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            </tr>
                        </thead>
                        {fencers.map((fencer) => this.renderIndividual(fencer)) }
                    </table>);
    }

    renderFooter(group_costs, open_group, remaining_group, individual_costs, open_individual,as_organiser) {
        return (
            <div>
            {!as_organiser && (<div className='col-12'>
                    <table className='payment-details'>
                        <tbody>
                            <tr>
                                <td className='label'>Group costs:</td>
                                <td>{this.props.item.symbol} {format_currency(group_costs)}</td>
                            </tr>
                            {!is_organisation() && open_group > 0.0 && (<tr>
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
        </div>);
    }


    renderContent() {
        var sideEventById = this.eventsToObj();
        var as_organiser = !is_valid(this.state.country_item.id) && is_organisation();

        var group_costs = 0.0; // total of all group-payments
        var individual_costs = 0.0; // total of all individual payments
        var open_group = 0.0; // total group payments still not at organisation
        var open_individual = 0.0; // total individual payments still not at organisation
        var remaining_group = 0.0; // total group payments still not at HoD
        // sort all registered fencers in athletes and non-athletes
        // Additionally, separate the team registrations and display them in a distinct list
        var teams = {};
        console.log("reparsing registrations ",this.state.registered);
        var fencers = Object.keys(this.state.registered).map((key) => {
            var fencer = this.state.registered[key];
            fencer.is_athlete = -1;
            fencer.total_costs = 0.0;
            fencer.has_paid = true; // start with all-events-paid, as the set of 'no-events' is always paid
            fencer.has_paid_org = true;
            fencer.costs_for = [];
            fencer.paysIndividual = "?";
            fencer.state = "ok";
            if (fencer.registrations) {
                var regs = fencer.registrations.map((reg) => {
                    // Requirement 2.1.5: only events with a fee are listed
                    if (!reg) return { filter_me: false };

                    var se = sideEventById["e" + reg.sideevent] || null;
                    var is_comp = se && is_valid(se.competition_id);
                    var comp = se.competition;
                    var cat = comp ? comp.category : null;
                    var wpn = comp ? comp.weapon : null;
                    var costs = 0.0;

                    if (is_comp && parseInt(reg.role) == 0) {

                        // if this is a team event, add this registration to the proper team list
                        // and filter it out. No costs are calculated in this loop
                        if (cat && cat.type == 'T' && reg.team) {
                            console.log("registration for ",fencer.name,' for a team event');
                            var key = 'k' + se.id;
                            if (!teams[key]) teams[key] = { "sideevent": se, teams:{} };
                            if (!teams[key].teams[reg.team]) teams[key].teams[reg.team] = [];
                            teams[key].teams[reg.team].push(reg);
                            // do not set costs, so we are filtered out below
                            console.log('pushed to team ',reg.team,' of ',key);
                            reg.is_team=true;
                        }
                        else {
                            console.log("registration for a regular competition ",se);
                            // Requirement 2.1.6: use the competition fee for competitions
                            costs = parseFloat(this.props.item.competition_fee);
                            //console.log("competition costs ",costs, this.props.item);
                            // Requirement 2.1.7: use the base fee once per participant
                            if (fencer.is_athlete < 0) {
                                fencer.is_athlete = reg.id; // Requirement 2.1.7: check using base fee once only
                                costs += parseFloat(this.props.item.base_fee);
                                //console.log("added base fee, costs are ",costs);
                            }
                        }
                    }
                    else if (!is_comp) {
                        // side events have their own specific costs for any participant
                        if (se) {
                            console.log("registration for a side event ",se.costs);
                            costs = parseFloat(se.costs);
                            //console.log("costs for side event",costs);
                        }
                        // if no side-event, then there are no costs
                        // this occurs for event-wide roles like trainer, coach, team armourer
                    }

                    // Requirement 2.1.5: only list events that come with a price
                    reg.filter_me = (costs > 0.0);
                    if (reg.filter_me) {
                        if (as_organiser) {
                            // Requirement 2.1.11: display E and O types for organisation-invitees
                            // filter out any registrations that are not paid by the organisation
                            reg.filter_me = ['E', 'O'].includes(reg.payment);
                            //console.log("organisers only see organisation payments");
                        }
                        else {
                            // Requirement 2.1.12: display I and G types for country-participants
                            // only display individual and group payments
                            reg.filter_me = ['I', 'G'].includes(reg.payment);
                            //console.log("HoDs see only individual and group payments");
                        }
                    }

                    // if still listed, see if the overall payment can be displayed, or is
                    // 'complicated'
                    if (reg.filter_me) {
                        if (![reg.payment, '?'].includes(fencer.paysIndividual)) {
                            fencer.paysIndividual = 'C'; // complicated
                        }
                        else {
                            fencer.paysIndividual = reg.payment;
                        }

                        // Requirement 2.1.14: create a convenience total for individual and group payments
                        // keep track of all open group and individual costs
                        if (reg.payment == 'I') {
                            individual_costs += costs;
                            // the open costs are based on the actual paid status, not on the HoD registration
                            if (reg.paid != 'Y') {
                                open_individual += costs;
                            }
                        }
                        else if (reg.payment == 'G') {
                            group_costs += costs;
                            // for group payments, calculate the amount that needs to be paid to the HoD
                            if (is_hod() && reg.paid_hod != 'Y') {
                                remaining_group += costs;
                            }
                            if (reg.paid != 'Y') {
                                open_group += costs;
                            }
                        }

                        //console.log("adding total costs to fencer costs");
                        // no sideevent, no costs, so se must be set here
                        fencer.costs_for.push([se, reg, costs]);
                        fencer.total_costs += costs;

                        // only take paid status of actual cost-registrations into account
                        // Requirement 2.1.15: HoD and Cashier manage different payments
                        if (is_hod()) {
                            if (reg.payment == 'G') {
                                // HoD's are only concerned with group payments
                                fencer.has_paid = fencer.has_paid && (reg.paid_hod == 'Y');
                            }
                            // mark payments that were received by the cashier
                            fencer.has_paid_org = fencer.has_paid_org && (reg.paid == 'Y');
                        }
                        else {
                            fencer.has_paid = fencer.has_paid && (reg.paid == 'Y');
                        }
    
                        // display an error if there was an error anywhere. Display saving if
                        // any of the items is currently saving. Display success only when there
                        // are no errors and nothing is saving.
                        if (reg.pending == "error1") {
                            fencer.state = "error";
                        }
                        else if (reg.pending == "save" && fencer.state != "error") {
                            fencer.state = "saving";
                        }
                        else if (reg.pending == "saved" && fencer.state == "ok") {
                            fencer.state = "success";
                        }
                    }

                    console.log("registration filter: ",reg);
                    return reg;
                })
                // filter out all non-individual, invalid registrations (filter_me), but keep the team registrations
                .filter((reg) => reg.filter_me || reg.is_team );
                fencer.registrations = regs;
            }
            return fencer;
        })
            // Requirement 2.1.5: filter out non-paying participants (coaches, etc)
            .filter((fencer) => {
                // only show items that have an associated cost
                if (parseFloat(fencer.total_costs) <= 0.0) {
                    //console.log("filtering out fencer because total costs <= 0", fencer);
                    return false;
                }
                return true;
            })
            // Requirement 2.1.18: sort athletes first, then by name
            .sort(function (a1, a2) {
                if (a1.is_athlete && !a2.is_athlete) return -1;
                if (a2.is_athlete && !a1.is_athlete) return 1;

                return a1.name > a2.name;
            });

        // loop over all team registrations and determine the overall team costs
        // For teams, only 1 payment per person is taken into account: the number of
        // participants does not matter
        var teamcosts = Object.keys(teams).map((key) => {
            var se = teams[key].sideevent;
            console.log('looping over teams for sideevent ', se);
            var compteams = teams[key].teams;
            var totalcosts = 0.0;
            var allpaid = true;
            var allpaidorg = true;
            var teamstate = 'ok';

            console.log("creating teamlist");
            var teamlist = Object.keys(compteams).map((key) => {
                console.log("team ",key);
                var entries = compteams[key];
                var team = {
                    id: key+se.id,
                    name: key,
                    has_paid: true,
                    has_paid_org: true,
                    total_cost: 0.0,
                    state: 'ok'
                };

                // there is no base fee associated for team events, only the competition fee
                var costs = parseFloat(this.props.item.competition_fee);
                totalcosts+=costs;
                team.total_cost = costs;
                group_costs += costs;
                var addedcosts = false; // track the state of the payment stored in the registrations

                console.log("looping over individual team member registrations");
                team.registrations = entries.map((reg) => {
                    console.log("team registration ", reg);
                    // we already tested above that these are athlete entries for a team event

                    // only add the team costs once to the total
                    if (!addedcosts) {
                        addedcosts = true;
                        // team events are always group costs
                        // for group payments, calculate the amount that needs to be paid to the HoD
                        if (is_hod() && reg.paid_hod != 'Y') {
                            remaining_group += costs;
                        }
                        if (reg.paid != 'Y') {
                            open_group += costs;
                        }
                    }

                    // only take paid status of actual cost-registrations into account
                    // Requirement 2.1.15: HoD and Cashier manage different payments
                    if (is_hod()) {
                        team.has_paid = team.has_paid && (reg.paid_hod == 'Y');
                        // mark payments that were received by the cashier
                        team.has_paid_org = fenteamcer.has_paid_org && (reg.paid == 'Y');
                    }
                    else {
                        console.log("checking team paid state based on ",reg.paid,team.has_paid);
                        team.has_paid = team.has_paid && (reg.paid == 'Y');
                    }

                    console.log("setting teamstate based on ",reg.pending,team.state);
                    if (reg.pending == "error1") {
                        team.state = "error";
                    }
                    else if (reg.pending == "save" && team.state != "error") {
                        team.state = "saving";
                    }
                    else if (reg.pending == "saved" && team.state == "ok") {
                        team.state = "success";
                    }
                });

                allpaid = allpaid && team.has_paid;
                allpaidorg = allpaidorg && team.has_paid_org;
                console.log("setting overall state based on ",team.state,teamstate);
                if (team.state == 'error') {
                    teamstate = 'error';
                }
                else if (team.state == 'saving' && teamstate != 'error') {
                    teamstate = 'saving';
                }
                else if (team.state == 'success' && teamstate == 'ok') {
                    teamstate = 'success';
                }

                return team;
            });

            return {
                sideevent: se,
                teams: teamlist,
                total_costs: totalcosts,
                has_paid: allpaid,
                has_paid_org: allpaidorg,
                state: teamstate
            };
        })
            // sort teams by event first, then team name
            .sort(function (a1, a2) {
                console.log("sorting ",a1,a2);
                if (   a1.sideevent && a2.sideevent 
                    && a1.sideevent.competition && a2.sideevent.competition 
                    && a1.sideevent.competition.name != a2.sideevent.competition.name) return a1.sideevent.competition.name > a2.sideevent.competition.name;
                return a1.team > a2.team;
            });

            console.log("rendering content",fencers);
        return (
            <div className='row'>
                <div className='col-12'>
                    {this.renderTeams(teamcosts)}
                    {this.renderIndividuals(fencers)}
                </div>
                {this.renderFooter(group_costs, open_group, remaining_group, individual_costs, open_individual, as_organiser)}

            </div>
        );
    }
}
