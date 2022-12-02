import { pad, format_currency, format_datetime, format_date, is_valid } from "../../components/functions.js";
import { format_date_fe, format_date_fe_short, get_yob } from "../../components/functions.js";
import { random_hash, parse_float, parse_int, parse_date } from "../../components/functions.js";
import { date_to_category_num, my_category_is_older, date_to_category } from "../../components/functions.js";
import { nl2br } from "../../components/functions.js";
import { is_hod, is_cashier, is_organisation, is_registrar, is_accreditor, is_organiser, is_sysop } from "../../components/functions.js";
import { make_team_key, parse_team_for_number, create_abbr, create_countryById, create_roleById, create_wpnById, create_catById, create_cmpById, create_sideeventById } from "../../components/functions.js";
import moment from 'moment';

test('create_sideeventById',() => {
  var cmps={
    "c1": { id: 1, name: 'cmp1'},
    "c2": { id: 2, name: 'cmp2'},
  };
  var sids1=[
    {id: 1, name:'s1',competition_id:1},
    {id: 2, name:'s2',competition_id:2},
    {id: 3, name:'s3',competition_id:3},
  ];

  var exp1={
    's1': {id: 1, name:'s1',competition_id: 1, competition: {id: 1, name: 'cmp1'}},
    's2': {id: 2, name:'s2',competition_id: 2, competition: {id:2, name: 'cmp2'}},
    's3': {id: 3, name:'s3',competition_id:3, competition: null}
  };
  expect(create_sideeventById(sids1,cmps)).toEqual(exp1);

});

test('create_cmpById',() => {
  var wpns={"w1": {id: 1, name:'w1'}, "w2": {id:2, name:'w2'}};
  var cats={"c1": {id: 1, name:'c1'}, "c2": {id:2, name:'c2'}};
  var cmps1=[
    {id:1, name: 'cmp1', weapon: 1, category: 1}, 
    {id:2, name:'cmp2', weapon:2, category: 1}
  ];
  var cmps2=[
    {id:1, name: 'cmp1', weapon: 3, category: 1}, 
    {id:2, name:'cmp2', weapon:2, category: 1}
  ];
  var cmps3=[
    {id:1, name: 'cmp1', weapon: 2, category: 1}, 
    {id:2, name:'cmp2', weapon:2, category: 4}
  ];

  var exp1={
    "c1": { id: 1, name: 'cmp1', weapon: { id: 1, name: 'w1'}, category: {id: 1, name: 'c1'}},
    "c2": { id: 2, name: 'cmp2', weapon: { id: 2, name: 'w2'}, category: {id: 1, name: 'c1'}},
  };
  var exp2={
    "c1": { id: 1, name: 'cmp1', weapon: 3, category: {id: 1, name: 'c1'}},
    "c2": { id: 2, name: 'cmp2', weapon: { id: 2, name: 'w2'}, category: {id: 1, name: 'c1'}},
  };
  var exp3={
    "c1": { id: 1, name: 'cmp1', weapon: { id: 2, name: 'w2'}, category: {id: 1, name: 'c1'}},
    "c2": { id: 2, name: 'cmp2', weapon: { id: 2, name: 'w2'}, category: 4},
  };

  expect(create_cmpById(cmps1,wpns,cats)).toEqual(exp1);
  expect(create_cmpById(cmps2,wpns,cats)).toEqual(exp2);
  expect(create_cmpById(cmps3,wpns,cats)).toEqual(exp3);
});

test('create_catById',() => {
  expect(create_catById([{id:1,name:'a'},{id:2,name:'b'}])).toEqual({"c1":{id:1,name:'a'},"c2":{id:2,name:'b'}});

});

test('create_wpnById',() => {
  expect(create_wpnById([{id:1,name:'a'},{id:2,name:'b'}])).toEqual({"w1":{id:1,name:'a'},"w2":{id:2,name:'b'}});

});

test('create_roleById',() => {
  expect(create_roleById([{id:1,name:'a'},{id:2,name:'b'}])).toEqual({"r1":{id:1,name:'a'},"r2":{id:2,name:'b'}});

});

test('create_countryById',() => {
  expect(create_countryById([{id:1,name:'a'},{id:2,name:'b'}])).toEqual({"c1":{id:1,name:'a'},"c2":{id:2,name:'b'}});
});

