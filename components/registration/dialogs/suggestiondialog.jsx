import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';

export default class SuggestionDialog extends React.Component {
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

    selectSuggestion = (itm) => {
        if (this.props.onSelect) this.props.onSelect(itm);
        this.close();
    }

    render() {
        if(!this.props.suggestions) {
            return (null);
        }
        var closebutton=(<Button label="Cancel" icon="pi pi-times" className="p-button-raised p-button-text" onClick={this.onCancelDialog} />);

        var footer=(<div>{closebutton}</div>);

        return (
        <Dialog baseZIndex={3000} header="Select Suggestions" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
          {this.props.suggestions.length > 0 && (
          <div>
            <label className='wide'>Suggestions</label>
            <div className='input'>
              <table className="suggestiontable">
                <tbody>
                  {this.props.suggestions.map((itm,idx) => (
                  <tr key={idx} onClick={() => this.selectSuggestion(itm)}>
                    <td>{itm.name}</td>
                    <td>{itm.firstname}</td>
                    <td>{this.props.basic.countriesById["c"+itm.country] && this.props.basic.countriesById["c"+itm.country].abbr}</td>
                    <td>{itm.birthday}</td>
                    <td>{itm.gender}</td>
                    <td className='icon'>
                      <Button icon="pi pi-replay" className="p-button p-button-text" label='Select'/>
                    </td>
                  </tr>
                ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </Dialog>);
    }
}

