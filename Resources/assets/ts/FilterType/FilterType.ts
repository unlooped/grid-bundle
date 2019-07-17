export class FilterType {

    constructor(options: any[] = []) {

    }


    public fieldWithFilterChosen(row: Element, fieldName: string, config: any) {
        console.log('a');
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
        let valueInput = <HTMLInputElement>row.querySelector('input[name$="[value]"]');

        valueInput.setAttribute('type', config.options.widget);
    }
}
