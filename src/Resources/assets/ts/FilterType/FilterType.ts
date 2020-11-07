export class FilterType {

    constructor(options: any[] = []) {

    }

    public attachEventListeners(row: Element) {
        // nothing to do
    }

    public fieldWithFilterChosen(row: Element, fieldName: string, config: any) {
        let operatorSelect = <HTMLSelectElement>row.querySelector('select[name$="[operator]"]');
        let currentValue = null;
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

        if (currentValue !== null) {
            operatorSelect.value = currentValue;
        }
    }

    public updateValueInput(row: Element, fieldName: string, config: any) {
        let container = <HTMLInputElement>row.querySelector('.filter-value-container');
        container.innerHTML = config.template.replace(/__name__/g, row.getAttribute('data-row'));
    }
}
