<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class Validation
{
    protected $var_name; //Input Name in form
    protected $var_value; //Input value
    protected $var_type; //Type of value accept
    protected $var_req_flag; //Required, By default is 0(Not required)
    protected $var_minLength; //Minimum characters, By default is 0 charcaters
    protected $var_maxLength; //Maximum characters, By default is 100 charcaters
    protected $var_message; //Message on error, By default is 'is invalid'
    protected $var_minValue; //Minimum value, By default is null
    protected $var_maxValue; //Maximum value, By default is null

    public $arrErrors = []; //Return Errors
    private $_arrValidator = [];

    public function addValidation($var_name, $var_value, $var_type, $var_req_flag = 0, $var_minLength = 0, $var_maxLength = 100, $var_message = 'is invalid', $min_value = null, $max_value = null)
    {
        $arrValid = [];
        $this->var_name = $var_name;
        $this->var_value = filter($var_value);
        $this->var_type = $var_type;
        $this->var_req_flag = $var_req_flag;
        $this->var_minLength = $var_minLength;
        $this->var_maxLength = $var_maxLength;
        $this->var_message = $var_message;
        $this->var_minValue = $min_value;
        $this->var_maxValue = $max_value;

        array_push($arrValid, $this->var_name, $this->var_value, $this->var_type, $this->var_req_flag, $this->var_minLength, $this->var_maxLength, $this->var_message, $this->var_minValue, $this->var_maxValue);

        array_push($this->_arrValidator, $arrValid);
    }

    public function getErrors()
    {
        $arrErrors = [];

        if (isNonEmptyArray($this->arrErrors)) {
            foreach ($this->arrErrors as $error) {
                $arrErrors[] = $error;
            }
        }
        return $arrErrors;
    }

    public function validateForm()
    {
        foreach ($this->_arrValidator as $arrInput) {
            $this->validateInput($arrInput);
        }

        if ($this->arrErrors) {
            return false;
        }
        return true;
    }

    private function validateInput($arrInput)
    {
        $sName = $arrInput[0];
        $sValue = trim($arrInput[1]);
        $sType = $arrInput[2];
        $iRequired = $arrInput[3];
        $iMinLength = $arrInput[4];
        $iMaxLength = $arrInput[5];
        $sMsg = $arrInput[6];
        $iMinValue = $arrInput[7];
        $iMaxValue = $arrInput[8];

        if ($iRequired === 1 && empty($sValue) && $sValue !== "0") {
            $this->arrErrors[$sName] = "$sMsg cannot be blank";
        } elseif (isset($sValue) && $sValue !== "") {
            // Check the min and max value
            if (!is_null($iMinValue) && $sValue < $iMinValue) {
                $this->arrErrors[$sName] = "$sMsg should be atleast $iMinValue";
            } elseif (!is_null($iMaxValue) && $sValue > $iMaxValue) {
                $this->arrErrors[$sName] = "$sMsg should not exceed $iMaxValue";
            } elseif ((!is_null($iMinLength) && strlen($sValue) < $iMinLength) || (!is_null($iMaxLength) && strlen($sValue) > $iMaxLength)) {
                //Check the length of value
                $this->arrErrors[$sName] = "$sMsg must be $iMinLength to $iMaxLength characters long";
            } else {
                //Check for its type

                // Alphabets only
                if ($sType === 'alpha') {
                    $sRegex = "/^[a-zA-Z]+$/";
                    if (!(preg_match($sRegex, $sValue) && ctype_alpha($sValue))) {
                        $this->arrErrors[$sName] = "$sMsg can contain only alphabets";
                    }
                } elseif ($sType == 'alpha_s') {
                    // Alphabets and spaces only
                    $sRegex = "/^[a-zA-Z]+([ ][a-zA-Z]+)*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet and can contain only alphabets and space";
                    }
                } elseif ($sType == 'alnum') {
                    // Alphanumeric only
                    $sRegex = "/^[a-zA-Z][a-zA-Z0-9]*$/";
                    if (!(preg_match($sRegex, $sValue) && ctype_alnum($sValue))) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet and can contain only alphabets and digits";
                    }
                } elseif ($sType == 'alnum_s') {
                    // Alphanumeric and spaces only
                    $sRegex = "/^[a-zA-Z0-9]+([ ][a-zA-Z0-9]+)*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet or digit and can contain only alphabets, space and digits";
                    }
                } elseif ($sType == 'alpha_h') {
                    // Alphabets and hyphen only
                    $sRegex = "/^[a-zA-Z]+(\-?[a-zA-Z]+)?$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet and can contain only alphabets and hyphen";
                    }
                } elseif ($sType == 'alnum_s_u_h') {
                    // Alphabets, digits, space, underscore and hyphen only
                    $sRegex = "/^[a-zA-Z0-9]+([ \_\-][a-zA-Z0-9]+)*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet and can contain only alphabets, numbers, space, underscore and hyphen";
                    }
                } elseif ($sType == 'pz_num') {
                    // Positive Numbers only and can start with 0
                    $sRegex = "/^\d+$/";
                    if (!(preg_match($sRegex, $sValue) && ctype_digit($sValue))) {
                        $this->arrErrors[$sName] = "$sMsg can contain only positive number";
                    }
                } elseif ($sType == 'pnz_num') {
                    // Positive Numbers only and doesn't start with 0
                    $sRegex = "/^[1-9]\d*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg can contain only positive non-zero number";
                    }
                } elseif ($sType == 'pnz_num_all') {
                    // All or Positive Numbers only and doesn't start with 0
                    $sRegex = '/^' . $GLOBALS["APP_CONSTANTS"]["ALL_VALUE"] . '|[1-9]\d*$/';
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg can contain only positive non-zero number";
                    }
                } elseif ($sType == 'pn_num') {
                    // Positive or Negative Numbers only
                    $sRegex = "/^-?\d*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg can contain only positive or negative number";
                    }
                } elseif ($sType == 'float') {
                    // Decimal or Floating Numbers only
                    $sRegex = "/^-?\d+(\.\d+)?$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg can contain only positive or nagative float number";
                    }
                } elseif ($sType == 'token') {
                    // User token only
                    $sRegex = "/^[a-zA-Z0-9]+$/";
                    if (!(preg_match($sRegex, $sValue) && ctype_alnum($sValue))) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'mobile') {
                    // Mobile number w/o country code only
                    $sRegex = "/^(0)?\d+$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with 6, 7, 8, or 9 and can contain only number";
                    }
                } elseif ($sType == 'mobile_wcc') {
                    // Mobile number with country code only
                    $sRegex = "/^(\+91|0)?[6789]\d{9}$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with 6, 7, 8, or 9 and can contain only number with optional country code or 0";
                    }
                } elseif ($sType == 'email') {
                    // Email only
                    $sRegex = "/^[a-zA-Z0-9][\w\.\-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,8}$/";
                    if (!(preg_match($sRegex, $sValue) || filter_var($sValue, FILTER_VALIDATE_EMAIL))) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'username') {
                    // username only
                    $sRegex = "/^[a-zA-Z][a-zA-Z0-9]*([\.\_\-][a-zA-Z0-9]+)*$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with an alphabet and can contain only alphabets, number, ./-/_ (followed by alphabet or number)";
                    }
                } elseif ($sType == 'pwd') {
                    // password only
                    if (constant("STRONG_PASSWORD_CHECK_FUNC")) {
                        $sRegex = "/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\!\@\#\$\%\^\*\=\+\(\)])(?!.*[\[\]\&\-\_\{\}\;\:\"\'\.\,\?\/\\\|])/";
                    } else {
                        $sRegex = "/^[a-zA-Z0-9\!\@\#\$\%\^\*\_\=\+\(\)\.]+$/";
                    }
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'address') {
                    // Address only
                    $sRegex = "/^[\w\-\., ]+$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'description') {
                    // Description only
                    $sRegex = "/^[\w\,\?\'\@\-\_\.\r\n ]+$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'date') {
                    // Date only, Format YYYY-MM-DD
                    $sRegex = "/^(19|20)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "Enter valid $sMsg in YYYY-MM-DD format";
                    }
                } elseif ($sType == 'url') {
                    //URL
                    $sRegex = "/^(19|20)\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])$/";
                    if (!filter_var($sValue, FILTER_VALIDATE_URL)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'modc') {
                    //Module code
                    $sRegex = "/^(0|([A-Z]+[0-9]+))$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg can be either 0 or must start with capital alphabets followed by numbers";
                    }
                } elseif ($sType == 'mod_url') {
                    //Module url
                    $sRegex = "/^[a-zA-Z]+[0-9]*(\/[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*)*(.php)$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg must start with alphabet and can contain numbers, /, _ and must end with .php extension";
                    }
                } elseif ($sType == 'captcha') {
                    //captcha
                    $sRegex = "/^[a-zA-Z0-9\@\#\$\%]+$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                } elseif ($sType == 'json') {
                    // json
                    $sRegex = "/^[a-zA-Z0-9\_\-]+(\.json)$/";
                    if (!preg_match($sRegex, $sValue)) {
                        $this->arrErrors[$sName] = "$sMsg is invalid";
                    }
                }
            }
        }
    }
}
