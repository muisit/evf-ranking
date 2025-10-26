import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';
import FencerDialog from '../../dialogs/fencerdialog';

export default class SuggestionDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        var countryById={};
        for(var i in this.props.countries) {
            var cnt=this.props.countries[i];
            var key="k"+cnt.id;
            countryById[key]=cnt;
        }

        this.state = {
            countryById: countryById,
            displayFencerDialog: false,
            item: {id:-1}
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCloseDialog = (event) => {
        if(this.props.onSave) this.props.onSave(this.props.value);
    }

    onCancelDialog = (event) => {
        this.close();
    }

    onNew = () => {
        this.setState({displayFencerDialog: true, item: {
            name: this.props.value.name,
            firstname: this.props.value.firstname,
            country_id: this.props.value.country_id,
            id: -1, // make a new entry
            birthday: this.props.value.birthday,
            gender: this.props.value.gender
        }});
    }

    onUpdate = () => {
      this.setState({displayFencerDialog: true, item: {
          name: this.props.value.name,
          firstname: this.props.value.firstname,
          country_id: this.props.value.country_id,
          id: this.props.value.fencer_id,
          birthday: this.props.value.birthday,
          gender: this.props.value.gender
      }});
  }

    onFencer = (tp,itm) => {
        if(tp == 'change') {
            this.setState({item: itm});
        }
        else if(tp == 'close') {
            this.setState({displayFencerDialog: false});
        }
        else if(tp == 'save') {
            console.log('selecting suggestion after save', itm);
            this.selectSuggestion(itm);
        }
    }

    onChangeEl = (event) => {
        if(!event.target || !event.target.value) return;
        var item=this.props.value;
        switch(event.target.name) {
        case 'name':
            var val = event.target.value.toUpperCase();
            item[event.target.name] = val; break;
        case 'firstname':
        case 'country_id':
        case 'birthday':
        case 'gender':item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    selectSuggestion = (itm) => {
        console.log('selecting suggestion', itm);
        var item = Object.assign({},this.props.value);
        // Do not! override the names, so we can add the new spelling to the accepted (mis)spellings
        //item.name = itm.name;
        //item.firstname = itm.firstname;
        item.country_id = itm.country_id;
        item.country = this.state.countryById["k" + itm.country_id].abbr;
        item.fencer_id = itm.id;
        item.birthday = itm.birthday;
        item.gender = itm.gender;

        item.lastname_check="ok";
        item.lastname_text='';
        item.firstname_check="ok";
        item.firstname_text="";
        item.country_check="ok";
        item.country_text="";
        item.all_check="ok";
        item.all_text="";

        if (this.props.onChange) this.props.onChange(item);
        //this.close();
    }

    render() {
        if(!this.props.value) {
            return (<div></div>);
        }
        var closebutton=(<Button label="Cancel" icon="pi pi-times" className="p-button-raised p-button-text" onClick={this.onCancelDialog} />);
        var savebutton=(null);
        
        var newbutton =(<Button label="Add" icon="pi pi-file-o" className="p-button-raised p-button-text" onClick={this.onNew} />);
        if(this.props.value.fencer_id > 0) {
          newbutton =(<Button label="Update" icon="pi pi-file-o" className="p-button-raised p-button-text" onClick={this.onUpdate} />);
          savebutton = (<Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />);
        }

        var footer=(<div>{newbutton}{closebutton}{savebutton}</div>);
        let genders = [{ name: 'Male', code: 'M' }, { name: 'Female', code: 'F' }];

        return (<Dialog header="Adjust Fencer Data" position="center" visible={this.props.display} style={{ width: '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          <div>
            <label>Last name</label>
            <div className='input'>
              <InputText name='lastname' value={this.props.value.name} placeholder='Name' readOnly={true}/>
            </div>
          </div>
          <div>
            <label>First name</label>
            <div className='input'>
              <InputText name='firstname' value={this.props.value.firstname} placeholder='Name'  readOnly={true}/>
            </div>
          </div>
          <div>
            <label>Country</label>
            <div className='input'>
            <Dropdown name='country_id' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country_id} options={this.props.countries} placeholder="Country"  readOnly={true}/>
            </div>
          </div>
          <div>
            <label>Birthday</label>
            <div className="input">
              <InputMask name='birthday' mask="9999-99-99" slotChar="yyyy-mm-dd" value={this.props.value.birthday} readOnly={true}/>
            </div>
          </div>
          <div>
            <label>Gender</label>
            <div className="input">
              <Dropdown name='gender' appendTo={document.body} optionLabel="name" optionValue="code" value={this.props.value.gender} options={genders} placeholder="Gender" readOnly={true}/>
            </div>
          </div>
          {this.props.value.suggestions.length > 0 && (
          <div>
          <label>Suggestions</label>
          <div className='input'>
            <table className="suggestiontable"><tbody>
                { this.renderSuggestions() }
            </tbody></table>
          </div>
        </div>

          )}
          <FencerDialog countries={this.props.countries} onClose={() => this.onFencer('close')} onChange={(itm) => this.onFencer('change',itm)} onSave={(itm) => this.onFencer('save',itm)} delete={false} display={this.state.displayFencerDialog} value={this.state.item} />
        </Dialog>);
    }

    renderSuggestions()
    {
      return this.props.value.suggestions.map((itm,idx) => this.renderSuggestion(itm, idx));
    }

    renderSuggestion(suggestion, idx) 
    {
      var lnameClass = (suggestion.checks.filter((c) => c.type === 'lastname').length > 0) ? 'nok' : 'ok';
      var fnameClass = (suggestion.checks.filter((c) => c.type === 'firstname').length > 0) ? 'nok' : 'ok';
      var countryClass = (suggestion.checks.filter((c) => c.type === 'country').length > 0) ? 'nok' : 'ok';
      var ageClass = (suggestion.checks.filter((c) => c.type === 'age').length > 0) ? 'nok' : 'ok';
      var fencer = suggestion.fencer;
      return (
        <tr key={idx} className={fencer.id == this.props.value.fencer_id ? "selected" : "unselected"}>
          <td className={lnameClass}>{fencer.name}</td>
          <td className={fnameClass}>{fencer.firstname}</td>
          <td className={countryClass}>{this.state.countryById["k"+fencer.country_id] && this.state.countryById["k"+fencer.country_id].abbr}</td>
          <td className={ageClass}>{fencer.birthday}</td>
          <td>{fencer.gender}</td>
          <td className='icon'>
            <a onClick={()=>this.selectSuggestion(fencer)}><i className="pi pi-replay"></i></a>
          </td>
        </tr>
      );
    }
}

