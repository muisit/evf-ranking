import { fencers, accreditation } from "./api.js";
import { Accordion, AccordionTab } from 'primereact/accordion';
import { InputText } from 'primereact/inputtext';
import { parse_date, date_to_category_num, format_date, is_organisation, is_sysop, is_hod, is_valid, my_category_is_older, is_accreditor, is_organiser } from './functions';
import React from 'react';
import FencerDialog from './dialogs/fencerdialog';
import FencerSelectDialog from './dialogs/fencerselectdialog';
import { ParticipantList } from './elements/participantlist';
import FEBase from './febase';

export default class FERegistrationTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencer: "",
            fencer_object: { id: -1 },
            displayFencerDialog: false,
            displaySelectDialog: false,
            suggestions: [],
            accreditations: [],
            fencer_events: [], // sideevents with fencer-specific convenience data
        });
    }

    autocomplete = (evt) => {
        this.setState({fencer:evt.target.value});
        var thistarget=evt.target.value;

        if(thistarget.length > 0) {
            var filters = { name: evt.target.value};
            if(this.state.country_item && is_valid(this.state.country_item.id)) {
                filters.country=this.state.country;
            }
            fencers(0, 10000, filters, "nf")
                .then((json) => {
                    if(this.state.fencer == thistarget) {
                        var fencers=[];
                        json.data.list.map((itm)=> {
                            itm = this.adjustFencerData(itm);

                            // see if we already have this item in our list of registered fencers
                            // If so, replace with our local version to retain the registrations
                            if(this.state.registered["k" + itm.id]) {
                                itm = this.state.registered["k"+itm.id];
                            }
                            fencers.push(itm);
                        });
                        this.setState({suggestions: fencers });
                    }
                });
        }
        else {
            this.setState({ suggestions: [] });
        }
    }

    addFencer = () => {
        var dt=parse_date(this.props.item.opens);
        dt.add(-40,'y');
        this.setState({fencer_object: {
            id: -1,
            name:"",
            firstname:"",
            birthday: format_date(dt),
            country: this.state.country
        }, displayFencerDialog:true});
    }

    onFencerEdit = (fencer) => {
        this.setState({fencer_object: fencer, displayFencerDialog: true});
    }

    onFencer = (tp, itm) => {
        if (tp == 'change') {
            this.setState({ fencer_object: itm });
        }
        else if (tp == 'close') {
            this.setState({ displayFencerDialog: false });
        }
        else if (tp == 'save') {
            itm = this.adjustFencerData(itm);
            this.replaceRegistration(itm);
            this.setState({suggestions:[itm]});
        }
    }

    onFencerSelection = (tp, itm) => {
        if (tp == 'change') {
            this.setState({ selected_fencer: itm });
            this.changeSingleRegisteredFencer(itm);
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
        if (!itm.registrations) itm.registrations=[];
        var key = "k" + itm.id;
        var registered = Object.assign({}, this.state.registered);
        registered[key] = itm;
        var events=this.selectEventsForFencer(itm);

        // for accreditation purposes, retrieve the list of current badges first
        if(is_accreditor() || is_organiser() || is_sysop()) {
            this.loadAccreditations(this.props.item.id, itm.id);
        }
        this.setState({displaySelectDialog: true, selected_fencer: itm, registered:registered, fencer_events:events });
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
        var cmpById = {};
        for (var i in this.state.competitions) {
            var c = this.state.competitions[i];
            cmpById["k" + c.id] = c;
        }
        var wpnById = {};
        for (var i in this.props.weapons) {
            var c = this.props.weapons[i];
            wpnById["k" + c.id] = c;
        }
        var catById = {};
        for (var i in this.props.categories) {
            var c = this.props.categories[i];
            catById["k" + c.id] = c;
        }

        var mycat = null;
        var events=[];

        // filter out valid roles for the capabilities
        var roles = this.props.roles.filter((itm) => {
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

        if (fencer && this.state.sideevents && this.props.item) {
            mycat = date_to_category_num(fencer.birthday, this.props.item.opens);

            var weaponevents={};
            var allweaponevents={};

            events = this.state.sideevents.map((event) => {
                var ev = Object.assign({}, event);
                ev.is_athlete_event = false;
                ev.default_role = "0"; // regular participant
                ev.is_sideevent=false;

                if (ev.competition_id && cmpById["k" + ev.competition_id]) {
                    ev.competition = cmpById["k" + ev.competition_id];
                    ev.weapon = wpnById["k" + ev.competition.weapon];
                    ev.category = catById["k" + ev.competition.category];

                    ev.default_role = roles[0].id; // any non-athlete role
                    if (ev.category && ev.weapon) {
                        if (ev.category.value == mycat && ev.weapon.gender == fencer.gender) {
                            ev.is_athlete_event = true;
                            ev.default_role="0"; // default role for athlete events is athlete
                            weaponevents[ev.weapon.abbr]=ev;
                        }
                        if(my_category_is_older(mycat,ev.category.value) && ev.weapon.gender == fencer.gender) {
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
            var openevents={};
            for(var i in allweaponevents) {
                // look only in events that have no athlete-weapon-event for this fencer
                if(!weaponevents[i]) {
                    // check all events and pick the one for the highest category
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
                if (ev.competition_id && cmpById["k" + ev.competition_id]) {
                    if (ev.category && ev.weapon) {
                        var hasOwnCat = openevents[ev.weapon.abbr];
                        if(hasOwnCat && ev.weapon.gender == fencer.gender && ev.category.value == hasOwnCat) {
                            ev.is_athlete_event = true;
                            ev.default_role="0"; // default role for athlete events is athlete
                        }
                    }
                }
                return ev;
            });
        }
        return events;
    }

    renderContent() {
        // map participants per side event, so we can show totals of participants
        var pcount={"all":0};
        var acount={"all":0};
        var sevents={};
        this.state.sideevents.map((s) => {
            var skey="s"+ s.id;
            sevents[skey]=s;
            pcount[skey]=0;
            acount[skey]=0;
        });
        Object.keys(this.state.registered).map((key) => {
            var fencer=this.state.registered[key];
            if(fencer.registrations && fencer.registrations.length > 0) {
                pcount["all"]+=1;
                var isathlete=false;
                fencer.registrations.map((reg) => {
                    var skey = "s" + reg.sideevent;
                    if(sevents[skey]) {
                        if(parseInt(sevents[skey].competition_id) > 0 && parseInt(reg.role) == 0) {
                            isathlete=true;
                            acount[skey]+=1;
                        }
                        pcount[skey]+=1;
                    }
                });
                if(isathlete) {
                    acount["all"]+=1;
                }
            }
        });

        var addcountries=[this.state.country_item];

        return (
            <div className='row topmargin'>
                <div className='col-4 vertcenter'>Search fencers:</div>
                <div className='col-8'>
                    <InputText value={this.state.fencer} onChange={(e) => this.autocomplete(e)} />
                </div>
                <div className='col-12'>
                    {this.state.suggestions.length ==0 && (
                        <div className='subtitle center'>No fencers found</div>
                    )}
                    {this.state.suggestions.length > 0 && (
                        <ParticipantList fencers={this.state.suggestions} onSelect={this.onFencerSelect} onEdit={this.onFencerEdit}/>
                    )}
                    <div className="cright" onClick={this.addFencer}>Add New Fencer</div>
                    <FencerDialog apidata={{event: this.props.item.id, country: this.state.country }} country={this.props.country} countries={addcountries} onClose={() => this.onFencer('close')} onChange={(itm) => this.onFencer('change', itm)} onSave={(itm) => this.onFencer('save', itm)} delete={false} display={this.state.displayFencerDialog} value={this.state.fencer_object} />
                    <FencerSelectDialog value={this.state.selected_fencer} display={this.state.displaySelectDialog} events={this.state.fencer_events} onClose={() => this.onFencerSelection('close')} onChange={(itm) => this.onFencerSelection('change', itm)} onSave={(itm) => this.onFencerSelection('save', itm)} roles={this.props.roles}  event={this.props.item} country={this.state.country_item} accreditations={this.state.accreditations} reloadAccreditations={this.loadAccreditations}/>
                </div>
                <div className='col-12'>
                    {(is_hod() && is_valid(this.state.country_item.id)) && (
                    <Accordion id="evfrankingacc" activeIndex={0}>
                        <AccordionTab header={"All Participants (" + pcount["all"] + ")"}>
                                <ParticipantList roles={this.props.roles} country={this.state.country_item} camera fencers={this.state.registered} onSelect={this.onFencerSelect} allfencers={true} events={this.state.sideevents} competitions={this.state.competitions} categories={this.props.categories} onEdit={this.onFencerEdit}/>
                        </AccordionTab>
                        {this.state.sideevents.map((itm,idx) => (
                            <AccordionTab header={itm.title + " (" + (is_valid(itm.competition_id) ? acount["s" + itm.id] : pcount["s" + itm.id]) + ")"} key={idx}>
                                <ParticipantList event={itm} fencers={this.state.registered} onSelect={this.onFencerSelect} roles={this.props.roles} competitions={this.state.competitions} categories={this.props.categories} onEdit={this.onFencerEdit}/>
                            </AccordionTab>
                        ))}
                    </Accordion>)}
                    {is_organisation() && (
                    <Accordion id="evfrankingacc" activeIndex={0}>
                            <AccordionTab header={"All Participants (" + pcount["all"] + ")"}>
                                <ParticipantList roles={this.props.roles} country={this.state.country_item} camera fencers={this.state.registered} onSelect={this.onFencerSelect} allfencers={true} events={this.state.sideevents} competitions={this.state.competitions} categories={this.props.categories} onEdit={this.onFencerEdit}/>
                            </AccordionTab>
                    </Accordion>)}
                </div>
            </div>
        );
    }

}
