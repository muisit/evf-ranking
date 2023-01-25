import React from 'react';
import { TabView,TabPanel } from 'primereact/tabview';
import RankingTab from "./rankingtab";
import ActionsTab from "./actionstab";
import ResultsTab from "./resultstab";
import FencersTab from "./fencerstab";
import CountriesTab from "./countriestab";
import EventsTab from './eventstab';
import MigrationsTab from './migrationstab';

import { countries, eventtypes } from "../api.js";

export default class IndexPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true,
            countries: [],
            countries_count: 0,
            eventtypes:[],
            eventtypes_count: 0,
            activeIndex: 0,
            tabActions: {},
            dialogs: {
                results: false,
                events: false,
                fencers: false,
                countries: false,
                migrations: false
            },
            eventId: -1
        };
    }
    componentDidMount = () => {
        countries(0,1000,'',"n")
            .then(json => {
                if(json) this.setState({ "countries": json.data.list, "countries_count": json.data.total});
                if(this.state.countries.length && this.state.eventtypes.length) this.setState({initializing:false});
        });
        eventtypes(0,1000,'','n')
            .then(json => {
                if(json) this.setState({"eventtypes": json.data.list, "eventtypes_count": json.data.total});
                if (this.state.countries.length && this.state.eventtypes.length) this.setState({ initializing: false });
        });
    }

    onAction = (tab, data, cb) => {
        if (tab == 'actions') {
            if (data.event == 'addEvent') {
                this.changeTabIndex('events');
                this.setState({dialogs: {results: false, events: true, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'results') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {results: true, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
            else if (data.event == "select") {
                this.setState({eventId: data.value}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'events') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {results: false, events: true, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
            else if(data.event == 'import') {
                this.changeTabIndex('results');
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}});
                this.setState({eventId: data.value}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'fencers') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: true, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'countries') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: true, migrations: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'migrations') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: true}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {results: false, events: false, fencers: false, countries: false, migrations: false}}, () => {if (cb) cb() });
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
    <TabPanel id="fencers" header="Fencers"><FencersTab  countries={this.state.countries} displayDialog={this.state.dialogs.fencers} onAction={(f,c) => this.onAction('fencers', f, c)}/></TabPanel>
    <TabPanel id="countries" header="Countries"><CountriesTab displayDialog={this.state.dialogs.countries} onAction={(f,c) => this.onAction('countries', f, c)}/></TabPanel>
    <TabPanel id="migrations" header="Migrations"><MigrationsTab displayDialog={this.state.dialogs.migrations} onAction={(f,c) => this.onAction('migrations', f, c)}/></TabPanel>
</TabView></div>);
    }
}
