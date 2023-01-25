import { Button } from 'primereact/button';
import { Toast } from 'primereact/toast';
import PerusalDialog from './dialogs/perusaldialog';
import { abort_all_calls, ranking } from "../api.js";

import React from 'react';

export default class ActionsTab extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.abortType='fencers';
        this.state = {
            peruseDialog: false
        };
    }

    componentDidMount = () => {
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

    onPeruseDialog = () => {
        this.setState({peruseDialog: true});
    }
    onClosePeruseDialog = () => {
        this.setState({peruseDialog: false});
    }

    onCreateEvent = () => {
        this.props.onAction({event: 'addEvent'});
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
                    Currently, this retains the four (4) highest scoring results of all events that
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
                <label>Events</label>
            </div>
            <div className='col-10'>
                <Button label="Add" icon="pi pi-plus" className="p-button p-button-raised p-button-text" onClick={this.onCreateEvent} />
            </div>
        </div>
    </div>
    <PerusalDialog onClose={this.onClosePeruseDialog} display={this.state.peruseDialog}/>
</div>);
    }
}
