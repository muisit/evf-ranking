
import React from "react";
import { useDrag } from 'react-dnd';

export const Image = (props) => {
    var el = Object.assign({index:0},props.element);

    const [collectedProps, dragRef] = useDrag(
        () => ({
            type: "box",
            item: { index: el.index, type: "img"},
            collect: (monitor) => ({
                isDragging: monitor.isDragging()
            })
        }),
        []
    );

    var style = Object.assign({}, props.element.style);
    
    if(collectedProps.isDragging) {
        style.opacity=0.5;
    }
    var style2={};
    var url = evfranking.url + "&picture=" + props.element.file_id + "&template=" + props.template.id + "&nonce=" + evfranking.nonce + "&event=" + props.template.event + '&hash=' + Math.round(Math.random() * 1000000);
    style2.backgroundImage = 'url(' + url + ')';
    delete style.backgroundImage;

    const clicked = (e) => {
        props.onSelect(props.element);
    }

    const deleteMe = (e) => {
        props.onDelete(props.element);
    }

    return (<div onClick={clicked} className="elementwrapper" style={style}>
        <span className="pi pi-trash cright" onClick={deleteMe}></span>
        <div ref={dragRef} className='element image' style={style2}></div>
    </div>);
}

