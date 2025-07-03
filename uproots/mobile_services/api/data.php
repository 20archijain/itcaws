<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class UploadData extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_data";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/data");
    }

    private function validateData()
    {
        return $this->commonFunctions->isNonEmptyArray($this->requestPostData);
    }

    private function upload()
    {
        global $TBL_SURVEY_RES_NEW, $IMPACT_DB, $NOVICEMARCOM_DB, $WONDER_DB, $ZX_DB;

        $dbName = $this->arrUserDetails["db_name"];
        $clientId = $this->arrUserDetails["client_id"];
        $projectId = $this->arrUserDetails["project_id"];
        $teamId = $this->arrUserDetails["team_id"];
        $jsonId = $this->arrUserDetails["s_id"];
        $currentDate = $this->commonFunctions->currentDate();
        $currentDateTime = $this->commonFunctions->currentDateTime();

        // start transaction
        $this->dbConn->BeginTransaction();
        $arrStatus = array();
        $arrQuery = array();

        foreach ($this->requestPostData as $arrResponse) {
            $iUnique_Id = $teamId . htmlentities($arrResponse["uniqueId"]);
            $sSID = isset($arrResponse["surveyId"]) ? htmlentities($arrResponse["surveyId"]) : 0;
            $sTime_Diff = isset($arrResponse["surveyTime"]) ? abs(htmlentities($arrResponse["surveyTime"])) : 0;
            $sLT = isset($arrResponse["lt"]) && $arrResponse["lt"] !== "" ? floatval($arrResponse["lt"]) : 0;
            $sLG = isset($arrResponse["lg"]) && $arrResponse["lg"] !== "" ? floatval($arrResponse["lg"]) : 0;
            $sM_datetime = isset($arrResponse["dt"]) ?
                htmlentities(date("Y-m-d H:i:s", ceil($arrResponse["dt"] / 1000))) : $currentDateTime;
            $sM_date = explode(" ", $sM_datetime)[0];
            // if capture dateTime is greater than current dateTime then replace capture dateTime to current dateTime
            if ($sM_datetime > $currentDateTime && $sM_date > $currentDate) {
                $sM_date = $currentDate;
                $sM_datetime = $currentDateTime;
            }
            $sSu_Content = json_encode($arrResponse["appFormList"]);
            $iDistanceTravelledInKm = isset($arrResponse["totalDistance"]) ?
                round(htmlentities($arrResponse["totalDistance"]), 8) : 0;
            // Coming in new Flutter radar app
            $iDistanceInMeters = isset($arrResponse["distanceInMeters"]) ?
                round(htmlentities($arrResponse["distanceInMeters"]), 8) : 0;

            // If call time is more than 2hr, set random time between 1hr and 2hr
            $msIn1Hr = 2 * 60 * 60 * 1000;
            if ($sTime_Diff > $msIn1Hr) {
                $randomMinuteBw1And2Hr = rand(60, 120);
                $sTime_Diff = $randomMinuteBw1And2Hr * 60 * 1000;
            }

            // update JSON ID
            if (!$sSID) {
                $sSID = $jsonId;
            }

            // Check if record is already uploaded or not, if not then only upload
            $isRecordExist = $this->tableUtil->isRecordExist(
                "$dbName.$TBL_SURVEY_RES_NEW",
                "resp_id",
                "dstatus = 0 AND uni_id = ? AND team_id = ?",
                array($iUnique_Id, $teamId)
            );

            // Not exist, so upload
            if ($isRecordExist == 0) {
                $sInsert_Action = null;
                $sInsert_Result = 0;
                // Impact, Novicemarcom, Wonder, ZX
                if (
                    $dbName === $IMPACT_DB || $dbName === $NOVICEMARCOM_DB ||
                    $dbName === $WONDER_DB || $dbName === $ZX_DB
                ) {
                    $Query_Insert_Org = "INSERT INTO $dbName.$TBL_SURVEY_RES_NEW (cid, pid, team_id" .
                        ", uni_id, lic_auth_code, s_id, sur_response, distance_travelled_in_km" .
                        ", distance_travelled_in_meter, capture_date, capture_datetime, upload_date" .
                        ", upload_datetime, p_lt, p_lg, lt, lg, rcd, rdt, call_time) VALUES ('$clientId'" .
                        ", '$projectId', '$teamId', '$iUnique_Id', '{$this->sToken}', '$sSID', '$sSu_Content'" .
                        ", '$iDistanceTravelledInKm', '$iDistanceInMeters', '$sM_date', '$sM_datetime'" .
                        ", '$currentDate', '$currentDateTime', '$sLT', '$sLG', '$sLT', '$sLG', '$currentDate'" .
                        ", '$currentDateTime', '$sTime_Diff')";

                    $Query_Insert = "INSERT INTO $dbName.$TBL_SURVEY_RES_NEW (cid, pid, team_id" .
                        ", uni_id, lic_auth_code, s_id, sur_response, distance_travelled_in_km" .
                        ", distance_travelled_in_meter, capture_date, capture_datetime, upload_date" .
                        ", upload_datetime, p_lt, p_lg, lt, lg, rcd, rdt, call_time) VALUES ('$clientId'" .
                        ", '$projectId', '$teamId', ?, ?, ?, ?, ?, ?, ?, ?, '$currentDate'" .
                        ", '$currentDateTime', ?, ?, ?, ?, '$currentDate', '$currentDateTime', ?)";

                    $arrParams = array(
                        "iUnique_Id" => $iUnique_Id, "sToken" => $this->sToken, "sSID" => $sSID,
                        "sSu_Content" => $sSu_Content, "iDistanceTravelledInKm" => $iDistanceTravelledInKm,
                        "iDistanceInMeters" => $iDistanceInMeters, "sM_date" => $sM_date,
                        "sM_datetime" => $sM_datetime, "sLT" => $sLT, "sLG" => $sLG,
                        "psLT" => $sLT, "psLG" => $sLG, "sTime_Diff" => $sTime_Diff
                    );
                } else {
                    $Query_Insert_Org = "INSERT INTO $dbName.$TBL_SURVEY_RES_NEW (client_id" .
                        ", project_id, team_id, uni_id, lic_auth_code, s_id, sur_response" .
                        ", distance_travelled_in_km, distance_travelled_in_meter, capture_date, capture_datetime" .
                        ", lt, lg, rcd, rdt, call_time) VALUES ('$clientId', '$projectId', '$teamId'" .
                        ", '$iUnique_Id', '{$this->sToken}', '$sSID', '$sSu_Content', $iDistanceTravelledInKm" .
                        ", $iDistanceInMeters, '$sM_date', '$sM_datetime', '$sLT', '$sLG', '$currentDate'" .
                        ", '$currentDateTime', '$sTime_Diff')";

                    $Query_Insert = "INSERT INTO $dbName.$TBL_SURVEY_RES_NEW (client_id" .
                        ", project_id, team_id, uni_id, lic_auth_code, s_id, sur_response" .
                        ", distance_travelled_in_km, distance_travelled_in_meter, capture_date, capture_datetime" .
                        ", lt, lg, rcd, rdt, call_time) VALUES ('$clientId', '$projectId', '$teamId'" .
                        ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '$currentDate', '$currentDateTime', ?)";

                    $arrParams = array(
                        "iUnique_Id" => $iUnique_Id, "sToken" => $this->sToken, "sSID" => $sSID,
                        "sSu_Content" => $sSu_Content, "iDistanceTravelledInKm" => $iDistanceTravelledInKm,
                        "iDistanceInMeters" => $iDistanceInMeters,
                        "sM_date" => $sM_date, "sM_datetime" => $sM_datetime, "sLT" => $sLT,
                        "sLG" => $sLG, "sTime_Diff" => $sTime_Diff
                    );
                }

                $this->dbConn->ExecuteQuery($Query_Insert, $sInsert_Action, $sInsert_Result, $arrParams);
                $arrStatus[] = $sInsert_Result;
                $arrQuery[] = $Query_Insert_Org;
            }
        }

        // some error in upload, abort
        if (in_array(0, $arrStatus)) {
            // rollback transaction
            $this->dbConn->RollbackTransaction();

            // Data not uploaded
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA03"]));
        } else {
            // all success, commit
            $this->dbConn->CommitTransaction();

            // Data uploaded successfully
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA02"]), 1);
        }

        $this->sExtraLogData .= "\r\nQuery:\r\n" . implode("\r\n", $arrQuery);
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function uploadData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->upload();
            } else {
                // Data cannot be empty
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$upload = new UploadData($dbConn, $tableUtil, $commonFunctions);
$upload->uploadData();
$dbConn->Close();
