import React from 'react';
import FEBase from './febase';
import { registration } from './api';
import { parse_net_error, is_valid } from "./functions";
import FencerAccreditationDialog from './dialogs/fenceraccreditationdialog';
import { Accordion, AccordionTab } from 'primereact/accordion';
import cloneDeep from 'lodash.clonedeep';
import { InputSwitch } from 'primereact/inputswitch';
import { Checkbox } from 'primereact/checkbox';
import { Button } from 'primereact/button';
import { adjustFencerData } from './lib/registrations';

export default class FEAccreditationTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencers: {},
            fencer: null,
            displayDialog: false,
            displayFilter: false,
            displayEvents: false,
            displayPresent: false,
            selectedEvents: {},
            callPending:false,
            autoRefresh: true
        });
    }

    componentDidMount = () => {
        this._componentDidMount();
        this.getAllRegistrations();
    }

    setConvenienceData = () => {
        this.roleById = this.props.basic ? this.props.basic.rolesById : {};
        this.cntById = this.props.basic ? this.props.basic.countriesById : {};
        this.cntById["soff"]={name:"Officials"};
        this.cntById["sorg"]={name: "Organisation"};

        this.wpnById = this.props.basic ? this.props.basic.weaponsById : {};
        this.catById = this.props.basic ? this.props.basic.categoriesById : {};
        this.cmpById = this.props.basic ? this.props.basic.competitionsById : {};
        this.eventById = this.props.basic ? this.props.basic.sideeventsById : {};
    }

    componentDidUpdate(prevProps) {
        if (this.props.basic.countries.length != prevProps.basic.countries.length) {
            this.setConvenienceData();
            this.getAllRegistrations();
        }
    }

    getAllRegistrations = () => {
        this.setState({registrations: {}});
        this.props.basic.countries.map((cnt) => {
            this.doGetRegistrations(cnt.id,false);
        });
    }

    parseRegistrations = (regs, doclear) => {
        var myregs=cloneDeep(this.state.registered);
        var myfencers=cloneDeep(this.state.fencers);
        var changedfids={};

        regs.map((reg) => {
            var fid = 'f' + reg.fencer;
            var se = 's' + (reg.sideevent ? reg.sideevent : 'n');
            var cnt = 'c' + reg.fencer_data.country;

            var role = reg.role ? parseInt(reg.role) : 0;
            if(role > 0 || se=="sn") {
                var role_obj = this.roleById["r" + role] || null;
                if(role_obj) {
                    if(role_obj.org == "Org") {
                        cnt="sorg"; // organisation 'country'
                    }
                    else if(role_obj.org !== "Country") {
                        cnt="soff"; // officials 'country' (EVF, FIE)

                    }
                    se="r"+role; // sort by roles
                }
            }

            // based on who pays, we can further override the registration sorting
            // do this only for roles that are not federative (so are grouped as support under cnt=sorg)
            // This check allows us to put the registration of invited fencers for, for example, the
            // gala diner under the Organisation/Officials country list instead of under that of the 
            // fencer him/herself.
            if(cnt != "soff" && cnt != "sorg") {
                if(reg.payment == 'E') {
                    cnt="soff";
                }
                else if(reg.payment == 'O') {
                    cnt='sorg';
                }
            }            

            if(!myfencers[fid]) {
                var obj = adjustFencerData(reg.fencer_data, this.props.basic.event);
                myfencers[fid] = obj;
            }
            delete reg.fencer_data;
            myfencers[fid].registration = this.mergeRegs(myfencers[fid].registration, reg);

            // store the registrations both by country and by side event
            if(!myregs.events) myregs.events={};
            if(!myregs.events[se]) myregs.events[se]={};
            myregs.events[se][fid] = true;

            if(!myregs.countries) myregs.countries={};
            if (!myregs.countries[cnt]) myregs.countries[cnt]={};
            myregs.countries[cnt][fid] = true;

            changedfids[fid]=true;
        });
        // simple hack to avoid state changes for countries with no entries
        if(Object.keys(changedfids).length>0) {
            Object.keys(changedfids).map((fid) => {
                myfencers[fid] = this.updateRoles(myfencers[fid]);
            });
            this.setState({ registered: myregs, fencers: myfencers });
        }
        var allcountries=this.state.allCountries - 1;
        if(allcountries>=0) {
            this.setState({allCountries: allcountries});

            if(allcountries === 0) {
                this.startRegularCall("continue");
            }
        }
        this.setState({callPending:false});
    }

    updateRolesOfFencer = (id) => {
        if(id.id) id=id.id;
        var key='f' + id;
        if(this.state.fencers[key]) {
            var fencers=cloneDeep(this.state.fencers);
            fencers[key] = this.updateRoles(fencers[key]);
            this.setState({fencers:fencers});
        }
    }

    updateRoles = (data) => {
        if(!data.registration.roles || !data.registration.regstate) {
            var roles = [];
            var allstates='';
            data.registration.registrations.map((r) => {
                var role = r.role ? parseInt(r.role) : 0;

                if (role > 0) {
                    role = this.roleById["r" + role];
                    if (role) roles.push(role.name);
                }
                else {
                    // add the event abbreviation as a role
                    var se = this.eventById["s" + r.sideevent];
                    if (se) {
                        roles.push(se.abbreviation);
                    }
                }

                if(!r.state || r.state =='' || r.state == 'R') {
                    if(allstates !== '' && allstates !== 'R') {
                        allstates='O'; // other
                    }
                    else allstates='R';
                }
                else if(r.state == 'P') {
                    if (allstates !== '' && allstates !== 'P') {
                        allstates = 'O'; // other
                    }
                    else allstates = 'P';
                }
                else {
                    allstates='O';
                }
            });

            if (roles.length) {
                roles = roles.join(", ");
            }
            else {
                roles = "";
            }

            data.registration.roles=roles;
            data.registration.state = allstates;
        }
        return data;
    }

    mergeRegs = (old, nw) => {
        if(!old) old={};
        if(!old.registrations) {
            old.pending=false;
            old.fid=nw.fencer;
            old.registrations=[];
        }
        var fnd=false;
        var nwr= nw.role ? parseInt(nw.role) : 0;
        var nwse = nw.sideevent ? parseInt(nw.sideevent) : 0;

        old.registrations = old.registrations.map((r) => {
            var rl=r.role ? parseInt(r.role) : 0;
            var se=r.sideevent ? parseInt(r.sideevent) : 0;

            if(rl == nwr && se == nwse) {
                fnd=true;
                return nw;
            }
            return r;
        });
        if(!fnd) {
            old.registrations.push(nw);
        }
        old.roles=null; // force recalculation of roles and state
        return old;
    }

    startRegularCall = (doauto) => {
        
        if(this.state.allCountries > 0) {
            window.setTimeout(()=> this.startRegularCall(doauto), 500);
            return;
        }

        if (doauto === "stop") {
            this.setState({ autoRefresh: false });
            return;
        }

        var countries=[];
        this.props.basic.countries.map((country) => {
            if(is_valid(country.id)) {
                var key="c"+country.id;
                var total=0;
                if(this.state.registered.countries[key]) {
                    total = Object.keys(this.state.registered.countries[key]).length;
                }
                countries.push({country: country,total:total});
            }
        });
        countries.sort((a1,a2) => {
            if(a1.total > a2.total) return 1;
            if(a1.total < a2.total) return -1;
            if(a1.country.name > a2.country.name) return 1;
            if(a1.country.name < a2.country.name) return -1;
            return 0;
        });

        var allbags=null;
        if(doauto != "single") {
            // we create bags of countries to refresh countries with
            // more participants more often than those with few
            var bag=[];
            var bag0=[];
            var bag1=[];
            countries.map((cnt) => {
                if(cnt.total > 3) {
                    bag.push(cnt);
                }
                if(cnt.total < 4 && cnt.total > 0) {
                    bag1.push(cnt);
                }
                if(cnt.total == 0) {
                    bag0.push(cnt);
                }
            });

            allbags = [];
            // call updates for all countries, but those with more participants more often
            allbags = allbags
                .concat(bag.slice())
                .concat(bag1.slice())
                .concat(bag.slice())
                .concat(bag0.slice())
                .concat(bag.slice())
                .concat(bag1.slice());
        }
        else {
            // in case of a single refresh, we want to load all countries once
            allbags=countries.slice();
        }
        this.setState({bags: allbags, bagIndex:0, callPending:false, autoRefresh: true });
        window.setTimeout(() => { this.regularCall(doauto) },300);
    }

    regularCall = (doauto) => {
        // break off if we are no longer auto-refreshing
        if(!this.state.autoRefresh) return;

        if(this.state.callPending) {
            window.setTimeout(() => { this.regularCall(doauto) }, 300);
        }
        else {
            var index=this.state.bagIndex;
            if(index>= this.state.bags.length) {
                if(doauto == "single") {
                    // in case of a single refresh, stop now
                    this.setState({autoRefresh: false});
                    return;
                }
                // else continue with the start of our list-of-bags-of-countries
                index=0;
            }
            var timeout= (doauto == "continue" ? 300 : 1);
            var bag=this.state.bags[index];
            this.setState({bagIndex: index+1, callPending:true}, () => {
                this.doGetRegistrations(bag.country.id, false);
                window.setTimeout(() => {this.regularCall(doauto) }, timeout);
            });
        }
    }

    countryHeader = () => {
        return (null);
    }

    onChange= (fencer,value) => {
        var self=this;
        if(['R','P'].includes(value)) {
            fencer.registration.registrations.map((r) => {
                var oldvalue=r.state;
                r.state = value;
                r.pending=true;

                registration('save', {
                    id: r.id || -1,
                    state: r.state,
                    event: this.props.basic.event.id,
                    sideevent: r.sideevent,
                    fencer: r.fencer
                })
                .then((json) => {
                    r.pending=false;
                    self.replaceRegistration(fencer,r);
                })
                .catch((err) => {
                    r.state=oldvalue;
                    r.pending=false;
                    self.replaceRegistration(fencer,r);
                    return parse_net_error(err);
                });
            });
        }
    }

    replaceRegistration = (fencer,reg) => {
        var key='f' + fencer.id;
        if(this.state.fencers[key]) {
            var fencers=cloneDeep(this.state.fencers);
            fencers[key].registration.registrations = fencers[key].registration.registrations.map((r) => {
                if(reg.id == r.id) return reg;
                return r;
            });
            fencers[key] = this.updateRoles(fencers[key]);
            this.setState({fencers:fencers});
        }
    }

    onSelect = (f) => {
        this.setState({displayDialog: true, fencer: f});
    }

    onDialog = (what,data) => {
        if(what == "save") {
            // data is a list of registrations for the current fencer object
            var key='f' + this.state.fencer.id;
            var fencers=cloneDeep(this.state.fencers);
            if(fencers[key]) {
                fencers[key].registration.registrations=data;
                fencers[key] = this.updateRoles(fencers[key]);
                this.setState({fencers, fencers});
            }
        }
        else if(what=="close") {
            this.setState({displayDialog:false, fencer:null});
        }
    }

    selectFilterEvent = (what, key, state) => {
        var selectedEvents=Object.assign({},this.state.selectedEvents);
        if(Object.keys(selectedEvents).length == 0) {
            Object.keys(this.eventById).map((key) => { selectedEvents[key] = true; });
            Object.keys(this.roleById).map((key) => { selectedEvents[key] = true; })
        }
        selectedEvents[key]=state;
        this.setState({selectedEvents: selectedEvents});
    }

    filterFencers = (fencers,dopresent) => {
        // filter out any registrations that are already present if so marked
        // filter out any registrations not for the indicated events
        var allselected = Object.keys(this.state.selectedEvents).length == 0;
        var myfencers = fencers.filter((fencer) => {
            var ispresent = fencer.registration.state != 'R'; // not still-registered, so present or some combination
            if (!this.state.displayPresent || (!dopresent && !ispresent) || (dopresent && ispresent)) {
                if (allselected) return true;
                var anyfound = false;
                fencer.registration.registrations.map((reg) => {                    
                    var ekey = "s" + reg.sideevent;
                    var rkey = "r" + reg.role;
                    if (this.state.selectedEvents[ekey] || this.state.selectedEvents[rkey]) {
                        anyfound = true;
                    }
                });
                return anyfound;
            }
            return false;
        });
        myfencers.sort((e1, e2) => {
            if (e1.name == e2.name) {
                return e1.firstname > e2.firstname ? 1 : -1;
            }
            else {
                return e1.name > e2.name ? 1 : -1;
            }
        });
        return myfencers;
    }

    renderCountry (cnt,idx,dopresent) {
        var country=this.cntById[cnt];
        if(!country) return (<AccordionTab></AccordionTab>);

        var myfencers=[];
        Object.keys(this.state.registered.countries[cnt]).map((fid) => {  
            var fobj = this.state.fencers[fid];
            if(fobj) {
                myfencers.push(fobj);
            }
        });
        myfencers=this.filterFencers(myfencers,dopresent);

        return (<AccordionTab key={'a'+idx} header={country.name + " (" + myfencers.length + ")"}>
            <table>
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Firstname</th>
                    <th>Year</th>
                    <th>Registration(s)</th>
                    <th>State</th>
                    <th></th>
                    </tr>
                </thead>
                <tbody>
                  {myfencers.map((f,idx) => {
                      var isdisabled = f.registration.state == 'O'; // other than R/<empty> and P
                      var ischecked = f.registration.state == 'P';
                      return (
                      <tr key={idx}>
                          <td>{f.name}</td>
                          <td>{f.firstname}</td>
                          <td>
                              {f.birthyear=="unknown" && ""}
                              {f.birthyear!="unknown" && f.birthyear}
                          </td>
                          <td>{f.registration.roles}</td>
                          <td>
                              {!isdisabled && (<InputSwitch checked={ischecked} onChange={(e) => this.onChange(f, e.value ? "P" : "R")} />)}
                          </td>
                          <td><a onClick={(e) => this.onSelect(f)}><i className='pi pi-chevron-circle-right'></i></a></td>
                      </tr>
                  )})}
                </tbody>
            </table>
        </AccordionTab>);
    }

    renderPerCountry () {
        if(!this.state.registered.countries) return (null);
        // find out all eligible countries left
        var leftover={};
        Object.keys(this.state.registered.countries).map((key) => {
            var total = Object.keys(this.state.registered.countries[key]).length;

            if(total>0) {
                var country=this.cntById[key];
                if(country) {
                    leftover[country.name]=key;
                }
            }
        });
        var keys=Object.keys(leftover);
        keys.sort();
        var sortedlist=[];
        for(var i in keys) {
            var key=keys[i];
            sortedlist.push(leftover[key]);
        }

        var absent = (
                <div className='row'>
                    <div className='col-12'>
                        <h5>Entries per Country{this.state.displayPresent && (<span>&nbsp;(Missing)</span>)}</h5>
                        <Accordion id="accreditcountries" activeIndex={0}>
                            {sortedlist.map((cnt,idx) => this.renderCountry(cnt,idx,false))}
                        </Accordion>
                    </div>
                </div>
        );
        var present=(null);
        if(this.state.displayPresent) {
            present = (
                <div className='row'>
                    <div className='col-12'>
                        <h5>Entries per Country (Present/Absent)</h5>
                        <Accordion id="accreditcountries">
                            {sortedlist.map((cnt, idx) => this.renderCountry(cnt, idx,true))}
                        </Accordion>
                    </div>
                </div>
            );
        }
        return (<div className='container'>
            {absent}
            {present}
        </div>)
    }


    renderEvent (obj,idx, dopresent) {
        if(!obj) return (<AccordionTab></AccordionTab>);

        var myfencers=[];
        var key = (obj.title ? 's' : 'r' )+ obj.id;
        var title = obj.title ? 'Event '+ obj.title : 'Role '+ obj.name;
        Object.keys(this.state.registered.events[key]).map((fid) => {  
            var fobj = this.state.fencers[fid];
            if(fobj) {
                myfencers.push(fobj);
            }
        });

        myfencers=this.filterFencers(myfencers,dopresent);

        return (<AccordionTab key={'a'+idx} header={title + " (" + myfencers.length + ")"}>
            <table>
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Firstname</th>
                    <th>Country</th>
                    <th>Year</th>
                    <th>Registration(s)</th>
                    <th>State</th>
                    <th></th>
                    </tr>
                </thead>
                <tbody>
                  {myfencers.map((f,idx) => {
                      var isdisabled = f.registration.state == 'O'; // other than R/<empty> and P
                      var ischecked = f.registration.state == 'P';
                      var cnt=this.cntById['c'+f.country];
                      return (
                      <tr key={idx}>
                          <td>{f.name}</td>
                          <td>{f.firstname}</td>
                          <td>{cnt && cnt.abbr}</td>
                          <td>
                              {f.birthyear=="unknown" && ""}
                              {f.birthyear!="unknown" && f.birthyear}
                          </td>
                          <td>{f.registration.roles}</td>
                          <td>
                              {!isdisabled && (<InputSwitch checked={ischecked} onChange={(e) => this.onChange(f, e.value ? "P" : "R")} />)}
                          </td>
                          <td><a onClick={(e) => this.onSelect(f)}><i className='pi pi-chevron-circle-right'></i></a></td>
                      </tr>
                  )})}
                </tbody>
            </table>
        </AccordionTab>);
    }

    renderPerEvent() {
        if (!this.state.registered.events) return (null);
        // find out all eligible countries left
        var leftover = [];
        var allselected=Object.keys(this.state.selectedEvents).length == 0;
        Object.keys(this.state.registered.events).map((key) => {
            var total = Object.keys(this.state.registered.events[key]).length;

            if (total > 0) {
                var event = this.eventById[key];
                if (event && (allselected || this.state.selectedEvents[key])) {
                    leftover.push(event);
                }
                else {
                    var role=this.roleById[key];
                    if (role && (allselected || this.state.selectedEvents[key])) {
                        leftover.push(role);
                    }
                }
            }
        });

        leftover.sort(function(a,b) {
            if(a.name && b.title) {
                return 1;
            }
            if(a.title && b.name) {
                return -1;
            }

            var v1=a.title ? a.title : a.name;
            var v2=b.title ? b.title : b.name;
            if(v1==v2) return 0;
            if(v1>v2) return 1;
            return -1;
        });

        var absent= (<div className='row'>
                    <div className='col-12'>
                        <h5>Entries per Event/Role{this.displayPresent && (<span>&nbsp;(Missing)</span>)}</h5>
                        <Accordion id="accreditcountries" activeIndex={0}>
                            {leftover.map((obj, idx) => this.renderEvent(obj, idx,false))}
                        </Accordion>
                    </div>
                </div>);
        var present=(null);
        if(this.state.displayPresent) {
            present = (<div className='row'>
                <div className='col-12'>
                    <h5>Entries per Event/Role (Present/Absent)</h5>
                    <Accordion id="accreditcountries">
                        {leftover.map((obj, idx) => this.renderEvent(obj, idx, true))}
                    </Accordion>
                </div>
            </div>);
        }
        return (<div className='container'>
            {absent}
            {present}
        </div>)
    }

    renderHeader() {
        if(!this.state.displayFilter) {
            return (<div className='wrapper clearfix filter-header'>
                <h5 className='filter-event'>Filter Settings</h5>
                <div className='right'>
                    <i className="pi pi-chevron-right" onClick={(e) => this.setState({ displayFilter: true })}></i>
                </div>
            </div>);
        }
        else {
            var ischecked=this.state.displayEvents;
            var ischecked2=this.state.displayPresent;
            var ischecked3 = this.state.autoRefresh;
            var selectedEvents = Object.assign({},this.state.selectedEvents);
            if(Object.keys(selectedEvents).length == 0) {
                // if no events selected, select all events and roles
                Object.keys(this.eventById).map((key) => {selectedEvents[key]=true; });
                Object.keys(this.roleById).map((key) => {selectedEvents[key]=true; })
            }

            // sort the roles by role category
            var sortedRoles={};
            Object.keys(this.roleById).map((key, idx) => {
                var role=this.roleById[key];
                var rtype=role.org;
                if(!sortedRoles[rtype]) sortedRoles[rtype]=[];
                sortedRoles[rtype].push(key);
            });

            return (<div className='wrapper clearfix filter-header'>
                <h5 className='filter-event'>Filter Settings</h5>
                <div className='clearfix'>
                    <div className='right'>
                    <i className="pi pi-chevron-down" onClick={(e) => this.setState({displayFilter: false})}></i>
                    </div>
                </div>
                <div className='clearfix filter-header'>
                    <InputSwitch checked={ischecked} onChange={(e) => this.setState({displayEvents: e.value })} /> List per Event
                </div>
                <div className='clearfix filter-header'>
                    <InputSwitch checked={ischecked2} onChange={(e) => this.setState({ displayPresent: e.value })} /> Separate in Absent/Present
                </div>
                <div className='clearfix filter-header'>
                    <InputSwitch checked={ischecked3} onChange={(e) => this.startRegularCall(e.value ? "continue":"stop")} /> Automatically refresh
                </div>
                <div className='filter-header'>
                    <h5>Restrict listing to the following events and roles</h5>
                    <div className='clearfix'>
                {Object.keys(this.eventById).map((key,idx) => {
                    return (
                        <div className='filter-event' key={'filter-'+idx}>
                            <Checkbox name={'fe-'+key} checked={selectedEvents[key] ? true:false} onChange={(e) => this.selectFilterEvent("event",key,e.checked)} /> {this.eventById[key].title} ({this.eventById[key].abbreviation})
                        </div>
                    );
                })}
                    </div>
                    <br />
                {Object.keys(sortedRoles).map((skey,idx1) => {
                    return (
                        <div className='clearfix' key={'filter-'+idx1}>
                        {sortedRoles[skey].map((key,idx2) => {
                            return (<div className='filter-event' key={'filter-' + idx1+'-'+idx2}>
                                  <Checkbox name={'fe-' + key} checked={selectedEvents[key] ? true : false} onChange={(e) => this.selectFilterEvent("role", key, e.checked)} /> {this.roleById[key].name}
                                </div>);
                        })}
                        </div>);
                })}
                </div>
            </div>
            );
        }
    }

    renderContent () {
        this.setConvenienceData();
        return (<div>
            {this.renderHeader()}
            {!this.state.autoRefresh && (
                <div>
                    <Button label="Refresh" icon="pi pi-refresh" className="p-button-raised" onClick={(e) => this.startRegularCall("single")} />
                </div>
            )}
            {!this.state.displayEvents && (<div>
                {this.renderPerCountry()}
            </div>)}
            
            {this.state.displayEvents && (<div>
                {this.renderPerEvent()}
            </div>)}
            <FencerAccreditationDialog value={this.state.fencer} display={this.state.displayDialog} events={this.props.basic.sideevents} onClose={() => this.onDialog('close')} onChange={(itm) => this.onDialog('save', itm)} basic={this.props.basic}/>
        </div>);
    }
}
