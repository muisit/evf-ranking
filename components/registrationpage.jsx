import React from 'react';
import { TabView, TabPanel } from 'primereact/tabview';
import FERegistrationTab from './feregistrationtab';
import FECashierTab from './fecashiertab';
import FEAccreditorTab from './feaccreditortab';
import FEAccrTemplateTab from './feaccrtemplatetab';
import FEOverviewTab from "./feoverviewtab";
import FEAccreditationTab from './feaccreditationtab';

import { countries, singleevent, weapons, categories, roles } from "./api.js";
import { parse_date, is_hod, is_organiser, is_sysop, is_organisation, is_registrar, is_accreditor, is_cashier } from "./functions";

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
        singleevent("view",{id: evfranking.eventid})
            .then(json => {
                //console.log("registration event is ",json.data.item);
                var opensat = parse_date(json.data.item.reg_open);
                var closesat = parse_date(json.data.item.reg_close);
                var now = parse_date();
                this.setState({
                    item: json.data.item,  
                    notopenyet: now.isBefore(opensat),
                    isclosed: closesat.isBefore(now)
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
        
        var closedmessage="";
        if(!is_organisation() && this.state.notopenyet) {
            closedmessage=" (not open)";
        }
        if (!is_organisation() && this.state.isclosed) {
            closedmessage = " (closed for registration)";
        }

        var overviewtab = (<TabPanel id="overview" header="Overview">
            <FEOverviewTab item={this.state.item} countries={this.state.countries} weapons={this.state.weapons} categories={this.state.categories} roles={this.state.roles} />
        </TabPanel>);
        var regtab = (<TabPanel id="register" header="Registration">
            <FERegistrationTab item={this.state.item} countries={this.state.countries} weapons={this.state.weapons} categories={this.state.categories} roles={this.state.roles} />
        </TabPanel>);
        var cashiertab = (<TabPanel id="cashier" header="Cashier">
            <FECashierTab item={this.state.item} countries={this.state.countries} roles={this.state.roles} weapons={this.state.weapons} categories={this.state.categories}/>
        </TabPanel>);
        var accrtab = (<TabPanel id="badges" header="Badges">
            <FEAccreditorTab item={this.state.item} countries={this.state.countries} roles={this.state.roles} />
        </TabPanel>);
        var accrtab1 = (<TabPanel id="accreditation" header="Accreditation">
            <FEAccreditationTab item={this.state.item} countries={this.state.countries} roles={this.state.roles} weapons={this.state.weapons} categories={this.state.categories} />
        </TabPanel>);
        var accrtab2 = (<TabPanel id="accrtemplates" header="Templates">
            <FEAccrTemplateTab item={this.state.item} roles={this.state.roles} />
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
