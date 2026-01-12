import React from 'react';
import { Dropdown } from 'primereact/dropdown';
import { Calendar } from 'primereact/calendar';
import { Button } from 'primereact/button';
import { workflow, error_handler } from "../../../api.js";
import { is_valid, is_valid_date, parse_date, format_date } from '../../../functions.js';

export default class SelectCompetition extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = this.selectDefaultValues(this.props.value.sandbox.selectedCompetition ?? -1);
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }    

    skip = () => {
        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'save_competition',
            skip: true
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    }

    save = () => {
        // validation
        if (!is_valid(this.state.category)) {
            alert('Category is required');
            return;
        }
        if (!is_valid(this.state.weapon)) {
            alert('Weapon is required');
            return;
        }
        if (!is_valid_date(this.state.date)) {
            alert('Date is required');
            return;
        }

        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'save_competition',
            competition: {
                id: this.state.competition_id,
                date: format_date(this.state.date),
                category: this.state.category,
                weapon: this.state.weapon
            }
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    }

    onChangeEl = (event, attrvalue) => {
        var name=attrvalue ? event : (event.target ? event.target.name : event.originalEvent.target.name);
        var value=attrvalue ? attrvalue : (event.target ? event.target.value : event.value);
        switch (name) {
            case 'competition':
                this.setState(this.selectDefaultValues(parseInt(value)));
                break;
            case 'category':
                this.setState({category: parseInt(value)});
                break;
            case 'weapon':
                this.setState({weapon: parseInt(value)});
                break;
            case 'date':
                this.setState({date: parse_date(value)});
                break;
        }
    }

    selectDefaultValues = (cId) => {
        const selectedCompetitions = this.props.value.sandbox.competitions.filter((item) => item.id == cId);
        const selectedCompetition = selectedCompetitions.length > 0 ? selectedCompetitions[0] : {};
        return {
            competition_id: parseInt(cId),
            date: parse_date(selectedCompetition?.date ?? this.props.value.sandbox.date),
            category: parseInt(selectedCompetition?.category ?? this.props.value.sandbox.category),
            weapon: parseInt(selectedCompetition?.weapon ?? this.props.value.sandbox.weapon)
        };
    }

    render() {
        const competitionId = this.state.competition_id;
        const competitions = this.props.value.sandbox.competitions?.sort((a,b) => {
            const wpna = this.props.data.weaponsById['w' + a.weapon];
            const wpnb = this.props.data.weaponsById['w' + b.weapon];
            const cata = this.props.data.categoriesById['c' + a.category];
            const catb = this.props.data.categoriesById['c' + b.category];

            if (wpna && !wpnb) return -1;
            if (!wpna && wpnb) return 1;
            if (wpna.id != wpnb.id) {
                const ga = wpna.abbr[0];
                const wa = wpna.abbr[1];
                const gb = wpnb.abbr[0];
                const wb = wpnb.abbr[1];
                if (wa < wb) return -1;
                if (wa > wb) return 1;
                if (ga < gb) return -1;
                return 1;
            }

            if (cata && !catb) return -1;
            if (catb && !cata) return 1;
            return cata.id < catb.id ? -1 : 1;
        }).map((item) => {
            const wpn = this.props.data.weaponsById['w' + item.weapon];
            const cat = this.props.data.categoriesById['c' + item.category];
            return {id: item.id, name: wpn?.name + ' ' + cat?.name};
        }) ?? [];
        competitions.unshift({id:-1, name:'New competition'});
        const categories = this.props.data?.categories?.map((item) => {
            return {id: item.id, name: item.name};
        }) ?? [];
        const weapons = this.props.data?.weapons?.map((item) => {
            return {id: item.id, name: item.name};
        }) ?? [];

        return (
      <div>
        <div className='input-form'>
            <div>
                <label>Original</label>
                <div className="input">
                    {this.props.value.sandbox.competition_name}
                </div>
            </div>
            <div>
                <label>Competition</label>
                <div className="input">
                    <Dropdown className='evntdrop' appendTo={document.body} name="competition" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={competitionId} options={competitions} placeholder="Competition" />
                </div>
            </div>
            <div>
                <label>Weapon</label>
                <div className='input'>
                    <Dropdown optionLabel="name" optionValue="id" value={this.state.weapon} options={weapons} placeholder="Weapon" onChange={this.onChangeEl} name='weapon'/>
                </div>
            </div>
            <div>
                <label>Category</label>
                <div className='input'>
                    <Dropdown optionLabel="name" optionValue="id" value={this.state.category} options={categories} placeholder="Category" onChange={this.onChangeEl} name='category'/>
                </div>
            </div>
            <div>
                <label>Starts</label>
                <div className='input'>
                    <Calendar name="opens" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={this.state.date?.toDate() ?? ''}></Calendar>
                </div>
            </div>
          </div>
          <div className="alignright">
            <Button label="Skip" icon="pi pi-fast-forward" className="p-button-secondary p-button-raised p-button-text" onClick={this.skip} />
            <Button label="Save" icon="pi pi-save" className="p-button-primary p-button-raised p-button-text" onClick={this.save} />
          </div>
      </div>
        );
    }
}

