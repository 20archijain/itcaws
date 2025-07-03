<?php

// Used in ITC Phase 2 setup to get the Route on map in app

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetMDOSummary extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_mdo_summary_data";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"];
    }

    private function getMdo()
    {
        // global $TBL_ROUTE_DETAILS;

        // $jsondata = file_get_contents("php://input");
        // $jsondata = json_decode($jsondata, true);
        // $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        // $dbName = $this->arrUserDetails["db_name"];

        // $todayDate = date('Y-m-d');
        // $oneMonthAgoDate = date('Y-m-d', strtotime('-1 months'));

        // // Fetch visited outlet rec_ids in last 1 month
        // $visitedOutletIds = $this->tableUtil->getRowsColumn(
        //     "$dbName.tblsurvey_response_details",
        //     "ques_3",
        //     "dstatus = 0 AND ques_0 = 'Outlet Order' AND capture_date BETWEEN '$oneMonthAgoDate' AND '$todayDate' AND team_id = '$teamId'",
        //     [],
        //     true
        // );

        // $sQuery = "SELECT rec_id, route_name, outlet_name, outlet_mobile, lt, lg
        //            FROM $dbName.$TBL_ROUTE_DETAILS
        //            WHERE dstatus = 0 AND team_id = '$teamId'";
        // $sAction = null;
        // $iRows = 0;
        // $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        // $groupedRoutes = [];

        // if ($iRows > 0) {
        //     while ($row = $this->dbConn->GetData($sAction)) {
        //         $routeName = $row["route_name"];
        //         $recId = $row["rec_id"];
        //         $outletName = $row["outlet_name"] ?? "Unknown";

        //         // Determine background color
        //         $backgroundColor = (!in_array($recId, $visitedOutletIds)) ? "#ff8a8a" : "";

        //         $groupedRoutes[$routeName][] = [
        //             "label" => $outletName,
        //             "value" => $recId,
        //             "otherDetails" => [
        //                 "contactNo" => $row["outlet_mobile"] ?? "",
        //                 "showMapIcon" => true,
        //                 "lt" => (float)$row["lt"],
        //                 "lg" => (float)$row["lg"],
        //                 "backGroundColour" => $backgroundColor
        //             ]
        //         ];
        //     }

        $mdoDataList = [];
        //     foreach ($groupedRoutes as $route => $outlets) {
        //         $masterDataList[] = [
        //             "label" => $route,
        //             "value" => $route,
        //             "outletOptions" => $outlets
        //         ];
        //     }

        //     $responseArr = [["masterDataList" => $masterDataList]];
        //     $response = $this->response->sendResponse(["message" => "", "response" => $responseArr], 1);
        //     $this->logOutput($response, $this->sExtraLogData);
        // } else {
        //     $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
        //     $this->logOutput($response, $this->sExtraLogData);
        // }

        $mdoDataList[] = array(
            "label" => "Outlet A",
            "value" => "outlet_a_123",
            "otherDetails" => array(
                "backGroundColour" => "#F5F5F5",
                "htmlText" => "<b>Premium Tier Outlet</b>",
                "outletIdColumn" => "A123",
                "addressColumn" => "123 Market Street, Sector 21",
                "landmarkColumn" => "Near City Mall",
                "contactNo" => "+911234567890",
                "showMapIcon" => true,
                "lt" => 28.6139,
                "lg" => 77.209,
                "datetimeInMilisec" => 1714377600000,
                "listKpiFirst" => array(
                    array("label" => "Total Survey Qty", "value" => "1200"),
                    array("label" => "Avg Survey Qty", "value" => "75"),
                    array("label" => "Avg CFT", "value" => "2.4"),
                    array("label" => "ULC", "value" => "4.1"),
                    array("label" => "Total Visits", "value" => "25")
                ),
                "listKpiSecond" => array(
                    array("label" => "Last DS Visit", "value" => "2025-04-15"),
                    array("label" => "Last Order", "value" => "2025-04-20"),
                    array("label" => "Highest Survey Product", "value" => "Product X"),
                    array("label" => "Lowest Survey Product", "value" => "Product Y"),
                    array("label" => "Focus 1 Last Purchase", "value" => "2025-04-10, 250 units"),
                    array("label" => "Focus 2 Last Purchase", "value" => "2025-04-12, 180 units")
                )
            ),
        );

        // echo "<pre>";
        // print_r($mdoDataList);die;


        $responseArr = [["mdoDataList" => $mdoDataList]];
        $response = $this->response->sendResponse(["message" => "", "response" => $responseArr], 1);
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getMdoData()
    {
        // $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        // if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
        //     $dbName = $this->arrUserDetails["db_name"];
        //     $teamId = $this->arrUserDetails["team_id"];

        //     $this->setLogFileName($this->localLogFileName . "_$dbName");

        //     $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

        // if ($this->validateData()) {
        $this->getMdo();
        // } else {
        //     $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST01"]]);
        //     $this->logOutput($response, $this->sExtraLogData);
        // }
        // }
    }
}

$stock = new GetMDOSummary($dbConn, $tableUtil, $commonFunctions);
$stock->getMdoData();
$dbConn->Close();
