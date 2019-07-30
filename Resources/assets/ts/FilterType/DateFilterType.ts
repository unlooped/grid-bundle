import {FilterType} from "./FilterType";

export class DateFilterType extends FilterType {

    public updateValueInput(row: Element, fieldName: string, config: any) {
        super.updateValueInput(row, fieldName, config);
        this.attachEventListeners(row);
    }

    public attachEventListeners(row: Element) {
        let currentWidgetSelect = <HTMLSelectElement>row.querySelector('select[name$="[_valueChoices]"]');
        this.getSelectedWidgetType(currentWidgetSelect);

        currentWidgetSelect.addEventListener('change', (event: Event) => {
            this.updateSelectedWidget(<HTMLSelectElement>event.target, row);
        });

        this.updateSelectedWidget(currentWidgetSelect, row);
    }

    public getSelectedWidgetType(currentWidgetSelect: HTMLSelectElement) {
        let currentValue = null;
        if (currentWidgetSelect.selectedIndex !== null) {
            currentValue = currentWidgetSelect.options[currentWidgetSelect.selectedIndex].value;
        }

        return currentValue;
    }

    public updateSelectedWidget(currentWidgetSelect: HTMLSelectElement, row) {
        let widget = this.getSelectedWidgetType(currentWidgetSelect);

        let variablesEl = row.querySelector('select[name$="[_variables]"]');
        let dateEl = row.querySelector('input[name$="[_dateValue]"]');

        if (widget === 'date') {
            dateEl.classList.remove('d-none');
            dateEl.required = true;

            variablesEl.classList.add('d-none');
            variablesEl.required = false;
        } else if (widget === 'variables') {
            variablesEl.classList.remove('d-none');
            variablesEl.required = true;

            dateEl.classList.add('d-none');
            dateEl.required = false;
        }
    }
}
