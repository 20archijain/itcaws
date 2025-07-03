<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetCalendarStatus extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_calendar_status";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/calendar");
    }

    final public function getStatus()
    {
        global $TBL_MOBILE_CALENDAR_SUMMARY;

        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            $sSummary = $this->tableUtil->getRowColumn(
                "$dbName.$TBL_MOBILE_CALENDAR_SUMMARY",
                "summary",
                "dstatus = 0 AND team_id = $teamId"
            );

            if ($sSummary) {
                $iStatus = 1;
                $arrResponse = json_decode(html_entity_decode($sSummary), true);

                $message = "";
            } else {
                $iStatus = 0;
                $arrResponse = array();

                // No data found
                $message = $this->arrCustomMessages["CUST05"];
            }

            $response = $this->response->sendResponse(array("message" => $message, "response" => $arrResponse), $iStatus);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }
}

$status = new GetCalendarStatus($dbConn, $tableUtil, $commonFunctions);
$status->getStatus();
$dbConn->Close();
