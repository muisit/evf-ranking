
import React from "react";
import { useState, useEffect } from "react";
import ContentEditable from 'react-contenteditable';
import { InputNumber } from 'primereact/inputnumber';
import { useDrag } from 'react-dnd';

export const TextElement = (props) => {
    var el = Object.assign({ index: 0 }, props.element);
    const [{ isDragging }, dragRef] = useDrag(
        () => ({
            type: "box",
            item: { index: el.index, type: "text", },
            collect: (monitor) => ({
                isDragging: monitor.isDragging()
            })
        }),
        []
    );

    var style = Object.assign({}, props.element.style);
    style.fontSize = style.fontSize + "pt";
    style.fontFamily = "Sans"; // force sans to avoid font problems
    style.backgroundColor = "none";
    if (isDragging) {
        style.opacity = 0.5;
    }

    const changeText = (e) => {
        var el=Object.assign({},props.element);
        el.text = e.target.value.replace(/<\/?[^>]+(>|$)/g, "");
        props.onChange(el);
    }

    const clicked = (e) => {
        props.onSelect(props.element);
    }

    const deleteMe = (e) => {
        props.onDelete(props.element);
    }

    return (<div onClick={clicked} className='elementwrapper' style={style}>
        <span className="pi pi-trash cright" onClick={deleteMe}></span>
        <div ref={dragRef} className='element textelement'>
            <ContentEditable
                html={props.element.text}
                onChange={changeText}
                tagName="p"
            />
        </div></div>
    )
}
