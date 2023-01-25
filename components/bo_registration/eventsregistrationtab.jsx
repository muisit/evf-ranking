import { eventroles, events, sideevents } from "../api.js";
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import EventRegistrationDialog from './dialogs/eventregistrationdialog';

import React from 'react';
import PagedTab from '../pagedtab';

const fieldToSorterList={
    "id":"i",
    "name":"n",
    "opens": "d",
    "year": "y",
    "type_name":"t"
};

export default class EventsRegistrationTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType='events';

        this.state = Object.assign(this.state, {
            multiSortMeta: [ { field: "opens", order: -1}],
        });
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    apiCall = (o,p,f,s) => {
        return events(o,p,f,s);
    }

    onEdit = (event)=> {
        var p1=sideevents(event.data.id).then((cmp1) => cmp1.data.list);
        var p2=eventroles(event.data.id).then((cmp2) => cmp2.data.list);

        Promise.all([p1,p2])
          .then((results) => {
            if(results.length == 2) {
                var item = Object.assign({ sides: results[0], roles: results[1] }, event.data);
                this.setState({ item: item }, () => this.props.onAction({event: 'openDialog'}));
            }
          });
        return false;
    }


    toastMessage = (type,item) => {
        if(type == "save") {
            return { severity: 'info', summary: 'Event Saved', detail: 'Event ' + item.name+ ' was succesfully stored in the database', life: 3000 };
        }
        if(type == "delete") {
            return { severity: 'info', summary: 'Event Deleted', detail: 'Event ' + item.name+ ' was succesfully removed from the database', life: 3000 };
        }
        return {"severity":"info","summary":"Unknown","detail":"","life":1};
    }

    renderDialog() {
        return (
            <EventRegistrationDialog users={this.props.users} countries={this.props.countries} types={this.props.types} onDelete={this.onDelete} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.props.displayDialog} value={this.state.item}/>
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
    <Column field="type_name" header="Type" sortable={true}/>
    <Column field="year" header="Year" sortable={true}/>
    <Column field="opens" header="Open" sortable={true} />
</DataTable>);
    }
}
