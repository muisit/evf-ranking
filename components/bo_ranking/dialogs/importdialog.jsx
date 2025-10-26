import React from 'react';
import { result } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { InputTextarea } from 'primereact/inputtextarea';
import SuggestionDialog from './suggestiondialog';

export default class ImportDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            loading: false,
            current_check: 0,
            showDialog:false,
            includeInRanking: true
        };

        this.countryByAbbrev={};
        if(this.props.countries) {
            for(var i in this.props.countries) {
                var cnt=this.props.countries[i];
                this.countryByAbbrev[cnt.abbr.toLowerCase()]=cnt;
            }
        }

        this.gender = 'M';
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    delete = (item) => {
        if(this.props.onDelete) this.props.onDelete(item);
        this.close();
    }

    printRanking2 = (rnk) => {
        var ranking=[];
        for(var i in rnk) {
            var rnk=rnk[i];
            var obj={pos: rnk.pos, fencer_id: rnk.fencer_id};
            ranking.push(obj);
        }
    }

    printRanking = () => {
        var ranking=[];
        for(var i in this.props.value.object.ranking) {
            var rnk=this.props.value.object.ranking[i];
            var obj={pos: rnk.pos, fencer_id: rnk.fencer_id};
            ranking.push(obj);
        }
    }

    onCloseDialog = (event) => {
        if(this.props.value.object.ranking.length) {
            var ranking=[];
            var allHaveAnId = true;
            for(var i in this.props.value.object.ranking) {
                var rnk=this.props.value.object.ranking[i];
                var obj={pos: rnk.pos, fencer_id: rnk.fencer_id, firstname: rnk.firstname, name: rnk.name};
                ranking.push(obj);

                if (!obj.fencer_id || obj.fencer_id < 1) {
                    allHaveAnId = false;
                }
            }

            if (!allHaveAnId) {
                alert("Not all entries have been assigned a new or existing record. Please adjust the lines with a non-green indicator");
                return;
            }

            result('import',{competition_id: this.props.value.object.competition_id, import: { ranking: ranking}})
                .then((res) => {
                    if(res) {
                        this.close();
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

            //var eventValue = {
            //    id: this.props.event,
            //    in_ranking: this.state.includeInRanking ? 'Y' : 'N'
            //};
            //singleevent('save',eventValue)
            //    .catch(parse_net_error);
        }
    }

    onCancelDialog = (event) => {
        this.close();
    }

    setChecksBasedOnFirstSuggestion = (el, suggestion, countOfSuggestions) => {
        el.lastname_check = 'ok';
        el.firstname_check = 'ok';
        el.country_check = 'ok';
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
                    el.all_check = 'nok';
                    el.all_text = chk.message;
                }
            }
        }
        return el;
    }

    checkResults = () => {
        // check the next 10 results
        var start=this.state.current_check;
        if(this.props.value.object.ranking.length < start) {
            return false;
        }
        var checkme=[];
        for(var i=start; i<this.props.value.object.ranking.length && i< (start+10);i++) {
            checkme.push(this.props.value.object.ranking[i]);
        }

        this.setState({current_check: start+10},() => {
            result('check',{
                ranking: checkme,
                competition_id: this.props.value.object.competition_id
            })
            .then((res) => {
                // parse results
                var ranking=this.props.value.object.ranking.slice();
                if(res && res.data && res.data.length) {
                    for(var i in res.data) {
                        var entry=res.data[i];

                        for(var j in ranking) {
                            var el=ranking[j];
                            if(el.index === entry.index) {
                                el.fencer_id = entry.fencer_id || -1;
                                el = this.setChecksBasedOnFirstSuggestion(el, entry.suggestions ? entry.suggestions[0] : {}, entry.suggestions?.length || 0);

                                el.gender = entry.gender || this.gender;
                                el.birthday = entry.birthday || '';

                                el.suggestions = [];
                                if(entry.suggestions && entry.suggestions.length) {
                                    el.suggestions = entry.suggestions.slice();
                                }
                                ranking[j]=el;
                            }
                        }
                    }

                    var item=this.props.value;
                    item.object.ranking=ranking;
                    if (this.props.onChange) this.props.onChange(item);
                    setTimeout(this.checkResults, 100);
                }
            })
        });
    }

    splitLine = (line) => {
        // we expect a tab separated document
        var fields=line.split('\t');
        if(fields.length >= 4) {
            return fields;
        }

        // if we have fewer fields, assume tab is not the separation character
        // try regular whitespace (result from copying)
        fields = line.split(/\s+/);
        if(fields.length == 4) {
            return fields;
        }

        // if we have more fields, it could be that we have a name containing a space.
        // Retry with at least 2 spaces between the fields
        if(fields.length>4) {
            fields = line.split(/\s\s+/);
            if(fields.length==4) {
                return fields;
            }
        }

        // if we have too few fields, see if we need comma separation
        var fields2=line.split(',');
        if(fields2.length>3) {
            return fields2;
        }

        if(fields.length <4) {
            // too few fields, but whitespace separation is not the way forward...
            // fall back to tab space separation, unless that yields only 1 line
            fields2=line.split('\t');
            if(fields2.length>1) fields=fields2;
        }

        if(fields.length <4) {
            // perhaps the first field contains <pos><space><name> with only one
            // space between position and name
            var flds1=fields[0].split(/\s/,2);
            if(flds1.length==2 && !isNaN(parseInt(flds1[0]))) {
                fields.shift();
                fields = [flds1[0],flds1[1],...fields];
            }
        }

        if(fields.length < 4) {
            // we expect the country to be the non-spaced last element
            var flds2=fields[fields.length-1].split(/\s/);
            if(flds2.length>1) {
                fields.pop();
                var country=flds2.pop();
                fields=fields.concat([flds2.join(' '),country]);
            }
        }
        return fields;
    }

    onConvert = () => {
        // set the default gender based on the selected competition
        if(this.props.weapons && this.props.competition) {
            for(var j in this.props.weapons) {
                var wpn=this.props.weapons[j];
                if(wpn.id == this.props.competition.weapon) {
                    this.gender = wpn.gender;
                }
            }
        }

        var lines=this.props.value.text.split('\n');
        var lastpos=0;
        var ranking = lines.reduce((result, line) => {
            var elements=this.splitLine(line);
            var pos = parseInt(elements[0]);
            var lastname = elements.length > 1 ? elements[1].trim() : '';
            var firstname = elements.length > 2 ? elements[2].trim() : '';
            var countryname = elements.length > 3 ? elements[3].trim() : '';

            var obj = {
                index:result.length,
                pos: pos,
                pos_check: isNaN(pos) ? "nok":"ok",
                pos_text: isNaN(pos) ? 'invalid number':'',
                name: lastname,
                lastname_check: "und",
                lastname_text: '',
                firstname: firstname,
                firstname_check: "und",
                firstname_text: '',
                country: countryname,
                country_check: "und",
                country_text: '',
                all_check:'und',
                all: '',
                all_text: '',
                fencer_id: -1,
                country_id: -1,
                suggestions: []
            }

            if(! (isNaN(pos) && lastname.length==0 && firstname.length==0 && countryname.length==0)) {

                if(pos < lastpos) {
                    obj.pos_check='nok';
                    obj.pos_text='Position does not follow previous value';
                }

                if(this.countryByAbbrev[obj.country.toLowerCase()]) {
                    obj.country = this.countryByAbbrev[obj.country.toLowerCase()].abbr;
                    obj.country_check="ok";
                    obj.country_id=this.countryByAbbrev[obj.country.toLowerCase()].id;
                }
                else {
                    obj.country_check="nok";
                }
                result.push(obj);
            }
            return result;
        },[]);

        var item=this.props.value;
        item.object.ranking=ranking;
        item.converted=true;
        if (this.props.onChange) this.props.onChange(item);
        this.setState({current_check: 0}, () => {
            setTimeout(this.checkResults, 1);
        });
    }

    onChange = (event) => {
        var item=this.props.value;
        switch(event.target.name) {
        case 'text': 
            item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    setIncludeInRanking = (val) => {
        this.setState({includeInRanking: val !== false});
    }

    onSuggest = (tp,itm) => {
        if(tp === 'close') {
            this.setState({showDialog:false,item:null});
        }
        if(tp === 'save') {
            this.setState({showDialog:false,item:null});
            // change the value of the item at this index itm.index
            var ranking=this.props.value.object.ranking.map((rnk,idx) => {
                if(rnk.index == this.state.item.index) {
                    return this.state.item;
                }
                return rnk;
            });
            var item=this.props.value;
            item.object.ranking=ranking;
            if (this.props.onChange) this.props.onChange(item);
        }
        if(tp === 'change') {
            console.log('changing state of selected item for suggestion dialog to ', itm);
            this.setState({item:itm});
        }
    }

    selectRow = (itm) => {
        this.printRanking();
        this.setState({showDialog: true, item: itm});
    }

    renderImport () {
        //    <div>
        //        <ToggleButton onLabel='Include in ranking' offLabel='Do NOT include in ranking' onIcon="pi pi-check" offIcon="pi pi-times" checked={this.state.includeInRanking} onChange={(e) => this.setIncludeInRanking(e.value)} />
        //    </div>
        return (
            <div>
                <table className="resultranking">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Lastname</th>
                            <th>Firstname</th>
                            <th>Country</th>
                            <th>#</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        {this.props.value.object.ranking.map((itm,idx) => (
                      <tr key={idx} onDoubleClick={() => this.selectRow(itm)}>
                        <td className={itm.pos_check}>
                            {itm.pos}
                            {itm.pos_text !== '' && (
                            <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.pos_text} />)}
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
                        <td className={itm.all_check}>
                            {itm.all} ({itm.suggestions.length})
                            {itm.all_text !== '' && (
                            <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={itm.all_text} />)}
                        </td>
                        <td className={itm.all_check}>
                            {itm.fencer_id > 0 && itm.fencer_id}
                        </td>
                      </tr>
                        ))}
                    </tbody>
                </table>
                <SuggestionDialog 
            countries={this.props.countries} gender={this.gender}
            onClose={()=>this.onSuggest('close')} onChange={(itm)=>this.onSuggest('change',itm)} onSave={()=>this.onSuggest('save')} 
            value={this.state.item} display={this.state.showDialog}
            />
            </div>
        );
    }

    renderConvert () {
        return (
        <div>
            <label>Paste text</label>
            <div className='input'>
                <InputTextarea name='text' className="p-inputtext-sm" value={this.props.value.text} rows={40} placeholder="Results" onChange={this.onChange} />
            </div>
        </div>
        );
    }

    render() {
        var closebutton=(<Button label="Close" icon="pi pi-times" className="p-button-raised p-button-text" onClick={this.onCancelDialog} />);
        var importbutton=(<Button label="Import" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />);
        var convertbutton=(<Button label="Convert" icon="pi pi-trash" className="p-button-raised p-button-text" onClick={this.onConvert} />);

        var footer=(<div>
        {this.props.value.converted && importbutton}
        {!this.props.value.converted && convertbutton}
        {closebutton}
</div>);
        var title="Import Results for " + this.props.value.title;

        var content = this.props.value.converted ? this.renderImport() : this.renderConvert();
        return (<Dialog header={title} position="center" visible={this.props.display} style={{ width: '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
            {content}
        </Dialog>);
    }
}

