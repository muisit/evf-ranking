import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';
import FencerDialog from './fencerdialog';

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
            name: this.props.value.lastname,
            firstname: this.props.value.firstname,
            country: this.props.value.country_id,
            id: -1, // make a new entry
            birthday: this.props.value.birthday,
            gender: this.props.value.gender
        }});
    }

    onUpdate = () => {
      this.setState({displayFencerDialog: true, item: {
          name: this.props.value.lastname,
          firstname: this.props.value.firstname,
          country: this.props.value.country_id,
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
            console.log("saving fencer ",itm);
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
        case 'country':
        case 'birthday':
        case 'gender':item[event.target.name] = event.target.value; break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    selectSuggestion = (itm) => {
        console.log('item selected, updating values', itm);
        var item=Object.assign({},this.props.value);
        item.lastname = itm.name;
        item.firstname = itm.firstname;
        item.country_id=itm.country;
        item.country = this.state.countryById["k"+itm.country].abbr;
        item.fencer_id = itm.id;
        item.birthday = itm.birthday;
        item.gender=itm.gender;

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
        var savebutton=(<Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />);
        var newbutton =(<Button label="Add" icon="pi pi-file-o" className="p-button-raised p-button-text" onClick={this.onNew} />);
        var updatebutton="";
        if(this.props.value.fencer_id > 0) {
          updatebutton =(<Button label="Update" icon="pi pi-file-o" className="p-button-raised p-button-text" onClick={this.onUpdate} />);
        }

        var footer=(<div>{newbutton}{updatebutton}{closebutton}{savebutton}</div>);
        let genders = [{ name: 'Male', code: 'M' }, { name: 'Female', code: 'F' }];

        return (<Dialog header="Adjust Fencer Data" position="center" visible={this.props.display} style={{ width: '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          <div>
            <label>Last name</label>
            <div className='input'>
              <InputText name='lastname' value={this.props.value.lastname} placeholder='Name' readOnly={true}/>
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
            <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country_id} options={this.props.countries} placeholder="Country"  readOnly={true}/>
            </div>
          </div>
          {this.props.value.suggestions.length > 0 && (
          <div>
          <label>Suggestions</label>
          <div className='input'>
            <table className="suggestiontable"><tbody>
                {this.props.value.suggestions.map((itm,idx) => (
              <tr key={idx} className={itm.id == this.props.value.fencer_id && "selected"}>
                <td>{itm.name}</td>
                <td>{itm.firstname}</td>
                <td>{this.state.countryById["k"+itm.country] && this.state.countryById["k"+itm.country].abbr}</td>
                <td>{itm.birthday}</td>
                <td>{itm.gender}</td>
                <td className='icon'>
                  <a onClick={()=>this.selectSuggestion(itm)}><i className="pi pi-replay"></i></a>
                </td>
              </tr>
                ))}
            </tbody></table>
          </div>
        </div>

          )}
          <FencerDialog countries={this.props.countries} onClose={() => this.onFencer('close')} onChange={(itm) => this.onFencer('change',itm)} onSave={(itm) => this.onFencer('save',itm)} delete={false} display={this.state.displayFencerDialog} value={this.state.item} />
        </Dialog>);
    }
}

