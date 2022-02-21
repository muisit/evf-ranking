import { useRef, useState, useEffect } from "react";
import {upload_file } from "../api";
import {parse_net_error, format_datetime, jsonOutput} from "../functions";
import { Toolbar } from './toolbar';
import { SettingsPanel } from './settingspanel';
import { PhotoID } from "./photoid";
import { TextElement } from "./textelement";
import { BoxElement } from "./boxelement";
import { Name } from "./name";
import { CountryDecl } from "./countrydecl";
import { CountryFlag } from "./countryflag";
import { OrgDecl } from "./orgdecl";
import { Roles } from "./roles";
import { Dates } from "./dates";
import { Image } from "./image";
import { QRCode } from "./qrcode";
import { AccID } from "./accid";
import { DndProvider, useDrop } from 'react-dnd'
import { HTML5Backend } from 'react-dnd-html5-backend'
import cloneDeep from 'lodash.clonedeep';

function EditorCanvas(props) {
    const [collectedProps, dropRef] = useDrop(() => ({
        accept: ["box","tool"],
        drop: (item, monitor) => onDrop(item,monitor),
    }));

    const onDrop = (item, monitor) => {
        var type=monitor.getItemType();
        if(type == "tool") {
            // return a new item if this item was dragged from the toolbar
            props.onDrop(cloneDeep(monitor.getItem()),"add");
        }
        else {
            // move the item to the new location
            var offset = monitor.getDifferenceFromInitialOffset();
            props.onDrop({index: item.index, top: offset.y, left: offset.x}, "replace");
        }
    }

    const changeElement = (el) => {
        props.onChange(el);
    }

    const selectElement = (el) => {
        props.onSelect(el);
    }

    const deleteElement = (el) => {
        props.onDelete(el);
    }

    const instantiateElement = (itm) => {
        var key=itm.index;
        switch(itm.type) {
        case "photo": return (<PhotoID key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "text": return (<TextElement key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "name": return (<Name key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        //case "accid": return (<AccID key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "country": return (<CountryDecl key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "cntflag": return (<CountryFlag key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "org": return (<OrgDecl key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement}/>);
        case "roles": return (<Roles key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement} />);
        case "dates": return (<Dates key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement} />);
        case "box": return (<BoxElement key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement} />);
        case 'qr': return (<QRCode key={key} element={itm} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement} />);
        case "img": return (<Image key={key} element={itm} template={props.template} imageHash={props.imageHash} onChange={changeElement} onSelect={selectElement} onDelete={deleteElement} />);
        }
    }

    return (        
        <div className="canvas" ref={dropRef}>
            <div className='canvasmargins'></div>
            {props.elements && props.elements.map((itm) => instantiateElement(itm)) }
        </div>
    );    
}

