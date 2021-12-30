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

const makeRequest = (requestUrl, requestMethod = 'GET', data = null) => {
    return new Promise(function (resolve, reject) {
        xhr = new XMLHttpRequest();
        xhr.open(requestMethod, requestUrl);
        if (requestMethod === 'GET') {
            xhr.setRequestHeader("Content-Type", 'application/json');
            xhr.setRequestHeader("Cache-Control", "no-cache");
        } else {
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            if (token) {
                xhr.setRequestHeader("X-CSRF-TOKEN", token.content);
            }
        }
        xhr.setRequestHeader("Puller-KeepAlive", WEB_ID);
        xhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                resolve(xhr.response);
            } else {
                reject({
                    status: this.status,
                    statusText: xhr.statusText
                });
            }
        };
        xhr.onerror = function () {
            reject({
                status: this.status,
                statusText: xhr.statusText
            });
        };
        if (data && typeof data === 'object') {
            const formData = new FormData();
            Object.keys(data).map(k => formData.append(k, data[k]))
            xhr.send(formData);
        } else {
            xhr.send();
        }
    });
};

let timer = 0;

const subscribe = () => {
    if (timer) {
        clearTimeout(timer);
    }
    timer = setTimeout(() => {
        const $params = Object.keys(queryState).map(k => `${k}=${encodeURIComponent(queryState[k])}`).join('&');
        makeRequest(url + ($params ? `?${$params}` : '')).then((result) => {
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

const applyGlobalAnswer = (result) => {
    result = String(result).trim();
    const resultJson = result ? JSON.parse(result) : null;
    // if (resultJson && Array.isArray(resultJson)) {
    //     resultJson.map((newResult) => {
    //
    //     });
    // }

    if (resultJson && resultJson.results && Array.isArray(resultJson.results)) {
        const results = resultJson.results;
        results.map(applyAnswer);
    }
    if (resultJson && resultJson.states && typeof resultJson.states === 'object') {
        if (Array.isArray(resultJson.states)) {
            resultJson.states.map(k => window.Puller.state(k,null,false))
        } else {
            Object.keys(resultJson.states).map(k => window.Puller.state(k,resultJson.states[k],false))
        }
    }
}

const channels = {
    alpine: require('./alpineChanel'),
    livewire: require('./livewireChanel'),
};

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
    run: () => {
        subscribe();
    },
    stop: () => {
        xhr.abort();
    },
    channel: (name, cb) => {
        channels[name] = cb;
    },
    restart: () => {
        window.Puller.stop();
        //newWebId();
        window.Puller.run();
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
    emit: (channel, name, detail = undefined) => {
        applyAnswer({name: `${channel}::${name}`, detail})
    },
    dispatch: (name, detail) => {
        document.dispatchEvent(new CustomEvent(name, {detail: detail}));
    },
    message: async (name, data) => {
        if (!name) throw "Enter a message name!";
        await makeRequest(messageUrl + `/${name}`, 'POST', data).then(function (result) {
            applyGlobalAnswer(result);
        });
    },
};


window.Puller.run();
