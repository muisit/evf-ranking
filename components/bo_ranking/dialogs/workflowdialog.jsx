import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import UploadXML from './workflows/UploadXML.jsx';
import { workflow, error_handler } from '../../api.js';

export default class WorkflowDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            data: {},
            closed: false
        }
    }

    componentDidUpdate = () => {
        console.log('didUpdate:', this.props.display, this.state.data);
        if (this.props.display && (!this.state.data || !this.state.data.id || this.state.data.id < 0)) {
            this.loading(true);
            workflow('step', {id: -1, name: this.props.value})
                .then((json) => {
                    this.loading(false);
                    if (json.data && json.data.model) {
                        this.setState({data: json.data.model});
                    }
                })
                .catch(error_handler);
        }
        else {
            console.log('no update');
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    testClose = () => {
        if (this.data.closed) {
            this.close();
        }
    }

    nextStep = (step) => {
        this.setState({data: step});
    }

    render() {
        const step = this.state.data?.step ?? this.props.value;
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog header={this.props.title} position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
            <p>{step} , {this.props.value}</p>
            {step == 'uploadXML' && (<UploadXML value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} />)}
</Dialog>
);
    }
}

