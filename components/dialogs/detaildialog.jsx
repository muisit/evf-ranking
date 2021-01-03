import React from 'react';
import { country } from "../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';

export default class DetailDialog extends React.Component {
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
        if (!this.props.detail || this.props.detail.length == 0) {
            return (<div>...</div>);
        }
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
    </div>);

        var firstentry=this.props.detail[0];
        return (
                <Dialog header="Details" position="center" visible={this.props.display} style={{ width: '70vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
                  <div className='personaldetail'>
                    <div className='fencer'>
                        <span className='name'>{firstentry.surname},&nbsp;</span>
                        <span className='firstname'>{firstentry.firstname}</span>
                        <span className='abbr'>{firstentry.abbr}</span>
                    </div>
                  </div>
                  <table className='detail'>
                    <thead>
                        <tr>
                            <th scope='col'>#</th>
                            <th scope='col'>Event</th>
                            <th scope='col'>Year</th>
                            <th scope='col'>Location</th>
                            <th scope='col'>Weapon</th>
                            <th scope='col'>Entry</th>
                            <th scope='col'>Place</th>
                            <th scope='col'>Points</th>
                            <th scope='col'>DE</th>
                            <th scope='col'>Podium</th>
                            <th scope='col'>Factor</th>
                            <th scope='col'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {this.props.detail.map((comp,idx) => (
                      <tr key={idx} className={(comp.included == 'Y') ? "counting":"notcounting"}>
                          <td className='pos'>{idx+1}</td>
                          <td>{comp.event}</td>
                          <td>{comp.year}</td>
                          <td>{comp.location}</td>
                          <td>{comp.weapon}</td>
                          <td className='pos'>{comp.entry}</td>
                          <td className='pos'>{comp.place}</td>
                          <td className='pos'>{comp.points}</td>
                          <td className='pos'>{comp.de}</td>
                          <td className='pos'>{comp.podium}</td>
                          <td className='pos'>{comp.factor}</td>
                          <td className='pos'>{comp.total}</td>
                      </tr>
                        ))}
                    </tbody>
                  </table>
                </Dialog>
            );
        }
}

