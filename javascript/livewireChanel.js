module.exports = ({name, detail}) => {
    if (window.Livewire) {
        window.Livewire.emit(`puller:${name}`, detail)
    }
}
