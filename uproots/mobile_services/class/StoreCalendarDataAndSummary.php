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

            $teamType = $this->tableUtil->getRowColumn(
                "$dbName.tblproject_team",
                "is_type",
                "team_id = $teamId"
            );
            if ($teamType == 7 || $teamType == 10) {
                $attendanceDetails = $this->tableUtil->getRowColumns("$dbName.tblattendance", "MIN(capture_datetime), other_details", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '0'");
                if (!empty($attendanceDetails)) {
                    $arrOtherDetails = json_decode($attendanceDetails[1], true);
                    $workingWith = $arrOtherDetails['workingWith'];
                    if ($workingWith == 'Market work with AE' || $workingWith == 'Market work with GT TL' || $workingWith == 'Independent market work') {
                        $startTime = $attendanceDetails[0];
                        $arrDayEnd = $this->tableUtil->getRowColumns("$dbName.tblattendance", "distance, capture_datetime", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                        $distanceInKm = $arrDayEnd ? $arrDayEnd[0] : "";
                        $endTime = $arrDayEnd ? $arrDayEnd[1] : "";
                        $timeSpentInSec = $this->commonFunctions->getTimeDifference($startTime, $endTime, true);
                    } else {
                        $responseTime = $this->tableUtil->getRowColumnS("$dbName.tblsurvey_response_details_mdo", "MIN(capture_datetime), MAX(capture_datetime)", "dstatus = 0 AND ques_0 NOT IN ('Infra Details','InfraDetails') AND team_id = $teamId AND capture_date = '$date'");
                        $responseDistanceInKm = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "distance_in_meter", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' ORDER BY pro_id DESC");
                        $startTime =  (!empty($responseTime[0]) ? $responseTime[0] : 0);
                        $endTime =  (!empty($responseTime[1]) ? $responseTime[1] : 0);
                        $distanceInKm = !empty($arrDayEnd[0]) ? $arrDayEnd[0] : (!empty($responseDistanceInKm) ? $responseDistanceInKm : 0);
                        $timeSpentInSec = $this->commonFunctions->getTimeDifference($startTime, $endTime, true);
                    }
                }

                // Convert 6 hours into seconds
                $requiredSeconds = 360 * 60;

                // Check Present
                if ($attendanceDetails) {
                    if ($teamType == 10) {
                        $isQualifiedAttendance = $this->arrStatus["QUALIFIED"];
                    } elseif ($teamType == 7) {
                        if ($timeSpentInSec >= $requiredSeconds && $distanceInKm >= 10) {
                            $isQualifiedAttendance = $this->arrStatus["QUALIFIED"];
                        } else {
                            $isQualifiedAttendance = $this->arrStatus["UNQUALIFIED"];
                        }
                    }
                    return $isQualifiedAttendance;
                } else {
                    // Absent
                    return $this->arrStatus["ABSENT"];
                }
            } else {
                $arrSummaryData = $this->tableUtil->getRowColumn(
                    "$dbName.$summaryTable",
                    "is_qualified",
                    "dstatus = 0 AND team_id = ? AND activity_date = ?",
                    array($teamId, $date)
                );

                // Present
                if ($arrSummaryData !== null) {
                    $isQualified = (int)$arrSummaryData;
                    $isQualifiedAttendance = $isQualified == 1
                        ? $this->arrStatus["QUALIFIED"]
                        : $this->arrStatus["UNQUALIFIED"];

                    return $isQualifiedAttendance;
                } else {
                    return $this->arrStatus["ABSENT"];
                }
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
                $teamType = $this->tableUtil->getRowColumn(
                    "$dbName.tblproject_team",
                    "is_type",
                    "team_id = $teamId"
                );
                if ($teamType == 7 || $teamType == 10) {
                    // Reset day variables to avoid using previous day data
                    $timeSpent = "";
                    $distanceInKm = "";
                    $workWith = "";
                    $arrWork = $this->tableUtil->getRowColumns("$dbName.tblattendance", "other_details, capture_datetime", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '0'");
                    if ($this->commonFunctions->isNonEmptyArray($arrWork)) {
                        $arrDetails = json_decode($arrWork[0], true);
                        $workingWith = $arrDetails['workingWith'];
                        $wdDsName = $arrDetails['selectRouteYouAreGoingOn'];
                        $workType = $arrDetails[1];
                        $wdCode = $workType == 0 ? $wdDsName[0] : $wdDsName[1];
                        $dsName = $workType == 0 ? $wdDsName[1] : "";
                        $workWith = $workingWith . " - " . $wdCode . " - " . $dsName;
                        $arrDayEnd = $this->tableUtil->getRowColumns("$dbName.tblattendance", "distance, capture_datetime", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                        $responseTime = $this->tableUtil->getRowColumns("$dbName.tblsurvey_response_details_mdo", "MIN(capture_datetime), MAX(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND ques_0 NOT IN ('Infra Details','InfraDetails')");
                        $responseDistanceInKm = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "distance_in_meter", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' ORDER BY pro_id DESC");
                        $startTime = (!empty($responseTime[0]) ? $responseTime[0] : 0);
                        $endTime = (!empty($responseTime[1]) ? $responseTime[1] : 0);
                        $timeSpent = $this->commonFunctions->getTimeDifference($startTime, $endTime, false, false, true);
                        $distanceInKm = !empty($arrDayEnd[0]) ? $arrDayEnd[0] : (!empty($responseDistanceInKm) ? $responseDistanceInKm : 0);
                    }

                    $arrExtraSummary = array(
                        array(
                            "label" => "Time Spent",
                            "value" => (!empty($timeSpent)) ? "$timeSpent" : "0",
                            "viewType" => "label",
                            "icon" => "https://radardashboard.com/uproots/mobi_sum_icon/ic_clock.png"
                        ),
                        array(
                            "label" => "Distance",
                            "value" => (!empty($distanceInKm)) ? "$distanceInKm" : "0",
                            "viewType" => "label",
                            "icon" => "https://radardashboard.com/uproots/mobi_sum_icon/ic_km.png"
                        ),
                        array(
                            "label" => "Work With",
                            "value" => (!empty($workWith)) ? "$workWith" : "",
                            "viewType" => "label",
                            "icon" => "https://radardashboard.com/uproots/mobi_sum_icon/ic_shop.png"
                        ),
                    );
                } else {
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
                }

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
