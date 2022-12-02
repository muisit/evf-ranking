import { events, competitions } from "../api.js";
import { DataTable } from 'primereact/components/datatable/DataTable';
import { Column } from 'primereact/components/column/Column';
import EventDialog from './dialogs/eventdialog';
import { format_date, parse_date } from '../functions';

import React from 'react';
import PagedTab from '../pagedtab';

const fieldToSorterList={
    "id":"i",
    "name":"n",
    "opens": "d",
    "year": "y",
    "type_name":"t"
};

export default class EventsTab extends PagedTab {
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

    onAdd = (event) => {
        var dt=parse_date();
        var year=dt.year();

        this.setState({ item: { 
            id: -1, 
            year: year, 
            base_fee: 50.0, 
            competition_fee: 30.0, 
            duration: 2,
            opens: format_date(dt)
        }, displayDialog: true });
    }

    onEdit = (event)=> {
        competitions(event.data.id)
            .then((cmp) => {
                if(cmp) {
                    var item = Object.assign({competitions: cmp.data.list},event.data);
                    this.setState({item: item, displayDialog:true });                    
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
            <EventDialog countries={this.props.countries} types={this.props.types} onDelete={this.onDelete} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item}/>
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
