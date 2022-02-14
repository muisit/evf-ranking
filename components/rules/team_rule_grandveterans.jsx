import { fencer } from '../api';
import { is_valid  } from '../functions';

export const team_rule_grandveterans = (data) => {
    // teams of 3 fencers belonging to age category 60-69 or over
    // with at least 1 fencer from age category 70+
    // and up to 2 reserves (5 total)
    if(data.competition && data.competition.category && data.competition.category.abbr == 'T(G)') {
        var team=[];
        var teamname=data.registration.team;

        // loop over all fencers and pick all registrations with the same team name and category
        var keys=Object.keys(data.fencers);
        keys.map((k) => {
            var itm=data.fencers[k];
            if(itm.registrations) {
                itm.registrations.map((r) => {
                    // make sure we pick teams from the same competition/sideevent
                    if(r.sideevent == data.registration.sideevent && r.team === teamname) {
                        team.push(itm);
                    }
                });
            }
        });

        if(team.length < 3 || team.length>5) {
            return true;
        }
        var has_a_cat4_fencer=false;
        var has_no_cat12_fencer=true;
        team.map((f) => {
            if(f.category_num == 4) has_a_cat4_fencer=true;
            if(f.category_num < 3) has_no_cat12_fencer=false;
        });
        // return true on error
        return !(has_a_cat4_fencer && has_no_cat12_fencer);
    }
    return false;
}

export const filter_event_team_grandveterans = (fencer,sideevent) => {
    return sideevent.category.type == 'T' 
        && sideevent.category.abbr == 'T(G)' 
        && sideevent.weapon.gender == fencer.gender
        && fencer.category_num > 2;

}