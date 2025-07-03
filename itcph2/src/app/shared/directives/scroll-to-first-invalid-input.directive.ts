import { Directive, ElementRef, HostListener, Input } from '@angular/core';
import { AbstractControl, UntypedFormArray, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { find } from 'ramda';

import { CustomPolyfill } from 'src/app/core/utils/polyfills';
import { Functions } from 'src/app/core/utils/functions.list';

@Directive({
  selector: '[appScrollToFirstInvalidInput]',
})
export class ScrollToFirstInvalidInputDirective {
  @Input() private appScrollToFirstInvalidInput: UntypedFormGroup;
  tagNames = [
    'INPUT',
    'SELECT',
    'TEXTAREA',
  ];
  control: HTMLElement;

  constructor(private el: ElementRef) { }

  @HostListener('click')
  onClick() {
    // do nothing if form is valid
    if (this.form) {
      if (this.form.valid) {
        return true;
      } else {
        let controls = [];
        // Get control names
        for (const key in this.form.controls) {
          // eslint-disable-next-line no-prototype-builtins
          if (this.form.controls.hasOwnProperty(key)) {
            controls.push(key);
            // Form Group
            if (this.form.controls[key] instanceof UntypedFormGroup) {
              const formGroupControls = Object.keys((this.form.controls[key] as UntypedFormGroup).controls);
              controls = [...controls, ...formGroupControls];
            } else if (this.form.controls[key] instanceof UntypedFormArray) {
              // Form Array
              const formArray = this.form.controls[key] as UntypedFormArray;
              const formArrayControls = formArray.controls[0] ? Object.keys((formArray.controls[0] as UntypedFormGroup).controls) : [];
              controls = [...controls, ...formArrayControls];
            }
          }
        }

        if (controls && controls.length > 0) {
          // Check if all fields are valid
          if (this.areAllFieldsValid(controls)) {
            return true;
          } else {
            const isAnyInvalidInputElm = find((control: string) => {
              return this.form.controls[control] && this.form.controls[control].invalid;
            })(controls);

            // Invalid field found
            if (isAnyInvalidInputElm) {
              // const el = this.el.nativeElement.parentNode.parentNode.parentNode;
              const el1 = this.el.nativeElement.parentNode?.parentNode;
              const el2 = this.el.nativeElement.parentNode?.parentNode?.parentNode;
              const el3 = this.el.nativeElement.parentNode?.parentNode?.parentNode?.parentNode;
              const el4 = this.el.nativeElement.parentNode?.parentNode?.parentNode?.parentNode?.parentNode;

              const inputs1: HTMLElement[] = CustomPolyfill.from(el1.querySelectorAll('.ng-invalid[id]'));
              const inputs2: HTMLElement[] = CustomPolyfill.from(el2.querySelectorAll('.ng-invalid[id]'));
              const inputs3: HTMLElement[] = CustomPolyfill.from(el3.querySelectorAll('.ng-invalid[id]'));
              const inputs4: HTMLElement[] = CustomPolyfill.from(el4.querySelectorAll('.ng-invalid[id]'));

              let invalidInputFound = false;
              // Loop through all invalid elements to focus so that error message can be displayed
              if (inputs1?.length || inputs2?.length || inputs3?.length || inputs4?.length) {
                const inputs = inputs1?.length ? inputs1 : (inputs2?.length ? inputs2 : (inputs3?.length ? inputs3 : inputs4));
                let count = 1;
                inputs.reverse().forEach((input: HTMLElement) => {
                  const inputField = find((control: string) => (input.id).indexOf(control) > -1)(controls);

                  // Focus on invalid input field
                  if (inputField) {
                    this.findInputControl(input);
                    if (this.control && this.control.focus) {
                      this.control.focus();
                    }
                    invalidInputFound = true;

                    // scroll till first invalid input
                    if (count === inputs.length) {
                      Functions.scrollToFocusElement(input, 0, true);
                    }
                    count++;
                  }
                });
              }

              // update validity if any invalid input found
              if (invalidInputFound) {
                controls.forEach(control => {
                  if (this.form.controls[control]
                    && this.form.controls[control].invalid) {
                    this.touchControl(this.form.controls[control]);
                  }
                });
              }
            } else {
              return true;
            }
          }
        }
      }
    }
  }

  areAllFieldsValid(controls: string[]) {
    return controls.every(control => this.form.controls[control] && this.form.controls[control].valid);
  }

  touchControl(control: AbstractControl | UntypedFormControl | UntypedFormGroup | UntypedFormArray) {
    if (control) {
      if (control['controls']) {
        // FormArray
        if (control instanceof UntypedFormArray) {
          (control as UntypedFormArray).controls.forEach(ctrl => {
            this.touchControl(ctrl);
          });
        } else {
          // FormGroup
          const controls = Object.keys(control['controls']);
          controls.forEach(formControl => {
            if (control['controls'][formControl]
              && control['controls'][formControl].invalid) {
              this.touchControl(control['controls'][formControl]);
            }
          });
        }
      } else {
        // FormControl
        control.markAsTouched();
        control.updateValueAndValidity();
      }
    }
  }

  findInputControl(control) {
    if (control && this.tagNames.indexOf(control.tagName) > -1) {
      return this.control = control;
    } else {
      if (control && control.children) {
        for (const item of control.children) {
          const formElement = item.tagName === 'INPUT' ? [item] : item.getElementsByTagName('INPUT');
          if (formElement) {
            this.findInputControl(formElement[0]);
            break;
          }
        }
      }
    }
  }

  get form() {
    return this.appScrollToFirstInvalidInput;
  }
}
