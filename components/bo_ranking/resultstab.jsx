import { events, results, result, competitions } from "../api.js";
import { is_valid } from "../functions";
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
    "fencer_dob": "b",
    "place": "p",
    "country": "c",
    "points": "s",
    "total_points": "t"
};

export default class ResultsTab extends PagedTab {
    constructor(props, context) {
        super(props, context);
        this.state = {
            competition: {},
            events:[],
            loading: false,
            sorting:"i",
            sortField: "",
            sortOrder: 1,
            multiSortMeta: [ { field: "place", order: 1}],
            filter: "",
            items: [],
            competitions: [],
            loadedFor: -1,
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
        this.setState({'loading':true});
        events(0, 20000, '', "D", "with_competitions").then((evnts) => { if (evnts) {
            this.setState({'events': evnts.data.list, 'loading': false });
        }});
    }

    componentDidUpdate = () => {
        this.checkState();
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
            this.setState({loading:true});
            return results(o, p, f, s, this.state.competition.id).then((r) => {this.setState({loading:false}); return r;});
        }
        else if(is_valid(this.props.eventId)) {
            this.setState({loadedFor: this.props.eventId, loading:true});
            return competitions(this.props.eventId)
            .then((cmp) => {
                this.setState({loading:false});
                if(cmp) {
                    var cmps = cmp.data.list.map((itm,idx) => {
                        var k1="k" + itm.categoryId;
                        if(this.props.categories[k1]) {
                            itm.category_name = this.props.categories[k1].name;
                            itm.category_obj = this.props.categories[k1];
                        }
                        var k2="k" + itm.weaponId;
                        if(this.props.weapons[k2]) {
                            itm.weapon_name = this.props.weapons[k2].name;
                            itm.weapon_obj = this.props.weapons[k2];
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
        if (is_valid(this.props.eventId) && this.props.eventId != this.state.loadedFor) {
            this.loadItemPage();
        }
    }

    onChangeEl = (ev) => {
        var name = ev.target.name;
        var value = ev.target.value;
        if(name == "event") {
            this.setState({items:[], competition:{}, competitions:[]}, () => {
                this.props.onAction({event: "select", value: value, offset:0, page: 1}, () => this.checkState());
            });
        }
    }

    onImport = (tp, itm) => {
        if(tp==='open') {
            var evname=this.state.events.filter((e) => e.id == this.props.eventId).map((e)=> e.name);
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
        <ResultDialog countries={this.props.countries} onDelete={this.onDelete} onClose={this.onClose} onChange={this.onChange} onSave={this.onSave} onLoad={this.onLoad} display={this.props.displayDialog} value={this.state.item} />
        <ImportDialog 
            countries={this.props.countries} competition={this.state.competition} event={this.props.eventId} weapons={this.props.weapons}
            onClose={()=>this.onImport('close')} onChange={(itm)=>this.onImport('change',itm)} onSave={()=>this.onImport('save')} 
            value={this.state.importObject} display={this.state.importDialog}
            />
        </div>
        );
    }

    renderFilter() {
        return (
            <span className="p-input-icon-left search-input search-input-results">
                <Dropdown className='evntdrop' appendTo={document.body} name="event" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={this.props.eventId} options={this.state.events} placeholder="Event" />
            </span>
        );
    }

    renderAdd() {
        if(this.state.competition.id) {
            return (<span className="p-input-icon-left add-button">
                <i className="pi pi-file-import"></i><a onClick={()=>this.onImport('open')}>Import</a>
                <i className="pi pi-trash"></i><a onClick={()=>this.onImport('clear')}>Clear</a>
                <i className="pi pi-replay"></i><a onClick={()=>this.onImport('recalc')}>Recalculate</a>
                <i className="pi pi-caret-left"></i><a onClick={()=>this.setState({competition: {}, items: [1], offset:0, page: 1}) }>Back</a>
            </span>);
        }
    }

    renderPager() {
        if(!this.state.competition.id) {
            return (<div></div>);
        }

        let pagesizes=[5, 10, 20, 50];// {name: 5, code: 5},{name: 10, code: 10}, {name:20, code:20},{name: 50, code:50}];
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
    <div className='p-d-inline-block pagesize'>
      <Dropdown value={this.state.pagesize} options={pagesizes} onChange={this.onPagesizeChange} placeholder="Results" />
    </div>
</div>);
        }
    }

    renderResultTable(pager) {
        var evname=this.state.events.filter((e) => e.id == this.props.eventId).map((e)=> e.name);
        var comptitle=''+evname +' ' + this.state.competition.weapon_name + " " + this.state.competition.category_name;

        return (<div className='table-title'>
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
            <Column field="fencer_dob" header="DOB" sortable={true} />
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
        var evtitle=this.state.events.filter((e) => e.id == this.props.eventId).map((e)=> e.name);
        return (<div className='table-title'>
            <h4>{evtitle}</h4>
            <DataTable
            ref={this.dt}
            value={this.state.competitions}
            className="p-datatable-striped"
            onRowDoubleClick={this.selectComp}            
        >
            <Column field="weapon_name" header="Weapon" sortable={false} style={{width: '20%'}}/>
            <Column field="category_name" header="Category" sortable={false} style={{width: '20%'}}/>
            <Column field="total" header="Entries" sortable={false} />
        </DataTable></div>);
    }

    renderTable(pager) {
        if(this.state.competition.id) {
            return this.renderResultTable(pager);
        }
        return this.renderCompetitionTable(pager);
    }
}
