import React from 'react';
import { TabView, TabPanel } from 'primereact/tabview';
import FERegistrationTab from './feregistrationtab';
import FECashierTab from './fecashiertab';
import FEAccreditorTab from './feaccreditortab';
import FEAccrTemplateTab from './feaccrtemplatetab';
import FEOverviewTab from "./feoverviewtab";
import FEAccreditationTab from './feaccreditationtab';
import { Loading } from './elements/loading';

import { countries, singleevent, weapons, categories, roles, competitions, sideevents } from "./api.js";
import { parse_date, is_hod, is_organiser, is_sysop, is_organisation, is_registrar, is_accreditor, is_cashier,
        create_abbr,
        create_countryById, create_roleById, create_cmpById, create_wpnById, create_catById, create_sideeventById  } from "./functions";

export default class RegistrationPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true, // loading basic event data, nothing to show
            loading: {}, // loading additional data, can show basic information
            event: {},
            notopenyet: true,
            isclosed: true,
            categories: [],
            categoriesById: {},
            countries: [],
            countriesById: {},
            weapons: [],
            weaponsById: {},
            roles: [],
            rolesById: {},
            competitions: [],
            competitionsById: {},
            sideevents: [],
            sideeventsById: {},
            sortedSideEvents: [],
            basicdata: {}
        };
    }

    unload = (key, value) => {
        this.setState((state) => {
            if (state.loading[key] && state.loading[key].value == value) {
                state.loading[key].state = true;
            }
            return { loading: state.loading };
        });
    }

    onload = (key, label, value) => {
        this.setState((state) => {
            state.loading[key] = { label: label, state: false, value: value };
            return { loading: state.loading };
        });
    }

    sortCompetitions = (competitions, weapons, categories) => {
        if(competitions && competitions.length > 0 && Object.keys(weapons).length > 0 && Object.keys(categories).length > 0) {
            var cmpById = create_cmpById(competitions,weapons,categories);
            this.setState({competitionsById: cmpById});
            return cmpById;
        }
        return {};
    }

    sortSideEvents = (sideevents, cmpById) => {
        console.log("sorting side events using ",sideevents);
        if (sideevents && cmpById && sideevents.length > 0 && Object.keys(cmpById).length > 0) {
            var sortedevents = sideevents.slice();

            // link the competition object and create abbreviations
            sortedevents = sortedevents.map((ev) => {
                ev.competition=null;
                if(ev.competition_id) {
                    var key="c"+ev.competition_id;
                    if(cmpById[key]) {
                        ev.competition = cmpById[key];
                        ev.weapon=ev.competition.weapon;
                        ev.category=ev.competition.category;
                        cmpById[key].sideevent=ev;
                    }
                }
                ev.abbreviation = create_abbr(ev);
                return ev;
            });

            // this also applies the competition to the event
            var byId = create_sideeventById(sortedevents, cmpById);
            // first integrate all competitions into the sortedevents

            // Requirement 3.1.2: sort events first, then by title
            sortedevents.sort(function (e1, e2) {
                // we sort competitions first, so if one item has a competition_id and the other not, return a value
                if (e1.competition && !e2.competition) return -1; // e1 before e2
                if (e2.competition && !e1.competition) return 1; // e2 before e1

                // else compare only on title
                return e1.title > e2.title;
            });

            this.setState({ sortedSideevents: sortedevents, sideeventsById: byId });
            console.log("returning ",sortedevents);
            return {"sorted": sortedevents, "byId": byId};
        }
        return {};
    }

    createBasicData = (sideevents, seBI, competitions, cBI, weapons, wBI, categories, ctBI, roles, rBI, countries,cntBI) => {
        // if sideevents and competitions are set, event is also set
        if(sideevents.length && competitions.length && weapons.length && categories.length && roles.length && countries.length) {
            var obj = {
                event: this.state.event,
                sideevents: sideevents,
                sideeventsById: seBI,
                competitions: competitions,
                competitionsById: cBI,
                weapons: weapons,
                weaponsById: wBI,
                categories: categories,
                categoriesById: ctBI,
                roles: roles,
                rolesById: rBI,
                countries:countries,
                countriesById: cntBI
            };
            this.setState({basicdata: obj, initializing: false });
        }
    }

    componentDidMount = () => {
        this.onload("event", "Loading event data", evfranking.eventid);
        singleevent("view",{id: evfranking.eventid})
            .then(json => {
                this.unload("event", evfranking.eventid);

                var opensat = parse_date(json.data.item.reg_open);
                var closesat = parse_date(json.data.item.reg_close);
                var now = parse_date();
                this.setState({
                    event: json.data.item,  
                    notopenyet: now.isBefore(opensat),
                    isclosed: closesat.isBefore(now)
                });

                var eventid=json.data.item.id;
                // now load the competitions and side-events
                this.onload("sideevents", "Loading side events", eventid);
                sideevents(eventid).then((json) => {
                    this.unload("sideevents", eventid);

                    if(json && json.data && json.data.list && json.data.list.length) {
                        this.setState({ sideevents: json.data.list });
                        console.log("sorting side events after load returns");
                        var retval = this.sortSideEvents(json.data.list, this.state.competitionsById);

                        // see if this was the last step and we can create the basic combined data object
                        if(retval.sorted && retval.byId) {
                            this.createBasicData(retval.sorted, retval.byId, this.state.competitions, this.state.competitionsById,
                                this.state.weapons, this.state.weaponsById, this.state.categories, this.state.categoriesById,
                                this.state.roles, this.state.rolesById, this.state.countries, this.state.countriesById);
                        }

                    }
                });

                this.onload("competitions", "Loading competitions", eventid);
                competitions(eventid).then((cmp) => {
                    if (cmp && cmp.data && cmp.data.list) {
                        this.unload("competitions", eventid);
                        
                        this.setState({ competitions: cmp.data.list });
                        var cmpById = this.sortCompetitions(cmp.data.list, this.state.weaponsById, this.state.categoriesById);
                        console.log("sorting side events after competitions return");
                        var retval = this.sortSideEvents(this.state.sideevents, cmpById);

                        if (retval.sorted && retval.byId) {
                            this.createBasicData(retval.sorted, retval.byId, cmp.data.list, cmpById,
                                this.state.weapons, this.state.weaponsById, this.state.categories, this.state.categoriesById,
                                this.state.roles, this.state.rolesById, this.state.countries, this.state.countriesById);
                        }

                    }
                });

            });
        this.onload("countries", "Loading country data", "countries");
        countries(0, 1000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("countries", "countries");
                    var countriesById = create_countryById(json.data.list);
                    this.setState({ countries: json.data.list, countriesById: countriesById });
                    this.createBasicData(this.state.sortedSideEvents, this.state.sideeventsById, this.state.competitions, this.state.competitionsById,
                        this.state.weapons, this.state.weaponsById, this.state.categories, this.state.categoriesById,
                        this.state.roles, this.state.rolesById, json.data.list, countriesById);                    
                }
            });
        this.onload("categories", "Loading category data", "categories");
        categories(0, 1000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("categories", "categories");
                    var categoriesById = create_catById(json.data.list);
                    var cmpById = this.sortCompetitions(this.state.competitions, this.state.weaponsById, categoriesById);
                    this.setState({ categories: json.data.list, categoriesById: categoriesById });
                    this.createBasicData(this.state.sortedSideEvents, this.state.sideeventsById, this.state.competitions, cmpById,
                        this.state.weapons, this.state.weaponsById, json.data.list, categoriesById,
                        this.state.roles, this.state.rolesById, this.state.countries, this.state.countriesById);                    
                }
            });
        this.onload("roles", "Loading role data", "roles");
        roles(0, 10000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("roles", "roles");
                    var rolesById = create_roleById(json.data.list);
                    this.setState({ roles: json.data.list, rolesById: rolesById });
                    this.createBasicData(this.state.sortedSideEvents, this.state.sideeventsById, this.state.competitions, this.state.competitionsById,
                        this.state.weapons, this.state.weaponsById, this.state.categories, this.state.categoriesById,
                        json.data.list, rolesById, this.state.countries, this.state.countriesById);  
                    }                  
            });
        this.onload("weapons", "Loading weapon data", "weapons");
        weapons(0, 1000, '', "n")
            .then(json => {
                this.unload("weapons", "weapons");
                var byId = create_wpnById(json.data.list);
                var cmpById = this.sortCompetitions(this.state.competitions, byId, this.state.categoriesById);
                this.setState({ weapons: json.data.list, weaponsById: byId });
                this.createBasicData(this.state.sortedSideEvents, this.state.sideeventsById, this.state.competitions, cmpById,
                    json.data.list, byId, this.state.categories, this.state.categoriesById,
                    this.state.roles, this.state.rolesById, this.state.countries, this.state.countriesById);                    
            });
    }

    render() {
        if (this.state.initializing) {
            return (<Loading loading={this.state.loading} />);
        }
        if (this.state.item === null) {
            return (<div>
                <h2>No such event</h2>
                <p>The event you were looking for is not available at the moment. Please go back to the
                    previous page and reload to prevent following outdated links.
                </p>
            </div>)
        }

        var closedmessage = "";
        if (!is_organisation() && this.state.notopenyet) {
            closedmessage = " (not open)";
        }
        if (!is_organisation() && this.state.isclosed) {
            closedmessage = " (closed for registration)";
        }

        return (
            <div>
                <Loading loading={this.state.loading} />
                { this.renderTabs() }
            </div>
        )
    }

    renderTabs() {

        var overviewtab = (<TabPanel id="overview" header="Overview">
            <FEOverviewTab basic={this.state.basicdata} onload={this.onload} unload={this.unload}/>
        </TabPanel>);
        var regtab = (<TabPanel id="register" header="Registration">
            <FERegistrationTab basic={this.state.basicdata} onload={this.onload} unload={this.unload} />
        </TabPanel>);
        var cashiertab = (<TabPanel id="cashier" header="Cashier">
            <FECashierTab basic={this.state.basicdata} onload={this.onload} unload={this.unload}/>
        </TabPanel>);
        var accrtab = (<TabPanel id="badges" header="Badges">
            <FEAccreditorTab basic={this.state.basicdata} onload={this.onload} unload={this.unload} />
        </TabPanel>);
        var accrtab1 = (<TabPanel id="accreditation" header="Accreditation">
            <FEAccreditationTab basic={this.state.basicdata} onload={this.onload} unload={this.unload} />
        </TabPanel>);
        var accrtab2 = (<TabPanel id="accrtemplates" header="Templates">
            <FEAccrTemplateTab basic={this.state.basicdata} onload={this.onload} unload={this.unload} />
        </TabPanel>);

        var canregister = (is_sysop() || is_organiser() || is_registrar() || is_hod());
        var cancashier = (is_sysop() || is_organiser() || is_cashier() || is_hod());
        var canaccredit = (is_sysop() || is_organiser() || is_accreditor());
        var canaccredit2 = (is_sysop() || is_organiser());

        if(canaccredit2) {
            return (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="overview">
                {overviewtab}
                {canregister && regtab}
                {cancashier && cashiertab}
                {canaccredit && accrtab1}
                {canaccredit && accrtab}
                {canaccredit2 && accrtab2}
            </TabView>);
        }
        else if(is_hod()) {
            return (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="overview">
                {overviewtab}
                {canregister && regtab}
                {cancashier && cashiertab}
            </TabView>);
        }
        else if (canregister) {
            return (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="overview">
                {overviewtab}
                {canregister && regtab}
            </TabView>);
        }
        else if(cancashier) {
            return (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="overview">
                {overviewtab}
                {cancashier && cashiertab}
            </TabView>);
        }
        else if(canaccredit) {
            return (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="overview">
                {overviewtab}
                {canaccredit && accrtab1}
                {canaccredit && accrtab}
            </TabView>);
        }

    }
}
