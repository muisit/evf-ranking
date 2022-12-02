import { is_valid, my_category_is_older  } from '../../functions';

export const wrong_gender = (data) => {
    if (data.competition && data.competition.weapon) {
        // this only occurs if you assign a fencer, then change the gender
        if (  data.fencer.gender != data.competition.weapon.gender) {
            return true;
        }
    }
    return false;
}
