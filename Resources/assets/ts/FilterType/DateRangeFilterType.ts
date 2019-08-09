import {DateFilterType} from "./DateFilterType";

export class DateRangeFilterType extends DateFilterType {

    public updateSelectedWidget(currentWidgetSelect: HTMLSelectElement, row) {
        let widget = this.getSelectedWidgetType(currentWidgetSelect);

        let variablesFromEl = row.querySelector('select[name$="[_variables_from]"]');
        let variablesToEl = row.querySelector('select[name$="[_variables_to]"]');
        let dateFromEl = row.querySelector('input[name$="[_dateValue_from]"]');
        let dateToEl = row.querySelector('input[name$="[_dateValue_to]"]');

        if (widget === 'date') {
            dateFromEl.classList.remove('d-none');
            dateFromEl.required = true;
            dateToEl.classList.remove('d-none');
            dateToEl.required = true;

            variablesFromEl.classList.add('d-none');
            variablesFromEl.required = false;
            variablesToEl.classList.add('d-none');
            variablesToEl.required = false;
        } else if (widget === 'variables') {
            variablesFromEl.classList.remove('d-none');
            variablesFromEl.required = true;
            variablesToEl.classList.remove('d-none');
            variablesToEl.required = true;

            dateFromEl.classList.add('d-none');
            dateFromEl.required = false;
            dateToEl.classList.add('d-none');
            dateToEl.required = false;
        }
    }
}
