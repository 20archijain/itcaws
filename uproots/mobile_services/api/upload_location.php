<?php

// This API is used to
// 1. upload user's current location
// 2. find any Deviation in user's location
// Note: Deviation is considered if a user is not within the range of 300meter or as per defined limit from his attendance location

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class UploadLocation extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_upload_location";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/upload_location");
    }

    private function upload()
    {
        global $TBL_ATTENDANCE, $TBL_PROJECT_TEAM, $TBL_TEAM_LOCATION;

        $currentDate = $this->commonFunctions->currentDate();
        $currentDateTime = $this->commonFunctions->currentDateTime();

        $sLT = isset($this->requestPostData["lat"]) ? htmlentities($this->requestPostData["lat"]) : 0;
        $sLG = isset($this->requestPostData["lng"]) ? htmlentities($this->requestPostData["lng"]) : 0;
        $sM_datetime = $this->requestPostData["dt"] && is_numeric($this->requestPostData["dt"]) ?
            htmlentities(date("Y-m-d H:i:s", ceil($this->requestPostData["dt"] / 1000))) : $currentDateTime;
        $sM_date = explode(" ", $sM_datetime)[0];
        $iDistanceInMeters = isset($this->requestPostData["distanceInMeters"]) &&
            is_numeric($this->requestPostData["distanceInMeters"]) ?
            round(htmlentities($this->requestPostData["distanceInMeters"]), 8) : 0;

        $dbName = $this->arrUserDetails["db_name"];
        $clientId = $this->arrUserDetails["client_id"];
        $projectId = $this->arrUserDetails["project_id"];
        $teamId = $this->arrUserDetails["team_id"];

        // Add current location record
        $iRows = $this->tableUtil->addRecord(
            "$dbName.$TBL_TEAM_LOCATION",
            "client_id, project_id, team_id, lt, lg, distance_travelled_in_meter, capture_date, capture_datetime, rcd, rdt",
            "?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
            array($clientId, $projectId, $teamId, $sLT, $sLG, $iDistanceInMeters, $sM_date, $sM_datetime, $currentDate, $currentDateTime)
        );

        if ($iRows > 0) {
            $tl_id = null;
            $this->dbConn->GetLastInsertId($tl_id);

            // Gt user's today's attendance location
            $arrTeamAttendanceLocationInfo = $this->tableUtil->getRowColumns(
                "$dbName.$TBL_ATTENDANCE",
                "lt, lg",
                "team_id = $teamId AND call_type = '0' AND capture_date = '$currentDate'"
            );

            $attendanceLt = isset($arrTeamAttendanceLocationInfo[0]) ? $arrTeamAttendanceLocationInfo[0] : 0;
            $attendanceLg = isset($arrTeamAttendanceLocationInfo[1]) ? $arrTeamAttendanceLocationInfo[1] : 0;

            // Find if deviation is present or not, deviation is present if distance between attendace and current location is > 500 meter(> 5000 meter for pid = 118)
            $maxDistanceInMeter = 500;
            if ($projectId == 118) {
                $maxDistanceInMeter = 5000;
            }
            $distanceInMeter = $this->commonFunctions->calculateDistanceBwCoordinates($attendanceLt, $attendanceLg, $sLT, $sLG);
            $isDeviationPresent = $distanceInMeter > $maxDistanceInMeter ? 1 : 0;

            // Check if team is OUT (means not in range) or IN (means in range) from his attendance location
            // in_date IS NULL means team is out
            $isDeviationRecordExist = $this->tableUtil->getRowColumn(
                "$dbName.tblteam_deviation_location",
                "dl_id",
                "team_id = $teamId AND capture_date = ? AND in_date IS NULL",
                array($sM_date)
            );

            // Team is already OUT
            if ($isDeviationRecordExist) {
                // Check if deviation is more than defined limit or not
                // if > defined limit, team is still out so don't do anything
                // else team has come inside the range so update team's IN date and datetime
                if (!$isDeviationPresent) {
                    $this->tableUtil->updateRecord(
                        "$dbName.tblteam_deviation_location",
                        "in_date = '$currentDate', in_datetime = '$currentDateTime'",
                        "dstatus = 0 AND dl_id = $isDeviationRecordExist"
                    );
                }
            } else {
                // Team is IN

                // Check if deviation is more than defined limit or not
                // if > defined limit, team is out so check break and add deviation
                // else don't do anything

                // Check if team is on break or not
                $iStatusBreak = $this->tableUtil->isRecordExist(
                    "$dbName.$TBL_PROJECT_TEAM",
                    "team_id",
                    "team_id = $teamId AND on_break = '1'"
                );

                // Add record in deviation table if user is not on break
                if (
                    $isDeviationPresent && !$iStatusBreak && $attendanceLt != 0 && $attendanceLg != 0 &&
                    $sLT != 0 && $sLG != 0
                ) {
                    $this->tableUtil->addRecord(
                        "$dbName.tblteam_deviation_location",
                        "tl_id, client_id, project_id, team_id, current_lt, current_lg, deviation_distance_travelled_in_meter, capture_date, capture_datetime, rcd, rdt",
                        "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
                        array(
                            $tl_id, $clientId, $projectId, $teamId, $sLT, $sLG,
                            $distanceInMeter, $sM_date, $sM_datetime, $currentDate, $currentDateTime
                        )
                    );
                }
            }

            // Data uploaded successfully
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA02"]), 1);
        } else {
            // Data not uploaded
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA03"]));
        }

        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function uploadLocation()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            $this->upload();
        }
    }
}

$location = new UploadLocation($dbConn, $tableUtil, $commonFunctions);
$location->uploadLocation();
$dbConn->Close();
