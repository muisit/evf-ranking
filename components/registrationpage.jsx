import React from 'react';
import { TabView,TabPanel } from 'primereact/tabview';
import EventsRegistrationTab from './eventsregistrationtab';
import RoleTypeTab from './roletypetab';
import RolesTab from './rolestab';
import RegistrarsTab from './registrarstab';

import { countries, eventtypes, users } from "./api.js";

export default class RegistrationPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true,
            countries: [],
            countries_count: -1,
            eventtypes:[],
            eventtypes_count: -1,
            users: [],
            users_count: -1
        };
    }
    componentDidMount = () => {
        countries(0,1000,'',"n")
            .then(json => {
                if(json) this.setState({ "countries": json.data.list, "countries_count": json.data.total});
                if (this.state.countries_count >= 0 && this.state.eventtypes_count >= 0 && this.state.users_count >= 0) this.setState({ initializing: false });
        });
        eventtypes(0,1000,'','n')
            .then(json => {
                if(json) this.setState({"eventtypes": json.data.list, "eventtypes_count": json.data.total});
                if (this.state.countries_count >= 0 && this.state.eventtypes_count >= 0 && this.state.users_count >= 0) this.setState({ initializing: false });
        });
        users(0, 1000, '', 'n')
            .then(json => {
                if (json) this.setState({ "users": json.data.list, "users_count": json.data.total });
                if (this.state.countries_count>=0 && this.state.eventtypes_count>=0 && this.state.users_count>=0) this.setState({ initializing: false });
            });
    }

    render() {
        if(this.state.initializing) {
            return (<p>initializing... please wait</p>);
        }
        return (<div>Test</div>);
    }
}
