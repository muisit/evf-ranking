import React from 'react';
import { roletype } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';

export default class RoleTypeDialog extends React.Component {
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

        roletype('save',this.props.value)
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
        if(!event.target || !event.target.value) return;
        var item=this.props.value;
        switch(event.target.name) {
        case 'org_declaration':
        case 'name': item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete thie Role Type? This action cannot be undone!')) {
            this.loading(true);
            roletype('delete',{ id: this.props.value.id})
            .then((json) => {
                this.loading(false);
                this.delete();
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
        const declarations=[{name: 'Country', code: 'Country'},{name: 'Organisation', code: 'Org'},{name:'EVF',code:'EVF'},{name:'FIE',code:'FIE'}];

        return (<Dialog header="Edit Role Type" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
      <div>
        <label>Name</label>
        <div className='input'>
            <InputText name='name' value={this.props.value.name} onChange={this.onChangeEl} placeholder='Name'/>
        </div>
      </div>
      <div>
        <label>Organisation Source</label>
        <div className='input'>
        <Dropdown appendTo={document.body} name='org_declaration' optionLabel="name" optionValue="code" value={this.props.value.org_declaration} options={declarations} placeholder="Reg" onChange={this.onChangeEl}/>
        </div>
      </div>
</Dialog>
);
    }
}

