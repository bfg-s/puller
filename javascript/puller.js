const WEB_ID = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
const metaPrefix = document.querySelector(`meta[name=puller-url]`);
let url = "/puller/message";
let xhr = null;

if (metaPrefix) {
    let newValue = metaPrefix.getAttribute('content');
    if (newValue) {
        url = newValue;
    }
}

window.onbeforeunload = function (event) {
    Puller.stop();
};

const makeRequest = (url) => {
    return new Promise(function (resolve, reject) {
        xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader("Content-Type", 'application/json');
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
        xhr.send();
    });
};

const subscribe = async () => {
    try {
        const result = await makeRequest(url);
        try {
            applyGlobalAnswer(result);
            await subscribe();
        } catch (e) {
            console.error(e);
        }
    } catch (e) {
        if (e.status !== 404) {
            setTimeout(() => {
                subscribe();
            }, 500);
        }
    }
};

const applyGlobalAnswer = (result) => {
    result = String(result).trim();
    const resultJson = result ? JSON.parse(result) : null;
    if (resultJson && resultJson.results && Array.isArray(resultJson.results)) {
        const results = resultJson.results;
        results.map(applyAnswer);
    }
}

const applyAnswer = (cmd) => {
    if (cmd.name) {
        const livewire = /^livewire:(.*)/.exec(cmd.name);
        const alpine = /^alpine:([^.]+)\.?([^.]+)?$/.exec(cmd.name);
        if (livewire) {
            if (window.Livewire) {
                window.Livewire.emit(livewire[1], cmd.detail)
            } else {
                console.error("Livewire not found!");
            }
        } else if (alpine) {
            if (window.Alpine) {
                if (alpine[2]) {
                    if (typeof Alpine.store(alpine[1])[alpine[2]] === "function") {
                        Alpine.store(alpine[1])[alpine[2]](cmd.detail);
                    } else {
                        Alpine.store(alpine[1])[alpine[2]] = cmd.detail;
                    }
                } else {
                    if (typeof Alpine.store(alpine[1]) === "function") {
                        Alpine.store(alpine[1])(cmd.detail);
                    } else {
                        Alpine.store(alpine[1], cmd.detail);
                    }
                }
            } else {
                console.error("Alpine not found!");
            }
        } else {
            document.dispatchEvent(new CustomEvent(cmd.name, {detail: cmd.detail}));
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
    restart: () => {
        window.Puller.stop();
        window.Puller.run();
    },
    emit: (name, detail) => {
        applyAnswer({name, detail})
    },
    emitLivewire: (name, detail) => {
        applyAnswer({name: `livewire:${name}`, detail})
    },
    emitAlpine: (name, detail) => {
        applyAnswer({name: `alpine:${name}`, detail})
    },
};


window.Puller.run();
