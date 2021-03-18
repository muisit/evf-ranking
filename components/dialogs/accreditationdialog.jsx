import React from 'react';
import { registration, fencer, upload_file } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { format_date_fe_short, date_to_category_num, date_to_category, jsonOutput } from "../functions";

export default class AccreditationDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            imageHash: Date.now()
        };
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onChange) this.props.onChange(item);
    }

    saveFencer = (item) => {
        fencer('save', {
            id: item.id,
            picture: item.picture
        })
            .then((json) => {
                var itm = Object.assign({}, this.props.value);
                if (json.data.model) {
                    itm = Object.assign({}, itm, json.data.model);
                }
                this.save(itm);
            })
            .catch ((err) => {
                if (err.response.data.messages && err.response.data.messages.length) {
                    var txt = "";
                    for (var i = 0; i < err.response.data.messages.length; i++) {
                        txt += err.response.data.messages[i] + "\r\n";
                    }
                    alert(txt);
                }
                else {
                    alert('Error storing the data. Please try again');
                }
            });
    }

    onFileChange = (event) => {
        var selectedFile=event.target.files[0];
        upload_file("events",selectedFile,{
            fencer: this.props.value.id,
            event: this.props.event.id})
        .then((json) => {
            var itm = Object.assign({}, this.props.value);
            if (json.data.model) {
                itm = Object.assign({}, itm, json.data.model);
            }
            console.log("saving item ",itm);
            this.save(itm);
            this.setState({imageHash: Date.now()});
        })
        .catch((err) => {
            if (err.response.data.messages && err.response.data.messages.length) {
                var txt = "";
                for (var i = 0; i < err.response.data.messages.length; i++) {
                    txt += err.response.data.messages[i] + "\r\n";
                }
                alert(txt);
            }
            else {
                alert('Error storing the data. Please try again');
            }
        });
    }

    onCloseDialog = (event) => {
        // registration selection is done as the checkboxes are marked
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChangeFencer = (event) => {
        if (!event.target) return;
        var name = event.target.name;
        var value = event.value;
        switch (name) {
        case 'picture':
            // allow changes from Y->A, Y->R, A->R, R->A
            var oldstate=this.props.value.picture;
            if(  (oldstate=='Y' && (value=='A' || value=='R'))
              || (oldstate == 'A' && value=='R')
              || (oldstate == 'R' && value=='A')) {
                this.props.value.picture=value;
                this.saveFencer(this.props.value);
                this.save(this.props.value);
            }
            break;
        }
    }

    render() {
        if(!this.props.value) {
            return (null);
        }

        var canapprove = ["accreditor", "organiser"].includes(evfranking.eventcap) && this.props.value.picture != 'N';
        var approvestates = [{
            name: "Newly uploaded",
            id: "Y"
        }, {
            name: "Approved",
            id: "A"
        }, {
            name: "Request replacement",
            id: "R"
        }, {
            name: "None available",
            id: "N"
        }];
        var picstate = this.props.value.picture;
        if (!['Y', 'N', 'R', 'A'].includes(picstate)) {
            picstate = 'N';
        }

        var footer=(<div>
        <Button label="Close" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog header="Approve Picture" position="center" visible={this.props.display} className="accreditation-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
    <h5>{ this.props.value.name }, {this.props.value.firstname }</h5>
    <h5>Birthyear: { this.props.value.birthyear } Gender: {this.props.value.gender == 'M' ? 'Man': 'Woman'}</h5>
    <div className='container'>
        <div className='row'>
            <div className='col-2'>
                <a onClick={() => this.props.goTo('prev')}><span className='pi pi-angle-double-left'></span>&nbsp;Previous</a>
            </div>
            <div className='col-8 textcenter'>
            {canapprove && (
                  <Dropdown name={'picture'} appendTo={document.body} optionLabel="name" optionValue="id" value={picstate} options={approvestates} onChange={this.onChangeFencer} />
            )}
            </div>
            <div className='col-2'>
                <a onClick={() => this.props.goTo('next')}>Next&nbsp;<span className='pi pi-angle-double-right'></span></a>
            </div>
        </div>
        {this.renderPicture()}
    </div>
</Dialog>
);
    }

    renderPicture () {
        console.log("rendering picture for ",this.props.value);
        // display the accreditation photo
        // anyone that can view this dialog can upload a better image
        var canapprove=["accreditor","organiser"].includes(evfranking.eventcap) && this.props.value.picture!='N';
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
        return (<div className='row'>
            <div className='col-10 offset-1'>
            {['Y','A','R'].includes(this.props.value.picture) && (
                <div className='accreditation'>
                  <img src={evfranking.url + "&picture="+this.props.value.id + "&nonce=" + evfranking.nonce + "&event=" + this.props.event.id + '&hash='+this.state.imageHash}></img>
                </div>
            )}
            <div className='textcenter'>
              <input type="file" onChange={this.onFileChange} />
            </div>
            </div>
        </div>);
    }
}

