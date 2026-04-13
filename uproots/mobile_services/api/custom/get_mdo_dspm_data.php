<?php

// Used in ITC Phase 2 setup to get the DSPM in MDO APP

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class getMdoDSPM extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_mdo_dspm";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getMdoDSPM()
    {
        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];

        // $arrOtherSummary = [];
        $mdoLeaderboardData = [];
        $months = array(
            date('Y-m', strtotime('-2 month')),  // Previous 2 months
            date('Y-m', strtotime('-1 month')), // Previous month
            date('Y-m'),
        );

        foreach ($months as $month) {
            $cardItems = array();
            list($year, $newMonth) = explode('-', $month);
            $datesInMonth = $this->tableUtil->getRowsColumn("$dbName.tblattendance", "capture_date", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month'", array(), true);
            $Query = "SELECT type, COUNT(DISTINCT CONCAT(ds_name, '_', DATE(capture_date))) AS cnt FROM $dbName.tblattendance WHERE dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND type IN (0, 6, 8, 9) GROUP BY type";
            $sAction = null;
            $sRows = 0;
            $this->dbConn->ExecuteSelectQuery($Query, $sAction, $sRows);
            // Default values (in case a type is missing from the result)
            $vanDsMtdCount    = 0;
            $rmdDsMtdCount    = 0;
            $stokiestMtdCount = 0;
            $fmcgMtdCount     = 0;
            $rmdScpMtdCount     = 0;

            if ($sRows > 0) {
                while ($row = $this->dbConn->GetData($sAction)) {
                    switch ($row['type']) {
                        case 0:
                            $vanDsMtdCount    = $row['cnt'];
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
                    $rmdScpMtdCount = $rmdDsMtdCount + $stokiestMtdCount + $fmcgMtdCount;
                }
            }
            $gtTlCount = $this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT capture_date)", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND work_with = 2 AND call_type = '0'");
            $aeCount = $this->tableUtil->getRowColumn("$dbName.tblattendance", "COUNT(DISTINCT capture_date)", "dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND work_with = 1 AND call_type = '0'");

            $Query1 = "SELECT team_id, MIN(capture_datetime) AS startTime, capture_Date FROM $dbName.tblattendance WHERE dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND call_type = '0' GROUP BY capture_date";
            $sAction1 = null;
            $sRows1 = 0;
            $daysCount = 0;
            $this->dbConn->ExecuteSelectQuery($Query1, $sAction1, $sRows1);
            if ($sRows1 > 0) {
                while ($row1 = $this->dbConn->GetData($sAction1)) {
                    $teamId = $row1['team_id'];
                    $startTime = $row1['startTime'];
                    $date = $row1['capture_Date'];
                    $dayEnd = $this->tableUtil->getRowColumn("$dbName.tblattendance", "MIN(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                    $marketEnd = $this->tableUtil->getRowColumn("$dbName.tblsurvey_response_details_mdo", "MAX(capture_datetime)", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date'");
                    $endTime = !empty($dayEnd) ? $dayEnd : (!empty($marketEnd) ? $marketEnd : 0);
                    if ($startTime && $endTime) {
                        $totalTime = $this->commonFunctions->getTimeDifference($startTime, $endTime, false, true);
                        if ($totalTime >= 360) {
                            $daysCount++;
                        }
                    }
                }
            }

            $isLocked = 1;
            if ($vanDsMtdCount >= 6 && $rmdScpMtdCount >= 10 && $gtTlCount >= 2 && $aeCount >= 2 && $daysCount >= 18) {
                $isLocked = 0;
            }

            $Query2 = "SELECT team_id, capture_date, SUM(ques_5) AS sale, AVG(ques_7) AS alc FROM $dbName.tblsurvey_response_details_mdo WHERE dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND type IN (6, 8) GROUP BY capture_date";
            $sAction2 = null;
            $sRows2 = 0;
            $totalIncentive = 0;
            $this->dbConn->ExecuteSelectQuery($Query2, $sAction2, $sRows2);
            if ($sRows2 > 0) {
                while ($row2 = $this->dbConn->GetData($sAction2)) {
                    $teamId = $row2['team_id'];
                    $sale = $row2['sale'];
                    $alc  = round((float)$row2['alc']); // average linecut
                    $dayIncentive = 0;
                    if ($sale >= 4 && $alc >= 6) {
                        $dayIncentive = 120;
                    } elseif ($sale >= 3 && $alc >= 5) {
                        $dayIncentive = 100;
                    } elseif ($sale >= 2 && $alc >= 4) {
                        $dayIncentive = 75;
                    }

                    $totalIncentive += $dayIncentive;
                    $totalIncentive = min($totalIncentive, 1200);
                }
            }

            $Query3 = "SELECT team_id, capture_date, COUNT(DISTINCT ques_4) AS uob, AVG(ques_7) AS alc FROM $dbName.tblsurvey_response_details_mdo WHERE dstatus = 0 AND team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = '$month' AND type = 0 GROUP BY capture_date";
            $sAction3 = null;
            $sRows3 = 0;
            $totalIncentive2 = 0;
            $this->dbConn->ExecuteSelectQuery($Query3, $sAction3, $sRows3);
            if ($sRows3 > 0) {
                while ($row3 = $this->dbConn->GetData($sAction3)) {
                    $teamId = $row3['team_id'];
                    $uob = $row3['uob'];
                    $alc  = round((float)$row3['alc']); // average linecut
                    $dayIncentive2 = 0;
                    if ($uob >= 18 && $alc >= 6) {
                        $dayIncentive2 = 120;
                    } elseif ($uob >= 16 && $alc >= 5) {
                        $dayIncentive2 = 100;
                    } elseif ($uob >= 14 && $alc >= 4) {
                        $dayIncentive2 = 75;
                    }

                    $totalIncentive2 += $dayIncentive2;
                    $totalIncentive2 = min($totalIncentive2, 1200);
                }
            }

            $acessTeams = $this->tableUtil->getRowsColumn("$dbName.tblmdo_access", "teams", "dstatus = 0 AND mdo_id = $teamId AND is_type IN (6, 8)");
            $teams = "'" . implode("','", $acessTeams) . "'";

            $Query4 = "SELECT capture_date, value_m, outlet_re_visit, total_sale FROM $dbName.tblbreeze_response_data WHERE dstatus = 0 AND ds_id IN ($teams) AND DATE_FORMAT(capture_date, '%Y-%m') = '$month'";
            $sAction4 = null;
            $sRows4 = 0;
            $daysCriteria1 = 0; // >=14 UOB and >=2 sales
            $daysCriteria2 = 0; // >=18 UOB and >=4 sales
            $incentiveCriteria1 = 0;
            $incentiveCriteria2 = 0;
            $this->dbConn->ExecuteSelectQuery($Query4, $sAction4, $sRows4);
            if ($sRows4 > 0) {
                while ($row4 = $this->dbConn->GetData($sAction4)) {
                    $valueM = $row4['value_m'];
                    $outletReVisit  = $row4['outlet_re_visit'];
                    $totalSale  = $row4['total_sale'];
                    if ($valueM > 0) {
                        $sale = $totalSale / $valueM;
                    } else {
                        $sale = 0;
                    }

                    // Criteria 1
                    if ($outletReVisit >= 14 && $sale >= 2) {
                        $daysCriteria1++;
                    }

                    // Criteria 2
                    if ($outletReVisit >= 18 && $sale >= 4) {
                        $daysCriteria2++;
                    }
                }

                $targetDays = 18;

                $incentiveCriteria1 = min(($daysCriteria1 / $targetDays) * 1500, 1500);
                $incentiveCriteria2 = min(($daysCriteria2 / $targetDays) * 4000, 4000);
            }

            $totalEarned = $totalIncentive + $totalIncentive2 + $incentiveCriteria1 + $incentiveCriteria2;

            $bannerItems = [];
            for ($i = 1; $i <= 4; $i++) {
                $id = "";
                $label = "";
                $icon = "";
                $current_value = "";
                $target_value = "";
                $max_value = "";
                $unit = "";
                $view_type = "";
                $rate = "";
                $color = "";
                $lockInfo = "";
                if ($i == 1) {
                    $id = $i;
                    $label = "Van DS";
                    $icon = "https://cdn-icons-png.flaticon.com/512/747/747376.png";
                    $current_value = $vanDsMtdCount ? $vanDsMtdCount : "0";
                    $target_value = 6;
                    $max_value = 26;
                    $unit = "days";
                    $view_type = 1;
                    $rate = "";
                    $color = "#19AF55";
                    $lockInfo = "";
                }
                if ($i == 2) {
                    $id = $i;
                    $label = "RMD+SCP DS";
                    $icon = "https://cdn-icons-png.flaticon.com/512/1055/1055687.png";
                    $current_value = $rmdScpMtdCount ? $rmdScpMtdCount : "0";
                    $target_value = 10;
                    $max_value = 26;
                    $unit = "days";
                    $view_type = 1;
                    $rate = "";
                    $color = "#0047ab";
                    $lockInfo = "";
                }
                if ($i == 3) {
                    $id = $i;
                    $label = "GT TL";
                    $icon = "https://cdn-icons-png.flaticon.com/512/3616/3616215.png";
                    $current_value = $gtTlCount ? $gtTlCount : "0";
                    $target_value = 2;
                    $max_value = 26;
                    $unit = "days";
                    $view_type = 1;
                    $rate = "";
                    $color = "#118ab2";
                    $lockInfo = "";
                }
                if ($i == 4) {
                    $id = $i;
                    $label = "AE";
                    $icon = "https://cdn-icons-png.flaticon.com/512/3616/3616215.png";
                    $current_value = $aeCount ? $aeCount : "0";
                    $target_value = 2;
                    $max_value = 26;
                    $unit = "days";
                    $view_type = 1;
                    $rate = "";
                    $color = "#19AF55";
                    $lockInfo = "";
                }

                $bannerItems[] = [
                    "id" => $id,
                    "label" => $label,
                    "icon" => $icon,
                    "current_value" => $current_value,
                    "target_value" => $target_value,
                    "max_value" => $max_value,
                    "unit" => $unit,
                    "view_type" => $view_type,
                    "rate" => $rate,
                    "color" => $color,
                    "lockInfo" => $lockInfo
                ];
            }

            $cardItems[] = [
                "id" => 1,
                "label" => "Slab: >=6 Hours",
                "icon" => "https://cdn-icons-png.flaticon.com/512/2331/2331941.png",
                "current_value" => $daysCount,
                "target_value" => 18,
                "max_value" => 18,
                "unit" => "days",
                "view_type" => 2,
                "rate" => "",
                "color" => "#118ab2",
                "lockInfo" => "Locked based on gate parameters 1"
            ];

            $metricGroups = array(
                array(
                    "groupTitle" => "Market with Infra",
                    "groupIcon" => "https://cdn-icons-png.flaticon.com/512/2913/2913133.png",
                    "groupColor" => "#19AF55",
                    "groupViewType" => "grid",
                    "groupStyle" => "banner",
                    "metrics" => $bannerItems,
                ),
                array(
                    "groupTitle" => "Market Working & CFT",
                    "groupIcon" => "https://cdn-icons-png.flaticon.com/512/3135/3135706.png",
                    "groupColor" => "#0047ab",
                    "groupViewType" => "list",
                    "groupStyle" => "card",
                    "metrics" => $cardItems,
                ),
            );

            $insentiveItems = array(
                array(
                    "id" => 1,
                    "label" => "On Accompanied RMD / SCP DS visit",
                    "icon" => "https://cdn-icons-png.flaticon.com/512/2331/2331941.png",
                    "current_value" => (int)$totalIncentive,
                    "target_value" => 1200,
                    "max_value" => 1200,
                    "unit" => "",
                    "view_type" => 3,
                    "rate" => "₹1200",
                    "color" => "#118ab2",
                    "isLocked" => $isLocked,
                    "lockInfo" => "Locked based on gate parameters 2"
                ),
                array(
                    "id" => 2,
                    "label" => "On Accompanied Van DS visit",
                    "icon" => "https://cdn-icons-png.flaticon.com/512/2331/2331941.png",
                    "current_value" => (int)$totalIncentive2,
                    "target_value" => 1200,
                    "max_value" => 1200,
                    "unit" => "",
                    "view_type" => 3,
                    "rate" => "₹1200",
                    "color" => "#19AF55",
                    "isLocked" => $isLocked,
                    "lockInfo" => "Locked based on gate parameters 3"
                ),
            );

            $insentiveItems2 = array(
                array(
                    "id" => 1,
                    "label" => "Sales > 4M /Day / UOB >=14",
                    "icon" => "https://cdn-icons-png.flaticon.com/512/2331/2331941.png",
                    "current_value" => (int)$incentiveCriteria1,
                    "target_value" => 1500,
                    "max_value" => 1500,
                    "unit" => "",
                    "view_type" => 3,
                    "rate" => "1500",
                    "color" => "#55298a",
                    "isLocked" => $isLocked,
                    "lockInfo" => "Locked based on gate parameters 4"
                ),
                array(
                    "id" => 2,
                    "label" => "Sales > 4M /Day / UOB >=18",
                    "icon" => "https://cdn-icons-png.flaticon.com/512/2331/2331941.png",
                    "current_value" => (int)$incentiveCriteria2,
                    "target_value" => 4000,
                    "max_value" => 4000,
                    "unit" => "",
                    "view_type" => 3,
                    "rate" => "4000",
                    "color" => "#d91136",
                    "isLocked" => $isLocked,
                    "lockInfo" => "Locked based on gate parameters 5"
                ),
            );

            $metricGroups2 = array(
                array(
                    "groupTitle" => "Sales Incentive",
                    "groupIcon" => "https://cdn-icons-png.flaticon.com/512/2913/2913133.png",
                    "groupColor" => "#19AF55",
                    "groupViewType" => "list",
                    "groupStyle" => "banner",
                    "metrics" => $insentiveItems,
                ),
                array(
                    "groupTitle" => "Basis Sales of All Infra Mapped to MDO",
                    "groupIcon" => "https://cdn-icons-png.flaticon.com/512/2913/2913133.png",
                    "groupColor" => "#f30909",
                    "groupViewType" => "list",
                    "groupStyle" => "banner",
                    "metrics" => $insentiveItems2,
                ),
            );

            $marketWithInfraItems = array(
                array(
                    "id" => "1",
                    "sectionTitle" => "Gate Parameters",
                    "sectionIcon" => "https://cdn-icons-png.flaticon.com/512/2913/2913133.png",
                    "color" => "#19AF55",
                    "metricGroups" => $metricGroups
                ),
                array(
                    "id" => "2",
                    "sectionTitle" => "Insentive Parameters",
                    "sectionIcon" => "https://cdn-icons-png.flaticon.com/512/3135/3135706.png",
                    "color" => "#0047ab",
                    "metricGroups" => $metricGroups2
                ),
            );

            $mdoLeaderboardData[] = array(
                "month" => date('M', strtotime($month)),
                "earned_payout" => $totalEarned,
                "max_payout" => 6400,
                "marketWithInfraItems" => $marketWithInfraItems,
            );
        }

        $arrOtherSummary = array(
            "leaderBoardTitle" => "MDO D.S.P.M",
            "monthWiseMdoLeaderboardData" => $mdoLeaderboardData
        );

        $response = $this->response->sendResponse(array("message" => "", "response" => $arrOtherSummary ? $arrOtherSummary : array()), 1);
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getAlert()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            // if ($this->validateData($dbName)) {
            $this->getMdoDSPM();
            // } else {
            //     // JSON ID is missing
            //     $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
            //     $this->logOutput($response, $this->sExtraLogData);
            // }
        }
    }
}

$notification = new getMdoDSPM($dbConn, $tableUtil, $commonFunctions);
$notification->getAlert();
$dbConn->Close();
