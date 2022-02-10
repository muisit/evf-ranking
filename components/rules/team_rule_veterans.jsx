import { is_valid  } from '../functions';

export const team_rule_veterans = (data) => {
    // teams of 3 fencers belonging to age category 40-49 and 50-59
    // with at least 1 fencer from age category 50-59
    // and up to 2 reserves (5 total)    
    if(data.competition && data.competition.category && data.competition.category.abbr == 'T') {
        //console.log("checking veteran team event rule for ",data);
        var team=[];
        var teamname=data.registration.team;

        // loop over all fencers and pick all registrations with the same team name
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
            //console.log("team is incomplete ",team);
            return true;
        }
        var has_a_cat2_fencer=false;
        var has_no_cat345_fencer=true;
        team.map((f) => {
            if(f.category_num == 2) {
                //console.log("team has a cat2 fencer");
                has_a_cat2_fencer=true;
            }
            if(f.category_num > 2) {
                //console.log("team has a cat 345-fencer (false)",team);
                has_no_cat345_fencer=false;
            }
        });
        // return true on error
        //console.log(has_a_cat2_fencer,has_no_cat345_fencer);
        return !(has_a_cat2_fencer && has_no_cat345_fencer);
    }
    return false;
}

export const filter_event_team_veterans = (fencer, sideevent, event) => {
    return sideevent.category.type == 'T' 
        && sideevent.category.abbr == 'T' 
        && sideevent.weapon.gender == fencer.gender
        && fencer.category_num < 3 && is_valid(fencer.category_num);
}