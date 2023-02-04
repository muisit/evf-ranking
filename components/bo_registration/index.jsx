import React from 'react';
import { TabView,TabPanel } from 'primereact/tabview';
import EventsRegistrationTab from './eventsregistrationtab';
import RoleTypeTab from './roletypetab';
import RolesTab from './rolestab';
import RegistrarsTab from './registrarstab';

import { countries, eventtypes, users } from "../api.js";

export default class RegistrationIndexPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true,
            countries: [],
            countries_count: -1,
            eventtypes:[],
            eventtypes_count: -1,
            users: [],
            users_count: -1,
            dialogs: {
                events: false,
                registrars: false,
                roles: false,
                roletypes: false
            }
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

    onAction = (tab, data, cb) => {
        if (tab == 'registrars') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {registrars: true, events: false, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'events') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {registrars: false, events: true, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'roles') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: true, roletypes: false}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
        }
        else if (tab == 'roletypes') {
            if (data.event == 'openDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: false, roletypes: true}}, () => {if (cb) cb() });
            }
            else if (data.event == 'closeDialog') {
                this.setState({dialogs: {registrars: false, events: false, roles: false, roletypes: false}}, () => {if (cb) cb() });
            }
        }
    }

    render() {
        if(this.state.initializing) {
            return (<p>initializing... please wait</p>);
        }
        return (<div>
<TabView id="evfrankingtabs">
    <TabPanel id="events" header="Events"><EventsRegistrationTab users={this.state.users} countries={this.state.countries} types={this.state.eventtypes}  displayDialog={this.state.dialogs.events}  onAction={(f,c) => this.onAction('events', f, c)} /></TabPanel>
    <TabPanel id="registrars" header="Registrars"><RegistrarsTab users={this.state.users} countries={this.state.countries} displayDialog={this.state.dialogs.registrars}  onAction={(f,c) => this.onAction('registrars', f, c)} /></TabPanel>
    <TabPanel id="roles" header="Roles"><RolesTab displayDialog={this.state.dialogs.roles}  onAction={(f,c) => this.onAction('roles', f, c)} /></TabPanel>
    <TabPanel id="roletypes" header="Role Types"><RoleTypeTab displayDialog={this.state.dialogs.roletypes}  onAction={(f,c) => this.onAction('roletypes', f, c)} /></TabPanel>
</TabView></div>);
    }
}
