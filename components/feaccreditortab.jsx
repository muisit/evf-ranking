import React from 'react';
import FEBase from './febase';
import {format_currency, jsonOutput } from './functions';
import { registration } from './api';
import { Checkbox } from 'primereact/checkbox';
import AccreditationDialog from './dialogs/accreditationdialog';

export default class FEAccreditorTab extends FEBase {
    constructor(props, context) {
        super(props, context);

        this.state = Object.assign(this.state, {
            fencer_object: { id: -1 },
            displayDialog: false,
            loadedAllFencers:false
        });

    }

    countryHeader = () => {
        return (null);
    }

    redirectList = (event, type) => {
        var href=evfranking.url + "&download=" + type + "&event=" + event.id+"&nonce=" + evfranking.nonce;
        window.open(href);
    }

    loadAllRegistrations = () => {
        if(!this.state.loadedAllFencers) {
            var promises=[];
            this.props.countries.map((cnt) => {
                promises.push(this.doGetRegistrations(cnt.id));
            });
            return Promise.all(promises).then((values) => {
                this.setState({loadedAllFencers:true});
            });
        }
        return Promise.resolve("done");
    }

    findFencerWithPicture = (startwith, direction) => {
        return this.loadAllRegistrations()
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

        console.log("looping over keys of registered "+Object.keys(this.state.registered).length);
        Object.keys(this.state.registered).map((key) => {
            var fencer=this.state.registered[key];
            console.log("checking fencer "+fencer.name+" with picture state "+fencer.picture);
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
                console.log("finding fencer with picture");
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
                this.setState({ selected_fencer: dt });
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

    renderContent() {
        return (
            <div className='row'>
                <div className='col-12'>
                    <table className='cashier style-stripes-body'>
                        <thead>
                            <tr>
                            <th>Event</th>
                            <th className='textcenter'>Accreditations</th>
                            <th className='textcenter'>Participants</th>
                            </tr>
                        </thead>
                        <tbody>
                        {this.state.sideevents.map((event,idx) => (
                            <tr key={'f'+event.id}>
                              <td>{event.title}</td>
                              <td className='textcenter'>
                              {!(parseInt(event.competition_id)>0) && (
                                    <i className='pi'></i>
                              )}
                              {parseInt(event.competition_id) > 0 && (
                                    <i className='pi pi-download' onClick={()=>this.redirectList(event,"accreditations")}></i>
                              )}
                              </td>
                              <td className='textcenter'>
                                    <i className='pi pi-list' onClick={() => this.redirectList(event, "participants")}></i>
                              </td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
                <div className='col-12'>
                    <div className='textcenter'>
                        <span className='pi pi-search' onClick={() => this.onDialog('open')}>&nbsp;Inspect Photo's</span>
                    </div>
                </div>
                <AccreditationDialog event={this.props.item} value={this.state.fencer_object} display={this.state.displayDialog} onClose={() => this.onDialog('close')} onChange={(itm) => this.onDialog('change', itm)} onSave={(itm) => this.onDialog('save', itm)} goTo={(itm) => this.onDialog('goto',itm)} />
            </div>
        );
    }
}
