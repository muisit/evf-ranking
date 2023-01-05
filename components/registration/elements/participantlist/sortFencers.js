import { is_valid, parse_date } from "../../../functions";

export function sortFencers (fencerList, sortingOrder, config) {
    console.log('sorting on ',sortingOrder);
    // sort based on role (athletes or non-athletes) and name
    fencerList.sort((a1, a2) => {
        for (var i = 0; i < sortingOrder.length; i++) {
            var sortvalue = sortFencers_sortOn(a1, a2, sortingOrder[i], config);

            if (sortvalue !== 0) {
                return sortvalue;
            }
        }

        return a1.id == a2.id;
    });
    return fencerList;
}

function sortFencers_sortOn(a1, a2, dosort, config) {
    switch(dosort) {
        case 'i': return sortFencers_sortOnId(a1, a2, true, config);
        case 'I': return sortFencers_sortOnId(a1, a2, false, config);
        case 'g': return sortFencers_sortOnGender(a1, a2, true, config);
        case 'G': return sortFencers_sortOnGender(a1, a2, false, config);
        case 'n': return sortFencers_sortOnName(a1, a2, true, config);
        case 'N': return sortFencers_sortOnName(a1, a2, false, config);
        case 'f': return sortFencers_sortOnFirstName(a1, a2, true, config);
        case 'F': return sortFencers_sortOnFirstName(a1, a2, false, config);
        case 'e': return sortFencers_sortOnEvent(a1, a2, true, config);
        case 'E': return sortFencers_sortOnEvent(a1, a2, false, config);
        case 't': return sortFencers_sortOnTeam(a1, a2, true, config);
        case 'T': return sortFencers_sortOnTeam(a1, a2, false, config);
        case 'a': return sortFencers_sortOnCategory(a1, a2, true, config);
        case 'A': return sortFencers_sortOnCategory(a1, a2, false, config);
        case 'c': return sortFencers_sortOnCountry(a1, a2, true, config);
        case 'C': return sortFencers_sortOnCountry(a1, a2, false, config);
        case 'd': return sortFencers_sortOnBirthdate(a1, a2, true, config);
        case 'D': return sortFencers_sortOnBirthdate(a1, a2, false, config);
    }
}

function sortFencers_sortValue(a1, a2, asc)
{
    if (a1 == a2) return 0;
    return a1 > a2 ? (asc ? 1 : -1) : (asc ? -1 : 1);
}

function sortFencers_sortString(a1, a2, asc)
{
    if (a1 && !a2) return 1;
    if (a2 && !a1) return -1;
    var a1l = ('' + a1).toLowerCase();
    var a2l = ('' + a2).toLowerCase();

    if (a1l == a2l) return 0;
    return a1l > a2l ? (asc ? 1 : -1) : (asc ? -1 : 1);
}

function sortFencers_sortOnId(a1, a2, asc, config)
{
    if (!is_valid(a1.id) && is_valid(a2.id)) return asc ? 1 : -1;
    if (is_valid(a1.id) && !is_valid(a2.id)) return asc ? -1 : 1;
    return sortFencers_sortValue(parseInt(a1.id), parseInt(a2.id), asc);
}

function sortFencers_sortOnGender(a1, a2, asc, config)
{
    // because F is displayed as W, we switch sorting orders to avoid confusion
    return sortFencers_sortString(a1.gender, a2.gender, !asc);
}

function sortFencers_sortOnName(a1, a2, asc, config)
{
    return sortFencers_sortString(a1.name, a2.name, asc);
}

function sortFencers_sortOnFirstName(a1, a2, asc, config)
{
    return sortFencers_sortString(a1.firstname, a2.firstname, asc);
}

function sortFencers_sortOnEvent(a1, a2, asc, config)
{
    // sort participants of non-athlete events before the athletes
    if (a1.has_role.includes("Athlete") && !a2.has_role.includes('Athlete')) return asc ? -1 : 1;
    if (a2.has_role.includes("Athlete") && !a1.has_role.includes("Athlete")) return asc ? 1 : -1;

    return sortFencers_sortString(a1.allroles, a2.allroles, asc);
}

function sortFencers_sortOnTeam(a1, a2, asc, config)
{
    if(config.allow_more_teams) {
        if(a1.has_team && !a2.has_team) return asc ? -1 : 1;
        if(!a1.has_team && a2.has_team) return asc ? 1 : -1;
        if(a1.has_team && a2.has_team) {
            return sortFencers_sortString(a1.has_team, a2.has_team, asc);
        }
    }
    return 0;
}

function sortFencers_sortOnCategory(a1, a2, asc, config)
{
    return sortFencers_sortValue(a1.category, a2.category, asc);
}

function sortFencers_sortOnBirthdate(a1, a2, asc, config)
{
    var dt1 = parse_date(a1.birthday);
    var dt2 = parse_date(a2.birthday);

    if (asc) {
        return dt2.isBefore(dt1) ? 1 : (dt1.isBefore(dt2) ? -1 : 0);
    }
    else {
        return dt2.isBefore(dt1) ? -1 : (dt1.isBefore(dt2) ? 1 : 0);
    }
}

function sortFencers_sortOnCountry(a1, a2, asc, config)
{
    return sortFencers.sortFencers_sortString(a1.country_name, a2.country_name, asc);
}
