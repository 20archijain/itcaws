<?php

// phpcs:ignore
class AppLogin extends Utilities
{
    private $logFilename = "log_auth";
    private $sUsername;
    private $sPassword;
    private $sIMEI;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->logFilename);
    }

    private function validateData()
    {
        if (
            $this->commonFunctions->isNonEmptyArray($this->requestPostData) && isset($this->requestPostData["username"]) &&
            $this->requestPostData["username"]
        ) {
            $this->sUsername = $this->requestPostData["username"];
        }
        if (
            $this->commonFunctions->isNonEmptyArray($this->requestPostData) && isset($this->requestPostData["password"]) &&
            $this->requestPostData["password"]
        ) {
            $this->sPassword = $this->requestPostData["password"];
        }
        if ($this->commonFunctions->isNonEmptyArray($this->requestPostData) && isset($this->requestPostData["imei"])) {
            $this->sIMEI = $this->requestPostData["imei"];
        }

        return $this->sUsername && $this->sPassword ? true : false;
    }

    private function sendLoginResponse($row, $loginMethod, $msg = "", $sExtraLogData = "")
    {
        global $TBL_PROJECT_TEAM, $PRODS_ANY_FOLDER, $CLIENTS_FOLDER, $JSON_FOLDER, $RES_FOLDER,
            $DRAWABLE_FOLDER, $ITCNEW_DB, $TBL_CLOUD_AUTH_PIN, $DELHI_DB, $ITC_DB, $JAIPUR_DB,
            $SNPL_DB, $SOUTH_DB, $BTLMO74_DB_NAME, $BTLMO74_DB_USERNAME,
            $BTLMO74_DB_PASSWORD, $TBL_PROJECTS, $TBL_APP_LOGIN_LOG;

        $recId = $row["rec_id"];
        $cusFolder = $row["client_res"];            // Customer Folder
        $clientFolder = $row["proj_res_folder"];    // Client Folder
        $deviceType = $row['device_type'];
        // $imei = $row['imei'];
        $clientId = $row["client_id"];
        $projectId = $row["project_id"];
        $teamId = $row["team_id"];
        $dbName = $row['db_name'];
        $cSubdomain = $row['c_subdomain'];
        $jsonName = $row["c_init_xml"];
        $token = $row["token"];
        $teamName = $row["team_name"] ?
            $row["team_name"] : $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "team_name",
                "team_id = $teamId"
            );

        $status = 1;
        // Update IMEI
        // if ($this->sIMEI && !$imei) {
        //     $sAction = null;
        //     $iActionRows2 = 0;
        //     $sQuery2 = "UPDATE $TBL_CLOUD_AUTH_PIN SET imei = ? WHERE dstatus = 0 AND token = '$token' LIMIT 1";
        //     $this->dbConn->ExecuteQuery($sQuery2, $sAction, $iActionRows2, array("sIMEI" => $this->sIMEI));
        // } else {
        //     // not same IMEI, generate warning
        //     if (strtolower($imei) !== strtolower($this->sIMEI)) {
        //         $status = 2;
        //         // This pin is already used on another phone. Please confirm to continue?
        //         $msg = $this->arrAuthMessages["AUTH03"];
        //     }
        // }

        $baseUrl = $cSubdomain . $PRODS_ANY_FOLDER;
        $assetsFolder = $baseUrl . "/" . $cusFolder . $CLIENTS_FOLDER . "/" . $clientFolder;

        $json_link = $assetsFolder . $JSON_FOLDER . "/" . $jsonName;
        $splash = $assetsFolder . $RES_FOLDER . "/$deviceType" . $DRAWABLE_FOLDER . "/splash.png";
        $logo = $assetsFolder . $RES_FOLDER . "/$deviceType" . $DRAWABLE_FOLDER . "/logo.png";

        $arrResp = array(
            "token" => base64_encode("$token:$token"),
            "json_link" => $json_link,
            "splash_img" => $splash,
            "logo_img" => $logo,
            "client_name" => $teamName,
        );

        // Show OTP screen after username and password in old/kotlin radar app
        if ($dbName === $ITCNEW_DB && ($teamId == "12464" || $teamId == "12268")) {
            $arrResp["showOtpScreen"] = true;
        }

        $currentDate = $this->commonFunctions->currentDate();
        $currentDateTime = $this->commonFunctions->currentDateTime();

        // Update loggedin datetime
        $this->tableUtil->updateRecord(
            constant("DB_NAME") . ".$TBL_CLOUD_AUTH_PIN",
            "last_loggedin_datetime = '$currentDateTime'",
            "rec_id = $recId"
        );

        // Set whether to show one time agreement or not
        // Note: Agreement is visible if project requires agreement and user/team has not uploaded it yet
        if (
            $dbName === $DELHI_DB || $dbName === $ITC_DB || $dbName === $ITCNEW_DB ||
            $dbName === $JAIPUR_DB || $dbName === $SNPL_DB || $dbName === $SOUTH_DB ||
            in_array($dbName, $GLOBALS["ARR_BTLMO74_DBS"])
        ) {
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

            // Check if agreement is required or not in a project
            $requireAgreement = $tableUtilToUse->isRecordExist(
                "$dbName.$TBL_PROJECTS",
                "project_id",
                "project_id = $projectId AND require_agreement_upload = '1'"
            );

            // Require agreement upload, check if user has uploaded it or not
            if ($requireAgreement === 1) {
                $uploadedAgreement = $tableUtilToUse->getRowColumn(
                    "$dbName.$TBL_PROJECT_TEAM",
                    "uploaded_agreement",
                    "team_id = $teamId"
                );

                // Agreement not uploaded, so send the agreement form ID in the response
                if ($uploadedAgreement == 0) {
                    $arrResp["agreementFormId"] = 9999;
                }
            }

            if ($newConn) {
                $newConn->Close();
            }
        }

        // Output
        $arrResponse = array("message" => $msg, "response" => $arrResp);

        // Add login details in log
        $this->tableUtil->addRecord(
            constant("DB_NAME") . ".$TBL_APP_LOGIN_LOG",
            "db_name, client_id, project_id, team_id, login_method, response, rcd, rdt",
            "?, ?, ?, ?, ?, ?, ?, ?",
            array(
                $dbName, $clientId, $projectId, $teamId, $loginMethod,
                json_encode($arrResponse), $currentDate, $currentDateTime
            )
        );

        $response = $this->response->sendResponse($arrResponse, $status);
        $this->logOutput($response, $sExtraLogData);
    }

    final public function getUserFromToken($sToken)
    {
        global $TBL_CLOUD_AUTH_PIN;

        if (!$sToken) {
            return false;
        }

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT rec_id, s_id, client_res, proj_res_folder, device_type, imei, mobile, client_id, project_id, team_id" .
            ", team_name, db_name, c_subdomain, c_init_xml, token FROM $TBL_CLOUD_AUTH_PIN" .
            " WHERE token = ? AND dstatus = 0 LIMIT 1";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, array($sToken));

        if ($iActionRows === 1) {
            return $this->dbConn->GetData($rsAction);
        }

        return array();
    }

    final public function loginViaUsername()
    {
        global $TBL_CLOUD_AUTH_PIN;

        // Valid data
        if ($this->validateData()) {
            $rsAction = null;
            $iActionRows = 0;
            $sQuery = "SELECT rec_id, client_res, proj_res_folder, device_type, imei, client_id, project_id, team_id" .
                ", team_name, db_name, c_subdomain, c_init_xml, token FROM $TBL_CLOUD_AUTH_PIN" .
                " WHERE username = ? AND password = ? AND dstatus = 0 LIMIT 1";
            $this->dbConn->ExecuteSelectQuery(
                $sQuery,
                $rsAction,
                $iActionRows,
                array($this->sUsername, $this->sPassword)
            );

            // User found
            if ($iActionRows === 1) {
                $row = $this->dbConn->GetData($rsAction);
                $this->sendLoginResponse($row, 0, $this->arrAuthMessages["AUTH08"]);
            } else {
                // Invalid credentials
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH02"]));
                $this->logOutput($response);
            }
        } else {
            // Fields cannot be blank
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH01"]));
            $this->logOutput($response);
        }
    }

    final public function loginViaOtp($sToken, $sExtraLogData = "")
    {
        $row = $this->getUserFromToken($sToken);
        $this->sendLoginResponse($row, 1, $this->arrOTPMessages["OTP16"], $sExtraLogData);
    }
}
