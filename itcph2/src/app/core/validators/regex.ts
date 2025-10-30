/* eslint-disable no-useless-escape */
import { CONSTANTS } from 'src/app/app.constants';

export const USERNAME_REGEX = /^[A-Za-z][A-Za-z0-9]*([\.\_\-]+[0-9A-Za-z]+)*$/;

export const PASSWORD_REGEX = /^[a-zA-Z0-9\!\@\#\$\%\^\*\_\=\+\(\)\.]+$/;

export const STRONG_PASSWORD_REGEX =
  /(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\!\@\#\$\%\^\*\=\+\(\)])(?!.*[\[\]\&\-\_\{\}\;\:\"\'\.\,\?\/\\\|])/;

export const CAPTCHA_REGEX = /^[a-zA-Z0-9\@\#\$\%]+$/;

export const EMAIL_REGEX = /^[a-zA-Z0-9][\w\.\-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,8}$/;

export const NAME_REGEX = /^[a-zA-Z]+([ ][a-zA-Z]+)*$/;

export const NAME_WITH_NUMBER_REGEX = /^[a-zA-Z]+([ ][a-zA-Z0-9]+)*$/;

export const MOBILE_REGEX = /^(0)?\d+$/;

export const MOBILE_REGEX_WITH_CODE_OR_ZERO = /^(\+91|0)?[6789]\d{9}$/;

export const ADDRESS_REGEX = /^[\w\-\., ]+$/;

export const ALNUM_S_REGEX = /^[a-zA-Z0-9]+([ ][a-zA-Z0-9]+)*$/;

export const STOCK_NUMBER_REGEX = /^(?:[0-9]{1,7}|\.\d{1,2}|[0-9]{1,7}\.\d{1,2})$/;

export const STOCK_NUMBER_MAX_3_REGEX = /^(?:[0-9]{1,3}|\.\d{1,2}|[0-9]{1,3}\.\d{1,2})$/;

export const NON_ZERO_NUMBER_REGEX = /^[1-9]\d*$/;

export const NON_ZERO_NUMBER_AND_ALL_REGEX = new RegExp(`^${CONSTANTS.ALL_VALUE}|[1-9]\\d*$`);

export const NUMBER_REGEX = /^[0-9]+$/;

export const POSITIVE_FLOAT_NUMBER_REGEX = /^\d*(\.\d+)?$/;

export const ALPHA_HYP_REGEX = /^[a-zA-Z]+(\-?[a-zA-Z]+)*$/;

export const MODULE_CODE_REGEX = /^(0|([A-Z]+[0-9]+))$/;

export const MODULE_URL_REGEX = /^[a-zA-Z]+((\_|\-)?[a-zA-Z0-9]+)*((\/[a-zA-Z]+((\_|\-)?[a-zA-Z0-9]+)*)*(.php)|\/)$/;

export const ALPHA_REGEX = /^[a-zA-Z]+$/;

export const ALNUM_S_U_H_REGEX = /^[a-zA-Z0-9]+([ \_\-][a-zA-Z0-9]+)*$/;

export const DESCRIPTION_REGEX = /^[\w\,\?\'\@\-\_\.\r\n ]+$/;

export const JSON_REGEX = /^[a-zA-Z0-9\_\-]+(\.json)$/;
