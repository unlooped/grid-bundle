export class FilterType {

    constructor(options: any[] = []) {
    }

    public getSelectedValue(select: HTMLSelectElement) {
        let currentValue = null;
        if (select.selectedIndex !== null) {
            currentValue = select.options[select.selectedIndex].value;
        }

        return currentValue;
    }

    public attachEventListeners(row: Element) {
        let conditionField = <HTMLSelectElement>row.querySelector('select[name$="[operator]"]');
        conditionField.addEventListener('change', (event: Event) => {
            this.conditionUpdated(row);
        });

        this.conditionUpdated(row);
    }

    protected getSelectedCondition(row: Element) {
        return this.getSelectedValue(row.querySelector('select[name$="[operator]"]'));
    }

    protected conditionUpdated(row: Element) {
        let valueContainer = row.querySelector('.filter-value-container');
        if (this.getSelectedCondition(row) === 'is_empty') {
            valueContainer.classList.add('d-none');
        } else {
            valueContainer.classList.remove('d-none');
        }
    }

    public fieldWithFilterChosen(row: Element, fieldName: string, config: any) {
        let operatorSelect = <HTMLSelectElement>row.querySelector('select[name$="[operator]"]');
        let currentValue = config?.options?.default_data?.operator ?? null;
        if (operatorSelect.selectedIndex) {
            currentValue = operatorSelect.options[operatorSelect.selectedIndex].value;
        }

        if (operatorSelect.hasChildNodes()) {
            while (operatorSelect.firstChild) {
                operatorSelect.removeChild(operatorSelect.firstChild);
            }
        }

        let operators:string[] = config.operators;
        for (let operator in operators) {
            let label = operators[operator];
            let option = document.createElement('option');
            option.setAttribute('value', operator);
            option.innerText = label;

            operatorSelect.appendChild(option);
        }

        if (currentValue !== null && Object.keys(operators).indexOf(currentValue) !== -1) {
            operatorSelect.value = currentValue;
        } else {
            operatorSelect.value = Object.keys(operators)[0];
        }
    }

    public updateValueInput(row: Element, fieldName: string, config: any) {
        let container = <HTMLInputElement>row.querySelector('.filter-value-container');
        container.innerHTML = config.template.replace(/__name__/g, row.getAttribute('data-row'));
    }
}
