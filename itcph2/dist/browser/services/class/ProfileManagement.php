<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ProfileManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $data, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }

    private function checkProfileValidation($name, $email)
    {
        $obj = new Validation();
        $obj->addValidation('name', $name, 'alnum_s', 1, 1, $this->_validationLength['NAME_MAXLENGTH'], 'Name');
        $obj->addValidation('email', $email, 'email', 0, $this->_validationLength['EMAIL_MINLENGTH'], $this->_validationLength['EMAIL_MAXLENGTH'], 'Email');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function changePasswordValidation($current, $new, $confirm)
    {
        $obj = new Validation();
        $obj->addValidation('current', $current, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Current password');
        $obj->addValidation('new', $new, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'New password');
        $obj->addValidation('confirm', $confirm, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Confirm New Password');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function editProfile()
    {
        $name = getFormData($this->_data['name']);
        $email = getFormData($this->_data['email']);

        $isValidated = $this->checkProfileValidation($name, $email);

        //inputs validated
        if ($isValidated) {
            $cols = "usr_fullname = ?, usr_email = ?";

            $iStatus = updateRecord($this->_dbConn, $this->_tables["USER_AUTHDETAILS_TABLE"], $cols, "dstatus = 0 AND user_id = ?", [$name, $email, $this->_iUserId]);

            if ($iStatus == 1) {
                $arrMessage = responseMessage([$GLOBALS['PROFILE_UPDATED_SUCCESSFULL']], 1);
            } else {
                $arrMessage = responseMessage([$GLOBALS['PROFILE_NOT_UPDATED']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function changePassword()
    {
        $current = getFormData($this->_data['currentPassword']);
        $new = getFormData($this->_data['newPassword']);
        $confirmnew = getFormData($this->_data['confirmNewPassword']);

        $isValidated = $this->changePasswordValidation($current, $new, $confirmnew);

        //inputs validated
        if ($isValidated) {
            //password criteria not match
            if (constant("STRONG_PASSWORD_CHECK_FUNC") && !strongPwdCheck($new)) {
                $arrMessage = responseMessage([$GLOBALS['WEAK_PASSWORD']]);
            } elseif (!matchValue($new, $confirmnew, true)) {
                //new and confirm password not match
                $arrMessage = responseMessage([$GLOBALS['PASSWORD_MISMATCH']]);
            } else {
                $sAction = null;
                $iRows = 0;
                $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
                $sQuery = "SELECT auth_pwd FROM $userAuthdetailsTable WHERE user_id = ? AND dstatus = 0 LIMIT 1";
                $arrParams = [$this->_iUserId];
                $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

                //user found
                if ($iRows === 1) {
                    $arrData = $this->_dbConn->GetData($sAction);

                    $iHashPwd = $arrData["auth_pwd"];

                    //check if current password is correct
                    if (matchValue(validPassword($current, $iHashPwd), true, true)) {
                        // check if new password is same as current or not
                        if (matchValue(validPassword($new, $iHashPwd), true, true)) {
                            $arrMessage = responseMessage([$GLOBALS['CHOOSE_DIFFERENT_PASSWORD']]);
                        } else {
                            $sauth_salt = pseudoRandomKey(32);

                            $usr_pwd = securePassword($new, $sauth_salt);

                            $last_update = currentDateTime();
                            $arrParams = [$usr_pwd, $last_update, $this->_iUserId];
                            $cols = "temp_pwd = '', temp_flag = 0, auth_pwd = ?, last_pwd_update = ?";

                            $iStatus = updateRecord($this->_dbConn, $userAuthdetailsTable, $cols, "dstatus = 0 AND user_id = ?", $arrParams);

                            if (matchValue($iStatus, 1, true)) {
                                $arrMessage = responseMessage([$GLOBALS['PASSWORD_CHANGED_SUCCESS']], 1);
                            } else {
                                $arrMessage = responseMessage([$GLOBALS['PASSWORD_CHANGED_FAILED']]);
                            }
                        }
                    } else {
                        $arrMessage = responseMessage([$GLOBALS['WRONG_CURRENT_PASSWORD']]);
                    }
                } else {
                    $arrMessage = responseMessage([$GLOBALS['UNAUTHORIZED_ACCESS']]);
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
