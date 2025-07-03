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

        $minTotalShops =  (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
        $minQualifiedAttendanceTimeInMin =  (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
        $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT summary_id, team_id, activity_date, start_datetime, end_datetime, resp_startdatetime, resp_enddatetime FROM $summaryTable WHERE dstatus = 0 $cond LIMIT 500";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $summaryId = $row["summary_id"];
                $date = $row["activity_date"];
                $teamId = $row["team_id"];
                $orderShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $addShop = getRowColumn($this->dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                $totalShops = $orderShop + $addShop;
                $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
                $isQualifiedAttendance = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? 1 : 0;
                $timeInMarket = getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);
                $updateValues = "is_update = 1, is_qualified = ?, time_in_market = ?";
                $condition = "summary_id = ?";
                updateRecord($this->dbConn, $summaryTable, $updateValues, $condition, [$isQualifiedAttendance, $timeInMarket, $summaryId]);
            }
        }
    }
}

$processResponse = new processUpdateQualifiedMarketTime($dbConn);
$processResponse->processUpdateQualifiedMarketTime();
