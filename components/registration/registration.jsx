import { fencers, accreditation } from "../api.js";
import { Accordion, AccordionTab } from 'primereact/accordion';
import { InputText } from 'primereact/inputtext';
import { Button } from 'primereact/button';
import { parse_date, format_date, 
         is_organisation, is_sysop, is_hod, is_accreditor, is_organiser, is_hod_view } from '../functions';
import React from 'react';
import FencerDialog from './dialogs/fencerdialog';
import FencerSelectDialog from './dialogs/fencerselectdialog';
import CSVUploadDialog from "./dialogs/csvuploaddialog.jsx";
import { ParticipantList } from './elements/participantlist';
import FEBase from './base';
import { filter_event_category, filter_event_category_younger } from "./rules/wrong_category.jsx";
import { filter_event_team_veterans } from "./rules/team_rule_veterans.jsx";
import { filter_event_team_grandveterans } from "./rules/team_rule_grandveterans";
import { adjustFencerData, updateFencerData, updateFencerRegistrations } from "../lib/registrations.js";
import { defaultPayment } from "../lib/defaultPayment";

export default class FERegistrationTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencer: "",
            fencer_object: { id: -1, name: '' },
            displayFencerDialog: false,
            displaySelectDialog: false,
            addingNewRegistration: false,
            searchingForFencer: true,
            accreditations: [],
            fencer_events: [], // sideevents with fencer-specific convenience data
            displayUploadDialog: false,
        });
    }

    onCountrySelect = (val) => {
        // retrieve the list of registrations for the selected country
        this.setState({ 'country': val, "country_item": this.countryFromId(val) }, this.getRegistrations);
    }

    uploadCSV = () => {
        this.setState({displayUploadDialog: true});
    }

    onUpload = (tp, items) => {
        if (tp == 'close') {
            this.setState({ displayUploadDialog: false }, this.getRegistrations);
        }
    }

    addFencer = () => {
        var dt=parse_date(this.props.basic.event.opens);
        dt.add(-40,'y');
        this.setState({fencer_object: {
            id: -1,
            name:"",
            firstname:"",
            birthday: format_date(dt),
            country: this.state.country
        }, displayFencerDialog:true, addingNewRegistration: true, searchingForFencer: true});
    }

    onFencerEdit = (fencer) => {
        this.setState({fencer_object: fencer, displayFencerDialog: true, addingNewRegistration: false, searchingForFencer: false});
    }

    onFencer = (tp, itm, extra) => {
        if (tp == 'change') {
            this.setState({ fencer_object: itm, searchingForFencer: !extra });
        }
        else if (tp == 'close') {
            this.setState({ displayFencerDialog: false });
        }
        else if (tp == 'save') {
            itm = adjustFencerData(itm, this.props.basic.event);
            var newlist=updateFencerData(this.state.registered, itm); // replace data, keep registrations
            this.setState({registered:newlist}, () => { if (this.state.addingNewRegistration) this.onFencerSelect(itm); });
        }
    }

    onFencerSelection = (tp, itm) => {
        if (tp == 'change') {
            var newlist=updateFencerRegistrations(this.state.registered, itm);
            itm.defaultPayment = itm.defaultPayment || defaultPayment(this.state.country_item, itm);
            this.setState({ selected_fencer: itm, registered: newlist});
        }
        else if (tp == 'close') {
            this.setState({ displaySelectDialog: false });
        }
        else if (tp == 'save') {
            // state changes were already effected
        }
    }

    onFencerSelect = (itm) => {
        // newly suggested fencers do not have a registration list yet
        // unless they were already enrolled
        var newlist = updateFencerData(this.state.registered, itm);
        var events=this.selectEventsForFencer(itm);

        // for accreditation purposes, retrieve the list of current badges first
        if(is_accreditor() || is_organiser() || is_sysop()) {
            this.loadAccreditations(this.props.basic.event.id, itm.id);
        }
        itm.defaultPayment = defaultPayment(this.state.country_item, itm);
        this.setState({displaySelectDialog: true, selected_fencer: itm, fencer_events:events, registered: newlist});
    }

    loadAccreditations = (eid,fid) => {
        if(!eid && !fid) {
            this.setState({accreditations:[]});
        }
        else {
            return accreditation("fencer", { event: eid, fencer: fid })
                .then((json) => {
                    if(json.data.list) {
                        this.setState({accreditations: json.data.list});
                    }
                });
        }
    }

    selectEventsForFencer = (fencer) => {
        // filter the available events based on category and gender
        var events=[];
        var allow_registration_lower_age = this.props.basic.event.config && this.props.basic.event.config.allow_registration_lower_age;

        // filter out valid roles for the capabilities
        var roles = this.props.basic.roles.filter((itm) => {
            if (is_hod() && itm.org=='Country')  return true;
            if (is_organisation() && (itm.org == 'Org' || itm.org=='Country')) return true;
            if (is_sysop()) return true; // allow all roles for system administrators
            return false;
        });
        roles.sort((a,b) => {
            if (a.org == 'Country' && b.org != 'Country') return -1;
            if (b.org=='Country' && a.org!='Country') return 1;
            if (a.org=='Org' && b.org != 'Org') return -1;
            if (b.org == 'Org' && a.org != 'Org') return 1;

            if(a.name < b.name) return -1;
            if(a.name > b.name) return 1;
            return 0;
        });

        if (fencer && this.props.basic && this.props.basic.sideevents && this.props.basic.event) {
            var weaponevents={}; // stores the events qualified for this fencer based on weapon
            var allweaponevents={}; // stores all events based on weapon

            events = this.props.basic.sideevents.map((event) => {
                var ev = Object.assign({}, event);
                ev.is_athlete_event = false; // is this a competition event selectable for this specific athlete
                ev.is_team_event = false; // is this a competition event selectable for this specific athlete AND a team event
                ev.default_role = "0"; // regular participant
                ev.is_sideevent=false; // is this a non-competition event

                if (ev.competition) {
                    ev.default_role = roles[0].id; // any non-athlete role
                    if (ev.category && ev.weapon) {
                        ev.is_athlete_event = filter_event_category(fencer,ev)
                                || filter_event_team_veterans(fencer,ev)
                                || filter_event_team_grandveterans(fencer,ev);

                        if(ev.is_athlete_event) {
                            ev.default_role = "0";
                            if(ev.category.type == 'T') {
                                ev.is_team_event = true;
                                weaponevents["T"+ev.weapon.abbr]=ev; // allow individual and team events in the same tournament
                            }
                            else {
                                weaponevents[ev.weapon.abbr]=ev;
                            }
                        }
                        if(allow_registration_lower_age && filter_event_category_younger(fencer,ev)) {
                            // create a list of all events that match gender and are for a younger category
                            if(!allweaponevents[ev.weapon.abbr]) allweaponevents[ev.weapon.abbr]=[];
                            allweaponevents[ev.weapon.abbr].push(ev);
                        }
                    }
                }
                else {
                    // not an athlete event
                    ev.is_sideevent=true;
                }
                return ev;
            });

            // see if we need to open up events of a younger category
            if(allow_registration_lower_age) {
                var openevents={};
                for(var i in allweaponevents) {
                    // look only in events that have no athlete-weapon-event for this fencer
                    if(!weaponevents[i]) {
                        // check all events and pick the one for the highest category
                        // only events that are for younger categories are listed at this point
                        var highestcat=-1;
                        for(var j in allweaponevents[i]) {
                            var ev=allweaponevents[i][j];
                            if(ev.category.value > highestcat) highestcat=ev.category.value;
                        }
                        openevents[i]=highestcat;
                    }
                }

                // now set the is_athlete flag on the events we need to open for a younger category as well
                events = events.map((ev) => {
                    if (ev.competition && ev.category && ev.weapon) {
                        var hasOwnCat = openevents[ev.weapon.abbr];
                        if(hasOwnCat && ev.weapon.gender == fencer.gender && ev.category.value == hasOwnCat) {
                            ev.is_athlete_event = true;
                            ev.default_role="0"; // default role for athlete events is athlete
                        }
                    }
                    return ev;
                });
            }
        }
        return events;
    }

    renderContent() {
        // map participants per side event, so we can show totals of participants
        var pcount={"all":0};
        var acount={"all":0};
        var sevents={};
        this.props.basic.sideevents.map((s) => {
            var skey="s"+ s.id;
            sevents[skey]=s;
            pcount[skey]=0;
            acount[skey]=0;
        });
        var allteams={};
        Object.keys(this.state.registered).map((key) => {
            var fencer=this.state.registered[key];
            if(fencer.registrations && fencer.registrations.length > 0) {
                pcount["all"]+=1;
                var isathlete=false;
                fencer.registrations.map((reg) => {
                    var skey = "s" + reg.sideevent;
                    if(sevents[skey]) {
                        if(sevents[skey].competition && parseInt(reg.role) == 0) {
                            isathlete=true;
                            acount[skey]+=1;

                            // if this registration is for a team, keep track of it
                            if(reg.team && reg.team.length>0) {
                                var key2="k"+reg.sideevent;
                                if(!allteams[key2]) allteams[key2]={};
                                allteams[key2][reg.team]=true;
                            }
                        }
                        pcount[skey]+=1;
                    }
                });
                if(isathlete) {
                    acount["all"]+=1;
                }
            }
        });
        Object.keys(allteams).map((key) => {
            // sort the team names and convert to list of values
            var keys=Object.keys(allteams[key]);
            keys.sort();
            allteams[key]=keys;                
        });

        var addcountries=[this.state.country_item];
        if (is_organiser()) {
            addcountries = this.props.basic.countries.filter((item) => {
                return item.name != 'Organisers';
            });
        }

        return (<div>
            {!is_hod_view() && this.renderHeader(addcountries)}
            {this.renderParticipants(allteams)}
            </div>
        );
    }

    renderHeader(addcountries) {
        return (<div className='row topmargin'>
            <div className='col-6 vertcenter'>
                <Button label="Add Registration" icon="pi pi-plus" className="p-button-raised cright" onClick={this.addFencer} />
                <FencerDialog basic={this.props.basic} country={this.state.country} countries={addcountries} onClose={() => this.onFencer('close')} onChange={(itm, doSelect) => this.onFencer('change', itm, doSelect)} onSave={(itm) => this.onFencer('save', itm)} delete={false} display={this.state.displayFencerDialog} fencer={this.state.fencer_object} allowSearch={this.state.searchingForFencer} />
            </div>
            <div className='col-3 offset-3 vertcenter'>
                <Button label="Upload CSV" icon="pi pi-upload" className="p-button-raised cright" onClick={this.uploadCSV} />
                <CSVUploadDialog basic={this.props.basic} country={this.state.country} countries={addcountries} onClose={() => this.onUpload('close')} onSave={(items) => this.onUpload('save', items)} delete={false} display={this.state.displayUploadDialog} width='80vw'/>
            </div>
        </div>);
    }

    renderParticipants(allteams) {
        return (<div className='row topmargin'>
            <ParticipantList basic={this.props.basic} showRoles camera fencers={this.state.registered} onSelect={this.onFencerSelect} onEdit={this.onFencerEdit} allfencers={true}/>
            <FencerSelectDialog value={this.state.selected_fencer} display={this.state.displaySelectDialog} events={this.state.fencer_events} onClose={() => this.onFencerSelection('close')} onChange={(itm) => this.onFencerSelection('change', itm, true)} onSave={(itm) => this.onFencerSelection('save', itm)} basic={this.props.basic} country={this.state.country_item} accreditations={this.state.accreditations} reloadAccreditations={this.loadAccreditations} teams={allteams}/>
        </div>);
    }

}
