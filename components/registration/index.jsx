import React from 'react';
import { TabView, TabPanel } from 'primereact/tabview';
import FERegistrationTab from './registration';
import FECashierTab from './cashier';
import FEAccreditorTab from './accreditor';
import FEAccrTemplateTab from './accreditationtemplate';
import FEOverviewTab from "./overview";
import FEAccreditationTab from './accreditation';
import { Loading } from './elements/loading';

import { countries, singleevent, weapons, categories, roles, competitions, sideevents } from "../api.js";
import { parse_date, is_hod, is_organiser, is_sysop, is_organisation, is_registrar, is_accreditor, is_cashier,
        create_abbr,
        create_countryById, create_roleById, create_cmpById, create_wpnById, create_catById, create_sideeventById, is_valid  } from "../functions";

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

    mergeParams = (type,lst) => {
        var data = {
            'competitions': this.state.competitions,
            'competitionsBI': this.state.competitionsById,
            'events': this.state.sideevents,
            'eventsBI': this.state.sideeventsById,
            'weapons': this.state.weapons,
            'weaponsBI': this.state.weaponsById,
            'categories': this.state.categories,
            'categoriesBI': this.state.categoriesById,
            'countries': this.state.countries,
            'countriesBI': this.state.countriesById,
            "roles": this.state.roles,
            "rolesBI": this.state.rolesById
        };

        switch(type) {
        case 'competitions': data.competitions=lst; break;
        case 'events': data.events=lst;break;
        case 'weapons': data.weapons=lst; break;
        case 'categories': data.categories=lst; break;
        case 'countries': data.countries=lst; break;
        case 'roles': data.roles=lst; break;
        }
        return data;
    }

    sortAllInOrder(type,lst) {
        var data = this.mergeParams(type,lst);
        if(Object.keys(this.state.countriesById).length == 0 && type=="countries") {
            data.countries=lst;
            data.countriesBI = create_countryById(data.countries);
            this.setState({ countries: data.countries, countriesById: data.countriesBI });
        }
        if(Object.keys(this.state.categoriesById).length == 0 && type=="categories") {
            data.categories=lst;
            data.categoriesBI = create_catById(data.categories);
            this.setState({ categories: data.categories, categoriesById: data.categoriesBI });
        }
        if(Object.keys(this.state.rolesById).length == 0 && type=="roles") {
            data.roles=lst;
            data.rolesBI = create_roleById(data.roles);
            this.setState({ roles: data.roles, rolesById: data.rolesBI });
        }
        if(Object.keys(this.state.weaponsById).length == 0 && type=="weapons") {
            data.weapons=lst;
            data.weaponsBI = create_wpnById(data.weapons);
            this.setState({ weapons: data.weapons, weaponsById: data.weaponsBI });
        }

        if(type == "competitions") {
            data.competitions=lst;
            this.setState({ competitions: data.competitions });
        }

        if(type == "events") {
            data.events=lst;
            this.setState({ sideevents: data.events });
        }

        if(  Object.keys(data.weaponsBI).length > 0
          && Object.keys(data.categoriesBI).length > 0
          && Object.keys(data.competitionsBI).length == 0 
          && data.competitions.length > 0) {
            data.competitionsBI = this.sortCompetitions(data);
            this.setState({competitionsById: data.competitionsBI});
        }

        if(  Object.keys(data.competitionsBI).length > 0
          && Object.keys(data.eventsBI).length == 0 
          && data.events.length > 0) {
              var retval = this.sortSideEvents(data);
              this.setState({sideeventsById: retval.byId, sideevents: retval.sorted, competitionsById: retval.competitions});
              data.events=retval.sorted;
              data.eventsBI=retval.byId;
              data.competitionsBI=retval.competitions;
        }
        this.createBasicData(data);
    }

    sortCompetitions = (data) => {
        if(   data.competitions.length>0            
           && Object.keys(data.weaponsBI).length > 0 
           && Object.keys(data.categoriesBI).length > 0) {
            var cmpById = create_cmpById(data.competitions,data.weaponsBI,data.categoriesBI);
            
            return cmpById;
        }
        return {};
    }

    sortSideEvents = (data) => {
        if ( data.events && data.events.length>0 
            && Object.keys(data.competitionsBI).length > 0) {
            var sortedevents = data.events.slice().sort(function(e1,e2) {
                if(is_valid(e1.competition) && !is_valid(e2.competition)) return -1;
                if(!is_valid(e1.competition) && is_valid(e2.competition)) return 1;
                return e1.title > e2.title;
            });

            // link the competition object and create abbreviations
            sortedevents = sortedevents.map((ev) => {
                ev.competition=null;
                if(ev.competition_id) {
                    var key="c"+ev.competition_id;
                    if(data.competitionsBI[key]) {
                        ev.competition = data.competitionsBI[key];
                        ev.weapon=ev.competition.weapon;
                        ev.category=ev.competition.category;
                        data.competitionsBI[key].sideevent=ev;
                    }
                }
                ev.abbreviation = create_abbr(ev);
                return ev;
            });

            // this also applies the competition to the event
            var byId = create_sideeventById(sortedevents, data.competitionsBI);
            // first integrate all competitions into the sortedevents

            // Requirement 3.1.2: sort events first, then by title
            sortedevents.sort(function (e1, e2) {
                // we sort competitions first, so if one item has a competition_id and the other not, return a value
                if (e1.competition && !e2.competition) return -1; // e1 before e2
                if (e2.competition && !e1.competition) return 1; // e2 before e1

                // else compare only on title
                return e1.title > e2.title;
            });
            return { sorted: sortedevents, byId: byId, competitions: data.competitionsBI};
        }
        return {sorted: [], byId: {}, competitions: data.competitionsBI};
    }

    createBasicData = (data) => {
        // if sideevents and competitions are set, event is also set        
        if(   data.events.length 
           && data.competitions.length 
           && data.weapons.length 
           && data.categories.length 
           && data.roles.length 
           && data.countries.length
           && Object.keys(data.eventsBI).length > 0
           && Object.keys(data.competitionsBI).length > 0
           && Object.keys(data.weaponsBI).length > 0
           && Object.keys(data.rolesBI).length > 0
           && Object.keys(data.categoriesBI).length > 0
           && Object.keys(data.countriesBI).length > 0
           ) {
            var obj = {
                event: this.state.event,
                sideevents: data.events,
                sideeventsById: data.eventsBI,
                competitions: data.competitions,
                competitionsById: data.competitionsBI,
                weapons: data.weapons,
                weaponsById: data.weaponsBI,
                categories: data.categories,
                categoriesById: data.categoriesBI,
                roles: data.roles,
                rolesById: data.rolesBI,
                countries: data.countries,
                countriesById: data.countriesBI
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
                        this.sortAllInOrder("events",json.data.list);
                    }
                });

                this.onload("competitions", "Loading competitions", eventid);
                competitions(eventid).then((cmp) => {
                    if (cmp && cmp.data && cmp.data.list) {
                        this.unload("competitions", eventid);
                        this.sortAllInOrder("competitions", cmp.data.list);
                    }
                });

            });
        this.onload("countries", "Loading country data", "countries");
        countries(0, 1000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("countries", "countries");
                    this.sortAllInOrder("countries", json.data.list);
                }
            });
        this.onload("categories", "Loading category data", "categories");
        categories(0, 1000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("categories", "categories");
                    this.sortAllInOrder("categories", json.data.list);
                }
            });
        this.onload("roles", "Loading role data", "roles");
        roles(0, 10000, '', "n")
            .then(json => {
                if (json) {
                    this.unload("roles", "roles");
                    this.sortAllInOrder("roles", json.data.list);
                }
            });
        this.onload("weapons", "Loading weapon data", "weapons");
        weapons(0, 1000, '', "n")
            .then(json => {
                if(json) {
                    this.unload("weapons", "weapons");
                    this.sortAllInOrder("weapons",json.data.list);
                }
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
