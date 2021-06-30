
import React from "react";
import { useState, useEffect } from "react";
import ContentEditable from 'react-contenteditable';
import { InputNumber } from 'primereact/inputnumber';
import { useDrag } from 'react-dnd';

export const BoxElement = (props) => {
    var el = Object.assign({ index: 0 }, props.element);
    const [{ isDragging }, dragRef] = useDrag(
        () => ({
            type: "box",
            item: { index: el.index, type: "box", },
            collect: (monitor) => ({
                isDragging: monitor.isDragging()
            })
        }),
        []
    );

    var style = Object.assign({}, props.element.style);
    if (isDragging) {
        style.opacity = 0.5;
    }

    const clicked = (e) => {
        props.onSelect(props.element);
    }

    const deleteMe = (e) => {
        props.onDelete(props.element);
    }

    return (<div onClick={clicked} className='elementwrapper' style={style}>
        <span className="pi pi-trash cright" onClick={deleteMe}></span>
        <div ref={dragRef} className='element boxelement'></div>
    </div>);
}
