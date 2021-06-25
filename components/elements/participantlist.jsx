import { is_hod  } from '../functions';

export function ParticipantList(props) {
    if (!props.fencers) {
        return (<div></div>);
    }

    // Roles are assigned over the whole event now, and not on a specific sideevent. This
    // means we show roles only if this is not a list of a specific event and we actually 
    // have a roles list
    var showRoles = props.roles && !props.event;
    var roleById = {};
    if(props.roles) {
        props.roles.map((rl) => {
            roleById["r" + rl.id] = rl;
        });
    }
    roleById["r0"] = { name: "Participant" };
    if(props.event && parseInt(props.event.competition_id) > 0) {
        roleById["r0"] = { name: "Athlete" };
    }

    var eventById={};
    if(props.events) {
        props.events.map((evt) => {
            eventById["e"+evt.id]=evt;
        });
    }

    var cmpById = {};
    for (var i in props.competitions) {
        var c = props.competitions[i];
        cmpById["k" + c.id] = c;
    }
    var catById = {};
    for (var i in props.categories) {
        var c = props.categories[i];
        catById["k" + c.id] = c;
    }

    var fencers=Object.keys(props.fencers).map((key) => {
        var fencer=props.fencers[key];
        fencer.is_registered=!props.event; // if we are not filtering on event, always registered
        fencer.role=[];
        fencer.has_role = [];
        fencer.reg_cat=-1;
        fencer.incorrect_cat = false;

        fencer.registrations.map((reg) => {
            // Requirement 1.1.5: display event roles, or, for role 0 (participant), display
            //    the event title
            var comp = null;
            var role = parseInt(reg.role);
            if (props.event && reg.sideevent == props.event.id) {
                // this is a fencer participating in a specific event (competition or other side-event)
                fencer.sideevent = reg;
                fencer.is_registered = true;
                comp = cmpById["k" + props.event.competition_id];

                // determine the roleof the fencer for this specific event.
                fencer.role.push(role);
                fencer.has_role.push(roleById["r" + role] ? roleById["r" + role].name : "");
            }
            else if (!props.event) {
                // if we are not filtering out fencers, summarise all registrations
                var regFor = eventById["e" + reg.sideevent];
                if (roleById["r" + reg.role] && regFor && parseInt(regFor.competition_id) > 0) {
                    // role for a specific competition
                    fencer.role.push(role);

                    // generic participant/athlete: push the event title
                    if(role == 0) {
                        fencer.has_role.push(regFor.title);
                    }
                    else {
                        // specific event role, push the actual role name + event title
                        // note: this is currently dead code: we cannot assign a role to a specific
                        // event at this time
                        var rname = roleById["r" + role] ? roleById["r" + role].name : "";
                        rname += " (" + regFor.title + ")";
                        fencer.has_role.push(rname);
                    }
                    comp = cmpById["k" + regFor.competition_id];
                }
                else {
                    var role = parseInt(reg.role);
                    fencer.role.push(role);
                    var rname = roleById["r" + role] ? roleById["r" + role].name : "";
                    if(regFor && role == 0) {
                        // participation in a side event, list the title
                        fencer.has_role.push(regFor.title);
                    }
                    else if(rname != "") {
                        fencer.has_role.push(rname);
                    }
                }
            }

            // Requirement 1.1.6: events with a mismatch in category are marked
            if (comp && catById["k" + comp.category]) {
                fencer.reg_cat = catById["k" + comp.category].value;

                // mark the incorrect-category error only for competition events
                if (fencer.role.includes(0) && parseInt(fencer.reg_cat) != parseInt(fencer.category_num) && parseInt(fencer.reg_cat) > 0) {
                    fencer.incorrect_cat = true;
                }
            }
        });

        return fencer;
    }).filter((fencer) => {
        return fencer.is_registered;
    });

    // sort based on role (athletes or non-athletes) and name
    fencers.sort(function (a1, a2) {
            if(props.event) {
                if (a1.has_role.includes("Athlete") && !a2.has_role.includes('Athlete')) return -1;
                if (a2.has_role.includes("Athlete") && !a1.has_role.includes("Athlete")) return 1;
                return a1.fullname > a2.fullname;
            }

            if(a1.has_role.length > 0 && a2.has_role.length == 0) return -1;
            if (a2.has_role.length > 0 && a1.has_role.length == 0) return 1;
            return a1.fullname > a2.fullname;
        });

    // the roles column is now shown when we have no side-event. 
    // if we have sideevent-specific roles, we should show roles as well on the side-event lists
    // (and perhaps no longer on the overall list)
    // The roles will include the events for regular participants, so no need to display the events
    // as a column. If we move to sideevent-specific roles, we might want to rename the column header
    // to Event/Role in that case for the overall list.
    return (
        <table className='style-stripes'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>First name</th>
                    {!is_hod() && (
                        <th>Country</th>
                    )}
                    <th>Gender</th>
                    <th>Birthyear</th>
                    <th>Category</th>
                    {showRoles && (<th>Role</th>)}
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {fencers.map((fencer,idx) => (
                        <tr key={idx} className={fencer.incorrect_cat ? "incorrect-cat": ""}>
                            <td>{fencer.name}</td>
                            <td>{fencer.firstname}</td>
                            {!is_hod() && (
                                <td>{fencer.country_name}</td>
                            )}
                            <td>{fencer.fullgender}</td>
                            <td>{fencer.birthyear}</td>
                            <td>{fencer.category}</td>
                            {showRoles && (<td>{fencer.has_role.map((rl,idx) => (<span key={idx}>{idx>0 && (", ")}{rl}</span>))}</td>)}
                            {props.camera && (<td>
                                {fencer.picture == 'Y' && (<span className='pi pi-camera blue'></span>)}
                                {fencer.picture == 'A' && (<span className='pi pi-camera green'></span>)}
                                {fencer.picture == 'R' && (<span className='pi pi-camera red'></span>)}
                                {!['Y','A','R'].includes(fencer.picture) && (<span className='pi pi-times red'></span>)}
                            </td>)}
                            <td><a onClick={(e) => props.onEdit(fencer)}><i className='pi pi-pencil'></i></a></td>
                            <td><a onClick={(e) => props.onSelect(fencer)}><i className='pi pi-chevron-circle-right'></i></a></td>
                        </tr>
                    ))}
            </tbody>
        </table>
    );
}