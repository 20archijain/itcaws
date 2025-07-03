import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';

import { DropdownList } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { CONSTANTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-select',
  styleUrls: [
    './select.component.scss',
  ],
  templateUrl: './select.component.html',
})
export class SelectComponent implements OnChanges, OnInit {
  @Input() private validators = null;
  @Input() private defaultValue = null;
  @Output() private onBlur = new EventEmitter();
  @Output() private onFocus = new EventEmitter();
  @Output() private onClose = new EventEmitter();
  @Output() private onChange = new EventEmitter<DropdownList>();
  @Input() protected errorMessages: string[] = [];
  @Input() protected disable = false;
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'select';
  @Input() options: DropdownList[] = [];
  @Input() multiple = false;
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() isRequired = false;
  @Input() hide = false;
  @Input() showValidationState = true;
  @Input() bindLabel = 'label';
  @Input() bindValue = 'value';
  @Input() groupBy = '';
  @Input() label = '';
  @Input() placeholder = '';
  @Input() resetIfAll = true;
  @Input() scrollToTopOnFocus = false;
  @Input() id = '';
  isInvalid = false;
  errorMessage = '';

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
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
    }
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onFocusIn($event: FocusEvent) {
    this.resetError();
    this.onFocus.emit();
    if (this.scrollToTopOnFocus) {
      Functions.scrollToFocusElement($event.target as HTMLElement, 30);
    }
  }

  onFocusOut() {
    this.resetError();
    this.onBlur.emit();
  }

  onClosed() {
    this.resetError();
    this.onClose.emit();
  }

  onValueChange($event: DropdownList) {
    if (!this.multiple) {
      this.resetError();
      this.onChange.emit($event);
    }
  }

  onModelChange($event: DropdownList, isAdded?: boolean) {
    this.resetError();
    if (isAdded && this.group && this.group.get(this.controlName) && $event && ($event.value || (this.groupBy && $event[this.groupBy]))
      && this.multiple && this.resetIfAll) {
      const value: string[] = this.group.get(this.controlName).value;
      // If all selected, remove others
      if ($event.value === CONSTANTS.ALL_VALUE) {
        // if some value already selected
        if (value.length > 1) {
          this.group.get(this.controlName).setValue([$event.value]);
        }
      } else {
        // if other option selected, remove All

        if (value) {
          const index = value.indexOf(CONSTANTS.ALL_VALUE);
          if (index > -1) {
            value.splice(index, 1);
            this.group.get(this.controlName).setValue(value);
          }
        }
      }
    }
    this.onChange.emit($event);
  }

  resetError() {
    this.checkError();
  }

  checkError() {
    const resp = this.formService.getValidationError(this.inputField, this.errorMessages);
    this.isInvalid = resp.isInvalid;
    this.errorMessage = resp.errorMessage;
  }
}
