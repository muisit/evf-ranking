import { fencers, results } from "../api.js";
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import FencerDialog from '../dialogs/fencerdialog';
import MergeFencers from "./dialogs/mergefencers.jsx";

import PagedTab from '../pagedtab';

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

        this.state = Object.assign(this.state, {
            displayMergeDialog: false
        });
    }

    apiCall = (o,p,f,s) => {
        return fencers(o,p,{name: f},s);
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


    onEdit = (event)=> {
        results(0,10000,{id: event.data.id},"D",{"withevents":true})
            .then((json) => {
                this.setState({item: Object.assign({},event.data, {results: json.data.list}), displayDialog:true });
            });
        return false;
    }

    renderDialog() {
        return (<div>
            <FencerDialog countries={this.props.countries} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onDelete={this.onDelete} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />
            <MergeFencers countries={this.props.countries} display={this.state.displayMergeDialog} onClose={()=> { this.setState({displayMergeDialog:false}, this.loadItemPage); }} />
            </div>
        );
    }

    onMerge = (event) => {
        this.setState({item: {id:-1},displayMergeDialog:true});
    }

    renderAdd() {
        return (<div>
            <span className="p-input-icon-left header-button">
                <i className="pi pi-plus-circle"></i><a onClick={this.onAdd}>&nbsp;Add</a>
            </span>
            <span className="p-input-icon-left header-button">
              <i className="pi pi-link"></i><a href='#' onClick={this.onMerge}>&nbsp;Merge</a>
            </span>
        </div>);
    }

    renderTable(pager) {
        return (<DataTable
    ref={this.dt}
    value={this.state.items}
    className="p-datatable-striped"
    lazy={true} onPage={this.onLazyLoad} loading={this.state.loading}
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
