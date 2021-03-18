import { fencer } from '../api';
import { date_to_category_num  } from '../functions';

export function ParticipantList(props) {
    if (!props.fencers) return (null);
    console.log("participant list for ",props.fencers);

    var showRoles = props.event && props.roles && props.event.competition_id && parseInt(props.event.competition_id) > 0;
    var roleById = {};
    if(props.roles) {
        props.roles.map((rl) => {
            roleById["r" + rl.id] = rl;
        });
    }
    roleById["r0"] = { name: "Athlete" };

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
        fencer.is_registered=!props.event;
        fencer.role=-1;
        fencer.has_role = "";
        fencer.reg_cat=-1;
        fencer.incorrect_cat = false;

        fencer.registrations.map((reg) => {
            var comp=null;
            if (props.event && reg.sideevent == props.event.id) {
                fencer.sideevent = reg;
                fencer.is_registered = true;
                fencer.role = reg.role;
                fencer.has_role = roleById["r" + reg.role] ? roleById["r" + reg.role].name : "";
                comp = cmpById["k" + props.event.competition_id];
            }
            if (props.allfencers) {
                var regFor = eventById["e" + reg.sideevent];
                if (roleById["r" + reg.role] && regFor && parseInt(regFor.competition_id) > 0) {
                    fencer.role = reg.role;
                    fencer.has_role = regFor.title;
                    comp = cmpById["k" + regFor.competition_id];
                }
            }

            if (comp && catById["k" + comp.category]) {
                fencer.reg_cat = catById["k" + comp.category].value;
            }
        });

        console.log("matching category "+fencer.reg_cat + " vs " + fencer.category_num);
        if(fencer.role == 0) {
            fencer.incorrect_cat=parseInt(fencer.reg_cat) != parseInt(fencer.category_num) && parseInt(fencer.reg_cat)>0;
        }
        return fencer;
    }).filter((fencer) => {
        return fencer.is_registered;
    });

    // sort based on role (athletes or non-athletes) and name
    fencers.sort(function (a1, a2) {
            if(props.event) {
                if (a1.has_role == "Athlete" && a2.has_role!="Athlete") return -1;
                if (a2.has_role == "Athlete" && a1.has_role != "Athlete") return 1;
                return a1.fullname > a2.fullname;
            }

            if(a1.has_role != "" && a2.has_role == "") return -1;
            if (a2.has_role != "" && a1.has_role == "") return 1;
            if(a1.has_role != a2.has_role) return a1.has_role > a2.has_role;
            return a1.fullname > a2.fullname;
        });

    return (
        <table className='style-stripes'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>First name</th>
                    {evfranking.eventcap != "hod" && (
                        <th>Country</th>
                    )}
                    <th>Gender</th>
                    <th>Birthyear</th>
                    <th>Category</th>
                    {showRoles && (<th>Role</th>)}
                    {props.allfencers && (<th>Event</th>)}
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {fencers.map((fencer,idx) => (
                        <tr key={idx} className={fencer.incorrect_cat ? "incorrect-cat": ""}>
                            <td>{fencer.name}</td>
                            <td>{fencer.firstname}</td>
                            {evfranking.eventcap != "hod" && (
                                <td>{fencer.country_name}</td>
                            )}
                            <td>{fencer.fullgender}</td>
                            <td>{fencer.birthyear}</td>
                            <td>{fencer.category}</td>
                            {(showRoles || props.allfencers) && (<td>{fencer.has_role}</td>)}
                            {props.camera && (<td>
                                {fencer.picture == 'Y' && (<span className='pi pi-camera blue'></span>)}
                                {fencer.picture == 'A' && (<span className='pi pi-camera green'></span>)}
                                {fencer.picture == 'R' && (<span className='pi pi-camera red'></span>)}
                                {!['Y','A','R'].includes(fencer.picture) && (<span className='pi pi-times red'></span>)}
                            </td>)}
                            <td><a onClick={(e) => props.onSelect(fencer)}><i className='pi pi-chevron-circle-right'></i></a></td>
                        </tr>
                    ))}
            </tbody>
        </table>
    );
}