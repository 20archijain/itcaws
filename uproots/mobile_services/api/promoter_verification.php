<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
require_once $CLASSES_PATH . "/Otp.php";

// phpcs:ignore
class PromoterVerification extends Otp
{
    private $arrUserDetails;
    private $missCallTable;
    private $smsProvider;
    private $smsSenderId;
    private $smsTemplateId;
    private $mobile;
    private $code;
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, "log_promoter_verification");
        // OTP settings
        $this->missCallTable = $GLOBALS["TBL_CLOUDRING_PROMOTER_VERIFICATION"];
        $this->smsProvider = 4;
        $this->smsSenderId = "APPsms";
        $this->smsTemplateId = "";
    }

    private function sendOTPForPromoterVerification()
    {
        if ($this->mobile && is_numeric($this->mobile)) {
            // Generate OTP
            $getUniqueCode = $this->generateUniqueNumericCode(6);

            $message = "Thanks for registering at Appilary. Use OTP $getUniqueCode to verify your mobile number";

            list($isOtpSent, $sOutput) = $this->sendOTP(
                $this->mobile,
                $message,
                $this->smsProvider,
                $this->smsSenderId,
                $this->smsTemplateId
            );

            $this->sExtraLogData .= "\r\nMISS CALL API OUTPUT: $sOutput";

            if ($isOtpSent) {
                $this->sExtraLogData .= "\r\nOTP: $getUniqueCode Message: $message";

                $clientId = $this->arrUserDetails["client_id"];
                $projectId = $this->arrUserDetails["project_id"];
                $teamId = $this->arrUserDetails["team_id"];
                $dbName = $this->arrUserDetails["db_name"];
                $currentDate = $this->commonFunctions->currentDate();
                $currentDateTime = $this->commonFunctions->currentDateTime();

                $iNum_Action = null;
                $iNum_rows = 0;
                $sIN_Query_Org = "INSERT INTO {$this->missCallTable} (db_name, client_id" .
                    ", project_id, team_id, process, token, rec_who, api_output, rcd, rdt)" .
                    " VALUES ('$dbName', $clientId, $projectId, $teamId, '0', '$getUniqueCode', '{$this->mobile}'" .
                    ", '$sOutput', '$currentDate', '$currentDateTime')";
                $sIN_Query = "INSERT INTO {$this->missCallTable} (db_name, client_id" .
                    ", project_id, team_id, process, token, rec_who, api_output, rcd, rdt)" .
                    " VALUES ('$dbName', $clientId, $projectId, $teamId, '0', '$getUniqueCode', ?, ?" .
                    ", '$currentDate', '$currentDateTime')";
                $arrParams = array("mobile" => $this->mobile, "output" => $sOutput);
                $this->sExtraLogData .= "\r\nQUERY: $sIN_Query_Org";
                $this->dbConn->ExecuteQuery($sIN_Query, $iNum_Action, $iNum_rows, $arrParams);

                if ($iNum_rows > 0) {
                    // OTP sent
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP08"]), 1);
                } else {
                    // OTP sent but failed to store in DB
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP07"]), 1);
                }
            } else {
                // OTP not sent. Server down
                $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP05"]));
            }
        } else {
            if (!$this->mobile) {
                // Fields cannot be blank
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH01"]));
            } else {
                // Invalid data
                $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP00"]));
            }
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    private function checkIfMissCallGiven($teamId)
    {
        return $this->tableUtil->isRecordExist(
            constant("DB_NAME") . ".{$this->missCallTable}",
            "rec_id",
            "dstatus = 0 AND team_id = $teamId AND process = '0' AND token = ? AND rec_who = ?",
            array($this->code, $this->mobile)
        );
    }

    private function processMissCallGiven($teamId)
    {
        $this->tableUtil->updateRecord(
            constant("DB_NAME") . ".{$this->missCallTable}",
            "process = '1'",
            "dstatus = 0 AND team_id = $teamId AND token = ? AND rec_who = ?",
            array("code" => $this->code, "mobile" => $this->mobile)
        );
    }

    private function validateOTPForPromoterVerification()
    {
        global $TBL_CLOUD_AUTH_PIN;

        if ($this->mobile && is_numeric($this->mobile) && $this->code && preg_match("/^[A-Za-z0-9]+$/", $this->code)) {
            $recId = $this->arrUserDetails["rec_id"];
            $teamId = $this->arrUserDetails["team_id"];
            $dbMobileNo = $this->arrUserDetails["mobile"];

            // Mobile exists in DB
            // so check whether DB's and coming mobile (where OTP was sent) matches or not
            // if matches and OTP is valid, allow to login else don't allow
            if ($dbMobileNo) {
                // Both mobile matches so check miss call
                if ($dbMobileNo == $this->mobile) {
                    // Check if miss call found or not
                    $iStatus = $this->checkIfMissCallGiven($teamId);

                    // miss call found means valid user
                    if ($iStatus > 0) {
                        // Update process flag
                        $this->processMissCallGiven($teamId);

                        // Valid User
                        $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP09"]), 1);
                    } else {
                        // miss call not found means invalid user

                        // Miss call not found or Invalid code
                        $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP10"]));
                    }
                } else {
                    // Mobile not matches means some other person is using this credentials or person is changed by agency

                    // This account is already associated with a different mobile
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP21"]));
                }
            } else {
                // Mobile doesn't exists in DB so update mobile in DB if miss call found

                // Check if miss call found or not
                $iStatus = $this->checkIfMissCallGiven($teamId);

                // miss call found means valid user
                if ($iStatus > 0) {
                    // Update process flag
                    $this->processMissCallGiven($teamId);

                    // Update mobile number in DB
                    $this->tableUtil->updateRecord(
                        constant("DB_NAME") . ".$TBL_CLOUD_AUTH_PIN",
                        "mobile = ?",
                        "rec_id = $recId",
                        array("mobile" => $this->mobile)
                    );

                    // Valid User
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP09"]), 1);
                } else {
                    // miss call not found means invalid user

                    // Miss call not found or Invalid code
                    $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP10"]));
                }
            }
        } else {
            if (!$this->mobile || !$this->code) {
                // Fields cannot be blank
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH01"]));
            } else {
                // Invalid data
                $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP00"]));
            }
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function verifyPromoter()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $type = $this->requestGetData["type"];    // type = 1 means Send OTP, type = 2 means Validate OTP
            $this->mobile = isset($this->requestPostData["mobile"]) ? strtolower(trim($this->requestPostData["mobile"])) : "";
            $this->code = isset($this->requestPostData["code"]) ? strtolower(trim($this->requestPostData["code"])) : "";

            $this->sExtraLogData = "DB: {$this->arrUserDetails["db_name"]} Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: {$this->arrUserDetails["team_id"]}";
            $this->sExtraLogData .= "\r\nMissCallTable: {$this->missCallTable} Mobile: {$this->mobile} OTP: {$this->code} smsProvider: {$this->smsProvider} smsSenderId: {$this->smsSenderId} smsTemplateId: {$this->smsTemplateId}";

            // Send OTP
            if ($type == 1) {
                $this->sendOTPForPromoterVerification();
            } else {
                // Validate OTP
                $this->validateOTPForPromoterVerification();
            }
        }
    }
}

$promoterVerification = new PromoterVerification($dbConn, $tableUtil, $commonFunctions);
$promoterVerification->verifyPromoter();
$dbConn->Close();
