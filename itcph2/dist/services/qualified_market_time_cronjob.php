<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class processUpdateQualifiedMarketTime
{
    private $dbConn = null;
    private $tables = [];

    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
        $this->tables = $GLOBALS['TABLES'];
    }

    final public function processUpdateQualifiedMarketTime()
    {
        $constantsTable = $this->tables["CONSTANTS_TABLE"];
        $summaryTable = $this->tables["VANDS_SUMMARY_TABLE"];
        $respTable = $this->tables["RESPONSE_DETAILS_TABLE"];
        $currentDate = currentDate();
        $cond = "AND activity_date = '$currentDate'";

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT summary_id, team_id, activity_date, start_datetime, end_datetime, resp_startdatetime, resp_enddatetime FROM $summaryTable WHERE dstatus = 0 AND is_update = 0 $cond";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $summaryId = $row["summary_id"];
                $date = $row["activity_date"];
                $teamId = $row["team_id"];
                $teamType = (int) getRowColumn($this->dbConn, "tblproject_team", "is_type", "team_id = $teamId");
                $teamTypeCondition = ($teamType == 5) ? "AND team_type = 5" : "AND team_type = 0";
                $minTotalShops =  (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops' $teamTypeCondition");
                $minQualifiedAttendanceTimeInMin =  (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin' $teamTypeCondition");
                $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;
                $orderShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $addShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $totalShops = $orderShop + $addShop;
                $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
                $totalTime = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], false, false, true);
                $isQualifiedAttendance = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? 1 : 0;
                $timeInMarket = getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);
                $updateValues = "is_update = 1, is_qualified = ?, time_in_market = ?, total_time = ?";
                $condition = "summary_id = ?";
                if ($isQualifiedAttendance == 1) {
                    updateRecord($this->dbConn, $summaryTable, $updateValues, $condition, [$isQualifiedAttendance, $timeInMarket, $totalTime, $summaryId]);
                }
            }
        }
    }

    final public function processUpdateOutletCount()
    {
        $summaryTable = $this->tables["VANDS_SUMMARY_TABLE"];
        $respTable = $this->tables["RESPONSE_DETAILS_TABLE"];
        $routeTable = $this->tables["ROUTE_DETAILS_TABLE"];
        $projectTeamTable = $this->tables["PROJECT_TEAM_TABLE"];
        $branchPickupStockTable = $this->tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $currentDate = currentDate();
        $cond = "AND activity_date = '$currentDate'";

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT summary_id, team_id, activity_date, route, is_route_updated FROM $summaryTable WHERE dstatus = 0 $cond";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $summaryId = $row["summary_id"];
                $date = $row["activity_date"];
                $dayOfWeek = date('D', strtotime($date));
                $teamId = $row["team_id"];
                $routeName = $row["route"];
                $updated = $row["is_route_updated"];
                $callTime = getRowsColumn($this->dbConn, $respTable, "call_time", "ques_0 IN ('Outlet Order', 'Add Outlet') AND dstatus = '0' AND capture_date = '$date' AND team_id = '$teamId'");
                $totalTimeSpent = "";
                $totalMinutes = 0;
                $time = 0;
                if (!empty($callTime)) {
                    $totalTime = array_sum($callTime); // Sum all time values
                    $time = $totalTime / 1000;
                    // Convert time to H:i:s format
                    $totalTimeSpent = gmdate("H:i:s", (int) round($time));
                    $totalMinutes = floor($time / 60);
                }
                $orderShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $addShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $totalShops = $orderShop + $addShop;
                $branchId = getRowColumn($this->dbConn, $projectTeamTable, "branch_id", "team_id = $teamId");
                if ($branchId == 40) {
                    $table = "tblroute_details_delhi";
                } else {
                    $table = $routeTable;
                }
                $allBrandCols = getRowsColumns($this->dbConn, $branchPickupStockTable, "summary_column_name, product_name", "dstatus = 0 AND branch_id = $branchId", array(), true);
                $productCols = [];
                $productNames = [];

                foreach ($allBrandCols as $colRow) {
                    $productCols[] = $colRow[0];
                    $productNames[] = $colRow[1];
                }
                $summaryColumns = implode(") + SUM(", $productCols);
                $sumColumns = "SUM($summaryColumns)";
                // $sellInShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "dstatus = '0' AND capture_date = '$date' AND team_id = $teamId HAVING $sumColumns > 0");
                $sellInShop = getRowColumn($this->dbConn, "(SELECT ques_3 FROM $respTable WHERE team_id = $teamId and capture_date = '$date' GROUP BY ques_3 HAVING $sumColumns > 0 ) AS t", "COUNT(*) AS total_customers");
                // "(SELECT ques_2 FROM tblsurvey_response_details_kunal WHERE team_id = $teamId and capture_date = '$date' GROUP BY ques_2 HAVING $sumColumns > 0 ) AS t", "COUNT(*) AS total_customers"
                $idealRoute = getRowColumn($this->dbConn, $table, "route_name", "dstatus = '0' AND beat_day = '$dayOfWeek' AND team_id = '$teamId'");
                $beatDay = getRowColumn($this->dbConn, $table, "beat_day", "dstatus = '0' AND route_name = '$routeName' AND team_id = $teamId");
                $routeNameLower = strtolower($routeName);
                $beatDayLower   = strtolower($beatDay);
                if (strpos($routeNameLower, $beatDayLower) !== false) {
                    $showDay = $beatDay;   // Beat day exists inside the route name
                } else {
                    $showDay = $dayOfWeek; // Fallback to current day of week
                }
                if ($updated == 0) {
                    $updateValues = "ideal_route = ?, route_day = ?, uni_total_sales_deliveries = ?, uni_total_other_shops = ?, uni_total_sellin_shops = ?, uni_total_shops = ?, total_cft = ?, cft_time_sec = ?, is_route_updated = ?";
                    $condition = "summary_id = ?";
                    updateRecord($this->dbConn, $summaryTable, $updateValues, $condition, [$idealRoute, $showDay, $orderShop, $addShop, $sellInShop ?? 0, $totalShops, round($totalMinutes, 2), $time, 1, $summaryId]);
                } else {
                    $updateValues = "route_day = ?, uni_total_sales_deliveries = ?, uni_total_other_shops = ?, uni_total_sellin_shops = ?, uni_total_shops = ?, total_cft = ?, cft_time_sec = ?";
                    $condition = "summary_id = ?";
                    updateRecord($this->dbConn, $summaryTable, $updateValues, $condition, [$showDay, $orderShop, $addShop, $sellInShop ?? 0, $totalShops, round($totalMinutes, 2), $time, $summaryId]);
                }
            }
        }
    }
}

$processResponse = new processUpdateQualifiedMarketTime($dbConn);
$processResponse->processUpdateQualifiedMarketTime();
$processResponse->processUpdateOutletCount();
