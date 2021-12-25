const WEB_ID = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
const metaPrefix = document.querySelector(`meta[name=puller-url]`);
let url = "/puller/message";
let xhr = null;

if (metaPrefix) {
    let newValue = metaPrefix.getAttribute('content');
    if (newValue) {
        url = newValue + "/" + WEB_ID;
    }
} else {
    url = url + "/" + WEB_ID;
}

window.onbeforeunload = function (event) {
    Puller.stop();
};

const makeRequest = (url) => {
    return new Promise(function (resolve, reject) {
        // if (xhr) {
        //     url = url + "?keepalive=true";
        // }
        let keepalive = !!xhr;
        xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader("Content-Type", 'application/json');
        if (keepalive) {
            xhr.setRequestHeader("Puller-Keepalive", WEB_ID);
        }
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
        xhr.send();
    });
};

const subscribe = async () => {
    try {
        const result = await makeRequest(url);
        const resultJson = JSON.parse(result);
        if (resultJson && resultJson.results && Array.isArray(resultJson.results)) {
            const results = resultJson.results;
            results.map(cmd => {
                if (cmd.name) {
                    const lw = /^livewire:(.*)/.exec(cmd.name);
                    if (lw) {
                        if (window.Livewire) {
                            window.Livewire.emit(lw[1], cmd.detail)
                        } else {
                            console.error("Livewire not found!");
                        }
                    } else {
                        document.dispatchEvent(new CustomEvent(cmd.name, {detail: cmd.detail}));
                    }
                }
            });
        }
        await subscribe();
    } catch (e) {
        if (e.status !== 404) {
            setTimeout(() => {
                subscribe();
            }, 500);
        }
    }
};

window.Puller = {
    start: () => {
        subscribe();
    },
    stop: () => {
        xhr.abort();
    },
    restart: () => {
        xhr.abort();
        subscribe();
    },
};

require('./listeners');

subscribe();
