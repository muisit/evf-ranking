import React from 'react';
import FEBase from './febase';
import { Button } from 'primereact/button';
import { Tooltip } from 'primereact/tooltip';
import { registration } from './api';
import {parse_net_error} from "./functions";

export default class FEOverviewTab extends FEBase {
    constructor(props, context) {
        super(props, context);
        this.state = Object.assign(this.state, {
            summary:null
        });
    }

    componentDidMount = () => {
        this._componentDidMount();
        this.getSummary();
    }

    getSummary = () => {
        registration("overview", { event: this.props.item.id, filter: { event: this.props.item.id, country: this.state.country }})
            .then((json) => {
                this.setState({ summary: json.data });
            })
            .catch((err) => parse_net_error(err));
    }

    countryHeader = () => {
        return (null);
    }

    renderContent () {
        if(  !this.props.item 
          || !this.state.competitions.length 
          || !this.state.summary 
          || !this.state.sideevents.length) return (null);

        var wpnById={};
        this.props.weapons.map((w) => {
            var key="w"+w.id;
            wpnById[key]=w;
        });

        var catById={};
        this.props.categories.map((c) => {
            var key="c"+c.id;
            catById[key]=c;
        });

        var roleById={};
        this.props.roles.map((r) => {
            var key="r"+r.id;
            roleById[key]=r;
        });

        var cmpById={};
        this.state.competitions.map((c) => {
            var key="c"+c.id;
            var wkey="w"+c.weapon;
            if(wpnById[wkey]) c.weapon_obj = wpnById[wkey];

            var ckey = "c"+c.category;
            if(catById[ckey]) c.category_obj = catById[ckey];

            cmpById[key]=c;
        });

        var eventById={};
        var eventAbbrs=[];
        this.state.sideevents.map((se) => {
            var key="s"+se.id;
            var ckey="c" + se.competition_id;
            if(cmpById[ckey]) {
                var cmp = cmpById[ckey];
                var wpn = cmp.weapon_obj ? cmp.weapon_obj : {abbr:'??'};
                var cat = cmp.category_obj ? cmp.category_obj : {abbr: '??'};
                se.abbr = wpn.abbr + cat.abbr;
            }
            else {
                var words=se.title.split(' ');
                se.abbr="";
                for(var i in words) {
                    var word=words[i];
                    se.abbr+=word[0];
                }
            }
            eventById[key]=se;
            eventAbbrs.push(se.abbr);
        });
        eventById["sorg"]={abbr: 'Support' };
        eventAbbrs = eventAbbrs.sort();
        eventAbbrs.push("Support");

        var cntById={};
        this.props.countries.map((c) => {
            var key="c"+c.id;
            cntById[key]=c;
        });

        var cntoverview=[];
        var orgoverview=[];
        var offoverview=[];
        for(var c in this.state.summary) {
            var sides=this.state.summary[c];

            if(c === "corg") {
                // list of roles
                for(var s in sides) {
                    var tot=sides[s];

                    if(roleById[s]) {
                        var obj={
                            role: roleById[s],
                            total: tot
                        };
                        orgoverview.push(obj);
                    }
                }
            }
            else if(c === "coff") {
                // list of roles
                for(var s in sides) {
                    var tot=sides[s];

                    if(roleById[s]) {
                        var obj={
                            role: roleById[s],
                            total: tot
                        };
                        offoverview.push(obj);
                    }
                }
            }
            else if(cntById[c]) {
                // list of sideevents
                var obj={"country": cntById[c]};

                for (var s in sides) {
                    var tot = sides[s];

                    if (eventById[s]) {
                        obj[eventById[s].abbr] = tot;
                    }
                }
                cntoverview.push(obj);
            }
        }

        return (<div>
            {this.renderCountry(cntoverview, eventAbbrs)}
            {this.renderOrganisation(orgoverview, "Organisers")}
            {this.renderOrganisation(offoverview, "Officials")}
        </div>);
    }

    renderCountry(cnts, abbrs) {
        return (<div className='row'>
            <div className='col-12'>
                <h5>Registrations per Country</h5>
                <table className='style-stripes'>
            <thead>
                <tr>
                    <th>Country</th>
                    {abbrs.map((a,idx) => (
                        <th key={idx} className='textright'>{a}</th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {cnts.map((c,idx) => {
                    return (
                        <tr key={'c'+idx}>
                            <td className='textleft'>{c.country.name}</td>
                            {abbrs.map((a,idx2) => (
                            <td key={'c'+idx+'_t'+idx2} className='textright'>{c[a] && (c[a] > 0) && c[a]}</td>
                            ))}
                        </tr>
                    )
                })}
            </tbody>
        </table>
        </div></div>);
    }

    renderOrganisation(overview,title) {
        return (
            <div className='row'>
                <div className='col-12'>
                    <h5>{title}</h5>
                    <table className='style-stripes'>
                        <thead>
                            <tr>
                              <th>Role</th>
                              <th className='textcenter'>Registrations</th>
                            </tr>
                        </thead>
                        <tbody>
                        {overview.map((o,idx) => (
                            <tr key={idx}>
                                <td className='textleft'>{o.role.name}</td>
                                <td className='textright'>{o.total}</td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>
        );
    }
}
