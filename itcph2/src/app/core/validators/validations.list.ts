import { Validators } from '@angular/forms';

import { REGEX_VALIDATOR } from './common.validator';
import {
  ALNUM_S_REGEX, ALNUM_S_U_H_REGEX, ALPHA_HYP_REGEX, ALPHA_REGEX, CAPTCHA_REGEX, DESCRIPTION_REGEX, EMAIL_REGEX, JSON_REGEX,
  MOBILE_REGEX_WITH_CODE_OR_ZERO,
  MODULE_CODE_REGEX, MODULE_URL_REGEX, NON_ZERO_NUMBER_AND_ALL_REGEX, NON_ZERO_NUMBER_REGEX,
  NUMBER_REGEX, PASSWORD_REGEX, POSITIVE_FLOAT_NUMBER_REGEX, STOCK_NUMBER_REGEX, STRONG_PASSWORD_REGEX, USERNAME_REGEX,
} from './regex';
import {
  CUSTOM_VALIDATION_LENGTH,
  CUSTOM_VALIDATOR,
  FILESIZE_VALIDATOR,
  FILETYPE_VALIDATOR,
  MAXLENGTH_VALIDATOR,
  MAXVALUE_VALIDATOR,
  MINLENGTH_VALIDATOR,
  MINVALUE_VALIDATOR,
  PATTERN_VALIDATOR,
  REQUIRED_VALIDATOR,
  VALIDATION_LENGTH
} from './validators.list';
import { CONSTANTS, UPLOAD_FILES } from '../../app.constants';

export const FORGOT_VALIDATORS = {
  messages: {
    email: [
      MAXLENGTH_VALIDATOR('Email', VALIDATION_LENGTH.EMAIL_MAXLENGTH, VALIDATION_LENGTH.EMAIL_MINLENGTH),
      MINLENGTH_VALIDATOR('Email', VALIDATION_LENGTH.EMAIL_MINLENGTH, VALIDATION_LENGTH.EMAIL_MAXLENGTH),
      PATTERN_VALIDATOR('Email'),
      REQUIRED_VALIDATOR('Email'),
    ],
  },
  validators: {
    email: [
      REGEX_VALIDATOR(EMAIL_REGEX),
      Validators.minLength(VALIDATION_LENGTH.EMAIL_MINLENGTH),
      Validators.maxLength(VALIDATION_LENGTH.EMAIL_MAXLENGTH),
      Validators.required,
    ],
  },
};

