import { fencers, fencer } from "./api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';
import { Slider } from 'primereact/slider';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Paginator } from 'primereact/paginator';
import { Toast } from 'primereact/toast';
import FencerDialog from './dialogs/fencerdialog';

import PagedTab from './pagedtab';

import React from 'react';

const fieldToSorterList={
    "id":"i",
    "name":"n",
    "firstname":"f",
    "country_name":"c",
    "gender":"g",
    "birthday":"b"
};

export default class FencersTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='fencers';
    }

    apiCall = (o,p,f,s) => {
        return fencers(o,p,f,s);
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    toastMessage = (type,item) => {
        if(type == "save") {
            return { severity: 'info', summary: 'Fencer Saved', detail: 'Fencer ' + item.name+ ' was succesfully stored in the database', life: 3000 };
        }
        if(type == "delete") {
            return { severity: 'info', summary: 'Fencer Deleted', detail: 'Fencer ' + item.name + ' was succesfully removed from the database', life: 3000 };
        }
        return {"severity":"info","summary":"Unknown","detail":"","life":1};
    }

    renderDialog() {
        return (
            <FencerDialog countries={this.props.countries} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />
        );
    }

    renderTable(pager) {
        return (<DataTable
    ref={this.dt}
    value={this.state.items}
    className="p-datatable-striped"
    lazy={true} onPage={this.onLazyLoad} loading={this.state.loading}
    paginator={false}
    header={pager}
    footer={pager}
    sortMode="multiple" multiSortMeta={this.state.multiSortMeta} onSort={this.onSort}
    onRowDoubleClick={this.onEdit}
    >
    <Column field="id" header="ID" sortable={true} />
    <Column field="name" header="Name" sortable={true}/>
    <Column field="firstname" header="First" sortable={true}/>
    <Column field="country_name" header="Country" sortable={true}/>
    <Column field="gender" header="Sex" sortable={true} />
    <Column field="birthday" header="Birthday" sortable={true}/>
</DataTable>);
    }
}
