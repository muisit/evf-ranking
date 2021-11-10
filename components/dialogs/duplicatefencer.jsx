import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';

export default class DuplicateFencer extends React.Component {
    constructor(props, context) {
        super(props, context);
    }

    onCancelDialog = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onSaveDialog = () => {
        if(this.props.onSave) this.props.onSave();
    }

    render() {
        if(!this.props.suggestions || this.props.suggestions.length==0) {
            return (null);
        }
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Save" icon="pi pi-check" className="p-button-raised" onClick={this.onSaveDialog} />
</div>);

        return (<Dialog header="Peruse Suggestions" position="center" visible={this.props.display} className="fencer-suggestion-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} >
    <div>
          <p>A few similar fencers were found in the database. Please check the below list to see if the fencer you want to add 
              may perhaps already be listed, but for a different country or with a slightly different name. If so, select the Cancel
              button to stop adding this new fencer and contact administration if the data for that specific fencer needs to be
              adjusted. This prevents issues where the results of the duplicate fencer need to be merged later on manually.
          </p>
          <table>
              <thead>
                  <tr>
                      <th>Surname</th>
                      <th>Firstname</th>
                      <th>DOB</th>
                      <th>Country</th>
                  </tr>
              </thead>
              <tbody>
                  {this.props.suggestions.map((fencer,idx) => (
                      <tr key={idx}>
                          <td>{fencer.name}</td>
                          <td>{fencer.firstname}</td>
                          <td>{fencer.birthday}</td>
                          <td>{fencer.country_name}</td>
                      </tr>
                  ))}
              </tbody>
          </table>
    </div>
</Dialog>
);
    }
}

