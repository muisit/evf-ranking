import React from 'react';
import { fencer, upload_file } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';
import DuplicateFencer from './duplicatefencer';
import { parse_net_error, get_yob, format_date, random_hash } from '../functions';

export default class FencerDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            old_status:-1,
            suggestiondialog: false,
            suggestions: null,
            pendingSave: null,
            imageHash: random_hash()
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
        // clear our duplicate check settings for the next new fencer
        this.setState({suggestionDialog: false, suggestions:null, pendingSave:null});
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    delete = (item) => {
        if(this.props.delete !== false) {
            if(this.props.onDelete) this.props.onDelete(item);
        }
        this.close();
    }

    onFileChange = (event) => {
        var selectedFile=event.target.files[0];
        upload_file("events",selectedFile,{
            fencer: this.props.value.id,
        })
        .then((json) => {
            var itm = Object.assign({}, this.props.value);
            if (json.data.model) {
                itm = Object.assign({}, itm, json.data.model);
            }
            console.log("saving fencer ",itm);
            if(this.props.onSave) this.props.onSave(itm);
            this.setState({imageHash: random_hash()});
        })
        .catch((err) => parse_net_error(err));
    }

    actualSave = (obj) => {
        fencer('save',obj)
            .then((json) => {
                this.loading(false);
                var itm=Object.assign({},this.props.value);
                if(json.data && json.data.id) {
                    itm.id = json.data.id;
                }
                if(json.data.model) {
                    itm = Object.assign({},itm,json.data.model);
                }
                this.save(itm);
            })
            .catch(parse_net_error);
    }

    onCloseDialog = (event) => {
        this.loading(true);

        // create a new object containing only the data we want to store
        var obj = {
            birthday: this.props.value.birthday,
            country: this.props.value.country,
            firstname: this.props.value.firstname,
            gender: this.props.value.gender,
            name: this.props.value.name,
            id: this.props.value.id,
            picture: this.props.value.picture
        };

        if(obj.gender != 'M' && obj.gender != 'F') {
            alert('Please select the proper gender');
            return;
        }
        var yob=get_yob(obj.birthday);
        var now=get_yob();
        if(now-yob < 10 || now-yob > 120) {
            alert('Please select a proper date of birth');
            return;
        }
        if(obj.name.length<2 || obj.firstname.length<2) {
            alert("Please set the surname and firstname");
            return;
        }

        fencer('presavecheck',obj)
            .then((json) => {
                if(json && json.data && json.data.suggestions && json.data.suggestions.length) {
                    this.setState({suggestiondialog: true, suggestions: json.data.suggestions, pendingSave: obj})
                }
                else {
                    this.actualSave(obj);
                }
            })
            .catch(parse_net_error);
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
        if(!event.target || (!event.target.value && !event.value)) return;
        var item=this.props.value;
        switch(event.target.name) {
        case 'firstname':
        case 'name':
        case 'country':
        case 'birthday':
        case 'gender':item[event.target.name] = event.target.value; break;
        case 'picture':
            var value=event.value;
            // allow changes from Y->A, Y->R, A->R, R->A
            var oldstate=this.props.value.picture;
            if(  (oldstate=='Y' && (value=='A' || value=='R'))
              || (oldstate == 'A' && value=='R')
              || (oldstate == 'R' && value=='A')) {
                item.picture=value;
            }
            break;
        }
        if (this.props.onChange) this.props.onChange(item);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete fencer '+ this.props.value.name + "? This action cannot be undone!")) {
            this.loading(true);
            fencer('delete',{ id: this.props.value.id})
            .then((json) => {
                this.loading(false);
                this.delete();
            })
            .catch((err) => parse_net_error(err, () => this.loading(false)));
        }
    }

    renderPicture () {
        // display the accreditation photo
        // anyone that can view this dialog can upload a better image
        var canapprove=this.props.value.picture != 'N';
        if(evfranking && evfranking.eventcap) {
            canapprove=["accreditor","organiser","system"].includes(evfranking.eventcap) && this.props.value.picture!='N';
        }

        var approvestates=[{
            name: "Newly uploaded",
            id: "Y"
        },{
            name: "Approved",
            id: "A"
        },{
            name: "Request replacement",
            id: "R"
        },{
            name: "None available",
            id: "N"
        }];
        var picstate = this.props.value.picture;
        if(!['Y','N','R','A'].includes(picstate)) {
            picstate='N';
        }
        return (<div className='col-12'>
            <label className='header'>Accreditation Photo</label>
            <div>
            {['Y','A','R'].includes(this.props.value.picture) && (
                <div className='accreditation'>
                  <img className='photoid' src={evfranking.url + "&picture="+this.props.value.id + "&nonce=" + evfranking.nonce + '&hash='+this.state.imageHash}></img>
                </div>
            )}
            <div className='textcenter'>
              <input type="file" onChange={this.onFileChange} />
            </div>
            {canapprove && (
                <div>
                  <Dropdown name={'picture'} appendTo={document.body} optionLabel="name" optionValue="id" value={picstate} options={approvestates} onChange={this.onChangeEl} />
                </div>
            )}
            </div>
        </div>);
    }    

    renderResults() {
        if(!this.props.value.results) return (null);
        return (<div className="col-12 ranking-results">
            <table className='list'>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Country</th>
                        <th>Competition</th>
                        <th>Place</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    {this.props.value.results.map((itm,idx) => (
                        <tr key={itm.id} className={(idx%2)==0 ? "even":"odd"}>
                            <td>{itm.event_name}</td>
                            <td>{format_date(itm.event_date)}</td>
                            <td>{itm.event_country_name}</td>
                            <td>{itm.weapon_abbr} {itm.category_name}</td>
                            <td>{itm.place}</td>
                            <td>{itm.total_points}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>);
    }

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);
        if(this.props.value && this.props.value.id >0 && this.props.delete !== false) {
            footer=(<div>
                <Button label="Remove" icon="pi pi-trash" className="p-button-danger p-button-raised p-button-text" onClick={this.onDeleteDialog} />
                <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
                <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);
        }
        let genders = [{ name: 'M', code: 'M' }, { name: 'W', code: 'F' }];

        var country = (
          <Dropdown name='country' appendTo={document.body} optionLabel="name" optionValue="id" value={this.props.value.country} options={this.props.countries} placeholder="Country" onChange={this.onChangeEl} />
        );
        if(this.props.country) {
            var cname = "";
            this.props.countries.map((c) => {
                if(c.id == this.props.country) {
                    cname=c.name;
                }
            });
            country = (<div>{cname}</div>);
        }

        return (<Dialog header="Edit Fencer" position="center" visible={this.props.display} className="fencer-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} >
    <div className="grid">
      <div className="col-12 md:col-6">
        <div className="p-inputgroup">
          <InputText name='name' className="p-inputtext-sm" value={this.props.value.name} placeholder="Surname" onChange={this.onChangeEl} />
        </div>
      </div>
      <div className="col-12 md:col-6">
        <div className="p-inputgroup">
          <InputText name='firstname' className="p-inputtext-sm" value={this.props.value.firstname} placeholder="Firstname" onChange={this.onChangeEl} />
        </div>
      </div>
      <div className="col-12 md:col-4">
        <div className="p-inputgroup">{country}</div>
      </div>
      <div className="col-12 md:col-4">
        <div className="p-inputgroup">
          <InputMask name='birthday' mask="9999-99-99" slotChar="yyyy-mm-dd" value={this.props.value.birthday} onChange={this.onChangeEl}/>
        </div>
      </div>
      <div className="col-12 md:col-4">
        <div className="p-inputgroup">
          <Dropdown name='gender' appendTo={document.body} optionLabel="name" optionValue="code" value={this.props.value.gender} options={genders} placeholder="Gender" onChange={this.onChangeEl}/>
        </div>
      </div>
      { this.renderResults() }
      {this.renderPicture()}
    </div>
    <DuplicateFencer display={this.state.suggestiondialog} suggestions={this.state.suggestions} pending={this.state.pendingSave} onSave={()=>this.actualSave(this.state.pendingSave)} onClose={()=>this.close()} />
</Dialog>
);
    }
}

