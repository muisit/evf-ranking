import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { format_points } from '../../functions.js';

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
            <Button label="Close" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
    </div>);

        return (
                <Dialog header={"Results " + this.props.title} position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} className="ranking-dialog">
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
                        {this.props.results.map((res,idx) => this.renderResultLine(res))}
                    </tbody>
                  </table>
                </Dialog>
            );
        }
    
    renderResultLine(res) {
        if (res.ranked == 'E') {
            return (
                <tr key={res.id}>
                  <td className='pos'>{res.place}</td>
                  <td colSpan='2' style={{'text-align':'center'}}><i>excluded</i></td>
                  <td colSpan='2'>&nbsp;</td>
                </tr>
            );
        }
        return (
          <tr key={res.id}>
            <td className='pos'>
                {res.ranked != 'D' && (<span>{res.place}</span>)}
                {res.ranked == 'D' && (<span>DNF</span>)}
            </td>
            <td>{res.fencer_surname}</td>
            <td>{res.fencer_firstname}</td>
            <td>{res.country_abbr}</td>
            <td className='pos textright'>{format_points(res.total_points, 3)}</td>
          </tr>  
        )
    }
}

