<?php

// Get 2 months (previous month and mtd) attendance status to plot on a calendar in app

// Statuses
// 0 = Pending for update (if date is in future)
// 1 = Present and Qualified
// 2 = Present but Unqualified
// 3 = Absent

// phpcs:ignore
class StoreCalendarDataAndSummary
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $bUpdateIfExists = true;
    private $cloudTable = null;
    private $mobileCalendarDataTable = null;
    private $mobileCalendarSummaryTable = null;
    private $lastMonthYear = null;
    private $lastMonthNo = null;
    private $lastMonthLastDay = null;
    private $currentMonthYear = null;
    private $currentMonthNo = null;
    private $currentMonthLastDay = null;
    private $minTotalShops = 0;
    private $minQualifiedAttendanceTimeInSec = 0;
    private $currentDate = null;
    private $currentDatetime = null;
    private $previousDayDate = null;
    private $arrStatus = array(
        "PENDING" => 0,
        "QUALIFIED" => 1,
        "UNQUALIFIED" => 2,
        "ABSENT" => 3,
    );
    private $defaultSummary = array(
        "calendar_heading" => "Attendance",
        "calendar_legends" => array(
            array(
                "status" => 1,
                "bg_color" => "#a0eaa2",
                "text_color" => "#000000",
                "text" => "Qualified",
                "hide" => false
            ),
            array(
                "status" => 2,
                "bg_color" => "#ffed6e",
                "text_color" => "#000000",
                "text" => "Unqualified",
                "hide" => false
            ),
            array(
                "status" => 3,
                "bg_color" => "#fd9d97",
                "text_color" => "#000000",
                "text" => "Absent",
                "hide" => false
            ),
            array(
                "status" => 0,
                "bg_color" => "#e4e1e1",
                "text_color" => "#000000",
                "text" => "Pending",
                "hide" => false
            ),
        ),
        "monthwise_calendar_data" => array(),
    );
    private $summary = array();

    public function __construct($dbConn, $tableUtil, $commonFunctions, $bUpdateIfExists = true)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->bUpdateIfExists = $bUpdateIfExists;
        $this->cloudTable = $GLOBALS["TBL_CLOUD_AUTH_PIN"];
        $this->mobileCalendarDataTable = $GLOBALS["TBL_MOBILE_CALENDAR_DATA"];
        $this->mobileCalendarSummaryTable = $GLOBALS["TBL_MOBILE_CALENDAR_SUMMARY"];
        $this->lastMonthYear = date("Y", strtotime("first day of previous month"));
        $this->lastMonthNo = date("n", strtotime("first day of previous month"));
        $this->lastMonthLastDay = date("j", strtotime("last day of previous month"));
        $this->currentMonthYear = date("Y", strtotime("first day of this month"));
        $this->currentMonthNo = date("n", strtotime("first day of this month"));
        $this->currentMonthLastDay = date("j", strtotime("last day of this month"));
        $this->currentDate = $this->commonFunctions->currentDate();
        $this->currentDatetime = $this->commonFunctions->currentDateTime();
        $this->previousDayDate = date("Y-m-d", strtotime("-1 day"));
    }

    private function resetSummary()
    {
        $this->summary = $this->defaultSummary;
    }

    private function clearMobileCalendarSummaryTable($dbName)
    {
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT mcs_id FROM $dbName.{$this->mobileCalendarSummaryTable} WHERE dstatus = 0" .
            " AND activity_date = '{$this->previousDayDate}'";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $rsTruncateAction = null;
            $iTruncateActionRows = 0;
            $sTruncateQuery = "TRUNCATE $dbName.{$this->mobileCalendarSummaryTable}";
            $this->dbConn->ExecuteQuery($sTruncateQuery, $rsTruncateAction, $iTruncateActionRows);
        }
    }

    private function storeTeamCalendarData($dbName, $clientId, $projectId, $teamId)
    {
        // Check if record exists or not, if not, then add else update
        $mcdId = $this->tableUtil->getRowColumn(
            "$dbName.{$this->mobileCalendarDataTable}",
            "mcd_id",
            "dstatus = 0 AND client_id = ? AND project_id = ? AND team_id = ? AND year = ? AND month = ?",
            array($clientId, $projectId, $teamId, $this->currentMonthYear, $this->currentMonthNo)
        );

        $currentDay = date("j", strtotime($this->currentDate));

        // Not exist, so add
        if (!$mcdId) {
            // get Qualified attendance status for 1st day
            $iStatus = $this->getQualifiedAttendanceStatus(
                $dbName,
                $teamId,
                "{$this->currentMonthYear}-" .
                    ($this->currentMonthNo < 10 ? "0{$this->currentMonthNo}" : $this->currentMonthNo) .
                    "-01"
            );

            // Add in table
            $this->tableUtil->addRecord(
                "$dbName.{$this->mobileCalendarDataTable}",
                "client_id, project_id, team_id, year, month, day_1, rcd, rdt",
                "?, ?, ?, ?, ?, ?, ?, ?",
                array(
                    $clientId,
                    $projectId,
                    $teamId,
                    $this->currentMonthYear,
                    $this->currentMonthNo,
                    $iStatus,
                    $this->currentDate,
                    $this->currentDatetime
                )
            );
            $mcdId = 0;
            $this->dbConn->GetLastInsertId($mcdId);

            // Loop from 2nd to today's date to update status of new team if created after 1st
            if ($currentDay > 1) {
                for ($i = 2; $i <= $currentDay; $i++) {
                    // get Qualified attendance status
                    $iStatus = $this->getQualifiedAttendanceStatus(
                        $dbName,
                        $teamId,
                        "{$this->currentMonthYear}-" .
                            ($this->currentMonthNo < 10 ? "0{$this->currentMonthNo}" : $this->currentMonthNo) .
                            "-" . ($i < 10 ? "0$i" : $i)
                    );

                    // Update in table
                    $this->tableUtil->updateRecord(
                        "$dbName.{$this->mobileCalendarDataTable}",
                        "day_$i = ?",
                        "mcd_id = ?",
                        array($iStatus, $mcdId)
                    );
                }
            }
        } elseif ($mcdId && $this->bUpdateIfExists) {
            // exists so update existing record

            // get today's Qualified attendance status
            $iStatus = $this->getQualifiedAttendanceStatus($dbName, $teamId, $this->currentDate);

            // Update in table
            $this->tableUtil->updateRecord(
                "$dbName.{$this->mobileCalendarDataTable}",
                "day_$currentDay = ?",
                "mcd_id = ?",
                array($iStatus, $mcdId)
            );
        }
    }

    private function getQualifiedAttendanceStatus($dbName, $teamId, $date)
    {
        if ($dbName === $GLOBALS["DELHI_DB"] || $dbName === $GLOBALS["ITC_DB"] || $dbName === $GLOBALS["SOUTH_DB"] || $dbName === $GLOBALS["ITCPH2_DB"]) {
            $summaryTable = $GLOBALS["TBL_VANDS_SUMMARY"];

            if (!$this->minTotalShops) {
                $this->minTotalShops = $this->tableUtil->getRowColumn(
                    "$dbName.{$GLOBALS["TBL_CONSTANTS"]}",
                    "con_value",
                    "dstatus = 0 AND con_name = 'minTotalShops'"
                );
                $minQualifiedAttendanceTimeInMin = $this->tableUtil->getRowColumn(
                    "$dbName.{$GLOBALS["TBL_CONSTANTS"]}",
                    "con_value",
                    "dstatus = 0 AND con_name = 'minWorkingTimeInMin'"
                );
                $this->minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;
            }

            if ($dbName === $GLOBALS["DELHI_DB"]) {
                $rocDeliveryColumn = "total_sellin_shops";
                $otherDeliveryColumn = "total_other_shops";
            } elseif ($dbName === $GLOBALS["ITC_DB"]) {
                $rocDeliveryColumn = "total_roc_deliveries";
                $otherDeliveryColumn = "total_other_shops";
            } elseif ($dbName === $GLOBALS["SOUTH_DB"]) {
                $rocDeliveryColumn = "total_deliveries";
                $otherDeliveryColumn = "";
            } elseif ($dbName === $GLOBALS["ITCPH2_DB"]) {
                $rocDeliveryColumn = "total_sales_deliveries";
                $otherDeliveryColumn = "total_other_shops";
            }

            $arrSummaryData = $this->tableUtil->getRowColumns(
                "$dbName.$summaryTable",
                "start_datetime, end_datetime, SUM($rocDeliveryColumn) AS deliveries_1" .
                    ($otherDeliveryColumn ? ", SUM($otherDeliveryColumn) AS deliveries_2" : ""),
                "dstatus = 0 AND team_id = ? AND activity_date = ?",
                array($teamId, $date)
            );

            // Present
            if ($arrSummaryData && isset($arrSummaryData, $arrSummaryData[0]) && $arrSummaryData[0]) {
                $startDatetime = $arrSummaryData[0];
                $endDatetime = $arrSummaryData[1];
                $totalROCShops = $arrSummaryData[2];
                $totalOtherShops = isset($arrSummaryData[3]) ? $arrSummaryData[3] : 0;
                $totalShops = $totalROCShops + $totalOtherShops;

                $timeSpentInSec = $this->commonFunctions->getTimeDifference($startDatetime, $endDatetime, true);
                $isQualifiedAttendance = $totalShops >= $this->minTotalShops &&
                    $timeSpentInSec >= $this->minQualifiedAttendanceTimeInSec ?
                    $this->arrStatus["QUALIFIED"] : $this->arrStatus["UNQUALIFIED"];

                return $isQualifiedAttendance;
            } else {
                // Absent
                return $this->arrStatus["ABSENT"];
            }
        }
    }

    private function storeTeamCalendarSummary($dbName, $clientId, $projectId, $teamId)
    {
        // Check if record exists or not, if not, then add else update
        $mcsId = $this->tableUtil->getRowColumn(
            "$dbName.{$this->mobileCalendarSummaryTable}",
            "mcs_id",
            "dstatus = 0 AND client_id = ? AND project_id = ? AND team_id = ? AND activity_date = ?",
            array($clientId, $projectId, $teamId, $this->currentDate)
        );

        // Not exist, so add
        if (!$mcsId) {
            // get qualified attendance status
            $arrSummary = $this->getTeamCalendarSummary($dbName, $teamId, $this->currentDate);

            // Add in table
            $this->tableUtil->addRecord(
                "$dbName.{$this->mobileCalendarSummaryTable}",
                "client_id, project_id, team_id, summary, activity_date, activity_datetime",
                "?, ?, ?, ?, ?, ?",
                array(
                    $clientId,
                    $projectId,
                    $teamId,
                    json_encode($arrSummary),
                    $this->currentDate,
                    $this->currentDatetime
                )
            );
        } elseif ($mcsId && $this->bUpdateIfExists) {
            // exists so update existing record

            // get qualified attendance status
            $arrSummary = $this->getTeamCalendarSummary($dbName, $teamId, $this->currentDate);

            // Update in table
            $this->tableUtil->updateRecord(
                "$dbName.{$this->mobileCalendarSummaryTable}",
                "summary = ?",
                "mcs_id = ?",
                array(json_encode($arrSummary), $mcsId)
            );
        }
    }

    private function getTeamCalendarSummary($dbName, $teamId)
    {
        $this->resetSummary();

        $arrLastMonthData = $arrCurrentMonthData = $arrDateColumns = array();
        for ($i = 1; $i <= 31; $i++) {
            $arrDateColumns[] = "day_$i";
        }
        $sDateColumns = implode(", ", $arrDateColumns);

        // Get previous month and current month data
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT month, $sDateColumns FROM $dbName.{$this->mobileCalendarDataTable} WHERE dstatus = 0" .
            " AND team_id = ? AND ((year = ? AND month = ?) OR (year = ? AND month = ?))";
        $this->dbConn->ExecuteSelectQuery(
            $sQuery,
            $rsAction,
            $iActionRows,
            array($teamId, $this->lastMonthYear, $this->lastMonthNo, $this->currentMonthYear, $this->currentMonthNo)
        );

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                // Last month
                if ($row["month"] == $this->lastMonthNo) {
                    $arrLastMonthData = $row;
                } else {
                    // Current month
                    $arrCurrentMonthData = $row;
                }
            }
        }

        // Add summary for previous month
        $this->addDailyStatus($arrLastMonthData, $this->lastMonthYear, $this->lastMonthNo, $this->lastMonthLastDay, $dbName, $teamId, false);

        // Add summary for current month
        $this->addDailyStatus(
            $arrCurrentMonthData,
            $this->currentMonthYear,
            $this->currentMonthNo,
            $this->currentMonthLastDay,
            $dbName,
            $teamId,
            true
        );

        return $this->summary;
    }

    private function addDailyStatus($arrMonthData, $year, $monthNo, $totalDays, $dbName, $teamId, $isCurrentMonth = false)
    {
        $arrCalendarData = array();
        for ($i = 1; $i <= $totalDays; $i++) {
            $date = "$year-" . ($monthNo < 10 ? "0$monthNo" : $monthNo) . "-" . ($i < 10 ? "0$i" : $i);

            if ($isCurrentMonth) {
                if ($date > $this->currentDate) {
                    $iStatus = $this->arrStatus["PENDING"];
                } else {
                    $iStatus = isset($arrMonthData["day_$i"]) ? $arrMonthData["day_$i"] : $this->arrStatus["ABSENT"];
                }
            } else {
                $iStatus = isset($arrMonthData["day_$i"]) ? $arrMonthData["day_$i"] : $this->arrStatus["ABSENT"];
            }

            // Build the day data
            $dayData = array(
                "date" => $date,
                "day" => date("l", strtotime($date)),
                "status" => $iStatus,
            );

            if ($dbName === $GLOBALS["ITCPH2_DB"]) {
                $SummaryDetails = $this->tableUtil->getRowColumns(
                    "$dbName.tblmobile_calendar_summary_keydetails",
                    "planned_outlets, oulet_covered_today, add_oulet_covered_today, sell_in_shops_count_today, other_sell_in_shops_count_today, total_sales_today, time_spent_today, total_meter_travelled",
                    "team_id = $teamId AND rcd = '$date'"
                );
                $coveredOutlet = (isset($SummaryDetails[1]) ? $SummaryDetails[1] : 0) +
                    (isset($SummaryDetails[2]) ? $SummaryDetails[2] : 0);
                $outletPlanned = isset($SummaryDetails[0]) ? $SummaryDetails[0] : 0;
                $sellInOutlet = (isset($SummaryDetails[3]) ? $SummaryDetails[3] : 0) +
                    (isset($SummaryDetails[4]) ? $SummaryDetails[4] : 0);
                $salesQty = isset($SummaryDetails[5]) ? round($SummaryDetails[5], 1) : 0;
                $timeSpent = isset($SummaryDetails[6]) ? preg_replace('/\s*\d+s/', '', $SummaryDetails[6]) : ""; // Removes seconds;
                $totalMeterTravelled =  isset($SummaryDetails[7]) ? round($SummaryDetails[7] / 1000, 2) : 0;

                $arrExtraSummary = array(
                    array(
                        "label" => "Outlets Covered VS Outlets Planned",
                        "value" => (!empty($coveredOutlet) && !empty($outletPlanned)) ? "$coveredOutlet / $outletPlanned" : "0 / 0",
                        "viewType" => "progress",
                        "icon" => "https://upimg.btlmonitor.com/mobi_sum_icon/ic_shop.png"
                    ),
                    array(
                        "label" => "Productive Outlets",
                        "value" => (!empty($sellInOutlet) && !empty($outletPlanned)) ? "$sellInOutlet / $outletPlanned" : "0 / 0",
                        "viewType" => "label",
                        "icon" => "https://upimg.btlmonitor.com/mobi_sum_icon/ic_shop.png"
                    ),
                    array(
                        "label" => "Survey (M)",
                        "value" => !empty($salesQty) ? $salesQty : "0",
                        "viewType" => "label",
                        "icon" => "https://upimg.btlmonitor.com/mobi_sum_icon/ic_stock.png"
                    ),
                    array(
                        "label" => "Time spent",
                        "value" => !empty($timeSpent) ? $timeSpent : "0s",
                        "viewType" => "label",
                        "icon" => "https://upimg.btlmonitor.com/mobi_sum_icon/ic_clock.png"
                    ),
                    array(
                        "label" => "Distance",
                        "value" => isset($totalMeterTravelled) ? (string) $totalMeterTravelled . " Km" : "0 Km",
                        "viewType" => "label",
                        "icon" => "https://upimg.btlmonitor.com/mobi_sum_icon/ic_km.png"
                    )
                );

                $dayData["summary_data"] = $arrExtraSummary;
            }

            $arrCalendarData[] = $dayData;
        }

        // Append the data to the summary
        $this->summary["monthwise_calendar_data"][] = array(
            "year" => $year,
            "month" => date("F", strtotime($date)),
            "total_days" => $totalDays,
            "calendar_data" => $arrCalendarData,
        );
    }

    final public function storeDBWiseStatus($dbName, $condition = "")
    {
        // Clear previous day mobile summary from table
        $this->clearMobileCalendarSummaryTable($dbName);

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT db_name, client_id, project_id, team_id FROM {$this->cloudTable} WHERE" .
            " dstatus = 0 AND db_name = '$dbName' $condition";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows);

        if ($iNoRows > 0) {
            while ($row = $this->dbConn->GetData($rsRes)) {
                $dbName = $row['db_name'];
                $clientId = $row['client_id'];
                $projectId = $row['project_id'];
                $teamId = $row['team_id'];

                // store/update calendar data
                $this->storeTeamCalendarData($dbName, $clientId, $projectId, $teamId);

                // store/update calendar mobile summary
                $this->storeTeamCalendarSummary($dbName, $clientId, $projectId, $teamId);
            }
        }
    }
}
