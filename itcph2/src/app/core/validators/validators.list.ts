import { CUSTOM_VALIDATOR_KEYS, UPLOAD_FILES } from 'src/app/app.constants';

export const VALIDATION_LENGTH = {
  ADDRESS_MAXLENGTH: 100,
  CAPTCHA_MAXLENGTH: 6,
  CAPTCHA_MINLENGTH: 5,
  DESC_MAXLENGTH: 150,
  EMAIL_MAXLENGTH: 50,
  EMAIL_MINLENGTH: 6,
  JSON_MAXLENGTH: 60,
  MAXLENGTH: 100,
  MAXVALUE: 10000,
  MINLENGTH: 1,
  MINVALUE: 1,
  MOBILE_MAXLENGTH: 13,
  MOBILE_MINLENGTH: 10,
  NAME_MAXLENGTH: 40,
  PASSWORD_MAXLENGTH: 16,
  PASSWORD_MINLENGTH: 2,
  QTY_MAXLENGTH: 7,
  USERNAME_MAXLENGTH: 20,
  USERNAME_MINLENGTH: 2,
  WD_CODE_MAXLENGTH: 20,
};

export const CUSTOM_VALIDATION_LENGTH = {
  CLIENT_NAME_MAXLENGTH: 50,
  MODULE_CODE_MAXLENGTH: 10,
  MODULE_COMPONENT_MAXLENGTH: 100,
  MODULE_ICON_MAXLENGTH: 30,
  MODULE_NAME_MAXLENGTH: 60,
  MODULE_POSITION_MAXLENGTH: 15,
  PROJECT_NAME_MAXLENGTH: 60,
  TEAM_NAME_MAXLENGTH: 50,
  USERNAME_MAXLENGTH: 50,
};

export const FILESIZE_VALIDATOR =
  (name = 'File', maxFileSizeInBytes = UPLOAD_FILES.maxFileSizeInBytes, message = 'err.fileSize') => (
    { name, errorName: CUSTOM_VALIDATOR_KEYS.FILE_SIZE, fileSize: Math.round(maxFileSizeInBytes / (1024 * 1024)), message }
  );
export const FILETYPE_VALIDATOR =
  (name = 'File', allowedFileTypes = UPLOAD_FILES.fileTypes.imageOnly.fileExtensions, message = 'err.fileType') => ({ name, errorName: CUSTOM_VALIDATOR_KEYS.FILE_TYPE, fileType: allowedFileTypes.join(', '), message });

export const MAXLENGTH_VALIDATOR =
  (name: string, maxLength = VALIDATION_LENGTH.MAXLENGTH, minLength = VALIDATION_LENGTH.MINLENGTH, message = 'err.length') => ({
    maxLength, minLength, name, errorName: 'maxlength',
    message: minLength === maxLength ? 'err.sameLength' : message
  });

export const MINLENGTH_VALIDATOR =
  (name: string, minLength = VALIDATION_LENGTH.MINLENGTH, maxLength = VALIDATION_LENGTH.MAXLENGTH, message = 'err.length') => ({
    maxLength, minLength, name, errorName: 'minlength',
    message: minLength === maxLength ? 'err.sameLength' : message
  });

export const MAXVALUE_VALIDATOR =
  (name: string, maxValue = VALIDATION_LENGTH.MAXVALUE, message = 'err.max') => ({ errorName: 'max', maxValue, message, name });

export const MINVALUE_VALIDATOR =
  (name: string, minValue = VALIDATION_LENGTH.MINVALUE, message = 'err.min') => ({ errorName: 'min', minValue, message, name });

export const PATTERN_VALIDATOR = (name = 'value', message = 'err.pattern') => ({
  message, name,
  errorName: CUSTOM_VALIDATOR_KEYS.INVALID_PATTERN,
});

export const CUSTOM_VALIDATOR = (errorName: string, message: string, name = 'value') => ({
  errorName, message, name,
});

export const REQUIRED_VALIDATOR = (name = '', message = 'err.required') => ({ errorName: 'required', message, name });
