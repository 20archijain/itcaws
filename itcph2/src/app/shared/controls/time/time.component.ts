import { Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { FormService } from 'src/app/core/services/form.service';

@Component({
    selector: 'app-time',
    templateUrl: './time.component.html',
    standalone: false
})
export class TimeComponent implements OnChanges, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() private validators = null;
  @Input() private defaultValue = '';
  @Output() private onChange = new EventEmitter();
  @Input() protected errorMessages: string[] = [];
  @Input() protected disable = false;
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'time';
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() isRequired = false;
  @Input() label = '';
  @Input() showValidationState = true;
  @Input() hide = false;
  @Input() meridian = true;
  @Input() spinners = true;
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
        this.group.get(this.controlName).disable();
      } else {
        this.group.get(this.controlName).enable();
      }
    }
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
    }

    if (this.group && this.group.get(this.controlName)) {
      this.subscription.push(
        this.group.get(this.controlName).valueChanges
          .subscribe(() => {
            this.resetError();
            this.onChange.emit();
          })
      );
    }
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  resetError($event?: Event) {
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
