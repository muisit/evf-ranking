import { registrations, abort_all_calls } from "../api.js";
import { parse_date, format_date_fe, date_to_category, date_to_category_num, format_date, is_hod, is_valid } from '../functions';
import { Dropdown } from 'primereact/dropdown';
import { Loading } from './elements/loading';
import { retrieve, parse } from "../lib/registrations.js";
import React from 'react';

export default class FEBase extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='events';

        var cid=-1;
        if (is_hod() && is_valid(evfranking.country)) {
            cid=evfranking.country;
        }

        this.state = {
            loading: {},
            country: cid,
            country_item: this.countryFromId(cid),
            registered: {},
            registrations: []
        };
    }

    countryFromId = (id) => {
        if(id == -1) {
            return {id:-1,name:"Organisation"};
        }
        if(this.props.basic && this.props.basic.countriesById) {
            var key="c" + id;
            if(this.props.basic.countriesById[key]) {
                return this.props.basic.countriesById[key];
            }
        }
        return {id:id, name:"No such country"};
    }

    competitionFromId = (id) => {
        if(this.props.basic && this.props.basic.competitionById) {
            var key="c" + id;
            if(this.props.basic.competitionById[key]) {
                return this.props.basic.competitionById[key];
            }
        }
        return {id: id, title: "No such competition"}
    }

    sideeventFromId = (id) => {
        if(this.props.basic && this.props.basic.sideeventById) {
            var key="s" + id;
            if(this.props.basic.sideEventById[key]) {
                return this.props.basic.sideEventById[key];
            }
        }
        return {id: id, title: "No such event", abbreviation: "??", competition: null};
    }

    roleFromId = (id) => {
        if (this.props.basic && this.props.basic.roleById) {
            var key = "r" + id;
            if (this.props.basic.roleById[key]) {
                return this.props.basic.roleById[key];
            }
        }
        return { id: id, name: "No such role", type: 0 }
    }

    categoryFromId = (id) => {
        if (this.props.basic && this.props.basic.categoryById) {
            var key = "c" + id;
            if (this.props.basic.categoryById[key]) {
                return this.props.basic.categoryById[key];
            }
        }
        return { id: id, name: "No such category", type: "?", value: 10, abbr: "?" };
    }

    weaponFromId = (id) => {
        if (this.props.basic && this.props.basic.weaponById) {
            var key = "w" + id;
            if (this.props.basic.weaponById[key]) {
                return this.props.basic.cateweaponByIdgoryById[key];
            }
        }
        return { id: id, name: "No such weapon", gender: "?", abbr: "?" };
    }

    componentDidMount = () => {
        this._componentDidMount();
    }

    _componentDidMount = () => {
        if(this.props.basic && this.props.basic.event.id > 0) {
            this.getRegistrations();
        }
    }
    componentWillUnmount = () => {
        abort_all_calls(this.abortType);
    }

    getRegistrations = () => {
        if(!this.state.country_item) return;
        this.doGetRegistrations(this.state.country_item.id, true);
    }

    doGetRegistrations = (cid, doclear) => {
        // if we are clearing in between, indicate we need to reload the registration data
        // If we are not, this is a add-or-update operation used in the continuous accreditation
        // tab
        if(doclear) this.props.onload("registrations","Loading existing registrations", cid);
        retrieve(cid, this.props.basic.event.id)
            .then((lst) => {
                if(doclear) this.props.unload("registrations",cid);
                if(lst.data.list) {
                    this.parseRegistrations(lst.data.list,doclear);
                    if (this.postRegistrations) this.postRegistrations(cid);
                }
            });
    }

    parseRegistrations = (lst, doclear) => {
        var newlst = parse(this.state.registered, lst, doclear, this.props.basic.event);
        this.setState({registered: newlst});
    }

    onCountrySelect = (val) => {
        // retrieve the list of registrations for the selected country
        this.setState({ 'country': val, "country_item": this.countryFromId(val) }, this.getRegistrations);
    }

    countryHeader = () => {
        if(!this.props.basic || !this.props.basic.countries) { return (null); }
        var countries=this.props.basic.countries.slice();
        // add an Organisation at the top to allow all organisation-role fencers
        countries.splice(0,0,{id:-1,name:'Organisation'});
        countries = countries.filter((item) => item.name != 'Organisers');
        var country = (
            <div className='row'>
              <div className='col-4 vertcenter'>Select a country:</div>
              <div className='col-2'>
                <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.state.country} options={countries} onChange={(e) => this.onCountrySelect(e.value)} />
              </div>
              <div className='col-6'></div>
            </div>
        );

        // if this is a HoD, only display the currently selected country, do not allow switching
        if (is_hod()) {
            country = (
                <div className='row'>
                    <div className='col-12 textcenter'>
                        <h4>Head of Delegation of {this.state.country_item.name}</h4>
                    </div>
                </div>            
            );
        }
        return country;
    }

    render() {
        return (<div className='container'>
            <Loading loading={this.state.loading} />
            {this.countryHeader()}
            {this.renderContent()}
        </div>
        );
    }
}
