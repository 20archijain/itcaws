<?php

// Used in ITC Phase 2 setup to get the Route on map in app

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetAeMaster extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_ae_app_master_data";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"];
    }

    private function getAeMaster()
    {
        global $TBL_ROUTE_DETAILS;

        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        $dbName = $this->arrUserDetails["db_name"];

        $todayDate = date('Y-m-d');
        $oneMonthAgoDate = date('Y-m-d', strtotime('-1 months'));

        // Fetch visited outlet rec_ids in last 1 month
        $visitedOutletIds = $this->tableUtil->getRowsColumn(
            "$dbName.tblsurvey_response_details",
            "ques_3",
            "dstatus = 0 AND ques_0 = 'Outlet Order' AND capture_date BETWEEN '$oneMonthAgoDate' AND '$todayDate' AND team_id = '$teamId'",
            [],
            true
        );

        $sQuery = "SELECT rec_id, route_name, outlet_name, outlet_mobile, lt, lg 
                   FROM $dbName.$TBL_ROUTE_DETAILS 
                   WHERE dstatus = 0 AND team_id = '$teamId'";
        $sAction = null;
        $iRows = 0;
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        $groupedRoutes = [];

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                $routeName = $row["route_name"];
                $recId = $row["rec_id"];
                $outletName = $row["outlet_name"] ?? "Unknown";

                // Determine background color
                $backgroundColor = (!in_array($recId, $visitedOutletIds)) ? "#ff8a8a" : "";

                $groupedRoutes[$routeName][] = [
                    "label" => $outletName,
                    "value" => "$recId",
                    "otherDetails" => [
                        "contactNo" => $row["outlet_mobile"] ?? "",
                        "showMapIcon" => true,
                        "lt" => (float)$row["lt"],
                        "lg" => (float)$row["lg"],
                        "backGroundColour" => $backgroundColor
                    ]
                ];
            }

            $masterDataList = [];
            foreach ($groupedRoutes as $route => $outlets) {
                $masterDataList[] = [
                    "label" => $route,
                    "value" => $route,
                    "outletOptions" => $outlets
                ];
            }

            $responseArr = [["masterDataList" => $masterDataList]];
            $response = $this->response->sendResponse(["message" => "", "response" => $responseArr], 1);
            $this->logOutput($response, $this->sExtraLogData);
        } else {
            $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    final public function getAeMasterData()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->getAeMaster();
            } else {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST01"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new GetAeMaster($dbConn, $tableUtil, $commonFunctions);
$stock->getAeMasterData();
$dbConn->Close();
