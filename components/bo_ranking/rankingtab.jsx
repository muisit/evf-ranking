import { events, ranking } from "../api.js";
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import RankingDialog from './dialogs/rankingdialog';
import PerusalDialog from './dialogs/perusaldialog';

import React from 'react';
import PagedTab from '../pagedtab';

const fieldToSorterList = {
    "id": "i",
    "opens": "d",
    "location": "l",
    "country_name": "c",
    "in_ranking":"r"
};

export default class RankingTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.dt = React.createRef();
        this.abortType = 'events';

        this.state = Object.assign(this.state, {
            multiSortMeta: [ { field: "opens", order: -1}],
            perusedialog: false
        });
    }

    fieldToSorter = (fld) => {
        return fieldToSorterList[fld];
    }

    apiCall = (o, p, f, s) => {
        return events(o, p, f, s, "with_competitions");
    }


    toastMessage = (type, item) => {
        if (type == "save") {
            return { severity: 'info', summary: 'Ranking settings saved', detail: 'Ranking settings for ' + item.name + ' were succesfully stored in the database', life: 3000 };
        }
        return { "severity": "info", "summary": "Unknown", "detail": "", "life": 1 };
    }

    renderDialog() {
        return (
        <div>
          <RankingDialog onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />
          <PerusalDialog onClose={this.onClose2} display={this.state.perusedialog}/>
        </div>
        );
    }

    onReset = () => {
        ranking("reset",{})
            .then((res) => {
                if(res && res.data && res.data.total) {
                    this.toast.show({severity:'info',summary:'Rankings Reset',detail:'The points used for calculating the ranking were re-evaluated, ' + res.data.total + ' results are included'});
                }
            });
    }

    onPeruse = () => {
        this.setState({perusedialog: true});
    }
    onClose2 = () => {
        this.setState({perusedialog: false});
    }

    renderAdd() {
        return (<div>
            <span className="p-input-icon-left header-button">
              <i className="pi pi-align-justify"></i><a href='#' onClick={this.onReset}>&nbsp;Calculate Ranking</a>
            </span>
            <span className="p-input-icon-left header-button">
              <i className="pi pi-search"></i><a href='#' onClick={this.onPeruse}>&nbsp;See Ranking</a>
            </span>
          </div>);
    }

    renderTable(pager) {
        return (<DataTable
            ref={this.dt}
            value={this.state.items}
            className="p-datatable-striped"
            paginator={true}
            header={pager}
            footer={pager}
            onRowDoubleClick={this.onEdit}
            sortMode="multiple" multiSortMeta={this.state.multiSortMeta} onSort={this.onSort}
        >
            <Column field="id" header="ID" sortable={true} />
            <Column field="name" header="Name" sortable={true} />
            <Column field="opens" header="Date" sortable={true} />
            <Column field="location" header="Location" sortable={true} />
            <Column field="country_name" header="Country" sortable={true} />
            <Column field="in_ranking" header="Ranking" sortable={true} />
            <Column field="factor" header="Factor" sortable={false} />
        </DataTable>);
    }
}
