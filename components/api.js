const controllers  = {};

export function abort_all_calls(type) {
    //console.log("aborting all fetch calls for "+ type);
    if(controllers[type]) {
        controllers[type].abort();
        delete controllers[type];
    }
}

//Internal API

function simpleFetch(cnt, path,pdata,options, headers={}, postprocessor) {
    if(!controllers[cnt]) {
        controllers[cnt]=new AbortController();
    }
    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "application/json"} , headers);

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
    return fetch(evfranking.url, fetchOptions)
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
            //console.log('validate response ',json);
            if (!json || !json.success) {
                //console.log('no success entry found or success is false');
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

export function upload_file(cnt, selectedFile, add_data, options={}, headers={}) {
    if(!controllers[cnt]) {
        controllers[cnt]=new AbortController();
    }

    const contentHeaders = Object.assign({
        "Accept": "application/json",
        "Content-Type": "removeme"
        } , headers);

    delete contentHeaders['Content-Type'];

    var data = new FormData()
    data.append('picture', selectedFile);
    data.append('nonce',evfranking.nonce);
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
    //console.log(fetchOptions);

    return fetch(evfranking.url, fetchOptions)
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
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('fencers','fencers',obj);
}

export function fencer(action, fields) {
    return fetchJson('fencers','fencers/' + action,fields);
}

// Country
export function countries(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('countries','countries',obj);
}
export function country(action, fields) {
    return fetchJson('countries','countries/' + action,fields);
}

// Events and Competitions
export function events(offset,pagesize,filter,sort, special) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort, special:special};
    return fetchJson('events','events',obj);
}
export function singleevent(action,fields) {
    return fetchJson('events','events/'+action,fields);
}

export function competitions(id) {
    var obj = {id:id};
    return fetchJson('events','events/competitions',obj);
}
export function eventroles(id) {
    var obj = {id:id};
    return fetchJson('events','events/roles',obj);
}
export function categories(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','categories',obj);
}
export function weapons(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','weapons',obj);
}
export function eventtypes(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('events','types',obj);
}

export function results(offset,pagesize,filter,sort,special) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort,special:special};
    return fetchJson('events','results',obj);
}

export function result(action,fields) {
    return fetchJson('events','results/'+action,fields);
}

export function ranking(action,fields) {
    return fetchJson('events','ranking/'+action,fields);
}

// Administration
export function roles(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('roles','roles',obj);
}
export function role(action, fields) {
    return fetchJson('roles','roles/' + action,fields);
}
export function roletypes(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('roletypes','roletypes',obj);
}
export function roletype(action, fields) {
    return fetchJson('roletypes','roletypes/' + action,fields);
}
export function registrars(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('registrars','registrars',obj);
}
export function registrar(action, fields) {
    return fetchJson('registrars','registrars/' + action,fields);
}
export function users(offset,pagesize,filter,sort) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort};
    return fetchJson('registrars','users',obj);
}
export function posts(offset,pagesize,filter,sort,special) {
    var obj = {offset: offset, pagesize: pagesize, filter:filter,sort:sort, special:special};
    return fetchJson('events','posts',obj);
}
