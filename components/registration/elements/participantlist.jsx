import { is_hod_view } from '../../functions';
import { wrong_category } from '../rules/wrong_category';
import { wrong_gender } from '../rules/wrong_gender';
import { team_rule_grandveterans } from '../rules/team_rule_grandveterans';
import { team_rule_veterans } from '../rules/team_rule_veterans';

export function ParticipantList(props) {
    if (!props.fencers || !props.basic) {
        return (<div></div>);
    }

    var showErrors=false; // debug setting
    // requirement 1.4.3: for specific team events, show the team name instead of the fencer category
    var showTeam = false;

    // configuration setting: allow more than 1 team per competition. If so, we also store the team index
    // If not, we do not need to display the team name in the role, only the category
    var cfg = props.basic.event ? props.basic.event.config : {};
    var allow_more_teams = (cfg && cfg.allow_more_teams) ? true : false;

    var roleById = Object.assign({},props.basic.rolesById);
    roleById["r0"] = { name: "Participant" };
    if(props.event && props.event.competition) {
        roleById["r0"] = { name: "Athlete" };

        if(props.event.category && props.event.category.type == 'T') {
            showTeam=true;
        }
    }
    // if this is a team event, but only 1 team per country, do not show teams anyway
    if(!allow_more_teams) {
        showTeam=false;
    }

    var fencers=Object.keys(props.fencers).map((key) => {
        var fencer=props.fencers[key];
        fencer.is_registered=!props.event; // if we are not filtering on event, always registered
        fencer.role=[];
        fencer.has_role = [];
        fencer.incorrect_cat = false;
        fencer.has_team=false;

        fencer.registrations.map((reg) => {
            // Requirement 1.1.5: display event roles, or, for role 0 (participant), display
            //    the event title
            var comp = null;
            var role = parseInt(reg.role);
            if (props.event && reg.sideevent == props.event.id) {
                // this is a fencer participating in a specific event (competition or other side-event)
                fencer.sideevent = reg;
                fencer.is_registered = true;
                comp = props.event.competition;

                // determine the roleof the fencer for this specific event.
                fencer.role.push(role);
                fencer.has_role.push(roleById["r" + role] ? roleById["r" + role].name : "");
            }
            else if (!props.event) {
                // if we are not filtering out fencers, summarise all registrations
                var regFor = props.basic.sideeventsById["s" + reg.sideevent] || null;
                if (roleById["r" + reg.role] && regFor && regFor.competition) {
                    // role for a specific competition
                    fencer.role.push(role);

                    // generic participant/athlete: push the event title
                    if(role == 0) {
                        fencer.has_role.push(regFor.abbreviation);
                    }
                    else {
                        // specific event role, push the actual role name + event title
                        // note: this is currently dead code: we cannot assign a role to a specific
                        // event at this time
                        var rname = roleById["r" + role] ? roleById["r" + role].name : "";
                        rname += " (" + regFor.title + ")";
                        fencer.has_role.push(rname);
                    }
                    comp = regFor.competition;
                }
                else {
                    // role for the entire event
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

            // if we're not showing roles, this is the search-result. Do not display errors for search results
            // because the fencers in the search results do no necessarily make up a complete team, but they 
            // might already be selected
            if(!props.noErrors) {
                var ruleobject = {
                    event: props.event,
                    competition: comp,
                    fencer: fencer,
                    registration: reg,
                    fencers: props.fencers
                };

                // Requirement 1.1.6: events with a mismatch in category are marked
                fencer.error='';
                if(wrong_category(ruleobject)) {
                    fencer.incorrect_cat=true;
                    fencer.error="(C)";
                }
                if (wrong_gender(ruleobject)) {
                    fencer.incorrect_cat = true;
                    fencer.error = "(S)";
                }
                if(team_rule_veterans(ruleobject)) {
                    fencer.incorrect_cat=true;
                    fencer.error="(V)";
                }
                if(team_rule_grandveterans(ruleobject)) {
                    fencer.incorrect_cat=true;
                    fencer.error="(G)";
                }
            }

            // Requirement 1.4.1: sort by team name
            if(comp && comp.category && comp.category.type == 'T') {
                // this is a team event
                // only store the last team this fencer participates in. For the overall overview, this is useless
                // in case a fencer is part of more than 1 team, but for the individual events this works out fine
                fencer.has_team = reg.team; 
            }
        });

        return fencer;
    }).filter((fencer) => {
        return fencer.is_registered;
    }).map((fencer) => {
        if(fencer.has_role) {
            // sort the roles
            fencer.has_role.sort();
            fencer.allroles=fencer.has_role.join(", ");
        }
        return fencer;
    });

    // sort based on role (athletes or non-athletes) and name
    fencers.sort(function (a1, a2) {
            // requirement 1.4.1: sort by event, then by team, then by name
            // (two teams in the same event/role should 'stick' together)
            if(props.event) {
                // listing for a specific event, only separate non-athletes from athletes
                // if we have event-specific roles (currently not used)
                if (a1.has_role.includes("Athlete") && !a2.has_role.includes('Athlete')) return -1;
                if (a2.has_role.includes("Athlete") && !a1.has_role.includes("Athlete")) return 1;

                // then sort by teams
                if(allow_more_teams) {
                    if(a1.has_team && !a2.has_team) return -1;
                    if(!a1.has_team && a2.has_team) return 1;
                    if(a1.has_team && a2.has_team && a1.has_team != a2.has_team) return a1.has_team > a2.has_team;
                    // else same team, or no team
                }

                // then by name
                return a1.fullname > a2.fullname;
            }

            // do not sort by roles if this is an overall listing (not for a specific event)
            // it depends on how the roles were ordered and when people
            // have multiple roles it gets confusing
            //if(a1.has_role.length > 0 && a2.has_role.length == 0) return -1;
            //if (a2.has_role.length > 0 && a1.has_role.length == 0) return 1;
            //if(a1.allroles && a2.allroles && a1.allroles != a2.allroles) return a1.allroles > a2.allroles;

            // sort by teams if we have more than 1 team
            if(allow_more_teams) {
                if(a1.has_team && !a2.has_team) return -1;
                if(!a1.has_team && a2.has_team) return 1;
                if(a1.has_team && a2.has_team && a1.has_team != a2.has_team) return a1.has_team > a2.has_team;
                // else same team, or no team
            }

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
                    {props.showCountry && (<th>Country</th>)}
                    <th>Gender</th>
                    <th>YOB</th>
                    {!showTeam && (<th>Category</th>)}
                    {showTeam && (<th>Team</th>)}
                    {props.showRoles && (<th>Role</th>)}
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