
import React from "react";
import { useDrag } from 'react-dnd';

export const Dates = (props) => {
    var el = Object.assign({index:0},props.element);

    const [collectedProps, dragRef] = useDrag(
        () => ({
            type: "box",
            item: { index: el.index, type: "dates"},
            collect: (monitor) => ({
                isDragging: monitor.isDragging()
            })
        }),
        []
    );

    var style = Object.assign({}, props.element.style);
    style.backgroundColor = "none";
    style.fontSize = style.fontSize + "pt";
    style.fontFamily = "Sans"; // force sans to avoid font problems
    if(collectedProps.isDragging) {
        style.opacity=0.5;
    }

    const clicked = (e) => {
        props.onSelect(props.element);
    }

    const deleteMe = (e) => {
        props.onDelete(props.element);
    }

    var onedateonly = props.element.onedateonly === true;
    return (<div onClick={clicked} className='elementwrapper' style={style}>
        <span className="pi pi-trash cright" onClick={deleteMe}></span>
        <div ref={dragRef} className='element roles'>07 WED{!onedateonly && (<br/>)}{!onedateonly && "21 SUN"}</div>
    </div>);
}

