let WEB_ID = null;
const metaPrefix = document.querySelector(`meta[name=puller-prefix]`);
const queryState = {};
let token = document.head.querySelector('meta[name=csrf-token]');
let url = "/puller/keep-alive";
let messageUrl = "/puller/message";
let xhr = null;

const newWebId = () => WEB_ID = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

newWebId();

if (metaPrefix && metaPrefix.content) {
    url = "/" + metaPrefix.content + url;
    messageUrl = "/" + metaPrefix.content + messageUrl;
}

window.onbeforeunload = function (event) {
    Puller.stop();
};

let error_callbacks = {};
let message = null;
let errors = {};
let status = 0;

const errorCollections = (errorList = null, errorStatus = 0) => {
    if (!errorList) {
        status = 0; errors = {};
    } else {
        errorList = String(errorList).trim();
        try {
            errors = JSON.parse(errorList);
            message = errors.message ? errors.message : null;
            if (errors && errors.errors && typeof errors.errors === 'object') {
                errors = errors.errors;
                if (errors.message) errors = errors.message;
            }
            status = errorStatus;
        } catch (e) {
            status = e.status ? e.status : -1;
            errors = {};
        }
    }
    window.Puller.dispatch("puller:task:error", {status, errors, message});
    window.Puller.dispatch(`puller:task:error:${status}`, {status, errors, message});
};

const makeRequest = () => {
    return new Promise(function (resolve, reject) {
        const $params = Object.keys(queryState).map(k => `${k}=${encodeURIComponent(queryState[k])}`).join('&');
        xhr = new XMLHttpRequest();
        xhr.open('GET', url + ($params ? `?${$params}` : ''));
        xhr.setRequestHeader("Cache-Control", "no-cache");
        xhr.setRequestHeader("Content-Type", 'application/json');
        xhr.setRequestHeader("Puller-Message", WEB_ID);
        xhr.setRequestHeader("Puller-KeepAlive", WEB_ID);
        xhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                resolve(xhr.response);
                errorCollections()
            } else {
                errorCollections(xhr.responseText, this.status);
                reject({status: this.status, statusText: xhr.statusText});
            }
        };
        xhr.onerror = function () {
            errorCollections(xhr.responseText, this.status);
            reject({status: this.status, statusText: xhr.statusText});
        };
        xhr.send();
    });
};

const makeMessageRequest = (name, data) => {
    return new Promise(function (resolve, reject) {
        let messageXhr = new XMLHttpRequest();
        const $params = Object.keys(queryState).map(k => `${k}=${encodeURIComponent(queryState[k])}`).join('&');
        messageXhr.open('POST', messageUrl + `/${name}` + ($params ? `?${$params}` : ''));
        messageXhr.setRequestHeader("Cache-Control", "no-cache");
        messageXhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        if (token) {
            messageXhr.setRequestHeader("X-CSRF-TOKEN", token.content);
        }
        messageXhr.setRequestHeader("Puller-KeepAlive", WEB_ID);
        messageXhr.setRequestHeader("Puller-Message", WEB_ID);
        messageXhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                resolve(messageXhr.response);
                errorCollections()
            } else {
                errorCollections(messageXhr.responseText, this.status);
                reject({status: this.status, statusText: messageXhr.statusText});
            }
        };
        messageXhr.onerror = function () {
            errorCollections(messageXhr.responseText, this.status);
            reject({status: this.status, statusText: messageXhr.statusText});
        };
        if (data && typeof data === 'object') {
            const formData = new FormData();
            Object.keys(data).map(k => formData.append(k, data[k]))
            messageXhr.send(formData);
        } else {
            messageXhr.send();
        }
    });
};

let timer = 0;

const subscribe = () => {
    if (timer) {
        clearTimeout(timer);
    }
    timer = setTimeout(() => {
        makeRequest().then((result) => {
            applyGlobalAnswer(result);
            subscribe();
        }).catch((e) => {
            console.error(e);
            if (e.status !== 419 && e.status !== 404 && e.status !== 500) {
                subscribe();
            }
        });
    }, 500);
};

const applyGlobalAnswer = (responseResult) => {
    const result = String(responseResult).trim();
    const resultJson = result ? JSON.parse(result) : null;
    applyJsonResponse(resultJson);
}

const applyJsonResponse = (resultJson) => {
    if (resultJson && resultJson.results && Array.isArray(resultJson.results)) {
        let results = resultJson.results;
        results.map(applyAnswer);
    }
    if (resultJson && resultJson.states && typeof resultJson.states === 'object') {
        if (Array.isArray(resultJson.states)) {
            resultJson.states.map(k => window.Puller.state(k,null,false))
        } else {
            Object.keys(resultJson.states).map(k => window.Puller.state(k,resultJson.states[k],false))
        }
    }
};

const channels = {};

const applyAnswer = (cmd) => {
    if (cmd.name) {
        const moduleTest = /^([^:]+)::(.*)$/.exec(cmd.name);
        if (moduleTest) {
            if (moduleTest[1] && moduleTest[2]) {
                if (channels[moduleTest[1]]) {
                    cmd.name = moduleTest[2];
                    channels[moduleTest[1]](cmd);
                }
            }
        }  else {
            window.Puller.dispatch(cmd.name, cmd.detail);
        }
    }
};

window.Puller = {
    tab: () => {
        return WEB_ID;
    },
    run: () => {
        subscribe();
    },
    stop: () => {
        xhr.abort();
    },
    restart: () => {
        window.Puller.stop();
        //newWebId();
        window.Puller.run();
    },
    channel: (name, cb) => {
        channels[name] = cb;
    },
    state (name, value = null, restart = true) {
        if (typeof name === 'object') {
            let results = Object.keys(name).map(key => {
                return window.Puller.state(key, name[key], false);
            }).filter(i => i);
            if (restart && results.length) window.Puller.restart();
            return !!results.length;
        } else {
            if (name && value === null && queryState[name]) {
                delete queryState[name];
                if (restart) window.Puller.restart();
                return true;
            } else if (name && value !== null && queryState[name] !== value) {
                queryState[name] = value;
                if (restart) window.Puller.restart();
                return true;
            }
        }
        return false;
    },
    emit: (channel, name, detail = null) => {
        applyAnswer({name: `${channel}::${name}`, detail})
    },
    dispatch: (name, detail) => {
        document.dispatchEvent(new CustomEvent(name, {detail: detail}));
    },
    message: async (name, data) => {
        if (!name) throw "Enter a message name!";
        await makeMessageRequest(name, data).then(function (result) {
            applyGlobalAnswer(result);
        });
    },
    response: (object) => {
        applyJsonResponse(object);
    },
    on: (event, callable) => {
        return document.addEventListener(event, callable);
    },
    off: (event, callable) => {
        return document.removeEventListener(event, callable);
    },
    status: () => {
        return status;
    },
    errors: () => {
        return errors;
    },
    onError: (code, callable = null) => {
        if (callable === null) {
            callable = code;
            code = null;
        }
        return code
            ? document.addEventListener(`puller:task:error:${code}`, callable)
            : document.addEventListener("puller:task:error", callable);
    },
    offError: (code, callable = null) => {
        if (callable === null) {
            callable = code;
            code = null;
        }
        return code
            ? document.removeEventListener(`puller:task:error:${code}`, callable)
            : document.removeEventListener("puller:task:error", callable);
    },
};

window.Puller.run();
