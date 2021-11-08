import { is_valid, my_category_is_older  } from '../functions';

export const wrong_category = (data) => {
    if (data.competition && data.competition.category && data.competition.category.value) {
        // mark the incorrect-category error only for competition events
        if (  data.fencer.role.includes(0) 
           && is_valid(data.competition.category.value)
           && parseInt(data.competition.category.value) != parseInt(data.fencer.category_num)) {
            return true;
        }
    }
    return false;
}

export const filter_event_category = (fencer, sideevent, event) => {
    return sideevent.category.type != 'T' 
        && sideevent.category.value == fencer.category_num
        && sideevent.weapon.gender == fencer.gender;
}

export const filter_event_category_younger = (fencer, sideevent, event) => {
    return sideevent.category.type != 'T'
        && my_category_is_older(fencer.category_num,sideevent.category.value)
        && sideevent.weapon.gender == fencer.gender;
}