import {FilterManager} from "./Manager/FilterManager";

export function initGridBundle(filterOptions: any[] = [], filters: any = {}) {
    let filterForms = document.querySelectorAll('form[data-ug-filter]');

    for (let key in filterForms) {
        if (!filterForms.hasOwnProperty(key)) {
            continue;
        }

        let filterForm = filterForms[key];
        new FilterManager(filterForm, filterOptions, filters);
    }

    // @ts-ignore
    jQuery('.select2text[data-autostart="true"]').select2entity();
}
