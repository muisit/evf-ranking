import { fencer } from "../../api";
import { is_valid, parse_net_error } from "../../functions";

export function createFencerJob(model, cb) {
    return {
        hash: '',
        index: '',
        model: model,
        callback: cb,

        execute: function () {
            var obj = {
                id: is_valid(model.id) ? model.id : -1,
                name: model.name,
                firstname: model.firstname,
                birthday: model.birthday,
                gender: model.gender,
                country: model.country,
                picture: ['Y','N','A','R'].includes(model.picture) ? model.picture : 'N'
            };
            return fencer('save',obj)
                .then((json) => {
                    var itm = Object.assign({}, this.model);
                    if(json.data && json.data.id) {
                        itm.id = json.data.id;
                    }
                    if(json.data.model) {
                        itm = Object.assign({}, itm, json.data.model);
                    }
                    if (this.callback) this.callback(json, itm, this.model);
                    return this;
                })
                .catch(parse_net_error);    
        },
    };
}