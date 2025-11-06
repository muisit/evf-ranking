import React from 'react';
import DetailDialog from './dialogs/detaildialog';
import { ranking, weapons, categories } from "../api.js";

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
            orderBy: 'r',
            items: [],
            itemTries: -1,
            itemSelect:null,
            scrollPosition:0
        };
    }

    componentDidMount = () => {
        this.loadWeapons(0);
        this.loadCats(0);
    }

    loadWeapons = (tries) => {
        weapons().then((wpns) => { 
            if (wpns) this.setState({ 'weapons': wpns.data.list, weapon_id: wpns.data.list[0].id }, this.checkState) 
        })
        .catch((e) => {
            if(tries < 5) {
                this.loadWeapons(tries+1);
            }
            else {
                alert("There seems to be a network problem and the page is unable to retrieve the information. Please try reloading the whole page, or try at a later time. If the problem persists, please contact webmaster@veteransfencing.eu");
            }
        });
    }

    loadCats = (tries) => {
        categories().then((cats) => { if (cats) {
            var lst = cats.data.list.filter((cat) => {
                    if(cat.type === 'I' && parseInt(cat.value) < 5) {
                        return true;
                    }
                    return false;
                });
                this.setState({ 'categories': lst, category_id: lst[0].id }, this.checkState); 
            }
        })
        .catch((e) => {
            if(tries < 5) {
                this.loadCats(tries+1);
            }
            else {
                alert("There seems to be a network problem and the page is unable to retrieve the information. Please try reloading the whole page, or try at a later time. If the problem persists, please contact webmaster@veteransfencing.eu");
            }
        });
    }

    loadItemPage = (select, tries) => {
        // check if we did not reselect
        // do not keep retrying (tries>0) if our selection (select) does not match the latest selection
        if(tries > 0 && this.state.itemSelect !== null && this.state.itemSelect != select) return; 

        if(tries == 0 && this.state.itemTries > 0) tries=this.state.itemTries;
        this.setState({itemTries: tries, itemSelect: select});
        ranking("list", {category_id: this.state.category_id, weapon_id: this.state.weapon_id})
            .then((res) => {
                if(res && res.data && res.data.results) {
                    this.setState({items: res.data.results, itemTries: 0});
                }
            })
            .catch((e) => {
                if(this.state.itemTries < 5) {
                    this.loadItemPage(select, this.state.itemTries+1);
                }
                else {
                    alert("There seems to be a network problem and the page is unable to retrieve the information. Please try reloading the whole page, or try at a later time. If the problem persists, please contact webmaster@veteransfencing.eu");
                }
            });
    }

    checkState = () => {
        if (this.state.weapon_id > 0 && this.state.category_id > 0) {
            this.loadItemPage(this.state.weapon_id + "-" + this.state.category_id,0);
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
        this.setState({fencer_id:fencer, scrollPosition: window.scrollY});
        ranking("detail",{category_id: this.state.category_id, weapon_id: this.state.weapon_id, id: fencer})
            .then((res) => {
                if(res.data) {
                    this.setState({detail: res.data, detail_open: true});
                }
            }).catch((e) => console.log(e));
    }

    onList = () => {
        this.setState({fencer_id: null, detail: []});
    }

    onClose = () => {
        this.setState({detail_open: false}, () => { window.scroll(0, this.state.scrollPosition - 105)});
    }

    changeSort = (s) => {
        if(s=='r') {
            if(this.state.orderBy=='r') {
                this.setState({orderBy:'R'});
            }
            else {
                this.setState({orderBy:'r'});
            }
        }
        else if(s=='n') {
            if(this.state.orderBy=='n') {
                this.setState({orderBy:'N'});
            }
            else {
                this.setState({orderBy:'n'});
            }
        }
        else if(s=='c') {
            if(this.state.orderBy=='c') {
                this.setState({orderBy:'C'});
            }
            else {
                this.setState({orderBy:'c'});
            }
        }
    }

    render() {
        if(this.state.weapons && this.state.weapons.length && this.state.categories && this.state.categories.length) {
            var possortactive=(this.state.orderBy=='r' || this.state.orderBy=='R') ? ' active': '';
            var namesortactive=(this.state.orderBy=='n' || this.state.orderBy=='N') ? ' active': '';
            var cntsortactive=(this.state.orderBy=='c' || this.state.orderBy=='C') ? ' active': '';
            possortactive='pi pi-icon ' + (this.state.orderBy=='R' ? 'pi-sort-alpha-up' : 'pi-sort-alpha-down') + possortactive;
            namesortactive='pi pi-icon ' + (this.state.orderBy=='N' ? 'pi-sort-alpha-up' : 'pi-sort-alpha-down') + namesortactive;
            cntsortactive='pi pi-icon ' + (this.state.orderBy=='C' ? 'pi-sort-alpha-up' : 'pi-sort-alpha-down') + cntsortactive;

            var fencers=this.state.items.slice();
            var orderby=this.state.orderBy;
            fencers.sort(function(fn1,fn2) {
                if(orderby=='r' || orderby=='R') {
                    if(fn1.pos < fn2.pos) return orderby=='r' ? -1 : 1;
                    if(fn1.pos > fn2.pos) return orderby=='r' ? 1: -1;
                }
                else if(orderby=='c' || orderby=='C') {
                    var c=fn1.country.localeCompare(fn2.country);
                    if(c!==0) {
                        return orderby=='c'? c : -1*c;
                    }
                }
                var r=fn1.name.localeCompare(fn2.name);
                if(r===0) r=fn1.firstname.localeCompare(fn2.firstname);
                if(r===0) {
                    if(fn1.id < fn2.id) return (orderby=='r' || orderby=='n') ? -1 : 1;
                    return (orderby=='r' || orderby=='n') ? 1 : -1;
                }
                return (orderby=='r' || orderby=='n') ? r : -1*r;
            });

            var downloadUrl = evfranking.url + '&nonce=' + evfranking.nonce + '&download=ranking';

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
                      <div className='col-5 col-md-3'>
                        <select className='drop catdrop' name='category' value={this.state.category_id} onChange={this.onChangeEl}>
                            {this.state.categories && this.state.categories.map((cat) => {
                                return (<option key={cat.id} value={cat.id}>{cat.name}</option>);
                            })}
                        </select>
                      </div>
                      { evfranking.capabilities && evfranking.capabilities.download && (<div className='col'>
                        <a href={ downloadUrl } class='pi pi-icon pi-cloud-download'></a>
                      </div>)}
                    </div>
                    <div className='row'>
                        <div className='col-12'>
                        <table className='list'>
                          <thead>
                            <tr>
                              <th scope='col' onClick={(e)=>this.changeSort('r')} className='pos'>
                                <label>Pos.</label>
                                <i className={possortactive}>&nbsp;</i>
                              </th>
                              <th scope='col' onClick={(e)=>this.changeSort('n')}>
                                <label>Name</label>
                                <i className={namesortactive}></i>
                              </th>
                              <th scope='col'><label>First name</label></th>
                              <th scope='col' onClick={(e)=>this.changeSort('c')}>
                                <label>Country</label>
                                <i className={cntsortactive}></i>
                                </th>
                              <th scope='col'><label>Points</label></th>
                              <th scope='col'></th>
                            </tr>
                          </thead>
                          <tbody>
                          {fencers && fencers.length>0 && fencers.map((fencer,idx) => (
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
                              <tr><td colSpan='6' style={{text_align: 'center'}}>
                                {(this.state.itemTries<1) && (<span>...</span>)}
                                {(this.state.itemTries>0) && (<span>
                                    ... retrying ({this.state.itemTries})
                                </span>)}
                              </td></tr>
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
            return ('... please wait while loading ...');
        }
    }
}
