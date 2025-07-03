<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";

// $staticSummary = [
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100",
//                     "textColor" => "#ff0000"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Login summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => "fa fa-bath"
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => "fa fa-snowflake-o"
//                 )
//             ],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ],
//                     [
//                         "Product 4",
//                         "101",
//                         "1",
//                         "101"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     )
// ];


$currentMonth = date("Y-m-") . "%";
$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();
$logFileName = "debug_home_screen_data";
$unauthorisedAccessLogFileName = "debug_home_screen_data_unauthorised_access";
$logFolderName = "/summary";

$logResponse = array(
    "log" => true,
    "fileName" => $logFileName,
    "folderName" => $logFolderName,
);

$sToken = "";
if (
    isset($_SERVER["PHP_AUTH_PW"]) && $_SERVER["PHP_AUTH_PW"] &&
    $_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"] === $_SERVER["PHP_AUTH_USER"]
) {
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
        "SERVER LOG DATE TIME: $currentDateTime Token: $sToken\r\n" . $arrAuthMessages["AUTH04"],
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

        $arrResponse = array();

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
            $showDayendSummary = isset($arrProjectSummaryDetails["showDayendSummary"]) ?
                $arrProjectSummaryDetails["showDayendSummary"] : false;
            $showLeaveWeekOffSummary = isset($arrProjectSummaryDetails["showLeaveWeekOffSummary"]) ?
                $arrProjectSummaryDetails["showLeaveWeekOffSummary"] : false;
            $isSeparateAttendanceTable = isset($arrProjectSummaryDetails["isSeparateAttendanceTable"]) ?
                $arrProjectSummaryDetails["isSeparateAttendanceTable"] : false;
            $attendanceTable = isset($arrProjectSummaryDetails["attendanceTable"]) &&
                $arrProjectSummaryDetails["attendanceTable"] ? $arrProjectSummaryDetails["attendanceTable"] :
                $TBL_ATTENDANCE;
            $attendanceCond = isset($arrProjectSummaryDetails["attendanceCond"]) &&
                $arrProjectSummaryDetails["attendanceCond"] ? $arrProjectSummaryDetails["attendanceCond"] : "";
            $attendanceCondOther = isset($arrProjectSummaryDetails["attendanceCondOther"]) &&
                $arrProjectSummaryDetails["attendanceCondOther"] ?
                $arrProjectSummaryDetails["attendanceCondOther"] : "";
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
            if (($showAttendanceSummary || $showDayendSummary || $showLeaveWeekOffSummary)) {
                // Attendance is stored in separate/common table
                if ($isSeparateAttendanceTable) {
                    $arrTodayAttendance = $tableUtil->getRowColumns(
                        "$db_name.$attendanceTable",
                        "att_id, capture_datetime",
                        "dstatus = 0 AND team_id = $iTeam_ID AND call_type = '0'" .
                            " AND capture_date = '$currentDate' $attendanceCond"
                    );
                    $arrTodayDayend = $tableUtil->getRowColumns(
                        "$db_name.$attendanceTable",
                        "att_id, capture_datetime",
                        "dstatus = 0 AND team_id = $iTeam_ID AND call_type = '1'" .
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
                    $arrTodayDayend = $tableUtil->getRowColumns(
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
                    $arrLeave = $tableUtil->getRowColumn(
                        "$db_name.$attendanceTable",
                        "ques_1",
                        "ques_0 = 'Leave Report' AND dstatus = 0 AND team_id = $iTeam_ID AND capture_date = '$currentDate' $attendanceCond"
                    );
                }

                // Today's Login Details
                $isPresentToday = isset($arrTodayAttendance, $arrTodayAttendance[0]) && $arrTodayAttendance[0] ?
                    true : false;
                $isDayendToday = isset($arrTodayDayend, $arrTodayDayend[0]) && $arrTodayDayend[0] ?
                    true : false;
                $isLeaveWeekOffToday = isset($arrLeave, $arrLeave) && $arrLeave ?
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

                // if ($db_name === $SNPL_DB) {
                //     $arrResponse[0] = array(
                //         "title" => "हाजिरीको सारांश",
                //         "summaryList" => array(
                //             array(
                //                 "label" => "आजको हाजिरी",
                //                 "value" => $isPresentToday ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                //             ),
                //             array(
                //                 "label" => "यस महिनाको कुल हाजिरी",
                //                 "value" => (int) $mtdPresents,
                //             ),
                //         ),
                //     );
                // } else {
                $noOfDays = 0;
                if ($attendanceShowNoDaysInAMonth) {
                    $noOfDays = getCountOfDaysExcluding($attendanceExcludeWeekDay);
                    $mtdPresents = "$mtdPresents/$noOfDays";
                }

                $arrResponse[] = array(
                    "summaryTitle" => $arrSummaryLabels["ATTENDANCE_SUMMARY"],
                    "summaryData" => array(
                        "labelList" => array(
                            array(
                                "label" => $arrSummaryLabels["TODAYS_ATTENDANCE"],
                                "value" => $isPresentToday ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                            ),
                            array(
                                "label" => $arrSummaryLabels["TODAYS_DAYEND"],
                                "value" => $isDayendToday ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                            ),
                            array(
                                "label" => $arrSummaryLabels["TODAYS_LEAVE_WEEKEND"],
                                "value" => $isLeaveWeekOffToday == 'Week OFF' ? $arrSummaryLabels["YES"] : $arrSummaryLabels["NO"],
                            ),
                            array(
                                "label" => $attendanceMtdLabel ?
                                    (isset($arrSummaryLabels[$attendanceMtdLabel]) ?
                                        $arrSummaryLabels[$attendanceMtdLabel] :
                                        $attendanceMtdLabel) : $arrSummaryLabels["MTD_ATTENDANCE"],
                                "value" => $mtdPresents,
                            ),
                        ),
                        "cardList" => [],
                        "tableData" => null
                    ),
                );
            }
            // Today's Login time
            // if ($attendanceShowLoginTime) {
            //     $arrResponse[0]["summaryList"][] = array(
            //         "label" => $arrSummaryLabels["LOGIN_TIME"],
            //         "value" => $todayAttendanceLoginTime,
            //     );
            // }

            // Today's Logout time
            // if ($attendanceShowLogoutTime) {
            //     $arrResponse[0]["summaryList"][] = array(
            //         "label" => $arrSummaryLabels["LOGOUT_TIME"],
            //         "value" => $todayAttendanceLogoutTime,
            //     );
            // }
            // }
            // Other summary
            $showOtherSummary = isset($arrProjectSummaryDetails["showOtherSummary"]) ?
                $arrProjectSummaryDetails["showOtherSummary"] : false;

            if ($showOtherSummary) {
                if ($db_name === $ITCNEW_DB) {
                    if ($sProject_ID == 118) {
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
                            "summaryTitle" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryData" => array(
                                "cardList" => array(
                                    array(
                                        "label" => $arrSummaryLabels["TOTAL_CALLS"],
                                        "value" => (string) $iVisited,
                                    ),
                                    array(
                                        "label" => $arrSummaryLabels["PRODUCTIVE_CALLS"],
                                        "value" => (string) $iBought,
                                    ),
                                    array(
                                        "label" => $arrSummaryLabels["NON_PRODUCTIVE_CALLS"],
                                        "value" => (string) $iNotBought,
                                    ),
                                    array(
                                        "label" => $brand,
                                        "value" => (string) $count,
                                    ),
                                    array(
                                        "label" => $brand1,
                                        "value" => (string) $count1,
                                    ),
                                    array(
                                        "label" => $brand2,
                                        "value" => (string) $count2,
                                    ),
                                ),
                                "labelList" => [],
                                "tableData" => null
                            ),
                        );
                    } elseif (
                        ($sProject_ID == 111 || $sProject_ID == 114 || $sProject_ID == 115 ||
                            $sProject_ID == 116 || $sProject_ID == 117 || $sProject_ID == 123 ||
                            $sProject_ID == 124 || $sProject_ID == 125 || $sProject_ID == 126 || $sProject_ID == 127 || $sProject_ID == 128 || $sProject_ID == 129 || $sProject_ID == 139) &&
                        ($respTable && $respTable != "tblsurvey_response_details")
                    ) {
                        $arrOpeningClosingSalesDetails = $tableUtil->getRowColumns(
                            "$db_name.$respTable",
                            "MAX(CASE WHEN ques_0 = 'Opening Stock' THEN 'Yes' ELSE 'No' END)" .
                                " AS opening_stock_filled, MAX(CASE WHEN ques_0 = 'Closing Stock' THEN 'Yes'" .
                                " ELSE 'No' END) AS closing_stock_filled, MAX(CASE WHEN ques_0 = 'Sales Details'" .
                                " THEN 'Yes' ELSE 'No' END) AS sales_details_filled",
                            "dstatus = 0 AND project_id = $sProject_ID AND team_id = $iTeam_ID" .
                                " AND capture_date = '$currentDate'"
                        );
                        $arrLeaderBoardDetails = $tableUtil->getRowsColumns(
                            "$db_name.tbltimesheet AS ts",
                            "AVG(ts.total_percentage) AS avg_percentage, team_id, (SELECT team_name FROM" .
                                " $db_name.tblproject_team WHERE team_id = ts.team_id) AS team_name",
                            "ts.dstatus = 0 AND ts.project_id = $sProject_ID GROUP BY ts.team_id" .
                                " ORDER BY avg_percentage DESC"
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
                            $leaderboardList[] = array(
                                // Adding 1 to convert from array index to rank
                                "label" => $rankLabel . " Rank-" . ($i + 1),
                                "value" => "(" . $formattedPercentage . ") " . $leaderboardDetail[2]
                            );
                        }
                        // Include the rank of the logged-in user in the response
                        $leaderboardList[] = array(
                            "label" => "⭐ YOUR RANK",
                            "value" => isset($loggedInScoreAndRank) ? $loggedInScoreAndRank : "N/A"
                        );

                        $arrResponse[] = array(
                            "summaryTitle" => $arrSummaryLabels["TODAYS_SUMMARY"],
                            "summaryData" => array(
                                "cardList" => array(
                                    array(
                                        "label" => $arrSummaryLabels["OPENING_STOCK_FILLED"],
                                        "value" => isset($arrOpeningClosingSalesDetails[0]) ? $arrOpeningClosingSalesDetails[0] : "0",
                                    ),
                                    array(
                                        "label" => $arrSummaryLabels["CLOSING_STOCK_FILLED"],
                                        "value" => isset($arrOpeningClosingSalesDetails[1]) ? $arrOpeningClosingSalesDetails[1] : "0",
                                    ),
                                    array(
                                        "label" => $arrSummaryLabels["SALES_DETAILS"],
                                        "value" => isset($arrOpeningClosingSalesDetails[2]) ? $arrOpeningClosingSalesDetails[2] : "0",
                                    ),
                                ),
                                "labelList" => [],
                                "tableData" => null
                            ),
                        );

                        // Construct the leaderboard section of the response
                        $arrResponse[] = array(
                            "summaryTitle" => "🏆LEADERBOARD🏆",
                            "summaryData" => array(
                                "cardList" => $leaderboardList,
                                "labelList" => [],
                                "tableData" => null
                            ),
                        );
                    }
                }
            }

            responseMessage(array("message" => null, "response" => array("summaryList" => $arrResponse)), 1);
        } else {
            // No Summary to display
            $arrResponse[] = array(
                "summaryTitle" => $arrSummaryLabels["TODAYS_SUMMARY"],
                "summaryData" => array(
                    "labelList" => array(
                        array(
                            "label" => $arrAuthMessages["SUMM01"],
                            "value" => "",
                        ),
                    ),
                ),
            );
            $commonFunctions->debugLog(
                $arrAuthMessages["SUMM01"],
                $logFileName,
                $logFolderName
            );
            responseMessage(array("message" => null, "response" => array("summaryList" => $arrResponse)), 1);
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

// responseMessage(array("response" => array("summaryList" => $staticSummary)), 1);
$dbConn->Close();
