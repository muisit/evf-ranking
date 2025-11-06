const controllers  = {};

export function abort_all_calls(type) {
    //console.log("aborting all fetch calls for "+ type);
    if(controllers[type]) {
        controllers[type].abort();
        delete controllers[type];
    }
}

export function error_handler(err) {
    if(err.response.data.messages && err.response.data.messages.length) {
        var txt="";
        for(var i=0;i<err.response.data.messages.length;i++) {
           txt+=err.response.data.messages[i]+"\r\n";
        }
        alert(txt);
    }
    else {
        alert('Error storing the data. Please try again');
    }
}

// Internal API
function simpleFetch(cnt, path,pdata,options, headers={}, postprocessor = null) {
    if(!controllers[cnt]) {
        controllers[cnt]=new AbortController();
    }
    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "application/json"} , headers);
    if (evfranking.key) {
        contentHeaders['Authorization'] = 'Bearer ' + evfranking.key;
    }

    const data = {
        path: path,
        nonce: evfranking.nonce, 
        model: pdata
    };

    const fetchOptions = Object.assign({}, {headers: contentHeaders}, options, {
        credentials: "same-origin",
        redirect: "manual",
        method: 'POST',
        signal: controllers[cnt].signal,
        body: JSON.stringify(data)
    });

    //console.log('calling fetch using '+JSON.stringify(data));
    const url = evfranking.api + path;
    return fetch(url, fetchOptions)
        .then(postprocessor())
        .catch(err => {
            if(err.name === "AbortError") {
                //console.log('disregarding aborted call');
            }
            else {
                console.log("error in fetch: ",err);
                throw err;
            }
        });

}

function validateResponse() {
    return res => {
        return res.json().then(json => {
            if (!json || !json.success) {
                console.log('no success entry found or success is false');
                const error = new Error(res.statusText);
                error.response = json;
                throw error;
            }
            return json;
        })
    };
}

function fetchJson(cnt,path, data={}, options = {}, headers = {}) {
    //console.log('valid fetch using data '+JSON.stringify(data));
    return simpleFetch(cnt,path,data,options,headers,validateResponse);
}

function attachmentResponse() {
    return res => {
        //console.log("attachment post processor for response",res);
        return res.blob().then((blob)=> {
            //console.log("response blob received",blob);
            var file = window.URL.createObjectURL(blob);
            window.location.assign(file);
        });
    };
}

function fetchAttachment(cnt,path,data={},options={},headers={}) {
    return simpleFetch(cnt,path,data,options,headers,attachmentResponse);
}

export function upload_file(path, cnt, selectedFile, add_data, options={}, headers={}) {
    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "removeme",
        'Authorization': 'Bearer ' + evfranking.key
    } , headers);

    delete contentHeaders['Content-Type'];

    var data = new FormData()
    data.append('picture', selectedFile);
    data.append('nonce', evfranking.nonce);
    data.append('upload','true');
    Object.keys(add_data).map((key)=> {
        data.append(key,add_data[key]);
    })

    const fetchOptions = Object.assign({}, {headers: contentHeaders}, options, {
        credentials: "same-origin",
        redirect: "manual",
        method: 'POST',
        signal: controllers[cnt].signal,
        body: data
    });

    return fetch(evfranking.api + path, fetchOptions)
        .then(validateResponse())
        .catch(err => {
            if(err.name === "AbortError") {
                //console.log('disregarding aborted call');
            }
            else {
                //console.log("error in fetch: ",err);
                throw err;
            }
        });
}

// Fencers
export function fencers(offset,pagesize,filter,sort) {
    // all migrated to the new api
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('fencers','/fencers',obj, {}, {});
}

export function fencer(action, fields) {
    // all migrated to the new api
    return fetchJson('fencers','/fencers/' + action, fields, {}, {});
}

export function roletypes(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('roletypes','/roletypes',obj, {}, {});
}
export function roletype(action, fields) {
    return fetchJson('roletypes','/roletypes/' + action,fields, {}, {});
}

export function roles(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('roles','/roles',obj, {}, {});
}
export function role(action, fields) {
    return fetchJson('roles','/roles/' + action,fields, {}, {});
}

export function registrars(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('registrars','/registrars',obj, {}, {});
}
export function registrar(action, fields) {
    return fetchJson('registrars','/registrars/' + action,fields, {}, {});
}

export function countries(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('countries','/countries',obj, {}, {});
}
export function country(action, fields) {
    return fetchJson('countries','/countries/' + action,fields, {}, {});
}

// only listings
export function categories(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','/categories',obj, {}, {});
}
export function weapons(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','/weapons',obj, {}, {});
}
export function eventtypes(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','/types',obj, {}, {});
}
export function users(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('registrars','/users',obj, {}, {});
}

export function events(offset,pagesize,filter,sort, special) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort, special:special};
    return fetchJson('events','/events',obj, {}, {});
}

export function competitions(id) {
    var obj = {id:id};
    return fetchJson('events','/events/competitions',obj, {}, {});
}

export function singleevent(action,fields) {
    console.log('network call to events/' + action);
    return fetchJson('events','/events/'+action,fields, {}, {});
}

export function results(offset,pagesize,filter,sort,compId) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','/results/' + compId,obj, {}, {});
}

export function result(action,fields) {
    var compid = fields.competition_id;
    return fetchJson('events','/results/'+compid + '/' + action,fields, {}, {});
}

export function apidata(dt = {}) {
    return fetchJson('events','/apidata', dt, {}, {});
}

export function ranking(action,fields) {
    return fetchJson('events','/ranking/' + action,fields, {}, {});
}

export function workflow(action,fields) {
    return fetchJson('events','/workflow/' + action,fields, {}, {});
}
