import React from 'react';
import { upload_file, fencer } from "../../api.js";
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { parse_net_error, parse_date, date_to_category, date_to_category_num, format_date, is_valid, is_organisation } from '../../functions';
import { createRegistrationList } from '../../models/registrationlist.js';
import { InvalidCSVImport } from '../data/invalidcsvimport.jsx';
import { ValidCSVImport } from '../data/validcsvimport.jsx';
import { CheckedFencerList } from '../data/checkedfencerlist.jsx';
import { ImportFencerList } from '../data/importfencerlist.jsx';
import SuggestionDialog from './suggestiondialog';
import ReplaceDialog from './replacedialog.jsx';
import { defaultPayment } from "../../lib/defaultPayment";
import { useJobQueue } from '../../models/jobqueue.js';
import { createFencerJob } from '../../models/jobs/createFencer.js';
import { createRegistrationJob } from '../../models/jobs/createRegistration.js';
import { createDummyJob } from '../../models/jobs/dummyJob.js';

export default class CSVUploadDialog extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            step: "upload",
            contents: [],
            headerFields: {
                "h0": "last",
                "h1": "first",
                "h2": "gender",
                "h3": "dob",
                "h4": "events"
            },
            skipRows: '0',
            fencers: [],
            currentCheck: 0,
            activeSuggestions: [],
            activeIndex: -1,
            activeFencer: {},
            displaySuggestions: false,
            queue: {}
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    close = () => {
        this.setState({step: "upload" });
        if(this.props.onClose) this.props.onClose();
    }

    save = (item) => {
        if(this.props.onSave) this.props.onSave(item);
        this.close();
    }

    onCancelDialog = (event) => {
        this.close();
    }

    onCloseDialog = (event) => {
        if (this.state.step == "upload") {
            alert("Please select a file to upload using the file selection button");
        }
        else if(this.state.step == "select") {
            if (this.minimalColumnsSelected()) {
                this.setState({step: "check", fencers: this.importFields()}, () => this.validateFencerData());
            }
            else {
                alert("Please select at least lastname, firstname, gender and birthdate columns");
            }
        }
        else if (this.state.step == "check") {
            if (this.allRowsWithoutError()) {
                if (this.allRowsChecked()) {
                    this.resolveEvents();
                    this.setState({step: "check2"});
                }
                else {
                    alert("Please pick the right suggestion for each pending fencer registration");
                }
            }
            else {
                alert("Some rows contain invalid data. Please correct the CSV and retry the import");
            }
        }
        else if (this.state.step == 'check2') {
            if (this.allRegistrationsWithoutError()) {
                this.convertToRegistrations();
                this.setState({step: "import"});
            }
            else {
                alert('Some registrations are invalid. Either correct the CSV of pick the right suggestions');
            }
        }
        else {
            this.save();
        }
    }

    convertToRegistrations = () => {
        var queue = useJobQueue();

        var fencers = this.state.fencers.map((fencer) => {
            queue.add(this.addFencerCreationJob(fencer));
            fencer.state = 'Pending';
            return fencer;
        });
        this.setState({queue: queue, fencers: fencers }, ()=> { this.state.queue.run() });
    }

    afterFencerCreation = (json, newModel, fencer) => {
        if (newModel && is_valid(newModel.id)) fencer.id = newModel.id;
        var payment = defaultPayment(this.props.basic.countriesById['c' + this.props.country], fencer);

        fencer.reglist.registrations.map((reg) => {
            var model = { 
                id: -1,
                fencer: fencer.id, 
                event: this.props.basic.event.id, 
                sideevent: reg.sideevent,
                role: reg.role,
                team: null,
                payment: payment,
                country: this.props.country
            };
            model.fencerObject = fencer;
            this.state.queue.add(createRegistrationJob(model, this.afterRegistrationCreation));
        });
        fencer.state = 'Imported';
        this.setState((state,props) => this.replaceFencer(state,fencer));
        if (!this.state.queue.isRunning) {
            this.state.queue.run();
        }
    }

    replaceFencer = (state, fencer) => {
        for (var f in state.fencers) {
            var fObj = state.fencers[f];
            if (fObj && fObj.index == fencer.index) {
                fObj.id = fencer.id;
                fObj.state = fencer.state;
            }
        }
        return { fencers: state.fencers };
    }

    afterRegistrationCreation = (json, newModel, reg) => {
        newModel.wasImported = true;
        this.setState((state,props) => this.replaceRegistration(state, reg.fencerObject, newModel));
    }

    replaceRegistration = (state, fencer, reg) => {
        for(var f in state.fencers) {
            var fObj = state.fencers[f];
            if (fObj && fObj.index == fencer.index) {
                fObj.reglist.replace(reg);
            }
        }
        return { fencers: state.fencers };
    }

    addFencerCreationJob = (fencerModel) => {
        if (is_valid(fencerModel.id)) {
            return createDummyJob(() => {
                this.afterFencerCreation({}, null, fencerModel);
            });
        }
        else {
            if (!fencerModel.picture) fencerModel.picture = 'N';
            if (fencerModel.gender == 'W') fencerModel.gender = 'F';
            return createFencerJob(fencerModel, this.afterFencerCreation);
        }
    }

    resolveEvents = () => {
        var fencers = this.state.fencers.map((fencer) => {
            return this.resolveEventsForFencer(fencer);
        });

        if (this.state.activeFencer && this.state.activeFencer.reglist) {
            // see if the active replacement fencer has no pending registrations left
            var hasanysuggestion = false;
            this.state.activeFencer.reglist.loop((reg) => {
                if (reg.suggestion && reg.suggestion !== null) {
                    hasanysuggestion = true;
                }
            });
            if (!hasanysuggestion) {
                this.closeEventCheckDialog();
            }
        }
        this.setState({fencers: fencers});
    }

    resolveEventsForFencer = (fencer) => {
        var regs = fencer.reglist.loop((reg) => {
            reg.error = '';
            reg.suggestion = null;
            reg.wasImported=false;
            if (!is_valid(reg.sideevent) && !is_valid(reg.role) && reg.weaponId && reg.weapon) {
                var weapon = null;
                if (is_valid(reg.weaponId) && this.props.basic.weaponsById['w' + reg.weaponId]) {
                    weapon = this.props.basic.weaponsById['w' + reg.weaponId];
                }
                else {
                    // combine the weapon and the fencer gender into a weapon abbreviation
                    var combined = ((fencer.gender == 'M' ? 'M' : 'W') + reg.weapon).toUpperCase();
                    this.props.basic.weapons.map((wpn) => {
                        if (wpn.abbr == combined) weapon = wpn;
                    });
                }
                return this.matchWeapon(reg, weapon, fencer);
            }
            else if (is_valid(reg.sideevent)) {
                return this.matchEvent(reg, this.props.basic.sideeventsById['s' + reg.sideevent], fencer);
            }
            return reg;
        });
        fencer.reglist.registrations = regs;
        return fencer;
    }

    matchWeapon = (reg, weapon, fencer) => {
        if (weapon === null) {
            reg.error = 'no such weapon';
            return reg;
        }
        else {
            var catnum = date_to_category_num(fencer.birthday, this.props.basic.event.opens);
            var compabbr = weapon.abbr + catnum;

            var se = null;
            this.props.basic.sideevents.map((event) => {
                if (event.abbreviation == compabbr) {
                    se = event;
                }
            });
            return this.matchEvent(reg, se, fencer);
        }
    }

    matchEvent = (reg, event, fencer) => {
        if (!event) {
            reg.error = 'no such event';
        }
        else if (event.competition) { // only check gender and category for competitions
            var catnum = date_to_category_num(fencer.birthday, this.props.basic.event.opens);
            if (!event.category || catnum != event.category.value) {
                reg.error = 'incorrect category';
                reg.suggestion = this.replacementEvent(fencer, event.weapon);
            }

            if (!event.weapon || event.weapon.gender != fencer.gender) {
                reg.error = 'incorrect gender';
                reg.suggestion = this.replacementEvent(fencer, event.weapon);
            }
            else {
                reg.sideevent = event.id;
                reg.role = 0;
            }
        }
        return reg;
    }

    replacementEvent = (fencer, weapon) => {
        var compabbr = (fencer.gender == 'M' ? 'M' : 'W') + weapon.abbr[1] + date_to_category_num(fencer.birthday, this.props.basic.event.opens);
        var event = null;
        this.props.basic.sideevents.map((se) => {
            if (se.abbreviation == compabbr) {
                event = se;
            }
        });
        return event;
    }

    allRegistrationsWithoutError = () => {
        var alltrue = true;
        this.state.fencers.map((fencer) => {
            var noerrors = true;
            fencer.reglist.loop((reg) => {
                if (reg.error && reg.error.length>0) noerrors=false;
            })
            alltrue = noerrors && alltrue;
        });
        return alltrue;
    }

    allRowsWithoutError = () => {
        var alltrue = true;
        this.state.fencers.map((fencer) => {
            alltrue = (!fencer.error || fencer.error.length==0) && alltrue;
        });
        return alltrue;
    }

    allRowsChecked = () => {
        var alltrue = true;
        this.state.fencers.map((fencer) => {
            alltrue = fencer.wasPicked && alltrue;
        });
        return alltrue;
    }

    minimalColumnsSelected = () => {
        var colsRequired = {};
        var countryColumn = false;
        Object.keys(this.state.headerFields).map((fld) => {
            var value = this.state.headerFields[fld];
            if (['last','first','dob','gender'].includes(value)) {
                colsRequired[value] = true;
            }
            if (value == 'country') countryColumn = true;
        });
        if (Object.keys(colsRequired).length == 4) {
            return is_valid(this.props.country) || countryColumn;
        }
        return false;
    }

    importFields = () => {
        var offset = parseInt(this.state.skipRows);
        if (isNaN(offset)) {
            offset = 0;
        }

        var mappings = this.createMappings();
        var fencers = [];
        for(var i = offset;i < this.state.contents.length; i++) {
            var fencer = this.importFencer(this.state.contents[i], i, mappings);
            if (fencer !== null) {
                fencers.push(fencer);
            }
        }
        return fencers;
    }

    createMappings = () => {
        var eventsByAbbreviation = {};
        this.props.basic.sideevents.map((se) => {
            eventsByAbbreviation[se.abbreviation.toLowerCase()] = se;
        });
        var rolesByName = {};
        this.props.basic.roles.map((role) => {
            rolesByName[role.name.toLowerCase()] = role;
        });
        var wpnByName = {};
        this.props.basic.weapons.map((wpn) => {
            wpnByName[wpn.abbr.toLowerCase()] = wpn;
        });
        return { events: eventsByAbbreviation, roles: rolesByName, weapons: wpnByName};
    }

    importFencer = (fields, lineNo, mappings) => {
        var fencer = {
            index: lineNo,
            name: '',
            firstname: '',
            birthday: '',
            gender: 'M',
            lastNameCheck: 'nok',
            firstNameCheck: 'nok',
            birthdayCheck: 'nok',
            countryCheck: 'nok',
            suggestions: [],
            wasChecked: false,
            country: this.props.country,
            countryAbbreviation: is_valid(this.props.country) ? this.props.basic.countriesById['c'+this.props.country].abbreviation : '',
            reglist: createRegistrationList()
        }

        for (var i=0;i<fields.length;i++) {
            var key = 'h' + i;
            if (this.state.headerFields[key]) {
                switch(this.state.headerFields[key]) {
                    case 'skip': break;
                    case 'last': fencer.name = fields[i]; break;
                    case 'first': fencer.firstname = fields[i]; break;
                    case 'gender': fencer.gender = fields[i]; break;
                    case 'dob': fencer.birthday = fields[i]; break;
                    case 'country': fencer.countryAbbreviation = fields[i]; break;
                    case 'events': this.parseEvents(fields[i], fencer, lineNo, mappings); break;
                    default:
                        // all other values are supposedly valid side events
                        if (fields[i] && fields[i].trim().length > 0) {
                            this.parseSingleEvent({eventId: this.state.headerFields[key]}, fencer, lineNo);
                        }
                        break;
                }
            }
        }
        if (fencer.name == '' || fencer.name === null) {
            return null;
        }
        return fencer;
    }

    parseEvents = (events, fencer, lineNo, mappings) => {
        if (events.includes(",")) {
            events = events.split(",");
        }
        else if (events.includes(";")) {
            events = events.split(";");
        }
        else {
            events = [events];
        }

        if (events && events.length > 0 && events.map) {
            events.map((ev) => {
                var key = ev.trim().toLowerCase();
                if (mappings.events[key]) {
                    this.parseSingleEvent({eventId: mappings.events[key].id}, fencer, lineNo);
                }
                else if(mappings.roles[key]) {
                    this.parseSingleEvent({roleId: mappings.roles[key].id}, fencer, lineNo);
                }
                else if(mappings.weapons[key]) {
                    this.parseSingleEvent({weaponId: mappings.weapons[key].id, weapon: mappings.weapons[key].abbr[1]}, fencer, lineNo);
                }
                else if (['f','e','s'].includes(key)) {
                    this.parseSingleEvent({weaponId: -1, weapon: key.toUpperCase()}, fencer, lineNo);
                }
                else {
                    alert("Could not find event or role named '" + ev + "' on line " + lineNo + ' (fencer ' + fencer.name + ", " + fencer.firstname + ")");
                }
            });
        }
    }

    parseSingleEvent = (idlist, fencer, lineNo) => {
        if (idlist.eventId && this.props.basic.sideeventsById['s' + idlist.eventId]) {
            var registration = fencer.reglist.create(idlist.eventId, 0);
            fencer.reglist.add(registration);
        }
        else if (idlist.roleId && this.props.basic.rolesById['r' + idlist.roleId]) {
            var registration = fencer.reglist.create(0, idlist.roleId);
            fencer.reglist.add(registration);
        }
        else if(idlist.weaponId || idlist.weapon) {
            var registration = fencer.reglist.create(0, 0);
            registration.weaponId = idlist.weaponId;
            registration.weapon = idlist.weapon;
            fencer.reglist.add(registration);
        }
        else {
            alert("Could not find event marked as column on line " + lineNo);
        }
    }

    validateFencerData = () => {        
        this.setState({currentCheck: 0}, () => this.checkResults());
    }

    checkResults = () => {
        // check all fencer data
        var start=this.state.currentCheck;
        if(this.state.fencers.length < start) {
            return false;
        }

        var checkme=[];
        for(var i = start; i < this.state.fencers.length && i < (start+10); i++) {
            checkme.push({
                index: i,
                name: this.state.fencers[i].name,
                firstname: this.state.fencers[i].firstname,
                birthday: this.state.fencers[i].birthday,
                gender: this.state.fencers[i].gender,
                country: this.state.fencers[i].countryAbbreviation
            });
        }        

        this.setState({currentCheck: start+10},() => {
            fencer('importcheck',{
                fencers: checkme,
                event: this.props.basic.event.id,
                country: this.props.country
            })
            .then((res) => {
                if (res.data.result && res.data.result.length) {
                    for (var i = 0; i < res.data.result.length; i++) {
                        this.displayResultsForIndexedEntry(res.data.result[i]);
                    }
                }
                setTimeout(this.checkResults, 10);
            })
        });
    }

    displayResultsForIndexedEntry = (data) => {
        var index = data.index;
        var fencers = this.state.fencers;
        if (fencers.length > index) {
            var fencer = fencers[index];
            fencer.id = -1;
            fencer.name = data.name;
            fencer.lastNameCheck = data.lastNameCheck || 'err';
            fencer.firstname = data.firstname;
            fencer.firstNameCheck = data.firstNameCheck || 'err';
            fencer.gender = data.gender;
            fencer.country = data.country;
            fencer.countryCheck = data.countryCheck || 'err';
            fencer.birthday = data.birthday;
            fencer.birthdayCheck = data.birthdayCheck || 'err';
            fencer.suggestions = data.suggestions;
            fencer.wasChecked = (!data.error || data.error.length == 0) && fencer.lastNameCheck != 'err' && fencer.firstNameCheck != 'err' && fencer.countryCheck != 'err' && fencer.birthdayCheck != 'err';
            fencer.wasPicked = false;
            fencer.error = data.error || null;
            fencer.state = 'New';
            if (is_valid(data.id) && data.suggestions && data.suggestions.length == 1) {               
                fencer.wasPicked = true;
                fencer.state = 'Found';
                fencer.id = data.suggestions[0].id;
                fencer.name = data.suggestions[0].name;
                fencer.firstname = data.suggestions[0].firstname;
                fencer.country = data.suggestions[0].country;
                fencer.birthday = data.suggestions[0].birthday;
                fencer.picture = data.suggestions[0].picture;
            }
            else if (fencer.suggestions && fencer.suggestions.length > 0) {
                fencer.state = 'Pending';
            }
            else {
                fencer.wasPicked = true;
                fencer.state = 'New';
            }
            fencers[index] = fencer;
        }
        this.setState({fencers: fencers});
    }

    onFileChange = (event) => {
        var selectedFile=event.target.files[0];
        upload_file("registrations",selectedFile, {
            event: this.props.basic.event ? this.props.basic.event.id : -1,
            type: "csv"
        })
            .then((json) => {
                this.setState({contents: json.data.model, step: "select"});
            })
            .catch((err) => parse_net_error(err));        
    }

    setHeader = (index, value) => {
        var headerFields = this.state.headerFields;
        headerFields['h' + index] = value;
        this.setState({headerFields: headerFields});
    }

    getHeader = (index) => {
        var key='h' + index;
        if (!this.state.headerFields[key]) return 'skip';
        return this.state.headerFields[key];
    }

    openSuggestions = (index, suggestions) => {
        suggestions = suggestions.slice();
        suggestions.unshift({name: 'new entry', id: -1, firstname: '', country: -1, birthday: ''});
        this.setState({activeIndex: index, activeSuggestions: suggestions, displaySuggestions: true});
    }

    closeSuggestions = () => {
        this.setState({activeIndex: -1, activeSuggestions: [], displaySuggestions: false});
    }

    pickSuggestion = (item) => {
        var fencers = this.state.fencers;
        if (fencers.length > this.state.activeIndex && this.state.activeIndex >= 0) {
            var fencer = fencers[this.state.activeIndex];
            if (!is_valid(item.id)) {
                fencer.state = 'New';
                fencer.wasPicked=true;
            }
            else {
                fencer.name = item.name;
                fencer.id = item.id;
                fencer.firstname = item.firstname;
                fencer.birthday = item.birthday;
                fencer.country = item.country;
                fencer.state = 'Selected';
                fencer.wasPicked=true;
            }

            fencers[this.state.activeIndex] = fencer;
        }
        this.setState({fencers: fencers, displaySuggestions: false, activeIndex: -1});
    }

    openEventCheckDialog = (fencer) => {
        this.setState({activeIndex: fencer.index, activeFencer: fencer, displayEventCheck: true});
    }

    closeEventCheckDialog = (fencer) => {
        this.setState({activeIndex: -1, activeFencer: {}, displayEventCheck: false});
    }

    pickReplacement = (item) => {
        var fencers = this.state.fencers.map((fencer) => {
            if (fencer.index == item.index) {
                return item;
            }
            return fencer;
        });
        this.setState({fencers: fencers}, () => this.resolveEvents());
    }
