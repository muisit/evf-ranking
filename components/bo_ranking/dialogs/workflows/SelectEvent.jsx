import React from 'react';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { Calendar } from 'primereact/calendar';
import { Button } from 'primereact/button';
import { workflow, error_handler } from "../../../api.js";
import { is_valid, is_valid_date, parse_date, format_date } from '../../../functions.js';

export default class SelectEvent extends React.Component {
    constructor(props, context) {
        super(props, context);

        this.state = this.selectDefaultValues(this.props.value?.sandbox?.selectedEvent ?? -1);
    }

    componentDidUpdate = () => {
        if (this.state.event_id == 0 && is_valid(this.props.value.sandbox.selectedEvent)) {
            const values = this.selectDefaultValues(this.props.value.sandbox.selectedEvent);
            // this causes another componentDidUpdate, but now event_id <> 0
            this.setState(values);
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }    

    save = () => {
        // validation
        if (this.state.name.trim().length == 0) {
            alert('Name is required');
            return;
        }
        if (this.state.location.trim().length == 0) {
            alert('Location is required');
            return;
        }
        if (!is_valid(this.state.country)) {
            alert('Country is required');
            return;
        }
        if (!is_valid(this.state.type)) {
            alert('Type is required');
            return;
        }
        if (!is_valid_date(this.state.date)) {
            alert('Date is required');
            return;
        }

        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'save_event',
            event: {
                id: this.state.event_id,
                name: this.state.name,
                location: this.state.location,
                date: format_date(this.state.date),
                country: this.state.country,
                type: this.state.type
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
            case 'event':
                const eventId = parseInt(value);
                const values = this.selectDefaultValues(eventId);
                this.setState(values);
                break;
            case 'type':
                this.setState({type: parseInt(value)});
                break;
            case 'name':
                this.setState({name: value});
                break;
            case 'location':
                this.setState({location: value});
                break;
            case 'date':
                this.setState({date: parse_date(value)});
                break;
            case 'country':
                this.setState({country: parseInt(value)});
                break;
        }
    }

    selectDefaultValues = (peventId) => {
        const eventId = peventId || this.props.value.sandbox.selectedEvent;
        const selectedEvents = this.props.value.sandbox.events.filter((item) => item.id == eventId);
        const selectedEvent = selectedEvents.length > 0 ? selectedEvents[0] : {};
        return {
            event_id: eventId,
            name: selectedEvent?.name ?? (this.props.value.sandbox.eventData?.name ?? ''),
            location: selectedEvent?.location ?? (this.props.value.sandbox.eventData?.location ?? ''),
            date: parse_date(selectedEvent?.date ?? (this.props.value.sandbox.eventData?.date ?? '')),
            type: selectedEvent?.type_id ?? -1,
            country: selectedEvent?.country_id ?? (this.props.value.sandbox.eventData?.country ?? -1)
        };
    }

    render() {
        const events = this.props.value.sandbox.events?.sort(function (a,b) {
            const dt1 = parse_date(a.date);
            const dt2 = parse_date(b.date);
            if (dt1.diff(dt2) !== 0) {
                return dt1 > dt2 ? -1 : 1;
            }
            return  a.id > b.id ? -1 : 1;
        }).map((item) => {
            return {id: item.id, name: item.name};
        }) ?? [];
        events.unshift({id: '-1', name:'New event'});
        const types = this.props.data?.eventtypes?.map((item) => {
            return {id: item.id, name: item.name};
        }) ?? [];
        const countries = this.props.data?.countries?.map((item) => {
            return {id: item.id, name: item.name};
        }) ?? [];

        return (
      <div>
        <div className='input-form'>
            <div>
                <label>Event</label>
                <div className="input">
                    <Dropdown className='evntdrop' appendTo={document.body} name="event" onChange={this.onChangeEl} optionLabel="name" optionValue="id" value={this.state.event_id} options={events} placeholder="Event" />
                </div>
            </div>
            <div>
                <label>Name</label>
                <div className='input'>
                    <InputText name='name' value={this.state.name} onChange={this.onChangeEl} placeholder='Name'/>
                </div>
            </div>
            <div>
                <label>Location</label>
                <div className='input'>
                    <InputText name='location' value={this.state.location} onChange={this.onChangeEl} placeholder='Location'/>
                </div>
            </div>
            <div>
                <label>Country</label>
                <div className='input'>
                    <Dropdown optionLabel="name" optionValue="id" value={this.state.country} options={countries} placeholder="Country" onChange={this.onChangeEl} name='country'/>
                </div>
            </div>
            <div>
                <label>Type</label>
                <div className='input'>
                    <Dropdown optionLabel="name" optionValue="id" value={this.state.type} options={types} placeholder="Type" onChange={this.onChangeEl} name='type'/>
                </div>
            </div>
            <div>
                <label>Starts</label>
                <div className='input'>
                    <Calendar name="opens" appendTo={document.body} onChange={this.onChangeEl} dateFormat="yy-mm-dd" value={this.state.date?.toDate() ?? ''}></Calendar>
                </div>
            </div>
          </div>
          <div className="alignright"><Button label="Save" icon="pi pi-save" className="p-button-primary p-button-raised p-button-text" onClick={this.save} /></div>
      </div>
        );
    }
}

