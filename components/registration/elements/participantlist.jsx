import { is_hod_view } from '../../functions';
import { filterFencers } from './participantlist/filterfencers';
import { sortFencers } from './participantlist/sortFencers';
import { useState } from 'react';

export function ParticipantList(props) {
    const [sort, setSort] = useState(props.sort || "ni");

    if (!props.fencers || !props.basic) {
        return (<div></div>);
    }

    var showErrors=false; // debug setting
    // requirement 1.4.3: for specific team events, show the team name instead of the fencer category
    var showTeam = props.showTeam;

    // configuration setting: allow more than 1 team per competition. If so, we also store the team index
    // If not, we do not need to display the team name in the role, only the category
    var cfg = props.basic.event ? props.basic.event.config : {};
    var allow_more_teams = (cfg && cfg.allow_more_teams) ? true : false;

    var roleById = Object.assign({},props.basic.rolesById);
    roleById["r0"] = { name: "Athlete" };

    // if this is a team event, but only 1 team per country, do not show teams anyway
    if(!allow_more_teams) {
        showTeam=false;
    }

    var fencers=filterFencers(props.fencers, props.noErrors, roleById, props.basic);

    var sortingConfig = { allow_more_teams: allow_more_teams};
    fencers = sortFencers(fencers, sort, sortingConfig);

    var nameSort = determineSort(sort, 'n', 'N');
    var firstNameSort = determineSort(sort, 'f', 'F');
    var countrySort = determineSort(sort, 'c', 'C');
    var genderSort = determineSort(sort, 'g', 'G');
    var birthdaySort = determineSort(sort, 'd', 'D');
    var categorySort = determineSort(sort, 'a', 'A');
    var teamSort = determineSort(sort, 't', 'T');
    var roleSort = determineSort(sort, 'e', 'E');

    // the roles column is now shown when we have no side-event. 
    // if we have sideevent-specific roles, we should show roles as well on the side-event lists
    // (and perhaps no longer on the overall list)
    // The roles will include the events for regular participants, so no need to display the events
    // as a column. If we move to sideevent-specific roles, we might want to rename the column header
    // to Event/Role in that case for the overall list.
    return (
        <table className='style-stripes'>
            <thead className='with-sort'>
                <tr>
                    <th onClick={() => setSort(combineSort(sort, 'n', nameSort))}>
                        <div className='d-flex'>Name <i className={"inline pi pi-icon " + nameSort}></i></div>
                    </th>
                    <th onClick={() => setSort(combineSort(sort, 'f', firstNameSort))}>
                        <div className='d-flex'>First name <i className={"inline pi pi-icon " + firstNameSort}></i></div>
                    </th>
                    {props.showCountry && (<th onClick={() => setSort(combineSort(sort, 'c', countrySort))}>
                        <div className='d-flex'>Country  <i className={"inline pi pi-icon " + countrySort}></i></div>
                    </th>)}
                    <th onClick={() => setSort(combineSort(sort, 'g', genderSort))}>
                        <div className='d-flex'>Gender <i className={"inline pi pi-icon " + genderSort}></i></div>
                    </th>
                    <th onClick={() => setSort(combineSort(sort, 'd', birthdaySort))}>
                        <div className='d-flex'>YOB <i className={"inline pi pi-icon " + birthdaySort}></i></div>
                    </th>
                    {!showTeam && (<th onClick={() => setSort(combineSort(sort, 'a', categorySort))}>
                        <div className='d-flex'>Category <i className={"inline pi pi-icon " + categorySort}></i></div>
                    </th>)}
                    {showTeam && (<th onClick={() => setSort(combineSort(sort, 't', teamSort))}>
                        <div className='d-flex'>Team <i className={"inline pi pi-icon " + teamSort}></i></div>
                    </th>)}
                    {props.showRoles && (<th onClick={() => setSort(combineSort(sort, 'e', roleSort))}>
                        <div className='d-flex'>Role <i className={"inline pi pi-icon " + roleSort}></i></div>
                    </th>)}
                    {props.camera && (<th></th>)}
                    <th></th>
                    {!is_hod_view() && (<th></th>)}
                </tr>
            </thead>
            <tbody>
                {fencers.map((fencer,idx) => (
                        <tr key={idx} className={fencer.incorrect_cat ? "incorrect-cat": ""}>
                            <td>{fencer.name}{showErrors && fencer.error}</td>
                            <td>{fencer.firstname}</td>
                            {props.showCountry && (
                                <td>{fencer.country_name}</td>
                            )}
                            <td>{fencer.fullgender}</td>
                            <td>{fencer.birthyear}</td>
                            {!showTeam && (<td>{fencer.category}</td>)}
                            {showTeam && (<td>{fencer.has_team}</td>)}
                            {props.showRoles && (<td>{fencer.has_role.map((rl,idx) => (<span key={idx}>{idx>0 && (", ")}{rl}</span>))}</td>)}
                            {props.camera && (<td>
                                {fencer.picture == 'Y' && (<span className='pi pi-camera blue'></span>)}
                                {fencer.picture == 'A' && (<span className='pi pi-camera green'></span>)}
                                {fencer.picture == 'R' && (<span className='pi pi-camera red'></span>)}
                                {!['Y','A','R'].includes(fencer.picture) && (<span className='pi pi-times red'></span>)}
                            </td>)}
                            <td><a onClick={(e) => props.onEdit(fencer)}><i className='pi pi-pencil'></i></a></td>
                            {!is_hod_view() && (<td><a onClick={(e) => props.onSelect(fencer)}><i className='pi pi-chevron-circle-right'></i></a></td>)}
                        </tr>
                    ))}
            </tbody>
        </table>
    );
}

function determineSort(sortOrder, ascVal, descVal) {
    if (sortOrder.includes(ascVal)) return "pi-sort-up";
    if (sortOrder.includes(descVal)) return "pi-sort-down";
    return "pi-sort";
}

function combineSort(oldSort, sortChar, sortDir) {
    var ascVal = sortChar;
    var descVal = sortChar.toUpperCase();
    oldSort = oldSort.replace(ascVal,'').replace(descVal,'');

    if (sortDir == "pi-sort-up") {
        return descVal + oldSort;
    }
    return ascVal + oldSort;
}