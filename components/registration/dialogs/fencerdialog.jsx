import React from 'react';
import { fencers, fencer, upload_file } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { InputMask } from 'primereact/inputmask';
import { parse_net_error, get_yob, is_valid, random_hash, is_hod, is_hod_view } from '../../functions';
import { adjustFencerData } from "../../lib/registrations.js";
import { emptyFencerRegistration } from "../../lib/EmptyFencerRegistration.js";
import { getCountryFromIndex } from '../../lib/GetCountryFromIndex.js';
import { FencerList } from '../elements/fencerlist';


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
        this.setState({suggestionDialog: false, suggestions:null, pendingSave:null, imageHash: random_hash()});
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
        if (this.fencerDataIsComplete()) {
            if (!is_valid(this.props.fencer.id)) {
                this.actualSave(this.props.fencer)
                    .then((fencer) => {
                        if (this.props.onChange) this.props.onChange(fencer, true);
                        this.onFileChange(event);
                    });
            }
            else {
                var selectedFile=event.target.files[0];
                upload_file("events",selectedFile,{
                    fencer: this.props.fencer.id,
                    event: this.props.basic.event ? this.props.basic.event.id : -1})
                .then((json) => {
                    var itm = Object.assign({}, this.props.fencer);
                    if (json.data.model) {
                        itm = Object.assign({}, itm, json.data.model);
                    }
                    this.setState({imageHash: random_hash()});
                    if (this.props.onChange) this.props.onChange(itm, true);
                })
                .catch((err) => parse_net_error(err));        
            }
        }
    }

    onFencerSelect = (fencer) => {
        if (this.props.onChange) {
            this.props.onChange(fencer, true);
        }
    }

    fencerDataIsComplete = () => {
        if (  (this.props.fencer.name && this.props.fencer.name.length > 1)
           && (this.props.fencer.firstname && this.props.fencer.firstname.length > 0)
           && (this.props.fencer.gender && ['M','F'].includes(this.props.fencer.gender))
           && is_valid(this.props.fencer.country)
        ) {
            return true;
        }
        return false;
    }

    actualSave = (obj) => {
        return fencer('save',obj)
            .then((json) => {
                this.loading(false);
                var itm=Object.assign({},this.props.fencer);
                if(json.data && json.data.id) {
                    itm.id = json.data.id;
                }
                if(json.data.model) {
                    itm = Object.assign({},itm,json.data.model);
                }
                return itm;
            })
            .catch(parse_net_error);
    }

    onCloseDialog = (event) => {
        this.loading(true);

        // create a new object containing only the data we want to store
        var obj = {
            birthday: this.props.fencer.birthday,
            country: this.props.fencer.country,
            firstname: this.props.fencer.firstname,
            gender: this.props.fencer.gender,
            name: this.props.fencer.name,
            id: this.props.fencer.id,
            picture: this.props.fencer.picture,
            event: this.props.basic.event ? this.props.basic.event.id : -1,
        };

        if(obj.gender != 'M' && obj.gender != 'F') {
            alert('Please select the proper gender');
            return;
        }
        if (obj.birthday && obj.birthday.length > 0) {
            var yob=get_yob(obj.birthday);
            var now=get_yob();
            if(now-yob < 10 || now-yob > 120) {
                alert('Please select a proper date of birth');
                return;
            }
        }

        if(obj.name.length<2 || obj.firstname.length<2) {
            alert("Please set the surname and firstname");
            return;
        }
        this.actualSave(obj)
            .then((fencer) => {
                this.save(fencer);
            })
            .catch(parse_net_error);
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeEl = (event) => {
        if(!event.target) return;
        if (is_hod_view()) return;

        var item=this.props.fencer;
        switch(event.target.name) {
        case 'firstname':
        case 'name':
        case 'country':
        case 'birthday':
        case 'gender':
            item[event.target.name] = event.target.value; 
            break;
        case 'picture':
            var value=event.value;
            // allow changes from Y->A, Y->R, A->R, R->A
            var oldstate=this.props.fencer.picture;
            if(  (oldstate=='Y' && (value=='A' || value=='R'))
              || (oldstate == 'A' && value=='R')
              || (oldstate == 'R' && value=='A')) {
                item.picture=value;
            }
            break;
        }
        if (this.props.onChange) this.props.onChange(item, true);
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete fencer '+ this.props.fencer.name + "? This action cannot be undone!")) {
            this.loading(true);
            fencer('delete',{ id: this.props.fencer.id})
            .then((json) => {
                this.loading(false);
                this.delete();
            })
            .catch((err) => {
                if(err.response.data.messages && err.response.data.messages.length) {
                    var txt="";
                    for(var i=0;i<err.response.data.messages.length;i++) {
                        txt+=err.response.data.messages[i]+"\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error removing the data. Please try again');
                }
            })
    
        }
    }

    clearSearch = () => {
        this.setState({suggestions: []});
        var item=this.props.fencer;
        item.name = '';
        if (this.props.onChange) this.props.onChange(item, false);
    }

    autocomplete = (evt) => {
        var item=this.props.fencer;
        item.name = evt.target.value;
        if (this.props.onChange) this.props.onChange(item, !this.props.allowSearch);

        if (this.props.allowSearch) {
            var thistarget=evt.target.value;
            if(thistarget.length > 1) {
                var filters = { name: evt.target.value};
                if(this.props.country && is_valid(this.props.country)) {
                    filters.country=this.props.country;
                }
                fencers(0, 10000, filters, "nf")
                    .then((json) => {
                        if(this.props.fencer && this.props.fencer.name == thistarget) {
                            var fencers=[];
                            json.data.list.map((itm)=> {
                                itm = adjustFencerData(itm, this.props.basic.event);
                                fencers.push(itm);
                            });
                            this.setState({suggestions: this.addNewFencerToSuggestionList(fencers) });
                        }
                    });
            }
            else {
                this.setState({ suggestions: this.addNewFencerToSuggestionList([]) });
            }
        }
    }

    addNewFencerToSuggestionList = (lst) => {
        var newFencer = emptyFencerRegistration(this.props.fencer.name, getCountryFromIndex(this.props.country, this.props.basic.countriesById));
        lst.unshift(newFencer);
        return lst;
    }

    renderPicture () {
        // display the accreditation photo
        // anyone that can view this dialog can upload a better image
        var canapprove=this.props.fencer.picture != 'N';
        if(evfranking && evfranking.eventcap) {
            canapprove=["accreditor","organiser","system"].includes(evfranking.eventcap) && this.props.fencer.picture!='N';
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
        }];
        var picstate = this.props.fencer.picture;
        if(!['Y','N','R','A'].includes(picstate)) {
            picstate='N';
        }
        if (picstate == 'N') {
            approvestates=[{
                name: 'None available',
                id: 'N'    
            }];
        }
        var eventid=this.props.basic.event ? this.props.basic.event.id : -1;
        var uploadDisabled = !this.fencerDataIsComplete();
        return (<div className="grid">
            <div className='col-12'>
                <label className='header'>Accreditation Photo</label>
                <div>
                {['Y','A','R'].includes(this.props.fencer.picture) && (
                    <div className='accreditation'>
                    <img className='photoid' src={evfranking.url + "&picture="+this.props.fencer.id + "&nonce=" + evfranking.nonce + "&event=" + eventid + '&hash='+this.state.imageHash}></img>
                    </div>
                )}
                <div className='textcenter'>
                    <input type="file" onChange={this.onFileChange} disabled={uploadDisabled}/>
                </div>
                {canapprove && (
                    <div className='approval-dropdown'>
                    <Dropdown name={'picture'} appendTo={document.body} optionLabel="name" optionValue="id" value={picstate} options={approvestates} onChange={this.onChangeEl} disabled={picstate == 'N'}/>
                    </div>
                )}
                </div>
            </div>
        </div>);
    }    

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog header="Add Registration" position="center" visible={this.props.display} className="fencer-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} baseZIndex={1500}>
    <div className="grid">
      <div className="col-12 col-md-6">
        <div className="p-inputgroup">
          <InputText name='name' autoFocus className="p-inputtext-sm" value={this.props.fencer.name} placeholder="Surname" onChange={(e) => this.autocomplete(e)} />
          {this.props.allowSearch && <i className="pi pi-times-circle clear-search" onClick={(e)=>this.clearSearch()}/>}
        </div>
      </div>
      <div className="col-12 col-md-6">
        <div className="p-inputgroup">
          {!this.props.allowSearch && (<InputText name='firstname' className="p-inputtext-sm" value={this.props.fencer.firstname} placeholder="Firstname" onChange={this.onChangeEl} />)}
        </div>
      </div>
    </div>
    {this.props.allowSearch && this.renderSuggestions()}
    {!this.props.allowSearch && this.renderAdditional()}
    {!this.props.allowSearch && this.renderPicture()}
