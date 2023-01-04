import { is_valid } from '../functions';

export function registrationMatches(registrationToSearch, registration) {
    if (parseInt(registrationToSearch.event) == parseInt(registration.event)) {
        if (!is_valid(registrationToSearch.sideevent) && !is_valid(registration.sideevent)) {
            if (is_valid(registrationToSearch.role) && parseInt(registrationToSearch.role) == parseInt(registration.role)) {
                return true;
            }
        }
        else if(is_valid(registrationToSearch.sideevent) && is_valid(registration.sideevent)) {
            if (parseInt(registrationToSearch.sideevent) == parseInt(registration.sideevent)) {
                return true;
            }
        }
    }
    return false;
}
