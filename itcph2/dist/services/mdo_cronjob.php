<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class AddMdoParameter
{
    private $_dbConn = null;
    private $_tables = [];
    private $_commonSettings = [];
    private $_jsonWiseAndbranchWiseProductsColumns = [];
    private $_jsonWiseAndbranchWiseStockpickupProductsColumns = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
    }

    final public function addMdoDSPM()
    {
        $teamIds = getRowsColumns(
            $this->_dbConn,
            "tblproject_team",
            "team_id, team_name, is_type",
            "is_type IN(7,10) AND dstatus=0",
            [],
            false,
            2
        );

        if (empty($teamIds)) {
            return;
        }

        foreach ($teamIds as $team) {
            $teamId = $team['team_id'];
            $isType = $team['is_type'] ?? '';
            if (!$teamId) {
                continue;
            }
            $wdId = getRowColumn(
                $this->_dbConn,
                "tblmdo_wd_mapping",
                "wd_id",
                "mdo_id = $teamId"
            );
            $mdoDetails = $wdId ? getRowColumns(
                $this->_dbConn,
                "tblmapping_wd",
                "circle, section,
                wd_code, wd_market, wd_pop_group, district",
                "dstatus = 0 AND rec_id = $wdId",
                [],
                2
            ) : "";
            $teamNameBranch = getRowColumns(
                $this->_dbConn,
                "tblproject_team",
                "team_name, branch_id",
                "dstatus = 0 AND team_id = $teamId"
            );
            $branchDetails = getRowColumns(
                $this->_dbConn,
                "tblbranch",
                "branch_name, main_branch",
                "dstatus = 0 AND branch_id = $teamNameBranch[1]",
                [],
                2
            );
            $teamName   = $teamNameBranch['0'] ?? '';
            // $isType     = $teamDetails['1'] ?? '';
            $circle     = $mdoDetails[0] ?? '';
            $section    = $mdoDetails[1] ?? '';
            $wdCode     = $mdoDetails[2] ?? '';
            $wdMarket   = $mdoDetails[3] ?? '';
            $wdPop      = $mdoDetails[4] ?? '';
            $district   = $mdoDetails[5] ?? '';
            $mainBranch = $branchDetails[1] ?? '';
            $branchName = $branchDetails[0] ?? '';
            $months = [
                date('Y-m', strtotime('-2 month')),
                date('Y-m', strtotime('-1 month')),
                date('Y-m'),
            ];

            foreach ($months as $month) {
                list($year, $monthNum) = explode('-', $month);

                // ---------------- DS COUNT ----------------
                $Query = "SELECT type, COUNT(DISTINCT CONCAT(ds_name, '_', DATE(capture_date))) AS cnt
                      FROM tblattendance
                      WHERE dstatus = 0
                      AND team_id = $teamId
                      AND DATE_FORMAT(capture_date, '%Y-%m') = '$month'
                      AND type IN (0,6,8,9)
                      GROUP BY type";

                $sAction = null;
                $sRows   = 0;
                $this->_dbConn->ExecuteSelectQuery($Query, $sAction, $sRows);

                $vanDsMtdCount    = 0;
                $rmdDsMtdCount    = 0;
                $stokiestMtdCount = 0;
                $fmcgMtdCount     = 0;

                if ($sRows > 0) {
                    while ($row = $this->_dbConn->GetData($sAction)) {
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
                    }
                }

                $rmdScpMtdCount = $rmdDsMtdCount + $stokiestMtdCount + $fmcgMtdCount;

                // ---------------- GT TL & AE ----------------
                $gtTlCount = getRowColumn(
                    $this->_dbConn,
                    "tblattendance",
                    "COUNT(DISTINCT capture_date)",
                    "dstatus=0 AND team_id=$teamId AND DATE_FORMAT(capture_date,'%Y-%m')='$month' AND work_with=2 AND call_type='0'"
                );

                $aeCount = getRowColumn(
                    $this->_dbConn,
                    "tblattendance",
                    "COUNT(DISTINCT capture_date)",
                    "dstatus=0 AND team_id=$teamId AND DATE_FORMAT(capture_date,'%Y-%m')='$month' AND work_with=1 AND call_type='0'"
                );

                // ---------------- WORKING DAYS (>=6 hours) ----------------
                $Query1 = "SELECT capture_date, MIN(capture_datetime) AS startTime
                       FROM tblattendance
                       WHERE dstatus=0 AND team_id=$teamId
                       AND DATE_FORMAT(capture_date,'%Y-%m')='$month'
                       AND call_type='0'
                       GROUP BY capture_date";

                $this->_dbConn->ExecuteSelectQuery($Query1, $sAction, $sRows);

                $daysCount = 0;

                if ($sRows > 0) {
                    while ($row = $this->_dbConn->GetData($sAction)) {
                        $startTime = $row['startTime'];
                        $date      = $row['capture_date'];

                        $dayEnd = getRowColumn(
                            $this->_dbConn,
                            "tblattendance",
                            "MIN(capture_datetime)",
                            "dstatus=0 AND team_id=$teamId AND capture_date='$date' AND call_type='1'"
                        );

                        $marketEnd = getRowColumn(
                            $this->_dbConn,
                            "tblsurvey_response_details_mdo",
                            "MAX(capture_datetime)",
                            "dstatus=0 AND team_id=$teamId AND capture_date='$date'"
                        );

                        $endTime = $dayEnd ?: $marketEnd;

                        if ($startTime && $endTime) {
                            $minutes = abs(strtotime($endTime) - strtotime($startTime)) / 60;
                            if ($minutes >= 360) {
                                $daysCount++;
                            }
                        }
                    }
                }

                // ---------------- LOCK CHECK ----------------
                $isLocked = 1;
                if ($vanDsMtdCount  >= 6  && $rmdScpMtdCount >= 10 && $gtTlCount >= 2  && $aeCount >= 2  && $daysCount >= 18) {
                    $isLocked = 0;
                }

                // ---------------- INCENTIVE (per-day loop, matching reference code) ----------------
                $Query2 = "SELECT capture_date, SUM(ques_5) AS sale, AVG(ques_7) AS alc FROM tblsurvey_response_details_mdo WHERE dstatus=0 AND team_id=$teamId
                       AND DATE_FORMAT(capture_date,'%Y-%m')='$month'
                       AND type IN (6,8)
                       GROUP BY capture_date";

                $this->_dbConn->ExecuteSelectQuery($Query2, $sAction, $sRows);

                $totalIncentive = 0;

                if ($sRows > 0) {
                    while ($row = $this->_dbConn->GetData($sAction)) {
                        $sale = $row['sale'];
                        $alc  = round($row['alc']);

                        $dayIncentive = 0;
                        if ($sale >= 4 && $alc >= 6) {
                            $dayIncentive = 120;
                        } elseif ($sale >= 3 && $alc >= 5) {
                            $dayIncentive = 100;
                        } elseif ($sale >= 2 && $alc >= 4) {
                            $dayIncentive =  75;
                        }

                        $totalIncentive += $dayIncentive;
                        $totalIncentive  = min($totalIncentive, 1200);
                    }
                }
                // ---------------- INCENTIVE 2 - Van DS (Query3) ----------------
                $Query3 = "SELECT capture_date, COUNT(DISTINCT ques_4) AS uob, AVG(ques_7) AS alc
                    FROM tblsurvey_response_details_mdo
                    WHERE dstatus=0 AND team_id=$teamId
                    AND DATE_FORMAT(capture_date,'%Y-%m')='$month'
                    AND type = 0
                    GROUP BY capture_date";

                $this->_dbConn->ExecuteSelectQuery($Query3, $sAction, $sRows);

                $totalIncentive2 = 0;

                if ($sRows > 0) {
                    while ($row = $this->_dbConn->GetData($sAction)) {
                        $uob = $row['uob'];
                        $alc = round((float)$row['alc']);

                        $dayIncentive2 = 0;
                        if ($uob >= 18 && $alc >= 6) {
                            $dayIncentive2 = 120;
                        } elseif ($uob >= 16 && $alc >= 5) {
                            $dayIncentive2 = 100;
                        } elseif ($uob >= 14 && $alc >= 4) {
                            $dayIncentive2 =  75;
                        }

                        $totalIncentive2 += $dayIncentive2;
                        $totalIncentive2  = min($totalIncentive2, 1200);
                    }
                }

                // ---------------- INCENTIVE 3 & 4 - Criteria (Query4) ----------------
                $acessTeams = getRowsColumn(
                    $this->_dbConn,
                    "tblmdo_access",
                    "teams",
                    "dstatus=0 AND mdo_id=$teamId AND is_type IN (6,8)"
                );

                $incentiveCriteria1 = 0;
                $incentiveCriteria2 = 0;

                if (!empty($acessTeams)) {
                    $teams = "'" . implode("','", $acessTeams) . "'";
                    if (!empty($teams)) {
                        $Query4 = "SELECT capture_date, value_m, outlet_re_visit, total_sale
                        FROM tblbreeze_response_data
                        WHERE dstatus=0 AND ds_id IN ($teams)
                        AND DATE_FORMAT(capture_date,'%Y-%m')='$month'";

                        $this->_dbConn->ExecuteSelectQuery($Query4, $sAction, $sRows);

                        $daysCriteria1 = 0;
                        $daysCriteria2 = 0;

                        if ($sRows > 0) {
                            while ($row = $this->_dbConn->GetData($sAction)) {
                                $valueM        = $row['value_m'];
                                $outletReVisit = $row['outlet_re_visit'];
                                $totalSale     = $row['total_sale'];

                                $sale = $valueM > 0 ? ($totalSale / $valueM) : 0;

                                if ($outletReVisit >= 14 && $sale >= 2) {
                                    $daysCriteria1++;
                                }
                                if ($outletReVisit >= 18 && $sale >= 4) {
                                    $daysCriteria2++;
                                }
                            }

                            $targetDays         = 18;
                            $incentiveCriteria1 = min(($daysCriteria1 / $targetDays) * 1500, 1500);
                            $incentiveCriteria2 = min(($daysCriteria2 / $targetDays) * 4000, 4000);
                        }
                    }
                }

                // ---------------- TOTALS ----------------
                $maxPayout   = 6400;
                $totalEarned = $totalIncentive + $totalIncentive2 + $incentiveCriteria1 + $incentiveCriteria2;  // all 4 summed

                // ---------------- INSERT / UPDATE ----------------
                $recId = getRowColumn(
                    $this->_dbConn,
                    "mdo_dspm_summary",
                    "summary_id",
                    "dstatus=0 AND team_id=$teamId AND month='$month' AND year='$year'"
                );

                if (!$recId) {
                    // addRecord(
                    //     $this->_dbConn,
                    //     "mdo_dspm_summary",
                    //     "team_id, year, month, earned_payout, max_payout,
                    //     van_ds_days, rmd_scp_days, gt_tl_days, ae_days, working_days,
                    //     incentive_rmd_scp, incentive_van_ds, incentive_criteria_1, incentive_criteria_2,
                    //     is_locked, dstatus",
                    //     "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?",
                    //     [
                    //         $teamId,
                    //         (int)$year,
                    //         $month,
                    //         $totalEarned,
                    //         $maxPayout,
                    //         $vanDsMtdCount,
                    //         $rmdScpMtdCount,
                    //         $gtTlCount,
                    //         $aeCount,
                    //         $daysCount,
                    //         $totalIncentive,        // incentive_rmd_scp
                    //         $totalIncentive2,       // incentive_van_ds
                    //         $incentiveCriteria1,    // incentive_criteria_1
                    //         $incentiveCriteria2,    // incentive_criteria_2
                    //         $isLocked,
                    //         0
                    //     ]
                    // );
                    addRecord(
                        $this->_dbConn,
                        "mdo_dspm_summary",
                        "team_id, team_name, is_type, circle, section,
                        wd_code, wd_market, wd_pop_group, district,
                        main_branch, branch_name,
                        year, month, earned_payout, max_payout,
                        van_ds_days, rmd_scp_days, gt_tl_days, ae_days, working_days,
                        incentive_rmd_scp, incentive_van_ds, incentive_criteria_1, incentive_criteria_2,
                        is_locked, dstatus",
                        "?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?",
                        [
                            $teamId,
                            $teamName,
                            $isType,
                            $circle,
                            $section,
                            $wdCode,
                            $wdMarket,
                            $wdPop,
                            $district,
                            $mainBranch,
                            $branchName,
                            (int)$year,
                            $month,
                            $totalEarned,
                            $maxPayout,
                            $vanDsMtdCount,
                            $rmdScpMtdCount,
                            $gtTlCount,
                            $aeCount,
                            $daysCount,
                            $totalIncentive,
                            $totalIncentive2,
                            $incentiveCriteria1,
                            $incentiveCriteria2,
                            $isLocked,
                            0
                        ]
                    );
                } else {
                    // updateRecord(
                    //     $this->_dbConn,
                    //     "mdo_dspm_summary",
                    //     "year=?, month=?, earned_payout=?, max_payout=?,
                    //     van_ds_days=?, rmd_scp_days=?, gt_tl_days=?, ae_days=?, working_days=?,
                    //     incentive_rmd_scp=?, incentive_van_ds=?, incentive_criteria_1=?, incentive_criteria_2=?,
                    //     is_locked=?",
                    //     "summary_id=$recId",
                    //     [
                    //         (int)$year,
                    //         $month,
                    //         $totalEarned,
                    //         $maxPayout,
                    //         $vanDsMtdCount,
                    //         $rmdScpMtdCount,
                    //         $gtTlCount,
                    //         $aeCount,
                    //         $daysCount,
                    //         $totalIncentive,        // incentive_rmd_scp
                    //         $totalIncentive2,       // incentive_van_ds
                    //         $incentiveCriteria1,    // incentive_criteria_1
                    //         $incentiveCriteria2,    // incentive_criteria_2
                    //         $isLocked
                    //     ]
                    // );
                    updateRecord(
                        $this->_dbConn,
                        "mdo_dspm_summary",
                        "team_name=?, is_type=?, circle=?, section=?,
                        wd_code=?, wd_market=?, wd_pop_group=?, district=?,
                        main_branch=?, branch_name=?,
                        year=?, month=?, earned_payout=?, max_payout=?,
                        van_ds_days=?, rmd_scp_days=?, gt_tl_days=?, ae_days=?, working_days=?,
                        incentive_rmd_scp=?, incentive_van_ds=?, incentive_criteria_1=?, incentive_criteria_2=?,
                        is_locked=?",
                        "team_id=$teamId AND month='$month' AND year='$year'",
                        [
                            $teamName,
                            $isType,
                            $circle,
                            $section,
                            $wdCode,
                            $wdMarket,
                            $wdPop,
                            $district,
                            $mainBranch,
                            $branchName,
                            (int)$year,
                            $month,
                            $totalEarned,
                            $maxPayout,
                            $vanDsMtdCount,
                            $rmdScpMtdCount,
                            $gtTlCount,
                            $aeCount,
                            $daysCount,
                            $totalIncentive,
                            $totalIncentive2,
                            $incentiveCriteria1,
                            $incentiveCriteria2,
                            $isLocked
                        ]
                    );
                }
            }
        }
    }
}

$updateDataCronjob = new AddMdoParameter($dbConn);
$updateDataCronjob->addMdoDSPM();
