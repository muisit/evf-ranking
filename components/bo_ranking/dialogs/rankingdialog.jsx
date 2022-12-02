import React from 'react';
import { singleevent } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputNumber } from 'primereact/inputnumber';

export default class RankingDialog extends React.Component {
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

        singleevent('save',this.props.value)
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
        var item = this.props.value;
        var name = event.target ? event.target.name : event.originalEvent.target.name;
        var value = event.target ? event.target.value : event.value;
        switch(name) {
        case 'in_ranking': 
        case 'factor':item[name] = value;break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    render() {
        let options = [{ name: 'Yes', code: 'Y' }, { name: 'No', code: 'N' }];

        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog header="Edit Ranking Settings" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
            <div>
                <label>Factor</label>
                <div className='input'>
                    <InputNumber className='inputint' name='factor' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal' minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.factor}></InputNumber>
                </div>
            </div>
            <div>
                <label>In Ranking</label>
                <div className='input'>
                <Dropdown name='in_ranking' appendTo={document.body} optionLabel="name" optionValue="code" value={this.props.value.in_ranking} options={options} placeholder="Ranked" onChange={this.onChangeEl}/>
                </div>
            </div>
        </Dialog>);
    }
}

