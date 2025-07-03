<?php

use SMSAlert\Lib\Smsalert\Smsalert;

// phpcs:ignore
class Otp extends Utilities
{
    private $isLoggingViaOtp;
    private $sendOtpLogFilename = "log_send_otp";
    private $sendOtpForLoginLogFilename = "log_send_otp_for_login";
    private $validateOtpLogFilename = "log_otp_validate";
    private $validateOtpForLoginLogFilename = "log_otp_validate_for_login";
    public $arrOTPStatus = array(
        "INVALID_DATA" => 0,
        "MISS_CALL_NOT_FOUND" => 1,
        "UNKNOWN" => 2,
        "DUPLICATE" => 3,
        "INVALID_CODE" => 4,
        "VALID" => 5,
        "OTP_EXPIRED" => 6,
    );

    public function __construct($dbConn, $tableUtil, $commonFunctions, $logFilename = "", $logFolderName = "/otp")
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $logFilename, $logFolderName);
    }

    // Set $sendingOrValidatingOtp = 1 to Send OTP
    // Set $sendingOrValidatingOtp = 2 to Validate OTP
    private function getUser($sendingOrValidatingOtp = 1)
    {
        global $TBL_CLOUD_AUTH_PIN;

        $mobile = isset($this->requestPostData["mobile"]) ? strtolower(trim($this->requestPostData["mobile"])) : "";
        $code = isset($this->requestPostData["code"]) ? strtolower(trim($this->requestPostData["code"])) : "";

        // OTP send/validate request for login
        $this->isLoggingViaOtp = isset($this->requestPostData['login']) && $this->requestPostData['login'] == 1 ? true : false;

        // Normal OTP send request
        $sToken = $this->getToken();

        if ($this->isLoggingViaOtp) {
            $this->setLogFileName($sendingOrValidatingOtp == 1 ? $this->sendOtpForLoginLogFilename : $this->validateOtpForLoginLogFilename);
        } else {
            $this->setLogFileName($sendingOrValidatingOtp == 1 ? $this->sendOtpLogFilename : $this->validateOtpLogFilename);
        }

        // token not set or not logging via OTP
        if (!($sToken || $this->isLoggingViaOtp)) {
            // Unauthorized access
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH04"]));
            $this->logOutput($response);
            return array(null, $mobile, $code);
        } elseif (($sendingOrValidatingOtp == 1 && !$mobile) || ($sendingOrValidatingOtp == 2 && !$code)) {
            // For sending OTP, mobile is mandatory
            // For validating OTP, OTP is mandatory

            // Fields cannot be blank
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH01"]));
            $this->logOutput($response);
            return array(null, $mobile, $code);
        } elseif (($sendingOrValidatingOtp == 1 && !is_numeric($mobile)) || ($sendingOrValidatingOtp == 2 && !preg_match("/^[A-Za-z0-9]+$/", $code))) {
            // For sending OTP, mobile is mandatory
            // For validating OTP, OTP is mandatory

            // Invalid data
            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP00"]));
            $this->logOutput($response);
            return array(null, $mobile, $code);
        } else {
            $rsAction = null;
            $iActionRows = 0;

            // Send/Validate OTP for login request
            if ($this->isLoggingViaOtp) {
                $sQuery_Org = "SELECT client_id, project_id, team_id, team_name, db_name, token FROM $TBL_CLOUD_AUTH_PIN" .
                    " WHERE mobile = '$mobile' AND dstatus = 0 LIMIT 1";

                $sQuery = "SELECT client_id, project_id, team_id, team_name, db_name, token FROM $TBL_CLOUD_AUTH_PIN" .
                    " WHERE mobile = ? AND dstatus = 0 LIMIT 1";
                $arrParams = array($mobile);
            } else {
                // Send/Validate OTP for normal request
                $sQuery_Org = "SELECT client_id, project_id, team_id, team_name, db_name, token FROM $TBL_CLOUD_AUTH_PIN" .
                    " WHERE token = '$sToken' AND dstatus = 0 LIMIT 1";

                $sQuery = "SELECT client_id, project_id, team_id, team_name, db_name, token FROM $TBL_CLOUD_AUTH_PIN" .
                    " WHERE token = ? AND dstatus = 0 LIMIT 1";
                $arrParams = array($sToken);
            }
            $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, $arrParams);

            // User found
            if ($iActionRows === 1) {
                $row = $this->dbConn->GetData($rsAction);
                return array($row, $mobile, $code);
            } else {
                // Unauthorized phone
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH06"]));
                $this->logOutput($response, "QUERY: $sQuery_Org");
                return array(null, $mobile, $code);
            }
        }
    }

    private function readSettingsAndSendOtp($row, $sMobile)
    {
        global $TBL_PROJECT_TEAM, $TBL_CLOUDRING_LIVE, $OTP_MODE;

        $clientId = $row['client_id'];
        $projectId = $row['project_id'];
        $teamId = $row['team_id'];
        $dbName = $row['db_name'];

        $teamName = $row['team_name'] ? $row['team_name'] : $this->tableUtil->getRowColumn(
            "$dbName.$TBL_PROJECT_TEAM",
            "team_name",
            "team_id = $teamId"
        );

        // comes in delhi APK
        $iQty = isset($this->requestPostData["qty"]) && $this->requestPostData["qty"] ? intval($this->requestPostData["qty"]) : 0;
        // comes in new flutter APK
        $formId = isset($this->requestPostData["formId"]) && $this->requestPostData["formId"] ?
            intval($this->requestPostData["formId"]) : 0;

        // Read project config
        $arrProjectConfig = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]) ?
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId] : array();
        $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
            $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;
        $missCallMessage = isset($arrProjectConfig["missCallMessage"]) ?
            $arrProjectConfig["missCallMessage"] : "";
        $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
            $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
        $arrDummyNumberList = isset($arrProjectConfig["arrDummyNumberList"]) &&
            $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyNumberList"]) ?
            $arrProjectConfig["arrDummyNumberList"] : $GLOBALS["arrDefaultDummyNumberList"];
        $smsProvider = isset($arrProjectConfig["smsProvider"]) ?
            $arrProjectConfig["smsProvider"] : null;
        $smsSenderId = isset($arrProjectConfig["smsSenderId"]) ?
            $arrProjectConfig["smsSenderId"] : "";
        $smsTemplateId = isset($arrProjectConfig["smsTemplateId"]) ?
            $arrProjectConfig["smsTemplateId"] : "";
        $otpType = isset($arrProjectConfig["otpType"]) ?
            $arrProjectConfig["otpType"] : 1;
        $otpLength = isset($arrProjectConfig["otpLength"]) ?
            $arrProjectConfig["otpLength"] : 4;
        $regenerateOtpIfExistToday = isset(
            $arrProjectConfig["regenerateOtpIfExistToday"]
        ) ?
            $arrProjectConfig["regenerateOtpIfExistToday"] : false;
        $regenerateOtpCond = isset($arrProjectConfig["regenerateOtpCond"]) ?
            $arrProjectConfig["regenerateOtpCond"] : "";
        // if $otpType != 1, numericOtp = 1 means numeric otp, else alplanumeric OTP
        $numericOtp = isset($arrProjectConfig["numericOtp"]) ?
            $arrProjectConfig["numericOtp"] : 1;
        // Don't send OTP after this time
        $otpEndTime = isset($arrProjectConfig["otpEndTime"]) ?
            $arrProjectConfig["otpEndTime"] : null;
        // otpMode means how to send otp, via SMS or whatsapp or both. Default is SMS
        $otpMode = isset($arrProjectConfig["otpMode"]) ?
            $arrProjectConfig["otpMode"] : $OTP_MODE["SMS_ONLY"];
        $iTimeOverToSendOtp = false;
        if ($otpEndTime) {
            $currentTimestamp = strtotime($this->commonFunctions->currentDateTime());
            $endTimestamp = strtotime(date("Y-m-d H:i:s", strtotime($otpEndTime)));
            $iTimeOverToSendOtp = $currentTimestamp > $endTimestamp;
        }

        // Agreement/Declaration upload config
        if ($formId == 9999) {
            $arrProjectConfig = isset($this->arrDBDeclarationDetails[$dbName][$clientId][$projectId]) ?
                $this->arrDBDeclarationDetails[$dbName][$clientId][$projectId] : $this->arrDBDeclarationDetails;
            $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
                $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;
            $missCallMessage = isset($arrProjectConfig["missCallMessage"]) ?
                $arrProjectConfig["missCallMessage"] : "";
            $smsProvider = isset($arrProjectConfig["smsProvider"]) ?
                $arrProjectConfig["smsProvider"] : null;
            $smsSenderId = isset($arrProjectConfig["smsSenderId"]) ?
                $arrProjectConfig["smsSenderId"] : "";
            $otpLength = isset($arrProjectConfig["otpLength"]) ?
                $arrProjectConfig["otpLength"] : 4;
            $otpMode = isset($arrProjectConfig["otpMode"]) ?
                $arrProjectConfig["otpMode"] : $OTP_MODE["SMS_ONLY"];
        } elseif ($this->isLoggingViaOtp) {
            // Login via OTP

            $arrProjectConfig = isset($this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId]) ?
                $this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId] : $this->arrDBLoginViaOtpDetails;
            $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
                $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;
            $missCallMessage = isset($arrProjectConfig["missCallMessage"]) ?
                $arrProjectConfig["missCallMessage"] : "";
            $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
                $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
            $smsProvider = isset($arrProjectConfig["smsProvider"]) ?
                $arrProjectConfig["smsProvider"] : null;
            $smsSenderId = isset($arrProjectConfig["smsSenderId"]) ?
                $arrProjectConfig["smsSenderId"] : "";
            $otpLength = isset($arrProjectConfig["otpLength"]) ?
                $arrProjectConfig["otpLength"] : 4;
            $smsTemplateId = isset($arrProjectConfig["smsTemplateId"]) ?
                $arrProjectConfig["smsTemplateId"] : "";
            $regenerateOtpIfExistToday = isset(
                $arrProjectConfig["regenerateOtpIfExistToday"]
            ) ?
                $arrProjectConfig["regenerateOtpIfExistToday"] : false;
            $otpMode = isset($arrProjectConfig["otpMode"]) ?
                $arrProjectConfig["otpMode"] : $OTP_MODE["SMS_ONLY"];
        }

        $sExtraLogData = "DB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId MissCallTable: $missCallTable Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";

        // Prevent SMS to send if dummy number i.e Allow click Next button without sending OTP
        if ($preventDummyNumberToSendOTP && $sMobile && in_array($sMobile, $arrDummyNumberList)) {
            // Click Next
            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP14"]), 1);
            $this->logOutput($response, $sExtraLogData);
        } elseif ($otpEndTime && $iTimeOverToSendOtp) {
            // Time over. OTP not sent
            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP15"]), 1);
            $this->logOutput($response, $sExtraLogData);
        } else {
            // Message must be present
            if (
                ((!$otpMode || $otpMode == $OTP_MODE["SMS_ONLY"]) && $missCallMessage) ||
                ($otpMode == $OTP_MODE["WHATSAPP_ONLY"] || $otpMode == $OTP_MODE["SMS_AND_WHATSAPP_BOTH"])
            ) {
                // Unique and numeric OTP i.e unique OTP will be generated every time even for a given mobile number
                if ($otpType == 1) {
                    $getUniqueCode = $this->generateUniqueNumericCode(
                        $otpLength,
                        $regenerateOtpIfExistToday,
                        $dbName,
                        $missCallTable,
                        $regenerateOtpCond
                    );
                } else {
                    // Same alpha numeric or numeric OTP i.e same OTP will be generated every time for a given mobile number
                    $getUniqueCode = $this->generateUniqueCode($sMobile, $numericOtp, $otpLength);
                }

                $message = $missCallMessage ? str_replace("{OTP}", $getUniqueCode, $missCallMessage) : "";
                $message = $message ? str_replace("{MOBILE}", $sMobile, $message) : "";

                // QTY, DATE, TEAM_FIRST_NAME are used in Delhi APK
                $message = $message ? str_replace("{QTY}", $iQty, $message) : "";
                $message = $message ? str_replace("{DATE}", date("d/m"), $message) : "";
                $message = $message ? str_replace("{TEAM_FIRST_NAME}", explode(" ", $teamName)[0], $message) : "";

                list($isOtpSent, $sOutput, $useAsSMSProvider, $iOtpMode, $arrWhatsappOutput) = $this->sendOTP(
                    $sMobile,
                    $message,
                    $smsProvider,
                    $smsSenderId,
                    $smsTemplateId,
                    $otpMode,
                    $getUniqueCode
                );

                $sExtraLogData .= "\r\nOTP Mode: $iOtpMode";

                // API has given some result
                if ($sOutput || $arrWhatsappOutput) {
                    $sExtraLogData .= "\r\nMISS CALL API OUTPUT: $sOutput";
                    $sExtraLogData .= "\r\nWHATSAPP API OUTPUT: " . json_encode($arrWhatsappOutput);

                    // Otp sent
                    if ($isOtpSent) {
                        $sExtraLogData .= "\r\nOTP: $getUniqueCode Message: $message";

                        // Get DB connection as per server
                        list($connToUse, $tableUtilToUse) = $this->getDbConnectionAsPerServer($dbName);

                        $iNum_Action = null;
                        $iNum_rows = 0;
                        $sIN_Query_Org = "INSERT INTO $dbName.$missCallTable (process, token, rec_who" .
                            ", api_output, rcd, rdt) VALUES ('0', '$getUniqueCode', '$sMobile', '$sOutput'" .
                            ", '{$this->currentDate}', '{$this->currentDateTime}')";
                        $sIN_Query = "INSERT INTO $dbName.$missCallTable (process, token, rec_who" .
                            ", api_output, rcd, rdt) VALUES ('0', '$getUniqueCode', ?, ?, '{$this->currentDate}'" .
                            ", '{$this->currentDateTime}')";
                        $arrParams = array("sMobile" => $sMobile, "output" => $sOutput);
                        $connToUse->ExecuteQuery($sIN_Query, $iNum_Action, $iNum_rows, $arrParams);

                        $sExtraLogData .= "\r\nQUERY: $sIN_Query_Org";
                        if ($iNum_rows > 0) {
                            // OTP sent
                            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP08"]), 1);
                            $this->logOutput($response, $sExtraLogData);
                        } else {
                            // OTP sent but failed to store in DB
                            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP07"]));
                            $this->logOutput($response, $sExtraLogData);
                        }
                    } else {
                        // OTP not sent. Please try after some time
                        $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP06"]));
                        $this->logOutput($response, $sExtraLogData);
                    }
                } else {
                    // OTP not sent. Server down
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP05"]));
                    $this->logOutput($response, $sExtraLogData);
                }
            } else {
                // OTP not sent. Message cannot be empty
                $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP12"]));
                $this->logOutput($response, $sExtraLogData);
            }
        }
    }

    private function readSettingsAndValidateOtp($row, $sMobile, $sCode)
    {
        $clientId = $row['client_id'];
        $projectId = $row['project_id'];
        $teamId = $row['team_id'];
        $dbName = $row['db_name'];
        $sToken = isset($row['token']) ? $row['token'] : null;

        // comes in new flutter APK
        $formId = isset($this->requestPostData["formId"]) && $this->requestPostData["formId"] ?
            intval($this->requestPostData["formId"]) : 0;

        // Read project config
        $arrProjectConfig = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]) ?
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId] : array();
        $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
            $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
        $arrDummyNumberList = isset($arrProjectConfig["arrDummyNumberList"]) &&
            $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyNumberList"]) ?
            $arrProjectConfig["arrDummyNumberList"] : $GLOBALS["arrDefaultDummyNumberList"];
        $allowDummyOtpToValidate = isset($arrProjectConfig["allowDummyOtpToValidate"]) ?
            $arrProjectConfig["allowDummyOtpToValidate"] : false;
        $arrDummyOtpList = isset($arrProjectConfig["arrDummyOtpList"]) &&
            $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyOtpList"]) ?
            $arrProjectConfig["arrDummyOtpList"] : $GLOBALS["arrDefaultDummyOtpList"];

        $arrOtpValidateConfig = isset($arrProjectConfig["otpValidateConfig"]) ?
            $arrProjectConfig["otpValidateConfig"] : array();

        // Login via OTP
        if ($this->isLoggingViaOtp) {
            $arrProjectConfig = isset($this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId]) ?
                $this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId] : $this->arrDBLoginViaOtpDetails;

            $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
                $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
            $arrDummyNumberList = isset($arrProjectConfig["arrDummyNumberList"]) &&
                $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyNumberList"]) ?
                $arrProjectConfig["arrDummyNumberList"] : $GLOBALS["arrDefaultDummyNumberList"];
        }

        $sExtraLogData = "DB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId";

        // Return success for dummy number if not using for login via OTP
        if ($preventDummyNumberToSendOTP && $sMobile && in_array($sMobile, $arrDummyNumberList) && !$this->isLoggingViaOtp) {
            $response = $this->response->sendResponse(array("message" => ""), 1);
            $this->logOutput($response, $sExtraLogData);
        } elseif ($allowDummyOtpToValidate && $sCode && in_array($sCode, $arrDummyOtpList)) {
            // Return success for dummy otp
            $response = $this->response->sendResponse(array("message" => ""), 1);
            $this->logOutput($response, $sExtraLogData);
        } else {
            // Validate and update OTP process and get status
            $otpStatus = $this->validateOTP(
                $dbName,
                $clientId,
                $projectId,
                $teamId,
                $sMobile,
                $sCode,
                $formId,
                $sExtraLogData
            );

            $status = 0;
            switch ($otpStatus) {
                    // phpcs:ignore
                case 0:
                    // Invalid data
                    $statusMsg = $this->arrOTPMessages["OTP00"];
                    break;
                    // phpcs:ignore
                case 1:
                    // Miss call not found
                    $statusMsg = isset($arrOtpValidateConfig["noMissCallFoundErrorMsgKey"]) &&
                        $this->arrOTPMessages[$arrOtpValidateConfig["noMissCallFoundErrorMsgKey"]] ?
                        $this->arrOTPMessages[$arrOtpValidateConfig["noMissCallFoundErrorMsgKey"]] :
                        $this->arrOTPMessages["OTP01"];
                    break;
                    // phpcs:ignore
                case 2:
                    // OTP not verified due to unknown reason
                    $statusMsg = $this->arrOTPMessages["OTP17"];
                    break;
                    // phpcs:ignore
                case 3:
                    // Duplicate record
                    $statusMsg = $this->arrOTPMessages["OTP03"];
                    break;
                    // phpcs:ignore
                case 4:
                    // Invalid code (i.e Call found but code not match)
                    $statusMsg = $this->arrOTPMessages["OTP04"];
                    break;
                    // phpcs:ignore
                case 5:
                    // OTP validated
                    $status = 1;
                    $statusMsg = $this->arrOTPMessages["OTP16"];

                    // Login via OTP
                    if ($this->isLoggingViaOtp) {
                        $login = new AppLogin($this->dbConn, $this->tableUtil, $this->commonFunctions);
                        $login->loginViaOtp($sToken, $sExtraLogData);
                        $this->dbConn->Close();
                        exit;
                    }
                    break;
                    // phpcs:ignore
                case 6:
                    // OTP expired
                    $statusMsg = $this->arrOTPMessages["OTP02"];
                    break;
                default:
                    // Unknown issue
                    $statusMsg = $this->arrOTPMessages["OTP13"];
                    break;
            }

            $response = $this->response->sendResponse(array("message" => $statusMsg), $status);
            $this->logOutput($response, $sExtraLogData);
        }
    }

    // do extra task after record is uploaded
    private function doExtraTaskPostOtpUpdate(
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $arrProjectConfig,
        $sMobile,
        &$sExtraLogData = ""
    ) {
        $currentDate = $this->commonFunctions->currentDate();
        $smsProvider = isset($arrProjectConfig["smsProvider"]) ? $arrProjectConfig["smsProvider"] : null;
        $smsSenderId = isset($arrProjectConfig["smsSenderId"]) ? $arrProjectConfig["smsSenderId"] : "";
        $smsTemplateId = isset($arrProjectConfig["smsTemplateId"]) ? $arrProjectConfig["smsTemplateId"] : "";

        // Impact
        if ($dbName == $GLOBALS["IMPACT_DB"]) {
            if ($clientId == 13) {
                // D2D WD-40, WD 40 Phase 2
                if ($projectId == 28 || $projectId == 30) {
                    $couponTable = "tblcloudring_live_pidiliteAmazon";

                    // Send amazon coupon
                    $arrCouponDetails = $this->tableUtil->getRowColumns(
                        "$dbName.$couponTable",
                        "rec_id, token",
                        "dstatus = 0 AND process = '0'"
                    );

                    if (isset($arrCouponDetails) && $this->commonFunctions->isNonEmptyArray($arrCouponDetails)) {
                        $couponRecId = $arrCouponDetails[0];
                        $couponCode = $arrCouponDetails[1];

                        $message = "Your Amazon discount voucher code for WD40 is $couponCode." .
                            " \n\nClick to buy: https://tinyurl.com/y84rdzek\n\n- Appilary";
                        list($isOtpSent, $sOutput) = $this->sendOTP(
                            $sMobile,
                            $message,
                            $smsProvider,
                            $smsSenderId,
                            $smsTemplateId
                        );

                        $sExtraLogData .= "\r\nDB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId MissCallTable: $couponTable Mobile: $sMobile Coupon Code: $couponCode smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";
                        $sExtraLogData .= "\r\nMessage: $message\r\nMISS CALL API OUTPUT: $sOutput";

                        if ($sOutput && $isOtpSent) {
                            $this->tableUtil->updateRecord(
                                "$dbName.$couponTable",
                                "process = '1', pid = $projectId, rec_who = ?, capture_date = '$currentDate'",
                                "rec_id = $couponRecId",
                                array($sMobile)
                            );
                        }
                    }
                }
            }
        } elseif ($dbName == $GLOBALS["NOVICEMARCOM_DB"]) {
            // novicemarcom
            if ($clientId == 14) {
                if ($projectId == 17) {
                    $message = "Thank you for registering with Dr Fixit. Explore more on the Dr Fixit App:" .
                        " https://play.google.com/store/apps/details?id=pidilite.com.fixit\n-Appilary";
                    list($isOtpSent, $sOutput) = $this->sendOTP(
                        $sMobile,
                        $message,
                        $smsProvider,
                        $smsSenderId,
                        $smsTemplateId
                    );

                    $sExtraLogData .= "\r\nDB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";
                    $sExtraLogData .= "\r\nMessage: $message\r\nMISS CALL API OUTPUT: $sOutput";
                }
            }
        }
    }

    private function getDbConnectionAsPerServer($dbName)
    {
        global $BTLMO74_DB_NAME, $BTLMO74_DB_USERNAME, $BTLMO74_DB_PASSWORD;

        $newConn = null;
        $connToUse = $this->dbConn;
        $tableUtilToUse = $this->tableUtil;

        // Create connection to btlmo74
        if (in_array($dbName, $GLOBALS["ARR_BTLMO74_DBS"])) {
            $newConn = new DBConnection(
                $BTLMO74_DB_NAME,
                $BTLMO74_DB_USERNAME,
                $BTLMO74_DB_PASSWORD,
                $this->commonFunctions,
                false,
                constant("BTLMO74_DB_HOSTNAME")
            );
            $connToUse = $newConn;
            $tableUtilToUse = new TableUtil($connToUse, $this->commonFunctions);
        }

        return array($connToUse, $tableUtilToUse);
    }

    // generate unique OTP for different mobile (i.e same OTP will be generated everytime for a same mobile number)
    // $type = 0 -> Alphanumeric OTP
    // $type = 1 -> numeric OTP
    // $length = 5 -> 5 digit or 6 digit
    final public function generateUniqueCode($sMobile, $otpType = 0, $length = 5)
    {
        // numeric OTP
        if ($otpType === 1) {
            $ARRSubstitution = array(
                '0' => '8', '1' => '4', '2' => '1', '3' => '0', '4' => '7',
                '5' => '2', '6' => '5', '7' => '9', '8' => '6', '9' => '3'
            );    //8410725963
        } else {
            // Alphanumeric OTP
            $ARRSubstitution = array(
                '0' => 'k', '1' => 'y', '2' => 'x', '3' => 't', '4' => 'p',
                '5' => 'm', '6' => 'n', '7' => 'b', '8' => 'd', '9' => 'h'
            );
        }

        $arrInput = str_split($sMobile);
        $array_count = count($arrInput);

        if ($array_count != 10) {
            //invalid mobile number
            return "";
        } else {
            $data_1 = $arrInput[0];
            $data_2 = $arrInput[1];
            $data_3 = $arrInput[2];
            $data_4 = $arrInput[3];
            $data_5 = $arrInput[4];
            $data_6 = $arrInput[5];
            $data_7 = $arrInput[6];
            $data_8 = $arrInput[7];
            $data_9 = $arrInput[8];
            $data_10 = $arrInput[9];

            if ($data_5 == "0" || $data_5 == "1") {
                $data_5 = "5";
            }
            if ($data_8 == "0" || $data_8 == "1") {
                $data_8 = "8";
            }
            if ($data_3 == "0" || $data_3 == "1") {
                $data_3 = "3";
            }
            if ($data_7 == "0" || $data_7 == "1") {
                $data_7 = "7";
            }
            if ($data_4 == "0" || $data_4 == "1") {
                $data_4 = "4";
            }
            if ($data_6 == "0" || $data_6 == "1") {
                $data_6 = "6";
            }

            if ($data_1 == 9) {
                if ($length == 3) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_5 . $ARRSubstitution[$data_2];
                } elseif ($length == 5) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_5 . $ARRSubstitution[$data_9] .
                        $data_8 . $ARRSubstitution[$data_2];
                } else {
                    $unique_code = $ARRSubstitution[$data_10] . $data_5 . $ARRSubstitution[$data_9] .
                        $data_8 . $ARRSubstitution[$data_2] . $data_3;
                }
            } elseif ($data_1 == 8) {
                if ($length == 3) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_3 . $ARRSubstitution[$data_1];
                } elseif ($length == 5) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_3 . $ARRSubstitution[$data_1] .
                        $data_7 . $ARRSubstitution[$data_2];
                } else {
                    $unique_code = $ARRSubstitution[$data_10] . $data_3 . $ARRSubstitution[$data_1] .
                        $data_7 . $ARRSubstitution[$data_2] . $data_6;
                }
            } else {
                if ($length == 3) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_4 . $ARRSubstitution[$data_9];
                } elseif ($length == 5) {
                    $unique_code = $ARRSubstitution[$data_10] . $data_4 . $ARRSubstitution[$data_9] .
                        $data_6 . $ARRSubstitution[$data_2];
                } else {
                    $unique_code = $ARRSubstitution[$data_10] . $data_4 . $ARRSubstitution[$data_9] .
                        $data_6 . $ARRSubstitution[$data_2] . $data_7;
                }
            }
            return $unique_code;
        }
    }

    // generate unique OTP everytime (i.e different OTP even for a same mobile number)
    final public function generateUniqueNumericCode(
        $length = 5,
        $regenerateOtpIfExistToday = false,
        $dbName = null,
        $otpTable = "",
        $cond = "",
        $date = null
    ) {
        $code = null;
        switch ($length) {
            case 3:
                $code = rand(100, 999);
                break;
            case 4:
                $code = rand(1000, 9999);
                break;
            case 6:
                $code = rand(100000, 999999);
                break;
            default:
                $code = rand(10000, 99999);
                break;
        }

        // Check if this OTP exist in OTP table for today's or given date
        if ($regenerateOtpIfExistToday && $otpTable) {
            $rcd = $date ? $date : $this->commonFunctions->currentDate();

            // Get DB connection as per server
            list($connToUse, $tableUtilToUse) = $this->getDbConnectionAsPerServer($dbName);

            $isExist = $tableUtilToUse->isRecordExist(
                "$dbName.$otpTable",
                "rec_id",
                "dstatus = 0 AND token = '$code' AND rcd = '$rcd' $cond"
            );

            // Exist so generate new OTP
            if ($isExist > 0) {
                $code = $this->generateUniqueNumericCode($length, $regenerateOtpIfExistToday, $dbName, $otpTable, $cond, $date);
            }
        }

        return $code;
    }

    // Send OTP
    final public function sendOTP($sMobile, $message, $provider = null, $senderid = null, $templateId = null, $otpMode = null, $otp = null)
    {
        global $SMS_ALERT_LIB_PATH, $OTP_MODE;

        $smsOutput = $arrWhatsappOutput = null;
        $entityId = constant("OUR_DLT_ENTITY_ID");

        // Defines how to send OTP, via SMS or whatsapp or both
        $otpMode = $otpMode ? $otpMode : $OTP_MODE["SMS_ONLY"];

        // Send OTP via whatsapp
        if ($otpMode == $OTP_MODE["WHATSAPP_ONLY"] || $otpMode == $OTP_MODE["SMS_AND_WHATSAPP_BOTH"]) {
            $phoneNumber = $sMobile;

            $postFields = [
                'userid'        => 'Appilary',
                'password'      => 'Uyf6wtH0',
                'wabaNumber'    => '919289854142',
                'output'        => 'json',
                'mobile'        => '91' . $phoneNumber,
                'sendMethod'    => 'quick',
                'msgType'       => 'Text',
                'templateName'  => 'authenticationmarch',
                'msg'           => "$otp is your verification code. For your security, do not share this code."
            ];

            $arrWhatsappOutput = $this->commonFunctions->sendWhatsappMessage($postFields);

            if ($otpMode == $OTP_MODE["WHATSAPP_ONLY"]) {
                $isOtpSent = $arrWhatsappOutput ? true : false;
                return array($isOtpSent, null, null, $otpMode, $arrWhatsappOutput);
            }
        }

        // SMS provider vendor
        // textlocal=1, webtechsolution = 2, smsgatewayhub = 3, 4 = smsalert, 5 = onextel
        $useAsSMSProvider = isset($provider) && $provider ? $provider : 4;
        // sender id
        $customSenderId = $useAsSMSProvider === 4 ?
            (isset($senderid) && $senderid ? $senderid : "OTPtxt") : (isset($senderid) && $senderid ? $senderid : "APPOTP");

        // textlocal
        if ($useAsSMSProvider == 1) {
            $apiKey = "t6YFuIYpQy4-qwCgXGSf6bxShowfmWRoqKnXFX4DSx";
            $sApiKey = urlencode($apiKey);
            $numbers = array(intval($sMobile));
            $sender = urlencode('APPLRY');
            $message = rawurlencode($message);

            $numbers = implode(',', $numbers);
            $data = array("apikey" => $sApiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

            // Send the GET request with cURL
            $ch = curl_init('https://api.textlocal.in/send/');

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //get response
            $smsOutput = curl_exec($ch);
            // close cURL resource, and free up system resources
            curl_close($ch);
        } elseif ($useAsSMSProvider == 2) {
            // webtechsolution
            $username = "Avnish001";
            $hash = "RUPIQGEF";
            $numbers = array(intval($sMobile));
            $sender = urlencode('ITCSMS');
            $fl = 0;
            $gwid = 2;

            $numbers = implode(',', $numbers);
            $data = array(
                "user" => $username, "password" => $hash, 'msisdn' => $numbers,
                "sid" => $sender, "msg" => $message, "fl" => $fl, "gwid" => $gwid
            );

            // Send the GET request with cURL
            $ch = curl_init('http://sms.webtechsolution.co/vendorsms/pushsms.aspx');

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //get response
            $smsOutput = curl_exec($ch);
            // close cURL resource, and free up system resources
            curl_close($ch);
        } elseif ($useAsSMSProvider == 3) {
            // smsgatewayhub
            $APIKey = "XPp9JY6SV06cDQz6lqoHXw";
            // $user = "meavnish";
            $password = "732387";
            $senderid = $customSenderId;
            $numbers = array(intval($sMobile));
            $channel = 2;
            $DCS = 0;
            $flashsms = 0;
            $text = $message;
            $route = 11;

            $number = implode(',', $numbers);
            $data = array(
                "APIKey" => $APIKey, "senderid" => $senderid, "channel" => $channel,
                "DCS" => $DCS, "flashsms" => $flashsms, 'number' => $number, "route" => $route, "text" => $text
            );

            // Send the GET request with cURL
            $url = "https://www.smsgatewayhub.com/api/mt/SendSMS?" . http_build_query($data);
            $ch = curl_init($url);

            // curl_setopt($ch, CURLOPT_POST, true);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //get response
            $smsOutput = curl_exec($ch);
            // close cURL resource, and free up system resources
            curl_close($ch);
        } elseif ($useAsSMSProvider == 4) {
            // smsalert
            include_once $SMS_ALERT_LIB_PATH;

            $username = 'appilarytech';
            $password = 'Avnishk@1';
            $prefix = "91";

            $smsalert = (new Smsalert())
                ->authWithUserIdPwd($username, $password)
                ->setForcePrefix($prefix)
                ->setSender($customSenderId);

            $output = $smsalert->send($sMobile, $message);
            $smsOutput = is_array($output) ? json_encode($output) : $output;
        } elseif ($useAsSMSProvider == 5) {
            // onextel
            $APIKey = "I8G9dZIw";
            $senderid = $customSenderId;
            $text = $message;

            $data = array(
                "key" => $APIKey, "to" => $sMobile, "from" => $senderid,
                "body" => $text
            );

            if ($templateId) {
                $data["entityid"] = $entityId;
                $data["templateid"] = $templateId;
            }

            // Send the GET request with cURL
            $url = "https://api.onex-aura.com/api/sms?" . http_build_query($data);
            $ch = curl_init($url);

            // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //get response
            $smsOutput = curl_exec($ch);

            // close cURL resource, and free up system resources
            curl_close($ch);
        } elseif ($useAsSMSProvider == 6) {
            // https://www.karix.solutions
            $username = "Appilary_Tech";
            $password = "Welcome@1234";

            $hash = "xOZwr5NvnIpnDs4LxgpDrw==";

            $data = array(
                "ver" => "1.0",
                "key" => $hash,
                // sch_at is this is used to schedule msg. Note: Minimum Schedule time is CurrentTime + 15 Minutes
                // "sch_at" => date("Y-m-d H:i:s", strtotime("+15 minutes")),
                "messages" => array(
                    array(
                        "dest" => array($sMobile),
                        "text" => $message,
                        "send" => $senderid,
                        "type" => "PM",
                        "dlt_entity_id" => $entityId,
                        "dlt_template_id" => $templateId,
                    )
                ),
            );

            $arrHeaders = array(
                'Content-Type: application/json',
            );

            // Send the GET request with cURL
            $ch = curl_init('https://japi.instaalerts.zone/httpapi/JsonReceiver');

            curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //get response
            $smsOutput = curl_exec($ch);
            // close cURL resource, and free up system resources
            curl_close($ch);
        }

        $outputArr = $smsOutput ? json_decode($smsOutput, true) : array();
        $isOtpSent = false;

        if (
            (($otpMode == $OTP_MODE["SMS_ONLY"] || $otpMode == $OTP_MODE["SMS_AND_WHATSAPP_BOTH"]) &&
                (($useAsSMSProvider == 1 && $outputArr && isset($outputArr["status"]) && strtolower($outputArr["status"]) == 'success') ||
                    ($useAsSMSProvider == 2 && $outputArr && strtolower(explode("-", $outputArr)[0]) == "success") ||
                    ($useAsSMSProvider == 3 && $outputArr && isset($outputArr["ErrorCode"]) && $outputArr["ErrorCode"] == '000') ||
                    ($useAsSMSProvider == 4 && $outputArr && isset($outputArr["status"]) && strtolower($outputArr["status"]) === 'success') ||
                    ($useAsSMSProvider == 5 && $outputArr && isset($outputArr["status"]) && $outputArr["status"] == 100) ||
                    ($useAsSMSProvider == 6 && $outputArr && isset($outputArr["status"]["code"]) && $outputArr["status"]["code"] == 200)) ||
                (($otpMode == $OTP_MODE["WHATSAPP_ONLY"] || $otpMode == $OTP_MODE["SMS_AND_WHATSAPP_BOTH"]) && $arrWhatsappOutput))
        ) {
            $isOtpSent = true;
        }

        return array($isOtpSent, $smsOutput, $useAsSMSProvider, $otpMode, $arrWhatsappOutput);
    }

    // Set OTP config (normal call, login request, Agreement/Declaration upload) and returns otp status
    final public function validateOTP(
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $mobileNo,
        $otpCode,
        $formId = null,
        &$sExtraLogData = ""
    ) {
        global $TBL_CLOUDRING_LIVE;

        // Read project config
        $arrProjectConfig = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]) ?
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId] : array();

        $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
            $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;
        $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
            $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
        $arrDummyNumberList = isset($arrProjectConfig["arrDummyNumberList"]) &&
            $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyNumberList"]) ?
            $arrProjectConfig["arrDummyNumberList"] : $GLOBALS["arrDefaultDummyNumberList"];
        $arrOtpValidateConfig = isset($arrProjectConfig["otpValidateConfig"]) ?
            $arrProjectConfig["otpValidateConfig"] : array();
        $doExtraTaskPostValidOtp = isset($arrProjectConfig["doExtraTaskPostValidOtp"]) ?
            $arrProjectConfig["doExtraTaskPostValidOtp"] : false;

        // Agreement/Declaration upload config
        if ($formId == 9999) {
            $arrProjectConfig = isset($this->arrDBDeclarationDetails[$dbName][$clientId][$projectId]) ?
                $this->arrDBDeclarationDetails[$dbName][$clientId][$projectId] : $this->arrDBDeclarationDetails;
            $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
                $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;

            $arrOtpValidateConfig = isset($arrProjectConfig["otpValidateConfig"]) ?
                $arrProjectConfig["otpValidateConfig"] : array();
        } elseif ($this->isLoggingViaOtp) {
            // Login via OTP

            $arrProjectConfig = isset($this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId]) ?
                $this->arrDBLoginViaOtpDetails[$dbName][$clientId][$projectId] : $this->arrDBLoginViaOtpDetails;
            $missCallTable = isset($arrProjectConfig["missCallTable"]) ?
                $arrProjectConfig["missCallTable"] : $TBL_CLOUDRING_LIVE;
            $preventDummyNumberToSendOTP = isset($arrProjectConfig["preventDummyNumberToSendOTP"]) ?
                $arrProjectConfig["preventDummyNumberToSendOTP"] : false;
            $arrDummyNumberList = isset($arrProjectConfig["arrDummyNumberList"]) &&
                $this->commonFunctions->isNonEmptyArray($arrProjectConfig["arrDummyNumberList"]) ?
                $arrProjectConfig["arrDummyNumberList"] : $GLOBALS["arrDefaultDummyNumberList"];
            $arrOtpValidateConfig = isset($arrProjectConfig["otpValidateConfig"]) ?
                $arrProjectConfig["otpValidateConfig"] : array();
        }

        // Get status
        list($dupStatus, $updateRecWho) = $this->getCallStatus(
            $dbName,
            false,
            $arrOtpValidateConfig,
            "$dbName.$missCallTable",
            $otpCode,
            $mobileNo,
            "",
            array(),
            $preventDummyNumberToSendOTP && $mobileNo && in_array($mobileNo, $arrDummyNumberList) && $this->isLoggingViaOtp
        );

        $sExtraLogData .= " OTP Status: $dupStatus";

        // do extra work after OTP validate i.e. when 5
        if ($doExtraTaskPostValidOtp && $dupStatus == 5) {
            $this->doExtraTaskPostOtpUpdate(
                $dbName,
                $clientId,
                $projectId,
                $teamId,
                $arrProjectConfig,
                $updateRecWho,
                $sExtraLogData
            );
        }

        return $dupStatus;
    }

    // returns otp status
    // 0: Invalid data i.e mobile or OTP
    // 1: Miss call not found
    // 2: Unknown
    // 3: Duplicate record
    // 4: Invalid code (i.e Call found but code not match)
    // 5: Valid
    // 6: OTP expired
    final public function getCallStatus($dbName, $isValidatingFromCronjob, $arrOtpValidateConfig, $otpTable, $otpCode, $mobileNo = null, $extraCond = "", $arrExtraParams = array(), $allowAllOtpAsValidOtp = false)
    {
        // Using cronjob to check if call is valid or not after record is uploaded to server
        if ($isValidatingFromCronjob) {
            $processColumn = "dup_process";
            $processedOnColumn = "dup_processed_on";
        } else {
            $processColumn = "process";
            $processedOnColumn = "processed_on";
        }

        // flags
        // used to check the duration between OTP sent from server and request made by user for the OTP validation,
        // If duration exceeds, send OTP expire msg else send OTP
        $canOTPExpire = isset($arrOtpValidateConfig["canOTPExpire"]) ?
            $arrOtpValidateConfig["canOTPExpire"] : false;
        // used when above flag is true, 5min = 300sec means OTP is valid for 5 min only
        $maxOtpValidTimeInSec = isset($arrOtpValidateConfig["maxOtpValidTimeInSec"]) ?
            $arrOtpValidateConfig["maxOtpValidTimeInSec"] : 300;
        $skipMobileNoCheck = isset($arrOtpValidateConfig["skipMobileNoCheck"]) ?
            $arrOtpValidateConfig["skipMobileNoCheck"] : false;
        // used to check whether duplicate call is allowed after given no of days for the same mobile
        $allowBackDateDuplicateCall = isset($arrOtpValidateConfig["allowBackDateDuplicateCall"]) ?
            $arrOtpValidateConfig["allowBackDateDuplicateCall"] : false;
        // should be > 0, means there should be atleast 4 days gap between today and previous call made from the same mobile
        $minGapToAllowDuplicateCallInDays = isset($arrOtpValidateConfig["minGapToAllowDuplicateCallInDays"]) ?
            $arrOtpValidateConfig["minGapToAllowDuplicateCallInDays"] : 0;
        // previous call should not happened today
        $allowDuplicateCallIfDifferentDates = isset($arrOtpValidateConfig["allowDuplicateCallIfDifferentDates"]) ?
            $arrOtpValidateConfig["allowDuplicateCallIfDifferentDates"] : false;
        // update OTP process value
        $updateOTPProcess = isset($arrOtpValidateConfig["updateOTPProcess"]) ?
            $arrOtpValidateConfig["updateOTPProcess"] : true;
        // allow duplicate call with different OTP (this is used in Login via OTP)
        $allowDuplicateCallWithDifferentOtp = isset($arrOtpValidateConfig["allowDuplicateCallWithDifferentOtp"]) ?
            $arrOtpValidateConfig["allowDuplicateCallWithDifferentOtp"] : false;

        $mobRegex = "/^[6789][0-9]{9}$/";
        $otpRegex = "/^[A-Za-z0-9]{4,6}$/";

        // valid OTP as all OTP's are allowed
        if ($allowAllOtpAsValidOtp) {
            $dupStatus = $this->arrOTPStatus["VALID"];
            $updateRecWho = null;
        } elseif (
            (!$skipMobileNoCheck && (!$mobileNo || !preg_match($mobRegex, $mobileNo))) ||
            !$otpCode || !preg_match($otpRegex, $otpCode)
        ) {
            // invalid mobile or OTP
            $dupStatus = $this->arrOTPStatus["INVALID_DATA"];
            $updateRecWho = null;
        } else {
            $otpCode = strtoupper($otpCode);
            $cond = "AND rec_who = ?";
            $arrParams = array($mobileNo);

            // skip mobile no check
            if ($skipMobileNoCheck) {
                $cond = "AND token = ?";
                $arrParams = array($otpCode);
            } elseif ($allowDuplicateCallWithDifferentOtp) {
                // allow duplicate call with different OTP
                $cond .= " AND token = ?";
                $arrParams[] = $otpCode;
            }

            // Get DB connection as per server
            list($connToUse, $tableUtilToUse) = $this->getDbConnectionAsPerServer($dbName);

            // get all records
            $rsAction = null;
            $iActionRows = 0;
            $sQuery = "SELECT token, $processColumn, rdt, rec_who FROM $otpTable WHERE dstatus = 0 $cond $extraCond ORDER BY $processColumn DESC, rec_id DESC";
            $connToUse->ExecuteSelectQuery(
                $sQuery,
                $rsAction,
                $iActionRows,
                array_merge($arrParams, $arrExtraParams)
            );

            // miss call not found
            if ($iActionRows == 0) {
                $dupStatus = $this->arrOTPStatus["MISS_CALL_NOT_FOUND"];
                $updateRecWho = null;
            } else {
                // miss call found
                $isCallFound = false;   // used to exit loop if valid or duplicate call found
                $updateRecWho = null;   // used to update process = '1' for this mobile
                while ($row = $connToUse->GetData($rsAction)) {
                    $tableOtpCode = strtoupper(trim($row['token']));
                    $tableProcess = $row[$processColumn];
                    $oldCallRdt = $row['rdt'];
                    $oldCallRecWho = strtoupper(trim($row['rec_who']));
                    // used if $allowBackDateDuplicateCall = true to get difference b\w old and new call datetime
                    $arrAllUnprocessedCalls = null;

                    // Duplicate call
                    if ($tableProcess == 1) {
                        $isCallFound = true;
                        $dupStatus = $this->arrOTPStatus["DUPLICATE"];
                        $updateRecWho = $oldCallRecWho;

                        if ($skipMobileNoCheck) {
                            // It maybe possible that this ($tableOtpCode) OTP was sent to some other mobile no or
                            // same mobile no but on other day
                            // So check if any record is found with this OTP having $processColumn = 0,
                            // if found, then check if any record exists with found number having $processColumn = 1.
                            // Note: If found means duplicate call else valid call
                            $rsAction1 = null;
                            $iActionRows1 = 0;
                            $sQuery1 = "SELECT rec_who, rdt FROM $otpTable WHERE dstatus = 0 AND token = ? AND $processColumn = '0' $extraCond ORDER BY rec_id DESC";
                            $connToUse->ExecuteSelectQuery(
                                $sQuery1,
                                $rsAction1,
                                $iActionRows1,
                                array_merge(array($tableOtpCode), $arrExtraParams)
                            );

                            if ($iActionRows1 > 0) {
                                $isValidCallFound = false;
                                while ($row1 = $connToUse->GetData($rsAction1)) {
                                    $recWho = $row1["rec_who"];
                                    $rdt = $row1["rdt"];

                                    // Check if any call exist with $recWho mobile and $processColumn = 1,
                                    // means duplicate else valid
                                    $isExist = $tableUtilToUse->isRecordExist(
                                        $otpTable,
                                        "rec_id",
                                        "dstatus = 0 AND $processColumn = '1' AND rec_who = '$recWho'"
                                    );

                                    // valid call
                                    if ($isExist == 0) {
                                        $dupStatus = $this->arrOTPStatus["VALID"];
                                        $isValidCallFound = true;
                                        $updateRecWho = $recWho;
                                        $oldCallRdt = $rdt;
                                    }

                                    // valid call found, exit
                                    if ($isValidCallFound) {
                                        break;
                                    }
                                }
                            }
                        } else {
                            // get latest unprocessed record datetime if old call is not of today
                            if (
                                $allowBackDateDuplicateCall &&
                                $this->commonFunctions->currentDate("Y-m-d", $oldCallRdt) !== $this->commonFunctions->currentDate()
                            ) {
                                $arrAllUnprocessedCalls = $tableUtilToUse->getRowsColumns(
                                    $otpTable,
                                    "token, rdt",
                                    "dstatus = 0 AND rec_who = ? AND $processColumn = '0' $extraCond ORDER BY rec_id DESC",
                                    array_merge(array($updateRecWho), $arrExtraParams)
                                );
                            }
                        }
                    } elseif ($tableProcess == 0) {
                        // Code match
                        if ($tableOtpCode == $otpCode) {
                            // valid call
                            $isCallFound = true;
                            $dupStatus = $this->arrOTPStatus["VALID"];
                            $updateRecWho = $oldCallRecWho;

                            if ($skipMobileNoCheck) {
                                // It maybe possible that user has given multiple missed calls from same mobile
                                // and receive new OTP every time
                                // so check if any record exists with this mobile number and having process = 1
                                // which means duplicate call
                                $arrAnotherCall = $tableUtilToUse->getRowColumns(
                                    $otpTable,
                                    "$processColumn, rdt",
                                    "dstatus = 0 AND rec_who = ? $extraCond ORDER BY $processColumn DESC, rec_id DESC",
                                    array_merge(array($updateRecWho), $arrExtraParams)
                                );

                                // duplicate call
                                if (isset($arrAnotherCall) && $this->commonFunctions->isNonEmptyArray($arrAnotherCall) && $arrAnotherCall[0] == 1) {
                                    $dupStatus = $this->arrOTPStatus["DUPLICATE"];
                                    $oldCallRdt = $arrAnotherCall[1];
                                }
                            }
                        } else {
                            // Invalid code i.e Call found but code not match
                            $dupStatus = $this->arrOTPStatus["INVALID_CODE"];
                        }
                    } else {
                        // unknown
                        $dupStatus = $this->arrOTPStatus["UNKNOWN"];
                    }

                    // allow duplicate call if previous call was made minimun days back
                    if (
                        $dupStatus == $this->arrOTPStatus["DUPLICATE"] && $allowBackDateDuplicateCall &&
                        $arrAllUnprocessedCalls && $this->commonFunctions->isNonEmptyArray($arrAllUnprocessedCalls)
                    ) {
                        // Find if entered OTP matches any unprocessed OTP
                        $searchedIndex = array_search($otpCode, array_column($arrAllUnprocessedCalls, 0));
                        if ($searchedIndex !== false) {
                            $latestUnprocessedCallRdt = $arrAllUnprocessedCalls[$searchedIndex][1];

                            // there should be $minGapToAllowDuplicateCallInDays gap b/w previous and latest call
                            $latest = new DateTime($latestUnprocessedCallRdt, new DateTimeZone(constant("DEFAULT_TIMEZONE")));
                            $old = new DateTime($oldCallRdt, new DateTimeZone(constant("DEFAULT_TIMEZONE")));

                            if ($minGapToAllowDuplicateCallInDays > 0) {
                                $diff = $latest->diff($old);
                                $days = $diff->format('%d');

                                // Valid call if days past is min days
                                if ($days >= $minGapToAllowDuplicateCallInDays) {
                                    $dupStatus = $this->arrOTPStatus["VALID"];
                                    // update $oldCallRdt to check OTP expiry if $canOTPExpire = true
                                    $oldCallRdt = $latestUnprocessedCallRdt;
                                }
                            } elseif ($allowDuplicateCallIfDifferentDates) {
                                // previous call date should be different from today
                                $oldCallDate = $old->format("Y-m-d");
                                $latestCallDate = $latest->format("Y-m-d");

                                // Valid call if dates are not same
                                if ($oldCallDate !== $latestCallDate) {
                                    $dupStatus = $this->arrOTPStatus["VALID"];
                                    // update $oldCallRdt to check OTP expiry if $canOTPExpire = true
                                    $oldCallRdt = $latestUnprocessedCallRdt;
                                }
                            }
                        } else {
                            // User is trying to use either old processed OTP
                            // (OTP that is not sent today and has already processed) or OTP that doesn't exist in table
                            // Don't update latest OTP process value
                            $updateRecWho = null;
                            // Invalid code
                            $dupStatus = $this->arrOTPStatus["INVALID_CODE"];
                        }
                    }

                    // check if OTP is not expired i.e expired after given duration
                    if ($dupStatus == $this->arrOTPStatus["VALID"] && $canOTPExpire) {
                        $nowDate = new DateTime(date("Y-m-d H:i:s"), new DateTimeZone(constant("DEFAULT_TIMEZONE")));
                        $now = $nowDate->getTimestamp();

                        $pastDate = new DateTime($oldCallRdt, new DateTimeZone(constant("DEFAULT_TIMEZONE")));
                        $past = $pastDate->getTimestamp();
                        $noOfSecPassed = abs($now - $past);

                        // OTP expired
                        if ($noOfSecPassed > $maxOtpValidTimeInSec) {
                            $dupStatus = $this->arrOTPStatus["OTP_EXPIRED"];
                            $updateRecWho = null; // don't update $processColumn = '1' if OTP expired
                        }
                    }

                    // exit if call found
                    if ($isCallFound) {
                        break;
                    }
                }

                if ($updateOTPProcess && $updateRecWho) {
                    $rdt = $this->commonFunctions->currentDateTime();
                    $updateCond = "rec_who = ? $extraCond";
                    $arrParams = array($updateRecWho);

                    $sExtraUpdateColumns = "";
                    // Also update process from cronjob in case process was not updated maybe due to offline upload
                    if ($isValidatingFromCronjob) {
                        $sExtraUpdateColumns = ", process = '1', processed_on = '$rdt'";
                    }

                    $tableUtilToUse->updateRecord(
                        $otpTable,
                        "$processColumn = '1', $processedOnColumn = '$rdt' $sExtraUpdateColumns",
                        "dstatus = 0 AND $processColumn = '0' AND $updateCond",
                        array_merge($arrParams, $arrExtraParams)
                    );
                }
            }
        }

        return array($dupStatus, $updateRecWho);
    }

    final public function getUserAndSendOtp()
    {
        list($row, $mobile) = $this->getUser(1);
        if ($row) {
            $this->readSettingsAndSendOtp($row, $mobile);
        }
    }

    final public function getUserAndValidateOtp()
    {
        list($row, $mobile, $code) = $this->getUser(2);
        if ($row) {
            $this->readSettingsAndValidateOtp($row, $mobile, $code);
        }
    }
}
