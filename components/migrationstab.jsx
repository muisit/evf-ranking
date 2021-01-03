import React from 'react';
import { migrations } from "./api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Paginator } from 'primereact/paginator';
import MigrationDialog from './dialogs/migrationdialog';
import { Toast } from 'primereact/toast';
import PagedTab from './pagedtab';

const fieldToSorterList = {
    "id": "i",
    "name": "n"
};

export default class MigrationsTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType = 'migrations';
    }
    apiCall = (o, p, f, s) => {
        return migrations(o, p, f, s)
            .then((json) => {
                var lst = json.data.list.map((el)=> {
                    el.executed = el.status == 1 ? 'Y':'N';
                    return el;
                });
                json.data.list=lst;
                return json;
            });
    }

    onEdit = (event) => {
        this.setState({ item: Object.assign({old_status: event.data.status}, event.data), displayDialog: true });
        return false;
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    toastMessage = (type, item) => {
        if (type == "save") {
            return { severity: 'info', summary: 'Migration Executed', detail: 'Migration ' + item.name + ' was executed', life: 3000 };
        }
        return { "severity": "info", "summary": "Unknown", "detail": "", "life": 1 };
    }

    renderDialog() {
        return (
            <MigrationDialog onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item}/>
        );
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
    <Column field="executed" header="Executed" sortable={false} />
</DataTable>);
    }
}
