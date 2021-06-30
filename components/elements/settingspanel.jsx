import { InputNumber } from 'primereact/inputnumber';
import { InputText } from 'primereact/inputtext';
import { RadioButton } from 'primereact/radiobutton';
import { ColorPicker } from 'primereact/colorpicker';
import { Dropdown } from 'primereact/dropdown';
import cloneDeep from 'lodash.clonedeep';

export function SettingsPanel(props) {

    const set = (member, value) => {
        var itm=cloneDeep(props.item);
        if(!itm.style) itm.style={};
        switch(member) {
        case 'color':
        case 'backgroundColor':
            itm.style[member] = "#"+value;
            itm[member+"2"]= "#"+value;
            break;
        case 'color2':
        case 'backgroundColor2':
            itm[member] = value;
            if(value && value.length>=7) {
                itm.style[member.substr(0,member.length-1)] = value;
            }
            break;
        case 'width':
        case 'height':
            value = parseInt(value);
            if(value<1 || isNaN(value)) value=1;
            if(member == 'width' && value > 420) value=420;
            if (member == 'height' && value > 594) value = 594;
            itm.style[member] = value;
            break;
        case 'fontSize':
        case 'zIndex':
            if(parseInt(value)<1) value=1;
            itm.style[member] = value;
            break;
        case 'ratio':
            value = parseInt(value);
            if(value<1 || isNaN(value)) value=1;
            if(value > 594) value=594;
            itm.style.height = value;
            itm.style.width = parseFloat(itm.ratio) * value;
            if(itm.style.width > 420) {
                itm.style.width=420;
                itm.style.height=420 / parseFloat(itm.ratio);
            }
            break;
        case 'name':
        case 'link':
        case 'side':
            itm[member]=value;
            break;
        }
        props.onChange(itm);
    }

    const renderZIndex = () => {
        var val = props.item.style ? parseInt(props.item.style.zIndex) : 1;
        return (
                <div>
                    <label>Layer</label>
                    <div className="input">
                        <InputNumber useGrouping={false} className='inputint' min={1} value={val} onChange={(e) => set("zIndex", e.value)} />
                    </div>
                </div>
        );
    }

    const renderColour = () => {
        if(!props.item.hasColour && !props.item.hasBackgroundColour) return (null);
        
        var val = props.item.hasColour ? props.item.style.color : props.item.style.backgroundColor;
        var val2=props.item.hasColour ? props.item.color2 : props.item.backgroundColor2;
        if(!val2) val2=val;
        var name=props.item.hasColour ? "color": "backgroundColor";

        return (
            <div>
            <div>
                <label>Colour</label>
                <div className="input">
                    <ColorPicker value={val} onChange={(e) => set(name, e.value)} />
                </div>
            </div>
                <div>
                    <label></label>
                    <div className="input">
                        <InputText value={val2} onChange={(e) => set(name+"2", e.target.value)} />
                    </div>
                </div>
            </div>
        );
    }

    const renderSize = () => {
        // maxsize = 420x594
        if(props.item.hasRatio && !isNaN(parseFloat(props.item.ratio))) {
            var val=props.item.style ? parseInt(props.item.style.height) : 0;
            var m1 = 420 / parseFloat(props.item.ratio);
            if(m1 > 594) m1=594;

            // ratio set, allow only increase and decrease
            return (
                <div>
                    <div>
                        <label>Size</label>
                        <div className="input">
                            <InputNumber useGrouping={false} className='inputint' min={1} max={m1} value={val} onChange={(e) => set("ratio", e.value)} />
                        </div>
                    </div>
                </div>
                );
        }
        else if(props.item.resizeable && (props.item.style.width || props.item.style.height)) {
            var val1 = props.item.style ? parseInt(props.item.style.width) : 0;
            var val2 = props.item.style ? parseInt(props.item.style.height) : 0;
            return (
                <div>
                    {props.item.style.width && (
                        <div>
                            <label>Width</label>
                            <div className="input">
                                <InputNumber useGrouping={false} className='inputint' min={1} max={420} value={val1} onChange={(e) => set("width",e.value)} />
                            </div>
                        </div>
                    )}
                    {props.item.style.height && (
                        <div>
                            <label>Height</label>
                            <div className="input">
                                <InputNumber useGrouping={false} className='inputint' min={1} max={594} value={val2} onChange={(e) => set("height", e.value)} />
                            </div>
                        </div>
                    )}
                </div>
            )
        }
        return (null);
    }

    const renderFontSize = () => {
        // maxsize = 420x594
        if (props.item.hasFontSize && !isNaN(parseFloat(props.item.style.fontSize))) {
            var val = props.item.style ? parseInt(props.item.style.fontSize) : 1;
            return (
                <div>
                    <div>
                        <label>Font Size</label>
                        <div className="input">
                            <InputNumber useGrouping={false} className='inputint' min={1} max={400} value={val} onChange={(e) => set("fontSize", e.value)} />
                        </div>
                    </div>
                </div>
            );
        }
        return (null);
    }

    const renderSpecial = () => {
        if(props.item && props.item.type == "name") {
            var val=props.item.name;
            return (<div>
                <div>
                    <label>Restrict</label>
                    <div className="input">
                        <div className="p-inputfield-radiobutton">
                        <RadioButton checked={val !== "first" && val !== "last"} inputId='wholename' name='wholename' value='whole' onChange={e => set("name",e.value)} />
                        <label htmlFor="wholename">Whole name</label>
                        </div>
                        <div className="p-inputfield-radiobutton">
                        <RadioButton checked={val == "first"}  inputId='firstname' name='firstname' value='first' onChange={e => set("name",e.value)} />
                        <label htmlFor="firstname">First name only</label>
                        </div>
                        <div className="p-inputfield-radiobutton">
                        <RadioButton checked={val == "last"}  inputId='lastname' name='lastname' value='last' onChange={e => set("name",e.value)} />
                        <label htmlFor="lastname">Surname only</label>
                        </div>
                    </div>
                </div>                
            </div>);
        }
        if(props.item && props.item.type == "qr") {
            var val = props.item.link;
            return (<div>
                <div>
                    <label>QR link</label>
                    <div className="input">
                        <InputText value={val} onChange={(e) => set("link", e.target.value)} />
                    </div>
                </div>
            </div>);
        }
        if(props.item && props.item.type == "accid") {
            var options=[{id:"both",text:"Both"},{id:"left",text:"Left"},{id:"right",text:"Right"}];
            var value=props.item.side;
            return (<div>
                <div>
                    <label>Print on Side</label>
                    <div className="input">
                        <Dropdown name='side' optionLabel="text" optionValue="id" value={value} options={options} onChange={(e) => set("side", e.target.value)} />
                    </div>
                </div>
            </div>);
        }
        return (null);
    }

    if (!props.item) return (null);

    return (<div className="settings inputform">
        <div className='label'>
            <label>Type</label>
            <div>{props.item.type}</div>
        </div>
        {renderSize()}
        {renderFontSize()}
        {renderColour()}
        {renderZIndex()}
        {renderSpecial()}
    </div>);
}