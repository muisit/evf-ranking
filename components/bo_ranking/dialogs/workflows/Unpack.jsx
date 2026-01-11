import React from 'react';
import { Button } from 'primereact/button';
import { workflow, error_handler } from "../../../api.js";

export default class Unpack extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            file_id:-1
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }    

    unpack = () => {
        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'select_event'
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    }

    render() {
        return (
      <div>
        <div className='title'>Extracted</div>
        {this.props.value.sandbox?.files && this.props.value.sandbox.files.map((item) => {
            if (item && item.unpacked) {
                return (<div key={item.id} className='filename'>{item.name}</div>);
            }
        })
        }
        <div className='alignright'><Button label="Continue" icon="pi pi-caret-right" className="p-button-primary p-button-raised p-button-text" onClick={this.unpack} /></div>
      </div>
        );
    }
}

