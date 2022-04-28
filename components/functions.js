import moment from 'moment';

export function pad(number) {
    if (number < 10) {
        return '0' + number;
    }
    return ''+number;
}
export function format_currency(val) {
    return parseFloat(val).toFixed(2);
}

export function format_datetime(dt) {
    return moment(dt).format("YYYY-MM-DD HH:mm:ss");
};
export function format_date(dt) {
    return moment(dt).format("YYYY-MM-DD");
};
var months=["January","February","March","April","May","June","July","August","September","October","November","December"];
export function format_date_fe(dt) {
    var mmt = moment(dt);
    return mmt.date() + " " + months[mmt.month()] + " " + mmt.year();
}
var short_months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
export function format_date_fe_short(dt) {
    var mmt = moment(dt);
    return mmt.date() + " " + short_months[mmt.month()];
}

export function get_yob(dt) {
    var mmt = moment(dt);
    return parseInt(mmt.year());
}

export function random_hash() {
    return moment().format("YYYYMMDDHHmmss");
}

export function parse_float(val, def) {
    if(def === undefined) def=0.0;
    var retval=parseFloat(val);
    if(isNaN(retval)) retval=def;
    return retval;
}

export function parse_int(val, def) {
    if(def === undefined) def=0;
    var retval=parseInt(val);
    if(isNaN(retval)) retval=def;
    return retval;
}

export function parse_date(dt) {
    var retval=moment(dt);
    if(!retval || !retval.isValid()) retval=moment();
    return retval;
}

export function date_to_category_num(dt, wrt) {
    var date=moment(dt);
    var date2=moment(wrt);
    var yearold=date.year();
    var yearnew = date2.year();
    var diff=yearnew-yearold;

    if(date2.month() > 7) {
        // add 1 if the event takes place in aug-dec, in which case we take birthyears as-of-next-january
        diff+=1;
    }
    var catnum =  parseInt(Math.floor(diff / 10)) - 3;

    // category 5 was removed, the highest category is now 4
    if(catnum>4) catnum=4;
    if(catnum < 1) catnum=0;
    return catnum;
}

export function my_category_is_older(mycat, theircat) {
    if(mycat <= 0) return false; // no category for wrong birthdays
    return mycat > theircat;
}

export function date_to_category(dt,wrt) {
    var cat = date_to_category_num(dt,wrt);
    switch(cat) {
    // category 5 was removed from the implementation after the congress 2021
    case 5:// return "Cat 5";
    case 4: return "4";
    case 3: return "3";
    case 2: return "2";
    case 1: return "1";
    default: return "None";
    }
}

export function nl2br (str) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    var breakTag = '<br />';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

export function jsonOutput(json) {
    return (
        <div className='json'>
            <pre>
              {JSON.stringify(json,null,2)}
            </pre>
    </div>);
}

export function is_valid(id) {
    if(isNaN(id) || Array.isArray(id)) return false;
    id=parseInt(id);
    return !isNaN(id) && id>0;
}

export function parse_net_error(err) {
    if (   err.response 
        && err.response.data
        && err.response.data.messages 
        && err.response.data.messages.length) {
        var txt = "";
        for (var i = 0; i < err.response.data.messages.length; i++) {
            txt += err.response.data.messages[i] + "\r\n";
        }
        alert(txt);
        console.log("parse net error, result with backend error ",txt);
    }
    else {
        alert('Error storing the data. Please try again!');
        console.log("parse net error, result: ",err);
    }    
}

// convenience functions to check on the event-related capabilities
export function is_hod() {
    return evfranking.eventcap == "hod" || evfranking.eventcap=="hod-view";
}

export function is_hod_view() {
    return evfranking.eventcap=="hod-view";
}

export function is_organisation() {
    return ["system","organiser","registrar","cashier","accreditation"].includes(evfranking.eventcap);
}

export function is_cashier() {
    return evfranking.eventcap == "cashier";
}

export function is_registrar() {
    return evfranking.eventcap == "registrar";
}

export function is_accreditor() {
    return evfranking.eventcap == "accreditation";
}

export function is_organiser() {
    return evfranking.eventcap == "organiser";
}

export function is_sysop() {
    return evfranking.eventcap == "system";
}

export function make_team_key(team, se, cnt) {
    return  "k" + cnt + "_"+ se + "_" + team;
}

export function parse_team_for_number(name) {
    var result = name.match(/\d+/);
    if(!result || result.length == 0) return 0;
    return parseInt(result[0]);
}

export function create_abbr(se) {
    var abbr='??';
    if(se.competition) {
        var wpn = se.competition.weapon ? se.competition.weapon : {abbr:'??'};
        var cat = se.competition.category ? se.competition.category : {abbr: '??'};
        abbr = ''+wpn.abbr + cat.abbr;
    }
    else {
        var words=se.title.split(' ');
        abbr="";
        for(var i in words) {
            var word=words[i];
            abbr+=word[0];
        }
    }
    return abbr;
}

export function create_countryById(countries) {
    var countryById={};
    countries.map((r) => {
        var key="c"+r.id;
        countryById[key]=r;
    });
    return countryById;
}

export function create_roleById(roles) {
    var roleById={};
    roles.map((r) => {
        var key="r"+r.id;
        roleById[key]=r;
    });
    return roleById;
}

export function create_wpnById(weapons) {
    var wpnById={};
    weapons.map((w) => {
        var key="w"+w.id;
        wpnById[key]=w;
    });
    return wpnById;
}

export function create_catById(categories) {
    var catById={};
    categories.map((c) => {
        var key="c"+c.id;
        catById[key]=c;
    });
    return catById;
}

export function create_cmpById(competitions, wpnById, catById) {
    var cmpById={};
    competitions.map((c) => {
        var key="c"+c.id;
        var wkey="w"+c.weapon;
        if(wpnById[wkey]) c.weapon = wpnById[wkey];

        var ckey = "c"+c.category;
        if(catById[ckey]) c.category = catById[ckey];

        cmpById[key]=c;
    });
    return cmpById;
}

export function create_sideeventById(sideevents, competitions) {
    var byId={};
    sideevents.map((c) => {
        var key="s"+c.id;
        var ckey="c"+c.competition_id;
        c.competition=null;
        if(competitions[ckey]) c.competition = competitions[ckey];

        byId[key]=c;
    });
    return byId;
}