export default class TemplateDesigner extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            selected: null,
            imageHash: Math.round(Math.random() * 1000000)
        };
    }

    defaultPhoto = () => {
        var ratio = 413.0 / 531.0;
        var element = {
            type: "photo",
            style: { left: 0, top: 0, width: 100, height: 100 / ratio, zIndex: 1 },
            ratio: ratio,
            hasRatio: true
        };
        return element;
    }

    defaultText = () => {
        var element = {
            type: "text",
            text: "Change Me",
            style: { left: 0, top: 0, fontSize: 20, zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true
        };
        return element;
    }

    defaultName = () => {
        var element = {
            type: "name",
            text: "NOSUCHNAME, nosuchperson",
            style: { width: 420, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex:1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true
        };
        return element;
    }
    defaultAccID = () => {
        var element = {
            type: "accid",
            style: { width: 210, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true,
            side: "both"
        };
        return element;
    }

    defaultCountry = () => {
        var element = {
            type: "country",
            text: "EUR",
            style: { width: 420, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true
        };
        return element;
    }
    defaultCountryFlag = () => {
        var element = {
            type: "cntflag",
            style: { left: 0, top: 0, width: 400, height: 300, zIndex: 1 },
            hasRatio: true,
            ratio: 4/3
        };
        return element;
    }
    defaultOrg = () => {
        var element = {
            type: "org",
            text: "ORG",
            style: { width: 420, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true
        };
        return element;
    }
    defaultRoles = () => {
        var element = {
            type: "roles",
            style: { width: 420, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true
        };
        return element;
    }
    defaultDates = () => {
        var element = {
            type: "dates",
            style: { width: 420, height: 60, left: 0, top: 0, fontSize: 30, fontStyle: "bold", fontFamily: "Helvetica", zIndex: 1, color: "#000000" },
            hasFontSize: true,
            hasColour: true,
            resizeable: true,
            onedateonly: false
        };
        return element;
    }
    defaultBox = () => {
        var element = {
            type: "box",
            style: { left: 0, top: 0, width: 420, height: 200, backgroundColor:"#aa4444", zIndex: 1 },
            resizeable: true,
            hasBackgroundColour: true
        };
        return element;
    }
    defaultQR = () => {
        var element = {
            type: "qr",
            style: { left: 0, top: 0, width: 100, height: 100, zIndex: 1 },
            resizeable: true,
            hasRatio: true,
            ratio: 1.0,
        };
        return element;
    }
    defaultImage = () => {
        var element = {
            type: "img",
            style: { left: 0, top: 0, width: 10, height: 10, zIndex: 1 },
            hasRatio: true,
            ratio: 1.0
        };
        return element;
    }

    change = (itm) => {
        if(this.props.onChange) this.props.onChange(itm);
    }

    changeEditor = (dt) => {
        var els = (this.props.template.content && this.props.template.content.elements) ? this.props.template.content.elements : null;
        if (!els || !els.length) els = [];

        els = els.map((el) => {
            if (el.index == dt.index) {
                return dt;
            }
            return el;
        });

        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        itm.content.elements=els;
        this.change(itm);
        if (this.state.selected && dt.index == this.state.selected.index) {
            this.setState({ selected: dt });
        }
    }

    checkPos = (dt) => {
        // no negative values, keep it within the frame
        var w = parseInt(dt.style.width);
        var h = parseInt(dt.style.height);
        var x = parseInt(dt.style.left);
        var y = parseInt(dt.style.top);

        if ((x + w) > 420) x = 420 - w;
        if ((y + h) > 594) y = 594 - h;
        if(x<0) x=0;
        if(y<0) y=0;

        dt.style.left=x;
        dt.style.top=y;

        return dt;
    }

    changePos = (dt,action)=> {
        var els = (this.props.template.content && this.props.template.content.elements) ? this.props.template.content.elements : null;
        if (!els || !els.length) els = [];
        var found=null;

        if(action == "replace") {
            els = els.map((el) => {
                if (el.index == dt.index) {
                    el.style.top += dt.top;
                    el.style.left += dt.left;
                    el=this.checkPos(el);
                    found=el;
                }
                return el;
            });
        }
        else {
            var el={};
            switch(dt.type) {
            case 'photo': el = this.defaultPhoto(); break;
            case 'text': el = this.defaultText(); break;
            case 'country': el = this.defaultCountry(); break;
            case 'cntflag': el= this.defaultCountryFlag(); break;
            case 'org': el = this.defaultOrg(); break;
            case 'name': el = this.defaultName(); break;
            //case 'accid': el=this.defaultAccID(); break;
            case 'roles': el = this.defaultRoles(); break;
            case 'dates': el = this.defaultDates(); break;
            case 'box': el=this.defaultBox(); break;
            case 'qr': el=this.defaultQR(); break;
            case 'img':
                if(dt.file_id && dt.file_name) {
                    el = this.defaultImage(); 
                    el.ratio = parseFloat(dt.width) / parseFloat(dt.height);
                    el.style.width = dt.width;
                    el.style.height = dt.height;
                    el.file_id = dt.file_id;
                }
                break;
            }
            if(el.type) {
                el.index = Math.round(Math.random() * 1000000);
                found=el;
                els.push(el);
            }
        }

        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        itm.content.elements = els;
        this.change(itm);
        if(this.state.selected && found && found.index == this.state.selected.index) {
            this.setState({selected:found});
        }
    }

    onFileAdd = (data) => {
        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        if(!itm.content.pictures) itm.content.pictures=[];
        itm.content.pictures.push(data);
        this.setState({imageHash: Math.round(Math.random() * 1000000)});
        this.change(itm);
    }

    onFileDelete = (data) => {
        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        if (!itm.content.pictures) itm.content.pictures = [];
        var pics=itm.content.pictures.filter((pic) => {
            if(pic.file_id == data.file_id) {
                return false;
            }
            return true;
        });
        itm.content.pictures=pics;
        this.change(itm);
    }

    onSelect = (data) => {
        this.setState({selected: data});
    }

    onDelete = (data) => {
        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        if (!itm.content.elements) itm.content.elements = [];
        var els = itm.content.elements.filter((el) => {
            if (el.index == data.index) {
                return false;
            }
            return true;
        });
        itm.content.elements = els;
        this.change(itm);
        if(this.state.selected && this.state.selected.index == data.index) {
            this.setState({selected:null, imageHash: Math.round(Math.random() * 1000000)});
        }
    }

    changeSettings = (data) => {
        var itm = cloneDeep(this.props.template);
        if (!itm.content) itm.content = {};
        if (!itm.content.elements) itm.content.elements = [];
        var els = itm.content.elements.map((el) => {
            if (el.index == data.index) {
                return data;
            }
            return el;
        });
        itm.content.elements = els;
        this.setState({selected:data},() => this.change(itm));
    }

    render() {
        console.log("rendering template ",this.props.template);
        var pictures = (this.props.template.content && this.props.template.content.pictures) ? this.props.template.content.pictures : null;
        if(!pictures || !pictures.length) pictures=[];

        var els = (this.props.template.content && this.props.template.content.elements) ? this.props.template.content.elements : null;
        if(!els || !els.length) els=[];

        return (
        <div className='templateeditor clearfix'>
            <DndProvider backend={HTML5Backend}>
            <div className='toolbox'>
              <Toolbar template={this.props.template} images={pictures} onFileAdd={this.onFileAdd} onFileDelete={this.onFileDelete}/>
            </div>
            <EditorCanvas template={this.props.template} elements={els} onDelete={this.onDelete} onSelect={this.onSelect} onChange={this.changeEditor} onDrop={this.changePos} imageHash={this.state.imageHash}/>
            <SettingsPanel item={this.state.selected} onChange={this.changeSettings} fonts={this.props.fonts}/>
            <div className='debug'></div>
            </DndProvider>
        </div>
        );
    }
}