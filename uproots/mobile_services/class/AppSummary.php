<?php

// phpcs:ignore
class AppSummary extends Utilities
{
    protected $arrUserDetails;
    protected $localLogFileName;
    protected $sExtraLogData;
    protected $arrProjectSummaryDetails;
    private $arrTopSummary = array();
    private $arrCustomResp = array();
    protected $requestGetData;

    public function __construct($dbConn, $tableUtil, $commonFunctions, $localLogFileName)
    {
        $this->localLogFileName = $localLogFileName;
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $localLogFileName, "/summary");
    }

    protected function getAppAndJsonMinVersion()
    {
        return array(
            "appMinAndroidVersion" => "1.0.2",
            "appMiniOSVersion" => "1.0.0",
            "appMinVersionMsg" => "Please download new app",
            "appMinVersionLink" => "https://play.google.com/store/apps/details?id=com.appilary.newradar",
            "jsonMinVersion" => "3"
        );
    }

    protected function isSummaryVisible($dbName, $clientId, $projectId)
    {
        $this->arrProjectSummaryDetails = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["summary"]) &&
            $this->commonFunctions->isNonEmptyArray($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["summary"]) ?
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["summary"]
            : (isset($this->arrDBProjectDetails[$dbName][0][0]["summary"]) &&
                $this->commonFunctions->isNonEmptyArray($this->arrDBProjectDetails[$dbName][0][0]["summary"]) ?
                $this->arrDBProjectDetails[$dbName][0][0]["summary"] : null);

        return $this->arrProjectSummaryDetails ? true : false;
    }

    protected function getSummary($appType)
    {
        $jsonId = $this->arrUserDetails["s_id"];
        $dbName = $this->arrUserDetails["db_name"];
        $clientId = $this->arrUserDetails["client_id"];
        $projectId = $this->arrUserDetails["project_id"];
        $teamId = $this->arrUserDetails["team_id"];

        // Store summary here
        $arrSummary = array();
        $currentDate = $this->commonFunctions->currentDate();
        $currentMonth = date("Y-m-") . "%";

        // Get response table
        $respTable = $this->getResponseTable($dbName, $clientId, $projectId, $teamId);

        // Show Attendance/Dayend summary
        $arrAttendanceSummary = $this->getAttendanceSummary(
            $appType,
            $dbName,
            $clientId,
            $projectId,
            $teamId,
            $currentDate,
            $currentMonth,
            $respTable
        );
        if ($arrAttendanceSummary) {
            $arrSummary = array_merge($arrSummary, $arrAttendanceSummary);
        }

        // Show Other summary
        $arrOtherSummary = $this->getOtherSummary(
            $appType,
            $dbName,
            $clientId,
            $projectId,
            $teamId,
            $jsonId,
            $currentDate,
            $currentMonth,
            $respTable
        );
        if ($arrOtherSummary) {
            $arrSummary = array_merge($arrSummary, $arrOtherSummary);
        }

        // Show Sales summary
        $arrSalesSummary = $this->getSalesSummary(
            $appType,
            $dbName,
            $clientId,
            $projectId,
            $teamId,
            $currentDate
        );
        if ($arrSalesSummary) {
            $arrSummary = array_merge($arrSummary, $arrSalesSummary);
        }

        return array($arrSummary, $this->arrCustomResp, $this->arrTopSummary);
    }

    protected function getDefaultSummary($appType)
    {
        $arrLabelList = array(
            array(
                "label" => $this->arrAuthMessages["SUMM01"],
                "value" => "",
            ),
        );

        return array(
            $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrLabelList
            )
        );
    }

    private function getFormattedSummary(
        $appType,
        $title,
        $arrLabelList = array(),
        $arrCardList = array(),
        $arrTableList = array(),
        $progressList = array(),
        $showTitleOnly = false
    ) {
        // If $showTitleOnly = true, we want to display some title only, not any labels or cards or table
        if ($arrLabelList || $arrCardList || $arrTableList || $progressList || $showTitleOnly) {
            // Old/Kotlin App
            if ($appType == 1) {
                return array(
                    "title" => $title,
                    "summaryList" => $arrTableList ? $arrTableList : ($arrCardList ? $arrCardList : $arrLabelList),
                );
            } else {
                // New/Flutter App

                return array(
                    "summaryTitle" => $title,
                    "summaryData" => array(
                        "labelList" => $arrLabelList,
                        "cardList" => $arrCardList,
                        // "tableData" should be null if no table labels otherwise summary will not be visible in app
                        "tableData" => $arrTableList ? $arrTableList : null,
                        "progressList" => $progressList,
                    ),
                );
            }
        }

        return array();
    }

    private function getResponseTable($dbName, $clientId, $projectId, $teamId)
    {
        global $TBL_PROJECT_TEAM;

        $respTable = null;
        if (isset($this->arrDBProjectDetails[$dbName]["path"]) && $this->arrDBProjectDetails[$dbName]["path"]) {
            include_once $this->arrDBProjectDetails[$dbName]["path"];

            // Check if JSON ID is required or not to get response table
            $isJsonIdForRespTableRequire = isset($this->arrDBProjectDetails[$dbName]["requireJsonIdForRespTable"]) ?
                $this->arrDBProjectDetails[$dbName]["requireJsonIdForRespTable"] : false;
            if ($isJsonIdForRespTableRequire) {
                $jsonId = $this->tableUtil->getRowColumn(
                    "$dbName.$TBL_PROJECT_TEAM",
                    "s_id",
                    "team_id = $teamId"
                );
                $respTable = getRespTable($clientId, $projectId, $jsonId, $COMMON_PROCESS_SETTINGS, $PROJECT_SPECIFIC_SETTINGS);
            } else {
                $respTable = getRespTable($clientId, $projectId, null, $COMMON_PROCESS_SETTINGS, $PROJECT_SPECIFIC_SETTINGS);
            }
        } else {
            $respTable = isset($this->arrProjectSummaryDetails["respTable"]) &&
                $this->arrProjectSummaryDetails["respTable"] ? $this->arrProjectSummaryDetails["respTable"] : null;
        }

        return $respTable;
    }

    private function getTeamAttendanceOrDayend($dbName, $attendanceTable, $teamId, $date, $cond = "")
    {
        return $this->tableUtil->getRowColumn(
            "$dbName.$attendanceTable",
            "capture_datetime",
            "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' $cond"
        );
    }

    private function getTeamMtdAttendanceCount($dbName, $attendanceTable, $teamId, $month, $cond = "")
    {
        return $this->tableUtil->getRowColumn(
            "$dbName.$attendanceTable",
            "COUNT(DISTINCT capture_date) AS total",
            "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '$month' $cond"
        );
    }

    private function getAttendanceSummary($appType, $dbName, $clientId, $projectId, $teamId, $date, $month, $respTable)
    {
        global $TBL_ATTENDANCE, $DELHI_DB, $ITC_DB, $SNPL_DB, $SOUTH_DB;

        $arrAttendanceSummary = array();

        // Flags
        $showAttendanceSummary = isset($this->arrProjectSummaryDetails["showAttendanceSummary"]) ?
            $this->arrProjectSummaryDetails["showAttendanceSummary"] : false;
        $showDayendSummary = isset($this->arrProjectSummaryDetails["showDayendSummary"]) ?
            $this->arrProjectSummaryDetails["showDayendSummary"] : false;
        $showLeaveWeekOffSummary = isset($this->arrProjectSummaryDetails["showLeaveWeekOffSummary"]) ?
            $this->arrProjectSummaryDetails["showLeaveWeekOffSummary"] : false;
        $isSeparateAttendanceTable = isset($this->arrProjectSummaryDetails["isSeparateAttendanceTable"]) ?
            $this->arrProjectSummaryDetails["isSeparateAttendanceTable"] : false;
        $attendanceTable = isset($this->arrProjectSummaryDetails["attendanceTable"]) &&
            $this->arrProjectSummaryDetails["attendanceTable"] ?
            $this->arrProjectSummaryDetails["attendanceTable"] : $TBL_ATTENDANCE;
        $attendanceCond = isset($this->arrProjectSummaryDetails["attendanceCond"]) &&
            $this->arrProjectSummaryDetails["attendanceCond"] ?
            $this->arrProjectSummaryDetails["attendanceCond"] : "";
        $logoutCond = isset($this->arrProjectSummaryDetails["logoutCond"]) ?
            $this->arrProjectSummaryDetails["logoutCond"] : "";
        $attendanceShowNoDaysInAMonth = isset($this->arrProjectSummaryDetails["attendanceShowNoDaysInAMonth"]) ?
            $this->arrProjectSummaryDetails["attendanceShowNoDaysInAMonth"] : false;
        $attendanceExcludeWeekDay = isset($this->arrProjectSummaryDetails["attendanceExcludeWeekDay"]) ?
            $this->arrProjectSummaryDetails["attendanceExcludeWeekDay"] : "";
        $attendanceMtdLabel = isset($this->arrProjectSummaryDetails["attendanceMtdLabel"]) &&
            $this->arrProjectSummaryDetails["attendanceMtdLabel"] ?
            $this->arrProjectSummaryDetails["attendanceMtdLabel"] : "";
        $attendanceShowLoginTime = isset($this->arrProjectSummaryDetails["attendanceShowLoginTime"]) ?
            $this->arrProjectSummaryDetails["attendanceShowLoginTime"] : false;
        $attendanceShowLogoutTime = isset($this->arrProjectSummaryDetails["attendanceShowLogoutTime"]) ?
            $this->arrProjectSummaryDetails["attendanceShowLogoutTime"] : false;

        if (
            $showAttendanceSummary || $attendanceShowLoginTime ||
            $showDayendSummary || $attendanceShowLogoutTime || $showLeaveWeekOffSummary
        ) {
            $todayAttendance = $todayDayend = null;
            $mtdPresents = 0;

            // Attendance is stored in a common table for all projects like in delhi, itc2, etc
            if ($isSeparateAttendanceTable) {
                if ($showAttendanceSummary || $attendanceShowLoginTime) {
                    $todayAttendance = $this->getTeamAttendanceOrDayend(
                        $dbName,
                        $attendanceTable,
                        $teamId,
                        $date,
                        "AND call_type = '0' $attendanceCond"
                    );
                    // print_r($todayAttendance);die;
                }

                if ($showDayendSummary || $attendanceShowLogoutTime) {
                    $todayDayend = $this->getTeamAttendanceOrDayend(
                        $dbName,
                        $attendanceTable,
                        $teamId,
                        $date,
                        "AND call_type = '1' $logoutCond"
                    );
                }

                $mtdPresents = $this->getTeamMtdAttendanceCount(
                    $dbName,
                    $attendanceTable,
                    $teamId,
                    $month,
                    "AND call_type = '0' $attendanceCond"
                );
            } else {
                // Attendance is stored in response table

                if ($showAttendanceSummary || $attendanceShowLoginTime) {
                    $todayAttendance = $this->getTeamAttendanceOrDayend(
                        $dbName,
                        $attendanceTable,
                        $teamId,
                        $date,
                        $attendanceCond
                    );
                }

                if ($showDayendSummary || $attendanceShowLogoutTime) {
                    $todayDayend = $this->getTeamAttendanceOrDayend(
                        $dbName,
                        $attendanceTable,
                        $teamId,
                        $date,
                        $logoutCond
                    );
                }

                $mtdPresents = $this->getTeamMtdAttendanceCount(
                    $dbName,
                    $attendanceTable,
                    $teamId,
                    $month,
                    $attendanceCond
                );
            }

            $isPresentToday = isset($todayAttendance) && $todayAttendance ? true : false;
            $mtdPresents = $mtdPresents ? (string) $mtdPresents : "0";

            // Store Attendance summary
            $arrAttendanceLabelList = array();

            // SNPL DB attendance summary
            if ($dbName === $SNPL_DB) {
                if ($showAttendanceSummary) {
                    $arrAttendanceLabelList[] = array(
                        "label" => "आजको हाजिरी",
                        "value" => $isPresentToday ? $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                    );
                }

                $arrAttendanceLabelList[] = array(
                    "label" => "यस महिनाको कुल हाजिरी",
                    "value" => $mtdPresents,
                );
            } else {
                // Other DB's attendance summary

                // Show attendance
                if ($showAttendanceSummary) {
                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["TODAYS_ATTENDANCE"],
                        "value" => $isPresentToday ? $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                    );
                }

                // Show dayend
                if ($showDayendSummary) {
                    $isDayendToday = isset($todayDayend) && $todayDayend ? true : false;

                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["TODAYS_DAYEND"],
                        "value" => $isDayendToday ? $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                    );
                }

                // Show if employee/promoter in on week off today or not
                if ($showLeaveWeekOffSummary && $respTable && $respTable != "tblsurvey_response_details") {
                    $leaveType = $this->tableUtil->getRowColumn(
                        "$dbName.$respTable",
                        "ques_1",
                        "ques_0 = 'Leave Report' AND dstatus = 0 AND team_id = $teamId AND capture_date = '$date'"
                    );

                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["TODAYS_LEAVE_WEEKEND"],
                        "value" => $leaveType && $leaveType == 'Week OFF' ? $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                    );
                }

                // Show no of days in a month along with no of days present in a month
                if ($attendanceShowNoDaysInAMonth) {
                    $noOfDays = $this->commonFunctions->getCountOfDaysExcluding($attendanceExcludeWeekDay);
                    $mtdPresents = "$mtdPresents/$noOfDays";
                }
                // Show mtd attendance
                $arrAttendanceLabelList[] = array(
                    "label" => $attendanceMtdLabel ?
                        (isset($this->arrSummaryLabels[$attendanceMtdLabel]) ?
                            $this->arrSummaryLabels[$attendanceMtdLabel] :
                            $attendanceMtdLabel) : $this->arrSummaryLabels["MTD_ATTENDANCE"],
                    "value" => $mtdPresents,
                );

                // Show attendance time
                if ($attendanceShowLoginTime) {
                    $todayLoginDatetime = isset($todayAttendance) && $todayAttendance ? $todayAttendance : "";
                    $todayLoginTime = $todayLoginDatetime ?
                        $this->commonFunctions->currentDateTime("h:i:s A", $todayLoginDatetime) :
                        $this->arrSummaryLabels["NA"];

                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["LOGIN_TIME"],
                        "value" => $todayLoginTime,
                    );
                }

                // Show logout time
                if ($attendanceShowLogoutTime) {
                    $todayLogoutDatetime = isset($todayDayend) && $todayDayend ? $todayDayend : "";
                    $todayLogoutTime = $todayLogoutDatetime ?
                        $this->commonFunctions->currentDateTime("h:i:s A", $todayLogoutDatetime) :
                        $this->arrSummaryLabels["NA"];

                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["LOGOUT_TIME"],
                        "value" => $todayLogoutTime,
                    );
                }

                // Show Qualified attendance summary in delhi DB
                if ($dbName === $DELHI_DB) {
                    $teamType = $this->tableUtil->getRowColumn(
                        "$dbName.tblproject_team",
                        "is_ISS",
                        "dstatus = 0 AND team_id = $teamId"
                    );
                    $teamTypeCondition = ($teamType == 5) || ($teamType == 4) ? "team_type = '5'" : "team_type = '1'";
                    if ($teamType == 7) {
                        $arrAttendanceLabelList[] = array(
                            "label" => $this->arrSummaryLabels["MTD_ATTENDANCE"],
                            "value" => "$mtdPresents",
                        );
                    } else {
                        $minBillsShops = $this->tableUtil->getRowColumn(
                            "$dbName.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minTotalShops' AND $teamTypeCondition"
                        );
                        $minWorkingTimeInMin = $this->tableUtil->getRowColumn(
                            "$dbName.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minWorkingTimeInMin' AND $teamTypeCondition"
                        );

                        $arrQualifiedAttendance = $this->tableUtil->getRowsColumns(
                            "$dbName.tblvands_summary",
                            "SUM(total_sellin_shops + total_other_shops) AS totalShops",
                            "dstatus = 0 AND team_id = $teamId AND activity_date LIKE '$month'" .
                                " AND TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime) >= $minWorkingTimeInMin" .
                                " AND DAYOFWEEK(activity_date) != 1 GROUP BY activity_date HAVING totalShops >= $minBillsShops"
                        );

                        $noOfQualifiedAttendance = count($arrQualifiedAttendance);

                        $arrAttendanceLabelList[] = array(
                            "label" => $this->arrSummaryLabels["QUALIFIED_MARKET_WORKING_DAYS"],
                            "value" => "$noOfQualifiedAttendance/" . ($noOfDays ? $noOfDays : date("t")),
                        );
                    }
                } elseif ($dbName === $ITC_DB || $dbName === $SOUTH_DB) {
                    // Show Qualified attendance summary in Itc and South DB

                    $minTotalShops = $this->tableUtil->getRowColumn(
                        "$dbName.tblconstants",
                        "con_value",
                        "dstatus = 0 AND con_name = 'minTotalShops'"
                    );
                    $minWorkingTimeInMin = $this->tableUtil->getRowColumn(
                        "$dbName.tblconstants",
                        "con_value",
                        "dstatus = 0 AND con_name = 'minWorkingTimeInMin'"
                    );

                    $arrQualifiedAttendance = $this->tableUtil->getRowsColumns(
                        "$dbName.tblvands_summary",
                        $dbName === $ITC_DB ? "SUM(total_roc_deliveries + total_other_shops) AS totalShops" :
                            "SUM(total_deliveries) AS totalShops",
                        "dstatus = 0 AND team_id = $teamId AND activity_date LIKE '$month'" .
                            " AND TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime) >= $minWorkingTimeInMin" .
                            " GROUP BY activity_date HAVING totalShops >= $minTotalShops"
                    );
                    $noOfQualifiedAttendance = count($arrQualifiedAttendance);

                    $arrAttendanceLabelList[] = array(
                        "label" => $this->arrSummaryLabels["QUALIFIED_MARKET_WORKING_DAYS"],
                        "value" => (string) $noOfQualifiedAttendance,
                    );
                }
            }

            // Output summary
            $arrAttendanceSummary[] = $this->getFormattedSummary(
                $appType,
                $dbName == $SNPL_DB ? "हाजिरीको सारांश" : $this->arrSummaryLabels["ATTENDANCE_SUMMARY"],
                $arrAttendanceLabelList
            );
        }

        return $arrAttendanceSummary;
    }

    private function getEachSaleQuesData(&$arrSalesLabelList, $arrSalesSummaryQues, $rowSummary = array())
    {
        // loop through each sale question
        foreach ($arrSalesSummaryQues as $salesQuesIndex => $ques) {
            $salesValue = isset($rowSummary[$ques["quesNo"]]) && $rowSummary[$ques["quesNo"]] ?
                $rowSummary[$ques["quesNo"]] : null;
            $salesColumns = isset($ques["columns"]) && $ques["columns"] ? $ques["columns"] : null;
            $salesRows = isset($ques["rows"]) && $ques["rows"] ? $ques["rows"] : null;
            $arrSalesLabels = isset($ques["salesLabels"]) ? $ques["salesLabels"] : array();

            // if grid
            if ($salesColumns && $salesRows) {
                $this->commonFunctions->getSalesFromGridDataAsArray(
                    $arrSalesLabelList,
                    $salesValue ? json_decode($salesValue, true) : array(),
                    $arrSalesLabels,
                    $salesColumns,
                    $salesRows
                );
            } else {
                // not grid

                if (!isset($arrSalesLabelList[$salesQuesIndex])) {
                    $arrSalesLabelList[$salesQuesIndex] = array("label" => $arrSalesLabels[0], "value" => "0");
                }
                $arrSalesLabelList[$salesQuesIndex]["value"] = (string) ((float) $arrSalesLabelList[$salesQuesIndex]["value"] +
                    ($salesValue > 0 ? (float) $salesValue : 0));
            }
        }
    }

    private function getSalesSummary($appType, $dbName, $clientId, $projectId, $teamId, $date)
    {
        $arrSalesSummary = array();

        // Flags
        $showSalesSummary = isset($this->arrProjectSummaryDetails["showSalesSummary"]) ?
            $this->arrProjectSummaryDetails["showSalesSummary"] : false;
        $salesSummaryTable = isset($this->arrProjectSummaryDetails["salesSummaryTable"]) ?
            $this->arrProjectSummaryDetails["salesSummaryTable"] : null;
        $arrSalesSummaryConfig = isset($this->arrProjectSummaryDetails["salesSummaryConfig"]) &&
            $this->arrProjectSummaryDetails["salesSummaryConfig"] ?
            $this->arrProjectSummaryDetails["salesSummaryConfig"] : null;

        // Show sales summary
        if ($showSalesSummary && $salesSummaryTable && $arrSalesSummaryConfig) {
            // Loop through each radio option/condition in a JSON and send summary as separate card
            foreach ($arrSalesSummaryConfig as $arrConfig) {
                // Store sales labels
                $arrSalesLabelList = array();

                // get sales questions as JSON may have multiple sales ques
                $arrSalesSummaryQues = $arrConfig["salesSummaryQues"];
                $salesQues = $this->commonFunctions->getStringFromArray($arrSalesSummaryQues, true, ", ", "quesNo");

                // get sales condition
                $salesCond = isset($arrConfig["salesSummaryCond"]) && $arrConfig["salesSummaryCond"] ?
                    $arrConfig["salesSummaryCond"] : "";

                // get sales
                $rsSummaryAction = null;
                $iSummaryActionRows = 0;
                $sSummaryQuery = "SELECT $salesQues FROM $dbName.$salesSummaryTable WHERE dstatus = 0" .
                    " AND pid = $projectId AND team_id = $teamId AND capture_date = '$date' $salesCond";
                $this->dbConn->ExecuteSelectQuery($sSummaryQuery, $rsSummaryAction, $iSummaryActionRows);

                if ($iSummaryActionRows > 0) {
                    while ($rowSummary = $this->dbConn->GetData($rsSummaryAction)) {
                        // get each sale question summary
                        $this->getEachSaleQuesData($arrSalesLabelList, $arrSalesSummaryQues, $rowSummary);
                    }
                } else {
                    // get each sale question summary as 0
                    $this->getEachSaleQuesData($arrSalesLabelList, $arrSalesSummaryQues);
                }

                // get and send total records count
                $arrTotalRecordsSummary = isset($arrConfig["totalRecordsSummary"]) && $arrConfig["totalRecordsSummary"] ?
                    $arrConfig["totalRecordsSummary"] : array();
                if (isset($arrTotalRecordsSummary["count"]) && $arrTotalRecordsSummary["count"]) {
                    $arrSalesLabelList[] = array(
                        "label" => $arrTotalRecordsSummary["label"],
                        "value" => (string) $iSummaryActionRows,
                    );
                }

                // Output summary
                $arrSalesSummary[] = $this->getFormattedSummary(
                    $appType,
                    $arrConfig["salesSummaryTitle"],
                    $arrSalesLabelList
                );
            }
        }

        return $arrSalesSummary;
    }

    private function getTeamBranch($dbName, $teamId)
    {
        global $TBL_PROJECT_TEAM;

        // Get team branch
        $branchId = $this->tableUtil->getRowColumn(
            "$dbName.$TBL_PROJECT_TEAM",
            "branch_id",
            "team_id = $teamId"
        );

        return $branchId ? $branchId : 1;
    }

    private function getPreDefinedSummary($dbName, $teamId, $date, $table = null)
    {
        global $TBL_DAILY_MOBILE_SUMMARY;

        $table = $table ? $table : $TBL_DAILY_MOBILE_SUMMARY;

        // Get summary
        $sMobileSummary = $this->tableUtil->getRowColumn(
            "$dbName.$table",
            "summary",
            "dstatus = 0 AND team_id = $teamId AND rcd = '$date'"
        );

        if ($sMobileSummary) {
            return json_decode(html_entity_decode($sMobileSummary), true);
        }

        return array();
    }

    private function getOtherSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $jsonId,
        $date,
        $month,
        $respTable
    ) {
        global $DELHI_DB, $ITC_DB, $ITCNEW_DB, $ITCPH2_DB, $JAIPUR_DB, $SNPL_DB, $SOUTH_DB,
            $IMPACT_DB, $NOVICEMARCOM_DB, $WONDER_DB, $ZX_DB;

        $arrOtherSummary = array();

        // Other summary
        $showOtherSummary = isset($this->arrProjectSummaryDetails["showOtherSummary"]) ?
            $this->arrProjectSummaryDetails["showOtherSummary"] : false;
        $otherSummaryCond = isset($this->arrProjectSummaryDetails["otherSummaryCond"]) &&
            $this->arrProjectSummaryDetails["otherSummaryCond"] ?
            $this->arrProjectSummaryDetails["otherSummaryCond"] : "";

        // Show Other summary
        if ($showOtherSummary) {
            if ($dbName === $DELHI_DB) {
                $arrOtherSummary = $this->getDelhiSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $ITC_DB || $dbName === $JAIPUR_DB) {
                $arrOtherSummary = $this->getItcAndJaipurSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $ITCNEW_DB) {
                $arrOtherSummary = $this->getItcnewSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $ITCPH2_DB) {
                $arrOtherSummary = $this->getItcph2Summary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $jsonId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $SNPL_DB) {
                $arrOtherSummary = $this->getSnplSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $jsonId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $SOUTH_DB) {
                $arrOtherSummary = $this->getSouthSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $IMPACT_DB) {
                $arrOtherSummary = $this->getImpactSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $NOVICEMARCOM_DB) {
                $arrOtherSummary = $this->getNovicemarcomSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $WONDER_DB) {
                $arrOtherSummary = $this->getWonderSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            } elseif ($dbName === $ZX_DB) {
                $arrOtherSummary = $this->getZxSummary(
                    $appType,
                    $dbName,
                    $clientId,
                    $projectId,
                    $teamId,
                    $otherSummaryCond,
                    $date,
                    $month,
                    $respTable
                );
            }
        }

        return $arrOtherSummary;
    }

    private function getDelhiSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $TBL_ATTENDANCE, $TBL_ROUTE_DETAILS;

        $arrOtherSummary = $arrOtherLabelList = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        $currentDate = $this->commonFunctions->currentDate();
        $teamType = $this->tableUtil->getRowColumn(
            "$dbName.tblproject_team",
            "is_ISS",
            "dstatus = 0 AND team_id = $teamId"
        );
        if ($teamType == 7) {
            $todayKycShops = $this->tableUtil->getRowColumn(
                "$dbName.tblsurvey_response_details_gt_tl",
                "COUNT(pro_id) AS total",
                "ques_0 = 'Shop KYC' AND dstatus = 0 AND team_id = $teamId AND capture_date = '$date'"
            );
            $todaySalesVisit = $this->tableUtil->getRowColumn(
                "$dbName.tblsurvey_response_details_gt_tl",
                "COUNT(pro_id) AS total",
                "ques_0 = 'Sales Visit' AND dstatus = 0 AND team_id = $teamId AND capture_date = '$date'"
            );
            $todayAddOutlet = $this->tableUtil->getRowColumn(
                "$dbName.tblsurvey_response_details_gt_tl",
                "COUNT(pro_id) AS total",
                "ques_0 = 'Add Outlet' AND dstatus = 0 AND team_id = $teamId AND capture_date = '$date'"
            );

            $totalShops = $this->tableUtil->getRowColumn(
                "$dbName.tblroute_details",
                "COUNT(rec_id) AS total",
                "dstatus = 0 AND gt_tl_team_id = $teamId AND done = 0"
            );
            $totalKycShops = $this->tableUtil->getRowColumn(
                "$dbName.tblsurvey_response_details_gt_tl",
                "COUNT(pro_id) AS total",
                "ques_0 = 'Shop KYC' AND dstatus = 0 AND team_id = $teamId"
            );

            $arrOtherLabelList = array(
                array(
                    "label" => "Shop KYC",
                    "value" => "$todayKycShops",
                ),
                array(
                    "label" => "Sales Visit",
                    "value" => "$todaySalesVisit",
                ),
                array(
                    "label" => "Add Outlet",
                    "value" => "$todayAddOutlet",
                ),
            );

            $arrResponse2 = array(
                array(
                    "progressType" => "circleProgress",
                    "toplabel" => "KYC's Summary",
                    // "bottomlabel" => "Sales this week",
                    "bottomCardList" => array(
                        array(
                            "label" => "KYC Done / Pending",
                            "value1" => "$totalKycShops",
                            "value2" => "$totalShops",
                            "icon" => "0xf05c0",
                            "iconcolor" => "#0000ff",
                            "color1" => "#ff0000",
                            "color2" => "#ffa500",
                        ),
                    )
                ),

            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["DAY_SUMMARY"] . ":" . $currentDateTime,
                $arrOtherLabelList
            );

            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "",
                array(),
                array(),
                array(),
                $arrResponse2
            );
        } elseif ($teamType == 8 || $teamType == 9 || $teamType == 10) {
            $totalShops = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_kunal", "COUNT(pro_id) AS total", "dstatus = 0 AND team_id = $teamId AND capture_date = '$currentDate'");
            $uob = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_kunal", "COUNT(DISTINCT ques_2) AS uob", "dstatus = 0 AND team_id = $teamId AND capture_date = '$currentDate'");

            // Fetch products where show_on_tab = 1
            $products = $this->tableUtil->getRowsColumns("$dbName.tbl_kunal_pctl_products", "product_name, summary_column_name, sort_order", "show_on_tab = 1 AND team_type = $teamType");

            // Initialize arrays
            $arrOtherLabelTodayList = array();
            $arrOtherLabelMtdList = array();
            $month = date('Y-m', strtotime($currentDate)); // e.g., '2025-05'

            // Process each product
            foreach ($products as $product) {
                $productName = $product['product_name'];
                $summaryColumn = $product['summary_column_name'];

                // Calculate today's sales
                $todaySales = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_kunal", "SUM($summaryColumn) AS sales", "dstatus = 0 AND team_id = $teamId AND capture_date = '$currentDate'");

                // Calculate MTD sales
                $mtdSales = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_kunal", "SUM($summaryColumn) AS sales", "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '$month%'");

                // Add to Today list
                $arrOtherLabelTodayList[] = array(
                    "label" => $productName,
                    "todayTargetValue" => "5", // Hardcoded, replace with query if available
                    "todayAchivedValue" => $todaySales ?: "0"
                );

                // Add to MTD list
                $arrOtherLabelMtdList[] = array(
                    "label" => $productName,
                    "mtdTargetValue" => "5", // Hardcoded, replace with query if available
                    "mtdAchivedValue" => $mtdSales ?: "0"
                );
            }


            $arrOtherLabelTodayList = array(
                array(
                    "label" => "Visit Gate", //once visit
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "UOB Billing", // distinct visited
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "ULC Billing",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "Icon Sales",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "AC Sales",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "Social Sales",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "Verve Sales",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "Overall Sales",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
                array(
                    "label" => "Attendance Qualified",
                    "todayTargetValue" => "5",
                    "todayAchivedValue" => "3"
                ),
            );
            $arrOtherLabelMtdList = array(
                array(
                    "label" => "Visit Gate",
                    "mtdTargetValue" => "26",
                    "mtdAchivedValue" => "7", //once visi
                ),
                array(
                    "label" => "UOB Billing",
                    "mtdTargetValue" => "5", // no of outlet
                    "mtdAchivedValue" => "6", // distinct visite
                ),
                array(
                    "label" => "ULC Billing",
                    "mtdTargetValue" => "4",
                    "mtdAchivedValue" => "9"
                ),
                array(
                    "label" => "Icon Sales",
                    "mtdTargetValue" => "3",
                    "mtdAchivedValue" => "0"
                ),
                array(
                    "label" => "AC Sales",
                    "mtdTargetValue" => "3",
                    "mtdAchivedValue" => "2"
                ),
                array(
                    "label" => "Social Sales",
                    "mtdTargetValue" => "2",
                    "mtdAchivedValue" => "0"
                ),
                array(
                    "label" => "Verve Sales",
                    "mtdTargetValue" => "1",
                    "mtdAchivedValue" => "1"
                ),
                array(
                    "label" => "Overall Sales",
                    "mtdTargetValue" => "27",
                    "mtdAchivedValue" => "12"
                ),
                array(
                    "label" => "Attendance Qualified",
                    "mtdTargetValue" => "50",
                    "mtdAchivedValue" => "30"
                ),
            );
            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "Today",
                $arrOtherLabelTodayList
            );
            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "MTD",
                $arrOtherLabelMtdList
            );
        } else {
            // Get team branch
            $branchId = $this->getTeamBranch($dbName, $teamId);

            // Get sale products on a branch
            $rsProductAction = null;
            $iProductActionRows = 0;
            $sProductQuery = "SELECT DISTINCT product_name, summary_column_name, focus_product" .
                " FROM $dbName.tblbranch_products WHERE dstatus = 0 AND product_type = 0 AND branch_id = $branchId";
            $this->dbConn->ExecuteSelectQuery($sProductQuery, $rsProductAction, $iProductActionRows);

            $sAvgSaleTodayColumn = "";
            $sAvgSaleMtdColumn = "";
            $sMtdSaleColumn = "";
            $sFocusProductColumns = "";
            $arrFocusProductsNames = array();
            $arrFocusProductsColumns = array();
            if ($iProductActionRows > 0) {
                $arrProductColumns = array();
                while ($rowProduct = $this->dbConn->GetData($rsProductAction)) {
                    $productName = $rowProduct["product_name"];
                    $summaryColumn = $rowProduct["summary_column_name"];
                    $isFocusProduct = $rowProduct["focus_product"];

                    // Find avg sale and mtd sale of "Overall Sale" product only
                    // if (strtolower($productName) === "overall sale") {
                    $arrProductColumns[] = $summaryColumn;
                    // }

                    if ($isFocusProduct == 1) {
                        $arrFocusProductsNames[] = $productName;
                        $arrFocusProductsColumns[] = "SUM($summaryColumn) AS $summaryColumn";
                    }
                }

                $sProductColumns = implode(" + ", $arrProductColumns);
                $sAvgSaleTodayColumn = ", SUM($sProductColumns) AS avgTodaySale";
                $sAvgSaleMtdColumn = "AVG($sProductColumns) AS avgMtdSale";
                $sMtdSaleColumn = "SUM($sProductColumns) AS mtdSale";
                $sFocusProductColumns = $arrFocusProductsColumns ? ", " . implode(", ", $arrFocusProductsColumns) : "";

                // today's summary
                $arrTeamTodaySummary = $this->tableUtil->getRowColumns(
                    "$dbName.tblvands_summary",
                    "start_datetime, end_datetime, SUM(total_roc_deliveries + total_other_shops)" .
                        " AS outletsVisited, SUM(total_sellin_shops + total_other_shops) AS billsCut" .
                        " $sAvgSaleTodayColumn $sFocusProductColumns",
                    "dstatus = 0 AND team_id = $teamId AND activity_date = '$date'"
                );
                $timeSpent = isset($arrTeamTodaySummary[0]) && $arrTeamTodaySummary[0] ?
                    $this->commonFunctions->getTimeDifference($arrTeamTodaySummary[0], $arrTeamTodaySummary[1], false, false, true) : "0s";
                $outletsVisitedToday = isset($arrTeamTodaySummary[2]) && $arrTeamTodaySummary[2] ?
                    $arrTeamTodaySummary[2] : 0;
                $billsCutToday = isset($arrTeamTodaySummary[3]) && $arrTeamTodaySummary[3] ? $arrTeamTodaySummary[3] : 0;

                // divide by 100 to get sale in M
                $avgSaleToday = isset($arrTeamTodaySummary[4]) && $arrTeamTodaySummary[4] ?
                    round($arrTeamTodaySummary[4] / 100, 2) : 0;

                // Find avg sale, total sales and each focus product sale for current month (in M)
                $saleColumns = "$sAvgSaleMtdColumn, $sMtdSaleColumn $sFocusProductColumns";
                $arrTeamMonthSummary = $this->tableUtil->getRowColumns(
                    "$dbName.tblvands_summary",
                    $saleColumns,
                    "dstatus = 0 AND team_id = $teamId AND activity_date LIKE '$month'"
                );

                $avgSaleMonth = isset($arrTeamMonthSummary[0]) && $arrTeamMonthSummary[0] ?
                    round($arrTeamMonthSummary[0] / 100, 2) : 0;
                $saleMonthTillDate = isset($arrTeamMonthSummary[1]) && $arrTeamMonthSummary[1] ?
                    number_format(round($arrTeamMonthSummary[1] / 100, 2), 2) : 0;

                // Outlets mapped
                $outletsMapped = $this->tableUtil->getRowColumn(
                    "$dbName.$TBL_ROUTE_DETAILS",
                    "COUNT(rec_id) AS total",
                    "dstatus = 0 AND team_id = $teamId"
                );
                $outletsMapped = $outletsMapped ? $outletsMapped : 0;

                $arrOtherLabelList = array(
                    array(
                        "label" => $this->arrSummaryLabels["TIME_SPENT_IN_MARKET"],
                        "value" => $timeSpent,
                    ),
                    array(
                        "label" => $this->arrSummaryLabels["OUTLETS_VISITED"],
                        "value" => "$outletsVisitedToday/$outletsMapped",
                    ),
                    array(
                        "label" => $this->arrSummaryLabels["TOTAL_BILLS_CUT"],
                        "value" => "$billsCutToday/$outletsMapped",
                    ),
                    array(
                        "label" => $this->arrSummaryLabels["AVG_SALES"],
                        "value" => "$avgSaleToday/$avgSaleMonth",
                    ),
                    array(
                        "label" => $this->arrSummaryLabels["SALES_MONTH_TILL_DATE"],
                        "value" => "$saleMonthTillDate",
                    ),
                );

                // Output summary
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    $this->arrSummaryLabels["DAY_SUMMARY"] . ":" . $currentDateTime,
                    $arrOtherLabelList
                );

                // Output Focus SKU summary
                if ($arrFocusProductsColumns) {
                    $arrFocusSkuOtherLabelList = array();

                    $iTodaySaleColumnStartIndex = 5;
                    $iMonthSaleColumnStartIndex = 2;
                    foreach ($arrFocusProductsNames as $productIndex => $productname) {
                        $iTodaySales = isset($arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex]) &&
                            $arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex] ?
                            round($arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex] / 100, 2) : 0;

                        $iMonthSales = isset($arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex]) &&
                            $arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex] ?
                            round($arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex] / 100, 2) : 0;

                        $arrFocusSkuOtherLabelList[] = array(
                            "label" => $productname,
                            "value" => "$iTodaySales/$iMonthSales",
                        );
                    }

                    $arrOtherSummary[] = $this->getFormattedSummary(
                        $appType,
                        $this->arrSummaryLabels["FOCUS_SKU_SALES"],
                        $arrFocusSkuOtherLabelList
                    );
                }

                // Get today's route and send outlets not visited in this month on this route
                $arrOtherLabelList = array();
                $sOtherDetails = $this->tableUtil->getRowColumn(
                    "$dbName.$TBL_ATTENDANCE",
                    "other_details",
                    "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'"
                );
                $arrOtherDetails = $sOtherDetails ? json_decode($sOtherDetails, true) : array();
                $sRoute = isset($arrOtherDetails["route"][0]) ? $arrOtherDetails["route"][0] : "";

                if ($sRoute) {
                    $rsShopAction = null;
                    $iShopActionRows = 0;
                    $sShopQuery = "SELECT DISTINCT outlet_name FROM $dbName.$TBL_ROUTE_DETAILS WHERE dstatus = 0 AND team_id = $teamId AND route_name = ? AND rec_id NOT IN (SELECT REPLACE(REPLACE(ques_2, '\"]', ''), '[\"', '') FROM $dbName.$respTable WHERE dstatus = 0" .
                        " AND team_id = $teamId AND capture_date LIKE '$month')";
                    $this->dbConn->ExecuteSelectQuery($sShopQuery, $rsShopAction, $iShopActionRows, array($sRoute));

                    if ($iShopActionRows > 0) {
                        while ($rowShop = $this->dbConn->GetData($rsShopAction)) {
                            $arrOtherLabelList[] = array(
                                "label" => $rowShop["outlet_name"],
                                "value" => "",
                            );
                        }

                        // Output summary
                        $arrOtherSummary[] = $this->getFormattedSummary(
                            $appType,
                            str_replace("{ROUTE}", $sRoute, $this->arrSummaryLabels["OUTLETS_NOT_VISITED_IN_THIS_MONTH"]),
                            $arrOtherLabelList
                        );
                    }
                }

                // Send whether to call get_stock_products_selling_price.php API or not to get inhand stock
                $iCallStockApi = $this->tableUtil->getRowColumn(
                    "$dbName.tblstock_inhand",
                    "is_updated_in_app",
                    "dstatus = 0 AND team_id = $teamId"
                );
                $this->arrCustomResp = array(
                    "call_stock_api" => $iCallStockApi == 0 ? true : false,
                );
            }
        }

        return $arrOtherSummary;
    }

    private function getItcAndJaipurSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $ITC_DB, $TBL_MOBILE_SUMMARY;

        $arrOtherSummary = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        $arrTeamSummary = $this->tableUtil->getRowColumns(
            "$dbName.$TBL_MOBILE_SUMMARY",
            "time_spent_today, grocery_count_today, retail_count_today, wholesale_count_today" .
                ", roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd" .
                ", roc_total_shops_count, roc_covered_today, roc_covered_mtd",
            "dstatus = 0 AND team_id = $teamId"
        );

        $timeSpentToday = isset($arrTeamSummary, $arrTeamSummary[0]) ? $arrTeamSummary[0] : "0s";
        $todayOtherOutletGroceryCount = isset($arrTeamSummary, $arrTeamSummary[1]) ? $arrTeamSummary[1] : 0;
        $todayOtherOutletRetailCount = isset($arrTeamSummary, $arrTeamSummary[2]) ? $arrTeamSummary[2] : 0;
        $todayOtherOutletWholesaleCount = isset($arrTeamSummary, $arrTeamSummary[3]) ?
            $arrTeamSummary[3] : 0;
        $todayROCSellinShopsCount = isset($arrTeamSummary, $arrTeamSummary[4]) ? $arrTeamSummary[4] : 0;
        $otherCoveredShopsTodayCount = isset($arrTeamSummary, $arrTeamSummary[5]) ? $arrTeamSummary[5] : 0;
        $otherCoveredShopsMtdCount = isset($arrTeamSummary, $arrTeamSummary[6]) ? $arrTeamSummary[6] : 0;
        $assignedROCShopsCount = isset($arrTeamSummary, $arrTeamSummary[7]) ? $arrTeamSummary[7] : 0;
        $rocCoveredShopsTodayCount = isset($arrTeamSummary, $arrTeamSummary[8]) ? $arrTeamSummary[8] : 0;
        $rocCoveredShopsMtdCount = isset($arrTeamSummary, $arrTeamSummary[9]) ? $arrTeamSummary[9] : 0;

        $arrOtherLabelList1 = array(
            array(
                "label" => $this->arrSummaryLabels["TOTAL_TIME_SPENT"],
                "value" => $timeSpentToday,
            ),
            array(
                "label" => $this->arrSummaryLabels["OTHER_OUTLET_GROCERY"],
                "value" => (string) $todayOtherOutletGroceryCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["OTHER_OUTLET_RETAIL"],
                "value" => (string) $todayOtherOutletRetailCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["OTHER_OUTLET_WHOLESALE"],
                "value" => (string) $todayOtherOutletWholesaleCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["ROC_SELLIN_SHOPS"],
                "value" => (string) $todayROCSellinShopsCount,
            ),
        );

        // Output summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
            $arrOtherLabelList1
        );

        $arrOtherLabelList2 = array(
            array(
                "label" => $this->arrSummaryLabels["COVERED_TODAY"],
                "value" => (string) $otherCoveredShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_MTD"],
                "value" => (string) $otherCoveredShopsMtdCount,
            ),
        );

        // Output summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            $this->arrSummaryLabels["FEEDER_MARKET_SUMMARY"],
            $arrOtherLabelList2
        );

        $arrOtherLabelList3 = array(
            array(
                "label" => $this->arrSummaryLabels["TOTAL_SHOPS"],
                "value" => (string) $assignedROCShopsCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_TODAY"],
                "value" => (string) $rocCoveredShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_MTD"],
                "value" => (string) $rocCoveredShopsMtdCount,
            ),
        );

        // Output summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            $this->arrSummaryLabels["ROC_DELIVERY"],
            $arrOtherLabelList3
        );

        // Output leaderboard for E-Cal branch
        $branchId = $this->getTeamBranch($dbName, $teamId);
        if ($dbName === $ITC_DB && $branchId == 13) {
            // Get the current month and year
            $currentMonth = date('m');
            $currentYear = date('Y');

            $arrTeamLeaderBoard = $this->tableUtil->getRowsColumns(
                "$dbName.tblleaderboard AS lb",
                "AVG(para1_score) AS avg_para1, AVG(para2_score) AS avg_para2, AVG(para3_score) AS avg_para3, AVG(para4_score) AS avg_para4, AVG(total_score) AS avg_percentage, team_id, branch_id, (SELECT team_name FROM $dbName.tblproject_team WHERE team_id = lb.team_id) AS team_name",
                "lb.dstatus = 0 AND lb.branch_id = $branchId AND MONTH(lb.capture_date) = $currentMonth AND YEAR(lb.capture_date) = $currentYear GROUP BY lb.team_id ORDER BY avg_percentage DESC"
            );

            $arrLeaderboardList = array();
            $arrLoggedinUserRankDetail = array();

            // Get requested team leaderboard details
            foreach ($arrTeamLeaderBoard as $key => $leaderboardDetail) {
                if ($leaderboardDetail[5] == $teamId) {
                    $loggedInUserRank = $key + 1; // Adding 1 to convert from array index to rank
                    $formattedPercentage = sprintf("%.2f", $leaderboardDetail[4]);

                    $arrLoggedinUserRankDetail = array(
                        "rank" => "#$loggedInUserRank",
                        "dsName" => $leaderboardDetail[7],
                        "totalScore" => $formattedPercentage,
                        "scoreParameters" => array(
                            array(
                                "para1Label" => "QAtt",
                                "para1Value" => sprintf("%.1f", $leaderboardDetail[0]),
                                "para2Label" => "DAtt",
                                "para2Value" => sprintf("%.1f", $leaderboardDetail[1]),
                                "para3Label" => "UOB",
                                "para3Value" => sprintf("%.1f", $leaderboardDetail[2]),
                                "para4Label" => "B-Adh",
                                "para4Value" => sprintf("%.1f", $leaderboardDetail[3])
                            )
                        )
                    );
                    break;
                }
            }

            // Get Top 10 on leaderboard
            for ($i = 0; $i < min(10, count($arrTeamLeaderBoard)); $i++) {
                $leaderboardDetail = $arrTeamLeaderBoard[$i];
                $formattedPercentage = sprintf("%.2f", $leaderboardDetail[4]);

                $arrLeaderboardList[] = array(
                    "rank" => "#" . ($i + 1), // Adding 1 to convert from array index to rank
                    "dsName" => $leaderboardDetail[7],
                    "totalScore" => $formattedPercentage,
                    "scoreParameters" => array(
                        array(
                            "para1Label" => "QAtt",
                            "para1Value" => sprintf("%.1f", $leaderboardDetail[0]),
                            "para2Label" => "DAtt",
                            "para2Value" => sprintf("%.1f", $leaderboardDetail[1]),
                            "para3Label" => "UOB",
                            "para3Value" => sprintf("%.1f", $leaderboardDetail[2]),
                            "para4Label" => "B-Adh",
                            "para4Value" => sprintf("%.1f", $leaderboardDetail[3])
                        )
                    )
                );
            }

            // Add loggedin team rank
            if ($arrLoggedinUserRankDetail) {
                $arrLeaderboardList[] = $arrLoggedinUserRankDetail;
            }

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "🏆LEADERBOARD🏆",
                $arrLeaderboardList
            );
        }

        return $arrOtherSummary;
    }

    private function getItcnewSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        $arrOtherSummary = $arrOtherLabelList = $arrOtherCardList = array();

        if ($projectId == 59 || $projectId == 66 || $projectId == 67) {
            $iVisited = 0;
            $iBought = 0;

            $arrVisitedVsBought = $this->tableUtil->getRowsColumns(
                "$dbName.$respTable",
                "ques_4, COUNT(pro_id) AS total",
                "dstatus = 0 AND project_id = $projectId AND team_id = $teamId" .
                    " AND capture_date = '$date' AND ques_0 = 'Consumer Sales Details'" .
                    " GROUP BY ques_4"
            );

            foreach ($arrVisitedVsBought as $arrCall) {
                if ($arrCall[0] == "Yes") {
                    $iBought = (int) $arrCall[1];
                }
                $iVisited += (int) $arrCall[1];
            }

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CALLS"],
                    "value" => (string) $iVisited,
                ),
                array(
                    "label" => $this->arrSummaryLabels["PRODUCTIVE_CALLS"],
                    "value" => (string) $iBought,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrOtherLabelList
            );
        } elseif ($projectId == 118) {
            $iVisited = 0;
            $iBought = 0;
            $iNotBought = 0;
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
            $brand1 = "Sunfeast Marielite 225 gm @ 30 MRP.";
            $brand2 = "Moms Magic Cashew 200 gm @ 35 MRP";
            $brand3 = "Dark Fantasy 75gm @ 40 MRP";

            // Get Product bought or not count
            $arrVisitedVsBought = $this->tableUtil->getRowsColumns(
                "$dbName.tblresponse_sunfeastmarielite_d2d",
                "ques_7, COUNT(pro_id) AS total",
                "dstatus = 0 AND project_id = $projectId AND team_id = $teamId" .
                    " AND capture_date = '$date' AND ques_0 = 'Consumer Sales Details'" .
                    " GROUP BY ques_7"
            );

            foreach ($arrVisitedVsBought as $arrCall) {
                $call = $arrCall[0];
                if ($call == "ହଁ") {
                    $call = "Yes";
                } elseif ($call == "ନା") {
                    $call = "No";
                }

                if ($call == "Yes") {
                    $iBought = (int) $arrCall[1];
                } elseif ($call == "No") {
                    $iNotBought = (int) $arrCall[1];
                }
                $iVisited += (int) $arrCall[1];
            }

            // Get Sales qty for each product
            $arrSalesQty = $this->tableUtil->getRowsColumns(
                "$dbName.tblresponse_sunfeastmarielite_d2d",
                "ques_10, COUNT(pro_id) AS total",
                "dstatus = 0 AND project_id = $projectId AND team_id = $teamId" .
                    " AND capture_date = '$date' AND ques_0 = 'Consumer Sales Details'" .
                    " AND ques_7 = 'ହଁ' GROUP BY ques_10"
            );

            foreach ($arrSalesQty as $arrSales) {
                $brand = $arrSales[0];
                $count = $arrSales[1];

                if (strstr($brand, $brand1) !== false) {
                    $count1 = $count;
                } elseif (strstr($brand, $brand2) !== false) {
                    $count2 = $count;
                } elseif (strstr($brand, $brand3) !== false) {
                    $count3 = $count;
                }
            }

            $arrOtherCardList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CALLS"],
                    "value" => (string) $iVisited,
                ),
                array(
                    "label" => $this->arrSummaryLabels["PRODUCTIVE_CALLS"],
                    "value" => (string) $iBought,
                ),
                array(
                    "label" => $this->arrSummaryLabels["NON_PRODUCTIVE_CALLS"],
                    "value" => (string) $iNotBought,
                ),
                array(
                    "label" => $brand1,
                    "value" => (string) $count1,
                ),
                array(
                    "label" => $brand2,
                    "value" => (string) $count2,
                ),
                array(
                    "label" => $brand3,
                    "value" => (string) $count3,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                array(),
                $arrOtherCardList
            );
        } elseif (
            $respTable && $respTable != "tblsurvey_response_details" &&
            ($projectId == 111 || $projectId == 114 || $projectId == 115 ||
                $projectId == 116 || $projectId == 117 || $projectId == 123 ||
                $projectId == 124 || $projectId == 125 || $projectId == 126 ||
                $projectId == 127 || $projectId == 128 || $projectId == 129 ||
                $projectId == 139 || $projectId == 140 || $projectId == 157)
        ) {
            $currentDateTime = $this->commonFunctions->currentDateTime();
            // get if user has uploaded Opening Stock, Closing Stock, Sales Details data or not
            $arrOpeningClosingSalesDetails = $this->tableUtil->getRowColumns(
                "$dbName.$respTable",
                "MAX(CASE WHEN ques_0 = 'Opening Stock' THEN 'Yes' ELSE 'No' END)" .
                    " AS opening_stock_filled, MAX(CASE WHEN ques_0 = 'Closing Stock' THEN 'Yes'" .
                    " ELSE 'No' END) AS closing_stock_filled, MAX(CASE WHEN ques_0 = 'Sales Details'" .
                    " THEN 'Yes' ELSE 'No' END) AS sales_details_filled",
                "dstatus = 0 AND project_id = $projectId AND team_id = $teamId AND capture_date = '$date'"
            );

            // get leader board data
            $arrLeaderBoardDetails = $this->tableUtil->getRowsColumns(
                "$dbName.tbltimesheet AS ts",
                "AVG(ts.total_percentage) AS avg_percentage, team_id, (SELECT team_name FROM" .
                    " $dbName.tblproject_team WHERE team_id = ts.team_id) AS team_name",
                "ts.dstatus = 0 AND ts.project_id = $projectId GROUP BY ts.team_id" .
                    " ORDER BY avg_percentage DESC"
            );

            // Find requested team score
            $loggedInScoreAndRank = null;
            foreach ($arrLeaderBoardDetails as $key => $leaderboardDetail) {
                if ($leaderboardDetail[1] == $teamId) {
                    $loggedInUserRank = $key + 1; // Adding 1 to convert from array index to rank
                    $formattedPercentage = sprintf("%.2f", $leaderboardDetail[0]);
                    $loggedInScoreAndRank = "(" . $formattedPercentage . ") #" . $loggedInUserRank;
                    break;
                }
            }

            $arrLeaderboardCardList = array();
            // Loop through the top 5 teams
            for ($i = 0; $i < min(5, count($arrLeaderBoardDetails)); $i++) {
                $leaderboardDetail = $arrLeaderBoardDetails[$i];
                $formattedPercentage = sprintf("%.2f", $leaderboardDetail[0]);

                $rankLabel = "";
                // Assign emojis for ranks 1 through 5
                switch ($i) {
                    case 0:
                        $rankLabel = "🥇"; // Gold for Rank 1
                        break;
                    case 1:
                        $rankLabel = "🥈"; // Silver for Rank 2
                        break;
                    case 2:
                        $rankLabel = "🥉"; // Bronze for Rank 3
                        break;
                    case 3:
                        $rankLabel = "🎖️";
                        break;
                    case 4:
                        $rankLabel = "🎖️";
                        break;
                }

                $arrLeaderboardCardList[] = array(
                    // Adding 1 to convert from array index to rank
                    "label" => $rankLabel . " Rank-" . ($i + 1),
                    "value" => "(" . $formattedPercentage . ") " . $leaderboardDetail[2]
                );
            }

            // Include the rank of the requested user
            $arrLeaderboardCardList[] = array(
                "label" => "⭐ YOUR RANK",
                "value" => isset($loggedInScoreAndRank) && $loggedInScoreAndRank ? $loggedInScoreAndRank : "N/A"
            );

            $arrOtherCardList = array(
                array(
                    "label" => $this->arrSummaryLabels["OPENING_STOCK_FILLED"],
                    "value" => isset($arrOpeningClosingSalesDetails, $arrOpeningClosingSalesDetails[0]) ? $arrOpeningClosingSalesDetails[0] : "0",
                ),
                array(
                    "label" => $this->arrSummaryLabels["CLOSING_STOCK_FILLED"],
                    "value" => isset($arrOpeningClosingSalesDetails, $arrOpeningClosingSalesDetails[1]) ? $arrOpeningClosingSalesDetails[1] : "0",
                ),
                array(
                    "label" => $this->arrSummaryLabels["SALES_DETAILS"],
                    "value" => isset($arrOpeningClosingSalesDetails, $arrOpeningClosingSalesDetails[2]) ? $arrOpeningClosingSalesDetails[2] : "0",
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"] . ", " . $currentDateTime,
                array(),
                $arrOtherCardList
            );

            // Output leaderboard summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "🏆LEADERBOARD🏆",
                array(),
                $arrLeaderboardCardList
            );
        }

        return $arrOtherSummary;
    }

    private function getItcph2Summary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $jsonId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $ITCPH2_DB, $TBL_MOBILE_SUMMARY;
        $arrOtherSummary = array();
        if ($jsonId == 100 || $jsonId == 101) {
            // Get current date information
            $currentMonth = date('n'); // 1-12 (numeric representation of month)
            $currentYear = date('Y');  // 4-digit year

            // Create an empty array to store our dynamic month list
            $arrMonthFilterLabel = array();

            // Loop to generate the last 12 months (including current month)
            for ($i = 0; $i < 12; $i++) {
                // Calculate month and year for this iteration
                $monthNum = $currentMonth - $i;
                $yearNum = $currentYear;

                // Adjust for previous year if needed
                if ($monthNum <= 0) {
                    $monthNum += 12;
                    $yearNum -= 1;
                }

                // Convert month number to month name
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 1, $yearNum));
                $shortMonthName = date('M', mktime(0, 0, 0, $monthNum, 1, $yearNum));

                // Add to our array
                $arrMonthFilterLabel[] = array(
                    "label" => $shortMonthName . " " . $yearNum,
                    "value" => $monthName . "-" . $yearNum
                );
            }

            if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
                $this->requestGetData = $_GET;
            }

            if ($jsonId == 100) {
                //wd code
                $wdCode = ($dbName == $ITCPH2_DB) ? $this->tableUtil->getRowColumn("$dbName.tblproject_team", "wd_code", "team_id = $teamId") : null;
                $branchId = $this->tableUtil->getRowColumn("$dbName.tblproject_team", "branch_id", "dstatus = 0 AND wd_code = '$wdCode'");
                $arrDsType = $this->tableUtil->getRowsColumn("$dbName.tblproject_team", "is_type", "dstatus = 0 AND s_id = '99' AND wd_code = '$wdCode'", array(), true);
                $selectedDsType = isset($this->requestGetData['dsTypelist']) ? $this->requestGetData['dsTypelist'] : null;
                $dsTypesFilterConditions = "";
                if (!empty($selectedDsType)) {
                    $dsTypes = is_array($selectedDsType) ? $selectedDsType : explode(',', $selectedDsType);
                    $dsTypesList = "'" . implode("','", $dsTypes) . "'";
                    $dsTypesFilterConditions = " AND is_type IN ($dsTypesList)";
                }
                //teamlist
                $arrTeamDetails = $this->tableUtil->getRowsColumns("$dbName.tblproject_team", "team_id, team_name, branch_id", "dstatus = 0 AND wd_code = '$wdCode' AND s_id = 99 $dsTypesFilterConditions");
            } elseif ($jsonId == 101) {
                $branchId = $this->tableUtil->getRowColumn("$dbName.tblproject_team", "branch_id", "dstatus = 0 AND team_id = $teamId AND s_id = 101");
                $section = $this->tableUtil->getRowColumn("$dbName.tblproject_team", "section", "dstatus = 0 AND team_id = $teamId AND s_id = 101");
                $arrWdDetails = $this->tableUtil->getRowsColumns("$dbName.tblproject_team", "wd_code", "dstatus = 0 AND section = '$section' AND s_id = 99", array(), true);
                $wdcodesFilterConditions = "";
                //wdlist
                $selectedWdCodes = isset($this->requestGetData['wdlist']) ? $this->requestGetData['wdlist'] : null;
                if (!empty($selectedWdCodes)) {
                    $wdcodes = is_array($selectedWdCodes) ? $selectedWdCodes : explode(',', $selectedWdCodes);
                    $wdcodesList = "'" . implode("','", $wdcodes) . "'";
                    $wdcodesFilterConditions = " AND wd_code IN ($wdcodesList)";
                }
                $arrDsType = $this->tableUtil->getRowsColumn("$dbName.tblproject_team", "is_type", "dstatus = 0 AND section = '$section' $wdcodesFilterConditions", array(), true);
                $selectedDsType = isset($this->requestGetData['dsTypelist']) ? $this->requestGetData['dsTypelist'] : null;
                $dsTypesFilterConditions = "";
                if (!empty($selectedDsType)) {
                    $dsTypes = is_array($selectedDsType) ? $selectedDsType : explode(',', $selectedDsType);
                    $dsTypesList = "'" . implode("','", $dsTypes) . "'";
                    $dsTypesFilterConditions = " AND is_type IN ($dsTypesList)";
                }
                //teamlist
                $arrTeamDetails = $this->tableUtil->getRowsColumns("$dbName.tblproject_team", "team_id, team_name", "dstatus = 0 AND section = '$section' AND s_id = 99 $wdcodesFilterConditions $dsTypesFilterConditions");
            }

            //productlist
            $arrProductDetails = $this->tableUtil->getRowsColumns("$dbName.tblbranch_pickupstock_products", "product_name, summary_column_name", "dstatus = 0 AND branch_id = $branchId");

            // Define Team Type list for dropdown
            foreach ($arrDsType as $dsType) {
                // Check if $dsType is an array or a single value
                $dsTypeValue = is_array($dsType) ? $dsType[0] : $dsType;

                if ($dsTypeValue == 0) {
                    $type = "DS";
                } elseif ($dsTypeValue == 1) {
                    $type = "Niches";
                } elseif ($dsTypeValue == 2) {
                    $type = "Town SWD";
                } elseif ($dsTypeValue == 3) {
                    $type = "Hybrid";
                } elseif ($dsTypeValue == 4) {
                    $type = "SCP";
                } elseif ($dsTypeValue == 5) {
                    $type = "NPSR";
                }

                $arrDsTypeList[] = [
                    "label" => $type,
                    "value" => (string)$dsTypeValue,
                ];
            }

            // Define team list for dropdown
            foreach ($arrTeamDetails as $team) {
                $arrTeamList[] = [
                    "label" => $team[1],  // Use the team name
                    "value" => (string)$team[0], // Use the team ID as value
                ];
            }
            // Define product list for dropdown
            foreach ($arrProductDetails as $product) {
                $arrProductList[] = [
                    "label" => $product[0],  // product name
                    "value" => (string)$product[1], // column name
                ];
            }
            if ($jsonId == 101) {
                // Define wd list for dropdown
                foreach ($arrWdDetails as $wd) {
                    $arrWdList[] = [
                        "label" => $wd[0],  // Use the wd name
                        "value" => (string)$wd[0], // Use the wd as value
                    ];
                }
            }

            $filters = [
                'selectedMonth' => isset($this->requestGetData['monthlist']) ? $this->requestGetData['monthlist'] : null,
                'selectedWd' => isset($this->requestGetData['wdlist']) ? $this->requestGetData['wdlist'] : null,
                'selectedDsType' => isset($this->requestGetData['dsTypelist']) ? $this->requestGetData['dsTypelist'] : null,
                'selectedTeam' => isset($this->requestGetData['teamlist']) ? $this->requestGetData['teamlist'] : null,
                'selectedProduct' => isset($this->requestGetData['productlist']) ? $this->requestGetData['productlist'] : null,
            ];


            // Initialize condition variables at the top, before any conditional logic
            $dsTypeConditions = "";
            $wdcodesConditions = "";
            $teamConditions = "";
            $whereConditions = "AND dstatus = 0";
            if (!empty($filters['selectedMonth'])) {
                $selectedValue = $filters['selectedMonth']; // e.g., "February-2024"
                // Split the value into month and year
                list($selectedMonthName, $selectedYear) = explode('-', $selectedValue); // "February", "2024"
                // Convert the month name to a numeric format
                $selectedMonth = date('m', strtotime($selectedMonthName)); // e.g., "02"
                // Generate the first day of the selected month
                $firstDayOfSelectedMonth = "$selectedYear-$selectedMonth-01";
                // Calculate the first and last day of the range (including the previous two months)
                $startDate = date('Y-m-01', strtotime("-2 months", strtotime($firstDayOfSelectedMonth))); // First day of the range
                $endDate = date('Y-m-t', strtotime($firstDayOfSelectedMonth)); // Last day of the selected month
                // Add date range condition
                $whereConditions .= " AND (capture_date >= '$startDate' AND capture_date <= '$endDate')";
            } else {
                // If no month is selected, use the current month as the default
                $currentYear = date('Y');
                $currentMonth = date('m');

                $firstDayOfCurrentMonth = "$currentYear-$currentMonth-01";
                $startDate = date('Y-m-01', strtotime("-2 months", strtotime($firstDayOfCurrentMonth)));
                $endDate = date('Y-m-t', strtotime($firstDayOfCurrentMonth));

                $whereConditions .= " AND (capture_date >= '$startDate' AND capture_date <= '$endDate')";
            }

            // Create month list
            $arrMonthList = array();
            $date = $startDate;
            while ($date < $endDate) {
                $monthNo = date("n", strtotime($date));
                $yearNo = date("Y", strtotime($date));
                if (!isset($arrMonthList[$monthNo])) {
                    $arrMonthList[$monthNo] = $yearNo;
                }
                $date = date("Y-m-d", strtotime("$date +1 month"));
            }

            $arrWeeks = array(1, 2, 3, 4);

            //Data based on WdCode
            if (!empty($filters['selectedWd'])) {
                $wdcodes = is_array($filters['selectedWd']) ? $filters['selectedWd'] : explode(',', $filters['selectedWd']);
                $wdcodesList = "'" . implode("','", $wdcodes) . "'";
                $arrTeamIds = $this->tableUtil->getRowsColumn("$dbName.tblproject_team", "team_id", "dstatus = 0 AND wd_code IN ($wdcodesList) AND s_id = 99");
                $teamIds = is_array($arrTeamIds) ? $arrTeamIds : explode(',', $arrTeamIds);
                $teamList = "'" . implode("','", $teamIds) . "'";
                $wdcodesConditions = " AND team_id IN ($teamList)";
            }

            //Data based on DSType
            if (!empty($filters['selectedDsType'])) {
                $dsTypes = is_array($filters['selectedDsType']) ? $filters['selectedDsType'] : explode(',', $filters['selectedDsType']);
                $dsList = "'" . implode("','", $dsTypes) . "'";
                $arrTeamIds = $this->tableUtil->getRowsColumn("$dbName.tblproject_team", "team_id", "dstatus = 0 AND is_type IN ($dsList) AND s_id = 99");
                $teamIds = is_array($arrTeamIds) ? $arrTeamIds : explode(',', $arrTeamIds);
                $teamList = "'" . implode("','", $teamIds) . "'";
                $dsTypeConditions = " AND team_id IN ($teamList)";
            }

            // Handle multiple values for selectedTeam
            if (!empty($filters['selectedTeam'])) {
                $teams = is_array($filters['selectedTeam']) ? $filters['selectedTeam'] : explode(',', $filters['selectedTeam']);
                $teamList = "'" . implode("','", $teams) . "'";
                $teamConditions = " AND team_id IN ($teamList)";
            } else {
                if (!empty($arrTeamDetails)) {
                    $allTeamIds = array_map(function ($team) {
                        return $team[0]; // Get the team_id from the numeric array
                    }, $arrTeamDetails);

                    // Join the team IDs into a string for the SQL IN condition
                    $teamList = "'" . implode("','", $allTeamIds) . "'";
                    $teamConditions = " AND team_id IN ($teamList)";
                }
            }

            // Handle multiple values for selectedProduct
            if (!empty($filters['selectedProduct'])) {
                $productColumns = is_array($filters['selectedProduct']) ? $filters['selectedProduct'] : explode(',', $filters['selectedProduct']);

                // No need to prepend 'total_sale_' as the column names are already full (e.g., total_sale_product1)
                $summaryColumns = implode(" + ", $productColumns);
            } else {
                if (!empty($arrProductDetails)) {
                    // Corrected part: Extract summary_column_name correctly from the numeric array
                    $allProductColumns = array_map(function ($product) {
                        return $product[1]; // Get the summary_column_name from the numeric array
                    }, $arrProductDetails);

                    // Build the SUM expression from the available product columns
                    $summaryColumns = implode(" + ", $allProductColumns);
                }
            }

            // Create 3 month query
            $sMonthBaseQuery = "SELECT 1 AS type, capture_date, COUNT(DISTINCT ques_3) as uob, SUM($summaryColumns) AS TotalSale FROM $dbName.tblsurvey_response_details WHERE ($summaryColumns) > 0 $teamConditions $dsTypeConditions $wdcodesConditions";
            $arrMonthwiseQuery = array();
            foreach ($arrMonthList as $month => $year) {
                $monthStartDate = date("Y-m-d", strtotime("$year-$month-01"));
                $monthLastDate = date("Y-m-d", strtotime("last day of $monthStartDate"));

                $dateCond = " AND capture_date BETWEEN '$monthStartDate' AND '$monthLastDate'";
                $arrMonthwiseQuery[] = $sMonthBaseQuery . $dateCond;
            }
            $sMonthwiseQuery = implode(" UNION ALL ", $arrMonthwiseQuery);


            // Weekly Base query
            $sWeekBaseQuery = "SELECT 2 AS type, capture_date, COUNT(DISTINCT ques_3) as uob, SUM($summaryColumns) AS TotalSale FROM $dbName.tblsurvey_response_details 
            WHERE ($summaryColumns) > 0 $teamConditions $dsTypeConditions $wdcodesConditions";
            // Initialize the week-wise query array
            $arrWeekwiseQuery = array();
            // Loop through the months
            foreach ($arrMonthList as $month => $year) {
                // Calculate the last day of the month
                $monthStartDate = date("Y-m-d", strtotime("$year-$month-01"));
                $monthLastDate = date("Y-m-d", strtotime("last day of $monthStartDate"));
                // Week 1: 1st to 7th
                $weekStartDate = "$year-$month-01";
                $weekEndDate = "$year-$month-07";
                $arrWeekwiseQuery[] = $sWeekBaseQuery . " AND capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                // Week 2: 8th to 14th
                $weekStartDate = "$year-$month-08";
                $weekEndDate = "$year-$month-14";
                $arrWeekwiseQuery[] = $sWeekBaseQuery . " AND capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                // Week 3: 15th to 21st
                $weekStartDate = "$year-$month-15";
                $weekEndDate = "$year-$month-21";
                $arrWeekwiseQuery[] = $sWeekBaseQuery . " AND capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                // Week 4: 22nd to the last day of the month
                $weekStartDate = "$year-$month-22";
                $arrWeekwiseQuery[] = $sWeekBaseQuery . " AND capture_date BETWEEN '$weekStartDate' AND '$monthLastDate'";
            }
            // Combine all weekly queries
            $sWeekwiseQuery = implode(" UNION ALL ", $arrWeekwiseQuery);

            $UobAndSalesQuery = "$sMonthwiseQuery UNION ALL $sWeekwiseQuery UNION ALL
            SELECT 3 AS type, capture_date, COUNT(DISTINCT ques_3) as uob, SUM($summaryColumns) AS TotalSale FROM $dbName.tblsurvey_response_details WHERE $summaryColumns > 0 $whereConditions $teamConditions $dsTypeConditions $wdcodesConditions GROUP BY capture_date";
            $UobAndSalesAction = null;
            $UobAndSalesRows = 0;
            $this->dbConn->ExecuteSelectQuery($UobAndSalesQuery, $UobAndSalesAction, $UobAndSalesRows);
            // Initialize the data array
            $monthwiseUobdata = $weekwiseUobdata = $daywiseUobdata = [];
            while ($row = $this->dbConn->GetData($UobAndSalesAction)) {
                $type = $row['type'];
                $capture_date = $row['capture_date'];
                $uob = $row['uob'];
                $order = $row['TotalSale'];

                if ($capture_date) {
                    $monthNo = date("n", strtotime($capture_date));
                    $day = date("j", strtotime($capture_date));
                    $weekNo = $day <= 7 ? 1 : ($day <= 14 ? 2 : ($day <= 21 ? 3 : 4));

                    // Month wise data
                    if ($type == 1) {
                        if (!isset($monthwiseUobdata[$monthNo])) {
                            $monthwiseUobdata[$monthNo] = array(
                                "uob" => 0,
                                "order" => 0,
                            );
                        }
                        $monthwiseUobdata[$monthNo]["uob"] += $uob;
                        $monthwiseUobdata[$monthNo]["order"] += $order;
                    } elseif ($type == 2) {
                        // Weekwise data
                        if (!isset($weekwiseUobdata[$monthNo][$weekNo])) {
                            $weekwiseUobdata[$monthNo][$weekNo] = array(
                                "uob" => 0,
                                "order" => 0,
                            );
                        }
                        $weekwiseUobdata[$monthNo][$weekNo]["uob"] += $uob;
                        $weekwiseUobdata[$monthNo][$weekNo]["order"] += $order;
                    } else {
                        // daywise data
                        if (!isset($daywiseUobdata[$monthNo][$day])) {
                            $daywiseUobdata[$monthNo][$day] = array(
                                "uob" => 0,
                                "order" => 0,
                            );
                        }
                        $daywiseUobdata[$monthNo][$day]["uob"] += $uob;
                        $daywiseUobdata[$monthNo][$day]["order"] += $order;
                    }
                }
            }

            $arrFinalData = array();
            foreach (array_keys($arrMonthList) as $month) {
                $year = $arrMonthList[$month];
                $arrWeekData = array();

                foreach ($arrWeeks as $week) {
                    $arrDaysData = array();

                    if ($week == 1) {
                        $startDay = 1;
                        $endDay = 7;
                    } elseif ($week == 2) {
                        $startDay = 8;
                        $endDay = 14;
                    } elseif ($week == 3) {
                        $startDay = 15;
                        $endDay = 21;
                    } else {
                        $startDay = 22;
                        $endDay = date("t", strtotime("$year-$month-01"));
                    }

                    for ($i = $startDay; $i <= $endDay; $i++) {
                        $arrDaysData[] = array(
                            "date" => date("Y-m-d", strtotime("$year-$month-$i")),
                            "uob" => isset($daywiseUobdata[$month][$i]) ? (string) $daywiseUobdata[$month][$i]["uob"] : "0",
                            "order" => isset($daywiseUobdata[$month][$i]) ? (string) $daywiseUobdata[$month][$i]["order"] : "0",
                        );
                    }

                    $arrWeekData[] = array(
                        "week" => "Week $week",
                        "weekTotalUob" => isset($weekwiseUobdata[$month][$week]) ? (string) $weekwiseUobdata[$month][$week]["uob"] : "0",
                        "weekTotalOrder" => isset($weekwiseUobdata[$month][$week]) ? (string) $weekwiseUobdata[$month][$week]["order"] : "0",
                        "dataset" => $arrDaysData,
                    );
                }

                $arrFinalData[] = array(
                    "month" => date("M", strtotime("$year-$month-01")),
                    "monthTotalUob" => isset($monthwiseUobdata[$month]) ? (string) $monthwiseUobdata[$month]["uob"] : "0",
                    "monthTotalOrder" => isset($monthwiseUobdata[$month]) ? (string)number_format((float)$monthwiseUobdata[$month]["order"], 1) : "0",

                    "weeks" => $arrWeekData,
                );
            }

            // Prepare the final output
            $arrOtherSummary = [
                "uobdata" => [
                    "months" => $arrFinalData,
                ]
            ];

            if ($jsonId == 100) {
                // Organize the lists under the 'filters' key
                $arrOtherSummary["filters"] = array(
                    "monthlist" => $arrMonthFilterLabel,
                    "wdlist" => $arrWdList,
                    "dsTypelist" => $arrDsTypeList,
                    "teamlist" => $arrTeamList,
                    "productlist" => $arrProductList
                );
            } elseif ($jsonId == 101) {
                // Organize the lists under the 'filters' key
                $arrOtherSummary["filters"] = array(
                    "monthlist" => $arrMonthFilterLabel,
                    "wdlist" => $arrWdList,
                    "dsTypelist" => $arrDsTypeList,
                    "teamlist" => $arrTeamList,
                    "productlist" => $arrProductList
                );
            }
        } elseif ($jsonId == 10) {
            $teamType = $this->tableUtil->getRowColumn(
                "$dbName.tblproject_team",
                "is_type",
                "dstatus = 0 AND team_id = $teamId"
            );
            $pannedOutlets = $coverdOutlets = "";
            // Get DS route and outlet id for getting the team id with MDO work's today
            $route_outletId = $this->tableUtil->getRowColumns("$dbName.tblsurvey_response_details_mdo", "ques_2, ques_4, type", "dstatus = 0 AND ques_0 = 'Outlet Survey' AND team_id = $teamId AND capture_date = '$date'");
            if ($this->commonFunctions->isNonEmptyArray($route_outletId)) {
                $arrRouteDetails = json_decode($route_outletId[0], true);
                $route = isset($arrRouteDetails[2]) ? $arrRouteDetails[2] : "";
                $outletId = isset($route_outletId[1]) ? $route_outletId[1] : "";
                $type = $route_outletId[2];
                if ($type == 6 || $type == 8 || $type == 9) {
                    $dsId = $outletId ? $this->tableUtil->getRowColumn("$dbName.tblroute_details_breeze", "team_id", "dstatus = 0 AND rec_id = $outletId") : "";
                    $pannedOutlets = $dsId ? $this->tableUtil->getRowColumn("$dbName.tblroute_details_breeze", "COUNT(rec_id)", "dstatus = 0 AND team_id = '$dsId'") : "";
                } else {
                    $dsId = $outletId ? $this->tableUtil->getRowColumn("$dbName.tblroute_details", "team_id", "dstatus = 0 AND route_name = '$route' AND rec_id = $outletId") : "";
                    $pannedOutlets = $dsId ? $this->tableUtil->getRowColumn("$dbName.tblroute_details", "COUNT(rec_id)", "dstatus = 0 AND route_name = '$route' AND team_id = $dsId") : "";
                }
            }
            $coverdOutlets = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "COUNT(DISTINCT ques_4)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
            $productiveOutlets = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "COUNT(DISTINCT ques_4)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND ques_5 > 0");
            $surveyQty = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "SUM(ques_5)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
            $attendanceDetails = $this->tableUtil->getRowColumn("$dbName.tblattendance", "other_details", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '0'");
            $arrOtherDetails = !empty($attendanceDetails) ? json_decode($attendanceDetails, true) : [];
            $workingWith = isset($arrOtherDetails['workingWith']) ? $arrOtherDetails['workingWith'] : "";
            if ($workingWith == 'Market work with AE' || $workingWith == 'Market work with GT TL' || $workingWith == 'Independent market work') {
                $startTime = $this->tableUtil->getRowColumn("$dbName.tblattendance", "MIN(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '0'");
                $endTime = $this->tableUtil->getRowColumn("$dbName.tblattendance", "MIN(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                $timeSpent = $endTime ? $this->commonFunctions->getTimeDifference($startTime, $endTime, false, false, true) : 0;
                $distanceInKm = $this->tableUtil->getRowColumn("$dbName.tblattendance", "distance", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
            } else {
                $min_max_time = $this->tableUtil->getRowColumns("$dbName.tblsurvey_response_details_mdo", "MIN(capture_datetime), MAX(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
                if ($this->commonFunctions->isNonEmptyArray($min_max_time) && (!empty($min_max_time[0]) || !empty($min_max_time[1]))) {
                    $timeSpent = $this->commonFunctions->getTimeDifference($min_max_time[0], $min_max_time[1], false, false, true);
                    $distanceInKm = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "distance_in_meter", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' ORDER BY pro_id DESC");
                } else {
                    $startTime = $this->tableUtil->getRowColumn("$dbName.tblattendance", "MIN(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '0'");
                    $endTime = $this->tableUtil->getRowColumn("$dbName.tblattendance", "MIN(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                    $timeSpent = $this->commonFunctions->getTimeDifference($startTime, $endTime, false, false, true);
                    $distanceInKm = $this->tableUtil->getRowColumn("$dbName.tblattendance", "distance", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                }
            }

            // $distanceInKm = isset($distance) ? (string)round($distance / 1000, 2) : "0";
            $Query = "SELECT type, COUNT(DISTINCT CONCAT(ds_name, '_', DATE(capture_date))) AS cnt FROM $dbName.tblattendance WHERE dstatus = 0 AND team_id = $teamId AND capture_date LIKE '%$month%' AND type IN (0, 2, 5, 6, 8, 9) GROUP BY type";
            $sAction = null;
            $sRows = 0;
            $this->dbConn->ExecuteSelectQuery($Query, $sAction, $sRows);
            // Default values (in case a type is missing from the result)
            $vanDsMtdCount    = 0;
            $swdMtdCount      = 0;
            $npsrMtdCount     = 0;
            $rmdDsMtdCount    = 0;
            $stokiestMtdCount = 0;
            $fmcgMtdCount     = 0;

            if ($sRows > 0) {
                while ($row = $this->dbConn->GetData($sAction)) {
                    switch ($row['type']) {
                        case 0:
                            $vanDsMtdCount    = $row['cnt'];
                            break;
                        case 2:
                            $swdMtdCount      = $row['cnt'];
                            break;
                        case 5:
                            $npsrMtdCount     = $row['cnt'];
                            break;
                        case 6:
                            $rmdDsMtdCount    = $row['cnt'];
                            break;
                        case 8:
                            $stokiestMtdCount = $row['cnt'];
                            break;
                        case 9:
                            $fmcgMtdCount     = $row['cnt'];
                            break;
                    }
                }
            }
            // print_r($teamId);die;
            $gtTlCount = $this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT capture_date)", "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '%$month%' AND work_with = 2");
            $aeCount = $this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT capture_date)", "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '%$month%' AND work_with = 1");
            $independentCount = $this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT capture_date)", "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '%$month%' AND work_with = 3");
            $arrWdcodes = $this->tableUtil->getRowsColumn("$dbName.tblattendance", "wd_code", "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '%$month%'", array(), true);
            $arrWdcodeData = array();
            foreach ($arrWdcodes as $wdcode) {
                $wdCodeName = $wdcode;
                $arrWdcodeData[] = [
                    "label" => $wdCodeName,
                    "value" => (string)$this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT ds_name)", "dstatus = 0 AND wd_code = '$wdCodeName' AND team_id = $teamId AND capture_date LIKE '%$month%'"),
                ];
            }

            // Build dsTypeDistributionData conditionally
            if ($teamType == 10) {
                $dsTypeDistributionData = [
                    [
                        "pieDataTittle" => "Market Work Information (MTD)",
                        "pieInternalTittle" => "Total Count",
                        "label" => "Common FMCG Lite DS",
                        "value" => isset($fmcgMtdCount) ? (string)$fmcgMtdCount : "0",
                        "color" => "#9400D3"
                    ],
                ];
            } else {
                $dsTypeDistributionData = [
                    [
                        "pieDataTittle" => "Market Work Information (MTD)",
                        "pieInternalTittle" => "Total Count",
                        "label" => "VAN DS",
                        "value" => isset($vanDsMtdCount) ? (string)$vanDsMtdCount : "0",
                        "color" => "#9400D3"
                    ],
                    [
                        "label" => "RMD",
                        "value" => isset($rmdDsMtdCount) ? (string)$rmdDsMtdCount : "0",
                        "color" => "#073763"
                    ],
                    [
                        "label" => "SCP DS",
                        "value" => isset($stokiestMtdCount) ? (string)$stokiestMtdCount : "0",
                        "color" => "#660000"
                    ],
                    [
                        "label" => "GT TL",
                        "value" => isset($gtTlCount) ? (string)$gtTlCount : "0",
                        "color" => "#eb4034"
                    ],
                    [
                        "label" => "AE",
                        "value" => isset($aeCount) ? (string)$aeCount : "0",
                        "color" => "#741B47"
                    ],
                    [
                        "label" => "Independent Work",
                        "value" => isset($independentCount) ? (string)$independentCount : "0",
                        "color" => "#6AA84F"
                    ],
                ];
            }

            // Final output summary
            $arrOtherSummary[] = [
                "mdoSurveyData" => [
                    [
                        "typeofview" => "Progress",
                        "label" => "Outlet Covered VS Outlets Planned",
                        "value1" => $coverdOutlets ? (string)$coverdOutlets : "0",
                        "value2" => $pannedOutlets ? (string)$pannedOutlets : "0"
                    ],
                    [
                        "label" => "Time Spent",
                        "value" => isset($timeSpent) ? (string)$timeSpent : "0s",
                        "icon"  => "time"
                    ],
                    [
                        "label" => "Distance",
                        "value" => isset($distanceInKm) ? (string)$distanceInKm : "0s",
                        "icon"  => "distance"
                    ],
                ],
                "dsTypeDistributionData" => $dsTypeDistributionData,
                "wdCodeData" => $arrWdcodeData
            ];
        } else {
            $arrTodaySummary = $this->tableUtil->getRowColumns(
                "$dbName.tblmobile_summary",
                "planned_outlets, oulet_covered_today, sell_in_shops_count_today, total_sales_today, time_spent_today, total_meter_travelled, planned_outlets_mtd, oulet_covered_mtd" .
                    ", sell_in_shops_count_mtd, total_sales_mtd, add_oulet_covered_today, add_oulet_covered_mtd, other_sell_in_shops_count_today, other_sell_in_shops_count_mtd",
                "team_id = $teamId AND rcd = '$date'"
            );
            $todaySummaryKey0 = isset($arrTodaySummary[0]) ? $arrTodaySummary[0] : 0;
            $todaySummaryKey1 = isset($arrTodaySummary[1]) ? $arrTodaySummary[1] : 0;
            $todaySummaryKey2 = isset($arrTodaySummary[2]) ? $arrTodaySummary[2] : 0;
            $todaySummaryKey3 = isset($arrTodaySummary[3]) ? $arrTodaySummary[3] : 0;
            $todaySummaryKey4 = isset($arrTodaySummary[4]) ? $arrTodaySummary[4] : 0;
            $todaySummaryKey5 = isset($arrTodaySummary[5]) ? $arrTodaySummary[5] : 0;
            $todaySummaryKey6 = isset($arrTodaySummary[6]) ? $arrTodaySummary[6] : 0;
            $todaySummaryKey7 = isset($arrTodaySummary[7]) ? $arrTodaySummary[7] : 0;
            $todaySummaryKey8 = isset($arrTodaySummary[8]) ? $arrTodaySummary[8] : 0;
            $todaySummaryKey9 = isset($arrTodaySummary[9]) ? $arrTodaySummary[9] : 0;
            $todaySummaryKey10 = isset($arrTodaySummary[10]) ? $arrTodaySummary[10] : 0;
            $todaySummaryKey11 = isset($arrTodaySummary[11]) ? $arrTodaySummary[11] : 0;
            $todaySummaryKey12 = isset($arrTodaySummary[12]) ? $arrTodaySummary[12] : 0;
            $todaySummaryKey13 = isset($arrTodaySummary[13]) ? $arrTodaySummary[13] : 0;
            if ($todaySummaryKey0 > 0 && $todaySummaryKey1 > 0) {
                $percentage = (isset($todaySummaryKey0, $todaySummaryKey1) && $todaySummaryKey0 > 0)
                    ? round(($todaySummaryKey1 / $todaySummaryKey0) * 100, 0)
                    : 0;
            }
            if ($todaySummaryKey7 > 0 && $todaySummaryKey6 > 0) {
                $percentageMtd = (isset($todaySummaryKey6, $todaySummaryKey7) && $todaySummaryKey6 > 0)
                    ? round(($todaySummaryKey7 / $todaySummaryKey6) * 100, 0)
                    : 0;
            }
            // if ($arrTodaySummary[5] > 0) {
            $totalMeterTravelled = isset($todaySummaryKey5) ? round($todaySummaryKey5 / 1000, 2) : 0;
            // }
            $todayOutletCovered = ($todaySummaryKey1 ?? 0) + ($todaySummaryKey10 ?? 0);
            $mtdOutletCovered = ($todaySummaryKey7 ?? 0) + ($todaySummaryKey11 ?? 0);
            $sellInShopCount = ($todaySummaryKey2 ?? 0) + ($todaySummaryKey12 ?? 0);
            $sellInShopCountMtd = ($todaySummaryKey8 ?? 0) + ($todaySummaryKey13 ?? 0);
            $filteredValue = isset($todaySummaryKey4) ? preg_replace('/\s*\d+s/', '', (string)$todaySummaryKey4) : '0s';
            $arrOtherLabelList1 = array(
                array(
                    "label" => "Outlets covered VS Outlets planned",
                    "value1" => (string)$todayOutletCovered,
                    "value2" => (string)isset($todaySummaryKey0) ? $todaySummaryKey0 : 0,
                    "percentage" => isset($percentage) ? $percentage : 0,
                    "typeofview" => "Progress",
                ),
                array(
                    "label" => "Productive Outlets",
                    "value" => "$sellInShopCount" . "/" . "$todaySummaryKey0",
                    "typeofview" => "Simple",
                    "icon" => "store"
                ),
                array(
                    "label" => "Survey Qty (M)",
                    "value" => (string) isset($todaySummaryKey3) ? round($todaySummaryKey3, 1) : 0,
                    "typeofview" => "Simple",
                    "icon" => "sale"
                ),
                array(
                    "label" => "Time spent",
                    "value" => isset($filteredValue) && $filteredValue !== '' ? (string) $filteredValue : "0s",
                    "typeofview" => "Simple",
                    "icon" => "time"
                ),
                array(
                    "label" => "Distance",
                    "value" => isset($totalMeterTravelled) ? (string) $totalMeterTravelled . " Km" : "0 Km",
                    "typeofview" => "Simple",
                    "icon" => "distance"
                ),
            );


            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrOtherLabelList1
            );

            $arrOtherLabelList2 = array(
                array(
                    "label" => "Outlets covered VS Outlets planned",
                    "value1" => (string)$mtdOutletCovered,
                    "value2" => (string)$todaySummaryKey6,
                    "percentage" => isset($percentageMtd) ? $percentageMtd : 0,
                    "typeofview" => "Progress"
                ),
                array(
                    "label" => "Productive Outlets",
                    "value" => "$sellInShopCountMtd" . "/" . "$todaySummaryKey6",
                    "typeofview" => "Simple",
                    "icon" => "store"
                ),
                array(
                    "label" => "Survey Qty (M)",
                    "value" => (string) isset($todaySummaryKey9) ? round($todaySummaryKey9, 1) : 0,
                    "typeofview" => "Simple",
                    "icon" => "sale"
                )
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "Month’s Summary (MTD)",
                $arrOtherLabelList2
            );

            $chartResponse = $this->tableUtil->getRowColumns("$dbName.tblmobile_summary", "chart_response_current_month, chart_response_previous_month", "team_id = $teamId AND rcd = '$date'");
            $jsonResponseOfCurrentMonth = $chartResponse ? json_decode($chartResponse[0], true) : array();
            $jsonResponseOfPreviousMonth = $chartResponse ? json_decode($chartResponse[1], true) : array();
            // Define an array to hold the formatted dataset
            $datasetCurrentMonth = [];
            $datasetPreviousMonth = [];

            // Loop through the jsonResponse to reformat each data point
            foreach ($jsonResponseOfCurrentMonth as $dataPoint) {
                $datasetCurrentMonth[] = [
                    "x" => (string) $dataPoint['x'], // Convert to string if necessary
                    "y" => $dataPoint['y']
                ];
            }
            // Loop through the jsonResponse to reformat each data point
            foreach ($jsonResponseOfPreviousMonth as $dataPoint) {
                $datasetPreviousMonth[] = [
                    "x" => (string) $dataPoint['x'], // Convert to string if necessary
                    "y" => $dataPoint['y']
                ];
            }
            $arrOtherLabelList3 = array(
                array(
                    "xAxislabels" => "Date",
                    "yAxislabels" => "Qty (M)",
                    "arrayofmultiline" => array(
                        array(
                            "dataset" => $datasetPreviousMonth,
                            "label" => "Last Month",
                            "colour" => "#9d9d94"
                        ),
                        array(
                            "dataset" => $datasetCurrentMonth,
                            "label" => "Current Month",
                            "colour" => "#00b300"
                        )
                    ),
                    "typeofview" => "Chart",
                    "charttype" => "MultiLine"
                )
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "Order Summary",
                $arrOtherLabelList3
            );

            $branchId = $this->getTeamBranch($dbName, $teamId);
            // Output leaderboard
            if ($dbName === $ITCPH2_DB) {
                $teamType = $this->tableUtil->getRowColumn(
                    "$dbName.tblproject_team",
                    "is_type",
                    "dstatus = 0 AND team_id = $teamId"
                );

                if ($teamType == 5) {
                    $leaderboardData = [];
                    $months = array(
                        date('Y-m', strtotime('-1 month')),
                        date('Y-m'),
                    );
                    //productlist
                    // $arrFocusProduct = $this->tableUtil->getRowsColumn("$dbName.tblbranch_pickupstock_products", "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' AND team_type = 5 AND branch_id = $branchId");
                    // $arrFocusProductName = $this->tableUtil->getRowsColumn("$dbName.tblbranch_pickupstock_products", "product_name", "dstatus = 0 AND is_focusbrand = '1' AND team_type = 5 AND branch_id = $branchId");
                    $minTotalShops =  (int) $this->tableUtil->getRowColumn("$dbName.tblconstants", "con_value", "con_name = 'minTotalShops' AND team_type = '$teamType'");
                    $minQualifiedAttendanceTimeInMin =  (int) $this->tableUtil->getRowColumn("$dbName.tblconstants", "con_value", "con_name = 'minWorkingTimeInMin' AND team_type = '$teamType'");
                    $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;
                    $focusProduct1 = "";
                    $focusProduct2 = "";
                    foreach ($months as $month) {
                        list($year, $newMonth) = explode('-', $month);

                        $arrFocusProduct = $this->tableUtil->getRowsColumn("$dbName.tblbranch_products_month_wise", "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' AND team_type = 5 AND branch_id = $branchId AND month = '$newMonth' AND year = '$year'");
                        $arrFocusProductName = $this->tableUtil->getRowsColumn("$dbName.tblbranch_products_month_wise", "product_name", "dstatus = 0 AND is_focusbrand = '1' AND team_type = 5 AND branch_id = $branchId AND month = '$newMonth' AND year = '$year'");

                        $overAllProduct = $this->tableUtil->getRowColumn("$dbName.tblbranch_products_month_wise", "summary_column_name", "dstatus = 0 AND is_focusbrand = '2' AND team_type = 5 AND branch_id = $branchId AND month = '$newMonth' AND year = '$year'");

                        if (isset($arrFocusProduct[0]) && $arrFocusProduct[0]) {
                            $focusBrand1TargetArr = $this->tableUtil->getRowColumn("$dbName.tblassign_target", "$arrFocusProduct[0]", "dstatus = 0 AND team_id = $teamId AND year = '$year' AND month = '$newMonth'");
                        }

                        if (isset($arrFocusProduct[1]) && $arrFocusProduct[1]) {
                            $focusBrand2TargetArr = $this->tableUtil->getRowColumn("$dbName.tblassign_target", "$arrFocusProduct[1]", "dstatus = 0 AND team_id = $teamId AND year = '$year' AND month = '$newMonth'");
                        }

                        if (isset($overAllProduct) && $overAllProduct) {
                            $overAllProductTargetArr = $this->tableUtil->getRowColumn("$dbName.tblassign_target", "$overAllProduct", "dstatus = 0 AND team_id = $teamId AND year = '$year' AND month = '$newMonth'");
                        }

                        $focusProduct1 = $arrFocusProductName[0] ?? "NA";
                        $focusProduct2 = $arrFocusProductName[1] ?? "NA";

                        $focusBrand1Target = isset($focusBrand1TargetArr) && $focusBrand1TargetArr ? $focusBrand1TargetArr : 0;

                        $focusBrand2Target = isset($focusBrand2TargetArr) && $focusBrand2TargetArr ? $focusBrand2TargetArr : 0;

                        $overAllProductTarget = isset($overAllProductTargetArr) && $overAllProductTargetArr ? $overAllProductTargetArr : 0;

                        $productCols = $this->tableUtil->getRowsColumn("$dbName.tblbranch_pickupstock_products", "summary_column_name", "dstatus = 0 AND team_type = 5 AND branch_id = $branchId");

                        $columnExpression = implode(" + ", $productCols);

                        $sumColumns = "SUM($columnExpression) AS total";

                        $focusBrand1 = $this->tableUtil->getRowsColumn("$dbName.tblvands_summary", "SUM($arrFocusProduct[0])", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(activity_date, '%Y-%m') = '$month'");
                        $focusBrand2 = $this->tableUtil->getRowsColumn("$dbName.tblvands_summary", "SUM($arrFocusProduct[1])", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(activity_date, '%Y-%m') = '$month'");
                        $overAllValue = $this->tableUtil->getRowColumn("$dbName.tblvands_summary", $sumColumns, "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(activity_date, '%Y-%m') = '$month'");
                        // print_r($sumColumns);die;

                        $qualifiedAttendanceCount = 0;

                        $datesInMonth = $this->tableUtil->getRowsColumn("$dbName.tblvands_summary", "activity_date", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(activity_date, '%Y-%m') = '$month'");

                        foreach ($datesInMonth as $activityDate) {
                            $orderShop = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details", "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = 0 AND capture_date = '$activityDate' AND team_id = $teamId");

                            $addShop = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details", "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = 0 AND capture_date = '$activityDate' AND team_id = $teamId");

                            $totalShops = (int)$orderShop + (int)$addShop;

                            $startEndTime = $this->tableUtil->getRowColumns("$dbName.tblvands_summary", "start_datetime, end_datetime", "dstatus = 0 AND team_id = $teamId AND activity_date = '$activityDate'");
                            // print_r($startEndTime);die;

                            $timeSpentInSec = $this->commonFunctions->getTimeDifference($startEndTime[0], $startEndTime[1], true);

                            $isQualified = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? 1 : 0;

                            if ($isQualified == 1) {
                                $qualifiedAttendanceCount++;
                            }
                        }
                        // $overAllValue = (isset($focusBrand1[0]) ? (float) $focusBrand1[0] : 0) + (isset($focusBrand2[0]) ? (float) $focusBrand2[0] : 0);

                        // Generate dynamic card items based on the month
                        $cardItems = [];
                        $progressItems = [];
                        $earnedPoints = 0;

                        for ($i = 1; $i <= 4; $i++) { // Creating 4 items per month
                            $title = "";
                            $color = "";
                            $icon = "";
                            $iconReward = "";
                            $target = 0;
                            $earnedMoney = 0;
                            $rewardMoney = 0;
                            $achieved = 0;
                            if ($i == 1) {
                                $title = "Gate/Qualified Attendance";
                                $color = "#F05000";
                                $achieved = $qualifiedAttendanceCount;
                                $target = 20;
                                $rewardMoney = 30;
                                $earnedMoney = $achieved;
                                $icon = "";
                            }
                            if ($i == 2) {
                                $title = "Overall Survey";
                                $color = "#FF0000";
                                $achieved = isset($overAllValue) && $overAllValue !== null ? round($overAllValue, 0) : 0;
                                $target = (float) $overAllProductTarget;
                                $rewardMoney = 1000;
                                if ($achieved > $target) {
                                    $earnedMoney = $rewardMoney;
                                } else {
                                    $earnedMoney = isset($achieved) && $target > 0 ? ($achieved / $target) * $rewardMoney : 0;
                                }
                                $icon = "https://upimg.btlmonitor.com/dspm_icon/ic_money_100.PNG";
                            }
                            if ($i == 3) {
                                $title = (string) $focusProduct1 . " Survey";
                                $color = "#0000FF";
                                $achieved = isset($focusBrand1[0]) ? (float) $focusBrand1[0] : 0;
                                $target = (float) $focusBrand1Target;
                                $rewardMoney = 500;
                                if ($achieved > $target) {
                                    $earnedMoney = $rewardMoney;
                                } else {
                                    $earnedMoney = isset($achieved) && $target > 0 ? ($achieved / $target) * $rewardMoney : 0;
                                }
                                $icon = "https://upimg.btlmonitor.com/dspm_icon/ic_rupees.PNG";
                            }
                            if ($i == 4) {
                                $title = (string) $focusProduct2 . " Survey";
                                $color = "#FF00FF";
                                $achieved = isset($focusBrand2[0]) ? (float)$focusBrand2[0] : 0;
                                $target = (float) $focusBrand2Target;
                                $rewardMoney = 500;
                                if ($achieved > $target) {
                                    $earnedMoney = $rewardMoney;
                                } else {
                                    $earnedMoney = isset($achieved) && $target > 0 ? ($achieved / $target) * $rewardMoney : 0;
                                }
                                $icon = "https://upimg.btlmonitor.com/dspm_icon/ic_rupees.PNG";
                            }
                            // Explicitly index the arrays
                            $cardItems[] = [
                                "LeaderBoardCardItem" => [
                                    "achieved" => $achieved,
                                    "target" => $target,
                                    "title" => $title,
                                    "color" => $color,
                                ],
                            ];

                            // Default progress item
                            $progressItem = [
                                "earnedMoney" => round($earnedMoney, 0),
                                "rewardMoney" => $rewardMoney,
                                "title" => $title,
                                "color" => $color,
                                "icon" => $icon,
                                "iconReward" => "",
                            ];

                            // Add capTarget and capTargetAchvd ONLY for Qualified Attendance
                            if ($i == 1) {
                                $progressItem["capTarget"] = 20;
                                $progressItem["capTargetAchvd"] = $achieved;
                            }

                            $progressItems[] = [
                                "LeaderBoardProgressItem" => $progressItem
                            ];

                            if ($i != 1) {
                                $earnedPoints += $earnedMoney;
                            }
                        }

                        $leaderboardData[] = array(
                            "LeaderBoardData" => array(
                                "earnedPoints" => $earnedPoints,
                                "maxPoints" => 2000,
                                "monthName" => date('M', strtotime($month)),
                                "cardItems" => $cardItems,
                                "progressItems" => $progressItems
                            ),
                        );
                    }
                    $arrOtherSummary[] = array(
                        "leaderBoardTitle" => "D.S.P.M",
                        "monthWiseLeaderboardData" => $leaderboardData
                    );
                } else {
                    // Get the current month and year
                    $currentMonth = date('m');
                    $currentYear = date('Y');

                    // Fetch leaderboard data
                    $arrTeamLeaderBoard = $this->tableUtil->getRowsColumns(
                        "$dbName.tbl_leaderboard_backup AS lb",
                        "qualifiedDays, ttldays, ttloutlets, uob, fb1uob, fb2uob, total_score, team_id, branch_id, (SELECT team_name FROM $dbName.tblproject_team WHERE team_id = lb.team_id) AS team_name",
                        "lb.dstatus = 0 AND lb.branch_id = $branchId AND MONTH(lb.capture_date) = $currentMonth AND YEAR(lb.capture_date) = $currentYear GROUP BY lb.team_id ORDER BY total_score DESC"
                    );

                    // Initialize arrays
                    $arrLeaderboardList = [];
                    $loggedInUserRank = null;

                    // Find the logged-in user's rank and details
                    foreach ($arrTeamLeaderBoard as $key => $leaderboardDetail) {
                        if ($leaderboardDetail[7] == $teamId) { // Match team_id
                            $loggedInUserRank = $key + 1; // Convert array index to rank
                            break;
                        }
                    }

                    // Prepare leaderboard data
                    if (!is_null($loggedInUserRank)) {
                        $aboveRank = $loggedInUserRank > 1 ? $loggedInUserRank - 1 : null;
                        $belowRank = $loggedInUserRank < count($arrTeamLeaderBoard) ? $loggedInUserRank + 1 : null;

                        // Top 3 ranks
                        for ($i = 0; $i < min(3, count($arrTeamLeaderBoard)); $i++) {
                            $leaderboardDetail = $arrTeamLeaderBoard[$i];
                            $arrLeaderboardList[] = $this->formatLeaderboardEntry(
                                $leaderboardDetail,
                                $i + 1,  // Rank
                                "$leaderboardDetail[0] / $leaderboardDetail[1]",
                                "$leaderboardDetail[3] / $leaderboardDetail[2]",
                                "$leaderboardDetail[4] / $leaderboardDetail[2]",
                                "$leaderboardDetail[5] / $leaderboardDetail[2]",
                                round($leaderboardDetail[6], 1) . '%',
                                null,    // Label
                                "#DAA521",
                                "medal_" . ($i + 1)
                            );
                        }

                        // Add Above Rank
                        if ($aboveRank) {
                            $leaderboardDetail = $arrTeamLeaderBoard[$aboveRank - 1]; // Get the correct entry
                            $arrLeaderboardList[] = $this->formatLeaderboardEntry(
                                $arrTeamLeaderBoard[$aboveRank - 1],
                                $aboveRank,
                                "$leaderboardDetail[0] / $leaderboardDetail[1]",
                                "$leaderboardDetail[3] / $leaderboardDetail[2]",
                                "$leaderboardDetail[4] / $leaderboardDetail[2]",
                                "$leaderboardDetail[5] / $leaderboardDetail[2]",
                                round($leaderboardDetail[6], 1) . '%',
                                null,
                                "#CACACA",
                                "medal_star"
                            );
                        }

                        // Add Your Rank
                        $leaderboardDetail = $arrTeamLeaderBoard[$loggedInUserRank - 1];
                        $arrLeaderboardList[] = $this->formatLeaderboardEntry(
                            $arrTeamLeaderBoard[$loggedInUserRank - 1],
                            $loggedInUserRank,
                            "$leaderboardDetail[0] / $leaderboardDetail[1]",
                            "$leaderboardDetail[3] / $leaderboardDetail[2]",
                            "$leaderboardDetail[4] / $leaderboardDetail[2]",
                            "$leaderboardDetail[5] / $leaderboardDetail[2]",
                            round($leaderboardDetail[6], 1) . '%',
                            "Your Rank",
                            "#ED6785",
                            "medal_star"
                        );

                        // Add After Your Rank
                        if ($belowRank) {
                            $leaderboardDetail = $arrTeamLeaderBoard[$belowRank - 1];
                            $arrLeaderboardList[] = $this->formatLeaderboardEntry(
                                $arrTeamLeaderBoard[$belowRank - 1],
                                $belowRank,
                                "$leaderboardDetail[0] / $leaderboardDetail[1]",
                                "$leaderboardDetail[3] / $leaderboardDetail[2]",
                                "$leaderboardDetail[4] / $leaderboardDetail[2]",
                                "$leaderboardDetail[5] / $leaderboardDetail[2]",
                                round($leaderboardDetail[6], 1) . '%',
                                null,
                                "#CACACA",
                                "medal_star"
                            );
                        }
                    }

                    $arrLeaderboardList = !empty($arrLeaderboardList) ? $arrLeaderboardList : [];
                    $showTitleOnly = empty($arrLeaderboardList); // Force title-only behavior if the list is empty

                    $arrOtherSummary[] = $this->getFormattedSummary(
                        $appType,
                        "🏆LEADERBOARD🏆",
                        $arrLeaderboardList,
                        [], // Keep other lists empty
                        [], // Keep other lists empty
                        [], // Keep other lists empty
                        $showTitleOnly
                    );
                }
            }
        }
        return $arrOtherSummary;
    }

    private function getSnplSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $jsonId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $TBL_ROUTE_DETAILS, $TBL_MOBILE_SUMMARY, $TBL_SURVEY_RESPONSE;

        $arrOtherSummary = array();

        $arrTeamSummary = $this->tableUtil->getRowColumns(
            "$dbName.$TBL_MOBILE_SUMMARY",
            "time_spent_today, grocery_count_today, retail_count_today, wholesale_count_today" .
                ", roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd" .
                ", roc_total_shops_count, roc_covered_today, roc_covered_mtd",
            "dstatus = 0 AND team_id = $teamId"
        );

        $timeSpentToday = isset($arrTeamSummary[0]) && $arrTeamSummary[0] ? $arrTeamSummary[0] : "0s";
        $iMadiraPasalCount = isset($arrTeamSummary[1]) && $arrTeamSummary[1] ? $arrTeamSummary[1] : 0;
        $iKhudraVikretaCount = isset($arrTeamSummary[2]) && $arrTeamSummary[2] ? $arrTeamSummary[2] : 0;
        $iThokVikretaCount = isset($arrTeamSummary[3]) && $arrTeamSummary[3] ? $arrTeamSummary[3] : 0;
        $rocCount = isset($arrTeamSummary[4]) && $arrTeamSummary[4] ? $arrTeamSummary[4] : 0;
        $otherCoveredShopsTodayCount = isset($arrTeamSummary[5]) && $arrTeamSummary[5] ? $arrTeamSummary[5] : 0;
        $otherCoveredShopsMtdCount = isset($arrTeamSummary[6]) && $arrTeamSummary[6] ? $arrTeamSummary[6] : 0;
        $assignedROCShopsCount = isset($arrTeamSummary[7]) && $arrTeamSummary[7] ? $arrTeamSummary[7] : 0;
        $rocCoveredShopsTodayCount = isset($arrTeamSummary[8]) && $arrTeamSummary[8] ? $arrTeamSummary[8] : 0;
        $rocCoveredShopsMtdCount = isset($arrTeamSummary[9]) && $arrTeamSummary[9] ? $arrTeamSummary[9] : 0;

        $arrOtherLabelList1 = array(
            array(
                "label" => "कुल समय बिताया",
                "value" => (string) $timeSpentToday,
            ),
            array(
                "label" => "मदिरा पसल",
                "value" => (string) $iMadiraPasalCount,
            ),
            array(
                "label" => "खुद्रा बिक्रेता पसल",
                "value" => (string) $iKhudraVikretaCount,
            ),
            array(
                "label" => "थोक बिक्रेता पसल",
                "value" => (string) $iThokVikretaCount,
            ),
            array(
                "label" => "रुटमा बिक्री गरिएका पसलहरु",
                "value" => (string) $rocCount,
            ),
        );

        // Output today's summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            "आजको सारांश",
            $arrOtherLabelList1
        );

        $arrOtherLabelList2 = array(
            array(
                "label" => "आजको कभरेज",
                "value" => (string) $otherCoveredShopsTodayCount,
            ),
            array(
                "label" => "यस महिना हालसम्मको कभरेज",
                "value" => (string) $otherCoveredShopsMtdCount,
            ),
        );

        // Output "Other Outlet" summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            "नयाँ पसलहरुको सारांश",
            $arrOtherLabelList2
        );

        $arrOtherLabelList3 = array(
            array(
                "label" => "कुल पसल संख्या",
                "value" => (string) $assignedROCShopsCount,
            ),
            array(
                "label" => "आज कभर गरिएका पसलहरु",
                "value" => (string) $rocCoveredShopsTodayCount,
            ),
            array(
                "label" => "यस महिना हालसम्म कभर गरिएका पसलहरु",
                "value" => (string) $rocCoveredShopsMtdCount,
            ),
        );

        // Output "ROC Delivery" summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            "रुटको बिक्री",
            $arrOtherLabelList3
        );

        // Outut absent cycle DSS names
        if ($jsonId == 2) {
            $rsAbsentCycleDssAction = null;
            $iAbsentCycleDssActionRows = 0;
            $sAbsentCycleDssQuery = "SELECT outlet_name_eng FROM $dbName.$TBL_ROUTE_DETAILS" .
                " WHERE team_id = $teamId and cycle_dss = '1' AND dstatus = 0 AND rec_id NOT IN" .
                " (SELECT DISTINCT shop_id FROM $dbName.$TBL_SURVEY_RESPONSE WHERE" .
                " capture_date = '$date' and ques_0 = 'रुटको बिक्री' AND ques_5 = 'हाजिरी' AND dstatus = 0);";
            $this->dbConn->ExecuteSelectQuery(
                $sAbsentCycleDssQuery,
                $rsAbsentCycleDssAction,
                $iAbsentCycleDssActionRows
            );

            if ($iAbsentCycleDssActionRows > 0) {
                $arrDSSNamesLabelList = array();
                while ($rowAbsentCycleDss = $this->dbConn->GetData($rsAbsentCycleDssAction)) {
                    $arrDSSNamesLabelList[] = array(
                        "label" => $rowAbsentCycleDss["outlet_name_eng"],
                        "value" => "",
                    );
                }

                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    $this->arrSummaryLabels["ABSENT_CYCLE_DSS"],
                    $arrDSSNamesLabelList
                );
            }
        }

        return $arrOtherSummary;
    }

    private function getSouthSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $TBL_VANDS_SUMMARY;

        $arrOtherSummary = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        // Get today's count
        $arrTeamTodaySummary = $this->tableUtil->getRowColumns(
            "$dbName.$TBL_VANDS_SUMMARY",
            "start_datetime, end_datetime, total_deliveries, total_sellin_shops" .
                ", total_town_shops, total_rural_shops, total_village_shops, total_planned_shops",
            "dstatus = 0 AND team_id = $teamId AND activity_date = '$date'"
        );

        // Get Month count
        $arrTeamMonthSummary = $this->tableUtil->getRowColumns(
            "$dbName.$TBL_VANDS_SUMMARY",
            "SUM(total_deliveries) AS totalCalls, SUM(total_sellin_shops) AS productiveCalls",
            "dstatus = 0 AND team_id = $teamId AND activity_date LIKE '$month'"
        );

        $timeSpentToday = isset($arrTeamTodaySummary, $arrTeamTodaySummary[0]) ?
            $this->commonFunctions->getTimeDifference(
                $arrTeamTodaySummary[0],
                $arrTeamTodaySummary[1],
                false,
                false,
                true
            ) : "0s";
        $coveredShopsTodayCount = isset($arrTeamTodaySummary, $arrTeamTodaySummary[2]) ?
            $arrTeamTodaySummary[2] : 0;
        $productiveShopsTodayCount = isset($arrTeamTodaySummary, $arrTeamTodaySummary[3]) ?
            $arrTeamTodaySummary[3] : 0;
        $totalPlanned = isset($arrTeamTodaySummary, $arrTeamTodaySummary[7]) ?
            $arrTeamTodaySummary[7] : 0;

        $coveredTownShopsTodayCount = isset($arrTeamTodaySummary, $arrTeamTodaySummary[4]) ?
            $arrTeamTodaySummary[4] : 0;
        $coveredRuralShopsTodayCount = isset($arrTeamTodaySummary, $arrTeamTodaySummary[5]) ?
            $arrTeamTodaySummary[5] : 0;
        $coveredVillageShopsTodayCount = isset($arrTeamTodaySummary, $arrTeamTodaySummary[6]) ?
            $arrTeamTodaySummary[6] : 0;

        $coveredShopsMtdCount = isset($arrTeamMonthSummary, $arrTeamMonthSummary[0]) ?
            $arrTeamMonthSummary[0] : 0;
        $productiveShopsMtdCount = isset($arrTeamMonthSummary, $arrTeamMonthSummary[1]) ?
            $arrTeamMonthSummary[1] : 0;

        $arrOtherTodayLabelList = array(
            array(
                "label" => $this->arrSummaryLabels["TOTAL_TIME_SPENT"],
                "value" => $timeSpentToday,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_TODAY"],
                "value" => (string) $coveredShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_TOWN_SHOPS"],
                "value" => (string) $coveredTownShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_RURAL_SHOPS"],
                "value" => (string) $coveredRuralShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED_VILLAGE_SHOPS"],
                "value" => (string) $coveredVillageShopsTodayCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["PRODUCTIVE_TODAY"],
                "value" => (string) $productiveShopsTodayCount,
            ),
        );

        // Output today's summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
            $arrOtherTodayLabelList
        );

        $arrOtherMonthlyLabelList = array(
            array(
                "label" => $this->arrSummaryLabels["TOTAL_PLANNED"],
                "value" => (string) (isset($totalPlanned) ? $totalPlanned : 0),
            ),
            array(
                "label" => $this->arrSummaryLabels["COVERED"],
                "value" => (string)  $coveredShopsMtdCount,
            ),
            array(
                "label" => $this->arrSummaryLabels["PRODUCTIVE"],
                "value" => (string) $productiveShopsMtdCount,
            ),
        );

        // Output monthly summary
        $arrOtherSummary[] = $this->getFormattedSummary(
            $appType,
            $this->arrSummaryLabels["MONTHLY_SUMMARY"],
            $arrOtherMonthlyLabelList
        );

        return $arrOtherSummary;
    }

    private function getImpactSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        $arrOtherSummary = $arrOtherLabelList = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        if ($projectId == 53) {
            $totalCustomerConverted = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'Sales Details'"
            );
            $arrAttendance = $this->tableUtil->getRowColumns(
                "$dbName.$respTable",
                "pro_id, ques_7",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND ques_1 = 'Attendance'"
            );
            $isReturnStockAcknowledgementPhoto = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'DayEnd Report'"
            );

            $isStockAcknowledgementPhoto = isset($arrAttendance, $arrAttendance[0]) && $arrAttendance[0] ?
                true : false;
            $competitorStockDetails = isset($arrAttendance, $arrAttendance[1]) &&
                $arrAttendance[1] ? json_decode($arrAttendance[1], true) : array();
            $iCompetitorStockDetailsFilled = array_sum(
                $this->commonFunctions->getGridDataAsArray($competitorStockDetails, 1, 7, true, true)[0]
            );

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CUSTOMER_CONVERTED"],
                    "value" => (string) $totalCustomerConverted,
                ),
                array(
                    "label" => $this->arrSummaryLabels["PIC_TAKEN_RECEIVING_STOCK"],
                    "value" => $isStockAcknowledgementPhoto ?
                        $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                ),
                array(
                    "label" => $this->arrSummaryLabels["PIC_TAKEN_RETURN_STOCK"],
                    "value" => $isReturnStockAcknowledgementPhoto ?
                        $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                ),
                array(
                    "label" => $this->arrSummaryLabels["COMPETITOR_BRAND_DETAILS"],
                    "value" => $iCompetitorStockDetailsFilled > 0 ?
                        $this->arrSummaryLabels["YES"] : $this->arrSummaryLabels["NO"],
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
                $arrOtherLabelList
            );
        } elseif ($projectId == 65) {
            $totalConnect = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS totalConnect",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 IN ('D2D Visit','No Response') AND dup_processed = 1 AND dup_status = 5"
            );

            $totalInteration = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS totalInteration",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'D2D Visit' AND dup_processed = 1 AND dup_status = 5"
            );

            $arrTotalConversionAndSales = $this->tableUtil->getRowColumns(
                "$dbName.$respTable",
                "COUNT(pro_id) AS totalConversion, SUM(ques_12) AS totalSales",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes'" .
                    " AND dup_processed = 1 AND dup_status = 5"
            );
            $totalConversion = isset($arrTotalConversionAndSales[0]) && $arrTotalConversionAndSales[0] ?
                $arrTotalConversionAndSales[0] : 0;
            $totalSales = isset($arrTotalConversionAndSales[1]) && $arrTotalConversionAndSales[1] ?
                $arrTotalConversionAndSales[1] : 0;

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CONNECT"],
                    "value" => (string) $totalConnect,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_INTERACTION"],
                    "value" => (string) $totalInteration,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CONVERSION"],
                    "value" => (string) $totalConversion,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_SALES"],
                    "value" => (string) $totalSales
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrOtherLabelList
            );
        } elseif ($projectId == 80) {
            // Get summary
            $arrMobileSummary = $this->getPreDefinedSummary($dbName, $teamId, $date);

            if ($arrMobileSummary) {
                $arrOtherSummary[] = $arrMobileSummary;
            }
        }

        return $arrOtherSummary;
    }

    private function getNovicemarcomSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        $arrOtherSummary = $arrOtherLabelList = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        if ($projectId == 23) {
            $totalInteration = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS totalInteration",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'D2D Visit'"
            );

            $arrTotalConversionAndSales = $this->tableUtil->getRowColumns(
                "$dbName.$respTable",
                "COUNT(pro_id) AS totalConversion, SUM(ques_12) AS totalSales",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes'"
            );
            $totalConversion = isset($arrTotalConversionAndSales[0]) && $arrTotalConversionAndSales[0] ?
                $arrTotalConversionAndSales[0] : 0;
            $totalSales = isset($arrTotalConversionAndSales[1]) && $arrTotalConversionAndSales[1] ?
                $arrTotalConversionAndSales[1] : 0;

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_INTERACTION"],
                    "value" => (string) $totalInteration,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_CONVERSION"],
                    "value" => (string) $totalConversion,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_SALES"],
                    "value" => (string) $totalSales
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
                $arrOtherLabelList
            );
        } elseif ($projectId == 26) {
            $totalMechanicsEnrolled = 0;
            $totalAppInstalled = 0;
            $totalRepeatVisit = 0;

            $rsSummaryAction = null;
            $iSummaryActionRows = 0;
            $sSummaryQuery = "SELECT ques_3, ques_11, COUNT(pro_id) AS total FROM $dbName.$respTable" .
                " WHERE pid = $projectId AND team_id = $teamId AND capture_date = '$date'" .
                " AND ques_1 = 'Mechanic Visit Report' GROUP BY ques_3, ques_11";
            $this->dbConn->ExecuteSelectQuery($sSummaryQuery, $rsSummaryAction, $iSummaryActionRows);

            if ($iSummaryActionRows > 0) {
                while ($rowSummary = $this->dbConn->GetData($rsSummaryAction)) {
                    $ques_3 = $rowSummary['ques_3'];
                    $ques_11 = $rowSummary['ques_11'];
                    $total = $rowSummary['total'];

                    if ($ques_3 == 'New Enrollment') {
                        $totalMechanicsEnrolled += $total;

                        if ($ques_11 == 'Yes') {
                            $totalAppInstalled += $total;
                        }
                    } elseif ($ques_3 == 'Repeat Visit') {
                        $totalRepeatVisit += $total;
                    }
                }
            }

            $arrOtherLabelList1 = array(
                array(
                    "label" => $this->arrSummaryLabels["TODAYS_SCAN"],
                    "value" => "",
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_NEW_GARAGES_VISITED"],
                    "value" => (string) $totalMechanicsEnrolled,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_NEW_APP_INSTALLED"],
                    "value" => (string) $totalAppInstalled,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_REPEAT_VISIT"],
                    "value" => (string) $totalRepeatVisit
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrOtherLabelList1
            );

            $arrOtherLabelList2 = array(
                array(
                    "label" => $this->arrSummaryLabels["SCAN_MTD"],
                    "value" => "",
                ),
                array(
                    "label" => $this->arrSummaryLabels["REGISTRATIONS_MTD"],
                    "value" => "",
                ),
                array(
                    "label" => $this->arrSummaryLabels["REPEAT_VISIT_MTD"],
                    "value" => "",
                ),
                array(
                    "label" => $this->arrSummaryLabels["MECHANIC_MEETS"],
                    "value" => ""
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["MTD_SUMMARY"],
                $arrOtherLabelList2
            );
        }

        return $arrOtherSummary;
    }

    private function getWonderSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        $arrOtherSummary = $arrOtherLabelList = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        if ($clientId != 27) {
            $totalReccee = $this->tableUtil->getRowColumn(
                "$dbName.$respTable AS a, $dbName.tblshops AS b",
                "COUNT(a.pro_id) AS total",
                "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $teamId" .
                    " AND a.capture_date = '$date' AND a.ques_1 = '1'" .
                    " AND a.ques_14 = b.id AND a.ques_17 = '1' $otherSummaryCond"
            );

            $totalInstallation = $this->tableUtil->getRowColumn(
                "$dbName.$respTable AS a, $dbName.tblshops AS b",
                "COUNT(a.pro_id) AS total",
                "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $teamId" .
                    " AND a.capture_date = '$date' AND a.ques_1 = '2'" .
                    " AND a.ques_18 = b.id AND a.ques_20 = '1' $otherSummaryCond"
            );

            $totalDirectInstallation = $this->tableUtil->getRowColumn(
                "$dbName.$respTable AS a, $dbName.tblshops AS b",
                "COUNT(a.pro_id) AS total",
                "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $teamId" .
                    " AND a.capture_date = '$date' AND a.ques_1 = '3'" .
                    " AND a.ques_21 = b.id AND a.ques_24 = '1' $otherSummaryCond"
            );

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_RECCEE"],
                    "value" => (string) $totalReccee
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_INSTALLATION"],
                    "value" => (string) $totalInstallation
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_DIRECT_INSTALLATION"],
                    "value" => (string) $totalDirectInstallation
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
                $arrOtherLabelList
            );
        }

        return $arrOtherSummary;
    }

    private function getZxSummary(
        $appType,
        $dbName,
        $clientId,
        $projectId,
        $teamId,
        $otherSummaryCond,
        $date,
        $month,
        $respTable
    ) {
        global $TBL_ROUTE_DETAILS, $TBL_HAWKER_MOBILE_SUMMARY;

        $arrOtherSummary = $arrOtherLabelList = array();
        $currentDateTime = $this->commonFunctions->currentDateTime();
        if ($projectId == 39) {
            $target = $this->tableUtil->getRowColumn(
                "$dbName.$TBL_ROUTE_DETAILS",
                "COUNT(rec_id) AS total",
                "dstatus = 0 AND team_id = $teamId"
            );
            $completed = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(DISTINCT ques_2) AS total",
                "dstatus = 0 AND team_id = $teamId" .
                    " AND capture_date LIKE '$month' $otherSummaryCond"
            );
            $completed = $completed ? $completed : 0;

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TARGET"],
                    "value" => (string) $target,
                ),
                array(
                    "label" => $this->arrSummaryLabels["COMPLETED"],
                    "value" => (string) $completed,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["MTD_SUMMARY"],
                $arrOtherLabelList
            );
        } elseif ($projectId == 46) {
            $completedFirstCall = 0;
            $completedSecondCall = 0;

            $target = $this->tableUtil->getRowColumn(
                "$dbName.$TBL_ROUTE_DETAILS",
                "COUNT(rec_id) AS total",
                "dstatus = 0 AND team_id = $teamId"
            );

            $arrCompleted = $this->tableUtil->getRowsColumns(
                "$dbName.$respTable",
                "ques_1, COUNT(DISTINCT ques_2) AS total",
                "dstatus = 0 AND team_id = $teamId AND ques_1 in ('First Call', 'Second Call')" .
                    " AND capture_date LIKE '$month' $otherSummaryCond GROUP BY ques_1"
            );

            if ($this->commonFunctions->isNonEmptyArray($arrCompleted)) {
                foreach ($arrCompleted as $arrCall) {
                    if ($arrCall[0] == 'First Call') {
                        $completedFirstCall = (int) $arrCall[1];
                    } elseif ($arrCall[0] == 'Second Call') {
                        $completedSecondCall = (int) $arrCall[1];
                    }
                }
            }

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TARGET"],
                    "value" => (string) $target,
                ),
                array(
                    "label" => $this->arrSummaryLabels["FIRST_CALL_COMPLETED"],
                    "value" => (string) $completedFirstCall,
                ),
                array(
                    "label" => $this->arrSummaryLabels["SECOND_CALL_COMPLETED"],
                    "value" => (string) $completedSecondCall,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["MTD_SUMMARY"],
                $arrOtherLabelList
            );

            // Show Static text on top
            $this->arrTopSummary[] = $this->getFormattedSummary(
                $appType,
                "Attention: Cycle Plan 2080: Month Ashwin\r\n\r\nObjective 1:\r\n\r\n100% Availability In TM Outlet." .
                    "\r\nA) S Arctic Burst\r\nB) SLB\r\nC) S Dual Burst\r\nD) Naulo\r\n\r\nObjective 2:\r\n\r\n" .
                    "90% Availability of any 1 TSM(PSU) in TM Outlet.\r\nA) CTD- Counter Top Dispenser.\r\n" .
                    "B) GFD- Gravity Fed Dispenser.\r\nC) SLD- Slider",
                array(),
                array(),
                array(),
                array(),
                true
            );
        } elseif ($projectId == 67) {
            $iVisited = 0;
            $iSampled = 0;

            $arrVisitedVsSampled = $this->tableUtil->getRowsColumns(
                "$dbName.$respTable",
                "ques_8, COUNT(pro_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'" .
                    " AND dup_processed = 1 AND dup_status = 5 GROUP BY ques_8"
            );

            foreach ($arrVisitedVsSampled as $arrCall) {
                if ($arrCall[0] == "Yes") {
                    $iSampled = (int) $arrCall[1];
                }
                $iVisited += (int) $arrCall[1];
            }

            $arrOtherLabelList = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_HOUSE_VISITED"],
                    "value" => (string) $iVisited,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_SAMPLED_HOUSE"],
                    "value" => (string) $iSampled,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"] . ":" . $currentDateTime,
                $arrOtherLabelList
            );
        } elseif (
            $projectId == 74 || $projectId == 76 || $projectId == 77 ||
            $projectId == 78 || $projectId == 79
        ) {
            // Get summary
            $arrMobileSummary = $this->getPreDefinedSummary($dbName, $teamId, $date, $TBL_HAWKER_MOBILE_SUMMARY);

            if ($arrMobileSummary) {
                $arrOtherSummary[] = $arrMobileSummary;
            }
        } elseif ($projectId == 84) {
            // Get summary
            $arrMobileSummary = $this->getPreDefinedSummary($dbName, $teamId, $date);

            if ($arrMobileSummary) {
                $arrOtherSummary[] = $arrMobileSummary;
            }
        } elseif ($projectId == 100) {
            $todayTotalReccee = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(DISTINCT shop_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND rcd = '$date'"
            );
            $todayTotalInstallation = $this->tableUtil->getRowColumn(
                "$dbName.tblresponse_retail_merchandising_installation",
                "COUNT(DISTINCT shop_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND rcd = '$date'"
            );
            $overallTotalReccee = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(DISTINCT shop_id) AS total",
                "dstatus = 0 AND team_id = $teamId"
            );
            $overallTotalInstallation = $this->tableUtil->getRowColumn(
                "$dbName.tblresponse_retail_merchandising_installation",
                "COUNT(DISTINCT shop_id) AS total",
                "dstatus = 0 AND team_id = $teamId"
            );

            $arrOtherLabelList1 = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_RECCEE"],
                    "value" => (string) $todayTotalReccee,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_INSTALLATION"],
                    "value" => (string) $todayTotalInstallation,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["TODAYS_SUMMARY"],
                $arrOtherLabelList1
            );

            $arrOtherLabelList2 = array(
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_RECCEE"],
                    "value" => (string) $overallTotalReccee,
                ),
                array(
                    "label" => $this->arrSummaryLabels["TOTAL_INSTALLATION"],
                    "value" => (string) $overallTotalInstallation,
                ),
            );

            // Output summary
            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                $this->arrSummaryLabels["OVERALL_SUMMARY"],
                $arrOtherLabelList2
            );
        } elseif ($projectId == 110) {
            $currentStartDate = date("Y-m-01"); // First day of the current month
            $currentEndDate = date("Y-m-d"); // Current date
            $totalOutlet = $this->tableUtil->getRowColumn("$dbName.tblroute_details", "COUNT(DISTINCT village) AS totalOutlet", "dstatus = 0 AND team_id = $teamId");
            $todayCount = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "COUNT(state) AS total", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
            $mtdCount = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "COUNT(state) AS total", "dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'");
            $stockCountComapToday1 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_15) AS sum", "dstatus = 0 AND ques_9 = 'Ok' AND team_id = $teamId AND capture_date = '$date'");
            $stockCountComapMtd1 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_15) AS sum1", "dstatus = 0 AND ques_9 = 'Ok' AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'");
            $stockCountVistaToday1 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_16) AS sum2", "dstatus = 0 AND ques_9 = 'Ok' AND team_id = $teamId AND capture_date = '$date'");
            $stockCountVistaMtd1 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_16) AS sum3", "dstatus = 0 AND ques_9 = 'Ok' AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'");

            $stockCountComapToday2 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_16) AS sum", "dstatus = 0 AND ques_9 = 'Need Repair' AND team_id = $teamId AND capture_date = '$date'");
            $stockCountComapMtd2 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_16) AS sum1", "dstatus = 0 AND ques_9 = 'Need Repair' AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'");
            $stockCountVistaToday2 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_17) AS sum2", "dstatus = 0 AND ques_9 = 'Need Repair' AND team_id = $teamId AND capture_date = '$date'");
            $stockCountVistaMtd2 = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_merchantdisingph3", "SUM(ques_17) AS sum3", "dstatus = 0 AND ques_9 = 'Need Repair' AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'");
            $stockCountComapToday = $stockCountComapToday1 + $stockCountComapToday2;
            $stockCountComapMtd = $stockCountComapMtd1 + $stockCountComapMtd2;
            $stockCountVistaToday = $stockCountVistaToday1 + $stockCountVistaToday2;
            $stockCountVistaMtd = $stockCountVistaMtd1 + $stockCountVistaMtd2;
            // $arrTableList = array(
            //     "header" => [
            //         "Visit Today",
            //         "MTD"
            //     ],
            //     "body" => [
            //         [
            //             "$todayCount",
            //             "$mtdCount"
            //         ],
            //     ],
            // );
            $arrResponse1 = array(
                array(
                    "progressType" => "linearProgress",
                    "toplabel" => "Unique Outlet Visit MTD",
                    // "bottomlabel" => "bottom label",
                    "value1" => "$mtdCount",
                    "value2" => "$totalOutlet",
                    "color1" => "#148f77",
                    "color2" => "#d35400",
                    // "bottomCardList" => array(
                    //     array(
                    //         "label" => "card1",
                    //         "value1" => "1",
                    //         "value2" => "3",
                    //     ),
                    //     array(
                    //         "label" => "card2",
                    //         "value1" => "1",
                    //         "value2" => "3"
                    //     )
                    // )
                ),
            );
            $arrResponse2 = array(
                array(
                    "progressType" => "circleProgress",
                    "toplabel" => "MLC's Summary",
                    // "bottomlabel" => "Sales this week",
                    "bottomCardList" => array(
                        array(
                            "label" => "Compact Stock Today / MTD",
                            "value1" => "$stockCountComapToday",
                            "value2" => "$stockCountComapMtd",
                            "icon" => "0xf05c0",
                            "iconcolor" => "#0000ff",
                            "color1" => "#ff0000",
                            "color2" => "#ffa500",
                        ),
                        array(
                            "label" => "Vista Stock Today / MTD",
                            "value1" => "$stockCountVistaToday",
                            "value2" => "$stockCountVistaMtd",
                            "icon" => "0xf05c0",
                            "iconcolor" => "#8e44ad",
                            "color1" => "#d35400",
                            "color2" => "#148f77",
                        ),
                    )
                ),

            );
            // $arrOtherLabelList = array(
            //     array(
            //         "label" => "Visit Today",
            //         "value" => (string) $todayCount,
            //     ),
            //     array(
            //         "label" => "MTD",
            //         "value" => (string) $mtdCount,
            //     ),
            // );
            // $arrTableList2 = array(
            //     "header" => [
            //         "Compact stock picked",
            //         "qty"
            //     ],
            //     "body" => [
            //         [
            //             "Today",
            //             "$stockCountComapToday"
            //         ],
            //         [
            //             "MTD",
            //             "$stockCountComapMtd"
            //         ],
            //     ],
            // );
            $arrOtherLabelList1 = array(
                array(
                    "label" => "Compact Stock Picked (Today)",
                    "value" => (string) $stockCountComapToday,
                ),
                array(
                    "label" => "Compact Stock Picked (MTD)",
                    "value" => (string) $stockCountComapMtd,
                ),
                array(
                    "label" => "Vista Double Stock Picked (Today)",
                    "value" => (string) $stockCountVistaToday,
                ),
                array(
                    "label" => "Vista Double Stock Picked (MTD)",
                    "value" => (string) $stockCountVistaMtd,
                ),
            );
            // $arrTableList3 = array(
            //     "header" => [
            //         "Vista Double stock picked",
            //         "qty"
            //     ],
            //     "body" => [
            //         [
            //             "Today",
            //             "$stockCountVistaToday"
            //         ],
            //         [
            //             "MTD",
            //             "$stockCountVistaMtd"
            //         ],
            //     ],
            // );
            $arrOtherLabelList2 = array(
                array(
                    "label" => "Today",
                    "value" => (string) $stockCountVistaToday,
                ),
                array(
                    "label" => "MTD",
                    "value" => (string) $stockCountVistaMtd,
                ),
            );

            // Initialize variables
            $rsAction = null;
            $iRows = 0;
            $skuValuesToday = [];
            $skuValuesMTD = [];

            // Mapping of row numbers to SKU names
            $skuMapping = [
                1 => "AC LIT",
                2 => "AC Code",
                3 => "Verve",
                4 => "Icon",
                5 => "Social Redline",
                6 => "Social 2Pod"
            ];

            // Query to fetch today's records
            $queryToday = "SELECT ques_2 FROM $dbName.tblsurvey_response_details_merchantdisingph3 
               WHERE dstatus = 0 AND team_id = $teamId AND capture_date = '$date'";
            $this->dbConn->ExecuteSelectQuery($queryToday, $rsAction, $iRows);

            // Process today's sales
            if ($iRows > 0) {
                while ($row = $this->dbConn->GetData($rsAction)) {
                    $ques_2 = $row['ques_2'];

                    // Decode JSON response in ques_2
                    $decodedResponse = json_decode($ques_2, true);

                    // Process the decoded response
                    foreach ($decodedResponse as $entry) {
                        $rowNo = $entry['rowNo'];
                        $ans = $entry['ans'];

                        // Map the ans to the corresponding SKU name
                        if (isset($skuMapping[$rowNo])) {
                            $skuName = $skuMapping[$rowNo];
                            $skuValuesToday[$skuName] += ($ans && is_numeric($ans) ? $ans : 0);
                        }
                    }
                }
            }

            // Reset variables for MTD query
            $rsAction = null;
            $iRows = 0;
            // Query to fetch MTD records
            $queryMTD = "SELECT ques_2 FROM $dbName.tblsurvey_response_details_merchantdisingph3 
             WHERE dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN '$currentStartDate' AND '$currentEndDate'";
            $this->dbConn->ExecuteSelectQuery($queryMTD, $rsAction, $iRows);

            // Process MTD sales
            if ($iRows > 0) {
                while ($row = $this->dbConn->GetData($rsAction)) {
                    $ques_2 = $row['ques_2'];

                    // Decode JSON response in ques_2
                    $decodedResponse = json_decode($ques_2, true);

                    // Process the decoded response
                    foreach ($decodedResponse as $entry) {
                        $rowNo = $entry['rowNo'];
                        $ans = $entry['ans'];

                        // Map the ans to the corresponding SKU name
                        if (isset($skuMapping[$rowNo])) {
                            $skuName = $skuMapping[$rowNo];
                            $skuValuesMTD[$skuName] += floatval($ans);
                        }
                    }
                }
            }

            // Prepare the table list with today's and MTD sales
            $arrTableList4 = [
                "header" => [
                    "Sku",
                    "Today Sales",
                    "MTD Sales",
                ],
                "body" => []
            ];

            // Populate the body with SKU values
            foreach ($skuMapping as $rowNo => $sku) {
                $todaySales = isset($skuValuesToday[$sku]) ? (string)$skuValuesToday[$sku] : "0";
                $mtdSales = isset($skuValuesMTD[$sku]) ? (string)$skuValuesMTD[$sku] : "0";

                $arrTableList4['body'][] = [$sku, $todaySales, $mtdSales];
            }

            // Output summary
            if ($appType == 1) {
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    $this->arrSummaryLabels["TODAYS_SUMMARY"],
                    $arrOtherLabelList
                );
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    "MLCS Summary",
                    $arrOtherLabelList1
                );
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    "",
                    $arrOtherLabelList2
                );
            } else {
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    "Progress bar",
                    array(),
                    array(),
                    array(),
                    $arrResponse1
                );
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    "MLCs Summary",
                    array(),
                    array(),
                    array(),
                    $arrResponse2
                );
                $arrOtherSummary[] = $this->getFormattedSummary(
                    $appType,
                    "Sale Details",
                    array(),
                    array(),
                    $arrTableList4
                );
            }
        } elseif ($projectId == 103) {
            $installation = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS total",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND ques_1 = 'Installation'"
            );

            $monitoring = $this->tableUtil->getRowColumn(
                "$dbName.$respTable",
                "COUNT(pro_id) AS total1",
                "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND ques_1 = 'Monitoring'"
            );

            $arrTableList = array(
                "header" => [
                    "Insatalltion",
                    "Monitoring"
                ],
                "body" => [
                    [
                        "$installation",
                        "$monitoring"
                    ],
                ],
            );

            $arrOtherSummary[] = $this->getFormattedSummary(
                $appType,
                "Today's Summary",
                array(),
                array(),
                $arrTableList
            );
        }

        return $arrOtherSummary;
    }

    private function formatLeaderboardEntry(
        $leaderboardDetail,
        $rank,
        $sumPara1,
        $sumPara2,
        $sumPara3,
        $sumPara4,
        $totalPercentage,
        $rankLabel = null,
        $rankColor = "#CACACA",
        $rankIcon = "medal_star"
    ) {
        // Format the total percentage
        $formattedPercentage = $totalPercentage;

        return [
            "rank" => $rankLabel ? "#$rank ($rankLabel)" : "#$rank",
            "dsName" => $leaderboardDetail[9],  // Ensure the correct field for team name
            "totalScore" => $formattedPercentage,  // Total score formatted per working day
            "rankColor" => $rankColor,  // Rank color
            "rankIcon" => $rankIcon,  // Rank icon
            "scoreParameters" => [
                [
                    "para1Label" => "QA Days",  // Label for parameter 1
                    "para1Value" => $sumPara1,  // Value for parameter 1 per working day
                    "para2Label" => "UOB",  // Label for parameter 2
                    "para2Value" => $sumPara2,  // Value for parameter 2 per working day
                    "para3Label" => "FB1 UOB",  // Label for parameter 3
                    "para3Value" => $sumPara3,  // Value for parameter 3 per working day
                    "para4Label" => "FB2 UOB",  // Label for parameter 4
                    "para4Value" => $sumPara4,  // Value for parameter 4 per working day
                ]
            ]
        ];
    }
}
