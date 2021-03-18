import { competitions, sideevents, registrations } from "./api.js";
import { format_date_fe, date_to_category, date_to_category_num, format_date } from './functions';
import { Dropdown } from 'primereact/dropdown';
import React from 'react';

export default class FEBase extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='events';

        var cid=-1;
        if (evfranking.eventcap == "hod" && parseInt(evfranking.country) > 0) {
            cid=evfranking.country;
        }

        console.log("setting country to "+cid);
        this.state = {
            country: cid,
            country_item: this.countryFromId(cid),
            sideevents: [],
            registrations: [],
            registered: {},
            roles: [],
            competitions: []
        };
    }

    countryFromId = (id) => {
        var retval=null;
        this.props.countries.map((cnt, idx) => {
            if (cnt.id == id) {
                console.log("setting default HoD country to " + cnt.name);
                retval = cnt;
            }
        });
        console.log("country from id returns " + (retval ? retval.name : "nothing"));
        return retval;
    }

    componentDidMount = () => {
        if(this.props.item.id > 0) {
            sideevents(this.props.item.id).then((cmp1) => { 
                var sortedevents = cmp1.data.list.slice();
                sortedevents.sort(function (e1, e2) {
                    // we sort competitions first, so if one item has a competition_id and the other not, return a value
                    if (e1.competition_id > 0 && !e2.competition_id) return -1; // e1 before e2
                    if (e2.competition_id > 0 && !e1.competition_id) return 1; // e2 before e1

                    // else compare only on title
                    return (e1.title < e2.title) ? -1 : 1;
                });
                this.setState({sideevents: sortedevents}); 
            });
            competitions(this.props.item.id).then((cmp) => {
                this.setState({competitions: cmp.data.list});
            });
            this.getRegistrations();
        }
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
            fencer.category = "No veteran";
            fencer.category_num = -1;
            fencer.birthyear = "unknown";
            fencer.birthday=format_date(new Date());
        }
        fencer.fullgender = fencer.gender == 'M' ? "Man" : "Woman";
        fencer.registrations = [];
        return fencer;
    }

    changeSingleRegisteredFencer = (itm) => {
        console.log("creating new registered list");
        var newlist = {};
        Object.keys(this.state.registered).map((key) => {
            var fencer = this.state.registered[key];
            if (fencer.id == itm.id) {
                console.log("replacing newlist with new fencer data ",itm);
                newlist[key] = itm;
            }
            else {
                newlist[key] = fencer;
            }
        });
        console.log("updating state after registered change");
        this.setState({ registered: newlist });
    }

    getRegistrations = () => {
        if(!this.state.country_item) return;
        return this.doGetRegistrations(this.state.country_item.id, true);
    }

    doGetRegistrations = (cid, doclear) => {
        console.log("getting registrations");
        return registrations(0, 10000, { country: cid, event: this.props.item.id })
            .then((cmp) => {
                console.log("setting registrations of "+cid + ": " +cmp.data.list.length);
                // filter out all fencers for the active registrations
                if(cmp.data.list) {
                    var allfencers={};
                    if(!doclear) {
                        allfencers = this.state.registered;
                    }
                    cmp.data.list.map((itm) => {
                        var fid = itm.fencer;
                        var key="k"+fid;
                        if (!allfencers[key]) {
                            var obj = this.adjustFencerData(itm.fencer_data);
                            allfencers[key] = obj;
                        }
                        delete itm.fencer_data;
                        allfencers[key].registrations.push(itm);
                    });
                    this.setState({registrations: cmp.data.list, registered: allfencers});
                }
            });
    }

    onCountrySelect = (val) => {
        console.log("setting country to "+val);
        // retrieve the list of registrations for the selected country
        this.setState({ 'country': val, "country_item": this.countryFromId(val) }, this.getRegistrations);
    }

    countryHeader = () => {
        console.log("generating country header for "+this.state.country);
        var country = (
            <div className='row'>
              <div className='col-4 vertcenter'>Select a country:</div>
              <div className='col-2'>
                <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.state.country} options={this.props.countries} onChange={(e) => this.onCountrySelect(e.value)} />
              </div>
              <div className='col-6'></div>
            </div>
        );

        if (evfranking.eventcap == "hod") {
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
        var startdate = new Date(this.props.item.opens);
        startdate = format_date_fe(startdate);

        return (<div className='container'>
            {this.countryHeader()}
            {this.renderContent()}
        </div>
        );
    }
}
