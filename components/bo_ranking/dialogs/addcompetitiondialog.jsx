import React from 'react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Checkbox } from 'primereact/checkbox';

export default class AddCompetitionDialog extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = {
            selectedWeapons: [],
            selectedCategories: []
        };
    }

    close = () => {
        if(this.props.onClose) this.props.onClose();
    }

    onCloseDialog = (event) => {
        if(this.props.onSave) this.props.onSave(this.state.selectedCategories, this.state.selectedWeapons);
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }    

    setCategory = (cat, state) => {
        var cats = this.state.selectedCategories.filter((c) => c != cat.id);
        if (state) {
            cats.push(cat.id);
        }
        this.setState({selectedCategories: cats});
    }

    setWeapon = (wpn, state) => {
        var wpns = this.state.selectedWeapons.filter((c) => c != wpn.id);
        if (state) {
            wpns.push(wpn.id);
        }
        this.setState({selectedWeapons: wpns});
    }

    render() {
        var footer=(<div>
        <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
        <Button label="Create" icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
</div>);

        return (<Dialog header="Add Competitions" position="center" visible={this.props.display} style={{ width: '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog}>
            <div>
                <label>Category</label>
                <div className='input'>
                    <table><tbody>
                    {this.props.categories.map((cat) => (
                        <tr key={cat.id}>
                            <td>
                              <Checkbox inputId={'cat-' + cat.id} onChange={(e) => this.setCategory(cat, e.checked)} checked={this.state.selectedCategories.includes(cat.id)}/>
                            </td>
                            <td>
                              <label htmlFor={'cat-' + cat.id}>{cat.name}</label>
                            </td>
                        </tr>
                    ))}
                    </tbody></table>
                </div>
            </div>
            <div>
                <label>Weapon</label>
                <div className='input'>
                <table><tbody>
                    {this.props.weapons.map((wpn) => (
                        <tr key={wpn.id}>
                            <td>
                              <Checkbox inputId={'wpn-' + wpn.id} onChange={(e) => this.setWeapon(wpn, e.checked)} checked={this.state.selectedWeapons.includes(wpn.id)}/>
                            </td>
                            <td>
                              <label htmlFor={'wpn-' + wpn.id}>{wpn.name}</label>
                            </td>
                        </tr>
                    ))}
                    </tbody></table>
                </div>
            </div>
        </Dialog>);
    }
}