test('create_abbr',() => {
  expect(create_abbr({title:"A B"})).toBe("AB");
  expect(create_abbr({competition: {weapon:{abbr: "A"}, category: {abbr: 2}}})).toBe("A2");
  expect(create_abbr({competition: {weapon:{abbr: "A"}}})).toBe("A??");
  expect(create_abbr({competition: {category: {abbr: 2}}})).toBe("??2");
  expect(create_abbr({title:""})).toBe("undefined");
});

test('parse_team_for_number',() => {
  // only testing the cases we generate in the code, not the error situations
  expect(parse_team_for_number('vets 1')).toBe(1);
  expect(parse_team_for_number('vets12 2')).toBe(12);
  expect(parse_team_for_number('Grand Vets() 21')).toBe(21);
});

test('make_team_key',()=>{
  expect(make_team_key('a','b','c')).toBe("kc_b_a");
  expect(make_team_key('a',1,'c')).toBe("kc_1_a");
});

global.evfranking = {}

test('is_sysop', () => {
  var caps={
    "system": true,
    "organiser":false ,
    "registrar": false,
    "cashier": false,
    "accreditation": false,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_sysop() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_sysop() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_sysop() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_sysop() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_sysop() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_sysop() === false).toBe(true);
});


test('is_organiser', () => {
  var caps={
    "system": false,
    "organiser":true ,
    "registrar": false,
    "cashier": false,
    "accreditation": false,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_organiser() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_organiser() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_organiser() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_organiser() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_organiser() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_organiser() === false).toBe(true);
});

test('is_accreditor', () => {
  var caps={
    "system": false,
    "organiser":false ,
    "registrar": false,
    "cashier": false,
    "accreditation": true,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_accreditor() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_accreditor() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_accreditor() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_accreditor() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_accreditor() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_accreditor() === false).toBe(true);
});

test('is_registrar', () => {
  var caps={
    "system": false,
    "organiser":false ,
    "registrar": true,
    "cashier": false,
    "accreditation": false,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_registrar() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_registrar() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_registrar() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_registrar() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_registrar() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_registrar() === false).toBe(true);
});

test('is_organisation', () => {
  var caps={
    "system": true,
    "organiser":true ,
    "registrar": true,
    "cashier": true,
    "accreditation": true,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_organisation() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_organisation() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_organisation() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_organisation() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_organisation() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_organisation() === false).toBe(true);
});

test('is_cashier', () => {
  var caps={
    "system": false,
    "organiser":false ,
    "registrar": false,
    "cashier": true,
    "accreditation": false,
    "hod": false,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_cashier() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_cashier() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_cashier() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_cashier() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_cashier() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_cashier() === false).toBe(true);
});


test('is_hod', () => {
  var caps={
    "system": false,
    "organiser":false ,
    "registrar": false,
    "cashier": false,
    "accreditation": false,
    "hod": true,
    "nosuchcap": false
  };
  Object.keys(caps).map((key) => {
    var outcome=caps[key];
    evfranking.eventcap=key;
    expect(is_hod() === outcome).toBe(true);
  });
  evfranking.eventcap={};
  expect(is_hod() === false).toBe(true);
  evfranking.eventcap=[];
  expect(is_hod() === false).toBe(true);
  evfranking.eventcap=11;
  expect(is_hod() === false).toBe(true);
  evfranking.eventcap=-1;
  expect(is_hod() === false).toBe(true);
  evfranking.eventcap=()=>2;;
  expect(is_hod() === false).toBe(true);
});


test('nl2br',() => {
  expect(nl2br("a\nb")).toBe("a<br />\nb");
  expect(nl2br(undefined)).toBe("");
  expect(nl2br(null)).toBe("");
  expect(nl2br()).toBe("");
  expect(nl2br("a\nb\ncccccc\nd")).toBe("a<br />\nb<br />\ncccccc<br />\nd");

});

test('date_to_category',()=> {
  expect(date_to_category("1989-01-27","2021-01-07")).toBe("None");
  expect(date_to_category("1981-01-27","2021-01-07")).toBe("1");
  expect(date_to_category("1971-01-27","2021-01-07")).toBe("2");
  expect(date_to_category("1972-01-27","2021-01-07")).toBe("1");
  expect(date_to_category("1972-01-27","2021-09-07")).toBe("2");
  expect(date_to_category("1962-01-27","2021-01-07")).toBe("2");
  expect(date_to_category("1962-01-27","2021-09-07")).toBe("3");
  expect(date_to_category("1952-01-27","2021-01-07")).toBe("3");
  expect(date_to_category("1952-01-27","2021-09-07")).toBe("4");
  expect(date_to_category("1942-01-27","2021-01-07")).toBe("4");
  expect(date_to_category("1942-01-27","2021-09-07")).toBe("4"); // cat 5 not supported
});

