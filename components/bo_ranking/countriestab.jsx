import { countries, country } from "../api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Paginator } from 'primereact/paginator';
import { Toast } from 'primereact/toast';

import React from 'react';
import PagedTab from '../pagedtab';
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