</Dialog>
);
    }

    renderSuggestions() {
        if (!this.state.suggestions || this.state.suggestions.length == 0) return (null);
        return (
            <div className="grid">
                <div className="col-12">
                    <FencerList basic={this.props.basic} country={this.props.country} fencers={this.state.suggestions} onSelect={this.onFencerSelect} />
                </div>
        </div>
        );

    }

    renderAdditional() {
        let genders = [{ name: 'M', code: 'M' }, { name: 'W', code: 'F' }];

        var country = (
          <Dropdown name='country' optionLabel="name" optionValue="id" value={this.props.fencer.country} options={this.props.basic.countries} placeholder="Country" onChange={this.onChangeEl}  style={{color: 'red', zIndex: 20000}}/>
        );
        if(is_hod() || is_hod_view()) {
            var cname = "Organisation";
            this.props.countries.map((c) => {
                if(c.id == this.props.country) {
                    cname=c.name;
                }
            });
            country = (<div>{cname}</div>);
        }

        var birthday = this.props.fencer.birthday;
        if (!birthday || birthday == 'unknown' || birthday == '') birthday = null;
        return (
            <div className="grid">
                <div className="col-12 col-md-4">
                    <div className="p-inputgroup">{country}</div>
                </div>
                <div className="col-12 col-md-4">
                    <div className="p-inputgroup">
                        <div className='d-flex flex-column'>
                            <InputMask name='birthday' mask="9999-99-99" slotChar="yyyy-mm-dd" value={birthday} onChange={this.onChangeEl}/>
                            <div className='smallprint'>(date of birth is only required for athletes)</div>
                        </div>
                    </div>
                </div>
                <div className="col-12 col-md-4">
                    <div className="p-inputgroup">
                        <Dropdown name='gender' optionLabel="name" optionValue="code" value={this.props.fencer.gender} options={genders} placeholder="Gender" onChange={this.onChangeEl} style={{zIndex: 20000}}/>
                    </div>
                </div>
            </div>
        );
    }
}

