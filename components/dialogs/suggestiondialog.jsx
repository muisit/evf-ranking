import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';


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
            countryById: countryById
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
        console.log('item selected, updating values');
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

        var footer=(<div>{closebutton}{savebutton}</div>);
        let genders = [{ name: 'Male', code: 'M' }, { name: 'Female', code: 'F' }];

        return (<Dialog header="Adjust Fencer Data" position="center" visible={this.props.display} style={{ width: '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          <div>
            <label>Last name</label>
            <div className='input'>
              <InputText name='lastname' value={this.props.value.lastname} onChange={this.onChangeEl} placeholder='Name'/>
            </div>
          </div>
          <div>
            <label>First name</label>
            <div className='input'>
              <InputText name='firstname' value={this.props.value.firstname} onChange={this.onChangeEl} placeholder='Name'/>
            </div>
          </div>
          <div>
            <label>Country</label>
            <div className='input'>
            <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country_id} options={this.props.countries} placeholder="Country" onChange={this.onChangeEl}/>
            </div>
          </div>
          <div>
            <label>Date of Birth</label>
            <div className='input'>
            <InputMask name='birthday' mask="9999-99-99" slotChar="yyyy-mm-dd" value={this.props.value.birthday} onChange={this.onChangeEl}/>
            </div>
          </div>
          <div>
            <label>Gender</label>
            <div className='input'>
            <Dropdown name='gender' appendTo={document.body} optionLabel="name" optionValue="code" value={this.props.value.gender} options={genders} placeholder="Gender" onChange={this.onChangeEl}/>
            </div>
          </div>
          {this.props.value.suggestions.length > 0 && (
          <div>
          <label>Suggestions</label>
          <div className='input'>
            <table><tbody>
                {this.props.value.suggestions.map((itm,idx) => (
              <tr key={idx}>
                <td>{itm.name}</td>
                <td>{itm.firstname}</td>
                <td>{this.state.countryById["k"+itm.country] && this.state.countryById["k"+itm.country].abbr}</td>
                <td>{itm.birthday}</td>
                <td>{itm.gender}</td>
                <td>
                  <a onClick={()=>this.selectSuggestion(itm)}><i className="pi pi-replay"></i></a>
                </td>
              </tr>
                ))}
            </tbody></table>
          </div>
        </div>

          )}
          {(!this.props.value.suggestions || this.props.value.suggestions.length == 0) && (
              <div>
              <label>Suggestions</label>
              <div className='input'>
                <i>No suggestions found. Either adjust the names in the original data, or add the missing information to create a new fencer entry.</i>
              </div>
            </div>
          )}
        </Dialog>);
    }
}

