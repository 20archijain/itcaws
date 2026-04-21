<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// phpcs:ignore
class LeaderboardData
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
    }


    // CIRCLE
    final public function getCircle($branch = "branch_id")
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if ($branch) {
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            if (in_array('all', $branch)) {
                $branchCond = ""; // No condition for 'all'
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $branchCond = " AND branch_id IN ($branch)";
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $branch),
                "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // Section
    final public function getSection($circle = "circle")
    {
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $circleCond = "";
        $branchCond = "";
        if ($circle || $branch) {
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }
            $branchIds = getRowsColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", " dstatus = '0' AND s_id = '99' $branchCond $circleCond ");
            $where = "";
            if ($branchIds) {
                $matchAll = checkIfAllSelected($branchIds);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchIds)) {
                        $branchIds = implode(",", $branchIds);
                        $where = "$branchIds";
                    } else {
                        $where = "$branchIds";
                    }
                }
            }
            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $where),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99' $branchCond  $circleCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // WD Code
    final public function getWDCode($section = "section")
    {
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        $where = "";
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
        }
        if ($section || $branch || $section) {
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $where),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond $where"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond $where"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // Team Type
    final public function getTeamType()
    {
        $wdCode = $this->_data['wdCode'];
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $wdCodeCond = "";
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        if ($wdCode || $branch || $circle || $section) {
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND wd_code IN ($wdCode)";
                }
            }
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }
            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // Team List
    final public function getTeamList()
    {
        $dsType = $this->_data['dsType'];
        $wdCode = $this->_data['wdCode'];
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $wdCodeCond = "";
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        $dsTypeCond = "";
        if ($dsType || $wdCode || $branch || $circle || $section) {
            if ($dsType) {
                if (!is_array($dsType)) {
                    $dsType = array($dsType);
                }
                if (in_array('all', $dsType)) {
                    $dsTypeCond = ""; // No condition for 'all'
                } else {
                    $dsType = "'" . implode("','", $dsType) . "'";
                    $dsTypeCond = " AND is_type IN ($dsType)";
                }
            }
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND wd_code IN ($wdCode)";
                }
            }
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL  AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond $dsTypeCond"),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // GET  DATA
    final public function getData()
    {
        $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $user_id = $this->_iUserId;
        $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        if ($groupId == 1 || $groupId == 2) {
            $branchFilter = true;
        } else {
            $branchFilter = false;
        }
        $where = "";
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
        }
        $arrResult = array(
            "branchFilter" => $branchFilter,
            // Don't use dstatus = 0
            "branchList" => getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch"),
            "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id ='99'"),
            "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id ='99' "),
            "wdCodeList" => getOptions($this->_dbConn, "tblproject_team", "wd_code", "wd_code", " s_id = '99' $where"),
            "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id ='99'"),
            "dsType" => getTeamType($this->_dbConn),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // DOWNLOAD  CONDITION
    private function dwnCondition($capDate = "c.capture_date")
    {
        // filter query
        $dwnCond = getFilterResult(
            $this->_data,
            array(
                "dateFrom" => array($capDate, 4, "dateTo", true),
                "branch" => array("c.branch_id", 0, true, true),
                "circle" => array("a.circle", 0, true, true),
                "section" => array("a.section", 0, true, true),
                "dsName" => array("c.team_id", 0, true, true),
                "wdCode" => array("a.wd_code", 0, true, true),
                "dsType" => array("a.is_type", 1),
            ),
            $this->_dbConn
        );
        // print_r($dwnCond);
        return $dwnCond;
    }

    final public function downloadDSDetails()
    {
        $dwnCond = $this->dwnCondition();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $leaderboardTable = $this->_tables["LEADERBOARD_TABLE"];

        // order by condition
        $sOrderCond = getOrderByCond("c.capture_date");

        // filter by search query
        $where = "";

        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        // user has some specific permission
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        // $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD");
        $sQuery = "SELECT  c.team_id,c.capture_date, c.qualifiedDays, c.ttldays, c.para1_score, c.ttloutlets, c.uob, c.para2_score, c.fb1uob, c.para3_score, c.fb2uob, c.para4_score, c.total_score, a.is_type, a.team_name, a.circle, a.section, a.wd_code, b.district, b.branch_name,b.main_branch" .
            " FROM $projectTeamTable AS a, $branchTable AS b,$leaderboardTable as c  WHERE a.dstatus = 0  AND a.branch_id = b.branch_id AND a.team_id = c.team_id AND c.dstatus = 0 $where $dwnCond $sOrderCond";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $arrData = array(
                array(
                    "Date",
                    "District",
                    "Branch",
                    "Circle",
                    "Section",
                    "WD Code",
                    "DS ID",
                    "DS Name",
                    "QA Days",
                    "Total Days",
                    "QA Score",
                    "Total Outlets",
                    "UOB Billed Outlets",
                    "Overall UOB",
                    "FB1 Billed",
                    "FB1 UOB",
                    "FB2 Billed",
                    "FB2 UOB",
                    "Final Leaderboard Score",
                ),
            );
            while ($row = $this->_dbConn->GetData($sAction)) {
                $teamId = $row['team_id'];
                $captureDate = $row['capture_date'];
                $qualifiedDays = $row['qualifiedDays'];
                $ttldays = $row['ttldays'];
                $para1_score = isset($row["para1_score"]) ? round($row["para1_score"], 2) : 0;
                $ttloutlets = $row['ttloutlets'];
                $uob = $row['uob'];
                $para2_score = isset($row["para2_score"]) ? round($row["para2_score"], 2) : 0;
                $fb1uob = $row['fb1uob'];
                $para3_score = isset($row["para3_score"]) ? round($row["para3_score"], 2) : 0;
                $fb2uob = $row['fb2uob'];
                $para4_score = isset($row["para4_score"]) ? round($row["para4_score"], 2) : 0;
                $total_score = isset($row["total_score"]) ? round($row["total_score"], 2) : 0;
                $team_name = $row['team_name'];
                $circle = $row['circle'];
                $section = $row['section'];
                $wd_code = $row['wd_code'];
                $district = $row['district'];
                $branch_name = $row['branch_name'];
                $main_branch = $row['main_branch'];

                $arrData[] = array(
                    $captureDate,
                    $district,
                    $branch_name,
                    $circle,
                    $section,
                    $wd_code,
                    $teamId,
                    $team_name,
                    $qualifiedDays,
                    $ttldays,
                    $para1_score,
                    $ttloutlets,
                    $uob,
                    $para2_score,
                    $fb1uob,
                    $para3_score,
                    $fb2uob,
                    $para4_score,
                    $total_score,
                );
            }
        }

        if (!empty($arrData)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($arrData);

            // Auto-size columns
            foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $headerRow = '1';
            $styleHeader = $sheet->getStyle('A' . $headerRow . ':' . $sheet->getHighestDataColumn() . $headerRow);
            $styleHeader->getFont()->setBold(true);
            $styleHeader->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

            $allStyle = [
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ),
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())->applyFromArray($allStyle);

            // Save the spreadsheet
            $fileName = "LEADERBOARD_REPORT_" . date('Y-m-d_H-i-s') . ".xlsx";
            $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
            $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
            $fileDetails = array(
                "filePath" => $downloadFileLocation,
                "fileName" => $fileName,
            );
            $writer = new Xlsx($spreadsheet);
            try {
                $writer->save($filename);
                $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
            } catch (PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
                echo "Error saving spreadsheet: " . $e->getMessage();
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }
}
