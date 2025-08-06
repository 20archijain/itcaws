import { Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges, ViewChild } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { NgbDate, NgbDateParserFormatter, NgbInputDatepicker } from '@ng-bootstrap/ng-bootstrap';
import { Subscription } from 'rxjs';

import { FormService } from 'src/app/core/services/form.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { ControlMaxDate } from 'src/app/core/interfaces/helpers.interface';

@Component({
  selector: 'app-daterange',
  styleUrls: [
    './daterange.component.scss'
  ],
  templateUrl: './daterange.component.html',
})
export class DateRangeComponent implements OnChanges, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @ViewChild('d', { static: false }) private input: NgbInputDatepicker;
  @Input() private fromValidators = null;
  @Input() private toValidators = null;
  @Input() private defaultFromValue = '';
  @Input() private defaultToValue = '';
  @Output() private onClose = new EventEmitter();
  @Input() protected fromErrorMessages: string[] = [];
  @Input() protected toErrorMessages: string[] = [];
  @Input() protected disable = false;
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'range';
  @Input() fromControlName = 'from';
  @Input() toControlName = 'to';
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() isRequired = false;
  @Input() showIcon = false;
  @Input() icon = '';
  @Input() label = '';
  @Input() showValidationState = true;
  @Input() hide = false;
  @Input() isCurrentDateMin: boolean;
  @Input() minDate: ControlMaxDate;
  @Input() maxDate: ControlMaxDate;
  ngbMinDate: ControlMaxDate;
  ngbMaxDate: ControlMaxDate;
  errorMessageFrom = '';
  errorMessageTo = '';
  isInvalid = false;
  hoveredDate: NgbDate;
  fromDate: NgbDate;
  toDate: NgbDate;
  formGroup: UntypedFormGroup;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, protected ngbDateParserFormatter: NgbDateParserFormatter) {
  }

  ngOnChanges(changes: SimpleChanges) {
    // remove control if hide is true
    if (changes && changes.hide && this.group) {
      if (this.group.get(this.controlName) && changes.hide.currentValue) {
        this.group.removeControl(this.controlName);
      } else {
        if (!this.group.get(this.controlName)) {
          this.group.addControl(this.controlName,
            new UntypedFormGroup({
              [this.fromControlName]: new UntypedFormControl(this.defaultFromValue, this.fromValidators),
              [this.toControlName]: new UntypedFormControl(this.defaultToValue, this.toValidators),
            }));
        }
      }
    }

    // disable the control
    if (changes && changes.disable && this.group.get(this.controlName)) {
      if (changes.disable.currentValue) {
        this.group.get(this.controlName).disable();
      } else {
        this.group.get(this.controlName).enable();
      }
    }

    // set min date
    if (changes && changes.minDate && changes.minDate.currentValue && this.group.get(this.controlName)) {
      this.ngbMinDate = Functions.currentDate(`${this.minDate.year}-${this.minDate.month}-${this.minDate.day}`);
    }

    // set max date
    if (changes && changes.maxDate && changes.maxDate.currentValue && this.group.get(this.controlName)) {
      this.ngbMaxDate = Functions.currentDate(`${this.maxDate.year}-${this.maxDate.month}-${this.maxDate.day}`);
    }
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: this.fb.group({
          [this.fromControlName]: ['', this.fromValidators],
          [this.toControlName]: ['', this.toValidators],
        }),
      });
    }
    this.formGroup = this.group.get(this.controlName) as UntypedFormGroup;

    // set current date as minimum date
    if (this.isCurrentDateMin) {
      this.ngbMinDate = Functions.currentDate();
    }

    this.subscription.push(
      this.inputField.statusChanges
        .subscribe(() => this.resetError(null))
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get inputFromField() {
    return this.group && this.group.get(this.controlName) && this.group.get(this.controlName).get(this.fromControlName);
  }

  get inputToField() {
    return this.group && this.group.get(this.controlName) && this.group.get(this.controlName).get(this.toControlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onClosed($event: Event = null) {
    this.resetError($event);
    this.onClose.emit($event);
  }

  resetError($event: Event) {
    if ($event && $event.stopPropagation) {
      $event.stopPropagation();
    }
    this.checkError();
  }

  checkError() {
    const respFrom = this.formService.getValidationError(this.inputFromField, this.fromErrorMessages);
    const respTo = this.formService.getValidationError(this.inputToField, this.toErrorMessages);
    this.isInvalid = respFrom.isInvalid || respTo.isInvalid;
    this.errorMessageFrom = respFrom.errorMessage;
    this.errorMessageTo = respTo.errorMessage;
  }

  isHovered(date: NgbDate) {
    return this.fromDate && !this.toDate && this.hoveredDate && date.after(this.fromDate) && date.before(this.hoveredDate);
  }

  isInside(date: NgbDate) {
    return date.after(this.fromDate) && date.before(this.toDate);
  }

  isRange(date: NgbDate) {
    return date.equals(this.fromDate) || date.equals(this.toDate) || this.isInside(date) || this.isHovered(date);
  }

  onDateSelection(date: NgbDate) {
    if (!this.fromDate && !this.toDate) {
      this.fromDate = date;
      this.group.get(this.controlName).get(this.fromControlName).setValue(this.fromDate);
    } else if (this.fromDate && !this.toDate && (date.equals(this.fromDate) || date.after(this.fromDate))) {
      this.toDate = date;
      this.group.get(this.controlName).get(this.fromControlName).setValue(this.fromDate);
      this.group.get(this.controlName).get(this.toControlName).setValue(this.toDate);
      this.input.close();
    } else {
      this.toDate = null;
      this.fromDate = date;
      this.group.get(this.controlName).get(this.fromControlName).setValue(this.ngbDateParserFormatter.format(this.fromDate));
    }
  }

  clearForm() {
    this.fromDate = null;
    this.toDate = null;
  }
}
