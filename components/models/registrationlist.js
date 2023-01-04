import { loopOverRegistrations } from "../lib/loopOverRegistrations";
import { createRegistration } from "../lib/createRegistration";
import { findRegistration } from "../lib/findRegistration";
import { registrationMatches } from "../lib/registrationMatches";

export function createRegistrationList()
{
    return {
        registrations: [],

        loop: function (cb) {
            return loopOverRegistrations(this.registrations, cb);
        },

        create: function(sideevent, role) {
            return createRegistration(sideevent,role);
        },

        find: function(reg) {
            return findRegistration(this.registrations, reg);
        },

        replace: function (registrationToSearch) {
            this.registrations = loopOverRegistrations(this.registrations, (reg) => {
                if (registrationMatches(registrationToSearch, reg)) {
                    return registrationToSearch;
                }
                return reg;
            });
        },

        add: function(reg) {
            var existing = this.find(reg);
            if (existing === null) {
                this.registrations.push(reg);
            }
        },

        toString: function () {
            var txt = [];
            this.loop((reg) => {
                txt.push('r(' + reg.sideevent + ',' + reg.role + ')');
            });
            return txt.join(', ');
        }
    };
}
