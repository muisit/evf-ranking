import { registration } from "../../api";
import { parse_net_error } from "../../functions";

export function createRegistrationJob(model, cb) {
    return {
        hash: '',
        index: '',
        model: model,
        callback: cb,

        execute: function () {
            var obj = {
                id: -1,
                fencer: model.fencer,
                event: model.event,
                sideevent: model.sideevent,
                role: model.role,
                team: model.team,
                payment: model.payment,
                country: model.country
            };

            return registration('save', obj)
                .then((json) => {
                    var itm = null;
                    if (json.data.model) {
                        itm = Object.assign({}, this.model, json.data.model);
                    }
                    if (this.callback) this.callback(json, itm, this.model);
                    return this;
                })
                .catch(parse_net_error);
        }
    }
}