test('my_category_is_older', () => {
  expect(my_category_is_older(0,8)).toBe(false);
  expect(my_category_is_older(0.1,8)).toBe(false);
  expect(my_category_is_older(12,3)).toBe(true);
  expect(my_category_is_older(1.04,1.02)).toBe(true);
  expect(my_category_is_older("1.04",[])).toBe(true);
  expect(my_category_is_older("-Inf",0)).toBe(false);
});

test('date_to_category_num',()=> {
  expect(date_to_category_num("1989-01-27","2021-01-07")).toBe(0);
  expect(date_to_category_num("1981-01-27","2021-01-07")).toBe(1);
  expect(date_to_category_num("1971-01-27","2021-01-07")).toBe(2);
  expect(date_to_category_num("1972-01-27","2021-01-07")).toBe(1);
  expect(date_to_category_num("1972-01-27","2021-09-07")).toBe(2);
  expect(date_to_category_num("1962-01-27","2021-01-07")).toBe(2);
  expect(date_to_category_num("1962-01-27","2021-09-07")).toBe(3);
  expect(date_to_category_num("1952-01-27","2021-01-07")).toBe(3);
  expect(date_to_category_num("1952-01-27","2021-09-07")).toBe(4);
  expect(date_to_category_num("1942-01-27","2021-01-07")).toBe(4);
  expect(date_to_category_num("1942-01-27","2021-09-07")).toBe(4); // cat 5 not supported
});

function is_the_same(dt1,dt2) {
  var a=moment(dt1), b=moment(dt2);
  return a.format("YYYYMMDDHHmm") == b.format("YYYYMMDDHHmm");
}

test('parse_date',() => {
  var now=moment();
  expect(is_the_same(parse_date(),now)).toBe(true);
  expect(is_the_same(parse_date("aaaa"),now)).toBe(true);
  expect(is_the_same(parse_date([]),now)).toBe(true);
  expect(is_the_same(parse_date("2021-11-29"),"2021-11-29")).toBe(true);
});

test('parse_int',() => {
  expect(parse_int()).toBe(0);
  expect(parse_int({})).toBe(0);
  expect(parse_int([])).toBe(0);
  expect(parse_int(0.0)).toBe(0);
  expect(parse_int("aa",0.0)).toBeCloseTo(0.0);
  expect(parse_int("aa")).toBe(0);
  expect(parse_int("1.1",0.0)).toBe(1);
  expect(parse_int(1.1,0.0)).toBe(1);
  expect(parse_int([],1.1)).toBeCloseTo(1.1);
  expect(parse_int(undefined,1.1)).toBeCloseTo(1.1);
});


test('parse_float',() => {
  expect(parse_float()).toBeCloseTo(0.0);
  expect(parse_float({})).toBeCloseTo(0.0);
  expect(parse_float([])).toBeCloseTo(0.0);
  expect(parse_float(0.0)).toBeCloseTo(0.0);
  expect(parse_float("aa",0.0)).toBeCloseTo(0.0);
  expect(parse_float("aa")).toBeCloseTo(0.0);
  expect(parse_float("1.1",0.0)).toBeCloseTo(1.1);
  expect(parse_float(1.1,0.0)).toBeCloseTo(1.1);
  expect(parse_float([],1.1)).toBeCloseTo(1.1);
  expect(parse_float(undefined,1.1)).toBeCloseTo(1.1);
});

test('random_hash',() => {
  var hsh = moment().format("YYYYMMDDHHmmss");
  expect(random_hash()).toBe(hsh);
});

test('get_yob', () => {
  expect(get_yob("2021-02-12")).toBe(2021);
  expect(get_yob(new Date("2021-02-12"))).toBe(2021);
  expect(get_yob("1970-038")).toBe(1970);
});

test('format_date_fe_short', () => {
  expect(format_date_fe_short('2021-02-12')).toBe("12 Feb");
  //expect(format_date_fe_short('01/12/2021')).toBe("1 Dec");
  expect(format_date_fe_short('1970-01-01')).toBe("1 Jan");
  expect(format_date_fe_short('1970-02-01')).toBe("1 Feb");
  expect(format_date_fe_short('1970-03-01')).toBe("1 Mar");
  expect(format_date_fe_short('1970-04-01')).toBe("1 Apr");
  expect(format_date_fe_short('1970-05-01')).toBe("1 May");
  expect(format_date_fe_short('1970-06-01')).toBe("1 Jun");
  expect(format_date_fe_short('1970-07-01')).toBe("1 Jul");
  expect(format_date_fe_short('1970-08-01')).toBe("1 Aug");
  expect(format_date_fe_short('1970-09-01')).toBe("1 Sep");
  expect(format_date_fe_short('1970-10-01')).toBe("1 Oct");
  expect(format_date_fe_short('1970-11-01')).toBe("1 Nov");
  expect(format_date_fe_short('1970-12-01')).toBe("1 Dec");

});

