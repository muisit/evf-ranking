import { competitions, sideevents, registrations, abort_all_calls } from "./api.js";
import { parse_date, format_date_fe, date_to_category, date_to_category_num, format_date, is_hod, is_valid } from './functions';
import { Dropdown } from 'primereact/dropdown';
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
            country: cid,
            country_item: this.countryFromId(cid),
            sideevents: [],
            registrations: [],
            registered: {},
            competitions: []
        };
    }

    countryFromId = (id) => {
        var retval=null;
        if(id == -1) {
            return {id:-1,name:"Organisation"};
        }
        this.props.countries.map((cnt, idx) => {
            if (cnt.id == id) {
                retval = cnt;
            }
        });
        return retval;
    }

    componentDidMount = () => {
        this._componentDidMount();
    }

    _componentDidMount = () => {
        if(this.props.item.id > 0) {
            sideevents(this.props.item.id).then((cmp1) => { 
                if(cmp1 && cmp1.data && cmp1.data.list) {
                    var sortedevents = cmp1.data.list.slice();
                    // Requirement 3.1.2: sort events first, then by title
                    sortedevents.sort(function (e1, e2) {
                        // we sort competitions first, so if one item has a competition_id and the other not, return a value
                        if (e1.competition_id > 0 && !e2.competition_id) return -1; // e1 before e2
                        if (e2.competition_id > 0 && !e1.competition_id) return 1; // e2 before e1

                        // else compare only on title
                        return (e1.title < e2.title) ? -1 : 1;
                    });
                    this.setState({sideevents: sortedevents}); 
                }
            });
            competitions(this.props.item.id).then((cmp) => {
                if(cmp && cmp.data && cmp.data.list) {
                    this.setState({competitions: cmp.data.list});
                }
            });
            this.getRegistrations();
        }
    }
    componentWillUnmount = () => {
        abort_all_calls(this.abortType);
    }

    adjustFencerData = (fencer) => {
        var name = fencer.name + ", " + fencer.firstname;
        fencer.fullname = name;
        if (fencer.birthday) {
            fencer.category = date_to_category(fencer.birthday, this.props.item.opens);
            fencer.category_num = date_to_category_num(fencer.birthday, this.props.item.opens);
            fencer.birthyear = fencer.birthday.substring(0, 4);
        }
        else {
            fencer.category = "None";
            fencer.category_num = -1;
            fencer.birthyear = "unknown";
            fencer.birthday=format_date(parse_date());
        }
        fencer.fullgender = fencer.gender == 'M' ? "M" : "W";
        if(!fencer.registrations) fencer.registrations = [];
        return fencer;
    }

    changeSingleRegisteredFencer = (itm) => {
        var newlist = {};
        Object.keys(this.state.registered).map((key) => {
            var fencer = this.state.registered[key];
            if (fencer.id == itm.id) {
                newlist[key] = itm;
            }
            else {
                newlist[key] = fencer;
            }
        });
        this.setState({ registered: newlist });
    }

    getRegistrations = () => {
        if(!this.state.country_item) return;
        return this.doGetRegistrations(this.state.country_item.id, true);
    }

    doGetRegistrations = (cid, doclear) => {
        return registrations(0, 10000, { country: cid, event: this.props.item.id })
            .then((cmp) => this.parseRegistrations(cmp.data.list,doclear));
    }

    parseRegistrations = (registrations, doclear) => {
        // filter out all fencers for the active registrations
        if (registrations) {
            var allfencers = {};
            if (!doclear) {
                allfencers = this.state.registered;
            }
            registrations.map((itm) => {
                var fid = itm.fencer;
                var key = "k" + fid;
                if (!allfencers[key]) {
                    var obj = this.adjustFencerData(itm.fencer_data);
                    allfencers[key] = obj;
                }

                delete itm.fencer_data;
                allfencers[key].registrations.push(itm);
            });
            this.setState({ registrations: registrations, registered: allfencers });
        }
    }

    replaceRegistration = (fencer) => {
        var key="k" + fencer.id;
        var allfencers = Object.assign({}, this.state.registered);
        if(allfencers[key]) {
            fencer.registrations = allfencers[key].registrations;
        }
        else {
            fencer.registrations=[];
        }
        allfencers[key]=fencer;
        this.setState({registered: allfencers});
    }

    onCountrySelect = (val) => {
        // retrieve the list of registrations for the selected country
        this.setState({ 'country': val, "country_item": this.countryFromId(val) }, this.getRegistrations);
    }

    countryHeader = () => {
        var countries=this.props.countries.slice();
        // add an Organisation at the top to allow all organisation-role fencers
        countries.splice(0,0,{id:-1,name:'Organisation'});
        var country = (
            <div className='row'>
              <div className='col-4 vertcenter'>Select a country:</div>
              <div className='col-2'>
                <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.state.country} options={countries} onChange={(e) => this.onCountrySelect(e.value)} />
              </div>
              <div className='col-6'></div>
            </div>
        );

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
        var startdate = format_date_fe(this.props.item.opens);

        return (<div className='container'>
            {this.countryHeader()}
            {this.renderContent()}
        </div>
        );
    }
}
