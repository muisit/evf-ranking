import React from 'react';
import { result } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputNumber } from 'primereact/inputnumber';
import { Checkbox } from 'primereact/checkbox';

export default class ResultDialog extends React.Component {
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

        result('save',this.props.value)
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

    onChangeEl = (event, val) => {
        var item=this.props.value;
        var item = this.props.value;
        var name = event;
        var value = val;

        if (event.target) {
            name = event.target.name;
            value = event.target.value;
        }
        else if (event.originalEvent) name = event.originalEvent.target.name;

        if (event.value && !val) {
            value = event.value;
        }

        switch(name) {
        case 'ranked':
        case 'points':
        case 'place':
        case 'nat_points':
        case 'de_points':
        case 'podium_points':
        case 'entry':
        case 'total_points':        
        case 'factor':item[name] = value;break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete the result for fencer '+ this.props.value.fencer_surname + "? This action cannot be undone!")) {
            this.loading(true);
            result('delete',{ id: this.props.value.id})
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

    onSearchFencer = (ev) => {
        
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

        var rankedValues = [
            { 'label': 'Yes', 'value': 'Y'},
            { 'label': 'No', 'value': 'N'},
            { 'label': 'Exclude', 'value': 'E'},
        ];

        return (<Dialog header="Edit Result" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
            <div>
                <label>Surname</label>
                <div className='input'>
                    <InputText name='name' className="p-inputtext-sm" value={this.props.value.fencer_surname} placeholder="Surname" onChange={this.onSearchFencer} />
                </div>
            </div>
            <div>
                <label>Firstname</label>
                <div className='input'>
                    <InputText name='name' className="p-inputtext-sm" value={this.props.value.fencer_firstname} placeholder="First name" onChange={this.onSearchFencer} />
                </div>
            </div>
            <div>
                <label>Country</label>
                <div className='input'>
                    <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country_id} options={this.props.countries} placeholder="Country" onChange={this.onSearchFencer} />
                </div>
            </div>
            <div>
                <label>Place</label>
                <div className='input'>
                    <InputNumber className='inputint' onChange={this.onChangeEl} name='place' min={0} mode="decimal" useGrouping={false} value={this.props.value.place}></InputNumber>
                </div>
            </div>
            <div>
                <label>Entry</label>
                <div className='input'>
                    <InputNumber className='inputint' onChange={this.onChangeEl} name='entry' min={0} mode="decimal" useGrouping={false} value={this.props.value.entry}></InputNumber>
                </div>
            </div>
            <div>
                <label>Points</label>
                <div className='input'>
                    <InputNumber className='inputint' name='points' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal' minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.points}></InputNumber>
                </div>
            </div>
            <div>
                <label>National Points</label>
                <div className='input'>
                    <InputNumber className='inputint' name='nat_points' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal' minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.nat_points}></InputNumber>
                </div>
            </div>
            <div>
                <label>DE Points</label>
                <div className='input'>
                    <InputNumber className='inputint' name='de_points' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal' minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.de_points}></InputNumber>
                </div>
            </div>
            <div>
                <label>Podium Points</label>
                <div className='input'>
                    <InputNumber className='inputint' name='podium_points' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal'  minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.podium_points}></InputNumber>
                </div>
            </div>
            <div>
                <label>Total Points</label>
                <div className='input'>
                    <InputNumber className='inputint' name='total_points' onChange={this.onChangeEl}
                        mode="decimal" inputMode='decimal' minFractionDigits={1} maxFractionDigits={5} min={0} useGrouping={false}
                        value={this.props.value.total_points}></InputNumber>
                </div>
            </div>
            {this.props.value.ranked != 'E' && (
                <div>
                    <label>Included in ranking</label>
                    <div className='input'>
                        {this.props.value.ranked == 'Y' && (<span>Yes</span>)}
                        {this.props.value.ranked == 'N' && (<span>No</span>)}
                    </div>
                </div>
            )}
            <div>
                <label htmlFor='excludeFromRanking'>Exclude permanently</label>
                <div className='input'>
                    <Checkbox inputId={'excludeFromRanking'} onChange={(e) => this.onChangeEl('ranked', e.checked ? 'E' : 'N')} checked={this.props.value.ranked == 'E'}/>
                </div>
            </div>
        </Dialog>);
    }
}

