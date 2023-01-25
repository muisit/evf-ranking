import { events, weapons, categories, results, result, competitions } from "../api.js";
import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Dropdown } from 'primereact/dropdown';
import { Slider } from 'primereact/slider';
import { Paginator } from 'primereact/paginator';
import ResultDialog from './dialogs/resultdialog';
import ImportDialog from './dialogs/importdialog';

import React from 'react';
import PagedTab from '../pagedtab';

const fieldToSorterList = {
    "id": "i",
    "fencer_surname": "n",
    "fencer_firstname": "f",
    "place": "p",
    "country": "c",
    "points": "s",
    "total_points": "t"
};

export default class ResultsTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.state = {
            event_id: 0,
            competition: {},
            events:[],
            weapons:{},
            categories:{},
            loading: false,
            sorting:"i",
            sortField: "",
            sortOrder: 1,
            multiSortMeta: [ { field: "place", order: 1}],
            filter: "",
            items: [],
            competitions: [],
            count: 0,
            pagesize: 20,
            offset: 0,
            page: 1,
            noslider: false,
            filterTimeId:null,
            displayDialog: false,
            item: {},
            importDialog: false,
            importObject: {}
        };
        this.dt = React.createRef();
        this.abortType = 'events';
    }

    componentDidMount = () => {
        events(0, 0, '', "D", "with_competitions").then((evnts) => { if (evnts) {
            this.setState({'events': evnts.data.list });
        }});
        weapons().then((wpns) => { if (wpns) {
                var byid={};
                for(var i in wpns.data.list) {
                    var obj=wpns.data.list[i];
                    var key="k"+obj.id;
                    byid[key]=obj;
                }
                this.setState({ 'weapons': byid });
            }
        });
        categories().then((cats) => { if (cats) {
                var byid={};
                for(var i in cats.data.list) {
                    var obj=cats.data.list[i];
                    var key="k"+obj.id;
                    byid[key]=obj;
                }
                this.setState({ 'categories': byid });
            }
        });
    }

    fieldToSorter = (fld) => {
        if(fieldToSorterList[fld])  return fieldToSorterList[fld];
        return "id";
    }

    loadItemPage = () => {
        var sorting = this.convertMultiSort();
        this.apiCall(this.state.offset,this.state.pagesize,this.state.filter,sorting)
            .then(json => {
                var maxpages = parseInt(Math.floor(json.data.total / this.state.pagesize));
                if((maxpages * this.state.pagesize ) < json.data.total) {
                    maxpages+=1;
                }
                if(this.state.competition.id) {
                    this.setState({ 
                        "items": json.data.list, 
                        "count": json.data.total, 
                        "pages": maxpages, 
                        "noslider": maxpages<1
                    });
                }
                else {
                    this.setState({ 
                        "items": [1], // dummy value
                        "competition": {},
                        "competitions": json.data.list
                    });
                }
        });
    }


    apiCall = (o, p, f, s) => {    
        if(this.state.competition.id) {
            var special = JSON.stringify({ competition_id: this.state.competition.id });
            return results(o, p, f, s, special);
        }
        else if(this.state.event_id > 0) {
            return competitions(this.state.event_id)
            .then((cmp) => {
                if(cmp) {
                    var cmps = cmp.data.list.map((itm,idx) => {
                        var k1="k" + itm.category;
                        if(this.state.categories[k1]) {
                            itm.category_name = this.state.categories[k1].name;
                            itm.category_obj = this.state.categories[k1];
                        }
                        var k2="k" + itm.weapon;
                        if(this.state.weapons[k2]) {
                            itm.weapon_name = this.state.weapons[k2].name;
                            itm.weapon_obj = this.state.weapons[k2];
                        }
                        return itm;
                    });
                    return {data: {
                        list: cmps,
                        total: cmps.length
                    }}
                }
                else {
                    return {data: {total:0, list:[]}};
                }
            });
        }
        return {data: {total:0, list:[]}};
    }

    checkState = () => {
        if (this.state.event_id > 0) {
            this.loadItemPage();
        }
    }

    onChangeEl = (ev) => {
        var name = ev.target.name;
        var value = ev.target.value;
        if(name == "event") {
            this.setState({event_id: value, items:[], competition:{}, competitions:[]}, this.checkState);
        }
    }

    onImport = (tp, itm) => {
        if(tp==='open') {
            var evname=this.state.events.filter((e) => e.id == this.state.event_id).map((e)=> e.name);
            var comptitle=''+evname +' ' + this.state.competition.weapon_name + " " + this.state.competition.category_name;
            this.setState({importObject: {'text':"","object":{ranking:[], competition_id: this.state.competition.id },title: comptitle}, importDialog:true});
        }
        else if(tp === 'clear') {
            result("clear",{competition_id: this.state.competition.id})
                .then((res) => {
                    this.loadItemPage();
                });
        }
        else if(tp === 'recalc') {
            result("recalculate",{competition_id: this.state.competition.id})
                .then((res) => {
                    this.loadItemPage();
                });
        }
        else if(tp === 'close') {
            this.setState({importDialog:false, importObject: {}, competition_id: null}, () => {
                this.loadItemPage();
            });
        }        
        else if(tp === 'change') {
            this.setState({importObject:itm});
        }
    }

    toastMessage = (type, item) => {
        if (type == "save") {
            return { severity: 'info', summary: 'Result Saved', detail: 'Result for ' + item.fencer_surname + ' was succesfully stored in the database', life: 3000 };
        }
        if (type == "delete") {
            return { severity: 'info', summary: 'Result Deleted', detail: 'Result for ' + item.fencer_surname + ' was succesfully removed from the database', life: 3000 };
        }
        return { "severity": "info", "summary": "Unknown", "detail": "", "life": 1 };
    }

    renderDialog() {
        return (
            <div>
        <ResultDialog countries={this.props.countries} onDelete={this.onDelete} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.state.displayDialog} value={this.state.item} />
        <ImportDialog 
            countries={this.props.countries} competition={this.state.competition} event={this.state.event_id} weapons={this.state.weapons}
            onClose={()=>this.onImport('close')} onChange={(itm)=>this.onImport('change',itm)} onSave={()=>this.onImport('save')} 
            value={this.state.importObject} display={this.state.importDialog}
            />
        </div>
        );
    }

    renderFilter() {
        return (
            <span className="p-input-icon-left search-input search-input-results">
                <Dropdown className='evntdrop' appendTo={document.body} name="event" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={this.state.event_id} options={this.state.events} placeholder="Event" />
            </span>
        );
    }

    renderAdd() {
        if(this.state.competition.id) {
            return (<span className="p-input-icon-left add-button">
                <i className="pi pi-plus-circle"></i><a onClick={()=>this.onImport('open')}>Import</a>
                <i className="pi pi-trash"></i><a onClick={()=>this.onImport('clear')}>Clear</a>
                <i className="pi pi-replay"></i><a onClick={()=>this.onImport('recalc')}>Recalculate</a>
                <i className="pi pi-caret-left"></i><a onClick={()=>this.setState({competition: {}, items: [1]}) }>Back</a>
            </span>);
        }
    }

    renderPager() {
        if(!this.state.competition.id) {
            return (<div></div>);
        }

        let pagesizes=[5,10,20,50];//{name: 5, code: 5},{name: 10, code: 10}, {name:20, code:20},{name: 50, code:50}];
        if(this.state.pages > 10) {
            return (<div className='p-d-block pager'>
    <div className='p-d-inline-block slider'>
      <Slider value={this.state.page} onChange={this.onSliderChange} onSlideEnd={this.onSliderChange} step={1} min={1} max={this.state.pages}/> 
    </div>
    <div className="p-d-inline-block page">{this.state.page} / {this.state.pages}</div>
    <div className='p-d-inline-block pagesize'>
      <Dropdown value={this.state.pagesize} options={pagesizes} onChange={this.onPagesizeChange} placeholder="Results" />
    </div>
</div>);
        }
        else {
            return (<div className='p-d-block pager'>
    <div className='p-d-inline-block links'>
      <Paginator pageLinkSize={this.state.pages} template="PageLinks" first={this.state.offset} totalRecords={this.state.count} rows={this.state.pagesize} onPageChange={this.onPageChange} />
    </div>
    <div className='p-d-inline-block pagesize fixed'>
      <Dropdown value={this.state.pagesize} options={pagesizes} onChange={this.onPagesizeChange} placeholder="Results" />
    </div>
</div>);
        }
    }

    renderResultTable(pager) {
        var evname=this.state.events.filter((e) => e.id == this.state.event_id).map((e)=> e.name);
        var comptitle=''+evname +' ' + this.state.competition.weapon_name + " " + this.state.competition.category_name;

        return (<div>
            <h4>{comptitle}</h4>
            <DataTable
            ref={this.dt}
            value={this.state.items}
            paginator={false}
            header={pager}
            footer={pager}
            className="p-datatable-striped"
            onRowDoubleClick={this.onEdit}
            sortMode="multiple" multiSortMeta={this.state.multiSortMeta} onSort={this.onSort}
        >
            <Column field="id" header="ID" sortable={true} />
            <Column field="place" header="Place" sortable={true} />
            <Column field="fencer_surname" header="Name" sortable={true} />
            <Column field="fencer_firstname" header="Firstname" sortable={true} />
            <Column field="country" header="Country" sortable={true} />
            <Column field="points" header="Points" sortable={true} />
            <Column field="total_points" header="Total" sortable={true} />
            <Column field="ranked" header="Rnk" />
        </DataTable>
        </div>);
    }

    selectComp = (event) => {
        this.setState({items:[], competition: event.data}, () => {
            this.loadItemPage() });
    }

    renderCompetitionTable(pager) {
        return (<DataTable
            ref={this.dt}
            value={this.state.competitions}
            className="p-datatable-striped"
            onRowDoubleClick={this.selectComp}            
        >
            <Column field="weapon_name" header="Weapon" sortable={false} />
            <Column field="category_name" header="Category" sortable={false} />
        </DataTable>);
    }

    renderTable(pager) {
        if(this.state.competition.id) {
            return this.renderResultTable(pager);
        }
        return this.renderCompetitionTable(pager);
    }
}
