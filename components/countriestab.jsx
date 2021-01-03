import { countries, country } from "./api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Paginator } from 'primereact/paginator';
import { Toast } from 'primereact/toast';

import React from 'react';
import PagedTab from './pagedtab';
import CountryDialog from './dialogs/countrydialog';

const fieldToSorterList={
    "id":"i",
    "name":"n"
};

export default class CountriesTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='countries';
    }

    apiCall = (o,p,f,s) => {
        return countries(o,p,f,s);
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    toastMessage = (type,item) => {
        if(type == "save") {
            return { severity: 'info', summary: 'Country Saved', detail: 'Country ' + item.name+ ' was succesfully stored in the database', life: 3000 };
        }
        if(type == "delete") {
            return { severity: 'info', summary: 'Country Deleted', detail: 'Country ' + item.name + ' was succesfully removed from the database', life: 3000 };
        }
        return {"severity":"info","summary":"Unknown","detail":"","life":1};
    }

    renderDialog() {
        return (
            <CountryDialog onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />
        );
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete country '+ this.state.country_name + "? This action cannot be undone!")) {
            this.setState({loading:true});
            country('delete',{ id: this.state.country_id})
            .then((json) => {
                this.setState({displayDialog:false});
                this.loadItemPage();
                this.toast.show({ severity: 'info', summary: 'Country Deleted', detail: 'Country ' + this.state.country_name+ ' was succesfully removed from the database', life: 3000 });
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

    onCloseDialog = (event) => {
        this.setState({loading:true});
        country('save',{
            id: this.state.country_id,
            name: this.state.country_name,
            abbrev: this.state.ountry_abbrev,
            registered: this.state.country_registered})
            .then((json) => {
                this.setState({displayDialog:false});
                this.loadItemPage();
                console.log('toasting');
                this.toast.show({ severity: 'info', summary: 'Country Saved', detail: 'Country ' + this.state.country_name+ ' was succesfully stored in the database', life: 3000 });
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
        this.setState({displayDialog:false});
    }

    onChangeAbbrev = (event) => {
        if(event.type == "change") {
            this.setState({country_abbrev: event.target.value.toUpperCase()});
        }
    }

    onChangeName = (event) => {
        if(event.type == "change") {
            this.setState({country_name: name});
        }
    }

    onRegisterChange = (event)=> {
        this.setState({'country_registered':event.value});
    }

    renderDialog() {
        return (<CountryDialog countries={this.props.countries} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />);
    }

    renderTable(pager) {
        return (<DataTable
    ref={this.dt}
    value={this.state.items}
    className="p-datatable-striped"
    paginator={false}
    header={pager}
    footer={pager}
    onRowDoubleClick={this.onEdit}
    sortMode="multiple" multiSortMeta={this.state.multiSortMeta} onSort={this.onSort}
    >
    <Column field="id" header="ID" sortable={true} />
    <Column field="abbr" header="Abbreviation" sortable={false}/>
    <Column field="name" header="Name" sortable={true}/>
    <Column field="registered" header="Registered" sortable={false} />
</DataTable>
);
    }
}
