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
    }

    private init() {
        let firstFieldSelect = this.filterForm.querySelectorAll('select[name$="[field]"]');

        firstFieldSelect.forEach((fieldSelect: HTMLSelectElement, idx: number) => {
            this.updateForField(fieldSelect);
        });
    }

    private formChanged(event: Event) {
        if ((<Element>event.target).matches('[name*="[field]"]')) {
            this.updateForField(<HTMLSelectElement>event.target);
        }
    }

    private updateForField(target: HTMLSelectElement) {
        let row = target.closest('[role="collection-row"]');
        let fieldName = target.options[target.selectedIndex].value;
        let config = this.config[fieldName];

        let filter = this.defaultFilter;
        if (this.filters.hasOwnProperty(config.type)) {
            filter = this.filters[config.type];
        }

        filter.fieldWithFilterChosen(row, fieldName, config);
        filter.updateValueInput(row, fieldName, config);
    }
}
