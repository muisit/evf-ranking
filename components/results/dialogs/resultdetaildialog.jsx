import React from 'react';
import { results } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';

export default class ResultDetailDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    render() {
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
    </div>);

        return (
                <Dialog header="Results" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} className="ranking-dialog">
                  <table className='detail'>
                    <thead>
                        <tr>
                            <th scope='col'>Pos</th>
                            <th scope='col'>Name</th>
                            <th scope='col'>Firstname</th>
                            <th scope='col'>Country</th>
                            <th scope='col'>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        {this.props.results.map((res,idx) => (
                      <tr key={idx}>
                          <td className='pos'>{res.place}</td>
                          <td>{res.fencer_surname}</td>
                          <td>{res.fencer_firstname}</td>
                          <td>{res.country}</td>
                          <td className='pos'>{res.total_points}</td>
                      </tr>
                        ))}
                    </tbody>
                  </table>
                </Dialog>
            );
        }
}

