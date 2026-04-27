import { Component, EventEmitter, HostListener, Input, OnChanges, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';

import { FormControlErrorMessage } from 'src/app/core/interfaces/common.interface';
import { FormService } from 'src/app/core/services/form.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-input',
  templateUrl: './input.component.html',
  standalone: false,
})
export class InputComponent implements OnChanges, OnInit {
  @Input() private validators = null;
  @Input() private defaultValue = '';
  @Output() private onKeyupOut = new EventEmitter();
  @Output() private onFocus = new EventEmitter();
  @Output() private onBlur = new EventEmitter();
  @Input() protected errorMessages: FormControlErrorMessage[] = [];
  @Input() protected disable = false;
  @Input() type = 'text';
  @Input() label = '';
  @Input() icon = '';
  @Input() placeholder = '';
  @Input() controlName = 'input';
  @Input() showIcon = false;
  @Input() isRequired = false;
  @Input() showValidationState = true;
  @Input() autocompleteOff = false;
  @Input() hide = false;
  @Input() tooltipDirection = 'top';
  @Input() tooltipContent = '';
  @Input() group!: UntypedFormGroup;
  @Input() isHorizontalForm = false;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() inputClass = '';
  @Input() id = '';
  @Input() scrollToTopOnFocus = false;
  errorMessage = '';
  isInvalid = false;
  innerWidth = 0;

  constructor(private formService: FormService, protected fb: UntypedFormBuilder) {
  }

  @HostListener('window:resize')
  onWindowResize() {
    this.innerWidth = window.innerWidth;
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
    this.innerWidth = window.innerWidth;

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

  onKeyup($event: KeyboardEvent) {
    this.resetError($event);
    this.onKeyupOut.emit($event);
  }

  onFocusOut($event: FocusEvent) {
    this.resetError($event);
    this.onBlur.emit($event);
  }

  onFocusIn($event: FocusEvent) {
    this.resetError($event);
    this.onFocus.emit($event);
    if (this.scrollToTopOnFocus && this.innerWidth < 1200) {
      Functions.scrollToFocusElement($event.target as HTMLElement, 30);
    }
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
