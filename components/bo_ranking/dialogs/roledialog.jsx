import React from 'react';
import { role, roletypes } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';

export default class RoleDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state={
            roletypes:[]
        }

    }

    componentDidMount = () => {
        roletypes(0,1000,'',"n").then(json => { 
            this.setState({"roletypes": json.data.list});
        });
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

        role('save',this.props.value)
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
        case 'type':
        case 'name': item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete this role? This action cannot be undone!')) {
            this.loading(true);
            role('delete',{ id: this.props.value.id})
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

        return (<Dialog header="Edit Role" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
      <div>
        <label>Name</label>
        <div className='input'>
            <InputText name='name' value={this.props.value.name} onChange={this.onChangeEl} placeholder='Name'/>
        </div>
      </div>
      <div>
        <label>Type</label>
        <div className='input'>
          <Dropdown name='type' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.type} options={this.state.roletypes} placeholder="Type" onChange={this.onChangeEl}/>
        </div>
      </div>
</Dialog>
);
    }
}

