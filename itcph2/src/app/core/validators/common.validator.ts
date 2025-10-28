import { AbstractControl, UntypedFormArray, UntypedFormGroup, ValidatorFn } from '@angular/forms';

import { CUSTOM_VALIDATOR_KEYS } from 'src/app/app.constants';
import { PasswordConfig } from '../interfaces/helpers.interface';
import { ListingService } from '../services/listing.service';

export const FILE_SIZE_VALIDATOR = (allowedMaxSizeInBytes: number, currentSizeInBytes?: number): ValidatorFn => {
  return (control: AbstractControl): { [key: string]: any } => {

    if (control && allowedMaxSizeInBytes && currentSizeInBytes > allowedMaxSizeInBytes) {
      return { [CUSTOM_VALIDATOR_KEYS.FILE_SIZE]: true };
    }

    return null;
  };
};

export const FILE_TYPE_VALIDATOR = (allowedFileTypes: string, file: File): ValidatorFn => {
  return (control: AbstractControl): { [key: string]: any } => {

    if (control && allowedFileTypes && file) {
      const listingService = new ListingService(null);

      if (!listingService.isFileTypeValid(file, allowedFileTypes)) {
        return { [CUSTOM_VALIDATOR_KEYS.FILE_TYPE]: true };
      }
    }

    return null;
  };
};

export const REGEX_VALIDATOR = (validRegex: RegExp, isOptional?: boolean): ValidatorFn => {
  return (control: AbstractControl): { [key: string]: any } => {
    const input = control.value;
    let isInvalidValueFound = false;

    if (!input) {
      return null;
    }

    if (Array.isArray(input)) {
      if (isOptional && input.length === 0) {
        return null;
      }

      if (input.length > 0) {
        for (let index = 0; index < input.length; index++) {
          if (!validRegex.test(input[index])) {
            isInvalidValueFound = true;
            break;
          }
        }
      }

      if (isInvalidValueFound) {
        return { [CUSTOM_VALIDATOR_KEYS.INVALID_PATTERN]: true };
      }
    } else if (!validRegex.test(input)) {
      return { [CUSTOM_VALIDATOR_KEYS.INVALID_PATTERN]: true };
    }

    return null;
  };
};

export const CONFIRM_PASSWORD_VALIDATOR = (config: PasswordConfig): ValidatorFn => {
  return (form: UntypedFormGroup): { [key: string]: any } => {

    if (!form || !form.controls ||
      !form.controls[config.newPass] || !form.controls[config.newPass].value ||
      !form.controls[config.confPass] || !form.controls[config.confPass].value) {
      return null;
    }

    if (form.controls[config.newPass].valid && form.controls[config.confPass].valid &&
      form.controls[config.newPass].value !== form.controls[config.confPass].value) {
      return { [CUSTOM_VALIDATOR_KEYS.PASSWOR_NOT_MATCH]: true };
    }

    return null;
  };
};

// Used to validate entire form group or array
// Check if user has filled any textbox with value (> 0 if includeZeroAsQty = true else >= 0)
// If index >= 0, check form group else form array
export const ATLEAST_ONE_MATERIAL_REQUIRED_VALIDATOR = (index?: number, includeZeroAsQty = false): ValidatorFn => {
  return (form: UntypedFormArray): { [key: string]: any } => {
    if (!form || !form.value || form.value.length === 0) {
      return null;
    }
    const values = [];
    // get values from form group
    if (!(index === undefined || index === null)) {
      for (const control in form.value) {
        if (control) {
          values.push(form.value[control]);
        }
      }
    } else {
      // get values from form array
      form.value.forEach(qtyControl => {
        if (qtyControl) {
          for (const control in qtyControl) {
            if (control) {
              values.push(qtyControl[control]);
            }
          }
        }
      });
    }

    if (values && values.length > 0) {
      const isSomeValueFilled = values.some(value => value && ((includeZeroAsQty && +value >= 0) || (!includeZeroAsQty && +value > 0)));
      if (isSomeValueFilled) {
        return null;
      }

      return { [CUSTOM_VALIDATOR_KEYS.ATLEAST_ONE_QUANTITY_REQUIRED]: true };
    }

    return null;
  };
};

// export const GROUPED_VALUES_ALL_OR_NONE_VALIDATOR = (
//   groupSize: number,
//   index?: number,
//   includeZeroAsQty = false
// ): ValidatorFn => {
//   return (form: UntypedFormArray): { [key: string]: any } | null => {
//     if (!form || !form.value || form.value.length === 0 || groupSize <= 1) {
//       return null;
//     }

//     const values: any[] = [];

