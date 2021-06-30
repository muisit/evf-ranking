import React from 'react';
import { registration, template, upload_file, roletypes, abort_all_calls, templateexample } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { InputText } from 'primereact/inputtext';
import { Dropdown } from 'primereact/dropdown';
import { Checkbox } from 'primereact/checkbox';
import { MultiSelect } from 'primereact/multiselect';
import { parse_net_error, jsonOutput, random_hash, is_sysop } from "../functions";
import TemplateDesigner from "../elements/templatedesigner";
import DefaultTemplateDialog from "./defaulttemplatedialog";

export default class AccreditationTemplateDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            imageHash: random_hash(),
            roletypes: [],
            selectedRoles: [],
            showDefault: false,
            defaults: []
        };
        this.myRef = React.createRef();
        this.abortType="templates";
    }

    componentDidMount = () => {
        roletypes(0, 10000)
            .then((cmp1) => {
                if (cmp1.data && cmp1.data.list) {
                    this.setState({ roletypes: cmp1.data.list });
                }
            });
        this.getDefaults();
    }
    componentWillUnmount = () => {
        abort_all_calls(this.abortType);
    }
    getDefaults = () => {
        template("defaults",{})
            .then((json) => {
                if(json && json.data && json.data.list) {
                    this.setState({defaults: json.data.list});
                }
            });
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
    }
    change = (item) => {
        if (this.props.onChange) this.props.onChange(item);
    }
    delete = (item) => {
        if (this.props.onDelete) this.props.onDelete(item);
    }

    saveTemplate = (item) => {
        template('save', item)
            .then((json) => {
                var itm = Object.assign({}, item);
                if (json.data.model) {
                    itm = Object.assign({}, itm, json.data.model);
                }
                this.save(itm);
            })
            .catch ((err) => parse_net_error(err));
    }

    onCloseDialog = (event) => {
        this.saveTemplate(this.props.value);
        this.close();
    }

    onDeleteDialog = (event) => {
        if(confirm('Are you sure you want to delete this template? This action cannot be undone!')) {
            template('delete',{ id: this.props.value.id})
                .then((json) => {
                    this.delete(this.props.value);
                    this.close();
                })
                .catch((err) => parse_net_error(err));                    
        }
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    onChange = (event) => {
        if (!event.target) return;
        var name = event.target.name;
        var value = event.value || event.target.value;
        var item = Object.assign({},this.props.value);
        switch (name) {
        case 'name': 
            item[name] = value; 
            this.change(item);
            break;
        case 'print':
            item.content[name] = value; 
            this.change(item);
            break;
        case 'roles':
            value=value.map((itm) => {
                if(itm.label && itm.value) return itm.value;
                return itm;
            });
            item.content["roles"]=value;
            this.change(item);
            break;
        }
    }

    onDialogChange = (itm) => {
        this.change(itm);
    }

    onPrintDialog = () => {
        templateexample(this.props.value)
            .catch((err) => parse_net_error(err));
    }

    setAsDefault = () => {
        this.setState({showDefault: true});
    }
    removeAsDefault = () => {
        template('default', { id: this.props.value.id, 'unset': true })
            .then((json) => {
                this.getDefaults();
            })
            .catch((err) => parse_net_error(err));
    }
    onDefaultDialog = (action) => {
        switch(action) {
        case "close": this.setState({showDefault: false}); break;
        case "save":  this.getDefaults(); break;
        }
    }

    renderRoles() {
        var grouped={};
        this.props.roles.map((rl) => {
            var key="rt"+rl.type;
            if(!grouped[key]) grouped[key]=[];
            grouped[key].push(rl);
        });

        var options=[{
            label:"Athletes",
            items: [{label:"Athlete",value:"0"}]
        }];
        this.state.roletypes.map((rt) => {
            var key = "rt" + rt.id;
            if(grouped[key]) {
                var rls = grouped[key];
                var obj = {
                    label: rt.name,
                    code: key,
                    items: []
                };
                rls.map((rl) => {
                    obj.items.push({label: rl.name, value: rl.id});
                })
                options.push(obj);
            }
        });

        var value=[];
        if(this.props.value && this.props.value.content && this.props.value.content.roles) {
            value = this.props.value.content.roles;
        }

        return (<MultiSelect 
            style={{zIndex: 1001000 }}
            appendTo={document.body}
            name="roles"
            value={value} 
            onChange={this.onChange}
            options={options}
            optionGroupLabel="label"
            optionGroupChildren="items"/>
        );
    }


    renderPrints() {
        var options = [{
                label: "A6, double, A4 portrait, 2-per-page",
                value: "a4portrait"
        }, {
                label: "A6, double, A4 landscape, 2-per-page",
                value: "a4landscape"
        }, {
                label: "A6, single, A4 portrait, 4-per-page",
                value: "a4portrait2"
        },{
                label: "A6, single, A4 landscape, centered, 2-per-page",
                value: "a4landscape2"
        },{
                label: "A6, double, A5 landscape, 1-per-page",
                value: "a5landscape"
        }, {
                label: "A6, single, A5 landscape, 2-per-page",
                value: "a5landscape2"
        }, {
                label: "A6, single, A6 portrait, 1-per-page",
                value: "a6portrait"
        }
        ];
        var value = "a4portrait";
        if(this.props.value && this.props.value.content) {
            value=this.props.value.content.print || "a4portrait";
        }

        return (<Dropdown appendTo={document.body} name='print' optionLabel="label" optionValue="value" value={value} options={options} onChange={this.onChange} />
        );
    }

    render() {
        if(!this.props.value) {
            return (null);
        }

        var isDefault=false;
        this.state.defaults.map((itm) => {
            if(this.props.value.id == parseInt(itm.id)) {
                isDefault=true;
            }
        });

        var asdefbutton=(null);
        if(is_sysop()) {
            asdefbutton = (<Button label="Set as Default" icon="pi pi-link" className="p-button-raised p-button-text" onClick={this.setAsDefault} />);
            if(isDefault) {
                asdefbutton = (<Button label="Remove as Default" icon="pi pi-link" className="p-button-raised p-button-text" onClick={this.removeAsDefault} />);
            }
        }
        var footer = (<div>
            <Button label="Print" icon="pi pi-print" className="p-button-raised p-button-text" onClick={this.onPrintDialog} />
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
        </div>);
        if (this.props.value && this.props.value.id > 0 && this.props.delete !== false) {
            footer = (<div>
                {asdefbutton}
                <Button label="Print" icon="pi pi-print" className="p-button-raised p-button-text" onClick={this.onPrintDialog} />
                <Button label="Remove" icon="pi pi-trash" className="p-button-danger p-button-raised p-button-text" onClick={this.onDeleteDialog} />
                <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
                <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
            </div>);
        }

        return (<Dialog baseZIndex={100000} header="Accreditation Template" position="center" visible={this.props.display} className="accreditation-dialog" style={{ width: this.props.width || '75vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
      <div className='clearfix'>
        <label>Name</label>
        <div className='input'>
            <InputText name='name' value={this.props.value.name} onChange={this.onChange} placeholder='Name'/>
        </div>
      </div>
      <div className='clearfix'>
          <label>Roles</label>
          <div className='input'>
            {this.renderRoles()}
          </div>
      </div>
      <div className='clearfix'>
          <label>Print</label>
          <div className='input'>
            {this.renderPrints()}
          </div>
      </div>
      <div className='clearfix'>
        <TemplateDesigner template={this.props.value} event={this.props.event} onChange={this.onDialogChange} />
      </div>
      <DefaultTemplateDialog value={this.props.value} onClose={()=>this.onDefaultDialog("close")} onSave={()=>this.onDefaultDialog("save")} display={this.state.showDefault}/>
</Dialog>
);
    }
}

