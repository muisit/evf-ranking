import React from 'react';
import { fencer } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';

export default class FencerDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            old_status:-1
        }
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

        console.log('saving ',this.props.value);
        fencer('save',this.props.value)
            .then((json) => {
                this.loading(false);
                this.save(this.props.value);
            })
            .catch((err) => {
                console.log("caught error ",err);
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
        case 'firstname':
        case 'name':
        case 'country':
        case 'birthday':
        case 'gender':item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete fencer '+ this.props.value.name + "? This action cannot be undone!")) {
            this.loading(true);
            fencer('delete',{ id: this.props.value.id})
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
        let genders = [{ name: 'Male', code: 'M' }, { name: 'Female', code: 'F' }];

        return (<Dialog header="Edit Fencer" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>Name</h5>
    <div className="p-grid p-fluid">
      <div className="p-col-12 p-md-6">
        <div className="p-inputgroup">
          <InputText name='name' className="p-inputtext-sm" value={this.props.value.name} placeholder="Surname" onChange={this.onChangeEl} />
        </div>
      </div>
      <div className="p-col-12 p-md-6">
        <div className="p-inputgroup">
          <InputText name='firstname' className="p-inputtext-sm" value={this.props.value.firstname} placeholder="Firstname" onChange={this.onChangeEl} />
        </div>
      </div>
    </div>
    <h5>Details</h5>
    <div className="p-grid p-fluid">
      <div className="p-col-12 p-md-4">
        <div className="p-inputgroup">
          <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country} options={this.props.countries} placeholder="Country" onChange={this.onChangeEl}/>
        </div>
      </div>
      <div className="p-col-12 p-md-4">
        <div className="p-inputgroup">
          <InputMask name='birthday' mask="9999-99-99" slotChar="yyyy-mm-dd" value={this.props.value.birthday} onChange={this.onChangeEl}/>
        </div>
      </div>
      <div className="p-col-12 p-md-4">
        <div className="p-inputgroup">
          <Dropdown name='gender' appendTo={document.body} optionLabel="name" optionValue="code" value={this.props.value.gender} options={genders} placeholder="Gender" onChange={this.onChangeEl}/>
        </div>
      </div>
    </div>
</Dialog>
);
    }
}

