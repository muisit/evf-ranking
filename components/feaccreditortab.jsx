import React from 'react';
import FEBase from './febase';
import { Button } from 'primereact/button';
import { Tooltip } from 'primereact/tooltip';
import { registrations, accreditation } from './api';
import {is_organisation, parse_net_error} from "./functions";
import AccreditationDialog from './dialogs/accreditationdialog';

export default class FEAccreditorTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencer_object: { id: -1 },
            displayDialog: false,
            loadedAllFencers:false,
            summary: {}
        });

    }

    componentDidMount = () => {
        this._componentDidMount();
        this.getSummary();        
    }

    getSummary = () => {
        accreditation("overview", { event: this.props.item.id })
            .then((json) => {
                this.setState({ summary: json.data });
            })
            .catch((err) => parse_net_error(err));
    }

    countryHeader = () => {
        return (null);
    }

    redirectList = (event, type) => {
        var href = evfranking.url + "&download=" + type;
        
        if(!event) {
            href += "&mainevent=" + this.props.item.id + "&nonce=" + evfranking.nonce;
        }
        else {
            href += "&event=" + event.id + "&nonce=" + evfranking.nonce;
        }
        window.open(href);
    }

    doGetPendingPhotos = (cid, doclear) => {
        return registrations(0, 10000, { country: cid, event: this.props.item.id },"cnf",{photoid:true})
            .then((cmp) => this.parseRegistrations(cmp.data.list, doclear));
    }

    findFencerWithPicture = (startwith, direction) => {
        return this.doGetPendingPhotos()
            .then(() => {
                return this.doFindFencerWithPicture(startwith,direction);
            });
    }

    doFindFencerWithPicture = (startwith,direction) => {
        var startfound=false;
        var prevfencer=null;
        var nextfencer=null;
        var selectedfencer=null;

        if(!startwith) startfound=true;

        Object.keys(this.state.registered).map((key) => {
            var fencer=this.state.registered[key];
            if(fencer.picture == 'Y' || fencer.picture=='R') {
                if(startfound) {
                    if(selectedfencer === null) {
                        selectedfencer = fencer;
                    }
                    else if(nextfencer === null) {
                        nextfencer=fencer;
                    }
                }
                else {
                    if(startwith && startwith.id == fencer.id) {
                        startfound=true;
                    }
                    else if(!startfound) {
                        prevfencer=fencer;
                    }
                }
            }
        });

        if(direction == "prev") {
            return prevfencer;
        }
        else {
            return selectedfencer;
        }
    }

    onDialog = (itm, dt) => {
        switch(itm) {
            case 'open':
                this.findFencerWithPicture(null)
                    .then((fencer) => {
                        if (fencer) {
                            this.setState({ displayDialog: true, fencer_object: fencer });
                        }
                        else {
                            alert("All pictures have been approved");
                        }
                    });
                break;
            case 'close':
                this.setState({displayDialog: false});
                break;
            case 'save':
                // skip
                break;
            case 'change':
                this.changeSingleRegisteredFencer(dt);
                this.setState({ fencer_object: dt });
                break;
            case 'goto':
                this.findFencerWithPicture(this.state.fencer_object, dt)
                    .then((fencer) => {
                        if (fencer) {
                            this.setState({ fencer_object: fencer });
                        }
                        else if (dt == "prev") {
                            alert("No previous fencer found");
                        }
                        else {
                            alert("No remaining fencer found, all done");
                        }
                    });
                break;
        }
    }

    generateDoc = function(type, id) {
        var self=this;
        accreditation("generate", { event: this.props.item.id, type:type, type_id:id })
            .then((json) => {
                self.getSummary();
            })
            .catch((err) => parse_net_error(err));
    }

    downloadDoc = function(type,id) {
        var href = evfranking.url + "&download=summary&type=" + type + "&typeid="+id;
        href += "&mainevent=" + this.props.item.id + "&nonce=" + evfranking.nonce;
        window.open(href);
    }

    onGlobalAction = function(what) {
        var self=this;
        switch(what) {
        case 'regenerate':
        case 'check':
            accreditation(what, { event: this.props.item.id })
            .then((json) => {
                self.getSummary();
            })
            .catch((err) => parse_net_error(err));
            break;
        case 'refresh':
            this.getSummary();
        }        
    }

    renderContent () {
        return (<div>
            {this.renderParticipants()}
            {this.renderSummaryEvent()}
            {this.renderSummaryCountry()}
            {this.renderSummaryRole()}
            {this.renderSummaryTemplate()}
            {this.renderGlobalActions()}
        </div>);
    }

    renderGlobalActions() {
        return (<div className = 'row' >
            <div className='col-4'>
                <Button label="Regenerate" icon="pi pi-replay" className="p-button-raised p-button-text" onClick={()=>this.onGlobalAction("regenerate")} />
            </div>
            <div className='col-4'>
                <Button label="Refresh" icon="pi pi-refresh" className="p-button-raised p-button-text" onClick={() => this.onGlobalAction("refresh")} />
            </div>
            <div className='col-4'>
                <Button label="Check Summaries" icon="pi pi-refresh" className="p-button-raised p-button-text" onClick={() => this.onGlobalAction("check")} />
            </div>
        </div>);
    }

    renderSummary(elements, nameheader, namemember,idmember) {
        return (<table>
            <thead>
                <tr>
                    <th>{nameheader}</th>
                    <th>Reg.</th>
                    <th>Accr.</th>
                    <th>Open</th>
                    <th>Done</th>
                    <th>Doc</th>
                </tr>
            </thead>
            <tbody>
                <Tooltip target='.pi-icon-generate' />
                {elements.map((line, idx) => (
                    <tr key={idx}>
                        <td>{line[namemember]}</td>
                        <td>{line.registrations}</td>
                        <td>{line.accreditations}</td>
                        <td>{line.dirty}</td>
                        <td>{line.generated}</td>
                        <td>
                            {parseInt(line.accreditations) > 0 && line.available && (
                                <span className='pi pi-file-pdf' onClick={()=>this.downloadDoc(nameheader, line[idmember])}> {line.doc_size}</span>
                            )}
                            {parseInt(line.accreditations) > 0 && !line.available && (
                                <span className='pi pi-cog pi-icon-generate' onClick={() => this.generateDoc(nameheader, line[idmember])} data-pr-tooltip='Generate'></span>
                            )}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>)
    }

    renderParticipants() {
        return (
            <div className='row'>
                <div className='col-12'>
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Competitions and Side Events</th>
                            <th className='textcenter'>Participants</th>
                            </tr>
                        </thead>
                        <tbody>
                        {this.state.sideevents.map((event,idx) => (
                            <tr key={'f'+event.id}>
                              <td>{event.title}</td>
                              <td className='textcenter'>
                                    <i className='pi pi-list' onClick={() => this.redirectList(event, "participants")}></i>
                              </td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
                {is_organisation() && (
                <div className='col-12'>
                    <div className='textcenter'>
                        <i className='pi pi-download' onClick={() => this.redirectList(null,"participants")}> All Participants</i>
                    </div>
                </div>
                )}
                <div className='col-12'>
                    <div className='textcenter'>
                        <span className='pi pi-search' onClick={() => this.onDialog('open')}>&nbsp;Inspect Photo's</span>
                    </div>
                </div>
                <AccreditationDialog event={this.props.item} value={this.state.fencer_object} display={this.state.displayDialog} onClose={() => this.onDialog('close')} onChange={(itm) => this.onDialog('change', itm)} onSave={(itm) => this.onDialog('save', itm)} goTo={(itm) => this.onDialog('goto',itm)} />
            </div>
        );
    }

    renderSummaryEvent() {
        if(!this.state.summary || !this.state.summary.events) return (null);
        return (
            <div className='row'>
                <div className='col-12'>
                <h3>Overview per Competition</h3>
                <p className='smallprint'>These are only athlete registrations and accreditations.</p>
                {this.renderSummary(this.state.summary.events, "Event","title","event")}
                </div>
            </div>
        );
    }

    renderSummaryCountry() {
        if (!this.state.summary || !this.state.summary.countries) return (null);
        var cnts=this.state.summary.countries.filter((itm) => {
            return itm.registrations>0;
        });
        return (
            <div className='row'>
                <div className='col-12'>
                <h3>Overview per Country</h3>
                <p className='smallprint'>These are only the registrations and accreditations for athletes and
                people with a federative role (coach, head of delegation, etc). For some participants, registrations 
                are combined into a single accreditation, which causes a mismatch between the number of registrations 
                and the number of accreditations.</p>
                {this.renderSummary(cnts, "Country", "abbr","country")}
                </div>
            </div>
        );
    }

    renderSummaryRole() {
        if (!this.state.summary || !this.state.summary.roles) return (null);
        var rolesById={};
        this.props.roles.map((itm) => {
            rolesById["r"+parseInt(itm.id)]=itm;
        });

        var roles=this.state.summary.roles.map((itm) => {
            if(rolesById["r"+parseInt(itm.role)]) {
                itm.name=rolesById["r"+parseInt(itm.role)].name;
            }
            else if(parseInt(itm.role)==0){
                itm.name="Athlete";
            } 
            // no longer applicable after change in DB query           
            //else if (parseInt(itm.role) == -1) {
            //    itm.name = "Participant";
            //}
            else {
                itm.name="Various";
            }
            return itm;
        });

        return (
            <div className='row'>
                <div className='col-12'>
                <h3>Overview per Role</h3>
                <p className='smallprint'>For some roles, registrations are combined into a single accreditation, which
                causes a mismatch between the number of registrations and the number of accreditations.</p>
                {this.renderSummary(roles, "Role", "name","role")}
                </div>
            </div>
        );
    }

    renderSummaryTemplate() {
        if (!this.state.summary || !this.state.summary.templates) return (null);
        return (
            <div className='row'>
                <div className='col-12'>
                <h3>Overview per Accreditation Template</h3>
                {this.renderSummary(this.state.summary.templates, "Template", "name","template")}
                </div>
            </div>
        );
    }    
}
