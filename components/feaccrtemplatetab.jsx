import React from 'react';
import { templates, template, abort_all_calls } from './api';
import {parse_net_error} from "./functions";
import AccreditationTemplateDialog from './dialogs/accreditationtemplatedialog';
import cloneDeep from 'lodash.clonedeep';

export default class FEAccrTemplateTab extends React.Component  {
    constructor(props, context) {
        super(props, context);
        this.abortType = 'templates';

        this.state = {
            template: null,
            templates: [],
            defaults: [],
            fonts: [],
            displayDialog: false,
        };
    }

    componentDidMount = () => {
        if (this.props.basic.event.id > 0) {
            templates(0,10000,{"event":this.props.basic.event.id},"i",{})
                .then((cmp1) => {
                    if(cmp1.data && cmp1.data.list) {
                        this.setState({templates:cmp1.data.list});
                    }
                });
            this.getDefaults();
        }
    }
    componentWillUnmount = () => {
        abort_all_calls(this.abortType);
    }

    getDefaults = () => {
        template("defaults",{})
            .then((json) => {
                if(json && json.data && json.data.templates && json.data.fonts) {
                    this.setState({defaults: json.data.templates, fonts: json.data.fonts});
                }
            });
    }

    loadDefaults = () => {
        template("loaddefaults",{event: this.props.basic.event.id, filter: { event: this.props.basic.event.id }})
            .then((cmp1) => {
                if (cmp1.data && cmp1.data.list) {
                    this.setState({ templates: cmp1.data.list });
                }
            });
    }

    cloneTemplate = (itm) => {
        var newtemplate=cloneDeep(itm);
        newtemplate.id=-1;
        newtemplate.name="Copy of " + newtemplate.name;
        newtemplate.copy_of=itm.id;
        template('save', newtemplate)
            .then((json) => {
                var itm = {};
                if (json.data.model) {
                    itm = Object.assign({}, itm, json.data.model);
                }
                this.onDialog('save',itm);
            })
            .catch((err) => parse_net_error(err));
    }

    onDialog = (itm, dt) => {
        switch(itm) {
            case 'defaults':
                this.loadDefaults();
                break;
            case 'open':
                if(!dt) {
                    dt= {id:-1,name:'New Template', event: this.props.basic.event.id, content: {}};
                }
                this.setState({displayDialog: true, template: dt});
                break;
            case 'close':
                this.setState({displayDialog: false});
                break;
            case 'delete':
                // the template was removed, filter it from the list                
                if (!dt) dt = this.state.template;
                var tmpls = this.state.templates.filter((val) => {
                    return val.id !== dt.id;
                });
                this.setState({ templates: tmpls });
                break;
            case 'save':
                // the template was saved and potentially has a new id
                // replace or add it in/to the list
                var found = false;
                if (!dt) dt = this.state.template;
                var tmpls = this.state.templates.map((val) => {
                    if (val.id == dt.id) {
                        found = true;
                        return dt;
                    }
                    return val;
                });
                if (!found) {
                    tmpls.push(dt);
                }
                this.setState({ templates: tmpls });
                break;
            case 'change':
                console.log("tab change handler for template ",dt);
                this.setState({template:dt});
                break;
        }
    }

    render() {
        console.log("tab rerendering for selected template ",this.state.template);
        return (
            <div className='row'>
                <div className='col-12'>
                    <table className='accreditor style-stripes-body'>
                        <tbody>
                        {this.state.templates.map((tmpl,idx) => (
                            <tr key={idx} onDoubleClick={() => this.onDialog('open',tmpl)}>
                              <td>{tmpl.name}</td>
                              <td><span className='pi pi-clone' onClick={()=>this.cloneTemplate(tmpl)}>Copy</span></td>
                            </tr>
                        ))}
                        </tbody>
                    </table>                    
                </div>
                <div className='col-4 offset-6'>
                    <span className='pi pi-plus-circle' onClick={() => this.onDialog('open')}>&nbsp;Create New</span><br/>
                    <span className='pi pi-undo' onClick={() => this.onDialog('defaults')}>&nbsp;Add Defaults</span>
                </div>
                <AccreditationTemplateDialog roles={this.props.basic.roles} event={this.props.basic.event} value={this.state.template} display={this.state.displayDialog} onClose={() => this.onDialog('close')} onChange={(itm) => this.onDialog('change', itm)} onSave={(itm) => this.onDialog('save', itm)} onDelete={(itm) => this.onDialog('delete',itm)} defaults={this.state.defaults} fonts={this.state.fonts} getDefaults={()=>this.getDefaults()}/>
            </div>
        );
    }
}
