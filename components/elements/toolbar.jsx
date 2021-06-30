import React from "react";
import { PhotoID } from "./photoid";
import { TextElement } from "./textelement";
import { useDrag, useDrop } from 'react-dnd';
import { useEffect } from "react";
import { upload_file, template }  from "../api";
import {parse_net_error} from "../functions";

export const ToolbarLine = (props) => {
    const [{ isDragging }, dragRef] = useDrag(
        () => ({
            type: "tool",
            item: props.element,
            collect: (monitor) => ({
                isDragging: monitor.isDragging()
            })
        }),
        []
    );

    const deleteImage = (el) => {
        if(props.candelete) props.candelete(el);
    }

    var content;
    switch(props.type) {
    case "photo": content = (<span className='pi pi-user'> Photo ID</span>); break;
    case "text": content = (<span className='pi pi-align-left'> Text</span>); break;
    case "name": content = (<span className='pi pi-id-card'> Name</span>); break;
    case "country": content = (<span className='pi pi-globe'> Country designation</span>); break;
    case "cntflag": content = (<span className='pi pi-globe'> Country flag</span>); break;
    case "org": content = (<span className='pi pi-globe'> Org designation</span>); break;
    case "roles": content = (<span className='pi pi-sitemap'> Roles</span>); break;
    case "dates": content = (<span className='pi pi-calendar'> Dates</span>); break;
    case "colour": content = (<span className='pi pi-palette'> Coloured box</span>); break;
    case "qr": content = (<span className='pi pi-external-link'> QR code</span>); break;
    //case "accid": content = (<span className='pi pi-external-link'> Accreditation ID</span>); break;
    case "image": content = (<div className='clearfix'><span className="pi pi-trash cright" onClick={()=>deleteImage(props.element)}></span><span className='pi pi-image'> {props.title}</span></div>); break;
    }

    return (<div className='component' ref={dragRef}>{content}</div>)
}

export const Toolbar = (props) => {

    const onFileChange = (event) => {
        var selectedFile = event.target.files[0];
        upload_file("template", selectedFile, {
            template: props.template.id,
            event: props.template.event
        })
            .then((json) => {
                if (json.data.picture) {
                    props.onFileAdd(json.data.picture);
                }
            })
            .catch((err) => parse_net_error(err));
    }

    const onFileDelete = (element) => {
        template("delpic",{event: props.template.event, template_id: props.template.id, file_id: element.file_id})
        .then((json) => {
            props.onFileDelete(element);
        })
        .catch ((err) => parse_net_error(err));
    }


    //            <ToolbarLine type="accid" element={{ type: "accid" }} />
    return (
        <div className="toolbox">
        <div className='elements'>
            <ToolbarLine type="photo" element={{ type: "photo" }}/>
            <ToolbarLine type="name" element={{ type: "name" }} />
            <ToolbarLine type="country" element={{ type: "country" }}/>
            <ToolbarLine type="cntflag" element={{ type: "cntflag" }}/>
            <ToolbarLine type="org" element={{ type: "org" }}/>
            <ToolbarLine type="roles" element={{ type: "roles"}}/>
            <ToolbarLine type="dates" element={{ type: "dates"}} />
            <ToolbarLine type="text"  element={{ type: "text" }}/>
            <ToolbarLine type="qr" element={{ type: "qr" }}/>
            <ToolbarLine type="colour" element={{ type: "box" }}/>
        </div>
        <div className='images'>
            {props.images.map((pc, idx) => {
                pc.type='img';
                return (<ToolbarLine key={idx} type="image" title={pc.file_name} element={pc} candelete={onFileDelete}/>);
            })}
            <div><input type="file" onChange={onFileChange} /></div>
        </div>
        </div>);
};