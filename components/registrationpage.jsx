import React from 'react';
import { TabView, TabPanel } from 'primereact/tabview';
import FERegistrationTab from './feregistrationtab';
import FECashierTab from './fecashiertab';
import FEAccreditorTab from './feaccreditortab';

import { countries, singleevent, weapons, categories, roles } from "./api.js";

export default class RegistrationPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            initializing:true,
            countries: [],
            countries_count: -1,
            item: null,
            notopenyet: true,
            isclosed: true,
            weapons: [],
            weapons_count: -1,
            categories: [],
            categories_count: -1,
            roles: [],
            roles_count: -1
        };
    }
    componentDidMount = () => {
        console.log("looking for event with id ",evfranking.eventid);
        singleevent("view",{id: evfranking.eventid})
            .then(json => {
                var opensat = new Date(json.data.item.reg_open);
                var closesat = new Date(json.data.item.reg_close);
                var now = new Date();
                this.setState({
                    item: json.data.item,  
                    notopenyet: (now.getTime() < opensat.getTime()),
                    isclosed: (now.getTime() > closesat.getTime())
                });
            });
        countries(0, 1000, '', "n")
            .then(json => {
                if (json) this.setState({ "countries": json.data.list, "countries_count": json.data.total }, this.checkInit);
            });
        categories(0, 1000, '', "n")
            .then(json => {
                if (json) this.setState({ "categories": json.data.list, "categories_count": json.data.total }, this.checkInit);
            });
        roles(0, 10000, '', "n")
            .then(json => {
                if (json) this.setState({ "roles": json.data.list, "roles_count": json.data.total }, this.checkInit);
            });
        weapons(0, 1000, '', "n")
            .then(json => {
                if (json) this.setState({ "weapons": json.data.list, "weapons_count": json.data.total });
            });
    }

    checkInit = () => {
        if (this.state.roles_count > 0 && this.state.countries_count >= 0 && this.state.categories_count > 0) this.setState({ initializing: false });
    }

    render() {
        if(this.state.initializing) {
            return (<p>initializing... please wait</p>);
        }
        if(this.state.item === null) {
            return (<div>
                <h2>No such event</h2>
                <p>The event you were looking for is not available at the moment. Please go back to the
                    previous page and reload to prevent following outdated links.
                </p>
            </div>)
        }
        
        var canmanage = evfranking.eventcap == "organiser" || evfranking.eventcap == "cashier" || evfranking.eventcap == "accreditation";
        var closedmessage="";
        if(!canmanage && this.state.notopenyet) {
            closedmessage=" (not open)";
        }
        if (!canmanage && this.state.isclosed) {
            closedmessage = " (closed for registration)";
        }

        var regtab = (null);
        var cashiertab = (null);
        var accrtab=(null);

        if (evfranking.eventcap == "organiser" || evfranking.eventcap == "registrar" || evfranking.eventcap == "hod") {
            regtab = (<FERegistrationTab item={this.state.item} countries={this.state.countries} weapons={this.state.weapons} categories={this.state.categories} roles={this.state.roles} />);
        }

        if(evfranking.eventcap == "organiser" || evfranking.eventcap == "cashier" || evfranking.eventcap == "hod") {
            cashiertab = (<FECashierTab item={this.state.item} countries={this.state.countries} roles={this.state.roles} />);
        }

        if (evfranking.eventcap == "organiser" || evfranking.eventcap == "accreditor") {
            accrtab = (<FEAccreditorTab item={this.state.item} countries={this.state.countries} roles={this.state.roles} />);
        }

        var content = (null);
        if(evfranking.eventcap == "organiser" || evfranking.eventcap == "hod") {
            // tabbed view for users with more capabilities
            regtab = (<TabPanel id="register" header="Registration">{regtab}</TabPanel>);
            cashiertab = (<TabPanel id="cashier" header="Cashier">{cashiertab}</TabPanel>);
            if(evfranking.eventcap == "organiser") {
                accrtab = (<TabPanel id="accreditor" header="Accreditor">{accrtab}</TabPanel>);
                content = (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="register">
                    {regtab}
                    {cashiertab}
                    {accrtab}
                </TabView>
                );
            }
            else {
                content = (<TabView id="evfregistrationtabs" animate={true} large={true} defaultSelectedTabId="register">
                    {regtab}
                    {cashiertab}
                </TabView>
                );
            }
        }
        else {
            // non-tabbed view if only one tab is visible anyway
            content = (<div>
                {regtab}
                {cashiertab}
                {accrtab}
            </div>)
        }

        return content;
    }
}
