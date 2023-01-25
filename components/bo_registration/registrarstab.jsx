import { registrars } from "../api.js";
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import React from 'react';
import PagedTab from '../pagedtab';
import RegistrarDialog from './dialogs/registrardialog';

const fieldToSorterList={
    "id":"i",
    "name":"n",
    "country": "c"
};

export default class RegistrarsTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='registrars';
    }

    apiCall = (o,p,f,s) => {
        return registrars(o,p,f,s);
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    toastMessage = (type,item) => {
        if(type == "save") {
            return { severity: 'info', summary: 'Registrar Saved', detail: 'Registrar was succesfully stored in the database', life: 3000 };
        }
        if(type == "delete") {
            return { severity: 'info', summary: 'Registrar Deleted', detail: 'Registrar ' + item.name + ' was succesfully removed from the database', life: 3000 };
        }
        return {"severity":"info","summary":"Unknown","detail":"","life":1};
    }

    renderDialog() {
        return (<RegistrarDialog users={this.props.users} countries={this.props.countries} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.props.displayDialog} value={this.state.item} />);
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
    <Column field="name" header="Name" sortable={true}/>
    <Column field="country_name" header="Country" sortable={true} />
</DataTable>
);
    }
}
