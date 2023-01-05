import { ProgressBar } from 'primereact/progressbar';
import React, { useState } from "react";

export function Loading(props) {
    //const [value, setValue] = useState(1);

    //console.log("props loading is ",Object.assign({},props.loading));
    var steps=Object.keys(props.loading).length;
    var done=0;
    var curvalue=null;
    Object.keys(props.loading).map((key) => {
        if(props.loading[key].state) done+=1;
        else if(!curvalue) curvalue = props.loading[key].label;
    });
    //console.log("steps ",steps,done,curvalue);
    if (done >= steps) {
        //console.log("empty return value");
        return (null);
    }
    var value = 100.0*done/steps;

    const displayValueTemplate = (value) => {
        if(!curvalue) curvalue = "Loading ...";
        return (
            <React.Fragment>
                {curvalue}
            </React.Fragment>
        );
    }

    return (<div className='p-dialog-mask p-component-overlay p-dialog-visible overlay-panel'>
        <div className="progress-panel p-dialog p-dialog-visible p-component">
          <ProgressBar value={value} displayValueTemplate={displayValueTemplate}></ProgressBar>
        </div>
    </div>);
}
