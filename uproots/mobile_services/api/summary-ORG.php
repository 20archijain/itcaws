<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";

$currentMonth = date("Y-m-") . "%";
$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();
$logFileName = "debug_summary";
$unauthorisedAccessLogFileName = "debug_summary_unauthorised_access";
$logFolderName = "/summary";

$logResponse = array(
    "log" => true,
    "fileName" => $logFileName,
    "folderName" => $logFolderName,
);

$sToken = "";
if ($_SERVER["PHP_AUTH_PW"] && $_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"] === $_SERVER["PHP_AUTH_USER"]) {
    $sToken = $_SERVER["PHP_AUTH_PW"];
}

$commonFunctions->debugLog(
    "\r\nSERVER LOG DATE TIME: $currentDateTime Token: $sToken",
    $logFileName,
    $logFolderName
);

// token not set
if (!$sToken) {
    // Unauthorized access
    $commonFunctions->debugLog(
        "\r\nSERVER LOG DATE TIME: $currentDateTime Token: $sToken\r\n" . $arrAuthMessages["AUTH04"],
        $unauthorisedAccessLogFileName,
        $logFolderName
    );
    $commonFunctions->debugLog(
        $arrAuthMessages["AUTH04"],
        $logFileName,
        $logFolderName
    );
    responseMessage($arrAuthMessages["AUTH04"]);
} else {
    $sQuery_Org = "SELECT s_id, client_id, project_id, team_id, db_name FROM $TBL_CLOUD_AUTH_PIN" .
        " WHERE token = '$sToken' AND dstatus = 0 LIMIT 1";

    $sQuery = "SELECT s_id, client_id, project_id, team_id, db_name FROM $TBL_CLOUD_AUTH_PIN" .
        " WHERE token = ? AND dstatus = 0 LIMIT 1";
    $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, array($sToken));

    // User found
    if ($iActionRows === 1) {
        $row = $dbConn->GetData($rsAction);

        $json_ID = $row['s_id'];
        $db_name = $row['db_name'];
        $sClient_ID = $row['client_id'];
        $sProject_ID = $row['project_id'];
        $iTeam_ID = $row['team_id'];

        // Check whether to show summary
        $arrProjectSummaryDetails = isset($arrDBProjectDetails[$db_name][$sClient_ID][$sProject_ID]["summary"]) &&
            $commonFunctions->isNonEmptyArray($arrDBProjectDetails[$db_name][$sClient_ID][$sProject_ID]["summary"]) ?
            $arrDBProjectDetails[$db_name][$sClient_ID][$sProject_ID]["summary"]
            : (isset($arrDBProjectDetails[$db_name][0][0]["summary"]) &&
                $commonFunctions->isNonEmptyArray($arrDBProjectDetails[$db_name][0][0]["summary"]) ?
                $arrDBProjectDetails[$db_name][0][0]["summary"] : null);
        $showSummary = $arrProjectSummaryDetails ? true : false;

        $arrResponse = $arrCustomResponse = array();

        // Display summary
        if ($showSummary) {
            // Include DB based project details file
            if (isset($arrDBProjectDetails[$db_name]["path"]) && $arrDBProjectDetails[$db_name]["path"]) {
                include_once $arrDBProjectDetails[$db_name]["path"];

                $isJsonIdForRespTableRequire = isset($arrDBProjectDetails[$db_name]["requireJsonIdForRespTable"]) ?
                    $arrDBProjectDetails[$db_name]["requireJsonIdForRespTable"] : false;
                if ($isJsonIdForRespTableRequire) {
                    $jsonId = $tableUtil->getRowColumn(
                        "$db_name.$TBL_PROJECT_TEAM",
                        "s_id",
                        "team_id = $iTeam_ID"
                    );
                    $respTable = getRespTable($sClient_ID, $sProject_ID, $jsonId);
                } else {
                    $respTable = getRespTable($sClient_ID, $sProject_ID);
                }
            } else {
                $respTable = isset($arrProjectSummaryDetails["respTable"]) &&
                    $arrProjectSummaryDetails["respTable"] ? $arrProjectSummaryDetails["respTable"] : null;
            }

            // Flags
            $showAttendanceSummary = isset($arrProjectSummaryDetails["showAttendanceSummary"]) ?
                $arrProjectSummaryDetails["showAttendanceSummary"] : false;
            $isSeparateAttendanceTable = isset($arrProjectSummaryDetails["isSeparateAttendanceTable"]) ?
                $arrProjectSummaryDetails["isSeparateAttendanceTable"] : false;
            $attendanceTable = isset($arrProjectSummaryDetails["attendanceTable"]) &&
                $arrProjectSummaryDetails["attendanceTable"] ? $arrProjectSummaryDetails["attendanceTable"] :
                $TBL_ATTENDANCE;
            $attendanceCond = isset($arrProjectSummaryDetails["attendanceCond"]) &&
                $arrProjectSummaryDetails["attendanceCond"] ? $arrProjectSummaryDetails["attendanceCond"] : "";
            $attendanceShowNoDaysInAMonth = isset($arrProjectSummaryDetails["attendanceShowNoDaysInAMonth"]) ?
                $arrProjectSummaryDetails["attendanceShowNoDaysInAMonth"] : false;
            $attendanceExcludeWeekDay = isset($arrProjectSummaryDetails["attendanceExcludeWeekDay"]) ?
                $arrProjectSummaryDetails["attendanceExcludeWeekDay"] : "";
            $attendanceMtdLabel = isset($arrProjectSummaryDetails["attendanceMtdLabel"]) &&
                $arrProjectSummaryDetails["attendanceMtdLabel"] ?
                $arrProjectSummaryDetails["attendanceMtdLabel"] : "";
            $attendanceShowLoginTime = isset($arrProjectSummaryDetails["attendanceShowLoginTime"]) ?
                $arrProjectSummaryDetails["attendanceShowLoginTime"] : false;
            $attendanceShowLogoutTime = isset($arrProjectSummaryDetails["attendanceShowLogoutTime"]) ?
                $arrProjectSummaryDetails["attendanceShowLogoutTime"] : false;
            $logoutCond = isset($arrProjectSummaryDetails["logoutCond"]) ?
                $arrProjectSummaryDetails["logoutCond"] : "";

            // Attendance summary
            if ($showAttendanceSummary) {
                // Attendance is stored in separate/common table
                if ($isSeparateAttendanceTable) {
                    $arrTodayAttendance = $tableUtil->getRowColumns(
                        "$db_name.$attendanceTable",
                        "att_id, capture_datetime",
                        "dstatus = 0 AND team_id = $iTeam_ID AND call_type = '0'" .
                            " AND capture_date = '$currentDate' $attendanceCond"
                    );
                    if ($attendanceShowLogoutTime) {
                        $arrTodayLogout = $tableUtil->getRowColumns(
                            "$db_name.$attendanceTable",
                            "att_id, capture_datetime",
                            "dstatus = 0 AND team_id = $iTeam_ID AND call_type = '1'" .
                                " AND capture_date = '$currentDate' $logoutCond"
                        );
                    }
                    $mtdPresents = $tableUtil->getRowColumn(
                        "$db_name.$attendanceTable",
                        "COUNT(DISTINCT capture_date) AS total",
                        "dstatus = 0 AND team_id = $iTeam_ID AND call_type = '0'" .
                            " AND capture_date LIKE '$currentMonth' $attendanceCond"
                    );
                } else {
                    // Attendance is stored in same table
                    $arrTodayAttendance = $tableUtil->getRowColumns(
                        "$db_name.$attendanceTable",
                        "pro_id, capture_datetime",
                        "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate' $attendanceCond"
                    );
                    if ($attendanceShowLogoutTime) {
                        $arrTodayLogout = $tableUtil->getRowColumns(
                            "$db_name.$attendanceTable",
                            "pro_id, capture_datetime",
                            "dstatus = 0 AND team_id = $iTeam_ID" .
                                " AND capture_date = '$currentDate' $logoutCond"
                        );
                    }
                    $mtdPresents = $tableUtil->getRowColumn(
                        "$db_name.$attendanceTable",
                        "COUNT(DISTINCT capture_date) AS total",
                        "dstatus = 0 AND team_id = $iTeam_ID AND capture_date LIKE '$currentMonth' $attendanceCond"
                    );
                }

                // Today's Login Details
                $isPresentToday = isset($arrTodayAttendance, $arrTodayAttendance[0]) && $arrTodayAttendance[0] ?
                    true : false;
                $todayAttendanceLoginDatetime = isset($arrTodayAttendance, $arrTodayAttendance[1]) &&
                    $arrTodayAttendance[1] ? $arrTodayAttendance[1] : "";
                $todayAttendanceLoginTime = $todayAttendanceLoginDatetime ?
                    $commonFunctions->currentDateTime("h:i:s A", $todayAttendanceLoginDatetime) : $arrSummaryLabels["NA"];

                // Today's Logout Details
                if ($attendanceShowLogoutTime) {
                    $isLogoutToday = isset($arrTodayLogout, $arrTodayLogout[0]) && $arrTodayLogout[0] ? true : false;
                    $todayAttendanceLogoutDatetime = isset($arrTodayLogout, $arrTodayLogout[1]) &&
                        $arrTodayLogout[1] ? $arrTodayLogout[1] : "";
                    $todayAttendanceLogoutTime = $todayAttendanceLogoutDatetime ?
                        $commonFunctions->currentDateTime("h:i:s A", $todayAttendanceLogoutDatetime) : $arrSummaryLabels["NA"];
                }

                $mtdPresents = $mtdPresents ? $mtdPresents : 0;

                if ($db_name === $SNPL_DB) {
                    $arrResponse[0] = array(
                        "title" => "हाजिरीको सारांश",
                        "summaryList" => array(
                            array(
                                "label" => "आजको हाजिरी",
                                "value" => $isPresentToday ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                            ),
                            array(
                                "label" => "यस महिनाको कुल हाजिरी",
                                "value" => (int) $mtdPresents,
                            ),
                        ),
                    );
                } else {
                    $noOfDays = 0;
                    if ($attendanceShowNoDaysInAMonth) {
                        $noOfDays = getCountOfDaysExcluding($attendanceExcludeWeekDay);
                        $mtdPresents = "$mtdPresents/$noOfDays";
                    }

                    $arrResponse[0] = array(
                        "title" => $arrSummaryLabels["ATTENDANCE_SUMMARY"],
                        "summaryList" => array(
                            array(
                                "label" => $arrSummaryLabels["TODAYS_ATTENDANCE"],
                                "value" => $isPresentToday ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                            ),
                            array(
                                "label" => $attendanceMtdLabel ?
                                    (isset($arrSummaryLabels[$attendanceMtdLabel]) ?
                                        $arrSummaryLabels[$attendanceMtdLabel] :
                                        $attendanceMtdLabel) : $arrSummaryLabels["MTD_ATTENDANCE"],
                                "value" => $mtdPresents,
                            ),
                        ),
                    );

                    // Today's Login time
                    if ($attendanceShowLoginTime) {
                        $arrResponse[0]["summaryList"][] = array(
                            "label" => $arrSummaryLabels["LOGIN_TIME"],
                            "value" => $todayAttendanceLoginTime,
                        );
                    }

                    // Today's Logout time
                    if ($attendanceShowLogoutTime) {
                        $arrResponse[0]["summaryList"][] = array(
                            "label" => $arrSummaryLabels["LOGOUT_TIME"],
                            "value" => $todayAttendanceLogoutTime,
                        );
                    }

                    if ($db_name === $DELHI_DB) {
                        $minBillsShops = $tableUtil->getRowColumn(
                            "$db_name.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minTotalShops'"
                        );
                        $minWorkingTimeInMin = $tableUtil->getRowColumn(
                            "$db_name.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minWorkingTimeInMin'"
                        );

                        $arrQualifiedAttendance = $tableUtil->getRowsColumns(
                            "$db_name.tblvands_summary",
                            "SUM(total_sellin_shops + total_other_shops) AS totalShops",
                            "dstatus = 0 AND team_id = $iTeam_ID AND activity_date LIKE '$currentMonth'" .
                                " AND TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime) >= $minWorkingTimeInMin" .
                                " AND DAYOFWEEK(activity_date) != 1 GROUP BY activity_date HAVING totalShops >= $minBillsShops"
                        );
                        $noOfQualifiedAttendance = count($arrQualifiedAttendance);

                        $arrResponse[0]["summaryList"][] = array(
                            "label" => $arrSummaryLabels["QUALIFIED_MARKET_WORKING_DAYS"],
                            "value" => "$noOfQualifiedAttendance/" . ($noOfDays ? $noOfDays : date("t")),
                        );
                    } elseif ($db_name === $ITC_DB || $db_name === $SOUTH_DB) {
                        $minTotalShops = $tableUtil->getRowColumn(
                            "$db_name.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minTotalShops'"
                        );
                        $minWorkingTimeInMin = $tableUtil->getRowColumn(
                            "$db_name.tblconstants",
                            "con_value",
                            "dstatus = 0 AND con_name = 'minWorkingTimeInMin'"
                        );

                        $arrQualifiedAttendance = $tableUtil->getRowsColumns(
                            "$db_name.tblvands_summary",
                            $db_name === $ITC_DB ? "SUM(total_roc_deliveries + total_other_shops) AS totalShops" :
                                "SUM(total_deliveries) AS totalShops",
                            "dstatus = 0 AND team_id = $iTeam_ID AND activity_date LIKE '$currentMonth'" .
                                " AND TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime) >= $minWorkingTimeInMin" .
                                " GROUP BY activity_date HAVING totalShops >= $minTotalShops"
                        );
                        $noOfQualifiedAttendance = count($arrQualifiedAttendance);

                        $arrResponse[0]["summaryList"][] = array(
                            "label" => $arrSummaryLabels["QUALIFIED_MARKET_WORKING_DAYS"],
                            "value" => $noOfQualifiedAttendance,
                        );
                    }
                }
            }

            // Other summary
            $showOtherSummary = isset($arrProjectSummaryDetails["showOtherSummary"]) ?
                $arrProjectSummaryDetails["showOtherSummary"] : false;
            $otherSummaryCond = isset($arrProjectSummaryDetails["otherSummaryCond"]) &&
                $arrProjectSummaryDetails["otherSummaryCond"] ? $arrProjectSummaryDetails["otherSummaryCond"] : "";

            if ($showOtherSummary && $respTable) {
                // Wonder
                if ($db_name === $WONDER_DB) {
                    if ($sClient_ID != 27) {
                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total Reccee",
                                    "value" => (int) $tableUtil->getRowColumn(
                                        "$db_name.$respTable AS a, $db_name.tblshops AS b",
                                        "COUNT(a.pro_id) AS total",
                                        "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $iTeam_ID" .
                                            " AND a.capture_date = '$currentDate' AND a.ques_1 = '1'" .
                                            " AND a.ques_14 = b.id AND a.ques_17 = '1' $otherSummaryCond"
                                    ),
                                ),
                                array(
                                    "label" => "Total Installation",
                                    "value" => (int) $tableUtil->getRowColumn(
                                        "$db_name.$respTable AS a, $db_name.tblshops AS b",
                                        "COUNT(a.pro_id) AS total",
                                        "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $iTeam_ID" .
                                            " AND a.capture_date = '$currentDate' AND a.ques_1 = '2'" .
                                            " AND a.ques_18 = b.id AND a.ques_20 = '1' $otherSummaryCond"
                                    ),
                                ),
                                array(
                                    "label" => "Total Direct Installation",
                                    "value" => (int) $tableUtil->getRowColumn(
                                        "$db_name.$respTable AS a, $db_name.tblshops AS b",
                                        "COUNT(a.pro_id) AS total",
                                        "a.dstatus = 0 AND b.cancel = 0 AND b.dstatus = 0 AND a.team_id = $iTeam_ID" .
                                            " AND a.capture_date = '$currentDate' AND a.ques_1 = '3'" .
                                            " AND a.ques_21 = b.id AND a.ques_24 = '1' $otherSummaryCond"
                                    ),
                                ),
                            ),
                        );
                    }
                } elseif ($db_name === $SNPL_DB) {
                    $arrTeamSummary = $tableUtil->getRowColumns(
                        "$db_name.$TBL_MOBILE_SUMMARY",
                        "time_spent_today, grocery_count_today, retail_count_today, wholesale_count_today" .
                            ", roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd" .
                            ", roc_total_shops_count, roc_covered_today, roc_covered_mtd",
                        "dstatus = 0 AND team_id = $iTeam_ID"
                    );

                    $timeSpentToday = isset($arrTeamSummary, $arrTeamSummary[0]) ? $arrTeamSummary[0] : "";
                    $iMadiraPasalCount = isset($arrTeamSummary, $arrTeamSummary[1]) ? $arrTeamSummary[1] : 0;
                    $iKhudraVikretaCount = isset($arrTeamSummary, $arrTeamSummary[2]) ? $arrTeamSummary[2] : 0;
                    $iThokVikretaCount = isset($arrTeamSummary, $arrTeamSummary[3]) ? $arrTeamSummary[3] : 0;
                    $rocCount = isset($arrTeamSummary, $arrTeamSummary[4]) ? $arrTeamSummary[4] : 0;
                    $otherCoveredShopsTodayCount = isset($arrTeamSummary, $arrTeamSummary[5]) ? $arrTeamSummary[5] : 0;
                    $otherCoveredShopsMtdCount = isset($arrTeamSummary, $arrTeamSummary[6]) ? $arrTeamSummary[6] : 0;
                    $assignedROCShopsCount = isset($arrTeamSummary, $arrTeamSummary[7]) ? $arrTeamSummary[7] : 0;
                    $rocCoveredShopsTodayCount = isset($arrTeamSummary, $arrTeamSummary[8]) ? $arrTeamSummary[8] : 0;
                    $rocCoveredShopsMtdCount = isset($arrTeamSummary, $arrTeamSummary[9]) ? $arrTeamSummary[9] : 0;

                    $arrResponse[] = array(
                        "title" => "आजको सारांश",
                        "summaryList" => array(
                            array(
                                "label" => "कुल समय बिताया",
                                "value" => $timeSpentToday,
                            ),
                            array(
                                "label" => "मदिरा पसल",
                                "value" => $iMadiraPasalCount,
                            ),
                            array(
                                "label" => "खुद्रा बिक्रेता पसल",
                                "value" => $iKhudraVikretaCount,
                            ),
                            array(
                                "label" => "थोक बिक्रेता पसल",
                                "value" => $iThokVikretaCount,
                            ),
                            array(
                                "label" => "रुटमा बिक्री गरिएका पसलहरु",
                                "value" => $rocCount,
                            ),
                        ),
                    );

                    $arrResponse[] = array(
                        "title" => "नयाँ पसलहरुको सारांश",
                        "summaryList" => array(
                            array(
                                "label" => "आजको कभरेज",
                                "value" => $otherCoveredShopsTodayCount,

                            ),
                            array(
                                "label" => "यस महिना हालसम्मको कभरेज",
                                "value" => $otherCoveredShopsMtdCount,
                            ),
                        ),
                    );

                    $arrResponse[] = array(
                        "title" => "रुटको बिक्री",
                        "summaryList" => array(
                            array(
                                "label" => "कुल पसल संख्या",
                                "value" => $assignedROCShopsCount,
                            ),
                            array(
                                "label" => "आज कभर गरिएका पसलहरु",
                                "value" => $rocCoveredShopsTodayCount,
                            ),
                            array(
                                "label" => "यस महिना हालसम्म कभर गरिएका पसलहरु",
                                "value" => $rocCoveredShopsMtdCount,
                            ),
                        ),
                    );

                    // Get absent cycle DSS
                    if ($json_ID == 2) {
                        $rsAbsentCycleDssAction = null;
                        $iAbsentCycleDssActionRows = 0;
                        $sAbsentCycleDssQuery = "SELECT outlet_name_eng FROM $db_name.$TBL_ROUTE_DETAILS" .
                            " WHERE team_id = $iTeam_ID and cycle_dss = '1' AND dstatus = 0 AND rec_id NOT IN" .
                            " (SELECT DISTINCT shop_id FROM $db_name.$TBL_SURVEY_RESPONSE WHERE" .
                            " capture_date = '$currentDate' and ques_0 = 'रुटको बिक्री'" .
                            " AND ques_5 = 'हाजिरी' AND dstatus = 0);";
                        $dbConn->ExecuteSelectQuery(
                            $sAbsentCycleDssQuery,
                            $rsAbsentCycleDssAction,
                            $iAbsentCycleDssActionRows
                        );
                        if ($iAbsentCycleDssActionRows > 0) {
                            $arrDSSNames = array();
                            while ($rowAbsentCycleDss = $dbConn->GetData($rsAbsentCycleDssAction)) {
                                $arrDSSNames[] = array(
                                    "label" => $rowAbsentCycleDss["outlet_name_eng"],
                                    "value" => "",
                                );
                            }

                            $arrResponse[] = array(
                                "title" => "Absent Cycle DSS",
                                "summaryList" => $arrDSSNames,
                            );
                        }
                    }
                } elseif ($db_name === $ITC_DB || $db_name === $JAIPUR_DB) {
                    $arrTeamSummary = $tableUtil->getRowColumns(
                        "$db_name.$TBL_MOBILE_SUMMARY",
                        "time_spent_today, grocery_count_today, retail_count_today, wholesale_count_today" .
                            ", roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd" .
                            ", roc_total_shops_count, roc_covered_today, roc_covered_mtd",
                        "dstatus = 0 AND team_id = $iTeam_ID"
                    );

                    $timeSpentToday = isset($arrTeamSummary, $arrTeamSummary[0]) ? $arrTeamSummary[0] : "";
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

                    $branchId = $tableUtil->getRowColumn(
                        "$db_name.$TBL_PROJECT_TEAM",
                        "branch_id",
                        "team_id = $iTeam_ID"
                    );
                    $branchId = $branchId ? $branchId : 1;
                    // Get the current month and year
                    $currentMonth = date('m');
                    $currentYear = date('Y');
                    $arrTeamLeaderBoard = $db_name === $ITC_DB ? $tableUtil->getRowsColumns(
                        "$db_name.tblleaderboard AS lb",
                        "AVG(para1_score) AS avg_para1, AVG(para2_score) AS avg_para2, AVG(para3_score) AS avg_para3, AVG(para4_score) AS avg_para4, AVG(total_score) AS avg_percentage, team_id, branch_id, (SELECT team_name FROM $db_name.tblproject_team WHERE team_id = lb.team_id) AS team_name",
                        "lb.dstatus = 0 AND lb.branch_id = $branchId AND MONTH(lb.capture_date) = $currentMonth AND YEAR(lb.capture_date) = $currentYear GROUP BY lb.team_id ORDER BY avg_percentage DESC"
                    ) : array();

                    $leaderboardList = [];
                    $loggedInUserRank = null;
                    $userRankDetail = null;

                    foreach ($arrTeamLeaderBoard as $key => $leaderboardDetail) {
                        if ($leaderboardDetail[5] == $iTeam_ID) {
                            $loggedInUserRank = $key + 1; // Adding 1 to convert from array index to rank
                            $formattedPercentage = sprintf("%.2f", $leaderboardDetail[4]);
                            // $loggedInScoreAndRank = "(" . $formattedPercentage . ") #" . $loggedInUserRank;
                            $userRankDetail = [
                                "rank" => "#$loggedInUserRank",
                                "dsName" => $leaderboardDetail[7],
                                "totalScore" => $formattedPercentage,
                                "scoreParameters" => [
                                    [
                                        "para1Label" => "QAtt",
                                        "para1Value" => sprintf("%.1f", $leaderboardDetail[0]),
                                        "para2Label" => "DAtt",
                                        "para2Value" => sprintf("%.1f", $leaderboardDetail[1]),
                                        "para3Label" => "UOB",
                                        "para3Value" => sprintf("%.1f", $leaderboardDetail[2]),
                                        "para4Label" => "B-Adh",
                                        "para4Value" => sprintf("%.1f", $leaderboardDetail[3])
                                    ]
                                ]
                            ];
                            break;
                        }
                    }

                    for ($i = 0; $i < min(10, count($arrTeamLeaderBoard)); $i++) {
                        $leaderboardDetail = $arrTeamLeaderBoard[$i];
                        $formattedPercentage = sprintf("%.2f", $leaderboardDetail[4]);
                        $leaderboardList[] = array(
                            "rank" => "#" . ($i + 1), // Adding 1 to convert from array index to rank
                            "dsName" => $leaderboardDetail[7],
                            "totalScore" => $formattedPercentage,
                            "scoreParameters" => [
                                [
                                    "para1Label" => "QAtt",
                                    "para1Value" => sprintf("%.1f", $leaderboardDetail[0]),
                                    "para2Label" => "DAtt",
                                    "para2Value" => sprintf("%.1f", $leaderboardDetail[1]),
                                    "para3Label" => "UOB",
                                    "para3Value" => sprintf("%.1f", $leaderboardDetail[2]),
                                    "para4Label" => "B-Adh",
                                    "para4Value" => sprintf("%.1f", $leaderboardDetail[3])
                                ]
                            ]
                        );
                    }
                    if ($loggedInUserRank !== null) {
                        $leaderboardList[] = $userRankDetail;
                    }

                    $arrResponse[] = array(
                        "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                        "summaryList" => array(
                            array(
                                "label" => "Total Time Spent",
                                "value" => $timeSpentToday,
                            ),
                            array(
                                "label" => "Other Outlet (Grocery)",
                                "value" => $todayOtherOutletGroceryCount,
                            ),
                            array(
                                "label" => "Other Outlet (Retail)",
                                "value" => $todayOtherOutletRetailCount,
                            ),
                            array(
                                "label" => "Other Outlet (Wholesale)",
                                "value" => $todayOtherOutletWholesaleCount,
                            ),
                            array(
                                "label" => "ROC Sell-in shops",
                                "value" => $todayROCSellinShopsCount,
                            ),
                        ),
                    );

                    $arrResponse[] = array(
                        "title" => "Feeder Market Summary",
                        "summaryList" => array(
                            array(
                                "label" => "Covered Today",
                                "value" => $otherCoveredShopsTodayCount,
                            ),
                            array(
                                "label" => "Covered MTD",
                                "value" => $otherCoveredShopsMtdCount,
                            ),
                        ),
                    );

                    $arrResponse[] = array(
                        "title" => "ROC Delivery",
                        "summaryList" => array(
                            array(
                                "label" => "Total Shops",
                                "value" => $assignedROCShopsCount,
                            ),
                            array(
                                "label" => "Covered Today",
                                "value" => $rocCoveredShopsTodayCount,
                            ),
                            array(
                                "label" => "Covered MTD",
                                "value" => $rocCoveredShopsMtdCount,
                            ),
                        ),
                    );

                    if ($db_name === $ITC_DB && $branchId == 13) {
                        $arrResponse[] = array(
                            "title" => "🏆LEADERBOARD🏆",
                            "summaryList" => $leaderboardList
                        );
                    }
                } elseif ($db_name === $DELHI_DB) {
                    $branchId = $tableUtil->getRowColumn(
                        "$db_name.$TBL_PROJECT_TEAM",
                        "branch_id",
                        "team_id = $iTeam_ID"
                    );
                    $branchId = $branchId ? $branchId : 1;

                    // Get products
                    $rsProductAction = null;
                    $iProductActionRows = 0;
                    $sProductQuery = "SELECT DISTINCT product_name, summary_column_name, focus_product" .
                        " FROM $db_name.tblbranch_products WHERE dstatus = 0 AND product_type = 0" .
                        " AND branch_id = $branchId";
                    $dbConn->ExecuteSelectQuery($sProductQuery, $rsProductAction, $iProductActionRows);

                    $sAvgSaleTodayColumn = "";
                    $sAvgSaleMtdColumn = "";
                    $sMtdSaleColumn = "";
                    $sFocusProductColumns = "";
                    $arrFocusProductsNames = array();
                    $arrFocusProductsColumns = array();
                    if ($iProductActionRows > 0) {
                        $arrProductColumns = array();
                        while ($rowProduct = $dbConn->GetData($rsProductAction)) {
                            $productName = $rowProduct["product_name"];
                            $summaryColumn = $rowProduct["summary_column_name"];
                            $isFocusProduct = $rowProduct["focus_product"];

                            // Find avg sale and mtd sale of "Overall Sale" product only
                            // if (strtolower($productName) === "overall sale") {
                            $arrProductColumns[] = $summaryColumn;
                            // }

                            if ($isFocusProduct) {
                                $arrFocusProductsNames[] = $productName;
                                $arrFocusProductsColumns[] = "SUM($summaryColumn) AS $summaryColumn";
                            }
                        }

                        $sProductColumns = implode(" + ", $arrProductColumns);
                        $sAvgSaleTodayColumn = ", SUM($sProductColumns) AS avgTodaySale";
                        $sAvgSaleMtdColumn = "AVG($sProductColumns) AS avgMtdSale";
                        $sMtdSaleColumn = "SUM($sProductColumns) AS mtdSale";

                        $sFocusProductColumns = ", " . implode(", ", $arrFocusProductsColumns);
                    }

                    // today's summary
                    $arrTeamTodaySummary = $tableUtil->getRowColumns(
                        "$db_name.tblvands_summary",
                        "start_datetime, end_datetime, SUM(total_roc_deliveries + total_other_shops)" .
                            " AS outletsVisited, SUM(total_sellin_shops + total_other_shops) AS billsCut" .
                            " $sAvgSaleTodayColumn $sFocusProductColumns",
                        "dstatus = 0 AND team_id = $iTeam_ID AND activity_date = '$currentDate'"
                    );
                    $timeSpent = $arrTeamTodaySummary && $arrTeamTodaySummary[0] ?
                        $commonFunctions->getTimeDifference($arrTeamTodaySummary[0], $arrTeamTodaySummary[1], false, false, true) : "0s";
                    $outletsVisitedToday = $arrTeamTodaySummary && $arrTeamTodaySummary[2] ?
                        $arrTeamTodaySummary[2] : 0;
                    $billsCutToday = $arrTeamTodaySummary && $arrTeamTodaySummary[3] ? $arrTeamTodaySummary[3] : 0;

                    // divide by 100 is used to find sale in MS
                    $avgSaleToday = $arrTeamTodaySummary && $arrTeamTodaySummary[4] ?
                        round($arrTeamTodaySummary[4] / 100, 2) : 0;

                    // Find avg sale, total sales and each focus product sale in current month (in MS)
                    $arrTeamMonthSummary = array();
                    if ($sAvgSaleMtdColumn || $sFocusProductColumns) {
                        $sFocusProductColumns = $sFocusProductColumns ? substr($sFocusProductColumns, 2) : "";

                        $saleColumns = "$sAvgSaleMtdColumn, $sMtdSaleColumn";
                        $saleColumns = $sFocusProductColumns ? $saleColumns . ", $sFocusProductColumns" : $saleColumns;
                        $arrTeamMonthSummary = $tableUtil->getRowColumns(
                            "$db_name.tblvands_summary",
                            $saleColumns,
                            "dstatus = 0 AND team_id = $iTeam_ID AND activity_date LIKE '$currentMonth'"
                        );
                    }

                    $avgSaleMonth = 0;
                    if ($sAvgSaleMtdColumn) {
                        $avgSaleMonth = $arrTeamMonthSummary && $arrTeamMonthSummary[0] ?
                            round($arrTeamMonthSummary[0] / 100, 2) : 0;
                    }

                    // Find MTD sale (in MS)
                    $saleMonthTillDate = 0;
                    if ($sMtdSaleColumn) {
                        $saleMonthTillDate = $arrTeamMonthSummary && $arrTeamMonthSummary[1] ?
                            number_format(round($arrTeamMonthSummary[1] / 100, 2), 2) : 0;
                    }

                    // Outlets mapped
                    $outletsMapped = $tableUtil->getRowColumn(
                        "$db_name.$TBL_ROUTE_DETAILS",
                        "COUNT(rec_id) AS total",
                        "dstatus = 0 AND team_id = $iTeam_ID"
                    );
                    $outletsMapped = $outletsMapped ? $outletsMapped : 0;

                    $arrResponse[] = array(
                        "title" => $arrSummaryLabels["DAY_SUMMARY"],
                        "summaryList" => array(
                            array(
                                "label" => $arrSummaryLabels["TIME_SPENT_IN_MARKET"],
                                "value" => $timeSpent,
                            ),
                            array(
                                "label" => $arrSummaryLabels["OUTLETS_VISITED"],
                                "value" => "$outletsVisitedToday/$outletsMapped",
                            ),
                            array(
                                "label" => $arrSummaryLabels["TOTAL_BILLS_CUT"],
                                "value" => "$billsCutToday/$outletsMapped",
                            ),
                            array(
                                "label" => $arrSummaryLabels["AVG_SALES"],
                                "value" => "$avgSaleToday/$avgSaleMonth",
                            ),
                            array(
                                "label" => $arrSummaryLabels["SALES_MONTH_TILL_DATE"],
                                "value" => $saleMonthTillDate,
                            ),
                        ),
                    );

                    if ($commonFunctions->isNonEmptyArray($arrFocusProductsColumns)) {
                        $arrFocusSkuSummaryList = array();

                        $iTodaySaleColumnStartIndex = 5;
                        $iMonthSaleColumnStartIndex = 2;
                        foreach ($arrFocusProductsNames as $productIndex => $productname) {
                            $iTodaySales = isset($arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex]) &&
                                $arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex] ?
                                round($arrTeamTodaySummary[$iTodaySaleColumnStartIndex + $productIndex] / 100, 2) : 0;

                            $iMonthSales = isset($arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex]) &&
                                $arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex] ?
                                round($arrTeamMonthSummary[$iMonthSaleColumnStartIndex + $productIndex] / 100, 2) : 0;

                            $arrFocusSkuSummaryList[] = array(
                                "label" => $productname,
                                "value" => "$iTodaySales/$iMonthSales",
                            );
                        }

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["FOCUS_SKU_SALES"],
                            "summaryList" => $arrFocusSkuSummaryList,
                        );
                    }

                    // Send whether to call get_stock_products_selling_price.php API or not to get inhand stock
                    $iCallStockApi = (int) $tableUtil->getRowColumn(
                        "$db_name.tblstock_inhand",
                        "is_updated_in_app",
                        "dstatus = 0 AND team_id = $iTeam_ID"
                    );
                    $arrCustomResponse = array(
                        "call_stock_api" => $iCallStockApi == 0 ? true : false,
                    );
                } elseif ($db_name === $ZX_DB) {
                    if ($sProject_ID == 39) {
                        $completed = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(DISTINCT ques_2) AS total",
                            "dstatus = 0 AND team_id = $iTeam_ID" .
                                " AND capture_date LIKE '$currentMonth' $otherSummaryCond"
                        );
                        $completed = $completed ? (int) $completed : 0;

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["MTD_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => $arrSummaryLabels["TARGET"],
                                    "value" => (int) $tableUtil->getRowColumn(
                                        "$db_name.$TBL_ROUTE_DETAILS",
                                        "COUNT(rec_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID"
                                    ),
                                ),
                                array(
                                    "label" => $arrSummaryLabels["COMPLETED"],
                                    "value" => (int) $completed,
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 46) {
                        $completedFirstCall = 0;
                        $completedSecondCall = 0;

                        $arrCompleted = $tableUtil->getRowsColumns(
                            "$db_name.$respTable",
                            "ques_1, COUNT(DISTINCT ques_2) AS total",
                            "dstatus = 0 AND team_id = $iTeam_ID AND ques_1 in ('First Call', 'Second Call')" .
                                " AND capture_date LIKE '$currentMonth' $otherSummaryCond GROUP BY ques_1"
                        );

                        if ($commonFunctions->isNonEmptyArray($arrCompleted)) {
                            foreach ($arrCompleted as $arrCall) {
                                if ($arrCall[0] == 'First Call') {
                                    $completedFirstCall = (int) $arrCall[1];
                                } elseif ($arrCall[0] == 'Second Call') {
                                    $completedSecondCall = (int) $arrCall[1];
                                }
                            }
                        }

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["MTD_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => $arrSummaryLabels["TARGET"],
                                    "value" => (int) $tableUtil->getRowColumn(
                                        "$db_name.$TBL_ROUTE_DETAILS",
                                        "COUNT(rec_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID"
                                    ),
                                ),
                                array(
                                    "label" => "First Call Completed",
                                    "value" =>  $completedFirstCall,
                                ),
                                array(
                                    "label" => "Second Call Completed",
                                    "value" =>  $completedSecondCall,
                                ),
                            ),
                        );

                        array_unshift(
                            $arrResponse,
                            array(
                                "title" => "Attention: Cycle Plan 2080: Month Ashwin\r\n\r\nObjective 1:\r\n\r\n100% Availability In TM Outlet." .
                                    "\r\nA) S Arctic Burst\r\nB) SLB\r\nC) S Dual Burst\r\nD) Naulo\r\n\r\nObjective 2:\r\n\r\n" .
                                    "90% Availability of any 1 TSM(PSU) in TM Outlet.\r\nA) CTD- Counter Top Dispenser.\r\n" .
                                    "B) GFD- Gravity Fed Dispenser.\r\nC) SLD- Slider",
                            )
                        );
                    } elseif ($sProject_ID == 67) {
                        $iVisited = 0;
                        $iSampled = 0;

                        $arrVisitedVsSampled = $tableUtil->getRowsColumns(
                            "$db_name.$respTable",
                            "ques_8, COUNT(pro_id) AS total",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND dup_processed = 1 AND dup_status = 5 GROUP BY ques_8"
                        );

                        foreach ($arrVisitedVsSampled as $arrCall) {
                            if ($arrCall[0] == "Yes") {
                                $iSampled = (int) $arrCall[1];
                            }
                            $iVisited += (int) $arrCall[1];
                        }

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total House Visited",
                                    "value" => $iVisited,
                                ),
                                array(
                                    "label" => "Total Sampled House",
                                    "value" => $iSampled,
                                ),
                            ),
                        );
                    } elseif (
                        $sProject_ID == 74 || $sProject_ID == 76 || $sProject_ID == 77 ||
                        $sProject_ID == 78 || $sProject_ID == 79
                    ) {
                        // Get summary
                        $sMobileSummary = $tableUtil->getRowColumn(
                            "$db_name.tblhawker_mobile_summary",
                            "summary",
                            "dstatus = 0 AND team_id = $iTeam_ID AND rcd = '$currentDate'"
                        );

                        if ($sMobileSummary) {
                            $arrResponse[] = json_decode(html_entity_decode($sMobileSummary), true);
                        }
                    } elseif ($sProject_ID == 84) {
                        // Get summary
                        $sMobileSummary = $tableUtil->getRowColumn(
                            "$db_name.tbldaily_mobile_summary",
                            "summary",
                            "dstatus = 0 AND team_id = $iTeam_ID AND rcd = '$currentDate'"
                        );

                        if ($sMobileSummary) {
                            $arrResponse[] = json_decode(html_entity_decode($sMobileSummary), true);
                        }
                    } elseif ($sProject_ID == 100) {
                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total Reccee",
                                    "value" => $tableUtil->getRowColumn(
                                        "$db_name.$respTable",
                                        "COUNT(DISTINCT shop_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID AND rcd = '$currentDate'"
                                    ),
                                ),
                                array(
                                    "label" => "Total Installation",
                                    "value" => $tableUtil->getRowColumn(
                                        "$db_name.tblresponse_retail_merchandising_installation",
                                        "COUNT(DISTINCT shop_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID AND rcd = '$currentDate'"
                                    ),
                                ),
                            ),
                        );
                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["OVERALL_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total Reccee",
                                    "value" => $tableUtil->getRowColumn(
                                        "$db_name.$respTable",
                                        "COUNT(DISTINCT shop_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID"
                                    ),
                                ),
                                array(
                                    "label" => "Total Installation",
                                    "value" => $tableUtil->getRowColumn(
                                        "$db_name.tblresponse_retail_merchandising_installation",
                                        "COUNT(DISTINCT shop_id) AS total",
                                        "dstatus = 0 AND team_id = $iTeam_ID"
                                    ),
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 110) {
                        $rsResProj0 = null;
                        $iNum_rowsProj0 = 0;
                        $sGET_Query0 = "SELECT ques_1 FROM $db_name.tblsurvey_response_details_merchantdisingph3" .
                            " WHERE dstatus = 0 AND pid = '110' AND team_id = $iTeam_ID" .
                            " AND capture_date = '$currentDate' ORDER BY capture_datetime DESC";
                        $dbConn->ExecuteSelectQuery($sGET_Query0, $rsResProj0, $iNum_rowsProj0);
                        if ($iNum_rowsProj0 > 0) {
                            $title = "Today's Summary";
                            $summaryList = array();
                            $i = 1;
                            while ($row = $dbConn->GetData($rsResProj0)) {
                                $village = $row['ques_1'];
                                $arrVillage = isset($village) && $village ?
                                    json_decode($village, true) : array("", "", "", "");
                                $shopName = $arrVillage[2];
                                $summaryList[] = array(
                                    "label" => "$i-$shopName",
                                    "value" => null
                                );
                                $i++;
                            }
                            $arrResponse[] = array(
                                "title" => $title,
                                "summaryList" => $summaryList
                            );
                        }
                    }
                } elseif ($db_name === $IMPACT_DB) {
                    if ($sProject_ID == 53) {
                        $totalCustomerConverted = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS total",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'Sales Details'"
                        );
                        $arrAttendance = $tableUtil->getRowColumns(
                            "$db_name.$respTable",
                            "pro_id, ques_7",
                            "dstatus = 0 AND team_id = $iTeam_ID" .
                                " AND capture_date = '$currentDate' AND ques_1 = 'Attendance'"
                        );
                        $isStockAcknowledgementPhoto = isset($arrAttendance, $arrAttendance[0]) && $arrAttendance[0] ?
                            true : false;

                        $competitorStockDetails = isset($arrAttendance, $arrAttendance[1]) &&
                            $arrAttendance[1] ? json_decode($arrAttendance[1], true) : array();
                        $iCompetitorStockDetailsFilled = array_sum(
                            getGridDataAsArray($competitorStockDetails, 1, 7, true, true)[0]
                        );

                        $isReturnStockAcknowledgementPhoto = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS total",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'DayEnd Report'"
                        );

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total Customer Converted",
                                    "value" => (int) $totalCustomerConverted,
                                ),
                                array(
                                    "label" => "Pic Taken (Receiving stock)",
                                    "value" => $isStockAcknowledgementPhoto ?
                                        $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                                ),
                                array(
                                    "label" => "Pic Taken (Return stock)",
                                    "value" => $isReturnStockAcknowledgementPhoto ?
                                        $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                                ),
                                array(
                                    "label" => "Competitor Brand Details",
                                    "value" => $iCompetitorStockDetailsFilled > 0 ?
                                        $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 65) {
                        $totalConnect = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalConnect",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 IN ('D2D Visit','No Response') AND dup_processed = 1 AND dup_status = 5"
                        );

                        $totalInteration = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalInteration",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit' AND dup_processed = 1 AND dup_status = 5"
                        );

                        $totalConversion = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalConversion",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes' AND ques_1 = 'D2D Visit'" .
                                " AND dup_processed = 1 AND dup_status = 5"
                        );

                        $totalSales = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "SUM(ques_12) AS totalSales",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes' AND dup_processed = 1 AND dup_status = 5"
                        );

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Total Connect",
                                    "value" => (int) $totalConnect,
                                ),
                                array(
                                    "label" => "Total Interaction",
                                    "value" => $totalInteration,
                                ),
                                array(
                                    "label" => "Total Conversion",
                                    "value" => $totalConversion,
                                ),
                                array(
                                    "label" => "Total Sales",
                                    "value" => $totalSales
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 80) {
                        // Get summary
                        $sMobileSummary = $tableUtil->getRowColumn(
                            "$db_name.tbldaily_mobile_summary",
                            "summary",
                            "dstatus = 0 AND team_id = $iTeam_ID AND rcd = '$currentDate'"
                        );

                        if ($sMobileSummary) {
                            $arrResponse[] = json_decode(html_entity_decode($sMobileSummary), true);
                        }
                    }
                } elseif ($db_name === $NOVICEMARCOM_DB) {
                    if ($sProject_ID == 23) {
                        $totalConnect = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalConnect",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 IN ('D2D Visit','No Response')"
                        );

                        $totalInteration = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalInteration",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit'"
                        );

                        $totalConversion = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "COUNT(pro_id) AS totalConversion",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes'"
                        );

                        $totalSales = $tableUtil->getRowColumn(
                            "$db_name.$respTable",
                            "SUM(ques_12) AS totalSales",
                            "dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                                " AND ques_1 = 'D2D Visit' AND ques_9 = 'Yes'"
                        );

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(

                                array(
                                    "label" => "Total Interaction",
                                    "value" => $totalInteration,
                                ),
                                array(
                                    "label" => "Total Conversion",
                                    "value" => $totalConversion,
                                ),
                                array(
                                    "label" => "Total Sales",
                                    "value" => $totalSales
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 26) {
                        $rsSummaryAction = null;
                        $iSummaryActionRows = 0;
                        $sSummaryQuery = "SELECT ques_3, ques_11, COUNT(pro_id) AS total FROM $db_name.$respTable" .
                            " WHERE pid = $sProject_ID AND team_id = $iTeam_ID AND capture_date = '$currentDate'" .
                            " AND ques_1 = 'Mechanic Visit Report' GROUP BY ques_3, ques_11";
                        $dbConn->ExecuteSelectQuery($sSummaryQuery, $rsSummaryAction, $iSummaryActionRows);

                        $totalMechanicsEnrolled = 0;
                        $totalAppInstalled = 0;
                        $totalRepeatVisit = 0;

                        if ($iSummaryActionRows > 0) {
                            while ($rowSummary = $dbConn->GetData($rsSummaryAction)) {
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

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Today's Scan",
                                    "value" => "",
                                ),
                                array(
                                    "label" => "Total New Garages Visited",
                                    "value" => $totalMechanicsEnrolled,
                                ),
                                array(
                                    "label" => "Total New App Installed",
                                    "value" => $totalAppInstalled,
                                ),
                                array(
                                    "label" => "Total Repeat Visit",
                                    "value" => $totalRepeatVisit
                                ),
                            ),
                        );
                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["MTD_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => "Scan MTD",
                                    "value" => "",
                                ),
                                array(
                                    "label" => "Registrations MTD",
                                    "value" => "",
                                ),
                                array(
                                    "label" => "Repeat Visit MTD",
                                    "value" => "",
                                ),
                                array(
                                    "label" => "Mechanic Meets",
                                    "value" => ""
                                ),
                            ),
                        );
                    }
                } elseif ($db_name === $ITCNEW_DB) {
                    // Yippee D2D
                    if ($sProject_ID == 59 || $sProject_ID == 66 || $sProject_ID == 67) {
                        $iVisited = 0;
                        $iBought = 0;

                        $arrVisitedVsBought = $tableUtil->getRowsColumns(
                            "$db_name.$respTable",
                            "ques_4, COUNT(pro_id) AS total",
                            "dstatus = 0 AND project_id = $sProject_ID AND team_id = $iTeam_ID" .
                                " AND capture_date = '$currentDate' AND ques_0 = 'Consumer Sales Details'" .
                                " GROUP BY ques_4"
                        );

                        foreach ($arrVisitedVsBought as $arrCall) {
                            if ($arrCall[0] == "Yes") {
                                $iBought = (int) $arrCall[1];
                            }
                            $iVisited += (int) $arrCall[1];
                        }

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => $arrSummaryLabels["TOTAL_CALLS"],
                                    "value" => $iVisited,
                                ),
                                array(
                                    "label" => $arrSummaryLabels["PRODUCTIVE_CALLS"],
                                    "value" => $iBought,
                                ),
                            ),
                        );
                    } elseif ($sProject_ID == 118) {
                        $iVisited = 0;
                        $iBought = 0;
                        $iNotBought = 0;
                        $count = 0;
                        $count1 = 0;
                        $count2 = 0;
                        $brand = "Sunfeast Marielite 225 gm @ 30 MRP.";
                        $brand1 = "Moms Magic Cashew 200 gm @ 35 MRP";
                        $brand2 = "Dark Fantasy 75gm @ 40 MRP";

                        $arrVisitedVsBought = $tableUtil->getRowsColumns(
                            "$db_name.tblresponse_sunfeastmarielite_d2d",
                            "ques_7, COUNT(pro_id) AS total",
                            "dstatus = 0 AND project_id = $sProject_ID AND team_id = $iTeam_ID" .
                                " AND capture_date = '$currentDate' AND ques_0 = 'Consumer Sales Details'" .
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

                        $rsAction = null;
                        $iActionRows = 0;
                        $sQuery = "SELECT ques_10 FROM $db_name.tblresponse_sunfeastmarielite_d2d WHERE" .
                            " project_id = $sProject_ID AND team_id = $iTeam_ID AND dstatus = 0" .
                            " AND capture_date = '$currentDate' AND ques_0 = 'Consumer Sales Details'" .
                            "AND ques_7 = 'ହଁ'";
                        $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

                        if ($iActionRows > 0) {
                            while ($row = $dbConn->GetData($rsAction)) {
                                $ques_10 = $row['ques_10'];
                                $count += substr_count($ques_10, $brand);
                                $count1 += substr_count($ques_10, $brand1);
                                $count2 += substr_count($ques_10, $brand2);
                            }
                        }

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => $arrSummaryLabels["TOTAL_CALLS"],
                                    "value" => $iVisited,
                                ),
                                array(
                                    "label" => $arrSummaryLabels["PRODUCTIVE_CALLS"],
                                    "value" => $iBought,
                                ),
                                array(
                                    "label" => $arrSummaryLabels["NON_PRODUCTIVE_CALLS"],
                                    "value" => $iNotBought,
                                ),
                                array(
                                    "label" => $brand,
                                    "value" => $count,
                                ),
                                array(
                                    "label" => $brand1,
                                    "value" => $count1,
                                ),
                                array(
                                    "label" => $brand2,
                                    "value" => $count2,
                                ),
                            ),
                        );
                    } elseif (
                        $sProject_ID == 111 || $sProject_ID == 114 || $sProject_ID == 115 ||
                        $sProject_ID == 116 || $sProject_ID == 117 || $sProject_ID == 123 || $sProject_ID == 124
                    ) {
                        $arrOpeningClosingSalesDetails = $tableUtil->getRowColumns(
                            "$db_name.$respTable",
                            "MAX(CASE WHEN ques_0 = 'Opening Stock' THEN 'Yes' ELSE 'No' END) AS opening_stock_filled, MAX(CASE WHEN ques_0 = 'Closing Stock' THEN 'Yes' ELSE 'No' END) AS closing_stock_filled, MAX(CASE WHEN ques_0 = 'Sales Details' THEN 'Yes' ELSE 'No' END) AS sales_details_filled",
                            "dstatus = 0 AND project_id = $sProject_ID AND team_id = $iTeam_ID AND capture_date = '$currentDate'"
                        );
                        $arrLeaderBoardDetails = $tableUtil->getRowsColumns(
                            "$db_name.tbltimesheet AS ts",
                            "AVG(ts.total_percentage) AS avg_percentage, team_id, (SELECT team_name FROM $db_name.tblproject_team WHERE team_id = ts.team_id) AS team_name",
                            "ts.dstatus = 0 AND ts.project_id = $sProject_ID GROUP BY ts.team_id ORDER BY avg_percentage DESC"
                        );

                        $loggedInUserRank = null;
                        foreach ($arrLeaderBoardDetails as $key => $leaderboardDetail) {
                            if ($leaderboardDetail[1] == $iTeam_ID) {
                                $loggedInUserRank = $key + 1; // Adding 1 to convert from array index to rank
                                $formattedPercentage = sprintf("%.2f", $leaderboardDetail[0]);
                                $loggedInScoreAndRank = "(" . $formattedPercentage . ") #" . $loggedInUserRank;
                                break;
                            }
                        }

                        $leaderboardList = array();
                        // Loop through the top 5 teams
                        for ($i = 0; $i < min(5, count($arrLeaderBoardDetails)); $i++) {
                            $leaderboardDetail = $arrLeaderBoardDetails[$i];
                            $formattedPercentage = sprintf("%.2f", $leaderboardDetail[0]);
                            $leaderboardList[] = array(
                                "label" => "# Rank-" . ($i + 1), // Adding 1 to convert from array index to rank
                                "value" => "(" . $formattedPercentage . ") " . $leaderboardDetail[2]
                            );
                        }
                        // Include the rank of the logged-in user in the response
                        $leaderboardList[] = array(
                            "label" => "YOUR RANK",
                            "value" => isset($loggedInScoreAndRank) ? $loggedInScoreAndRank : "N/A"
                        );

                        $arrResponse[] = array(
                            "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryList" => array(
                                array(
                                    "label" => $arrSummaryLabels["OPENING_STOCK_FILLED"],
                                    "value" => $arrOpeningClosingSalesDetails[0],
                                ),
                                array(
                                    "label" => $arrSummaryLabels["CLOSING_STOCK_FILLED"],
                                    "value" => $arrOpeningClosingSalesDetails[1],
                                ),
                                array(
                                    "label" => $arrSummaryLabels["SALES_DETAILS"],
                                    "value" => $arrOpeningClosingSalesDetails[2],
                                ),
                            ),
                        );

                        // Construct the leaderboard section of the response
                        $arrResponse[] = array(
                            "title" => "********************LEADERBOARD********************",
                            "summaryList" => $leaderboardList
                        );
                    }
                } elseif ($db_name === $SOUTH_DB) {
                    // Get today's count
                    $arrTeamTodaySummary = $tableUtil->getRowColumns(
                        "$db_name.$TBL_VANDS_SUMMARY",
                        "start_datetime, end_datetime, total_deliveries, total_sellin_shops" .
                            ", total_town_shops, total_rural_shops, total_village_shops, total_planned_shops",
                        "dstatus = 0 AND team_id = $iTeam_ID AND activity_date = '$currentDate'"
                    );

                    // Get Month count
                    $arrTeamMonthSummary = $tableUtil->getRowColumns(
                        "$db_name.$TBL_VANDS_SUMMARY",
                        "SUM(total_deliveries) AS totalCalls, SUM(total_sellin_shops) AS productiveCalls",
                        "dstatus = 0 AND team_id = $iTeam_ID AND activity_date LIKE '$currentMonth'"
                    );

                    $timeSpentToday = isset($arrTeamTodaySummary, $arrTeamTodaySummary[0]) ?
                        $commonFunctions->getTimeDifference($arrTeamTodaySummary[0], $arrTeamTodaySummary[1], false, false, true) : "0s";
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

                    $arrResponse[] = array(
                        "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                        "summaryList" => array(
                            array(
                                "label" => "Total Time Spent",
                                "value" => $timeSpentToday,
                            ),
                            array(
                                "label" => "Covered Today",
                                "value" => $coveredShopsTodayCount,
                            ),
                            array(
                                "label" => "Covered Town Shops",
                                "value" =>  $coveredTownShopsTodayCount,
                            ),
                            array(
                                "label" => "Covered Rural Shops",
                                "value" => $coveredRuralShopsTodayCount,
                            ),
                            array(
                                "label" => "Covered Village Shops",
                                "value" => $coveredVillageShopsTodayCount,
                            ),
                            array(
                                "label" => "Productive Today",
                                "value" =>  $productiveShopsTodayCount,
                            ),

                        ),
                    );

                    $arrResponse[] = array(
                        "title" => "Monthly Summary",
                        "summaryList" => array(
                            array(
                                "label" => "Total Planned",
                                "value" => isset($totalPlanned) ? $totalPlanned : 0,
                            ),
                            array(
                                "label" => "Covered",
                                "value" => $coveredShopsMtdCount,
                            ),
                            array(
                                "label" => "Productive",
                                "value" => $productiveShopsMtdCount,
                            ),
                        ),
                    );
                }
            }

            $showSalesSummary = isset($arrProjectSummaryDetails["showSalesSummary"]) ?
                $arrProjectSummaryDetails["showSalesSummary"] : false;
            $salesSummaryTable = isset($arrProjectSummaryDetails["salesSummaryTable"]) &&
                $arrProjectSummaryDetails["salesSummaryTable"] ? $arrProjectSummaryDetails["salesSummaryTable"] : null;
            $arrSalesSummaryConfig = isset($arrProjectSummaryDetails["salesSummaryConfig"]) &&
                $arrProjectSummaryDetails["salesSummaryConfig"] ?
                $arrProjectSummaryDetails["salesSummaryConfig"] : null;

            // Today's sales summary
            if ($showSalesSummary && $salesSummaryTable && $arrSalesSummaryConfig) {
                foreach ($arrSalesSummaryConfig as $arrConfig) {
                    $arrSalesQues = $arrConfig["salesSummaryQues"];
                    $salesQues = $commonFunctions->getStringFromArray($arrSalesQues, true, ", ", "quesNo");
                    $salesCond = $arrConfig["salesSummaryCond"];
                    $arrTotalRecordsSummary = isset($arrConfig["totalRecordsSummary"]) ?
                        $arrConfig["totalRecordsSummary"] : array();

                    $rsSummaryAction = null;
                    $iSummaryActionRows = 0;
                    $sSummaryQuery = "SELECT $salesQues FROM $db_name.$salesSummaryTable WHERE pid = $sProject_ID" .
                        " AND team_id = $iTeam_ID AND dstatus = 0 AND capture_date = '$currentDate' $salesCond";
                    $dbConn->ExecuteSelectQuery($sSummaryQuery, $rsSummaryAction, $iSummaryActionRows);

                    $arrSalesSummary = array();
                    $count = 0;
                    if ($iSummaryActionRows > 0) {
                        while ($rowSummary = $dbConn->GetData($rsSummaryAction)) {
                            foreach ($arrSalesQues as $salesQuesIndex => $ques) {
                                $salesValue = $rowSummary[$ques["quesNo"]];
                                $salesColumns = isset($ques["columns"]) && $ques["columns"] ? $ques["columns"] : null;
                                $salesRows = isset($ques["rows"]) && $ques["rows"] ? $ques["rows"] : null;
                                $arrSalesLabels = isset($ques["salesLabels"]) ? $ques["salesLabels"] : null;

                                // if grid
                                if ($salesColumns) {
                                    $arrSalesSummary = getSummaryFromGridDataAsArray(
                                        $arrSalesSummary,
                                        json_decode($salesValue, true),
                                        $arrSalesLabels,
                                        $salesColumns,
                                        $salesRows
                                    );
                                } else {
                                    // not  grid
                                    if (!isset($arrSalesSummary[$salesQuesIndex])) {
                                        $arrSalesSummary[$salesQuesIndex] = array("label" => $arrSalesLabels[0], "value" => 0);
                                    }
                                    $arrSalesSummary[$salesQuesIndex]["value"] += $salesValue > 0 ? $salesValue : 0;
                                }
                            }
                            $count++;
                        }
                    }

                    // Send no of records
                    if (isset($arrTotalRecordsSummary["count"]) && $arrTotalRecordsSummary["count"]) {
                        $arrSalesSummary[] = array(
                            "label" => $arrTotalRecordsSummary["label"],
                            "value" => $count,
                        );
                    }

                    $arrResponse[] = array(
                        "title" => $arrConfig["salesSummaryTitle"],
                        "summaryList" => $arrSalesSummary,
                    );
                }
            }

            // No Summary to display
            if (!$commonFunctions->isNonEmptyArray($arrResponse)) {
                $arrResponse[] = array(
                    "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                    "summaryList" => array(
                        array(
                            "label" => $arrAuthMessages["SUMM01"],
                            "value" => "",
                        ),
                    ),
                );
            }

            responseMessage(array("message" => null, "response" => $arrResponse), 1, array(), $arrCustomResponse);
        } else {
            // No Summary to display
            $arrResponse[] = array(
                "title" => $arrSummaryLabels["TODAYS_SUMMARY"],
                "summaryList" => array(
                    array(
                        "label" => $arrAuthMessages["SUMM01"],
                        "value" => "",
                    ),
                ),
            );
            $commonFunctions->debugLog(
                $arrAuthMessages["SUMM01"],
                $logFileName,
                $logFolderName
            );
            responseMessage(array("message" => null, "response" => $arrResponse), 1);
        }
    } else {
        // Unauthorized phone
        $commonFunctions->debugLog(
            $arrAuthMessages["AUTH06"],
            $logFileName,
            $logFolderName
        );
        $commonFunctions->debugLog(
            "SERVER LOG DATE TIME: $currentDateTime Token: $sToken\r\n$sQuery_Org\r\n" . $arrAuthMessages["AUTH06"],
            $unauthorisedAccessLogFileName,
            $logFolderName
        );
        responseMessage($arrAuthMessages["AUTH06"]);
    }
}

$dbConn->Close();
