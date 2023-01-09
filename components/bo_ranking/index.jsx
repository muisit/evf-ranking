import React from 'react';
import { TabView,TabPanel } from 'primereact/tabview';
import RankingTab from "./rankingtab";
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
            eventtypes_count: 0
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

    render() {
        if(this.state.initializing) {
            return (<p>initializing... please wait</p>);
        }
        return (
<TabView id="evfrankingtabs">
    <TabPanel id="results" header="Results"><ResultsTab countries={this.state.countries} /></TabPanel>
    <TabPanel id="events" header="Events"><EventsTab  countries={this.state.countries} types={this.state.eventtypes}/></TabPanel>
    <TabPanel id="fencers" header="Fencers"><FencersTab  countries={this.state.countries}/></TabPanel>
    <TabPanel id="countries" header="Countries"><CountriesTab/></TabPanel>
    <TabPanel id="migrations" header="Migrations"><MigrationsTab/></TabPanel>
</TabView>);
    }
}
