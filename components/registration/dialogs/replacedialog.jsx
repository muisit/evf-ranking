import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';

export default class ReplaceDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            item: {id:-1}
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCloseDialog = (event) => {
        if(this.props.onSave) this.props.onSave(this.props.value);
    }

    onCancelDialog = (event) => {
        this.close();
    }

    selectReplacement = (itm) => {
        var fencer = this.props.fencer;
        itm.sideevent = itm.suggestion.id;
        itm.suggestion = null;
        fencer.reglist.replace(itm);
        if (this.props.onSelect) this.props.onSelect(fencer);
    }

    render() {
        if(!this.props.fencer || !this.props.fencer.reglist || !this.props.fencer.reglist.registrations) {
            return (null);
        }
        var regs = this.props.fencer.reglist.registrations.filter((reg) => (reg.suggestion && reg.suggestion !== null));
        if (regs.length == 0) {
            return (null);
        }

        var closebutton=(<Button label="Cancel" icon="pi pi-times" className="p-button-raised p-button-text" onClick={this.onCancelDialog} />);
        var footer=(<div>{closebutton}</div>);

        return (
        <Dialog baseZIndex={3000} header="Select Suggestions" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          <div>
            <label className='wide'>Replacements</label>
            <div className='input'>
              <table className="suggestiontable">
                <thead>
                    <tr>
                        <th>Current Competition</th>
                        <th>Suggested Competition</th>
                    </tr>
                </thead>
                <tbody>
                  {regs.map((reg,idx) => (
                  <tr key={idx} onClick={() => this.selectReplacement(reg)}>
                    <td>{this.getEventName(reg.sideevent)}</td>
                    <td>{this.getEventName(reg.suggestion)}</td>
                    <td className='icon'>
                      <Button icon="pi pi-replay" className="p-button p-button-text" label='Select'/>
                    </td>
                  </tr>
                ))}
                </tbody>
              </table>
            </div>
          </div>
      </Dialog>);
    }

    getEventName = (event) => {
        if (!event.title) {
            event = this.props.basic.sideeventsById['s' + event];
        }
        return event.title;
    }
}

