import { roletypes } from "./api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';

import React from 'react';
import PagedTab from './pagedtab';
import RoleTypeDialog from './dialogs/roletypedialog';

const fieldToSorterList={
    "id":"i",
    "name":"n"
};

export default class RoleTypeTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='roletypes';
    }

    apiCall = (o,p,f,s) => {
        return roletypes(o,p,f,s);
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    toastMessage = (type,item) => {
        if(type == "save") {
            return { severity: 'info', summary: 'Role Type Saved', detail: 'Role Type was succesfully stored in the database', life: 3000 };
        }
        if(type == "delete") {
            return { severity: 'info', summary: 'Role Type Deleted', detail: 'Role Type was succesfully removed from the database', life: 3000 };
        }
        return {"severity":"info","summary":"Unknown","detail":"","life":1};
    }

    renderDialog() {
        return (<RoleTypeDialog onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />);
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
    <Column field="org_declaration" header="Org" sortable={true}/>
</DataTable>
);
    }
}
