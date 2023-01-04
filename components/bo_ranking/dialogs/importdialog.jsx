import React from 'react';
import { country, result } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputTextarea } from 'primereact/inputtextarea';
import { InputNumber } from 'primereact/inputnumber';
import { Tooltip } from 'primereact/tooltip';
import SuggestionDialog from './suggestiondialog';

export default class ImportDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            loading: false,
            current_check: 0,
            showDialog:false
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
            for(var i in this.props.value.object.ranking) {
                var rnk=this.props.value.object.ranking[i];
                var obj={pos: rnk.pos, fencer_id: rnk.fencer_id};
                ranking.push(obj);
            }

            result('import',{import: { ranking: ranking, competition_id: this.props.value.object.competition_id }})
                .then((res) => {
                    if(res) {
                        this.close();
                    }
                    else {
                        throw("Error with return value");
                    }
                })
                .catch((err) => {
                    alert("Import error encountered: ",err);
                });
        }
    }

    onCancelDialog = (event) => {
        this.close();
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
            result('importcheck',{
                ranking: checkme,
                competition: this.props.value.object.competition_id
            })
            .then((res) => {
                // parse results
                var ranking=this.props.value.object.ranking.slice();
                if(res && res.data.ranking) {
                    for(var i in res.data.ranking) {
                        var entry=res.data.ranking[i];

                        for(var j in ranking) {
                            var el=ranking[j];
                            if(el.index === entry.index) {
                                if(entry.fencer_id > 0) {
                                    for(var k in entry.suggestions) {
                                        var sugg = entry.suggestions[k];
                                        if(sugg.id == entry.fencer_id) {
                                            entry.birthday = sugg.birthday;
                                            entry.gender = sugg.gender;
                                        }
                                    }
                                }

                                el.fencer_id = entry.fencer_id || -1;
                                el.lastname_check = entry.lastname_check || 'nok';
                                el.lastname_text = entry.lastname_text || '';
                                el.firstname_check = entry.firstname_check || 'nok';
                                el.firstname_text = entry.firstname_text || '';
                                el.country_check = entry.country_check || 'nok';
                                el.country_text = entry.country_text || '';
                                el.all_check = entry.all_check || 'nok';
                                el.all_text = entry.all_text || '';
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
        //console.log("spliting ",line);
        var fields=line.split('\t');
        if(fields.length >= 4) {
            //console.log("line is tab separated");
            return fields;
        }

        // if we have fewer fields, assume tab is not the separation character
        // try regular whitespace (result from copying)
        fields = line.split(/\s+/);
        if(fields.length == 4) {
            //console.log("line seems space separated");
            return fields;
        }

        // if we have more fields, it could be that we have a name containing a space.
        // Retry with at least 2 spaces between the fields
        if(fields.length>4) {
            //console.log("too many fields, splitting on double spaces");
            fields = line.split(/\s\s+/);
            if(fields.length==4) {
                //console.log("line seems double-space separated");
                //console.log("returning ",fields);
                return fields;
            }
        }

        // if we have too few fields, see if we need comma separation
        //console.log("trying comma separation");
        var fields2=line.split(',');
        if(fields2.length>3) {
            //console.log("line seems comma separated");
            //console.log("returning ",fields2);
            return fields2;
        }

        if(fields.length <4) {
            // too few fields, but whitespace separation is not the way forward...
            // fall back to tab space separation, unless that yields only 1 line
            fields2=line.split('\t');
            if(fields2.length>1) fields=fields2;
        }

        if(fields.length <4) {
            //console.log("too few fields, testing first field ",fields[0]);
            // perhaps the first field contains <pos><space><name> with only one
            // space between position and name
            var flds1=fields[0].split(/\s/,2);
            if(flds1.length==2 && !isNaN(parseInt(flds1[0]))) {
                //console.log("first field seems to contain position and a name");
                fields.shift();
                fields = [flds1[0],flds1[1],...fields];
                //console.log("fields is now ",fields);
            }
        }

        if(fields.length < 4) {
            //console.log("too few fields, testing last field of ",fields[fields.length-1]);
            // we expect the country to be the non-spaced last element
            var flds2=fields[fields.length-1].split(/\s/);
            if(flds2.length>1) {
                //console.log("last field seems to contain two fields at least ",flds2);
                fields.pop();
                var country=flds2.pop();
                fields=fields.concat([flds2.join(' '),country]);
            }
        }
        //console.log("returning ",fields);
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
                lastname: lastname,
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
            this.setState({item:itm});
        }
    }

    selectRow = (itm) => {
        this.printRanking();
        this.setState({showDialog: true, item: itm});
    }

    renderImport () {
        return (
            <div>
                <table className="resultranking">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Lastname</th>
                            <th>Firstname</th>
                            <th>Country</th>
                            <th></th>
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
                            <span className='item'>{itm.lastname}</span>
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

