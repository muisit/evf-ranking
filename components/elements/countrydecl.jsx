
import React from "react";
import { useDrag } from 'react-dnd';

export const CountryDecl = (props) => {
    var el = Object.assign({index:0},props.element);

    const [collectedProps, dragRef] = useDrag(
        () => ({
            type: "box",
            item: { index: el.index, type: "country"},
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

    return (<div onClick={clicked} className='elementwrapper' style={style}>
        <span className="pi pi-trash cright" onClick={deleteMe}></span>
        <div ref={dragRef} className='element country'>{props.element.text}</div>
    </div>);
}

