import { Component, Input, OnChanges, OnInit, SimpleChanges } from '@angular/core';
import { UntypedFormArray, UntypedFormGroup } from '@angular/forms';

import { InputComponent } from '../input/input.component';
import { InputConfig } from 'src/app/core/interfaces/helpers.interface';
import { CONTROL_CONFIG } from 'src/app/app.constants';
import { DropdownList } from 'src/app/core/interfaces/http-response.interface';

@Component({
  selector: 'app-input-dynamic',
  templateUrl: './input-dynamic.component.html'
})
export class InputDynamicComponent extends InputComponent implements OnChanges, OnInit {
  @Input() inputConfig: InputConfig[] = [];
  typeConfig = CONTROL_CONFIG;

  ngOnChanges(changes: SimpleChanges) {
    // remove control if hide is true
    if (changes && changes.hide && this.group) {
      if (this.group.get(this.controlName) && changes.hide.currentValue) {
        this.group.removeControl(this.controlName);
      } else {
        if (!this.group.get(this.controlName)) {
          this.group.addControl(this.controlName,
            new UntypedFormArray([this.createDynamicControl()]));
        }
      }
    }
  }

  ngOnInit() {
    if (this.dynamicControl && this.dynamicControl.length === 0) {
      this.addMoreControl();
    }
  }

  get dynamicControl() {
    return this.group.get(this.controlName) as UntypedFormArray;
  }

  addMoreControl() {
    if (this.dynamicControl) {
      this.dynamicControl.push(this.createDynamicControl());
    }
  }

  createDynamicControl() {
    const controls = {};
    if (this.inputConfig && this.inputConfig.length) {
      this.inputConfig.forEach(control => {
        controls[control.controlName] = [null, control.validators];
      });
    }

    return this.fb.group(controls);
  }

  removeControl(index: number) {
    if (this.dynamicControl.at(index)) {
      this.dynamicControl.removeAt(index);
    }
  }

  onChangeOption(controlIndex: number, configIndex: number, controlName: string, isDisableAllowed: boolean) {
    if (isDisableAllowed) {
      const selectedValues = this.dynamicControl.controls.map((formGroup: UntypedFormGroup) => {
        return formGroup.controls[controlName].value;
      });

      (this.inputConfig[configIndex].options as DropdownList[]).forEach((option: DropdownList) => {
        if (selectedValues.indexOf(option.value) > -1) {
          option.disabled = true;
        } else {
          option.disabled = false;
        }
      });
    }
  }
}
