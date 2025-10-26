import { Button } from 'primereact/button';
import React from 'react';

export class ToggleButton extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {};
    }

    render() {
        const onLabel = this.props.onLabel ?? 'On';
        const offLabel = this.props.offLabel ?? 'Off';
        const label = this.props.checked ? onLabel : offLabel;
        const severity = this.props.checked ? 'primary' : 'secondary';
        return (<Button severity={severity} label={label} onClick={(e) => this.props.onChange(!this.props.checked) } />);
    }
}
