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
        $previousDate = date("Y-m-d", strtotime("-1 day")); // yesterday's date
        $cond = "AND activity_date = '$previousDate'";

        $saleProductSumExpr = implode(" + ", array_map(function ($i) { return "total_sale_product$i"; }, range(1, 145)));
        $minDailySaleInM = (float) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minDailySaleInM' AND team_type = 0");
        $maxDailySaleInM = (float) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'maxDailySaleInM' AND team_type = 0");

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT summary_id, team_id, activity_date, start_datetime, end_datetime, resp_startdatetime, resp_enddatetime, ($saleProductSumExpr) AS dailySaleInM FROM $summaryTable WHERE dstatus = 0 AND is_update = 0 $cond";
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
                $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"] ? $row["end_datetime"] : $row["resp_enddatetime"], true);
                $totalTime = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], false, false, true);
                $dailySaleInM = (float) $row["dailySaleInM"];
                $saleQualified = ($teamType == 5) || ($dailySaleInM >= $minDailySaleInM && $dailySaleInM <= $maxDailySaleInM);
                $isQualifiedAttendance = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec && $saleQualified ? 1 : 0;
                $timeInMarket = getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);
                $updateValues = "is_update = 1, is_qualified = ?, time_in_market = ?, total_time = ?";
                $condition = "summary_id = ?";
                if ($isQualifiedAttendance == 1) {
                    updateRecord($this->dbConn, $summaryTable, $updateValues, $condition, [$isQualifiedAttendance, $timeInMarket, $totalTime, $summaryId]);
                }
            }
        }

        // Disqualification pass: re-check already-qualified type-0 records in case
        // new transactions pushed the sale out of range after the initial qualification.
        $rsRecheck = null;
        $iRecheckRows = 0;
        $sRecheckQuery = "SELECT summary_id, team_id, ($saleProductSumExpr) AS dailySaleInM FROM $summaryTable WHERE dstatus = 0 AND is_update = 1 AND is_qualified = 1 $cond";
        $this->dbConn->ExecuteSelectQuery($sRecheckQuery, $rsRecheck, $iRecheckRows);
        if ($iRecheckRows > 0) {
            while ($rowR = $this->dbConn->GetData($rsRecheck)) {
                $recheckTeamType = (int) getRowColumn($this->dbConn, "tblproject_team", "is_type", "team_id = " . $rowR["team_id"]);
                if ($recheckTeamType != 5) {
                    $recheckSale = (float) $rowR["dailySaleInM"];
                    if ($recheckSale < $minDailySaleInM || $recheckSale > $maxDailySaleInM) {
                        updateRecord($this->dbConn, $summaryTable, "is_qualified = ?", "summary_id = ?", [0, $rowR["summary_id"]]);
                    }
                }
            }
        }
    }
}

$processResponse = new processUpdateQualifiedMarketTime($dbConn);
$processResponse->processUpdateQualifiedMarketTime();
