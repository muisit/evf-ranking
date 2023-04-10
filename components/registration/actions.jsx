import React from 'react';
import FEBase from './base';
import { Button } from 'primereact/button';
import { registrations, singleevent } from '../api';
import { parse_net_error} from "../functions";
import AccreditationDialog from './dialogs/accreditationdialog';
import { updateFencerRegistrations } from '../lib/registrations';

export default class ActionsTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencer_object: { id: -1 },
            displayDialog: false,
            loadedAllFencers:false,
            summary: {},
        });
    }

    componentDidMount = () => {
        this._componentDidMount();
        singleevent("statistics", { id: this.props.basic.event.id })
            .then((json) => {
                this.setState({ summary: json.data,loadid: null });
            })
            .catch((err) => parse_net_error(err));
    }


    countryHeader = () => {
        return (null);
    }

    redirectList = (event, type) => {
        var href = evfranking.url + "&download=" + type;
        
        if(!event) {
            href += "&mainevent=" + this.props.basic.event.id + "&nonce=" + evfranking.nonce;
        }
        else {
            href += "&event=" + event.id + "&nonce=" + evfranking.nonce;
        }
        window.open(href);
    }

    doGetPendingPhotos = (cid, doclear) => {
        return registrations(0, 10000, { country: cid, event: this.props.basic.event.id },"cnf",{photoid:true})
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

        if(!startwith) startfound=true;

        console.log("looping over all fencers, looking in direction ",direction," starting with ",startwith);
        Object.keys(this.state.registered).map((key) => {
            var fencer=this.state.registered[key];
            if(startwith && startwith.id == fencer.id) {
                startfound=true;
            }
            else if(fencer.picture == 'Y' || fencer.picture=='R') {
                if(!startfound) {
                    prevfencer=fencer;
                }
                else if(startfound && nextfencer === null) {
                    nextfencer = fencer;
                }
            }
        });

        if(direction == "prev") {
            return prevfencer;
        }
        else {
            return nextfencer;
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
                var newlist = updateFencerRegistrations(this.state.registered, dt);
                this.setState({ fencer_object: dt, registered: newlist });
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

    renderContent () {
        return (<div className='accreditor-tab'>
            {this.renderStatistics()}
            {this.renderParticipants()}
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

    renderStatistics() {
        return (
            <div>
                <div className='row'>
                    <div className='col-12'>
                        <h3>Statistics</h3>
                    </div>
                </div>
                <div className='row'>
                    <div className='col-2'>Queue status</div>
                    <div className='col-10'>{this.state.summary.queue || 0} entries</div>
                </div>
                <div className='row'>
                    <div className='col-2'>Participants</div>
                    <div className='col-10'>
                        {this.state.summary && this.state.summary.participants && Object.keys(this.state.summary.participants).map((key, idx) => {
                            var key2 = key;
                            switch(key) {
                            case 'EVF':
                            case 'Athlete': break;
                            case 'Org': key2 = 'Organisation'; break;
                            case 'Country': key2 = 'Support'; break;
                            }
                            return (
                                <div key={idx}>
                                    {key2}: {this.state.summary.participants[key]}
                                </div>
                            );
                        })}
                    </div>
                </div>
                <div className='row'>
                    <div className='col-2'>Pictures</div>
                    <div className='col-10'>
                        {this.state.summary && this.state.summary.pictures && Object.keys(this.state.summary.pictures).map((key, idx) => {
                            var key2 = key;
                            switch(key) {
                            case 'N': key2='Missing picture'; break;
                            case 'A': key2='Accepted'; break;
                            case 'Y': key2 = 'Uploaded'; break;
                            case 'R': key2 = 'Replacement needed'; break;
                            }
                            return (
                                <div key={idx}>
                                    {key2}: {this.state.summary.pictures[key]}
                                </div>
                            );
                        })}
                        <Button label="Check Pictures" icon="pi pi-search" className="p-button-raised p-button-text" onClick={() => this.onDialog("open")} />
                        <br/><i className='pi pi-download' onClick={() => this.redirectList(null, "picturestate")}> Picture state list</i>
                    </div>
                </div>
                <AccreditationDialog event={this.props.basic.event} value={this.state.fencer_object} display={this.state.displayDialog} onClose={() => this.onDialog('close')} onChange={(itm) => this.onDialog('change', itm)} onSave={(itm) => this.onDialog('save', itm)} goTo={(itm) => this.onDialog('goto',itm)} />
            </div>
        )
    }

    renderParticipants() {
        return (
            <div className='row'>
                <div className='col-12'>
                    <h3>CSV and XML files</h3>
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Competitions and Side Events</th>
                            <th className='textcenter'>CSV</th>
                            <th className='textcenter'>XML</th>
                            </tr>
                        </thead>
                        <tbody>
                        {this.props.basic.sideevents.map((event,idx) => (
                            <tr key={'f'+event.id}>
                              <td>{event.title}</td>
                              <td className='textcenter'>
                                <i className='pi pi-list' onClick={() => this.redirectList(event, "participants")}></i>
                              </td>
                              <td className='textcenter'>
                                <i className='pi pi-file-excel' onClick={() => this.redirectList(event, "participantsxml")}></i>
                              </td>
                            </tr>
                        ))}
                            <tr>
                              <td>All participants</td>
                              <td className='textcenter'>
                                <i className='pi pi-list' onClick={() => this.redirectList(null, "participants")}></i>
                              </td>
                              <td className='textcenter'>
                                <i className='pi pi-file-excel' onClick={() => this.redirectList(null, "participantsxml")}></i>
                              </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        );
    }
}
