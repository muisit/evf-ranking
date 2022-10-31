import React from 'react';

import ResultDetailDialog from './dialogs/resultdetaildialog';
import { events, weapons, categories, results, competitions } from "./api.js";

export default class ResultsPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            event_id: 0,
            category_id: 0,
            weapon_id:0,
            events:[],
            categories:[],
            weapons:[],
            filter: "",
            items: [],
            item:null,
            competitions: [],
            competition: null,
            results: [],
            displayDialog: false
        };
    }
    componentDidMount = () => {
        events(0, 0, '', "D", "with_results").then((evnts) => { if (evnts) {
            this.setState({'events': evnts.data.list });
        }});
        weapons().then((wpns) => { if (wpns) this.setState({ 'weapons': wpns.data.list }) });
        categories().then((cats) => { if (cats) this.setState({ 'categories': cats.data.list }) });
    }

    idToCat = (id) => {
        if(this.state.categories) {
            for(var i in this.state.categories) {
                if(this.state.categories[i].id == id) {
                    return this.state.categories[i];
                }
            }
        }
        return {'name':'unknown',id:-1};
    }

    idToWpn = (id) => {
        if(this.state.weapons) {
            for(var i in this.state.weapons) {
                if(this.state.weapons[i].id == id) {
                    return this.state.weapons[i];
                }
            }
        }
        return {'name':'unknown',id:-1};
    }

    expand = (itm,doexpand) => {
        if(doexpand) {
            competitions(itm.id)
                .then((cmp) => {
                    if(cmp) {
                        this.setState({item: itm, competitions: cmp.data.list });                    
                    }
                });
        }
        else {
            this.setState({item: null, competitions: [] });
        }
        return false;
    }

    onClose = () => {
        this.setState({displayDialog: false});
    }

    onDetail = (itm) => {
        var special = JSON.stringify({ competition_id: itm.id });
        // sort by place, then name, then country
        results(0, 10000, '', 'pnc', special)
            .then((res) => {
                this.setState({displayDialog:true, competition:itm, results: res.data.list});
            });
    }

    render() {
        if(!this.state.events || this.state.events.length == 0) {
            return (<div>... please wait while loading data ...</div>);
        }
        return (
            <div className='container ranking-results front-ranking'>
                <table className="list">
                   <thead>
                      <tr>
                        <th scope='col'>Name</th>
                        <th scope='col'>Place</th>
                        <th scope='col'>Year</th>
                        <th scope='col'></th>
                      </tr>
                    </thead>
                    <tbody>
                      {this.state.events && this.state.events.length>0 && this.state.events.map((itm,idx) => (
                        <tr key={itm.id} className={(idx%2)==1 ? "odd":"even"}>
                            <td className='event'>
                                {itm.name}
                                {this.state.item && this.state.item.id == itm.id 
                                && this.state.competitions && this.state.competitions.length>0 
                                && this.state.competitions.map((cmp) => (
                                    <div className="competition" key={cmp.id}>
                                        <div className="wpn">{this.idToWpn(cmp.weapon).name}</div>
                                        <div className="cat">{this.idToCat(cmp.category).name}</div>
                                        <div className="opens">{cmp.opens}</div>
                                        <div className="action">
                                          <span className="p-input-icon-left view-detail">
                                            <a href='#' onClick={() => this.onDetail(cmp)}>
                                              <i className="pi pi-search-plus"></i>
                                            </a>
                                           </span>
                                        </div>
                                    </div>
                                ))}
                            </td>
                            <td>{itm.location}</td>
                            <td>{itm.year}</td>
                            <td>
                              {this.state.item && this.state.item.id == itm.id 
                                && this.state.competitions && this.state.competitions.length>0 
                                && (
                              <div className="p-input-icon-left table-action">
                                <i onClick={()=>this.expand(itm,false)} className="pi pi-angle-up"><a>&nbsp;</a></i>
                              </div>)}
                              {(!this.state.item || this.state.item.id !== itm.id 
                                || !this.state.competitions || this.state.competitions.length==0) 
                                && (
                              <div className="p-input-icon-left table-action">
                                <i onClick={()=>this.expand(itm,true)} className="pi pi-angle-down"><a>&nbsp;</a></i>
                              </div>)}
                            </td>
                        </tr>
                      ))}
                    </tbody>
                </table>
                <ResultDetailDialog display={this.state.displayDialog} onClose={this.onClose} value={this.state.competition} results={this.state.results}/>
            </div>
        );
    }
}
