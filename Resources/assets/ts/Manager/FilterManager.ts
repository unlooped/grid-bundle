import {FilterType} from "../FilterType/FilterType";
import {DateFilterType} from "../FilterType/DateFilterType";

export class FilterManager {

    private filterForm: Element;
    private config: string;
    private options: any[];
    private defaultFilter = new FilterType();
    private filters: any = {
        'Unlooped\\GridBundle\\FilterType\\DateFilterType': new DateFilterType()
    };

    constructor(filterForm: Element, options = []) {
        this.filterForm = filterForm;
        this.config = JSON.parse(this.filterForm.getAttribute('data-ug-filter'));
        this.options = options;

        this.loadElements();
        this.loadEvents();
        this.init();
    }

    private loadElements() {

    }

    private loadEvents() {
        this.filterForm.addEventListener('change', this.formChanged.bind(this));
        let formCollection = this.filterForm.querySelector('[data-collection="form-collection"]');
        // @ts-ignore
        jQuery(formCollection).on('unl.row_added', (event: Event, row: Element) => {
            this.updateForField(row.querySelector('[name*="[field]"]'));
        });
    }

    private init() {
        let firstFieldSelect = this.filterForm.querySelectorAll('select[name$="[field]"]');

        firstFieldSelect.forEach((fieldSelect: HTMLSelectElement, idx: number) => {
            this.updateForField(fieldSelect, false);
        });
    }

    private formChanged(event: Event) {
        if ((<Element>event.target).matches('[name*="[field]"]')) {
            this.updateForField(<HTMLSelectElement>event.target);
        }
    }

    private updateForField(target: HTMLSelectElement, updateInput: boolean = true) {
        let row = target.closest('[role="collection-row"]');
        let fieldName = target.options[target.selectedIndex].value;
        let config = this.config[fieldName];

        let filter = this.defaultFilter;
        if (this.filters.hasOwnProperty(config.type)) {
            filter = this.filters[config.type];
        }

        filter.fieldWithFilterChosen(row, fieldName, config);
        if (updateInput) {
            filter.updateValueInput(row, fieldName, config);
        }

        filter.attachEventListeners(row);
    }
}
