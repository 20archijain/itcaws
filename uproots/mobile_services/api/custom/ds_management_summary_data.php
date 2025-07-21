<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Used in ITC Phase 2 setup to get the Route on map in app

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class DsManagementSummary extends Utilities
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

    private function getDsData()
    {
        global $TBL_PROJECT_TEAM, $TBL_VANDS_SUMMARY, $TBL_ATTENDANCE, $TBL_SURVEY_RESPONSE, $TBL_BRANCH_PICKUPSTOCK_PRODUCTS, $TBL_ROUTE_DETAILS;

        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        $dbName = $this->arrUserDetails["db_name"];

        if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
            $this->requestGetData = $_GET;

            if (empty($this->requestGetData['wdlist'])) {
                $response = $this->response->sendResponse(["message" => "WD Code is required"]);
                $this->logOutput($response, $this->sExtraLogData);
                return;
            }
        }

        $wdcodesFilterConditions = "";
        //wdlist
        $selectedWdCodes = isset($this->requestGetData['wdlist']) ? $this->requestGetData['wdlist'] : null;
        if (!empty($selectedWdCodes)) {
            $wdcodes = is_array($selectedWdCodes) ? $selectedWdCodes : explode(',', $selectedWdCodes);
            $wdcodesList = "'" . implode("','", $wdcodes) . "'";
            $wdcodesFilterConditions = " AND wd_code IN ($wdcodesList)";
        }

        $monthCond = "";
        $monthCond1 = "";
        $currentYear = date('Y');
        $currentMonth = date('m');
        $yearMonth = "$currentYear-$currentMonth";
        $monthCond .= " AND capture_date LIKE '%$yearMonth%'";
        $monthCond1 .= " AND activity_date LIKE '%$yearMonth%'";

        // Fetch all teams corresponding the WD CODE
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT team_id, branch_id, team_name, is_type, ds_number FROM $dbName.$TBL_PROJECT_TEAM WHERE dstatus = 0 AND s_id = 99 $wdcodesFilterConditions";
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        $summaryDataList  = [];
        $type  = array(0 => "Van DS", 1 => "Niches", 2 => "Town Swd", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR", 6 => "RMD");
        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                $dsId = $row["team_id"];
                $branchId = $row["branch_id"];
                $dsName = $row["team_name"];
                $dsType = $row["is_type"];
                $dsNumber = $row["ds_number"];

                $daysPresent = $this->tableUtil->getRowColumn("$dbName.$TBL_ATTENDANCE", "COUNT(att_id) AS present", "dstatus = 0 AND team_id = $dsId AND call_type = '0' $monthCond");
                $adherence = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "COUNT(summary_id) AS adhered", "dstatus = 0 AND team_id = $dsId AND is_beat_adherence = 'Yes' $monthCond1");
                $qualified = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "COUNT(is_qualified)", "dstatus = 0 AND team_id = $dsId AND is_qualified = 1 $monthCond1");
                $startTime = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "DATE_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(start_datetime)))), '%H:%i') AS avg_time", "dstatus = 0 AND team_id = $dsId $monthCond1");
                $fistOutletTime = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "DATE_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(resp_startdatetime)))), '%H:%i') AS avg_time", "dstatus = 0 AND team_id = $dsId $monthCond1");
                $lastOutletTime = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "DATE_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(resp_enddatetime)))), '%H:%i') AS avg_time", "dstatus = 0 AND team_id = $dsId $monthCond1");
                $dayendTime = $this->tableUtil->getRowColumn("$dbName.$TBL_VANDS_SUMMARY", "DATE_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(dayend_datetime)))), '%H:%i') AS avg_time", "dstatus = 0 AND team_id = $dsId $monthCond1");

                $avgStartTime = $startTime;
                $avgFirstOutletTime = $fistOutletTime;
                $avgLastOutletTime = $lastOutletTime;
                $avgDayEndTime = $dayendTime;

                $timeSpent = $this->tableUtil->getRowsColumns("$dbName.$TBL_VANDS_SUMMARY", "start_datetime, dayend_datetime", "dstatus = 0 AND team_id = $dsId $monthCond1");
                $avgTimes = $this->commonFunctions->getAverageDatetimeRange($timeSpent);
                $totalTimeSpent = $this->commonFunctions->getTimeDifference($avgTimes['avgStartDatetime'], $avgTimes['avgEndDatetime']);

                $timeSpentInMarket = $this->tableUtil->getRowsColumns("$dbName.$TBL_VANDS_SUMMARY", "resp_startdatetime, resp_enddatetime", "dstatus = 0 AND team_id = $dsId $monthCond1");
                $avgTimesInMarkt = $this->commonFunctions->getAverageDatetimeRange($timeSpentInMarket);
                $totalTimeInMarket = $this->commonFunctions->getTimeDifference($avgTimesInMarkt['avgStartDatetime'], $avgTimesInMarkt['avgEndDatetime']);
                $callTime = $this->tableUtil->getRowsColumn("$dbName.$TBL_SURVEY_RESPONSE", "call_time", "dstatus = 0 AND team_id = $dsId $monthCond");
                $cftPerOutlet = 0;

                if (!empty($callTime)) {
                    $totalMilliseconds = array_sum($callTime);
                    $outlets = count($callTime); // Each record = 1 outlet

                    $averageMilliseconds = $totalMilliseconds / $outlets;
                    $avgSec = $averageMilliseconds / 1000; // Convert to seconds

                    // Cast explicitly to int to avoid warnings
                    $minutes = intdiv((int)$avgSec, 60);
                    $seconds = (int)$avgSec % 60;

                    $cftPerOutlet = "{$minutes}m {$seconds}s";
                }

                $callTimePerDay = $this->tableUtil->getRowsColumn("$dbName.$TBL_SURVEY_RESPONSE", "call_time", "dstatus = 0 AND team_id = $dsId $monthCond GROUP BY capture_date");
                $cftPerDay = 0;
                if (!empty($callTimePerDay)) {
                    $totalMillisecondsPerDay = array_sum($callTimePerDay);
                    $totalDays = count($callTimePerDay); // Each record = 1 Day

                    $averageMillisecondsPerDay = $totalMillisecondsPerDay / $totalDays;
                    $avgSecPerDay = $averageMillisecondsPerDay / 1000; // Convert to seconds

                    $minutesPerDay = intdiv((int)$avgSecPerDay, 60);
                    $secondsPerDay = (int)$avgSecPerDay % 60;

                    $cftPerDay = "{$minutesPerDay}m {$secondsPerDay}s";
                }

                $sAction1 = null;
                $iRows1 = 0;
                $sQuery1 = "SELECT DISTINCT summary_column_name, product_name FROM $dbName.$TBL_BRANCH_PICKUPSTOCK_PRODUCTS WHERE dstatus = 0 AND branch_id = $branchId";
                $this->dbConn->ExecuteSelectQuery($sQuery1, $sAction1, $iRows1);
                $productCol = array();
                $productColName = array();
                if ($iRows1 > 0) {
                    while ($row1 = $this->dbConn->GetData($sAction1)) {
                        $productCol[] = $row1['summary_column_name'];
                        $productColName[$row1['summary_column_name']] = $row1['product_name'];
                    }
                }
                // Prepare SUM columns
                $summaryColumns = implode(") + SUM(", $productCol);
                $sumColumns = "SUM($summaryColumns)";
                // Process current month data
                $sAction2 = null;
                $iRows2 = 0;
                $sQuery2 = "SELECT COUNT(*) AS total, $sumColumns AS totalSum FROM $dbName.$TBL_VANDS_SUMMARY WHERE dstatus = 0 AND team_id = $dsId $monthCond1";
                $this->dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
                $mtdTotalSale = 0;
                $count = 0;
                $avgSurvey = 0;
                if ($iRows2 > 0) {
                    while ($row2 = $this->dbConn->GetData($sAction2)) {
                        $mtdTotalSale += (float)$row2['totalSum'];
                        $count = (int)$row2['total'];
                    }
                    if ($count > 0) {
                        $avgSurvey = round($mtdTotalSale / $count, 2);
                    }
                }

                $totalOutlets = $this->tableUtil->getRowColumn("$dbName.$TBL_ROUTE_DETAILS", "COUNT(rec_id)", "dstatus = 0 AND team_id = $dsId");
                $outlets = $this->tableUtil->getRowColumns("$dbName.$TBL_SURVEY_RESPONSE", "COUNT(DISTINCT capture_date), COUNT(DISTINCT ques_3)", "dstatus = 0 AND team_id = $dsId $monthCond");
                $visitedPerDay = 0;

                if (isset($outlets[0], $outlets[1]) && $outlets[0] > 0) {
                    $visitedPerDay = round($outlets[1] / $outlets[0], 0);
                }

                $productiveOutlet = $this->tableUtil->getRowColumns("$dbName.$TBL_SURVEY_RESPONSE", "COUNT(DISTINCT capture_date), COUNT(DISTINCT ques_3), $sumColumns AS total_sales", "dstatus = 0 AND team_id = $dsId $monthCond HAVING total_sales > 0");
                if (isset($productiveOutlet[0], $productiveOutlet[1]) && $productiveOutlet[0] > 0) {
                    $productivePerDay = round($productiveOutlet[1] / $productiveOutlet[0], 0);
                }

                $visitedOutlets = $this->tableUtil->getRowColumn("$dbName.$TBL_SURVEY_RESPONSE", "COUNT(DISTINCT ques_3)", "dstatus = 0 AND team_id = $dsId $monthCond");
                $prductiveOutlets = $this->tableUtil->getRowColumns("$dbName.$TBL_SURVEY_RESPONSE", "COUNT(DISTINCT ques_3), $sumColumns AS total_sales", "dstatus = 0 AND team_id = $dsId $monthCond HAVING total_sales > 0");

                $sAction3 = null;
                $iRows3 = 0;
                $sQuery3 = "SELECT COUNT(*) AS total, $sumColumns AS totalSum FROM $dbName.$TBL_SURVEY_RESPONSE WHERE dstatus = 0 AND team_id = $dsId $monthCond";
                $this->dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

                $totalSale = 0;
                $count1 = 0;
                $avgSurveyPerOutlet = 0;

                if ($iRows3 > 0) {
                    while ($row3 = $this->dbConn->GetData($sAction3)) {
                        $totalSale += (float)$row3['totalSum'];  // explicitly cast to float
                        $count1 = (int)$row3['total'];           // cast to int just to be safe
                    }

                    if ($count1 > 0) {
                        $avgSurveyPerOutlet = round($totalSale / $count1, 2); // round to 2 decimals
                    }
                }

                $maxAvg = 0;
                $topProduct = '';
                $highestAvgValue = 0;

                foreach ($productColName as $colName => $productName) {
                    $sAction4 = null;
                    $iRows4 = 0;
                    $sQuery4 = "SELECT COUNT(*) AS total, SUM($colName) AS totalSum FROM $dbName.$TBL_SURVEY_RESPONSE WHERE dstatus = 0 AND team_id = $dsId $monthCond";
                    $this->dbConn->ExecuteSelectQuery($sQuery4, $sAction4, $iRows4);
                    if ($iRows4 > 0) {
                        while ($row4 = $this->dbConn->GetData($sAction4)) {
                            $total = (int)$row4['total'];
                            $sum = (float)$row4['totalSum'];

                            if ($total > 0) {
                                $avg = $sum / $total;
                                if ($avg > $maxAvg) {
                                    $maxAvg = $avg;
                                    $topProduct = $productName;
                                    $highestAvgValue = round($avg, 2);
                                }
                            }
                        }
                    }
                }

                $productColumns = implode(", ", $productCol);
                $query5 = "SELECT $productColumns FROM $dbName.$TBL_SURVEY_RESPONSE WHERE dstatus = 0 AND team_id = $dsId $monthCond";

                $sAction5 = null;
                $iRows5 = 0;
                $this->dbConn->ExecuteSelectQuery($query5, $sAction5, $iRows5);

                $totalCount = 0;
                $totalRecords = 0;

                if ($iRows5 > 0) {
                    while ($row5 = $this->dbConn->GetData($sAction5)) {
                        $count = 0;

                        foreach ($productCol as $col) {
                            $value = (float)($row5[$col] ?? 0);
                            if ($value > 0) {
                                $count++;
                            }
                        }

                        $totalCount += $count;
                        $totalRecords++;
                    }
                }

                $avgOfAllRecords = $totalRecords > 0 ? round($totalCount / $totalRecords, 2) : 0;

                $summaryDataList[] = [
                    "dsid" => isset($dsId) ? (string)$dsId : '',
                    "dsname" => isset($dsName) ? $dsName : '',
                    "dsnumber" => isset($dsNumber) ? $dsNumber : '',
                    "dstype" => isset($type[$dsType]) ? $type[$dsType] : '',
                    "card_data" => [
                        ["key" => "Days Present", "value" => isset($daysPresent) ? (string)$daysPresent : ''],
                        ["key" => "Days qualified", "value" => isset($qualified) ? (string)$qualified : ''],
                        ["key" => "Route Adherence", "value" => isset($adherence) ? (string)$adherence : ''],
                        ["key" => "Day Start", "value" => isset($avgStartTime) ? (string)$avgStartTime : ''],
                        ["key" => "First OL", "value" => isset($avgFirstOutletTime) ? (string)$avgFirstOutletTime : ''],
                        ["key" => "Last OL", "value" => isset($avgLastOutletTime) ? (string)$avgLastOutletTime : ''],
                        ["key" => "Day End", "value" => isset($avgDayEndTime) ? (string)$avgDayEndTime : ''],
                        ["key" => "Total Time Spent(MTD Average)", "value" => isset($totalTimeSpent) ? (string)$totalTimeSpent : ''],
                        ["key" => "Time in Market (MTD Average)", "value" => isset($totalTimeInMarket) ? (string)$totalTimeInMarket : ''],
                        ["key" => "CFT Per Day (MTD Average)", "value" => isset($cftPerDay) ? (string)$cftPerDay : ''],
                        ["key" => "CFT Per Outlet (MTD Average)", "value" => isset($cftPerOutlet) ? (string)$cftPerOutlet : ''],
                        ["key" => "Survey Per Day (MTD Average)", "value" => (isset($avgSurvey) ? (string)$avgSurvey : '') . " M"],
                        ["key" => "Survey (MTD Total)", "value" => (isset($mtdTotalSale) ? (string)$mtdTotalSale : '') . " M"],
                        ["key" => "Highest Survey Product", "value" => (isset($topProduct) ? (string)$topProduct : '') . " - " . (isset($highestAvgValue) ? $highestAvgValue : '') . " M"],
                        ["key" => "Daily Visited Outlets (MTD Average)", "value" => isset($visitedPerDay) ? (string)$visitedPerDay : ''],
                        ["key" => "Daily Productive Outlets (MTD Average)", "value" => isset($productivePerDay) ? (string)$productivePerDay : ''],
                        ["key" => "Survey Per Outlet (MTD Average)", "value" => (isset($avgSurveyPerOutlet) ? (string)$avgSurveyPerOutlet : '') . " M"],
                        ["key" => "ULC Per Outlet (MTD Average)", "value" => isset($avgOfAllRecords) ? (string)$avgOfAllRecords : ''],
                        ["key" => "Total Outlets", "value" => isset($totalOutlets) ? (string)$totalOutlets : ''],
                        ["key" => "Unique Visited Outlets", "value" => isset($visitedOutlets) ? (string)$visitedOutlets : ''],
                        ["key" => "Unique Productive Outlets", "value" => (isset($prductiveOutlets[0]) ? (string)$prductiveOutlets[0] : '')],
                    ]
                ];
            }

            $responseArr = $summaryDataList;
            $response = $this->response->sendResponse(["message" => "", "response" => $responseArr], 1);
            $this->logOutput($response, $this->sExtraLogData);
        } else {
            $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    final public function getDsManagementData()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->getDsData();
            } else {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST01"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new DsManagementSummary($dbConn, $tableUtil, $commonFunctions);
$stock->getDsManagementData();
$dbConn->Close();
