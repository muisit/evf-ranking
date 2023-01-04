import { loopOverRegistrations } from './loopOverRegistrations.js';
import { registrationMatches } from './registrationMatches.js';

export function findRegistration(registrations, registrationToSearch) {
    var registrationFound = null;
    loopOverRegistrations(registrations, (registration) => {
        if (registrationMatches(registrationToSearch, registration)) {
            registrationFound = registration;
        }
    });

    return registrationFound;
}