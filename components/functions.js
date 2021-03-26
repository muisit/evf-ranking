export function pad(number) {
    if (number < 10) {
        return '0' + number;
    }
    return number;
}
export function format_currency(val) {
    return parseFloat(val).toFixed(2);
}

export function format_date(dt) {
    console.log("formatting date ",dt);
    if(!dt.getDate) dt=new Date(dt);
    return dt.getFullYear() +
        '-' + pad(dt.getMonth() + 1) +
        '-' + pad(dt.getDate());
};
var months=["January","February","March","April","May","June","July","August","September","October","November","December"];
export function format_date_fe(dt) {
    if(!dt.getDate) dt=new Date(dt);
    return dt.getDate() + " " + months[dt.getMonth()] + " " + dt.getFullYear();
}
var short_months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
export function format_date_fe_short(dt) {
    if(!dt.getDate) dt=new Date(dt);
    return dt.getDate() + " " + short_months[dt.getMonth()];
}

export function date_to_category_num(dt, wrt) {
    var date=new Date(dt);
    var date2=new Date(wrt);
    var yearold=date.getFullYear();
    var yearnew = date2.getFullYear();
    var diff=yearnew-yearold;

    if(date2.getMonth() > 7) {
        // add 1 if the event takes place in aug-dec, in which case we take birthyears as-of-next-january
        diff+=1;
    }
    var catnum =  parseInt(Math.floor(diff / 10)) - 3;

    if(catnum>5) catnum=5;
    if(catnum < 1) catnum=0;
    return catnum;
}

export function date_to_category(dt,wrt) {
    var cat = date_to_category_num(dt,wrt);
    switch(cat) {
    case 5: return "Cat 5";
    case 4: return "Cat 4";
    case 3: return "Cat 3";
    case 2: return "Cat 2";
    case 1: return "Cat 1";
    default: return "No veteran";
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
    }
    else {
        alert('Error storing the data. Please try again');
    }    
}

// convenience functions to check on the event-related capabilities
export function is_hod() {
    return evfranking.eventcap == "hod";
}

export function is_organiser() {
    return ["system","organiser","cashier","accreditation"].includes(evfranking.eventcap);
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

export function is_administrator() {
    return evfranking.eventcap == "organiser";
}

export function is_sysop() {
    return evfranking.eventcap == "system";
}