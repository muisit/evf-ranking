import React from 'react';
import DetailDialog from './dialogs/detaildialog';
import { ranking, weapons, categories } from "./api.js";

export default class RankingPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            weapons: [],
            categories: [],
            fencer_id: -1,
            detail_open: false,
            detail: [],
            category_id: -1,
            weapon_id: -1,
            items: []
        };
    }

    componentDidMount = () => {
        weapons().then((wpns) => { if (wpns) this.setState({ 'weapons': wpns.data.list, weapon_id: wpns.data.list[0].id }, this.checkState) });
        categories().then((cats) => { if (cats) {
                var lst = cats.data.list.filter((cat) => {
                    if(cat.type === 'I' && parseInt(cat.value) < 5) {
                        return true;
                    }
                    return false;
                });
                this.setState({ 'categories': lst, category_id: lst[0].id }, this.checkState); 
            }
        });
    }

    loadItemPage = () => {
        ranking("list", {category_id: this.state.category_id, weapon_id: this.state.weapon_id})
            .then((res) => {
                if(res && res.data && res.data.results) {
                    this.setState({items: res.data.results});
                }
            });
    }

    checkState = () => {
        if (this.state.weapon_id > 0 && this.state.category_id > 0) {
            this.loadItemPage();
        }
    }

    onChangeEl = (ev) => {
        var name = ev.target.name;
        var value = ev.target.value;
        if(name == "category") {
            this.setState({category_id: value, items:[]}, this.checkState);
        }
        if(name == "weapon") {
            this.setState({weapon_id: value, items:[]}, this.checkState);
        }
    }

    onDetail = (fencer) => {
        this.setState({fencer_id:fencer});
        ranking("detail",{category_id: this.state.category_id, weapon_id: this.state.weapon_id, id: fencer})
            .then((res) => {
                if(res.data) {
                    this.setState({detail: res.data, detail_open: true});
                }
            });
    }

    onList = () => {
        this.setState({fencer_id: null, detail: []});
    }

    onClose = () => {
        this.setState({detail_open: false});
    }

    render() {
        if(this.state.weapons && this.state.weapons.length && this.state.categories && this.state.categories.length) {
            return (
                <div className='container ranking-results front-ranking'>
                    <div className='row'>
                      <div className='col-6 col-md-2'>Weapon :</div>
                      <div className='col-6 col-md-3'>
                        <select className='drop wpndrop' name='weapon' value={this.state.weapon_id} onChange={this.onChangeEl}>
                            {this.state.weapons && this.state.weapons.map((wpn) => {
                                return (<option key={wpn.id} value={wpn.id}>{wpn.name}</option>);
                            })}
                        </select>
                      </div>
                      <div className='col-6 col-md-2 col-md-offset-1'>Category :</div>
                      <div className='col-6 col-md-3'>
                        <select className='drop catdrop' name='category' value={this.state.category_id} onChange={this.onChangeEl}>
                            {this.state.categories && this.state.categories.map((cat) => {
                                return (<option key={cat.id} value={cat.id}>{cat.name}</option>);
                            })}
                        </select>
                      </div>
                    </div>
                    <div className='row'>
                        <div className='col-12'>
                        <table className='list'>
                          <thead>
                            <tr>
                              <th scope='col'>Pos.</th>
                              <th scope='col'>Name</th>
                              <th scope='col'>First name</th>
                              <th scope='col'>Country</th>
                              <th scope='col'>Points</th>
                              <th scope='col'></th>
                            </tr>
                          </thead>
                          <tbody>
                          {this.state.items && this.state.items.length>0 && this.state.items.map((fencer,idx) => (
                            <tr key={fencer.id} className={(idx%2)==1 ? "odd":"even"}>
                              <td className='pos'>{fencer.pos}</td>
                              <td>{fencer.name}</td>
                              <td>{fencer.firstname}</td>
                              <td>{fencer.country}</td>
                              <td>{fencer.points}</td>
                              <td className='icon'>
                                <span className="p-input-icon-left view-detail">
                                <a href='#' onClick={() => this.onDetail(fencer.id)}>
                                  <i className="pi pi-search-plus"></i>
                                </a>
                                </span>
                              </td>
                            </tr>
                          ))}
                          {(!this.state.items || this.state.items.length==0) && (
                              <tr><td colSpan='6' style={{text_align: 'center'}}>...</td></tr>
                          )}
                          </tbody>
                        </table>
                        </div>
                    </div>
                    <DetailDialog display={this.state.detail_open} detail={this.state.detail} onClose={this.onClose} />
                </div>
            );
        }
        else {
            return ('...');
        }
    }
}
