<?php

// Break feature is available in Radar app when appConfig.buttons.break.hide = 1
// "break": {
//     "label": "Break",
//     "hide": 1,
//     "optList": [
//     "Breakfast",
//     "Lunch",
//     "Dinner",
//     "Sutta"
//     ]
// }

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class BreakFeature extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_break";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/break");
    }

    private function startBreak()
    {
        global $TBL_PROJECT_TEAM;

        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];

        // Check if break is already started, if not, start break
        $iStatus = $this->tableUtil->isRecordExist(
            "$dbName.$TBL_PROJECT_TEAM",
            "team_id",
            "team_id = $teamId AND on_break = '1'"
        );

        // Not on break, update team on break
        if ($iStatus === 0) {
            $iStatus = $this->tableUtil->updateRecord(
                "$dbName.$TBL_PROJECT_TEAM",
                "on_break = '1'",
                "team_id = $teamId"
            );
        }

        // Updated
        if ($iStatus === 1) {
            // Promoter break started
            $response = $this->response->sendResponse(array("message" => $this->arrBreakMessages["BREA01"]), 1);
        } else {
            // Promoter break not started
            $response = $this->response->sendResponse(array("message" => $this->arrBreakMessages["BREA02"]));
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    private function endBreak()
    {
        global $TBL_PROJECT_TEAM, $TBL_BREAK;

        $dbName = $this->arrUserDetails["db_name"];
        $clientId = $this->arrUserDetails["client_id"];
        $projectId = $this->arrUserDetails["project_id"];
        $teamId = $this->arrUserDetails["team_id"];
        $currentDate = $this->commonFunctions->currentDate();
        $currentDateTime = $this->commonFunctions->currentDateTime();

        // start transaction
        $this->dbConn->BeginTransaction();

        $arrStatus = array();
        // Add multiple breaks as internet maybe down at the time of break end
        if ($this->commonFunctions->isNonEmptyArray($this->requestPostData)) {
            foreach ($this->requestPostData as $breakData) {
                $sReason = isset($breakData["reason"]) ? htmlentities($breakData["reason"]) : "";
                $sLT = isset($breakData["lt"]) ? htmlentities($breakData["lt"]) : 0;
                $sLG = isset($breakData["lg"]) ? htmlentities($breakData["lg"]) : 0;
                $sStart_datetime =  is_numeric($breakData["start_dt"]) ?
                    htmlentities(date("Y-m-d H:i:s", ceil($breakData["start_dt"] / 1000))) : $currentDateTime;
                $sEnd_datetime = is_numeric($breakData["end_dt"]) ?
                    htmlentities(date("Y-m-d H:i:s", ceil($breakData["end_dt"] / 1000))) : $currentDateTime;

                $durationInSec = $this->commonFunctions->getTimeDifference($sStart_datetime, $sEnd_datetime, true);

                $cols = "client_id, project_id, team_id, start_datetime, end_datetime, break_duration_in_sec" .
                    ", reason, lt, lg, rcd, rdt";
                $vals = "$clientId, $projectId, $teamId, ?, ?, $durationInSec, ?, ?, ?" .
                    ", '$currentDate', '$currentDateTime'";
                $arrParams = array(
                    "start" => $sStart_datetime, "end" => $sEnd_datetime, "reason" => $sReason,
                    "lt" => $sLT, "lg" => $sLG
                );

                // Add break record
                $iStatus = $this->tableUtil->addRecord(
                    "$dbName.$TBL_BREAK",
                    $cols,
                    $vals,
                    $arrParams
                );
                $arrStatus[] = $iStatus;
            }
        }

        // some error in upload, abort break
        if (!$this->commonFunctions->isNonEmptyArray($arrStatus) || in_array(0, $arrStatus)) {
            // rollback transaction
            $this->dbConn->RollbackTransaction();

            // Promoter break not ended
            $response = $this->response->sendResponse(array("message" => $this->arrBreakMessages["BREA04"]));
        } else {
            // all success, commit break
            $this->dbConn->CommitTransaction();

            // End break
            $this->tableUtil->updateRecord(
                "$dbName.$TBL_PROJECT_TEAM",
                "on_break = '0'",
                "team_id = $teamId"
            );

            // Promoter break end
            $response = $this->response->sendResponse(array("message" => $this->arrBreakMessages["BREA03"]), 1);
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function startStopBreak()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            // start = 1 -> Starting break;
            // start = 2 -> end break
            $startBreak = isset($this->requestGetData["start"]) ? $this->requestGetData["start"] : 1;

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: {$this->arrUserDetails["team_id"]}";

            // Start break
            if ($startBreak == 1) {
                $this->startBreak();
            } else {
                // End break
                $this->endBreak();
            }
        }
    }
}

$break = new BreakFeature($dbConn, $tableUtil, $commonFunctions);
$break->startStopBreak();
$dbConn->Close();