//     // Extract values
//     if (index !== undefined && index !== null) {
//       for (const control in form.value) {
//         if (control) {
//           values.push(form.value[control]);
//         }
//       }
//     } else {
//       form.value.forEach(qtyControl => {
//         if (qtyControl) {
//           for (const control in qtyControl) {
//             if (control) {
//               values.push(qtyControl[control]);
//             }
//           }
//         }
//       });
//     }

//     // Utility to determine if a value is "filled"
//     const isFilled = (val: any) =>
//       val !== null &&
//       val !== undefined &&
//       val !== '' &&
//       !isNaN(+val) &&
//       (includeZeroAsQty ? +val >= 0 : +val > 0);

//     // Check each group
//     for (let i = 0; i < values.length; i += groupSize) {
//       const group = values.slice(i, i + groupSize);
//       const filledCount = group.filter(val => isFilled(val)).length;

//       if (filledCount > 0 && filledCount < group.length) {
//         return { [CUSTOM_VALIDATOR_KEYS.ALL_VALUES_IN_GROUP_REQUIRED]: true };
//       }
//     }

//     return null;
//   };
// };


export const GROUPED_VALUES_ALL_OR_NONE_VALIDATOR = (
  groupSize: number,
  index?: number,
  includeZeroAsQty = false
): ValidatorFn => {
  return (form: UntypedFormArray): { [key: string]: any } | null => {
    if (!form || !form.value || form.value.length === 0 || groupSize <= 1) {
      return null;
    }

    const values: any[] = [];

    // Extract values
    if (index !== undefined && index !== null) {
      for (const control in form.value) {
        if (control) {
          values.push(form.value[control]);
        }
      }
    } else {
      form.value.forEach(qtyControl => {
        if (qtyControl) {
          for (const control in qtyControl) {
            if (control) {
              values.push(qtyControl[control]);
            }
          }
        }
      });
    }

    // Utility to determine if a value is "filled"
    const isFilled = (val: any) =>
      val !== null &&
      val !== undefined &&
      val !== '' &&
      !isNaN(+val) &&
      (includeZeroAsQty ? +val >= 0 : +val > 0);

    // ---- Rule 1: All-or-none validation ----
    for (let i = 0; i < values.length; i += groupSize) {
      const group = values.slice(i, i + groupSize);
      const filledCount = group.filter(val => isFilled(val)).length;

      if (filledCount > 0 && filledCount < group.length) {
        return { allValuesInGroupRequired: true };
      }
    }
    // Only if there are at least 3 values in the form
    if (values.length >= 3) {
      const val0 = +values[0] || 0;
      const val1 = +values[1] || 0;
      const val2 = +values[2] || 0;

      if (val2 < val0 + val1) {
        return { enterMoreThanFocusBands: true };
      }
    }

    return null;
  };
};

export const ATLEAST_ONE_VALUE_REQUIRED_VALIDATOR = (index?: number): ValidatorFn => {
  return (form: UntypedFormArray): { [key: string]: any } => {
    if (!form || !form.value || form.value.length === 0) {
      return null;
    }

    const values = [];
    // get values from form group
    if (!(index === undefined || index === null)) {
      for (const control in form.value) {
        if (control) {
          values.push(form.value[control]);
        }
      }
    } else {
      // get values from form array
      // Form Group
      if (form && form.controls) {
        Object.keys(form.controls).forEach(controlKey => {
          // Form array
          if (form.controls[controlKey] && form.controls[controlKey].controls) {
            if (form.controls[controlKey].controls.length) {
              form.controls[controlKey].controls.forEach(control => {
                // Form group
                if (control && control.controls && Object.keys(control.controls).length) {
                  Object.keys(control.controls).forEach(innerControl => {
                    values.push(control.controls[innerControl].value);
                  });
                } else {
                  values.push(control.value);
                }
              });
            } else {
              if (Object.keys(form.controls[controlKey].controls).length) {
                Object.keys(form.controls[controlKey].controls).forEach(innerControl => {
                  values.push(form.controls[controlKey].controls[innerControl].value);
                });
              } else {
                values.push(form.controls[controlKey].value);
              }
            }
          } else {
            // Form group
            values.push(form.controls[controlKey].value);
          }
        });
      }
    }

    // Don't allow '0' i.e just 0
    if (values && values.length > 0) {
      const isSomeValueFilled = values.some(value => {
        return value && (parseFloat(value) > 0 || isNaN(parseFloat(value))) && value.length;
      });

      if (isSomeValueFilled) {
        return null;
      }

      return { [CUSTOM_VALIDATOR_KEYS.ATLEAST_ONE_VALUE_REQUIRED]: true };
    }

    return null;
  };
};
