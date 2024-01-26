import React from 'react';
import { TabView,TabPanel } from 'primereact/tabview';
import RankingTab from "./rankingtab";
import ActionsTab from "./actionstab";
import ResultsTab from "./resultstab";
import FencersTab from "./fencerstab";
import CountriesTab from "./countriestab";
import EventsTab from './eventstab';
import RegistrarsTab from './registrarstab.jsx';
import RolesTab from './rolestab.jsx';
import RoleTypeTab from './roletypetab.jsx';

import { countries, eventtypes, users } from "../api.js";

export default class IndexPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true,
            countries: [],
            countries_count: 0,
            eventtypes:[],
            eventtypes_count: 0,
            users: [],
            users_count: -1,
            activeIndex: 0,
            tabActions: {},
            dialogs: {
                results: false,
                events: false,
                fencers: false,
                countries: false,
                registrars: false,
                roles: false,
                roletypes: false,
            },
            eventId: -1
        };
    }
    componentDidMount = () => {
        countries(0,1000,'',"n")
            .then(json => {
                if(json) this.setState({ "countries": json.data.list, "countries_count": json.data.total});
                this.checkInit();
        });
        eventtypes(0,1000,'','n')
            .then(json => {
                if(json) this.setState({"eventtypes": json.data.list, "eventtypes_count": json.data.total});
                this.checkInit();
        });
        users(0, 1000, '', 'n')
            .then(json => {
                if (json) this.setState({ "users": json.data.list, "users_count": json.data.total });
                this.checkInit();
            });
    }

    checkInit = () => {
        if (this.state.countries_count>=0 && this.state.eventtypes_count>=0 && this.state.users_count>=0) this.setState({ initializing: false });
    }

    setDialogs = (dialog) => {
        return {
            results: dialog == 'results',
            events: dialog == 'events',
            fencers: dialog == 'fencers',
            countries: dialog == 'countries',
            registrars: dialog == 'registrars',
            roles: dialog == 'roles',
            roletypes: dialog == 'roletypes'
        };
    }

    onAction = (tab, data, cb) => {
        if (tab == 'actions') {
            if (data.event == 'addEvent') {
                this.changeTabIndex('events');
                this.setState({dialogs: this.setDialogs('events')}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'results') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('results')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
            else if (data.event == "select") {
                this.setState({eventId: data.value}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'events') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('events')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
            else if(data.event == 'import') {
                this.changeTabIndex('results');
                this.setState({dialogs: this.setDialogs('none')});
                this.setState({eventId: data.value}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'fencers') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('fencers')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'countries') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('countries')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'registrars') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('registrars')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'roles') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('roles')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'roletypes') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: this.setDialogs('roletypes')}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: this.setDialogs('none')}, () => {if (cb) cb() });
            }
        }
    }

    changeTabIndex = (ti) => {
        if (ti == 'results') ti = 1;
        if (ti == 'events') ti = 2;
        this.setState({activeIndex: ti, eventId: -1});
    }

    render() {
        if(this.state.initializing) {
            return (<p>initializing... please wait</p>);
        }
        return (<div>
<TabView id="evfrankingtabs" activeIndex={this.state.activeIndex} onTabChange={(e) => this.changeTabIndex(e.index)}>
    <TabPanel id="actions" header="Actions"><ActionsTab onAction={(f,c) => this.onAction('actions', f, c)}/></TabPanel>
    <TabPanel id="results" header="Results"><ResultsTab countries={this.state.countries} displayDialog={this.state.dialogs.results}  onAction={(f,c) => this.onAction('results', f, c)} eventId={this.state.eventId}/></TabPanel>
    <TabPanel id="events" header="Events"><EventsTab countries={this.state.countries} types={this.state.eventtypes} displayDialog={this.state.dialogs.events} onAction={(f,c) => this.onAction('events', f, c)}/></TabPanel>
    <TabPanel id="fencers" header="Fencers"><FencersTab countries={this.state.countries} displayDialog={this.state.dialogs.fencers} onAction={(f,c) => this.onAction('fencers', f, c)}/></TabPanel>
    <TabPanel id="countries" header="Countries"><CountriesTab displayDialog={this.state.dialogs.countries} onAction={(f,c) => this.onAction('countries', f, c)}/></TabPanel>
    <TabPanel id="registrars" header="Registrars"><RegistrarsTab users={this.state.users} countries={this.state.countries} displayDialog={this.state.dialogs.registrars} onAction={(f,c) => this.onAction('registrars', f, c)}/></TabPanel>
    <TabPanel id="roles" header="Roles"><RolesTab displayDialog={this.state.dialogs.roles} onAction={(f,c) => this.onAction('roles', f, c)}/></TabPanel>
    <TabPanel id="roletypes" header="RoleTypes"><RoleTypeTab displayDialog={this.state.dialogs.roletypes} onAction={(f,c) => this.onAction('roletypes', f, c)}/></TabPanel>
</TabView></div>);
    }
}
