document.addEventListener("puller:start", () => { window.Puller.start(); });
document.addEventListener("puller:stop", () => { window.Puller.stop(); });
document.addEventListener("puller:restart", () => { window.Puller.restart(); });

document.addEventListener("document:location", ({detail}) => {
    if (window.Turbolinks && String(detail).indexOf('http') !== 0) {
        window.Turbolinks.visit(detail);
    } else {
        location = detail;
    }
});
