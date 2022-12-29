import { is_valid } from '../functions';

export function getCountryFromIndex(cid, countriesById) {
    if(!is_valid(cid)) {
        return {id: -1, name: "Organisation"};
    }
    if(countriesById) {
        var key="c" + cid;
        if(countriesById[key]) {
            return countriesById[key];
        }
    }
    return {id: cid, name: "No such country"};
}