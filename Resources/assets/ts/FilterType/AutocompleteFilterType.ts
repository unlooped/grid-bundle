import {FilterType} from "./FilterType";

export class AutocompleteFilterType extends FilterType {

    public updateValueInput(row: Element, fieldName: string, config: any) {
        super.updateValueInput(row, fieldName, config);

        // @ts-ignore
        jQuery('.select2entity[data-autostart="true"]').select2entity();
    }
}
