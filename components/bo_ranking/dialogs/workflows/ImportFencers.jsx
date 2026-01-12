import React from 'react';
import { Button } from 'primereact/button';
import { workflow, error_handler, result } from "../../../api.js";
import { is_valid } from '../../../functions.js';
import SuggestionDialog from '../suggestiondialog';

export default class ImportFencers extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            fencers: this.props.value.sandbox.fencers,
            checking: true,
            checked: false,
            item: null,
            showDialog: false
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }    

    setChecksBasedOnFirstSuggestion = (el, suggestion, countOfSuggestions) => {
        el.lastname_check = 'ok';
        el.firstname_check = 'ok';
        el.country_check = 'ok';
        el.dob_check = 'ok';
        el.all_check = countOfSuggestions > 1 ? 'nok' : 'ok';
        if (countOfSuggestions > 1) {
            el.all_text = 'Please pick a valid suggestion';
        }

        if (suggestion.checks && suggestion.checks.length) {
            for(var chk of suggestion.checks) {
                if (chk.type == 'lastname') {
                    el.lastname_check = 'nok';
                    el.lastname_text = chk.message;
                }
                if (chk.type == 'firstname') {
                    el.firstname_check = 'nok';
                    el.firstname_text = chk.message;
                }
                if (chk.type == 'country') {
                    el.country_check = 'nok';
                    el.country_text = chk.message;
                }
                if (chk.type == 'age') {
                    el.dob_check = 'nok';
                    el.dob_text = chk.message;
                }
            }
        }
        return el;
    }

    doCheck = () => {
        this.loading(true);
        result('check',{
            ranking: this.state.fencers,
            competition_id: this.props.value.sandbox.selectedCompetition
        })
        .then((res) => {
            this.loading(false);
            var allOk = true;
            // parse results
            var ranking=this.state.fencers.slice();
            if(res && res.data && res.data.length) {
                for(var i in res.data) {
                    var entry=res.data[i];

                    for(var j in ranking) {
                        var el=ranking[j];
                        if(el.index === entry.index) {
                            el.fencer_id = entry.fencer_id || -1;
                            if (!is_valid(el.fencer_id)) allOk = false;
                            el = this.setChecksBasedOnFirstSuggestion(el, entry.suggestions ? entry.suggestions[0] : {}, entry.suggestions?.length || 0);

                            el.suggestions = [];
                            if(entry.suggestions && entry.suggestions.length) {
                                el.suggestions = entry.suggestions.slice();
                            }
                            ranking[j]=el;
                        }
                    }
                }
                this.setState({'fencers':ranking, checking: false, checked: allOk});
            }
        })        
        .catch(error_handler);
    }

    doImport = () => {
        var ranking=[];
        var allHaveAnId = true;
        for(var i in this.state.fencers) {
            var rnk=this.state.fencers[i];
            var obj={pos: rnk.pos, fencer_id: rnk.fencer_id, firstname: rnk.firstname, name: rnk.name, status: rnk.status};
            // a fencer that dropped out, but still has a ranking, will retain that ranking as if they lost
            // Although we know they abandoned or forfeited, it does not influence their status
            // However, fencers that abandoned in the poule rounds and do not have a valid ranking are still
            // taken into account for ranking points, but at the bottom.
            if (rnk.pos < 9999 && rnk.status == 'dnf') {
                obj.status = 'normal';
            }
            ranking.push(obj);

            if (!obj.fencer_id || obj.fencer_id < 1) {
                allHaveAnId = false;
            }
        }

        if (!allHaveAnId) {
            // should not occur, but okay...
            alert("Not all entries have been assigned a new or existing record. Please adjust the lines with a non-green indicator");
            this.setState({'checked': false});
            return;
        }

        this.loading(true);
        result('import',{competition_id: this.props.value.sandbox.selectedCompetition, import: { ranking: ranking}})
            .then((res) => {
                this.loading(false);
                if(res) {
                    this.nextStep();
                }
                else {
                    throw("Error with return value");
                }
            })
            .catch((err) => {
                if (err.response && err.response.data && err.response.data.error) {
                    err = err.response.data.error.join('');
                    alert("Import error:\r\n" + err);
                }
                else {
                    alert("Network error encountered: " + err);
                }
            });
    }

    nextStep = () => {
        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'imported_competition'
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    } 

    onSuggest = (tp, itm) => {
        if(tp === 'close') {
            this.setState({showDialog:false,item:null});
        }
        if(tp === 'save') {
            var allOk = true;
            // change the value of the item at this index itm.index
            var ranking=this.state.fencers.map((rnk,idx) => {
                if(rnk.index == this.state.item.index) {
                    if (!is_valid(this.state.item.fencer_id)) allOk = false;
                    return this.state.item;
                }
                if (!is_valid(rnk.fencer_id)) allOk = false;
                return rnk;
            });
            this.setState({showDialog:false,item:null, fencers: ranking, checked: allOk});
        }
        if(tp === 'change') {
            this.setState({item:itm});
        }
    }

    selectRow = (itm) => {
        this.setState({showDialog: true, item: itm});
    }

    excludeFencer = (itm, state) => {
        var ranking=this.state.fencers.map((rnk) => {
            if(rnk.index == itm.index && state != 'T') {
                switch (state) {
                    case 'X': itm.status = 'exclude'; break;
                    case 'D': itm.status = 'dnf'; break;
                    case 'N': itm.status = 'normal'; break;
                }
                return itm;
            }
            return rnk;
        });
        if (state == 'T') {
            ranking = ranking.filter((i) => i.index != itm.index);
        }
        var allOk = true;
        ranking.forEach((itm) => {if (!is_valid(itm.fencer_id)) allOk = false; });
        this.setState({fencers: ranking, checked: allOk});
    }

    render() {
        let button = null;
        if (this.state.checking) {
            button = (<Button label="Check" icon="pi pi-hourglass" className="p-button-primary p-button-raised p-button-text" onClick={this.doCheck} />);
        }
        else if (this.state.checked) {
            button = (<Button label="Import" icon="pi pi-cloud-upload" className="p-button-primary p-button-raised p-button-text" onClick={this.doImport} />);
        }
        const weapon = this.props.data.weaponsById['w' + this.props.value.sandbox.weapon].name;
        const category = this.props.data.categoriesById['c' + this.props.value.sandbox.category].name;

        return (
      <div>
        <div className='title'>Results {weapon} {category}</div>
        <table className="resultranking">
            <thead>
                <tr>
                    <th>Pos</th>
                    <th>Lastname</th>
                    <th>Firstname</th>
                    <th>Country</th>
                    <th>D.o.B.</th>
                    <th>#</th>
                    <th>ID</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            {this.state.fencers.map((itm,idx) => (
                <tr key={idx} onDoubleClick={() => this.selectRow(itm)}>
                <td className="ok">
                    {itm.pos >0 && itm.pos < 9999 && itm.status != 'exclude' && (<span>{itm.pos}</span>)}
                    {itm.status == 'dnf' && itm.pos >= 9999 && (<span>DNF</span>)}
                    {itm.status == 'exclude' && (<span>EX</span>)}
                </td>
                <td className={itm.lastname_check}>
                    <span className='item'>{itm.name}</span>
                    {itm.lastname_text !== '' && (
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.lastname_text} />)}
                </td>
                <td className={itm.firstname_check}>
                    {itm.firstname}                            
                    {itm.firstname_text !== '' && (
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.firstname_text} />)}
                    </td>
                <td className={itm.country_check}>
                    {itm.country}
                    {itm.country_text !== '' && (
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.country_text} />)}
                </td>
                <td className={itm.dob_check}>
                    {itm.birthday}
                    {itm.dob_text !== '' && (
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.dob_text} />)}
                </td>
                <td className={itm.all_check}>
                    {itm.all} ({itm.suggestions?.length})
                    {itm.all_text !== '' && (
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.all_text} />)}
                </td>
                <td className={itm.all_check}>
                    {is_valid(itm.fencer_id) && itm.fencer_id}
                    {!is_valid(itm.fencer_id) && '-'}
                </td>
                <td className='und'>
                    {itm.status != 'exclude' && (<Button onClick={() => this.excludeFencer(itm, 'X')} icon="pi pi-times-circle" className="p-button-sm p-button-text right" tooltip="exclude this fencer" />)}
                    {itm.status == 'exclude' && (<i className='pi iconspacer right'></i>)}
                    {itm.status != 'dnf' && itm.pos < 9999 && (<Button onClick={() => this.excludeFencer(itm, 'D')} icon="pi pi-pause-circle" className="p-button-sm p-button-text right" tooltip="mark as did not finish" />)}
                    {(itm.status == 'dnf' || itm.pos >= 9999) && (<i className='pi iconspacer right'></i>)}
                    {itm.status != 'normal' && itm.pos < 9999 && (<Button onClick={() => this.excludeFencer(itm, 'N')} icon="pi pi-check-circle" className="p-button-sm p-button-text right" tooltip="reinstate" />)}
                    {(itm.status == 'normal' || itm.pos >= 9999) && (<i className='pi iconspacer right'></i>)}
                    <Button icon="pi pi-minus-circle" onClick={() => this.excludeFencer(itm, 'T')} className="p-button-sm p-button-text right" tooltip="remove from list" />
                </td>
                </tr>
                ))
            }
            </tbody>
        </table>
        <div className='alignright'>
            {button}
        </div>
        <SuggestionDialog 
            countries={this.props.data.countries} gender={this.props.value.sandbox.gender}
            onClose={()=>this.onSuggest('close')} onChange={(itm)=>this.onSuggest('change',itm)} onSave={()=>this.onSuggest('save')} 
            value={this.state.item} display={this.state.showDialog}
            />
      </div>
        );
    }
}

