<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetOfflineDropdownOptions extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_offline_dropdown_options";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/offline_dropdown");
    }

    final public function getDropdownOptions()
    {
        global $TBL_OFFLINE_DROPDOWN_OPTIONS;

        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            $sOptions = $this->tableUtil->getRowColumn(
                "$dbName.$TBL_OFFLINE_DROPDOWN_OPTIONS",
                "options_list",
                "dstatus = 0 AND team_id = $teamId"
            );
            $arrResponse = $sOptions ?
                json_decode(
                    html_entity_decode(html_entity_decode($sOptions)),
                    true
                ) : array();

            $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }
}

$offline = new GetOfflineDropdownOptions($dbConn, $tableUtil, $commonFunctions);
$offline->getDropdownOptions();
$dbConn->Close();
