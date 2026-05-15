<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class Auth
{
    private $_dbConn = null;
    private $_session = null;
    private $_data = null;
    private $_captchaFolder = null;
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $session, $data)
    {
        $this->_data = $data;
        $this->_session = $session;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
        $this->_captchaFolder = $GLOBALS["CAPTCHA_IMG_PATH"];
    }

    private function checkLoginValidation($username, $password, $captcha)
    {
        $obj = new Validation();
        $obj->addValidation('name', $username, 'username', 1, $this->_validationLength['USERNAME_MINLENGTH'], $this->_validationLength['USERNAME_MAXLENGTH'], 'Username');
        $obj->addValidation('password', $password, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Password');
        if (constant("ENABLE_CAPTCHA_FOR_LOGIN")) {
            $obj->addValidation('captcha', $captcha, 'captcha', 1, $this->_validationLength['CAPTCHA_MINLENGTH'], $this->_validationLength['CAPTCHA_MAXLENGTH'], 'Captcha');
        }

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function checkForgotValidation($email)
    {
        $obj = new Validation();
        $obj->addValidation('email', $email, 'email', 1, $this->_validationLength['EMAIL_MINLENGTH'], $this->_validationLength['EMAIL_MAXLENGTH'], 'Email');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function checkLogoutValidation($token)
    {
        $obj = new Validation();
        $obj->addValidation('token', $token, 'token', 1, 64, 64, 'User');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function login()
    {
        $isCaptchaEnable = constant("ENABLE_CAPTCHA_FOR_LOGIN");
        $username = getFormData($this->_data['username']);
        $password = getFormData($this->_data['password']);
        $captcha = "";
        if ($isCaptchaEnable) {
            $captcha = getFormData($this->_data['captcha']);
        }

        $isValidated = $this->checkLoginValidation($username, $password, $captcha);

        //inputs validated
        if ($isValidated) {
            $sAction = null;
            $iRows = 0;
            $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
            $userSessionTokenTable = $this->_tables["USER_SESSION_TOKEN_TABLE"];
            $userGrouproleView = $this->_tables["USERGROUPROLE_VIEW"];
            $sQuery = "SELECT user_id, landing_modc, landing_pmodc, usr_fullname, usr_email, auth_pwd, temp_flag, last_pwd_update, login_attempts FROM $userAuthdetailsTable WHERE auth_name = ? AND dstatus = 0 LIMIT 1";
            $arrParams = [$username];

            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

            //User found
            if ($iRows === 1) {
                $arResult = $this->_dbConn->GetData($sAction);
                $iHashPwd = $arResult["auth_pwd"];
                $iLoginAttempts = $arResult["login_attempts"];

                //Maximum Attempts
                if (constant("MAX_LOGIN_ATTEMPTS_FUNC") && $iLoginAttempts >= constant("MAX_LOGIN_ATTEMPTS")) {
                    $arrMessage = responseMessage([$GLOBALS['ACCOUNT_LOCKED_MSG']]);
                } else {
                    //Check valid password and captcha
                    if (
                        validPassword($password, $iHashPwd) && (!$isCaptchaEnable ||
                            ($isCaptchaEnable && $this->_session->getSessionKey($GLOBALS["VARIABLES_NAMES"]["SESSION"]["CAPTCHA_VALUE"]) === $captcha))
                    ) {
                        $iUserId = $arResult["user_id"];
                        $iTempFlag = (int) $arResult["temp_flag"];
                        $slastupdate = $arResult["last_pwd_update"];
                        $currentDateTime = currentDateTime();

                        $days = dateDiffInDays($slastupdate, $currentDateTime);

                        //If password change functionality active and user did not change password after password expire
                        if (constant("PASSWORD_CHANGE_ALERT_FUNC") && $days > constant("PASSWORD_CHANGE_MAX_DAYS")) {
                            $arrMessage = responseMessage([$GLOBALS['PASSWORD_EXPIRE_MSG']]);
                        } else {
                            $iCSRF_Con_salt = pseudoRandomKey(18);
                            $iCSRF_Confirm_key = uniqueHashValue($iCSRF_Con_salt);

                            //set login attempts to 0
                            $arrParams = [
                                "iUserId" => $iUserId,
                            ];
                            updateRecord($this->_dbConn, $userAuthdetailsTable, "login_attempts = 0", "dstatus = 0 AND user_id = ?", $arrParams);

                            //Store user details to send
                            $arrUser = [];

                            //Check whether user belongs to any group or not
                            $sAction2 = null;
                            $iRows2 = 0;
                            $Module_Allowed = "SELECT group_id, role_permission AS mod_ids FROM $userGrouproleView WHERE dstatus = 0 AND user_id = ? LIMIT 1";
                            $arrParams = [$iUserId];
                            $this->_dbConn->ExecuteSelectQuery($Module_Allowed, $sAction2, $iRows2, $arrParams);

                            //user group found
                            if ($iRows2 === 1) {
                                $arrMod = $this->_dbConn->GetData($sAction2);
                                $sModules = $arrMod['mod_ids'];
                                $this->_session->setSessionKey($GLOBALS["VARIABLES_NAMES"]["SESSION"]["GROUP_ID_VALUE"], $arrMod['group_id']);

                                $arrModules = getAllowedModules($this->_dbConn, $sModules);

                                if (isNonEmptyArray($arrModules)) {
                                    // Delete captcha file
                                    if ($isCaptchaEnable) {
                                        $captchaFileName = $this->_session->getSessionId() . ".png";
                                        $captchaUploadPath = $this->_captchaFolder;
                                        $captchaFile = $captchaUploadPath . "/" . $captchaFileName;
                                        if (file_exists($captchaFile)) {
                                            unlink($captchaFile);
                                        }
                                    }

                                    //client logo
                                    $logo = $GLOBALS['LOGO_URL'] . "/" . constant("CUSTOMER_LOGO");

                                    //landing page
                                    $landing = [
                                        "landing_modc" => $arResult['landing_modc'],
                                        "landing_pmodc" => $arResult['landing_pmodc'],
                                    ];

                                    $alert_Password_Change = false;
                                    $iAlert_Password_Change = 0;
                                    $Diff = 0;

                                    // check if user is using temporary password or has crossed minimum number of days for password change
                                    if (constant("PASSWORD_CHANGE_ALERT_FUNC") && ((constant("TEMP_PASSWORD_ALERT_FUNC") && matchValue($iTempFlag, 1, true)) || $days > constant("PASSWORD_CHANGE_WARNING_DAYS"))) {
                                        $alert_Password_Change = true;
                                        if (constant("TEMP_PASSWORD_ALERT_FUNC") && matchValue($iTempFlag, 1, true)) {
                                            $iAlert_Password_Change = 1;
                                            if ($days > constant("PASSWORD_CHANGE_WARNING_DAYS")) {
                                                $iAlert_Password_Change = 2;
                                            }
                                        } else {
                                            $iAlert_Password_Change = 3;
                                        }
                                        $Diff = constant("PASSWORD_CHANGE_MAX_DAYS") - $days;
                                    }

                                    //update token and permission
                                    $arrAccessInfo = getAccessInfo($this->_dbConn, $iUserId);
                                    $sAccessInfo = json_encode($arrAccessInfo);
                                    $rsAct = null;
                                    $iRows4 = $iNum_rows = 0;
                                    $sQuery = "SELECT rec_id FROM $userSessionTokenTable WHERE user_id = '$iUserId' AND dstatus = 0 LIMIT 1";
                                    $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAct, $iRows4);

                                    //if multiple login allowed then insert new row else update existing one
                                    if (constant("ALLOW_MULTIPLE_LOGIN_PER_USERID_FUNC") || matchValue($iRows4, 0, true)) {
                                        $insert_query = "INSERT INTO $userSessionTokenTable (csrf_token, permitted_ids, user_id, rdt, modif_id) VALUES ('$iCSRF_Confirm_key', '$sAccessInfo', '$iUserId', '$currentDateTime', '$iUserId')";
                                        $this->_dbConn->ExecuteQuery($insert_query, $sAction, $iNum_rows);
                                    } else {
                                        $update_query = "UPDATE $userSessionTokenTable SET csrf_token = '$iCSRF_Confirm_key', permitted_ids = '$sAccessInfo', modif_id = '$iUserId' WHERE user_id = '$iUserId' AND dstatus = 0";
                                        $this->_dbConn->ExecuteQuery($update_query, $sAction, $iNum_rows);
                                    }

                                    $arrUser['user'] = [
                                        "name" => $arResult['usr_fullname'],
                                        "email" => $arResult['usr_email'],
                                    ];
                                    $arrUser['landing'] = [
                                        "modc" => $landing['landing_modc'],
                                        "pmodc" => $landing['landing_pmodc'],
                                    ];
                                    $arrUser['client'] = [
                                        "logo" => $logo,
                                    ];
                                    $arrUser['token'] = $iCSRF_Confirm_key;
                                    $arrUser['modules'] = $arrModules;

                                    // show password change alert
                                    if ($alert_Password_Change) {
                                        // user using temporary password
                                        if (matchValue($iAlert_Password_Change, 1, true)) {
                                            $arrMessage = responseMessage([$GLOBALS['LOGIN_SUCCESSFULL_MSG_TMP_PWD']], 2, $arrUser);
                                        } else {
                                            // user using temporary password but has reached the minimum number of days for warning
                                            if (matchValue($iAlert_Password_Change, 2, true)) {
                                                $error = $GLOBALS['LOGIN_SUCCESSFULL_MSG_NEAR_EXP_TEMP_PWD'];
                                            } else {
                                                $error = $GLOBALS['LOGIN_SUCCESSFULL_MSG_NEAR_EXP_PWD'];
                                            }
                                            // last day
                                            if ($Diff === 0) {
                                                $daysStr = "today";
                                            } else {
                                                $daysStr = "in $Diff days";
                                            }
                                            $arrMessage = responseMessage([$error . $daysStr], 2, $arrUser);
                                        }
                                    } else {
                                        $arrMessage = responseMessage([$GLOBALS['LOGIN_SUCCESSFULL_MSG']], 1, $arrUser);
                                    }
                                } else {
                                    $arrMessage = responseMessage([$GLOBALS['NO_MODULE_FOUND']]);
                                }
                            } else {
                                $arrMessage = responseMessage([$GLOBALS['NO_ROLE_ASSIGNED']]);
                            }
                        }
                    } else {
                        //max attempts functionality active
                        if (constant("MAX_LOGIN_ATTEMPTS_FUNC")) {
                            $iLoginAttempts++;

                            //user reached max attempts
                            if ($iLoginAttempts >= constant("MAX_LOGIN_ATTEMPTS")) {
                                $arrMessage = responseMessage([$GLOBALS['ACCOUNT_LOCKED_MSG']]);
                            } else {
                                //user not reached max attempts
                                $arrMessage = responseMessage(["Login Failed. " . (constant("MAX_LOGIN_ATTEMPTS") - $iLoginAttempts) . " more attempts remaining"]);
                            }

                            //update attempts tried
                            $update_query = "UPDATE $userAuthdetailsTable SET login_attempts = ? WHERE auth_name = ? AND dstatus = 0";
                            $arrParams = [
                                "iLoginAttempts" => $iLoginAttempts,
                                "username" => $username,
                            ];
                            $iNum_rows = 0;
                            $this->_dbConn->ExecuteQuery($update_query, $sAction, $iNum_rows, $arrParams);
                        } else {
                            if ($isCaptchaEnable && $this->_session->getSessionKey($GLOBALS["VARIABLES_NAMES"]["SESSION"]["CAPTCHA_VALUE"]) !== $captcha) {
                                $arrMessage = responseMessage([$GLOBALS['INVALID_CAPTCHA']]);
                            } else {
                                $arrMessage = responseMessage([$GLOBALS['WRONG_PASSWORD_ENTERED_MSG']]);
                            }
                        }
                    }
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['INVALID_USER']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function forgot()
    {
        $sEmail = getFormData($this->_data['email']);

        $isValidated = $this->checkForgotValidation($sEmail);

        //inputs validated
        if ($isValidated) {
            $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
            $sAction = null;
            $iRows = 0;
            $sQuery = "SELECT user_id FROM $userAuthdetailsTable WHERE usr_email = ? AND dstatus = '0' LIMIT 1";
            $arrParams = [$sEmail];

            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

            //User found
            if ($iRows === 1) {
                $arResult = $this->_dbConn->GetData($sAction);
                $iUserID = $arResult["user_id"];

                $currentDateTime = currentDateTime();

                //Generates Temporary Password
                $sauth_salt = pseudoRandomKey(32);

                $randomLength = rand($this->_validationLength['PASSWORD_MINLENGTH'] + 1, $this->_validationLength['PASSWORD_MAXLENGTH']);
                $temp_pwd = generatePassword($randomLength, 2, 2, 2);

                //pwd
                $usr_pwd = securePassword($temp_pwd, $sauth_salt);

                //update the the db with temporary password
                $iNum_rows = 0;
                $update_query = "UPDATE $userAuthdetailsTable SET temp_pwd = '$temp_pwd', auth_pwd = '$usr_pwd', last_pwd_update = '$currentDateTime', temp_flag = '1', login_attempts = 0 WHERE user_id = '$iUserID' AND dstatus = '0'";
                $this->_dbConn->ExecuteQuery($update_query, $sAction, $iNum_rows);

                $To = $sEmail;
                $sub = "Password reset instructions";
                $MSG = "
							<p>Dear User,</p>
							<p>Please use this temporary password to login: $temp_pwd</p>
							<p>
								<strong>Note: Please change your password after login for security reasons.</strong>
							</p>";
                $MSG = generateMailTemplate($sub, $MSG);
                if (sendMail($To, $sub, $MSG)) {
                    $arrMessage = responseMessage([$GLOBALS['PASSWORD_SENT_MSG']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['PASSWORD_NOT_SENT_MSG']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['USER_NOT_REGISTERED']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function logout()
    {
        $sToken = getFormData($this->_data['token']);

        $isValidated = $this->checkLogoutValidation($sToken);

        //inputs validated
        if ($isValidated) {
            $userSessionTokenTable = $this->_tables["USER_SESSION_TOKEN_TABLE"];
            $sAction = null;
            $iRows = 0;
            $sQuery = "SELECT rec_id, user_id FROM $userSessionTokenTable WHERE csrf_token = ? AND dstatus = 0 LIMIT 1";
            $arrParams = [$sToken];

            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

            //User found
            if ($iRows === 1) {
                $arResult = $this->_dbConn->GetData($sAction);
                $iTokenId = $arResult["rec_id"];
                $iUserId = $arResult["user_id"];

                //delete the user from db
                $arrParams = [
                    "iTokenId" => $iTokenId,
                ];
                $iStatus = deleteRecord($this->_dbConn, $userSessionTokenTable, "rec_id", $iUserId, "", $arrParams);

                // Regenerate session id
                // $this->_session->regenerateSession();

                if (matchValue($iStatus, 1, true)) {
                    $arrMessage = responseMessage([$GLOBALS['LOGOUT_SUCCESSFULL_MSG']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['LOGOUT_FAILED_MSG']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['UNAUTHORIZED_ACCESS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
