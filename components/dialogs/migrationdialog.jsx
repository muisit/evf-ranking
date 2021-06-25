import React from 'react';
import { migration } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';

export default class MigrationDialog extends React.Component {
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

    onCloseDialog = (event) => {
        if(parseInt(this.props.value.old_status) == 0) {
            this.loading(true);

            migration('save',{
                id: this.props.value.id,
                name: this.props.value.name,
                status: 1})
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
    }
    

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
        switch(event.target.name) {
        case 'status':
            if(event.target.value == '1') {
                var item=this.props.value;
                item.status='1';
                if(this.props.onChange) this.props.onChange(item);
            }
        }
    }

    render() {
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label="Execute" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
    </div>);
        if(this.props.value.old_status == '1') {
            footer=(<div>
                <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        </div>);    
        }


        return (
<Dialog header="Edit Migration" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>Name</h5>
    { this.props.value.name }
</Dialog>            
);
    }
}

