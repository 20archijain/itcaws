import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { NgbDate } from '@ng-bootstrap/ng-bootstrap';

import { FormService } from 'src/app/core/services/form.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { ControlMaxDate } from 'src/app/core/interfaces/helpers.interface';
import { FormControlErrorMessage } from 'src/app/core/interfaces/common.interface';

@Component({
  selector: 'app-date',
  templateUrl: './date.component.html',
  standalone: false,
})
export class DateComponent implements OnChanges, OnInit {
  @Input() private validators = null;
  @Input() private defaultValue = null;
  @Output() private onClose = new EventEmitter();
  @Input() protected errorMessages: FormControlErrorMessage[] = [];
  @Input() protected disable = false;
  @Input() group!: UntypedFormGroup;
  @Input() controlName = 'date';
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() isRequired = false;
  @Input() showIcon = false;
  @Input() icon = '';
  @Input() label = '';
  @Input() showValidationState = true;
  @Input() onlyMonthSelection = false;
  @Input() hide = false;
  @Input() placeholder = '';
  @Input() isCurrentDateMin?: boolean;
  @Input() isCurrentDateMax?: boolean;
  @Input() minDate?: ControlMaxDate;
  @Input() maxDate?: ControlMaxDate;
  ngbMinDate: ControlMaxDate | null = null;
  ngbMaxDate: ControlMaxDate | null = null;
  errorMessage = '';
  isInvalid = false;
  markDisabledFn = this.markDisabled.bind(this);

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

    // set min date
    if (changes && changes.minDate && changes.minDate.currentValue && this.group.get(this.controlName) &&
      Functions.isValidDate(changes.minDate.currentValue)) {
      const month = this.minDate ? (+this.minDate.month < 10 ? `0${this.minDate.month}` : this.minDate.month) : '';
      const day = this.minDate ? (+this.minDate.day < 10 ? `0${this.minDate.day}` : this.minDate.day) : '';
      this.ngbMinDate = this.minDate ? Functions.currentDate(`${this.minDate.year}-${month}-${day}`) : null;
    } else {
      if (this.isCurrentDateMin) {
        this.ngbMinDate = Functions.currentDate();
      } else {
        this.ngbMinDate = null;
      }
    }

    // set max date
    if (changes && changes.maxDate && changes.maxDate.currentValue && this.group.get(this.controlName) &&
      Functions.isValidDate(changes.maxDate.currentValue)) {
      const month = this.maxDate ? (+this.maxDate.month < 10 ? `0${this.maxDate.month}` : this.maxDate.month) : '';
      const day = this.maxDate ? (+this.maxDate.day < 10 ? `0${this.maxDate.day}` : this.maxDate.day) : '';
      this.ngbMaxDate = this.maxDate ? Functions.currentDate(`${this.maxDate.year}-${month}-${day}`) : null;
    } else {
      if (this.isCurrentDateMax) {
        this.ngbMaxDate = Functions.currentDate();
      } else {
        this.ngbMaxDate = null;
      }
    }
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
    }

    // set current date as minimum date
    if (this.isCurrentDateMin) {
      this.ngbMinDate = Functions.currentDate();
    }
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onClosed($event: Event | null = null) {
    this.resetError($event);
    this.onClose.emit($event);
  }

  onBlur($event: FocusEvent) {
    this.resetError($event);
  }

  resetError($event: Event | null) {
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

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  markDisabled(date: NgbDate, current?: { year: number, month: number }) {
    return this.onlyMonthSelection ? date.day !== 1 : false;
  }
}
