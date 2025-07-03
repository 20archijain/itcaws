<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/AppSummary.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetSummary extends AppSummary
{
    private $appType = 1;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, "log_summary");
    }

    private function outputSummary($arrSummary = array(), $arrCustomResp = array(), $arrTopSummary = array())
    {
        // No Summary to display
        if (!$this->commonFunctions->isNonEmptyArray($arrSummary)) {
            $arrSummary = $this->getDefaultSummary($this->appType);
        }

        // Display $arrTopSummary summary first and then $arrSummary
        $arrSummary = array_merge($arrTopSummary, $arrSummary);

        // Add app and json min version
        $arrCustomResp = array_merge($arrCustomResp, $this->getAppAndJsonMinVersion());

        $response = $this->response->sendResponse(
            array("message" => "", "response" => $arrSummary),
            1,
            $arrCustomResp
        );
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getSummaryData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $clientId = $this->arrUserDetails["client_id"];
            $projectId = $this->arrUserDetails["project_id"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId";

            // Check whether to show summary
            if ($this->isSummaryVisible($dbName, $clientId, $projectId)) {
                list($arrSummary, $arrCustomResp, $arrTopSummary) = $this->getSummary($this->appType);
                $this->outputSummary($arrSummary, $arrCustomResp, $arrTopSummary);
            } else {
                $this->outputSummary();
            }
        }
    }
}

$summary = new GetSummary($dbConn, $tableUtil, $commonFunctions);
$summary->getSummaryData();
$dbConn->Close();
