import React from 'react';
import { ranking, weapons,categories } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';

export default class PerusalDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            weapons: [],
            categories: [],
            fencer_id: '',
            detail: [],
            category_id: -1,
            weapon_id: -1,
            items: []
        }
    }

    componentDidMount = () => {
        weapons().then((wpns) => { if (wpns) this.setState({ 'weapons': wpns.data.list, weapon_id: wpns.data.list[0].id }, this.checkState) });
        categories().then((cats) => { if (cats) {
                var lst = cats.data.list.filter((cat) => {
                    if(cat.type === 'I') {
                        return true;
                    }
                    return false;
                });
                this.setState({ 'categories': lst, category_id: lst[0].id }, this.checkState); 
            }
        });
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCloseDialog = (event) => {
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
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
            this.setState({category_id: value}, this.checkState);
        }
        if(name == "weapon") {
            this.setState({weapon_id: value}, this.checkState);
        }
    }

    onDetail = (fencer) => {
        this.setState({fencer_id:fencer});
        if (fencer != null) {
            ranking("detail",{category_id: this.state.category_id, weapon_id: this.state.weapon_id, id: fencer})
                .then((res) => {
                    if(res.data) {
                        this.setState({detail: res.data});
                    }
                });
        }
    }

    renderDetail = () => {
        return (
              <table className='detail'>
                <thead>
                    <tr>
                        <th scope='col'>#</th>
                        <th scope='col'>Event</th>
                        <th scope='col'>Year</th>
                        <th scope='col'>Location</th>
                        <th scope='col'>Weapon</th>
                        <th scope='col'>Entry</th>
                        <th scope='col'>Place</th>
                        <th scope='col'>Points</th>
                        <th scope='col'>DE</th>
                        <th scope='col'>Podium</th>
                        <th scope='col'>Factor</th>
                        <th scope='col'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {this.state.detail.map((comp,idx) => (
                  <tr key={idx} className={(comp.included == 'Y') ? "counting": (comp.included == 'E' ? "excluded" : "notcounting")}>
                      <td className='pos'>{idx+1}</td>
                      <td>{comp.event}</td>
                      <td>{comp.year}</td>
                      <td>{comp.location}</td>
                      <td>{comp.weapon}</td>
                      <td className='pos'>{comp.entry}</td>
                      <td className='pos'>{comp.place}</td>
                      <td className='pos'>{comp.points}</td>
                      <td className='pos'>{comp.de}</td>
                      <td className='pos'>{comp.podium}</td>
                      <td className='pos'>{comp.factor}</td>
                      <td className='pos'>{comp.total}</td>
                  </tr>
                    ))}
                </tbody>
              </table>
        );
    }

    renderEntry = (fencer, idx) => {
        return (<tbody key={fencer.id}>
          <tr className={(idx%2)==1 ? "odd":"even"}>
            <td className='pos'>{fencer.pos}</td>
            <td>{fencer.name}</td>
            <td>{fencer.firstname}</td>
            <td>{fencer.country}</td>
            <td>{fencer.points}</td>
            <td className='icon'>
                <span className="p-input-icon-left view-detail">
                {this.state.fencer_id == fencer.id && (<a href='#' onClick={() => this.onDetail(null)}>
                    <i className="pi pi-search-minus"></i>
                </a>)}
                {(this.state.fencer_id == null || this.state.fencer_id != fencer.id) && (<a href='#' onClick={() => this.onDetail(fencer.id)}>
                    <i className="pi pi-search-plus"></i>
                </a>)}
                </span>
            </td>
          </tr>
          {(this.state.fencer_id != null && this.state.fencer_id == fencer.id) && (
            <tr>
                <td colSpan='6'>
                    {this.renderDetail()}
                </td>
            </tr>
          )}
        </tbody>);
    }

    renderTable = () => {
        return (
        <div>
          <div className='ranking-select-catwpn'>
            <Dropdown className='drop catdrop' appendTo={document.body} name="category" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={this.state.category_id} options={this.state.categories} placeholder="Category" />
            <Dropdown className='drop wpndrop' appendTo={document.body} name="weapon" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={this.state.weapon_id} options={this.state.weapons} placeholder="Weapon" />
          </div>
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
            {this.state.items.map((fencer,idx) => this.renderEntry(fencer, idx))}
          </table>
        </div>
        );
    }

    render() {
            var footer=(<div>
            <Button label="Ok" icon="pi pi-times" className="p-button-raised p-button-text" onClick={this.onCancelDialog} />
    </div>);
            return (<Dialog header="Scan Rankings" position="center" visible={this.props.display} style={{ width: '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          <div className='ranking-dialog'>
              {this.renderTable()}
          </div>
        </Dialog>);
    }
}

