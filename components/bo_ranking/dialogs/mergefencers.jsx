import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { InputNumber } from 'primereact/inputnumber';
import { is_valid, parse_net_error } from '../../functions';
import { fencer } from '../../api';

export default class MergeFencers extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            mergeid1:null,
            mergeid2:null,
            fencer1: null,
            fencer2: null
        };
    }

    onCancelDialog = () => {
        this.setState({mergeid1:null, mergeid2:null, fencer1:null, fencer2:null});
        if(this.props.onClose) this.props.onClose();
    }

    onSaveDialog = () => {
        fencer('merge',{id1: this.state.mergeid1, id2: this.state.mergeid2})
            .then((json) => {
                if(json.data.messages) {
                    var txt='';
                    json.data.messages.map((t) => {
                        txt=txt+t+'\r\n';
                    });
                    alert(txt);
                }
                this.setState({mergeid1:null, mergeid2:null, fencer1:null, fencer2:null});
                if(this.props.onClose) this.props.onClose();
            })
            .catch(parse_net_error);
    }

    getFencer = (id,cb) => {
        if(!is_valid(id)) {
            cb(null);
            return;
        }

        fencer('view',{id:id, merge: true })
          .then((json)=> {
            if(json.data.item) {
                var obj=json.data.item;
                this.props.countries.map((cnt) => {
                    if(obj.country == cnt.id) {
                        obj.country_name=cnt.name;
                    }
                });
                cb(obj);
            }
            else {
                alert("No fencer found with ID "+id);
            }
          })
          .catch(parse_net_error);
    }

    change = (name,value) => {
        switch(name) {
        case 'mergeid1':
            this.setState({mergeid1: value});
            window.setTimeout(() => {
                if (value == this.state.mergeid1) {
                    this.getFencer(value, (dt)=> {
                        if (this.state.mergeid1 == value) {
                            this.setState({fencer1: dt});
                        }
                    });
                }
            }, 500);
            break;
        case 'mergeid2':
            this.setState({mergeid2: value});
            window.setTimeout(() => {
                if (value == this.state.mergeid2) {
                    this.getFencer(value, (dt)=> {
                        if (this.state.mergeid2 == value) {
                            this.setState({fencer2: dt});
                        }
                    });
                }
            }, 500);
            break;
        }
    }

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Merge" icon="pi pi-check" className="p-button-raised" onClick={this.onSaveDialog} />
</div>);

        if(!is_valid(this.state.mergeid1) || !is_valid(this.state.mergeid2)) {
            var footer=(<div>
                <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        </div>);
        }

return (<Dialog header="Merge Fencers Suggestions" position="center" visible={this.props.display} className="fencer-merge-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} >
    <div>
          {(!is_valid(this.state.mergeid1) || !is_valid(this.state.mergeid2) || !this.state.fencer1) && (<p>Merge two different fencers into one. Provide the IDs of both fencers first.</p>)}
          {(is_valid(this.state.mergeid1) && is_valid(this.state.mergeid2) && this.state.fencer1) && (<p>Click on 'Merge' to merge all data for these two fencers. All data of {this.state.fencer1.id} ({this.state.fencer1.name}, {this.state.fencer1.firstname}) will be retained, the other fencer is removed completely. <b>This cannot be undone, use at your own risk!</b></p>)}
          
              <div>
                <div className='formelement'>
                  <label>Fencer 1</label>
                    <div className='input'>
                    <InputNumber className='inputint' onChange={(e)=> this.change('mergeid1',e.value)}
                      mode="decimal" minFractionDigits={0} maxFractionDigits={0} min={0} useGrouping={false}
                      value={this.state.mergeid1}></InputNumber>
                    {this.state.fencer1 && (<div>
                        <span>{this.state.fencer1.name}, {this.state.fencer1.firstname}</span><br/>
                        <span>{this.state.fencer1.birthday}, {this.state.fencer1.country_name}</span><br/>
                        <span>
                            {this.state.fencer1.basic.rankings && (<span>Rankings:</span>)}
                            {this.state.fencer1.basic.rankings && (Object.keys(this.state.fencer1.basic.rankings).map((key) => {
                                var rnk = this.state.fencer1.basic.rankings[key];
                                return (<span key={key}>{key}: {rnk.pos} ({rnk.points})</span>);
                            }))}
                        </span>
                        {this.state.fencer1.basic.rankings && (<br/>)}
                        <span>
                            {this.state.fencer1.basic.registrations && (<span>Registrations:</span>)}
                            {this.state.fencer1.basic.registrations && (Object.keys(this.state.fencer1.basic.registrations).map((key) => {
                                var reg = this.state.fencer1.basic.registrations[key];
                                return (<span key={key}>{reg[0]}</span>);
                            }))}
                        </span>
                    </div>)}
                    </div>
                </div>
                <div className='formelement'>
                  <label>Fencer 2</label>
                    <div className='input'>
                    <InputNumber className='inputint' onChange={(e)=> this.change('mergeid2',e.value)}
                      mode="decimal" minFractionDigits={0} maxFractionDigits={0} min={0} useGrouping={false}
                      value={this.state.mergeid2}></InputNumber>
                    {this.state.fencer2 && (<div>
                        <span>{this.state.fencer2.name}, {this.state.fencer2.firstname}</span><br/>
                        <span>{this.state.fencer2.birthday}, {this.state.fencer2.country_name}</span><br/>
                        <span>
                            {this.state.fencer2.basic.rankings && (<span>Rankings:</span>)}
                            {this.state.fencer2.basic.rankings && (Object.keys(this.state.fencer2.basic.rankings).map((key) => {
                                var rnk = this.state.fencer2.basic.rankings[key];
                                return (<span key={key}>{key}: {rnk.pos} ({rnk.points})</span>);
                            }))}
                        </span>
                        {this.state.fencer2.basic.rankings && (<br/>)}
                        <span>
                            {this.state.fencer2.basic.registrations && (<span>Registrations:</span>)}
                            {this.state.fencer2.basic.registrations && (Object.keys(this.state.fencer2.basic.registrations).map((key) => {
                                var reg = this.state.fencer2.basic.registrations[key];
                                return (<span key={key}>{reg[0]}</span>);
                            }))}
                        </span>
                    </div>)}
                    </div>
                </div>
            </div>
    </div>
</Dialog>
);
    }
}

