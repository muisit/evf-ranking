import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { workflow, error_handler } from '../../api.js';
import { create_catById, create_countryById, create_wpnById } from '../../functions.js';
import UploadXML from './workflows/UploadXML.jsx';
import Unpack from './workflows/Unpack.jsx';
import SelectEvent from './workflows/SelectEvent.jsx';
import PrepareImport from './workflows/PrepareImport.jsx';
import SelectCompetition from './workflows/SelectCompetition.jsx';
import ImportFencers from './workflows/ImportFencers.jsx';

export default class WorkflowDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            data: {sandbox:{step:'Initialise'}},
            closed: false
        }
    }

    componentDidUpdate = () => {
        if (this.props.display && (!this.state.data || !this.state.data.id)) {            
            this.setState({data: {id:-1}}); // preventing the workflow initialisation from running again
            this.loading(true);
            workflow('step', {id: -1, name: this.props.value, step:'initialise'})
                .then((json) => {
                    this.loading(false);
                    if (json.data && json.data.id) {
                        this.setState({data: json.data});
                    }
                })
                .catch(error_handler);
        }
        else if(!this.props.display && this.state.data.sandbox?.step != 'Initialise') {
            this.setState({data:{sandbox:{step:"Initialise"}},closed:false});
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
    onCloseDialog = () => {
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
        const basicdata ={
            countries: this.props.countries,
            eventtypes: this.props.types,
            categories: this.props.categories,
            weapons: this.props.weapons,
            weaponsById: create_wpnById(this.props.weapons),
            categoriesById: create_catById(this.props.categories),
            countriesById: create_countryById(this.props.countries)
        };
        const step = this.state.data?.sandbox?.step ?? this.props.value;
        const footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
</div>);

        const header = (<span>Workflow {this.props.value} step {step}</span>);
        if (this.props.value == 'uploadXML') {
            return (<Dialog header={header} className='workflow-dialog' position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
                {step == 'Upload File' && (<UploadXML value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} />)}
                {step == 'Uploaded' && (<Unpack value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} />)}
                {step == 'Select Event' && (<SelectEvent value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} data={basicdata}/>)}
                {step == 'Prepare Import' && (<PrepareImport value={this.state.data} onLoad={this.loading} onClose={this.onCloseDialog} onCancel={this.onCancelDialog} onFinish={this.nextStep} data={basicdata}/>)}
                {step == 'Select Competition' && (<SelectCompetition value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} data={basicdata}/>)}
                {step == 'Import Fencers' && (<ImportFencers value={this.state.data} onLoad={this.loading} onCancel={this.onCancelDialog} onFinish={this.nextStep} data={basicdata}/>)}
            </Dialog>
            );
        }
        return (null);
    }
}

