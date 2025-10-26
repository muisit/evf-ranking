import { Button } from 'primereact/button';
import { ToggleButton } from '../togglebutton';
import { Toast } from 'primereact/toast';
import { InputNumber } from 'primereact/inputnumber';
import { InputText } from 'primereact/inputtext';
import PerusalDialog from './dialogs/perusaldialog.jsx';
import { abort_all_calls, ranking, singleevent, competitions, result, apidata } from "../api.js";

import React from 'react';

export default class ActionsTab extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.abortType='fencers';
        this.state = {
            peruseDialog: false,
            events: [],
            cutoff: 5,
            apiuser: -1,
            apikey: '',
            toggleTest: 'N'
        };
    }

    componentDidMount = () => {
        apidata()
            .then ((res) => {
                if (res && res.data) {
                    this.setState({cutoff: res.data.cutoff, apiuser: res.data.apiuser, apikey: res.data.apikey, events: res.data.events});
                }
            });
    }

    componentWillUnmount = () => {
        abort_all_calls(this.abortType);
    }

    onRecalculateDialog = () => {
        ranking("reset",{})
        .then((res) => {
            if(res && res.data && res.data.total) {
                this.toast.show({severity:'info',summary:'Rankings Reset',detail:'The points used for calculating the ranking were re-evaluated, ' + res.data.total + ' results are included'});
            }
        });
    }

    onToggleRanking = (event, value) => {
        singleevent('ranking', {id: event.id, inRanking: value ? 'Y' : 'N'})
            .then(() => {
                var events = this.state.events.map((ev) => {
                    if (ev.id == event.id) {
                        ev.inRanking = value ? 'Y' : 'N';
                    }
                    return ev;
                });
                this.setState({events: events});
            });
    }

    onPeruseDialog = () => {
        this.setState({peruseDialog: true});
    }
    onClosePeruseDialog = () => {
        this.setState({peruseDialog: false});
    }

    onCreateEvent = () => {
        this.props.onAction({event: 'addEvent'});
    }

    onChange = (name, value) => {
        switch (name) {
            case 'cutoff':
                this.setState({cutoff:value});
                break;
            case 'apikey':
                if (this.state.apikey != value) {
                    this.setState({apikey:value});
                }
                break;
            case 'apiuser':
                this.setState({apiuser:value});
                break;
        }
    }

    recalculateResults = (event) => {
        competitions(event.id).then((data) => {
            if (data && data.data.list) {
                var compsToCalc = [];
                data.data.list.map((cmp) => {
                    result("recalculate",{competition_id: cmp.id})
                        .then((res) => {
                            compsToCalc.push(cmp.id);
                            if (compsToCalc.length == data.data.list.length) {
                                alert("All competitions recalculated");
                            }
                        })
                });
            }
        });
    }

    onStore = (name) => {
        switch(name) {
            case 'cutoff':
                apidata({ cutoff: this.state.cutoff})
                    .then((res) => {
                        if (res && res.data) {
                            this.setState({cutoff: res.data.cutoff, apiuser: res.data.apiuser, apikey: res.data.apikey});
                            this.toast.show({severity:'info',summary:'Count stored',detail:'The configured include count for cut-off of ranking points was stored'});                            
                        }
                    })
                break;
            case 'apikey':
                if (this.state.apikey && this.state.apikey.length) {
                    apidata({ apikey: this.state.apikey})
                        .then((res) => {
                            if (res && res.data) {
                                this.setState({cutoff: res.data.cutoff, apiuser: res.data.apiuser, apikey: res.data.apikey});
                                this.toast.show({severity:'info',summary:'Key stored',detail:'The configured API key was stored'});
                            }
                        })
                }
                break;
            case 'apiuser':
                apidata({ apiuser: this.state.apiuser})
                    .then((res) => {
                        if (res && res.data) {
                            this.setState({cutoff: res.data.cutoff, apiuser: res.data.apiuser, apikey: res.data.apikey});
                            this.toast.show({severity:'info',summary:'User stored',detail:'The configured API user ID was stored'});                            
                        }
                    })
                break;
        }
    }

    render() {
        return (
<div>
    <Toast ref={(el) => this.toast = el} />
    <div className="datatable container">
        <div className='row'>
            <div className='col-2'>
                <label>Rankings</label>
            </div>
            <div className='col-10'>
                <Button label="Recalculate" icon="pi pi-align-justify" className="p-button p-button-raised p-button-text" onClick={this.onRecalculateDialog} />
                <p className='small'>
                    Determines which results for each fencer are included or excluded in the ranking.
                    Currently, this retains the { this.state.cutoff } highest scoring results of all events that
                    are included in the ranking.
                </p>
                <Button label="Peruse ranking" icon="pi pi-search" className="p-button p-button-raised p-button-text" onClick={this.onPeruseDialog} />
                <p className='small'>
                    Opens the ranking perusal dialog to skim through the current ranking. This is very similar
                    to the front end interface.
                </p>
            </div>
        </div>
        <div className='row'>
            <div className='col-2'>
                <label>Include count</label>
            </div>
            <div className='col-10'>
                <InputNumber className='inputint' name='cutoff' onChange={(event) => this.onChange('cutoff', event.value)}
                        mode="decimal" inputMode='decimal' step={1} min={1} useGrouping={false} onBlur={() => this.onStore('cutoff')}
                        value={this.state.cutoff}></InputNumber>
                <p className='small'>
                    Indicate the number of events to include in the ranking out of all eligible events
                </p>
            </div>
        </div>
        <div className='row'>
            <div className='col-2'>
                <label>Ranking Events</label>
            </div>
            <div className='col-10'>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                {this.state.events && this.state.events.map((ev) => {
                    return (
                        <tr key={ev.id}>
                            <td>{ev.id}</td>
                            <td>{ev.name}</td>
                            <td>{ev.opens}</td>
                            <td>
                                <ToggleButton onLabel='Included' offLabel='Excluded' checked={ev.inRanking == 'Y'} onChange={(e) => this.onToggleRanking(ev, e)} />
                            </td>
                            <td>
                                <i className="pi pi-replay"></i><a onClick={()=>this.recalculateResults(ev)}>Recalculate</a>
                            </td>
                        </tr>
                    );
                })}
                </tbody></table>
            </div>
        </div>
        <div className='row'>
            <div className='col-2'>
                <label>API Key</label>
            </div>
            <div className='col-10'>
                <InputText className='input' name='apikey' onChange={(event) => this.onChange('apikey', event.target.value)}
                        onBlur={() => this.onStore('apikey')}
                        value={this.state.apikey}></InputText>
                <p className='small'>
                    Provide the API key to the back-end API
                </p>
            </div>
        </div>
        <div className='row'>
            <div className='col-2'>
                <label>API User</label>
            </div>
            <div className='col-10'>
                <InputText className='input' name='apiuser' onChange={(event) => this.onChange('apiuser', event.target.value)}
                        onBlur={() => this.onStore('apiuser')}
                        value={this.state.apiuser}></InputText>
                <p className='small'>
                    Provide the API user ID
                </p>
            </div>
        </div>
    </div>
    <PerusalDialog onClose={this.onClosePeruseDialog} display={this.state.peruseDialog}/>
</div>);
//        <div className='row'>
//        <div className='col-2'>
//            <label>Events</label>
//        </div>
//        <div className='col-10'>
//            <Button label="Add" icon="pi pi-plus" className="p-button p-button-raised p-button-text" onClick={this.onCreateEvent} />
//        </div>
//    </div>
    }
}
