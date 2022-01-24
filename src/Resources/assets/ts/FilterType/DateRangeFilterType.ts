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
            dateToEl.classList.remove('d-none');

            variablesFromEl.classList.add('d-none');
            variablesToEl.classList.add('d-none');
        } else if (widget === 'variables') {
            variablesFromEl.classList.remove('d-none');
            variablesToEl.classList.remove('d-none');

            dateFromEl.classList.add('d-none');
            dateToEl.classList.add('d-none');
        }

        this.conditionUpdated(row);
    }
}
