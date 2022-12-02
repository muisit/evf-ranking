import React from 'react';

import { is_sysop, is_organiser, is_accreditor, parse_net_error, date_to_category_num, get_yob } from "../functions";
import { fencer, singleevent, country,  registrations, registration, categories, weapons, roles, sideevents,competitions } from "../api";
import { Dropdown } from 'primereact/dropdown';
import { InputSwitch } from 'primereact/inputswitch';
import { Checkbox } from 'primereact/checkbox';

export default class AccreditationPage extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            fencer: null,
            event: null,
            country: null,
            registrations: [],
            categories: [],
            roles: [],
            weapons: [],
            competitions: [],
            sideevents: []
        };
    }
    componentDidMount = () => {
        categories(0, 1000, '', "n")
            .then(json => {
                if (json) this.setState({ "categories": json.data.list, "categories_count": json.data.total });
            });
        roles(0, 10000, '', "n")
            .then(json => {
                if (json) this.setState({ "roles": json.data.list, "roles_count": json.data.total });
            });
        weapons(0, 1000, '', "n")
            .then(json => {
                if (json) this.setState({ "weapons": json.data.list, "weapons_count": json.data.total });
            });

        this.reinitialiseWithAccreditation(evfranking.accreditation);
    }

    reinitialiseWithAccreditation = (accreditation) => {
        this.setState({accreditation: accreditation, fencer:null, country:null, event:null, registrations:[], sideevents:[], competitions:[]}, this.initialiseWithAccreditation);
    }

    initialiseWithAccreditation = () => {
        fencer("view",{id: this.state.accreditation.fencer_id})
          .then((json) => {
            if(json && json.data) {
                this.setState({fencer: json.data.item});

                country("view",{id: json.data.item.country})
                    .then((json) => {
                        if(json && json.data) {
                            this.setState({country:json.data.item}, this.getRegistrations);
                        }
                    });
            }
          });

        singleevent("view",{id: this.state.accreditation.event_id})
          .then((json) => {
            if(json && json.data) {
                this.setState({event: json.data.item}, this.getRegistrations);
            }
          });
    }

    getRegistrations = () => {
        if(this.state.fencer && this.state.event && this.state.country) {
            registrations(0, 10000, { country: this.state.country.id, event: this.state.event.id })
                .then((cmp) => this.parseRegistrations(cmp.data.list));

            sideevents(this.state.event.id).then((cmp1) => { 
                var sortedevents = cmp1.data.list.slice();
                sortedevents.sort(function (e1, e2) {
                    // we sort competitions first, so if one item has a competition_id and the other not, return a value
                    if (e1.competition_id > 0 && !e2.competition_id) return -1; // e1 before e2
                    if (e2.competition_id > 0 && !e1.competition_id) return 1; // e2 before e1

                    // else compare only on title
                    return (e1.title < e2.title) ? -1 : 1;
                });
                this.setState({sideevents: sortedevents}); 
            });
            competitions(this.state.event.id).then((cmp) => {
                this.setState({competitions: cmp.data.list});
            });
        }
    }

    parseRegistrations = (registrations) => {
        var ourkey="k" + this.state.fencer.id;
        var ourregistrations=[];
        registrations.map((itm) => {
            var fid = itm.fencer;
            var key = "k" + fid;
            if (key == ourkey) {
                ourregistrations.push(itm);
            }
        });
        this.setState({ registrations: ourregistrations});
    }

    replaceRegistration = (reg) => {
        var regs = this.state.registrations.map((r) => {
            if (r.id == reg.id) {
                return reg;
            }
            return r;
        });
        this.setState({ registrations: regs });
    }

    // convenience function to allow changing all registrations at once
    onChange = (reg, field, value) => {
        if(reg == "all") {
            this.state.registrations.map((r) => {
                this.onChangeReg(r,field,value);
            });
        }
        else {
            this.onChangeReg(reg,field,value);
        }
    }

    onChangeReg = (reg,field,value) => {
        var self=this;
        switch(field) {
        case 'state':
            if(['R','P','C'].includes(value)) {
                var oldvalue=reg.state;
                reg.state = value;
                reg.pending=true;
                registration('save', {
                    id: reg.id || -1,
                    state: reg.state,
                    event: reg.event,
                    fencer: reg.fencer
                })
                .then((json) => {
                    window.setTimeout(() => {
                        reg.pending=false;
                        self.replaceRegistration(reg);
                    },500);
                })
                .catch((err) => {
                    reg.state=oldvalue;
                    reg.pending=false;
                    self.replaceRegistration(reg);
                    return parse_net_error(err);
                });
            }
            break;
        }
        this.replaceRegistration(reg);
    }

    render() {
        if(!is_sysop() && !is_organiser() && !is_accreditor()) {
            return (<div>
                <h2>Not Allowed</h2>
                <p>You do not have sufficient access rights to validate accreditations.</p>
            </div>);            
        }
        if(!this.state.accreditation || this.state.accreditation.id<0) {
            return (<div>
                <h2>No such accreditation</h2>
                <p>The accreditation you were trying to check in is not available at the moment.</p>
            </div>);
        }
        
        if(this.state.accreditation && (
               !this.state.event || !this.state.fencer 
            || !this.state.registrations.length || !this.state.roles.length 
            )) {
            return (<div>
                <h2>Retrieving data</h2>
                <p>Loading data from database.</p>
            </div>); 
        }

        var yob="";
        var cat="";
        if(this.state.fencer) {
            yob = get_yob(this.state.fencer.birthday);
            cat = date_to_category_num(this.state.fencer.birthday, this.state.event.opens);
        }

        var roleById={};
        this.state.roles.map((r)=> {
            var key="r"+r.id;
            roleById[key]=r;
        });
        roleById["r0"]={name:"Athlete"};

        var eventById={};
        this.state.sideevents.map((s) => {
            var key="s"+s.id;
            eventById[key]=s;
        });

        var isdisabled=true;
        var ischecked=true;
        this.state.registrations.map((r) => {
            switch(r.state) {
            case 'C': ischecked=false; break;
            case 'R': isdisabled=false; ischecked=false; break;
            case 'P': isdisabled=false; break;
            }
        });

        return (
            <div className='inputform wide'>
                <div>
                    <label className='header'>ID</label>
                    <div className='input'>{evfranking.accreditation.fe_id}</div>
                </div>
                <div>
                    <label className='header'>Fencer</label>
                    <div className='input'>
                        {this.state.fencer && this.state.fencer.name}, {this.state.fencer && this.state.fencer.firstname}
                    </div>
                </div>
                <div>
                    <label className='header'>Year of birth</label>
                    <div className='input'>
                        {yob} (category {cat})
                    </div>
                </div>
                <div>
                    <label className='header'>Country</label>
                    <div className='input'>
                        {this.state.country && this.state.country.name}
                    </div>
                </div>
                <div>
                    <label>Registrations</label>
                    <div className='input'>
                        <table className='compact'>
                            <tbody>
                                {this.state.registrations.length > 1 && (
                                    <tr key='o' className='header'>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <InputSwitch disabled={isdisabled} checked={ischecked} onChange={(e) => this.onChange("all", "state", e.value ? "P" : "R")} />&nbsp;&nbsp;
                                            <Checkbox value='C' name={'state-o'} onChange={(e) => this.onChange("all", 'state', e.checked ? 'C' : 'R')} checked={isdisabled} /> Unregister
                                        </td>
                                    </tr>
                                )}
                                {this.state.registrations.map((r,idx) => {
                                    var se=eventById["s" + r.sideevent];
                                    var role=roleById["r"+r.role];
                                    return (
                                    <tr key={idx}>
                                        <td>{se && se.title}</td>
                                        <td>{role && role.name}</td>
                                        <td>
                                            {this.renderRegistrationActions(r)}    
                                        </td>
                                    </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <label className='header'>Photo ID</label>
                    <div className='input'>
                      {this.state.fencer && this.state.event && (<img src={evfranking.url + "&picture="+this.state.fencer.id + "&nonce=" + evfranking.nonce + "&event=" + this.state.event.id}></img>)}
                    </div>
                </div>
            </div>
        );
    }

    renderRegistrationActions (reg) {
        var ischecked=reg.state == 'P';
        var isdisabled=reg.state == 'C';
        return (
            <div className={reg.pending? "bggreen" : ""}>
                <InputSwitch disabled={isdisabled} checked={ischecked} onChange={(e) => this.onChange(reg, "state",e.value ? "P":"R")} />&nbsp;&nbsp;
                <Checkbox value='C' name={'state'+reg.id} onChange={(e)=>this.onChange(reg, 'state', e.checked ? 'C' : 'R')} checked={isdisabled} /> Unregister
            </div>
        );
    }
}
