import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { find } from 'ramda';

import { FormControlErrorMessage } from 'src/app/core/interfaces/common.interface';
import { FormService } from 'src/app/core/services/form.service';

@Component({
  selector: 'app-dropdown',
  templateUrl: './dropdown.component.html',
  standalone: false,
})
export class DropdownComponent implements OnChanges, OnInit {
  @Input() private validators = null;
  @Input() private defaultValue = '';
  @Input() private valueKey = 'value';
  @Output() private onChange = new EventEmitter();
  @Output() private onFocus = new EventEmitter();
  @Output() private onBlur = new EventEmitter();
  @Input() protected errorMessages: FormControlErrorMessage[] = [];
  @Input() protected disable = false;
  @Input() label = '';
  @Input() controlName = 'dropdown';
  @Input() isRequired = false;
  @Input() showValidationState = true;
  @Input() hide = false;
  @Input() group!: UntypedFormGroup;
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() showBlankOption = true;
  @Input() blankOptionLabel = 'listing.pleaseSelect';
  @Input() options: any[] = [];
  @Input() labelKey = 'label';
  errorMessage = '';
  isInvalid = false;

  constructor(private formService: FormService, private fb: UntypedFormBuilder) {
  }

  ngOnChanges(changes: SimpleChanges) {
    // remove control if hide is true
    if (changes && changes.hide && this.group) {
      if (this.group.get(this.controlName) && changes.hide.currentValue) {
        this.group.removeControl(this.controlName);
      } else {
        if (!this.group.get(this.controlName)) {
          this.group.addControl(this.controlName,
            new UntypedFormControl(this.defaultValue, this.validators));
        }
      }
    }

    // disable the control
    if (changes && changes.disable && this.group.get(this.controlName)) {
      if (changes.disable.currentValue) {
        this.group.get(this.controlName)?.disable();
      } else {
        this.group.get(this.controlName)?.enable();
      }
    }
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
    } else if (this.group.get(this.controlName) &&
      !(this.group.get(this.controlName)?.value === null || this.group.get(this.controlName)?.value === '')) {
      this.onChange.emit(null);
    }
  }

  getLabel(option: any) {
    if (option) {
      if (typeof option === 'string' || typeof option === 'number') {

        // used to show the label instead of value in brick container
        if (this.valueKey) {
          const foundOption = this.options ? find((list: any) => list[this.valueKey] === option)(this.options) : '';
          if (foundOption) {
            return foundOption[this.labelKey];
          }

          return option;
        }

        return option;
      } else {
        if (this.labelKey) {
          return option[this.labelKey];
        }
      }
    }

    return '';
  }

  getValue(option: any) {
    if (option) {
      if (typeof option === 'string' || typeof option === 'number') {
        return option;
      } else {
        if (this.valueKey) {
          return option[this.valueKey];
        }

        return JSON.stringify(option);
      }
    }

    return '';
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onOptionChange($event: Event | KeyboardEvent) {
    this.resetError($event);
    this.onChange.emit($event);
  }

  onFocusOut($event: Event) {
    this.resetError($event);
    this.onBlur.emit($event);
  }

  onFocusIn($event: Event) {
    this.resetError($event);
    this.onFocus.emit($event);
  }

  resetError($event: Event) {
    if ($event && $event.stopPropagation) {
      $event.stopPropagation();
    }
    this.checkError();
  }

  checkError() {
    const resp = this.formService.getValidationError(this.inputField, this.errorMessages);
    this.isInvalid = resp.isInvalid;
    this.errorMessage = resp.errorMessage;
  }
}
