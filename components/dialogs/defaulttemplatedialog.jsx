import React from 'react';
import { template } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { parse_net_error } from '../functions';

export default class DefaultTemplateDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            name: ''
        }
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    onCloseDialog = (event) => {
        template('default', { id: this.props.value.id, name: this.state.name })
            .then((json) => {
                this.save();
            })
            .catch((err) => parse_net_error(err));
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
        if(!event.target || !event.target.value) return;
        var item=this.props.value;
        switch(event.target.name) {
        case 'name':
            this.setState({name: event.target.value}); 
            break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog baseZIndex={200000} header="Set as Default" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
      <div>
        <label>Name</label>
        <div className='input'>
            <InputText name='name' value={this.state.name} onChange={this.onChangeEl} placeholder='Name'/>
        </div>
      </div>
</Dialog>
);
    }
}

