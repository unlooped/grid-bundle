import {FilterManager} from "./Manager/FilterManager";

window.addEventListener('DOMContentLoaded', (event) => {
    let filterForms = document.querySelectorAll('form[data-ug-filter]');

    for (let key in filterForms) {
        if (!filterForms.hasOwnProperty(key)) {
            continue;
        }

        let filterForm = filterForms[key];
        new FilterManager(filterForm);
    }

    // @ts-ignore
    jQuery('.select2text[data-autostart="true"]').select2entity();
});
