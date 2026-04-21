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
class mdoSummary
{
    private $_dbConn = null;
    private $_tables = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
    }

    final public function processSummary()
    {
        $arrTeamType = array(0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
        $arrInfraType = array(7 => "MDO", 10 => "FSO");
        $currentDate = currentDate();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.pro_id, a.uni_id, a.call_time, a.capture_date, MIN(a.capture_datetime) AS startMarket, MAX(a.capture_datetime) AS lastMarket, a.capture_datetime, a.lt, a.lg, a.wd_code, a.ds_name, a.type" .
            ", a.route_name, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, SUM(a.ques_5) AS surveyVol, SUM(a.ques_6) AS surveyVal, SUM(a.ques_7) AS lineCut, a.ques_8, a.ques_9, a.ques_10, a.ques_11" .
            ", a.distance_in_meter, b.team_id, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.branch_id, c.district, c.branch_name, c.main_branch, a.lt,a.lg FROM tblsurvey_response_details_mdo AS a" .
            ", $projectTeamTable AS b, $branchTable AS c WHERE a.capture_date = '$currentDate' AND a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = 10 AND a.pro_id > 0 GROUP BY a.capture_date, a.team_id ORDER BY a.capture_datetime DESC";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $branchId = $row["branch_id"];
                $routeName = $row["route_name"];
                $date = $row["capture_date"];
                $infraType = $row["is_type"];
                $week = $this->getWeekNumber($date);
                $dayOfWeek = date('D', strtotime($date));
                $teamId = $row["team_id"];
                $mdoName = $row["team_name"];
                $typeOfWork = $row["ques_1"];
                $workWdCode = $row["wd_code"];
                $arrWdDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$workWdCode'");
                $shopId = $row["ques_4"];
                if ($row["type"] == 6 || $row["type"] == 8 || $row["type"] == 9) {
                    $dsId = $shopId ? getRowColumn($this->_dbConn, "tblroute_details_breeze", "team_id", "rec_id = $shopId") : "";
                    $pannedOutlets = $dsId ? getRowColumn($this->_dbConn, "tblroute_details_breeze", "COUNT(rec_id)", "team_id = '$dsId'") : "";
                    $orderShop = 0;
                    $addShop = 0;
                    $totalShops = 0;
                    $sellInShop = 0;
                    $totalSale = 0;
                    $totalUlc = 0;
                    $arrMdoSurveyedOutlets = array();
                    $mdoSurveyedOutlets = 0;
                    $sellbByDsMdoSurveyed = 0;
                } else {
                    $dsId = $shopId ? getRowColumn($this->_dbConn, "tblroute_details", "team_id", "rec_id = $shopId") : "";
                    $pannedOutlets = $dsId ? getRowColumn($this->_dbConn, "tblroute_details", "COUNT(rec_id)", "route_name = '$routeName' AND team_id = $dsId") : "";
                    $orderShop = $dsId ? getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = '0' AND capture_date = '$date' AND team_id = $dsId") : 0;
                    $addShop = $dsId ? getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' AND capture_date = '$date' AND team_id = $dsId") : 0;
                    $totalShops = $orderShop + $addShop;
                    $allBrandCols = getRowsColumns($this->_dbConn, $branchPickupStockTable, "summary_column_name, product_name", "dstatus = 0 AND branch_id = $branchId", array(), true);
                    $productCols = [];
                    $productNames = [];

                    foreach ($allBrandCols as $colRow) {
                        $productCols[] = $colRow[0];
                        $productNames[] = $colRow[1];
                    }
                    $summaryColumns = implode(") + SUM(", $productCols);
                    $sumColumns = "SUM($summaryColumns)";
                    $totalSale = 0;
                    if ($dsId) {
                        $sQuery2 = "SELECT $sumColumns AS totalSum FROM $respTable WHERE dstatus = 0 AND team_id = '$dsId' AND capture_date = '$date'";

                        $sAction2 = null;
                        $iRows2 = 0;

                        $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
                        if ($iRows2 > 0) {
                            while ($row2 = $this->_dbConn->GetData($sAction2)) {
                                $totalSale = $row2['totalSum'] ?? 0;
                            }
                        }

                        $summaryColumnsUlc = implode(",", $productCols);
                        $sQuery3 = "SELECT $summaryColumnsUlc FROM $respTable WHERE dstatus = 0 AND team_id = '$dsId' AND capture_date = '$date'";

                        $sAction3 = null;
                        $iRows3 = 0;
                        $totalUniqueProducts = []; // store unique products across ALL records
                        $recordCount = 0;
                        $perRecordUlc = []; // store ULC per record

                        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);
                        if ($iRows3 > 0) {
                            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                                $recordCount++;
                                $seenProducts = []; // for this record only
                                $ulc = 0;
                                foreach ($allBrandCols as $colRow) {
                                    $colName     = $colRow[0]; // summary_column_name
                                    $productName = $colRow[1]; // product name
                                    $value       = floatval($row3[$colName]);

                                    if ($value > 0) {
                                        // Count for this record
                                        if (!in_array($productName, $seenProducts)) {
                                            $seenProducts[] = $productName;
                                            $ulc++;
                                        }

                                        // Count for total unique across all records
                                        if (!in_array($productName, $totalUniqueProducts)) {
                                            $totalUniqueProducts[] = $productName;
                                        }
                                    }
                                }

                                $perRecordUlc[] = $ulc;
                            }
                        }
                        // Final totals
                        $totalUlc = count($totalUniqueProducts); // unique products across all records
                    }
                    $sellInShop = $dsId ? getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_4 = 'Yes' AND dstatus = '0' AND capture_date = '$date' AND team_id = $dsId HAVING $sumColumns > 0") : 0;
                    $arrMdoSurveyedOutlets = getRowsColumn($this->_dbConn, "tblsurvey_response_details_mdo", "ques_4", "dstatus = '0' AND capture_date = '$date' AND team_id = $teamId");
                    $mdoSurveyedOutlets = implode(",", $arrMdoSurveyedOutlets);
                    $sellbByDsMdoSurveyed = $dsId ? getRowColumn($this->_dbConn, $respTable, "$sumColumns AS totalSum", "ques_4 = 'Yes' AND dstatus = '0' AND capture_date = '$date' AND team_id = $dsId AND ques_3 IN ($mdoSurveyedOutlets)") : 0;
                }

                $dsName = $row["ds_name"];
                $parts = explode(" - ", $dsName, 2);
                $dsNameOnly = $parts[0];
                $dsType = $parts[1];
                $startTime = getRowColumn($this->_dbConn, "tblattendance", "MIN(capture_datetime)", "capture_date = '$date' AND team_id = $teamId AND call_type = '0'");
                $arrDayEndDetails = getRowColumns($this->_dbConn, "tblattendance", "MIN(capture_datetime), other_details, distance", "capture_date = '$date' AND team_id = $teamId AND call_type = '1'");
                if ($arrDayEndDetails) {
                    $endTime = isset($arrDayEndDetails[0]) ? $arrDayEndDetails[0] : null;
                    $arrDayEndOtherDetails = json_decode($arrDayEndDetails[1], true);
                    $dayEndOutlet = $arrDayEndOtherDetails['outlet'] ?? 0;
                    $salesVol = $arrDayEndOtherDetails['SalesVolume'] ?? 0;
                    $salesValue = $arrDayEndOtherDetails['SalesValue'] ?? 0;
                    $distanceInKm = isset($arrDayEndDetails[2]) ? $arrDayEndDetails[2] : 0;
                    // $dayEndOutlet = "";
                    // $salesVol = "";
                    // $salesValue = "";
                } else {
                    $endTime = "";
                    $arrDayEndOtherDetails = "";
                    $dayEndOutlet = "";
                    $salesVol = "";
                    $salesValue = "";
                    $distanceInKm = "";
                }
                $marketStartTime = isset($row["startMarket"]) ? $row["startMarket"] : "";
                $marketEndTime = isset($row["lastMarket"]) ? $row["lastMarket"] : "";
                $timeInMarket = getTimeDifferenceInString($marketStartTime, $marketEndTime, false, false, true);
                $timeSpent =  getTimeDifferenceInString($startTime, $endTime ? $endTime : $marketEndTime, false, false, true);
                $arrfist_lastTime = getRowColumns($this->_dbConn, "tblsurvey_response_details_mdo", "MIN(capture_datetime), MAX(capture_datetime)", "capture_date = '$date' AND team_id = $teamId");
                $firstOutletTime = isset($arrfist_lastTime[0]) ? $arrfist_lastTime[0] : "";
                $lastOutletTime = isset($arrfist_lastTime[1]) ? $arrfist_lastTime[1] : "";
                $coverdOutlets = getRowColumn($this->_dbConn, "tblsurvey_response_details_mdo", "COUNT(DISTINCT ques_4)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
                $surveyVol = $row["surveyVol"] ?? 0;
                $surveyVal = $row["surveyVal"] ?? 0;
                $lineCut = $row["lineCut"] ?? 0;
                if (empty($distanceInKm)) {
                    $distanceInKm =  getRowColumn($this->_dbConn, "tblsurvey_response_details_mdo", "distance_in_meter", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' ORDER BY pro_id DESC");
                }
                // Convert 6 hours into minutes
                $requiredmin = 360;
                if ($infraType == 7) {
                    if ($timeSpent >= $requiredmin && $distanceInKm >= 10) {
                        $qualified = '1';
                    } else {
                        $qualified = '0';
                    }
                } elseif ($infraType == 10) {
                    $qualified = '1';
                }

                $istatus = isRecordExist($this->_dbConn, "tblmdo_summary", "sum_id", "mdo_id = $teamId AND capture_date = '$date'");

                if ($istatus == 1) {
                    $cols = "qualified = ?, start_time = ?, end_time = ?, resp_startdatetime = ?, resp_enddatetime = ?, total_time_spent = ?, time_in_market = ?, total_km_travelled = ?, planned_outlets = ?, surveyed_outlets_mdo = ?, survey_vol = ?, survey_val = ?, line_cut = ?" .
                        ", outlet_visited = ?, sales_vol = ?, sales_val = ?, visited_outlets = ?, billed_outlets = ?, total_sale = ?, sale = ?, total_line_cut = ?";
                    $arrParams = array(
                        $qualified, isset($startTime) ? $startTime : null, isset($endTime) ? $endTime : null, isset($firstOutletTime) ? $firstOutletTime : null, isset($lastOutletTime) ? $lastOutletTime : null, $timeSpent, $timeInMarket, $distanceInKm, $pannedOutlets, $coverdOutlets,
                        $surveyVol, $surveyVal, $lineCut, $dayEndOutlet, $salesVol, $salesValue, $totalShops, $sellInShop ?? 0, $totalSale ?? 0, $sellbByDsMdoSurveyed ?? 0, $totalUlc, $teamId, $date
                    );

                    updateRecord($this->_dbConn, "tblmdo_summary", $cols, "mdo_id = ? AND capture_date = ?", $arrParams);
                } else {
                    $addCols = "capture_date, district, branch_id, main_branch, branch_name, circle, section, mdo_type, mdo_id, mdo_name, week, qualified, present, type_of_work, wd_code, wd_name, wd_market, pop_group, ds_id, type, ds_name, route_name, start_time, end_time, resp_startdatetime" .
                        ", resp_enddatetime, total_time_spent, time_in_market, total_km_travelled, planned_outlets, surveyed_outlets_mdo, survey_vol, survey_val, line_cut, outlet_visited, sales_vol, sales_val, visited_outlets, billed_outlets, total_sale, sale, total_line_cut";
                    $addVals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                    $arrAddParams = array(
                        $date,
                        $row["district"],
                        $branchId,
                        $row["main_branch"],
                        $row["branch_name"],
                        $row["circle"],
                        $row["section"],
                        isset($infraType) ? $arrInfraType[$infraType] : "",
                        $teamId,
                        $mdoName,
                        $week,
                        $qualified,
                        "1",
                        $typeOfWork,
                        $workWdCode,
                        isset($arrWdDetails[0]) ? $arrWdDetails[0] : "",
                        isset($arrWdDetails[1]) ? $arrWdDetails[1] : "",
                        isset($arrWdDetails[2]) ? $arrWdDetails[2] : "",
                        $dsId,
                        $dsType,
                        $dsNameOnly,
                        $routeName,
                        $startTime,
                        isset($endTime) ? $endTime : null,
                        $firstOutletTime,
                        $lastOutletTime,
                        $timeSpent,
                        $timeInMarket,
                        $distanceInKm,
                        $pannedOutlets,
                        $coverdOutlets,
                        $surveyVol,
                        $surveyVal,
                        $lineCut,
                        $dayEndOutlet,
                        $salesVol,
                        $salesValue,
                        $totalShops,
                        $sellInShop ?? 0,
                        $totalSale ?? 0,
                        $sellbByDsMdoSurveyed ?? 0,
                        $totalUlc
                    );
                    addRecord($this->_dbConn, "tblmdo_summary", $addCols, $addVals, $arrAddParams);
                }
            }
        }

        // ---------------------------------------------------------
        // STEP 2: HANDLE TEAMS WHO DID ATTENDANCE BUT NO DS WORK
        // ---------------------------------------------------------
        $startDate = new DateTime('2025-09-16');
        $endDate = new DateTime('2025-10-13');

        while ($startDate <= $endDate) {
            $date = $startDate->format('Y-m-d');
            $week = $this->getWeekNumber($date);
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $branchTable = $this->_tables["BRANCH_TABLE"];
            $infraTypeArr = array(7 => "MDO", 10 => "FSO");

            $iTeamRows = 0;
            $rsTeamAction = null;

            // Teams who marked attendance but did NOT work with DS
            $sTeamQuery = "SELECT a.team_id, a.capture_date, a.capture_datetime, a.lt, a.work_with, a.other_details, a.lg, b.team_name, b.branch_id, b.is_type, b.circle, b.section, c.district, c.branch_name, c.main_branch FROM tblattendance AS a JOIN $projectTeamTable AS b ON" .
                " a.team_id = b.team_id JOIN $branchTable AS c ON b.branch_id = c.branch_id WHERE a.dstatus = 0 AND b.s_id = 10 AND a.capture_date = '$date' AND a.call_type = '0' AND a.team_id NOT IN" .
                " (SELECT team_id FROM tblsurvey_response_details_mdo WHERE dstatus = 0 AND capture_date = '$date') GROUP BY a.team_id ORDER BY a.capture_datetime DESC";

            $this->_dbConn->ExecuteSelectQuery($sTeamQuery, $rsTeamAction, $iTeamRows);

            if ($iTeamRows > 0) {
                while ($rowTeam = $this->_dbConn->GetData($rsTeamAction)) {
                    $teamId = $rowTeam["team_id"];
                    $infraType = $rowTeam["is_type"];
                    $mdoName = $rowTeam["team_name"];
                    $startTime = $rowTeam["capture_datetime"];
                    $date = $rowTeam["capture_date"];
                    $endDetails = getRowColumns($this->_dbConn, "tblattendance", "MIN(capture_datetime), distance, other_details", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                    $endTime = isset($endDetails[0]) ? $endDetails[0] : null;
                    $distanceInKm = $endDetails[1] ?? 0;
                    $arrDayEndOtherDetails = json_decode($endDetails[2] ?? '{}', true);
                    $dayEndOutlet = $arrDayEndOtherDetails['outlet'] ?? 0;
                    $salesVol = $arrDayEndOtherDetails['SalesVolume'] ?? 0;
                    $salesValue = $arrDayEndOtherDetails['SalesValue'] ?? 0;
                    $timeSpent = $endTime ? getTimeDifferenceInString($startTime, $endTime, false, false, true) : 0;

                    $requiredMin = 360;
                    $qualified = ($timeSpent >= $requiredMin && $distanceInKm >= 10) ? '1' : '0';

                    // work_with = 0 → With DS, 1 → With AE, etc.
                    $attDetails = json_decode($rowTeam["other_details"], true);
                    $dsDetails = $attDetails['selectRouteYouAreGoingOn'] ?? [];

                    $typeOfWork = "Independent market work";
                    $wdCode = "";
                    $attDsNameOnly = "";
                    $attDsType = "";
                    $dsId = "";

                    if (isset($rowTeam["work_with"])) {
                        switch ($rowTeam["work_with"]) {
                            case 0:
                                $typeOfWork = "Market work with DS";
                                $wdCode = $dsDetails[0] ?? "";
                                $attDsName = $dsDetails[1] ?? "";
                                $parts = explode(" - ", $attDsName, 2);
                                $attDsNameOnly = $parts[0] ?? "";
                                $attDsType = $parts[1] ?? "";
                                $dsId = getRowColumn($this->_dbConn, "tblmdo_offline_data", "ds_id", "dstatus = 0 AND wd_code = '$wdCode' AND ds_name = '$attDsNameOnly'");
                                break;
                                $routeName = $dsDetails[2];
                            case 1:
                                $typeOfWork = "Market work with AE";
                                $wdCode = $dsDetails[1] ?? "";
                                $routeName = "";
                                break;
                            case 2:
                                $typeOfWork = "Market work with GT TL";
                                $wdCode = $dsDetails[1] ?? "";
                                $routeName = "";
                                break;
                            case 3:
                                $typeOfWork = "Independent market work";
                                $routeName = "";
                                break;
                        }
                    }

                    $arrWdDetails = $wdCode ? getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$wdCode'") : ["", "", ""];

                    // Insert/update into tblmdo_summary
                    $exists = isRecordExist($this->_dbConn, "tblmdo_summary", "sum_id", "mdo_id = $teamId AND capture_date = '$date'");

                    if ($exists == 1) {
                        $cols = "qualified=?, start_time=?, end_time=?, total_time_spent=?, total_km_travelled=?, outlet_visited=?, sales_vol=?, sales_val=?, type_of_work=?, route_name = ?, wd_code=?, wd_name=?, wd_market=?, pop_group=?";
                        $params = [$qualified, $startTime, $endTime, $timeSpent, $distanceInKm, $dayEndOutlet, $salesVol, $salesValue, $typeOfWork, $routeName, $wdCode, $arrWdDetails[0], $arrWdDetails[1], $arrWdDetails[2], $teamId, $date];
                        updateRecord($this->_dbConn, "tblmdo_summary", $cols, "mdo_id=? AND capture_date=?", $params);
                    } else {
                        $addCols = "capture_date, district, main_branch, branch_name, circle, section, mdo_type, mdo_id, mdo_name, week, qualified, present, type_of_work, route_name, wd_code, wd_name, wd_market" .
                            ", pop_group, ds_id, ds_name, start_time, end_time, total_time_spent, total_km_travelled, outlet_visited, sales_vol, sales_val";
                        $addVals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $params = [
                            $date,
                            $rowTeam["district"],
                            $rowTeam["main_branch"],
                            $rowTeam["branch_name"],
                            $rowTeam["circle"],
                            $rowTeam["section"],
                            $infraTypeArr[$infraType] ?? "",
                            $teamId,
                            $mdoName,
                            $week,
                            $qualified,
                            "1",
                            $typeOfWork,
                            $routeName,
                            $wdCode,
                            $arrWdDetails[0],
                            $arrWdDetails[1],
                            $arrWdDetails[2],
                            $dsId,
                            $attDsNameOnly,
                            $startTime,
                            $endTime,
                            $timeSpent,
                            $distanceInKm,
                            $dayEndOutlet,
                            $salesVol,
                            $salesValue
                        ];
                        addRecord($this->_dbConn, "tblmdo_summary", $addCols, $addVals, $params);
                    }
                }
            }

            $startDate->modify('+1 day');
        }
    }

    private function getWeekNumber($date)
    {
        $day = (int)date('j', strtotime($date)); // Get day of the month

        if ($day >= 1 && $day <= 7) {
            return "Week 1";
        } elseif ($day >= 8 && $day <= 14) {
            return "Week 2";
        } elseif ($day >= 15 && $day <= 21) {
            return "Week 3";
        } else {
            return "Week 4";
        }
    }
}

$processResponse = new mdoSummary($dbConn);
$processResponse->processSummary();
