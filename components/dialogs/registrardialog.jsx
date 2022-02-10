import React from 'react';
import { registrar } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';

export default class RegistrarDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    delete = (item) => {
        if(this.props.onDelete) this.props.onDelete(item);
        this.close();
    }

    onCloseDialog = (event) => {
        this.loading(true);

        registrar('save',this.props.value)
            .then((json) => {
                this.loading(false);
                this.save(this.props.value);
            })
            .catch((err) => {
                if(err.response.data.messages && err.response.data.messages.length) {
                    var txt="";
                    for(var i=0;i<err.response.data.messages.length;i++) {
                       txt+=err.response.data.messages[i]+"\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
        var item=this.props.value;
        var value=event.target.value;
        switch(event.target.name) {
        case 'country': if(value == -1) value=null;
        case 'user': item[event.target.name] = value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete this registrar? This action cannot be undone!')) {
            this.loading(true);
            registrar('delete',{ id: this.props.value.id})
            .then((json) => {
                this.loading(false);
                this.delete(this.props.value);
            })
            .catch((err) => {
                if(err.response.data.messages && err.response.data.messages.length) {
                    var txt="";
                    for(var i=0;i<err.response.data.messages.length;i++) {
                        txt+=err.response.data.messages[i]+"\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error removing the data. Please try again');
                }
            })
    
        }
    }

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);
        if(this.props.value.id >0) {
            footer=(<div>
                <Button label="Remove" icon="pi pi-trash" className="p-button-danger p-button-raised p-button-text" onClick={this.onDeleteDialog} />
                <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
                <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);
        }

        // we mess around with null and -1, because we cannot set null as drop-down value
        // but we cannot pass -1 to the back-end, because no-such-country
        var nullvalue = { 
            id:-1,
            name: 'General Administration'
        };
        var countries = this.props.countries.slice();
        countries.unshift(nullvalue);
        var cntvalue=this.props.value.country;
        if(cntvalue==null) cntvalue=-1;

        return (<Dialog header="Edit Registrar" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
      <div>
        <label>User</label>
        <div className='input'>
            <Dropdown name='user' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.user} options={this.props.users} placeholder="User" onChange={this.onChangeEl} />  
        </div>
      </div>
      <div>
        <label>Country</label>
        <div className='input'>
            <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={cntvalue} options={countries} onChange={this.onChangeEl} />
        </div>
      </div>
</Dialog>
);
    }
}