test('format_date_fe', () => {
  expect(format_date_fe('2021-02-12')).toBe("12 February 2021");
  //expect(format_date_fe('01/12/2021')).toBe("1 December 2021");
  expect(format_date_fe('1970-01-01')).toBe("1 January 1970");
  expect(format_date_fe('1970-02-01')).toBe("1 February 1970");
  expect(format_date_fe('1970-03-01')).toBe("1 March 1970");
  expect(format_date_fe('1970-04-01')).toBe("1 April 1970");
  expect(format_date_fe('1970-05-01')).toBe("1 May 1970");
  expect(format_date_fe('1970-06-01')).toBe("1 June 1970");
  expect(format_date_fe('1970-07-01')).toBe("1 July 1970");
  expect(format_date_fe('1970-08-01')).toBe("1 August 1970");
  expect(format_date_fe('1970-09-01')).toBe("1 September 1970");
  expect(format_date_fe('1970-10-01')).toBe("1 October 1970");
  expect(format_date_fe('1970-11-01')).toBe("1 November 1970");
  expect(format_date_fe('1970-12-01')).toBe("1 December 1970");

});

test('pad',() => {
  expect(pad(1)).toBe("01");
  expect(pad(2)).toBe("02");
  expect(pad(3)).toBe("03");
  expect(pad(0)).toBe("00");
  expect(pad(10)).toBe("10");
  expect(pad(19)).toBe("19");
  expect(pad(89)).toBe("89");
  expect(pad(102)).toBe("102");
  expect(pad(-1)).toBe("0-1");
});

test('format_currency', () => {
  expect(format_currency(1.2)).toBe("1.20");
  expect(format_currency("1.2")).toBe("1.20");
  expect(format_currency(NaN)).toBe("NaN");
  expect(format_currency([])).toBe("NaN");
  expect(format_currency([1.2])).toBe("1.20");
  expect(format_currency(["1.2"])).toBe("1.20");
  expect(format_currency({})).toBe("NaN");
  expect(format_currency(0)).toBe("0.00");
  expect(format_currency(-10)).toBe("-10.00");
});

test('format_datetime', () => {
  expect(format_datetime("2021-01-12 12:34:56")).toBe("2021-01-12 12:34:56");
  expect(format_datetime("2021-01-12")).toBe("2021-01-12 00:00:00");
  //expect(format_datetime("01-12-2021")).toBe("2021-01-12 00:00:00");
  //expect(format_datetime("01/12/2021 12:34:56")).toBe("2021-01-12 12:34:56");
  //expect(format_datetime("12/01/2021 12:34:56")).toBe("2021-12-01 12:34:56");
  expect(format_datetime(moment("1904-06-28 01:02:03"))).toBe("1904-06-28 01:02:03");
});

test('format_date', () => {
  expect(format_date("2021-01-12 12:34:56")).toBe("2021-01-12");
  expect(format_date("2021-01-12")).toBe("2021-01-12");
  //expect(format_date("01-12-2021")).toBe("2021-01-12");
  //expect(format_date("01/12/2021 12:34:56")).toBe("2021-01-12");
  //expect(format_date("12/01/2021 12:34:56")).toBe("2021-12-01");
  expect(format_date(moment("1904-06-28 01:02:03"))).toBe("1904-06-28");
});


test('is_valid', () => {
    expect(is_valid(1)).toBe(true);
    expect(is_valid(-1)).toBe(false);
    expect(is_valid()).toBe(false);
    expect(is_valid('1')).toBe(true);
    expect(is_valid('-1')).toBe(false);
    expect(is_valid(null)).toBe(false);
    expect(is_valid(1.1)).toBe(true);
    expect(is_valid("1.1")).toBe(true);
    expect(is_valid("-1.2")).toBe(false);
    expect(is_valid(-10.211)).toBe(false);
    expect(is_valid({test:1})).toBe(false);
    expect(is_valid("aaa")).toBe(false);
    expect(is_valid(['a','b'])).toBe(false);
    expect(is_valid([1])).toBe(false);
  });

