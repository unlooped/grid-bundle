import {FilterType} from "./FilterType";

export class AutocompleteTextFilterType extends FilterType {

    public updateValueInput(row: Element, fieldName: string, config: any) {
        super.updateValueInput(row, fieldName, config);

        // @ts-ignore
        jQuery('.select2text[data-autostart="true"]').select2entity();
    }
}
