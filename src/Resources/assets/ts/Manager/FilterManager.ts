import {FilterType} from "../FilterType/FilterType";
import {DateFilterType} from "../FilterType/DateFilterType";
import {DateRangeFilterType} from "../FilterType/DateRangeFilterType";
import {AutocompleteFilterType} from "../FilterType/AutocompleteFilterType";
import {AutocompleteTextFilterType} from "../FilterType/AutocompleteTextFilterType";
import {ChoiceFilterType} from "../FilterType/ChoiceFilterType";

export class FilterManager {

    private filterForm: Element;
    private config: string;
    private options: any[];
    private defaultFilter = new FilterType();
    private advancedFilterCb: HTMLInputElement;
    private filters: any = {
        'Unlooped\\GridBundle\\FilterType\\DateFilterType': new DateFilterType(),
        'Unlooped\\GridBundle\\FilterType\\DateRangeFilterType': new DateRangeFilterType(),
        'Unlooped\\GridBundle\\FilterType\\AutocompleteFilterType': new AutocompleteFilterType(),
        'Unlooped\\GridBundle\\FilterType\\AutocompleteTextFilterType': new AutocompleteTextFilterType(),
        'Unlooped\\GridBundle\\FilterType\\ChoiceFilterType': new ChoiceFilterType(),
    };

    constructor(filterForm: Element, options = [], filters: any = {}) {
        this.filters = {...this.filters, ...filters};
        this.filterForm = filterForm;
        this.config = JSON.parse(this.filterForm.getAttribute('data-ug-filter'));
        this.options = options;

        this.loadElements();
        this.loadEvents();
        this.init();
    }

    private loadElements() {
        this.advancedFilterCb = <HTMLInputElement>this.filterForm.querySelector('#filter_form_showAdvancedFilter');
    }

    private loadEvents() {
        this.filterForm.addEventListener('change', this.formChanged.bind(this));
        let formCollection = this.filterForm.querySelector('[data-collection="form-collection"]');
        // @ts-ignore
        jQuery(formCollection).on('unl.row_added', (event: Event, row: Element) => {
            this.updateForField(row.querySelector('[name*="[field]"]'));
        });

        this.advancedFilterCb.addEventListener('change', (e: Event) => {
            e.preventDefault();
            e.stopPropagation();

            this.toggleAdvancedFilter();
        });
    }

    private init() {
        this.toggleAdvancedFilter();
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

    private toggleAdvancedFilter() {
        let conditionColumns = this.filterForm.querySelectorAll('.filter-condition-column');
        if (conditionColumns.length > 0) {
            if (this.advancedFilterCb.checked) {
                conditionColumns.forEach((el: Element) => {
                    el.classList.remove('d-none');
                });
            } else {
                conditionColumns.forEach((el: Element) => {
                    el.classList.add('d-none');
                });
            }
        }
    }
}
