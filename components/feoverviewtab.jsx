import React from 'react';
import FEBase from './febase';
import { Button } from 'primereact/button';
import { Tooltip } from 'primereact/tooltip';
import { registration } from './api';
import {parse_net_error, create_abbr, create_cmpById, create_roleById, create_wpnById, create_catById } from "./functions";

export default class FEOverviewTab extends FEBase {
    constructor(props, context) {
        super(props, context);
        this.state = Object.assign(this.state, {
            summary:null
        });
    }

    componentDidMount = () => {
        this.getSummary();
    }

    getSummary = () => {
        if(!this.props.basic || !this.props.basic.event) return;
        this.props.onload("registrations","Loading registrations",this.props.basic.event.id);
        registration("overview", { event: this.props.basic.event.id, filter: { event: this.props.basic.event.id, country: this.state.country }})
            .then((json) => {
                this.props.unload("registrations",this.props.basic.event.id);
                this.setState({ summary: json.data });
            })
            .catch((err) => parse_net_error(err));
    }

    countryHeader = () => {
        return (null);
    }

    renderContent () {
        if(  !this.props.basic
          || !this.props.basic.event 
          || !this.props.basic.competitions.length 
          || !this.state.summary 
          || !this.props.basic.sideevents.length) return (null);


        var eventById=Object.assign({},this.props.basic.sideeventsById);
        var eventAbbrsComps = [];
        var eventAbbrsSide = [];
        this.props.basic.sideevents.map((se) => {
            if(se.competition) {
                eventAbbrsComps.push(se.abbreviation);
            }
            else {
                eventAbbrsSide.push(se.abbreviation);
            }
        });
        eventAbbrsComps = eventAbbrsComps.sort();
        eventAbbrsSide = eventAbbrsSide.sort();

        // put the support roles after the competitions, but before the side events
        eventById["sorg"] = { abbr: 'Support' };
        eventAbbrsComps.push("Support");
        var eventAbbrs = eventAbbrsComps.concat(eventAbbrsSide);

        var cntoverview=[];
        var orgoverview=[];
        var offoverview=[];
        for(var c in this.state.summary) {
            var sides=this.state.summary[c];

            if(c === "corg") {
                // list of roles
                for(var s in sides) {
                    var tot=sides[s];

                    if(this.props.data.rolesById[s]) {
                        var obj={
                            role: this.props.data.rolesById[s],
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

                    if(this.props.basic.rolesById[s]) {
                        var obj={
                            role: this.props.basic.rolesById[s],
                            total: tot
                        };
                        offoverview.push(obj);
                    }
                }
            }
            else if(this.props.basic.countriesById[c]) {
                // list of sideevents
                var obj = { "country": this.props.basic.countriesById[c]};

                for (var s in sides) {
                    var tot = sides[s];

                    if (eventById[s]) {
                        obj[eventById[s].abbreviation] = tot;
                    }
                }
                cntoverview.push(obj);
            }
        }

        // sort countries by alphabet
        cntoverview.sort(function(a,b) {
            return a.country.name > b.country.name;
        });

        return (<div>
            {this.renderCountry(cntoverview, eventAbbrs)}
            {/* requirement 7.1.4: show number of participants for roles for organiser roles */}
            {this.renderOrganisation(orgoverview, "Organisers")}
            {/* requirement 7.1.5: show number of participants for roles for official roles */}
            {this.renderOrganisation(offoverview, "Officials")}
        </div>);
    }

    renderCountry(cnts, abbrs) {
        var totalsPerEvent={};
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
                            {abbrs.map((a,idx2) => {
                                // requirement 7.1.2: a total is shown for each side event
                                var abbr=abbrs[idx2];
                                if(!totalsPerEvent[abbr]) totalsPerEvent[abbr]=0;
                                if(c[a] && c[a]>0) totalsPerEvent[abbr]+=c[a];
                                if(c[a] && c[a][0] && c[a].length==2) totalsPerEvent[abbr]+=c[a][1];
                                return (
                            <td key={'c'+idx+'_t'+idx2} className='textright'>
                                {/* requirement 7.1.1: show number of participants per country per side event */}
                                {c[a] && (c[a] > 0) && c[a]}
                                {/* requirement 7.1.3: show total number of teams and number of individuals */}
                                {c[a] && c[a][0] && c[a].length==2 && (<span>{c[a][1]} ({c[a][0]})</span>)}
                            </td>
                            )})}
                        </tr>
                    )
                })}                
            </tbody>            
            <tbody>
                <tr>
                    <td>Total</td>
                    {abbrs.map((a,idx) => {
                        // requirement 7.1.2: show line of totals
                        return (
                        <td key={idx} className='textright'>{totalsPerEvent[a]}</td>
                    )})}
                </tr>
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
