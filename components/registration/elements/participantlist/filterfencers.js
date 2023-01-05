import { wrong_category } from '../../rules/wrong_category';
import { wrong_gender } from '../../rules/wrong_gender';
import { team_rule_grandveterans } from '../../rules/team_rule_grandveterans';
import { team_rule_veterans } from '../../rules/team_rule_veterans';

export function filterFencers (fencerList, noErrors, roleById, basic) {
    return Object.keys(fencerList).map((key) => {
        var fencer=fencerList[key];
        fencer.is_registered = true;
        fencer.role=[];
        fencer.has_role = [];
        fencer.incorrect_cat = false;
        fencer.has_team=false;

        fencer.registrations.map((reg) => {
            // Requirement 1.1.5: display event roles, or, for role 0 (participant), display
            //    the event title
            var comp = null;
            var role = parseInt(reg.role);

            // if we are not filtering out fencers, summarise all registrations
            var regFor = basic.sideeventsById["s" + reg.sideevent] || null;
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

            // if we're not showing roles, this is the search-result. Do not display errors for search results
            // because the fencers in the search results do no necessarily make up a complete team, but they 
            // might already be selected
            if(!noErrors) {
                var ruleobject = {
                    competition: comp,
                    fencer: fencer,
                    registration: reg,
                    fencers: fencerList
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
}