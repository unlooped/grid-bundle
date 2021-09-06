import {FilterType} from "./FilterType";

export class ChoiceFilterType extends FilterType {

    public updateValueInput(row: Element, fieldName: string, config: any) {
        super.updateValueInput(row, fieldName, config);

        if (config.use_select2) {
            // @ts-ignore
            jQuery('.initSelect2').select2();
        }
    }
}
