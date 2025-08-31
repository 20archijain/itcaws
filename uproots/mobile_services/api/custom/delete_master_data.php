<?php

// Used in ITC Phase 2 setup to get the Route on map in app

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class DeleteRouteData extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_ae_app_ds_summary_data";
    private $sExtraLogData;
    protected $requestGetData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"];
    }

    final public function deleteData()
    {
        global $TBL_ROUTE_DETAILS;

        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        $dbName = $this->arrUserDetails["db_name"];

        if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
            $this->requestGetData = $_GET;
        }

        // Rout Id
        $recId = isset($this->requestGetData['recId']) ? $this->requestGetData['recId'] : null;
        if (!empty($recId)) {
            $status = $this->tableUtil->updateRecord("$dbName.$TBL_ROUTE_DETAILS", "dstatus = 1", "rec_id = $recId");

            if ($status === 1) {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST06"]], 1);
                $this->logOutput($response, $this->sExtraLogData);
            } else {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST06"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }

    final public function deleteMaterData()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->deleteData();
            } else {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST01"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}
$stock = new DeleteRouteData($dbConn, $tableUtil, $commonFunctions);
$stock->deleteMaterData();
$dbConn->Close();