/*
    onValidImport = () => {
        var data = ImportFencerList();
        var fencers = data.fencers.map((fencer) => {
            var reglist = createRegistrationList();
            reglist.registrations = fencer.reglist.registrations;
            fencer.reglist = reglist;
            return fencer;
        });
        this.setState({fencers: fencers, step: "check2"});
    }

    onValidEvents = () => {
        var data = CheckedFencerList();
        var fencers = data.fencers.map((fencer) => {
            var reglist = createRegistrationList();
            reglist.registrations = fencer.reglist.registrations;
            fencer.reglist = reglist;
            return fencer;
        });
        this.setState({fencers: fencers, step: "check"});
    }

    onInvalidCSV = () => {
        var data = InvalidCSVImport();
        this.setState({contents: data.contents, headerFields: data.headers, skipRows: data.skipRows, step: "select"});
    }

    onValidCSV = () => {
        var data = ValidCSVImport();
        this.setState({contents: data.contents, headerFields: data.headers, skipRows: data.skipRows, step: "select"});
    }
*/
    render() {
        var label='Continue';
        switch (this.state.step)
        {
        case 'upload': label = 'Upload'; break;
        case 'select': label = 'Check Fencers'; break;
        case 'check': label = 'Check Events'; break;
        case 'check2': label = 'Import'; break;
        case 'finish': label = 'Close'; break;
        }

        //<Button label="SkipImport" icon="pi" className="p-button-warning p-button-raised p-button-text" onClick={this.onValidImport} />
        //<Button label="SkipEvents" icon="pi" className="p-button-warning p-button-raised p-button-text" onClick={this.onValidEvents} />
        //<Button label="InvalidCSV" icon="pi" className="p-button-warning p-button-raised p-button-text" onClick={this.onInvalidCSV} />
        //<Button label="ValidCSV" icon="pi" className="p-button-warning p-button-raised p-button-text" onClick={this.onValidCSV} />
        var footer=(<div>
            <Button label="Cancel" icon="pi pi-times" className="p-button-warning p-button-raised p-button-text" onClick={this.onCancelDialog} />
            <Button label={label} icon="pi pi-check" className="p-button-raised" onClick={this.onCloseDialog} />
        </div>);

        return (<Dialog header="Upload CSV" position="center" visible={this.props.display} className="fencer-dialog" style={{ width: this.props.width || '50vw' }} modal={true} footer={footer} onHide={this.onCancelDialog} baseZIndex={1500}>
        {this.state.step == "upload" && this.renderUploadButton()}
        {this.state.step == "select" && this.renderColumnSelector()}
        {this.state.step == "check" && this.renderFencerCheckTable()}
        {this.state.step == "check2" && this.renderEventCheckTable()}
        {this.state.step == "import" && this.renderImportTable()}
        <SuggestionDialog display={this.state.displaySuggestions} basic={this.props.basic} suggestions={this.state.activeSuggestions} onSelect={(item) => this.pickSuggestion(item)} onClose={() => this.closeSuggestions()}/>
        <ReplaceDialog display={this.state.displayEventCheck} basic={this.props.basic} fencer={this.state.activeFencer} onSelect={(item) => this.pickReplacement(item)} onClose={() => this.closeEventCheckDialog()}/>
    </Dialog>);
    }

    renderUploadButton() {
        return (
        <div className="grid">
            <div className="col-12">
                <div className="p-inputgroup">
                    <input type="file" onChange={this.onFileChange}/>
                </div>
            </div>
        </div>
        );
    }

    renderColumnSelector() {
        var maxcolsize = 0;
        this.state.contents.map((row) => {
            if (row.length && row.length > maxcolsize) {
                maxcolsize = row.length;
            }
        });
        var indexes = [0,1,2,3,4,'none', this.state.contents.length - 3, this.state.contents.length - 2, this.state.contents.length - 1];
        if (this.state.contents.length < 10) {
            indexes = [];
            for (var i=0;i<10;i++) {
                if (this.state.contents.length > i) indexes.push(i);
            }
        }

        var headers = [
            {text: 'Skip', value: 'skip'},
            {text: 'Last name', value: 'last'},
            {text: 'First name', value: 'first'},
            {text: 'Gender', value: 'gender'},
            {text: 'Birthdate', value: 'dob'},
            {text: 'Events', value: 'events'}
        ];
        if (is_organisation() && !is_valid(this.props.country)) {
            headers.push({text: 'Country', value: 'country'});
        }
        this.props.basic.sideevents.map((se) => {
            headers.push({text: se.abbreviation, value: se.id});
        });        

        var skipList=[{v:'0'},{v:'1'},{v:'2'},{v:'3'},{v:'4'},{v:'5'},{v:'6'},{v:'7'},{v:'8'},{v:'9'},{v:'10'}];
        return (
            <div>
                <div>
                    <span>Skip Rows:</span>
                    <Dropdown value={this.state.skipRows} onChange={(e) => this.setState({skipRows: e.value})} appendTo={document.body} optionLabel='v' optionValue='v' options={skipList} />
                </div>
                <table>
                    <thead>
                        <tr>
                            {Array.from(Array(maxcolsize), (e,i) => this.renderColumnHeader(i, headers))}
                        </tr>
                    </thead>
                    <tbody>
                        {indexes.map((idx) => this.renderIndexedCSVRow(idx, maxcolsize))}
                    </tbody>
                </table>
            </div>
        )
    }

    renderColumnHeader(index, headers) {
        var id="header-" + index;

        return (
        <th key={'hdr-' + index}>
            <Dropdown name={id} value={this.getHeader(index)} onChange={(e) => this.setHeader(index, e.value)} appendTo={document.body} optionLabel="text" optionValue="value" options={headers} />
        </th>);
    }

    renderIndexedCSVRow(index, maxcolsize) {
        if (index == 'none') {
            return (<tr key={'row-' + index}><td colSpan={maxcolsize} className="textcentered">...</td></tr>);
        }

        if (this.state.contents.length < index) return (null);

        return (
            <tr key={'row-' + index}>
                {Array.from(Array(maxcolsize), (e,i) => this.renderColumnCell(index, i))}
            </tr>
        );
    }

    renderColumnCell(index, colIndex) {
        if (this.state.contents.length < index) return (<td key={'cell-' + index + '-' + colIndex}></td>);

        if (this.state.contents[index].length < colIndex) return (<td key={'cell-' + index + '-' + colIndex}></td>);

        return (<td key={'cell-' + index + '-' + colIndex}>{this.state.contents[index][colIndex]}</td>);
    }

    renderFencerCheckTable() {
        return (
            <table className='resultranking'>
                <thead>
                    <tr>
                        <th>Last name</th>
                        <th>First name</th>
                        <th>Gender</th>
                        {!is_valid(this.props.country) && (<th>Country</th>)}
                        <th>Birthdate</th>
                        <th>Events & Roles</th>
                        <th>State</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {this.state.fencers.map((fencer, index) => this.renderFencerCheck(fencer, index))}
                </tbody>
            </table>
        );
    }

    renderFencerCheck(fencer, index) {
        var events = fencer.reglist.registrations.map((reg) => {
            if (this.props.basic.sideeventsById['s' + reg.sideevent]) {
                return this.props.basic.sideeventsById['s' + reg.sideevent].abbreviation;
            }
            if (this.props.basic.rolesById['r' + reg.role]) {
                return this.props.basic.rolesById['r' + reg.role].name;
            }
            if (is_valid(reg.weaponId) && this.props.basic.weaponsById['w' + reg.weaponId]) {
                return this.props.basic.weaponsById['w' + reg.weaponId].abbr;
            }
            if (!is_valid(reg.weaponId) && reg.weapon) {
                return reg.weapon;
            }
        });

        var resultText = 'A single matching person was found';
        var errorClass = 'und';
        var stateText = fencer.state || 'New';
        if (!fencer.wasPicked) {
            if (fencer.error && fencer.error.length) {
                errorClass = 'nok';
                resultText = fencer.error;
                stateText = 'Error';
            }
            else if (!fencer.wasChecked) {
                errorClass = 'nok';
                resultText = 'A problem was detected, please adjust the data';
                stateText = 'Error';
            }
            else if (fencer.suggestions.length == 0) {
                resultText = 'No match was found importing as new registration';
                errorClass = 'ok';
            }
            else {
                resultText = 'Please pick a suggestion from the list, or import as new registration';
                stateText = 'Pending';
                errorClass = 'und';
            }
        }
        else if (stateText == 'New' || stateText == 'Selected' || stateText == 'Found') {
            errorClass = 'ok';
        }
        else {
            errorClass = 'nok';
        }

        return (
            <tr key={'fencer-' + index} className={errorClass}>
                <td>{fencer.name}</td>
                <td>{fencer.firstname}</td>
                <td>
                    {fencer.gender == 'M' && 'M'}
                    {fencer.gender == 'F' && 'W'}
                </td>
                {!is_valid(this.props.country) && (
                    <td>
                        {is_valid(fencer.country) && this.props.basic.countriesById['c' + fencer.country] && this.props.basic.countriesById['c' + fencer.country].abbr}
                        {!is_valid(fencer.country) && "<no country>"}
                    </td>
                )}
                <td>
                    {format_date(fencer.birthday)}
                </td>
                <td>
                    {events.join(',')}
                </td>
                <td>
                    {stateText}
                </td>
                <td>                   
                    <Button icon="pi pi-info-circle" className="p-button-sm p-button-text right" tooltip={resultText}/>
                </td>
                <td>                   
                    {stateText == 'Pending' && (
                        <Button icon="pi pi-bars" className="p-button p-button-text button" label='Select' onClick={() => this.openSuggestions(index, fencer.suggestions)}/>    
                    )}
                </td>
            </tr>
        )
    }

    renderEventCheckTable() {
        return (
            <table className='resultranking'>
                <thead>
                    <tr>
                        <th>Last name</th>
                        <th>First name</th>
                        <th>Gender</th>
                        {!is_valid(this.props.country) && (<th>Country</th>)}
                        <th>Cat</th>
                        <th>Events</th>
                        <th>Roles</th>
                        <th>State</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {this.state.fencers.map((fencer, index) => this.renderEventCheck(fencer, index))}
                </tbody>
            </table>
        );
    }

    renderEventCheck(fencer, index) {
        var anyErrors = false;
        var anyFixable = false;
        var events = fencer.reglist.registrations.map((reg) => {
            if (reg.error) {
                if (reg.suggestion) {
                    anyFixable = true;
                }
                else {
                    anyErrors = true;
                }
            }
            if (this.props.basic.sideeventsById['s' + reg.sideevent]) {
                return this.props.basic.sideeventsById['s' + reg.sideevent].abbreviation;
            }
            return null;
        }).filter((entry) => (entry && entry.length));

        var roles = fencer.reglist.registrations.map((reg) => {
            if (this.props.basic.rolesById['r' + reg.role]) {
                return this.props.basic.rolesById['r' + reg.role].name;
            }
            return null;
        }).filter((entry) => (entry && entry.length));

        var resultText = 'A single matching registration was found';
        var errorClass = 'und';
        if (anyErrors) {
            errorClass = 'nok';
            resultText = 'Some events or roles are unavailable'
        }
        else if (anyFixable) {
            errorClass = 'und';
            resultText = 'Some events or roles need clarification';
        }
        else {
            errorClass = 'ok';
            resultText = 'All events and roles determined';
        }

        return (
            <tr key={'fencer-' + index} className={errorClass}>
                <td>{fencer.name}</td>
                <td>{fencer.firstname}</td>
                <td>
                    {fencer.gender == 'M' && 'M'}
                    {fencer.gender == 'F' && 'W'}
                </td>
                {!is_valid(this.props.country) && (
                    <td>
                        {is_valid(fencer.country) && this.props.basic.countriesById['c' + fencer.country] && this.props.basic.countriesById['c' + fencer.country].abbr}
                        {!is_valid(fencer.country) && "<no country>"}
                    </td>
                )}
                <td>
                    {date_to_category(fencer.birthday, this.props.basic.event.opens)}
                </td>
                <td>
                    {events.join(',')}
                </td>
                <td>
                    {roles.join(',')}
                </td>
                <td>                   
                    {errorClass != 'ok' && (<Button icon="pi pi-info-circle" className="p-button-sm p-button-text" tooltip={resultText}/>)}
                </td>
                <td>                   
                    {errorClass == 'und' && (
                        <Button icon="pi pi-bars" className="p-button p-button-text button" label='Specify' onClick={() => this.openEventCheckDialog(fencer)}/>    
                    )}
                </td>
            </tr>
        )
    }

    renderImportTable() {
        return (
            <table className='resultranking'>
                <thead>
                    <tr>
                        <th>Last name</th>
                        <th>First name</th>
                        <th>Gender</th>
                        {!is_valid(this.props.country) && (<th>Country</th>)}
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                    {this.state.fencers.map((fencer, index) => this.renderImport(fencer, index))}
                </tbody>
            </table>
        );
    }

    renderImport(fencer, index) {
        var allImported = true;
        fencer.reglist.registrations.map((reg) => {
            allImported = reg.wasImported && allImported;
        });

        var stateText = fencer.state;
        var errorClass = 'und';
        if (allImported) {
            errorClass = 'ok';
            stateText = 'Done'
        }

        return (
            <tr key={'fencer-' + index} className={errorClass}>
                <td>{fencer.name}</td>
                <td>{fencer.firstname}</td>
                <td>
                    {fencer.gender == 'M' && 'M'}
                    {fencer.gender == 'F' && 'W'}
                </td>
                {!is_valid(this.props.country) && (
                    <td>
                        {is_valid(fencer.country) && this.props.basic.countriesById['c' + fencer.country] && this.props.basic.countriesById['c' + fencer.country].abbr}
                        {!is_valid(fencer.country) && "<no country>"}
                    </td>
                )}
                <td>
                    {stateText}
                </td>
            </tr>
        )
    }

}

