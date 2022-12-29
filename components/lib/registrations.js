// general object to manage registrations
import { registrations as getRegistrations } from "../api.js";
import { parse_date, date_to_category, date_to_category_num, format_date, is_valid } from '../functions';

export const retrieve =  (countryid, eventid) => {
        return getRegistrations(0, 10000, { country: countryid, event: eventid });
    };

export const parse = (fencers,lst, doclear, event) => {
        // filter out all fencers for the active registrations
        if (lst) {
            if (doclear) {
                fencers = {};
            }

            lst.map((itm) => {
                var fid = itm.fencer;
                var key = "k" + fid;
                if (!fencers[key]) {
                    var obj = adjustFencerData(itm.fencer_data,event);
                    if(obj.fencer_data) delete obj.fencer_data;
                    fencers[key] = obj;
                }

                // reverse the mapping: we have a registration-with-fencer, move to fencer-with-registrations
                delete itm.fencer_data;
                fencers[key].registrations.push(itm);
            });            
        }
        return fencers;
    };

export const adjustFencerData =  (fencer, event) => {
        var name = fencer.name + ", " + fencer.firstname;
        fencer.fullname = name;
        if (fencer.birthday) {
            fencer.category = date_to_category(fencer.birthday, event.opens);
            fencer.category_num = date_to_category_num(fencer.birthday, event.opens);
            fencer.birthyear = fencer.birthday.substring(0, 4);
        }
        else {
            fencer.category = "None";
            fencer.category_num = -1;
            fencer.birthyear = "unknown";
            fencer.birthday = null;
        }
        fencer.fullgender = fencer.gender == 'M' ? "M" : "W";
        if(!fencer.registrations) fencer.registrations = [];
        return Object.assign({},fencer);
    };

export const updateFencerRegistrations = (fencers, newfencer) => {
        var key="k" + newfencer.id;
        var newlist = Object.assign({}, fencers);
        newlist[key]=newfencer;
        return newlist;
    };

export const updateRegistration =  (fencers,fencer,itm) => {
        var regs = fencer.registrations.map((reg) => {
            if (is_valid(reg.sideevent) && reg.sideevent == itm.sideevent) {
                return itm;
            }
            else if (!is_valid(reg.sideevent) && !is_valid(itm.sideevent)) {
                // only replace if the roles match
                if (reg.role == itm.role) {
                    return itm;
                }
            }
            return reg;
        });
        fencer.registrations = regs;
        return updateFencerRegistrations(fencers, fencer);
    };

export const updateFencerData = (fencers, newfencer) => {
        var key="k" + newfencer.id;
        var newlist = Object.assign({}, fencers);
        if(newlist[key]) {
            newfencer.registrations = fencers[key].registrations;
        }
        else {
            newfencer.registrations=[];
        }
        newlist[key]=newfencer;
        return newlist;
    };

export const changePendingSituation = (fencers, fencer, reg, newstate, cb1, cb, timing, replacestate) => {
        reg.pending=newstate;
        var newlist=updateRegistration(fencers, fencer,reg);
        if(cb) cb(newlist);

        if(timing && cb1 && cb) {
            setTimeout(() => { 
                if(reg.pending == newstate) {
                    var fencers = cb1();
                    changePendingSituation(fencers,fencer,reg,replacestate);
                    cb(fencers);
                }
            }, timing);
        }
    };

export const forRegistration= (fencers, fid, rid, callback) => {
        var key="k" + fid;
        if(fencers[key] && fencers[key].registrations) {
            fencers[key].registrations.map((reg) => {
                if(reg.id === rid && !reg.is_team) {
                    callback(fencers[key], reg);
                }
            });
        }
    };

export const forRegistrations= (fencers,fid, callback) => {
        var key="k" + fid;
        if(fencers[key] && fencers[key].registrations) {
            fencers[key].registrations.map((reg) => {
                if(!reg.is_team) {
                    callback(fencers[key], reg);
                }
            });
        }
    };

export const forTeamRegistration= (fencers, sideevent, teamname, callback) => {
        Object.keys(fencers).map((key) => {
            var fencer=fencers[key];
            fencer.registrations.map((reg) => {
                if (reg.sideevent == sideevent && reg.team == teamname) {
                    callback(fencer,reg);
                }
            });
        });
    };

export const forTeams= (fencers, sideevent, callback) => {
        Object.keys(fencers).map((key) => {
            var fencer=fencers[key];
            if(fencer.registrations) {
            fencer.registrations.map((reg) => {
                if (reg.sideevent == sideevent && reg.is_team) {
                    callback(fencer,reg);
                }
            });}
        });
    };