export const LOGIN_VALIDATORS = {
  messages: {
    captcha: [
      MAXLENGTH_VALIDATOR('Captcha', VALIDATION_LENGTH.CAPTCHA_MAXLENGTH, VALIDATION_LENGTH.CAPTCHA_MINLENGTH),
      MINLENGTH_VALIDATOR('Captcha', VALIDATION_LENGTH.CAPTCHA_MINLENGTH, VALIDATION_LENGTH.CAPTCHA_MAXLENGTH),
      PATTERN_VALIDATOR('Captcha'),
      REQUIRED_VALIDATOR('Captcha'),
    ],
    password: [
      MAXLENGTH_VALIDATOR('Password', VALIDATION_LENGTH.PASSWORD_MAXLENGTH, VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      MINLENGTH_VALIDATOR('Password', VALIDATION_LENGTH.PASSWORD_MINLENGTH, VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      PATTERN_VALIDATOR('Password', CONSTANTS.STRONG_PASSWORD_FUNC ? 'tooltip.strongPasswordBody' : 'err.pattern'),
      REQUIRED_VALIDATOR('Password'),
    ],
    username(maxlength?: number) {
      return [
        MAXLENGTH_VALIDATOR('Username', maxlength ? maxlength : VALIDATION_LENGTH.USERNAME_MAXLENGTH, VALIDATION_LENGTH.USERNAME_MINLENGTH),
        MINLENGTH_VALIDATOR('Username', VALIDATION_LENGTH.USERNAME_MINLENGTH, maxlength ? maxlength : VALIDATION_LENGTH.USERNAME_MAXLENGTH),
        PATTERN_VALIDATOR('Username', 'err.username.pattern'),
        REQUIRED_VALIDATOR('Username'),
      ];
    },
  },
  validators: {
    captcha: [
      REGEX_VALIDATOR(CAPTCHA_REGEX),
      Validators.minLength(VALIDATION_LENGTH.CAPTCHA_MINLENGTH),
      Validators.maxLength(VALIDATION_LENGTH.CAPTCHA_MAXLENGTH),
      Validators.required,
    ],
    password: [
      REGEX_VALIDATOR(CONSTANTS.STRONG_PASSWORD_FUNC ? STRONG_PASSWORD_REGEX : PASSWORD_REGEX),
      Validators.minLength(VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      Validators.maxLength(VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      Validators.required,
    ],
    passwordOptional: [
      REGEX_VALIDATOR(CONSTANTS.STRONG_PASSWORD_FUNC ? STRONG_PASSWORD_REGEX : PASSWORD_REGEX),
      Validators.minLength(VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      Validators.maxLength(VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
    ],
    username(isRequired = true, maxlength?: number) {
      const validators = [
        REGEX_VALIDATOR(USERNAME_REGEX),
        Validators.minLength(VALIDATION_LENGTH.USERNAME_MINLENGTH),
        Validators.maxLength(maxlength ? maxlength : VALIDATION_LENGTH.USERNAME_MAXLENGTH),
      ];

      if (isRequired) {
        validators.push(Validators.required);
      }

      return validators;
    },
  },
};

export const USER_VALIDATORS = {
  messages: {
    confirmPassword: [
      MAXLENGTH_VALIDATOR('Confirm Password', VALIDATION_LENGTH.PASSWORD_MAXLENGTH, VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      MINLENGTH_VALIDATOR('Confirm Password', VALIDATION_LENGTH.PASSWORD_MINLENGTH, VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      PATTERN_VALIDATOR('Confirm Password'),
      REQUIRED_VALIDATOR('Confirm Password'),
    ],
  },
};

export const GROUP_VALIDATORS = {
  messages: {
    name: [
      MAXLENGTH_VALIDATOR('Group Name', VALIDATION_LENGTH.NAME_MAXLENGTH),
      PATTERN_VALIDATOR('Group Name'),
      REQUIRED_VALIDATOR('Group Name'),
    ],
  },
  validators: {
    name: [
      REGEX_VALIDATOR(ALNUM_S_REGEX),
      Validators.maxLength(VALIDATION_LENGTH.NAME_MAXLENGTH),
      Validators.required,
    ],
  },
};

export const MODULE_VALIDATORS = {
  messages: {
    breadcrumb: [
      PATTERN_VALIDATOR('Show Breadcrumb'),
      MAXVALUE_VALIDATOR('Show Breadcrumb', VALIDATION_LENGTH.MINVALUE),
      MINVALUE_VALIDATOR('Show Breadcrumb', VALIDATION_LENGTH.MINVALUE - 1),
      REQUIRED_VALIDATOR('Show Breadcrumb'),
    ],
    icon: [
      MAXLENGTH_VALIDATOR('Module Icon', CUSTOM_VALIDATION_LENGTH.MODULE_ICON_MAXLENGTH),
      MINLENGTH_VALIDATOR('Module Icon', VALIDATION_LENGTH.MINLENGTH, CUSTOM_VALIDATION_LENGTH.MODULE_ICON_MAXLENGTH),
      PATTERN_VALIDATOR('Module Icon'),
    ],
    id: [
      PATTERN_VALIDATOR('Module ID'),
      MINVALUE_VALIDATOR('Module ID'),
    ],
    modc(controlText: string) {
      return [
        MAXLENGTH_VALIDATOR(controlText, CUSTOM_VALIDATION_LENGTH.MODULE_CODE_MAXLENGTH),
        MINLENGTH_VALIDATOR(controlText, VALIDATION_LENGTH.MINLENGTH, CUSTOM_VALIDATION_LENGTH.MODULE_CODE_MAXLENGTH),
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    moduleComponent: [
      MAXLENGTH_VALIDATOR('Module Component', CUSTOM_VALIDATION_LENGTH.MODULE_COMPONENT_MAXLENGTH),
      MINLENGTH_VALIDATOR('Module Component', VALIDATION_LENGTH.MINLENGTH, CUSTOM_VALIDATION_LENGTH.MODULE_COMPONENT_MAXLENGTH),
      PATTERN_VALIDATOR('Module Component'),
    ],
    modulePos: [
      MAXLENGTH_VALIDATOR('Module Position', CUSTOM_VALIDATION_LENGTH.MODULE_POSITION_MAXLENGTH),
      MINLENGTH_VALIDATOR('Module Position', VALIDATION_LENGTH.MINLENGTH, CUSTOM_VALIDATION_LENGTH.MODULE_POSITION_MAXLENGTH),
      PATTERN_VALIDATOR('Module Position'),
      REQUIRED_VALIDATOR('Module Position'),
    ],
    url: [
      MAXLENGTH_VALIDATOR('Module URL'),
      MINLENGTH_VALIDATOR('Module URL'),
      PATTERN_VALIDATOR('Module URL'),
    ],
  },
  validators: {
    breadcrumb: [
      REGEX_VALIDATOR(NUMBER_REGEX),
      Validators.max(VALIDATION_LENGTH.MINVALUE),
      Validators.min(VALIDATION_LENGTH.MINVALUE - 1),
      Validators.required,
    ],
    icon: [
      REGEX_VALIDATOR(ALPHA_HYP_REGEX),
      Validators.maxLength(CUSTOM_VALIDATION_LENGTH.MODULE_ICON_MAXLENGTH),
      Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
    ],
    id: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_REGEX),
      Validators.min(VALIDATION_LENGTH.MINVALUE),
    ],
    modc: [
      REGEX_VALIDATOR(MODULE_CODE_REGEX),
      Validators.maxLength(CUSTOM_VALIDATION_LENGTH.MODULE_CODE_MAXLENGTH),
      Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
      Validators.required,
    ],
    moduleComponent: [
      REGEX_VALIDATOR(ALPHA_REGEX),
      Validators.maxLength(CUSTOM_VALIDATION_LENGTH.MODULE_COMPONENT_MAXLENGTH),
      Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
    ],
    moduleActionCode(maxLength: number) {
      return [
        REGEX_VALIDATOR(ALNUM_S_U_H_REGEX),
        Validators.maxLength(maxLength),
        Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
        Validators.required,
      ];
    },
    url: [
      REGEX_VALIDATOR(MODULE_URL_REGEX),
      Validators.maxLength(VALIDATION_LENGTH.MAXLENGTH),
      Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
    ],
  },
};

export const PROFILE_VALIDATORS = {
  messages: {
    confirmNewPassword: [
      MAXLENGTH_VALIDATOR('Confirm New Password', VALIDATION_LENGTH.PASSWORD_MAXLENGTH, VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      MINLENGTH_VALIDATOR('Confirm New Password', VALIDATION_LENGTH.PASSWORD_MINLENGTH, VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      PATTERN_VALIDATOR('Confirm New Password'),
      REQUIRED_VALIDATOR('Confirm New Password'),
    ],
    currentPassword: [
      MAXLENGTH_VALIDATOR('Current Password', VALIDATION_LENGTH.PASSWORD_MAXLENGTH, VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      MINLENGTH_VALIDATOR('Current Password', VALIDATION_LENGTH.PASSWORD_MINLENGTH, VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      PATTERN_VALIDATOR('Current Password'),
      REQUIRED_VALIDATOR('Current Password'),
    ],
    email: [
      MAXLENGTH_VALIDATOR('Email', VALIDATION_LENGTH.EMAIL_MAXLENGTH, VALIDATION_LENGTH.EMAIL_MINLENGTH),
      MINLENGTH_VALIDATOR('Email', VALIDATION_LENGTH.EMAIL_MINLENGTH, VALIDATION_LENGTH.EMAIL_MAXLENGTH),
      PATTERN_VALIDATOR('Email'),
    ],
    name: [
      MAXLENGTH_VALIDATOR('Name', VALIDATION_LENGTH.NAME_MAXLENGTH),
      PATTERN_VALIDATOR('Name'),
      REQUIRED_VALIDATOR('Name'),
    ],
    newPassword: [
      MAXLENGTH_VALIDATOR('New Password', VALIDATION_LENGTH.PASSWORD_MAXLENGTH, VALIDATION_LENGTH.PASSWORD_MINLENGTH),
      MINLENGTH_VALIDATOR('New Password', VALIDATION_LENGTH.PASSWORD_MINLENGTH, VALIDATION_LENGTH.PASSWORD_MAXLENGTH),
      PATTERN_VALIDATOR('New Password'),
      REQUIRED_VALIDATOR('New Password'),
    ],
  },
  validators: {
    email: [
      REGEX_VALIDATOR(EMAIL_REGEX),
      Validators.minLength(VALIDATION_LENGTH.EMAIL_MINLENGTH),
      Validators.maxLength(VALIDATION_LENGTH.EMAIL_MAXLENGTH),
    ],
  },
};

export const COMMON_VALIDATORS = {
  messages: {
    date(controlText: string) {
      return [
        REQUIRED_VALIDATOR(controlText),
        CUSTOM_VALIDATOR('ngbDate', 'err.ngbDateError', controlText),
      ];
    },
    dropdown(controlText: string) {
      return [
        PATTERN_VALIDATOR(controlText),
        MINVALUE_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    dropdownAll(controlText: string) {
      return [
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    dropdownAllOptional(controlText: string) {
      return [
        PATTERN_VALIDATOR(controlText),
      ];
    },
    dropdownNA(controlText: string) {
      return [
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    dropdownStringValue(controlText: string) {
      return [
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    file(controlText: string, maxFileSizeInBytes = UPLOAD_FILES.maxFileSizeInBytes, allowedFileTypes = UPLOAD_FILES.fileTypes.imageOnly.fileExtensions) {
      return [
        REQUIRED_VALIDATOR(controlText),
        FILESIZE_VALIDATOR(controlText, maxFileSizeInBytes),
        FILETYPE_VALIDATOR(controlText, allowedFileTypes),
      ];
    },
    mobile(controlText: string) {
      return [
        MAXLENGTH_VALIDATOR(controlText, VALIDATION_LENGTH.MOBILE_MAXLENGTH, VALIDATION_LENGTH.MOBILE_MINLENGTH),
        MINLENGTH_VALIDATOR(controlText, VALIDATION_LENGTH.MOBILE_MINLENGTH, VALIDATION_LENGTH.MOBILE_MAXLENGTH),
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    name(controlText: string, nameMaxlength?: number) {
      return [
        MAXLENGTH_VALIDATOR(controlText, nameMaxlength || VALIDATION_LENGTH.NAME_MAXLENGTH),
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    zeroAndFloatQtyStock: [
      MINVALUE_VALIDATOR('Quantity', VALIDATION_LENGTH.MINVALUE - 1),
      MAXLENGTH_VALIDATOR('Quantity', VALIDATION_LENGTH.QTY_MAXLENGTH + 3),
      PATTERN_VALIDATOR('Quantity'),
    ],
    requiredOnly(controlText: string) {
      return [REQUIRED_VALIDATOR(controlText)];
    },
    description(controlText: string, maxlength?: number) {
      return [
        MAXLENGTH_VALIDATOR(controlText, maxlength || VALIDATION_LENGTH.DESC_MAXLENGTH),
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText),
      ];
    },
    zeroQty: [
      MINVALUE_VALIDATOR('Quantity', VALIDATION_LENGTH.MINVALUE - 1),
      MAXLENGTH_VALIDATOR('Quantity', VALIDATION_LENGTH.QTY_MAXLENGTH),
      PATTERN_VALIDATOR('Quantity'),
    ],
  },
  validators: {
    date: [
      Validators.required,
    ],
    dropdown: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_REGEX),
      Validators.min(VALIDATION_LENGTH.MINVALUE),
      Validators.required,
    ],
    dropdownAll: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_AND_ALL_REGEX),
      Validators.required,
    ],
    dropdownAllOptional: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_AND_ALL_REGEX, true),
    ],
    dropdownNA: [
      REGEX_VALIDATOR(NUMBER_REGEX),
      Validators.required,
    ],
    dropdownNAOptional: [
      REGEX_VALIDATOR(NUMBER_REGEX),
    ],
    dropdownOptional: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_REGEX),
    ],
    dropdownStringValue: [
      REGEX_VALIDATOR(ALNUM_S_REGEX),
      Validators.required,
    ],
    dropdownStringValueOptional: [
      REGEX_VALIDATOR(ALNUM_S_REGEX),
    ],
    zeroAndFloatQtyStock: [
      REGEX_VALIDATOR(STOCK_NUMBER_REGEX),
      Validators.min(VALIDATION_LENGTH.MINVALUE - 1),
      Validators.maxLength(VALIDATION_LENGTH.QTY_MAXLENGTH + 3),
    ],
    file(isRequired = false) {
      const validators = [];

      if (isRequired) {
        validators.push(Validators.required);
      }

      return validators;
    },
    mobile(isRequired = false) {
      const validators = [
        REGEX_VALIDATOR(MOBILE_REGEX_WITH_CODE_OR_ZERO),
        Validators.maxLength(VALIDATION_LENGTH.MOBILE_MAXLENGTH),
        Validators.minLength(VALIDATION_LENGTH.MOBILE_MINLENGTH),
      ];

      if (isRequired) {
        validators.push(Validators.required);
      }

      return validators;
    },
    name(nameMaxlength?: number, isRequired = true) {
      const validators = [
        REGEX_VALIDATOR(ALNUM_S_REGEX),
        Validators.maxLength(nameMaxlength || VALIDATION_LENGTH.NAME_MAXLENGTH),
      ];

      if (isRequired) {
        validators.push(Validators.required);
      }

      return validators;
    },
    description(maxlength?: number, isRequired?: boolean) {
      const validators = [
        REGEX_VALIDATOR(DESCRIPTION_REGEX),
        Validators.maxLength(maxlength || VALIDATION_LENGTH.DESC_MAXLENGTH),
      ];

      if (isRequired) {
        validators.push(Validators.required);
      }

      return validators;
    },
    requiredOnly: [
      Validators.required,
    ],
    zeroQty: [
      REGEX_VALIDATOR(NUMBER_REGEX),
      Validators.min(VALIDATION_LENGTH.MINVALUE - 1),
      Validators.maxLength(VALIDATION_LENGTH.QTY_MAXLENGTH),
    ],
    zeroAndFloatQty: [
      REGEX_VALIDATOR(POSITIVE_FLOAT_NUMBER_REGEX),
      Validators.min(VALIDATION_LENGTH.MINVALUE - 1),
      Validators.maxLength(VALIDATION_LENGTH.QTY_MAXLENGTH),
    ],
  },
};

export const CLIENT_VALIDATORS = {
  messages: {
    desc: [
      MAXLENGTH_VALIDATOR('Client Description', VALIDATION_LENGTH.DESC_MAXLENGTH),
      PATTERN_VALIDATOR('Client Description'),
    ],
  },
  validators: {
    desc: [
      REGEX_VALIDATOR(ALNUM_S_REGEX),
      Validators.maxLength(VALIDATION_LENGTH.DESC_MAXLENGTH),
    ],
  },
};

export const TEAM_VALIDATORS = {
  messages: {
    index(controlText: string) {
      return [
        MINLENGTH_VALIDATOR(controlText),
        MINVALUE_VALIDATOR(controlText),
        PATTERN_VALIDATOR(controlText),
        REQUIRED_VALIDATOR(controlText)
      ];
    },
    json: [
      MAXLENGTH_VALIDATOR('JSON', VALIDATION_LENGTH.JSON_MAXLENGTH),
      PATTERN_VALIDATOR('JSON'),
      REQUIRED_VALIDATOR('JSON'),
    ],
    wdCode: [
      MAXLENGTH_VALIDATOR('WD Code', VALIDATION_LENGTH.WD_CODE_MAXLENGTH),
      PATTERN_VALIDATOR('WD Code'),
      REQUIRED_VALIDATOR('WD Code'),
    ],
  },
  validators: {
    index: [
      REGEX_VALIDATOR(NON_ZERO_NUMBER_REGEX),
      Validators.minLength(VALIDATION_LENGTH.MINLENGTH),
      Validators.min(VALIDATION_LENGTH.MINVALUE),
      Validators.required
    ],
    json: [
      REGEX_VALIDATOR(JSON_REGEX),
      Validators.maxLength(VALIDATION_LENGTH.JSON_MAXLENGTH),
      Validators.required,
    ],
    name: [
      REGEX_VALIDATOR(ALNUM_S_U_H_REGEX),
      Validators.maxLength(CUSTOM_VALIDATION_LENGTH.TEAM_NAME_MAXLENGTH),
      Validators.required,
    ],
    wdCode: [
      REGEX_VALIDATOR(ALNUM_S_U_H_REGEX),
      Validators.maxLength(VALIDATION_LENGTH.WD_CODE_MAXLENGTH),
      Validators.required,
    ],
  },
};
