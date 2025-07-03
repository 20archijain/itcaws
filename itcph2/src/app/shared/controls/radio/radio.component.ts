import { Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { DropdownList } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';

@Component({
  selector: 'app-radio',
  templateUrl: './radio.component.html'
})
export class RadioComponent implements OnChanges, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() private validators = null;
  @Input() private defaultValue = '';
  @Output() private onChange = new EventEmitter();
  @Input() protected errorMessages: string[] = [];
  @Input() options: DropdownList<any>[] = [];
  @Input() inline = false;
  @Input() hide = false;
  @Input() showValidationState = true;
  @Input() isRequired = false;
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'radio';
  @Input() label = '';
  @Input() labelClassName = 'form-label';
  errorMessage = '';
  isInvalid = false;

  constructor(private formService: FormService, private fb: UntypedFormBuilder) { }

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
    if (!this.group || !this.group.get(this.controlName)) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
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

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onOptionChange($event: Event) {
    this.resetError($event);
    this.onChange.emit({ event: $event, value: this.group.get(this.controlName).value });
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
