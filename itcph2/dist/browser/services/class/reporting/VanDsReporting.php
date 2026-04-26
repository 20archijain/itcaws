<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// phpcs:ignore
class VanDsReporting
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];
    private $_projectId = 1;
    private $arrBranchwiseProducts = [];
    private $arrBranchwiseCompetition = [];
    private $arrBranchWiseStockProducts = [];
    private $session;
    private $_iUserId = null;

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
    }
    // Filter Condition
    final public function getCondition($summary = false, $andCondition = true)
    {
        $teamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $condition = "";
        $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
        if ($district) {
            $matchAll = checkIfAllSelected($district);
            if (!$matchAll) {
                if (isNonEmptyArray($district)) {
                    $districts = "'" . implode("','", $district) . "'";
                    $condition .= " AND d.district IN ($districts)";
                } else {
                    $condition .= " AND d.district = $district";
                }
            }
        }
        $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = "'" . implode("','", $branch) . "'";
                    $condition .= " AND d.branch_id IN ($branchIds)";
                } else {
                    $condition .= " AND d.branch_id = $branch";
                }
            }
        }
        $circle = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "circle");
        if ($circle) {
            $matchAll = checkIfAllSelected($circle);
            if (!$matchAll) {
                if (isNonEmptyArray($circle)) {
                    $circleIds = "'" . implode("','", $circle) . "'";
                    $condition .= " AND b.circle IN ($circleIds)";
                } else {
                    $condition .= " AND b.circle = '$circle'";
                }
            }
        }
        $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $sectionIds = "'" . implode("','", $section) . "'";
                    $condition .= " AND b.section IN ($sectionIds)";
                } else {
                    $condition .= " AND b.section = '$section'";
                }
            }
        }
        $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCodeIds = "'" . implode("','", $wdCode) . "'";
                    $condition .= " AND b.wd_code IN ($wdCodeIds)";
                } else {
                    $condition .= " AND b.wd_code = '$wdCode'";
                }
            }
        }
        $wdMarket = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdMarket");
        if ($wdMarket) {
            if (!is_array($wdMarket)) {
                $wdMarket = array($wdMarket);
            }
            if (in_array('all', $wdMarket)) {
                $condition .= " ";
            } else {
                $wdMarket = "'" . implode("','", $wdMarket) . "'";
                $condition .= " AND e.wd_market IN ($wdMarket)";
            }
        }
        $wdPopGroup = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdPopGroup");
        if ($wdPopGroup) {
            if (!is_array($wdPopGroup)) {
                $wdPopGroup = array($wdPopGroup);
            }
            if (in_array('all', $wdPopGroup)) {
                $condition .= " ";
            } else {
                $wdPopGroup = "'" . implode("','", $wdPopGroup) . "'";
                $condition .= " AND e.wd_pop_group IN ($wdPopGroup)";
            }
        }
        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if (isset($teamType) && $teamType != "" && isNonEmptyArray($teamType) && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $condition .= " AND b.is_type IN ($teamTypes)";
                } else {
                    $condition .= " AND b.is_type = $teamType";
                }
            }
        }

        $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $condition .= " AND b.team_id IN ($dsNames)";
                } else {
                    $condition .= " AND b.team_id = $dsName";
                }
            }
        }

        $where = "";
        if ($condition && $andCondition) {
            $where .= " AND a.team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND e.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
        } elseif ($condition) {
            $where .= " AND a.team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND e.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        // print_r($where);die;
        return $where;
    }

    final public function getDistrictList()
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.district";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['district'],
                    "value" => $row['district']
                );
            }
        }

        return $arrData;
    }

    final public function getBranchList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all",
        );

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.branch_name";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['branch_name'],
                    "value" => $row['branch_id'],
                    "mainBranch" => $row['main_branch']
                );
            }
        }

        return $arrData;
    }

    final public function getCircleList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.circle, c.circle_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.circle";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['circle'] . " - " . $row['circle_name'],
                    "value" => $row['circle']
                );
            }
        }

        return $arrData;
    }

    final public function getSectionList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.section, c.section_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.section";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['section'] . " - " . $row['section_name'],
                    "value" => $row['section']
                );
            }
        }

        return $arrData;
    }


    final public function getWdCodeList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.wd_code";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_code'] . ' - ' . $row['wd_market'] . ' - ' . $row['wd_firm_name'],
                    "value" => $row['wd_code']
                );
            }
        }

        return $arrData;
    }


    final public function getWdMarketList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_market";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_market'],
                    "value" => $row['wd_market']
                );
            }
        }

        return $arrData;
    }

    final public function getWdPopGroupList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_pop_group";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_pop_group'],
                    "value" => $row['wd_pop_group']
                );
            }
        }

        return $arrData;
    }


    final public function getDsTypeList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND b.is_type != 4 AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = "";
                if ($row['is_type'] == 0) {
                    $teamType = "Van DS";
                } elseif ($row['is_type'] == 1) {
                    $teamType = "Niche";
                } elseif ($row['is_type'] == 2) {
                    $teamType = "Town SWD";
                } elseif ($row['is_type'] == 3) {
                    $teamType = "Hybrid";
                } elseif ($row['is_type'] == 5) {
                    $teamType = "NPSR";
                }
                $arrData[] = array(
                    "label" => $teamType,
                    "value" => $row['is_type']
                );
            }
        }

        return $arrData;
    }


    final public function getTeamsList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.team_name IS NOT NULL AND b.team_name != '' AND b.s_id = '99' $where order by b.team_name";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['team_name'],
                    "value" => $row['team_id']
                );
            }
        }

        return $arrData;
    }

    final public function getData()
    {
        // $where = "";
        // $where2 = "";
        $userBranch = "";
        // $teamList = $this->_arrAccessInfo["user_teams"];
        // if ($teamList) {
        //     $where .= " AND team_id IN $teamList";
        //     $where2 .= "team_id IN $teamList";
        //     $branchId = getRowColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", "$where2");
        // }
        // $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        // $user_id = $this->_iUserId;
        // $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        // if ($groupId == 1 || $groupId == 2) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // }
        $arrResult = array(
            // Don't use dstatus = 0
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "teamType" => $this->getDsTypeList(),
            "teamList" => $this->getTeamsList(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
            "showTransactionDownloadBtn" => true,
            "showSummaryDownloadBtn" => true,
            "branchFilter" => true,
            "userBranch" => $userBranch,
            "binderReportDownloadDays" => 5,
            "transactionReportDownloadDays" => 31,
            "summaryReportDownloadDays" => 31,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getBranch($district = "district")
    {
        $district = $this->_data['district'];
        $districtCond = "";
        if (!empty($district)) {
            if (!is_array($district)) {
                $district = array($district);
            }
            if (in_array('all', $district)) {
                $districtCond = ""; // No condition for 'all'
            } else {
                $district = "'" . implode("','", $district) . "'";
                $districtCond = " AND a.district IN ($district)";
            }

            $arrResult = array(
                "branchList" => $this->getBranchList($districtCond),
                "circleList" => $this->getCircleList($districtCond),
                "sectionList" => $this->getSectionList($districtCond),
                "wdCodeList" => $this->getWdCodeList($districtCond),
                "teamType" => $this->getDsTypeList($districtCond),
                "teamList" => $this->getTeamsList($districtCond),
                "wdMarketList" => $this->getWdMarketList($districtCond),
                "wdPopGroupList" => $this->getWdPopGroupList($districtCond),
            );
        } else {
            $arrResult = array(
                "branchList" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


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
                $branchCond = " AND a.branch_id IN ($branch)";
            }

            $arrResult = array(
                "circleList" => $this->getCircleList($branchCond),
                "sectionList" => $this->getSectionList($branchCond),
                "wdCodeList" => $this->getWdCodeList($branchCond),
                "teamType" => $this->getDsTypeList($branchCond),
                "teamList" => $this->getTeamsList($branchCond),
                "wdMarketList" => $this->getWdMarketList($branchCond),
                "wdPopGroupList" => $this->getWdPopGroupList($branchCond),
            );
        } else {
            $arrResult = array(
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection($circle = "circle")
    {
        $circle = $this->_data['circle'];
        $circleCond = "";
        if ($circle) {
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND b.circle IN ($circle)";
                }
            }
            $arrResult = array(
                "sectionList" => $this->getSectionList($circleCond),
                "wdCodeList" => $this->getWdCodeList($circleCond),
                "teamType" => $this->getDsTypeList($circleCond),
                "teamList" => $this->getTeamsList($circleCond),
                "wdMarketList" => $this->getWdMarketList($circleCond),
                "wdPopGroupList" => $this->getWdPopGroupList($circleCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getWDCode($section = "section")
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if ($section) {
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND b.section IN ($section)";
                }
            }

            $arrResult = array(
                "wdCodeList" => $this->getWdCodeList($sectionCond),
                "teamType" => $this->getDsTypeList($sectionCond),
                "teamList" => $this->getTeamsList($sectionCond),
                "wdMarketList" => $this->getWdMarketList($sectionCond),
                "wdPopGroupList" => $this->getWdPopGroupList($sectionCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $wdCode = $this->_data['wdCode'];
        $wdCodeCond = "";
        if ($wdCode) {
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND b.wd_code IN ($wdCode)";
                }
            }
            $arrResult = array(
                "teamType" => $this->getDsTypeList($wdCodeCond),
                "teamList" => $this->getTeamsList($wdCodeCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamList()
    {
        $dsType = $this->_data['dsType'];
        $dsTypeCond = "";
        if (isset($dsType) && $dsType != "" && $dsType >= 0) {
            if (!is_array($dsType)) {
                $dsType = array($dsType);
            }
            if (in_array('all', $dsType)) {
                $dsTypeCond = ""; // No condition for 'all'
            } else {
                $dsType = "'" . implode("','", $dsType) . "'";
                $dsTypeCond = " AND b.is_type IN ($dsType)";
            }
            $arrResult = array(
                "teamList" => $this->getTeamsList($dsTypeCond),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function getBranchWiseProducts($branchId = null, $teamType = null)
    {
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

        if ($branchId) {
            if (is_array($teamType) && !empty($teamType)) {
                $result = [];

                foreach ($teamType as $type) {
                    $result = array_merge($result, $this->arrBranchwiseProducts[$branchId][$type] ?? []);
                }

                return $result;
            } elseif ($teamType !== null && $teamType !== "") {
                return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
            } else {
                return $this->arrBranchwiseProducts[$branchId] ?? [];
            }
        } else {
            if (is_array($teamType) && !empty($teamType)) {
                $teamTypeIn = implode(",", array_map('intval', $teamType));

                $arrProductSummaryColumns = getRowsColumns(
                    $this->_dbConn,
                    $branchProductsTable,
                    "branch_id, team_type, product_name, summary_column_name, pkt_size",
                    "dstatus = 0 AND team_type IN ($teamTypeIn) ORDER BY product_name",
                    [],
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $type     = $arrBranchColumns[1];
                    $productName = $arrBranchColumns[2];
                    $summaryColumnName = $arrBranchColumns[3];
                    $pktSize = $arrBranchColumns[4];

                    if (!isset($this->arrBranchwiseProducts[$branchId][$type])) {
                        $this->arrBranchwiseProducts[$branchId][$type] = [];
                    }
                    $this->arrBranchwiseProducts[$branchId][$type][] = [$productName, $summaryColumnName, $pktSize];
                }
            } elseif ($teamType !== null && $teamType !== "") {
                // handle string teamType (single value)
                $arrProductSummaryColumns = getRowsColumns(
                    $this->_dbConn,
                    $branchProductsTable,
                    "branch_id, product_name, summary_column_name, pkt_size",
                    "dstatus = 0 AND team_type = '" . intval($teamType) . "' ORDER BY product_name",
                    [],
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $productName = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];
                    $pktSize = $arrBranchColumns[3];

                    if (!isset($this->arrBranchwiseProducts[$branchId][$teamType])) {
                        $this->arrBranchwiseProducts[$branchId][$teamType] = [];
                    }
                    $this->arrBranchwiseProducts[$branchId][$teamType][] = [$productName, $summaryColumnName, $pktSize];
                }
            } else {
                // No teamType provided
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name, pkt_size FROM $branchProductsTable WHERE dstatus = 0 ORDER BY product_name";
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];
                        if (!isset($this->arrBranchwiseProducts[$branchId])) {
                            $this->arrBranchwiseProducts[$branchId] = [];
                        }
                        $this->arrBranchwiseProducts[$branchId][] = [$rowProduct["product_name"], $rowProduct["summary_column_name"], $rowProduct["pkt_size"]];
                    }
                }
            }
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

    final public function getReportingData()
    {
        // filter query
        $where = $this->getCondition();

        // print_r($where);die;
        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $partialQuery = "FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = '99' $where";

        // Don't use b.dstatus = 0 AND c.dstatus = 0
        $arrData = array();
        $rsAction = null;
        $iRows = 0;
        // use a.pro_id > 0 to include primary column as index while calculating no of rows
        $sQuery = "SELECT a.pro_id $partialQuery AND a.pro_id > 0";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);

        $sQuery = "SELECT a.uni_id, a.capture_datetime, a.lt, a.lg, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, b.team_id, b.team_name,b.circle,b.section,b.branch_id, b.wd_code, c.branch_name $partialQuery";
        $sQuery .= " " . $limit["limit"];
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $i = 0;
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $shopId = $row["ques_3"];
                $branchId = $row["branch_id"];
                if ($branchId == 40) {
                    $shopDetails = is_numeric($shopId) ? getRowColumns($this->_dbConn, "tblroute_details_delhi", "shop_type, outlet_name, outlet_mobile", "rec_id = $shopId") : array("", "");
                } else {
                    // Don't use dstatus = 0
                    $shopDetails = is_numeric($shopId) ? getRowColumns($this->_dbConn, $routeDetailsTable, "shop_type, outlet_name, outlet_mobile", "rec_id = $shopId") : array("", "");
                }
                $shopType = $shopDetails[0] ?? "";
                $shopName = isset($shopDetails[1]) ? removeSpecialCharFromString($shopDetails[1]) : "";
                $mobileNumber = $shopDetails[2] ?? "";
                $sellIinOrder = $row["ques_4"];
                $shopFrontPicture = $row["ques_6"];
                $reasonForNoSale = $sellIinOrder == "Yes" ? $row["ques_5"] : "";

                $arrImages = getListingImages(
                    $this->_dbConn,
                    $row["uni_id"],
                    "",
                    array(
                        $shopFrontPicture => "Outlet front Picture Lt: {$row["lt"]} Lg: {$row["lg"]}",
                    )
                );

                // PRE-COMPUTE TIMESTAMP FOR SORTING (convert once, sort faster)
                $row['_timestamp'] = strtotime($row["capture_datetime"]);

                $arrData[$i] = array(
                    "reportingType" => $row["ques_0"],
                    "wdCode" => $row["wd_code"],
                    "route" => json_decode($row["ques_1"], true)[0],
                    "shopName" => $shopName,
                    "mobileNumber" => $mobileNumber,
                    "shopType" => $shopType,
                    "sellIinOrder" => $sellIinOrder,
                    "timestamp" => currentDateTime($row["capture_datetime"], "d-m-Y h:i:s A"),
                    "team_id" => $row["team_id"],
                    "team" => $row["team_name"],
                    "branchName" => $row["branch_name"],
                    "circle" => $row["circle"],
                    "section" => $row["section"],
                    "lt" => $row["lt"],
                    "lg" => $row["lg"],
                    "images" => $arrImages,
                    "_timestamp" => $row['_timestamp'],
                );

                $i++;
            }

            // Sort by capture_datetime descending
            usort($arrData, function ($a, $b) {
                return $b['_timestamp'] - $a['_timestamp'];
            });

            // Remove sort key from response
            foreach ($arrData as &$item) {
                unset($item['_timestamp']);
            }
            unset($item);
        }

        $arrResponse = array(
            "total" => $limit["total"],
            "listingData" => $arrData,
        );

        $arrMessage = responseMessage(array(), 1, $arrResponse, true);
        echo json_encode($arrMessage);
    }

    private function getBranches()
    {
        return getBranchList($this->_dbConn, false, "", "", 0, true);
    }

    private function safeValue($value)
    {
        // If value is null, return as-is to avoid type issues
        if ($value === null) {
            return null;
        }

        // If value has characters that need encoding
        return preg_match('/[<>&"\']/', $value) ? htmlentities($value) : $value;
    }

    //Batch load all shop details by rec_id

    private function getBatchShopDetails($shopIds = [], $branchId = null)
    {
        $shopCache = [];

        // Return empty array if no shop IDs provided
        if (empty($shopIds) || !is_array($shopIds)) {
            return $shopCache;
        }

        // Remove duplicates and filter only numeric IDs
        $uniqueShopIds = array_unique(array_filter($shopIds, 'is_numeric'));

        if (empty($uniqueShopIds)) {
            return $shopCache;
        }

        // Determine which table to query based on branch
        if ($branchId == 40) {
            $routeDetailsTable = "tblroute_details_delhi";
        } else {
            $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        }

        // Build the IN clause for SQL query
        $inClause = implode(",", $uniqueShopIds);

        // Execute single batch query instead of N individual queries
        // NOTE: Removed dstatus = 0 filter to get ALL shop details (not just active ones)
        // This matches the original getRowColumns behavior which didn't filter by dstatus
        $rsAction = null;
        $iRows = 0;
        $query = "SELECT rec_id, outlet_name, shop_uniq_code, outlet_mobile, outlet_type, shop_type
              FROM $routeDetailsTable
              WHERE rec_id IN ($inClause)";

        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                // WIN #3: PRE-PROCESS ALL STRINGS HERE (only once per shop, not per row)
                $shopCache[$row['rec_id']] = [
                    removeSpecialCharFromString($row['outlet_name']),  // [0] - Already cleaned
                    $this->safeValue($row['shop_uniq_code']),          // [1] - Already safe
                    $this->safeValue($row['outlet_mobile']),           // [2] - Already safe
                    $this->safeValue($row['outlet_type']),             // [3] - Already safe
                    $row['shop_type']                                   // [4] - Shop Type
                ];
            }
        }

        return $shopCache;
    }

    //Batch load all MDO summary data by ds_id and capture_date

    private function getBatchMDODetails($mdoFilters = [])
    {
        $mdoCache = [];

        // Return empty array if no filters provided
        if (empty($mdoFilters) || !is_array($mdoFilters)) {
            return $mdoCache;
        }

        // Build WHERE clause with multiple conditions
        // (ds_id = X AND capture_date = Y) OR (ds_id = Z AND capture_date = W)
        $orConditions = [];
        foreach ($mdoFilters as $filter) {
            if (isset($filter['ds_id']) && isset($filter['capture_date'])) {
                $dsId = (int)$filter['ds_id'];
                $captureDate = $filter['capture_date'];
                $orConditions[] = "(ds_id = $dsId AND capture_date = '$captureDate')";
            }
        }

        if (empty($orConditions)) {
            return $mdoCache;
        }

        // Execute single batch query instead of N individual queries
        $rsAction = null;
        $iRows = 0;
        $whereClause = implode(" OR ", $orConditions);
        $query = "SELECT ds_id, capture_date, mdo_id, mdo_name
              FROM tblmdo_summary
              WHERE $whereClause";

        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                // Use composite key: ds_id|capture_date
                $key = $row['ds_id'] . "|" . $row['capture_date'];
                $mdoCache[$key] = [
                    $row['mdo_id'],     // [0] - MDO ID
                    $row['mdo_name']    // [1] - MDO Name
                ];
            }
        }

        return $mdoCache;
    }


    final public function getDownloadData()
    {
        $arrTeamType = array(0 => "VAN DS", 1 => "Niche", 2 => "TOWN SWD", 5 => "NPSR");
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // filter query
        $where = $this->getCondition();
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );
        // $branch = array();
        $branch = getFormData($this->_data['searchbar'], "branch");
        $teamType = getFormData($this->_data['searchbar'], "dsType");
        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        // $stockSummaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];

        // create 2 arrays for sale and competition so that Products and Competition columns can be clubbed together
        $arrDownload = array(
            "sale" => array(
                array(),    // header
            ),
            // "competition" => array(
            //     array(),
            // ),
        );

        // create header
        $arrDownload["sale"][0][] = "ProId";
        $arrDownload["sale"][0][] = "Date";
        $arrDownload["sale"][0][] = "Week";
        $arrDownload["sale"][0][] = "Timestamp";
        $arrDownload["sale"][0][] = "Lt";
        $arrDownload["sale"][0][] = "Lg";
        $arrDownload["sale"][0][] = "District";
        $arrDownload["sale"][0][] = "Branch";
        $arrDownload["sale"][0][] = "Region";
        $arrDownload["sale"][0][] = "Circle";
        $arrDownload["sale"][0][] = "Section";
        $arrDownload["sale"][0][] = "WD Code";
        $arrDownload["sale"][0][] = "DS ID";
        $arrDownload["sale"][0][] = "DS Type";
        $arrDownload["sale"][0][] = "DS Name";
        $arrDownload["sale"][0][] = "Accompanied by MDO";
        $arrDownload["sale"][0][] = "MDO ID";
        $arrDownload["sale"][0][] = "MDO Name";
        $arrDownload["sale"][0][] = "Reporting Type";
        $arrDownload["sale"][0][] = "Route";
        // $arrDownload["sale"][0][] = "Market Name";
        $arrDownload["sale"][0][] = "Outlet Name";
        // $arrDownload["sale"][0][] = "Goi Market Id";
        // $arrDownload["sale"][0][] = "Goi Pop Group";
        $arrDownload["sale"][0][] = "Outlet ID";
        $arrDownload["sale"][0][] = "Owner Mobile Number";
        $arrDownload["sale"][0][] = "Outlet Type";
        $arrDownload["sale"][0][] = "Sell-in order";
        $arrDownload["sale"][0][] = "Base Location Distance (meters)";
        $arrDownload["sale"][0][] = "CFT";
        $cftIndex = array_search("CFT", $arrDownload["sale"][0]);
        array_splice($arrDownload["sale"][0], $cftIndex + 1, 0, "ULC");
        $iStartofProductsColumn = count($arrDownload["sale"][0]);

        // Store index of each product and competition to increment quantity in that column
        $arrProductIndex = array();
        //$arrCompetitionIndex = array();

        $this->getBranchWiseProducts(null, $teamType);

        // Loop through each brach data
        foreach ($branch as $branchId) {
            $branchCond = "";
            if ($branchId) {
                $matchAll = checkIfAllSelected($branchId);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchId)) {
                        $branchIds = implode(",", $branchId);
                        $branchCond = " AND b.branch_id IN ($branchIds)";
                    } else {
                        $branchCond = " AND b.branch_id = $branchId";
                    }
                }
            }

            // create header with product list and competition list
            $arrProductBought = $this->getBranchWiseProducts($branchId, $teamType);
            $sProductSaleColumns = "";
            if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                foreach ($arrProductBought as $arrProduct) {
                    $productName = strtoupper($arrProduct[0]);
                    $productColumnName = $arrProduct[1];
                    if (!isset($arrProductIndex[$productName])) {
                        $arrProductIndex[$productName] = count($arrDownload["sale"][0]);
                        $arrDownload["sale"][0][] = "$productName - Qty (M) Bought";
                        // $arrDownload["sale"][0][] = "$productName - Stock (Pkt)";
                    }

                    $sProductSaleColumns .= ", a.$productColumnName";
                }
            }
            // print_r($sProductSaleColumns);die;

            // $arrCompetition = $this->getBranchWiseProducts($branchId, false, $teamType);
            // if ($arrCompetition && isNonEmptyArray($arrCompetition)) {
            //     foreach ($arrCompetition as $competition) {
            //         $competition = strtoupper($competition);
            //         if (!isset($arrCompetitionIndex[$competition])) {
            //             $arrCompetitionIndex[$competition] = count($arrDownload["competition"][0]);
            //             $arrDownload["competition"][0][] = "$competition Competition - Avg Sale";
            //             $arrDownload["competition"][0][] = "$competition Competition - Stock";
            //         }
            //     }
            // }

            // Don't use b.dstatus = 0 AND c.dstatus = 0
            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.pro_id,a.call_time,a.capture_date, a.capture_datetime, a.lt, a.lg, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, a.distance_in_meter" .
                ", b.team_id, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.wd_code, b.branch_id, c.district, c.branch_name, c.main_branch $sProductSaleColumns FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id IN ('99',55) AND a.pro_id > 0 $where $branchCond";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows) {
                // OPTIMIZATION: Collect all shop IDs and MDO filters first, then batch load
                $allShopIds = [];
                $mdoFilters = [];
                $branchShopData = [];

                // Step 1: Collect shop IDs and MDO filters from result set
                if ($iRows > 0) {
                    $tempIndex = 0;
                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        // Collect shop IDs for Phase 1A
                        $shopId = $row["ques_3"];
                        if (is_numeric($shopId)) {
                            $allShopIds[] = $shopId;
                        }

                        // Collect MDO filters for Phase 1B
                        $teamId = $row["team_id"];
                        $captureDate = $row["capture_date"];
                        $mdoFilters[] = [
                            'ds_id' => $teamId,
                            'capture_date' => $captureDate
                        ];

                        // PRE-DECODE JSON FIELDS (decode once, use many times)
                        $ques1Decoded = json_decode($row["ques_1"], true);
                        $row['_ques_1_decoded'] = isset($ques1Decoded[0]) ? $ques1Decoded[0] : '';

                        // PRE-COMPUTE TIMESTAMP FOR SORTING (convert once, sort faster)
                        $row['_timestamp'] = strtotime($row["capture_datetime"]);

                        // Save row for processing
                        $branchShopData[$tempIndex] = $row;
                        $tempIndex++;
                    }
                }

                $shopCache = $this->getBatchShopDetails($allShopIds, $branchId);

                $mdoCache = $this->getBatchMDODetails($mdoFilters);

                usort($branchShopData, function ($a, $b) {
                    return $b['_timestamp'] - $a['_timestamp'];  // Descending order
                });

                if ($iRows > 0) {
                    $productCount = 0;
                    // $sReplacedProductSaleColumns = trim(substr(str_replace("a.", "", $sProductSaleColumns), 1));
                    $index = count($arrDownload["sale"]);

                    foreach ($branchShopData as $row) {
                        // Convert seconds to HH:MM:SS format
                        $proId = $row["pro_id"];
                        $currentBranchId = $row["branch_id"];
                        $captureDate = $row["capture_date"];
                        $week = $this->getWeekNumber($captureDate);
                        $time = round($row["call_time"] / 1000);  // convert ms → seconds
                        $minutes = floor($time / 60);
                        $seconds = $time % 60;
                        $timeSpent = sprintf("%d:%02d", $minutes, $seconds);
                        $meters = $row["distance_in_meter"] / 1000;
                        $roundedMeters = round($meters, 2);
                        $teamId = $row["team_id"];
                        $mdoKey = $teamId . "|" . $captureDate;
                        if (isset($mdoCache[$mdoKey])) {
                            $isMdo = "1";
                            $mdoId = $mdoCache[$mdoKey][0];
                            $mdoName = $mdoCache[$mdoKey][1];
                        } else {
                            $isMdo = "0";
                            $mdoId = "";
                            $mdoName = "";
                        }

                        $shopId = $row["ques_3"];

                        // Lookup in cache (O(1) operation) instead of database query
                        if (is_numeric($shopId) && isset($shopCache[$shopId])) {
                            $shopDetails = $shopCache[$shopId];
                        } else {
                            $shopDetails = ["", "", "", "", ""];  // Default values matching array size
                        }

                        // Just use them directly (no need to call removeSpecialCharFromString, safeValue again)
                        $shopName     = isset($shopDetails[0]) ? $shopDetails[0] : "";
                        $shopUniqCode = isset($shopDetails[1]) ? $shopDetails[1] : "";
                        $mobileNumber = isset($shopDetails[2]) ? $shopDetails[2] : "";
                        $shopType     = isset($shopDetails[3]) ? $shopDetails[3] : "";

                        // Determine sell-in order
                        if ($currentBranchId == 40) {
                            $allProductsSold = false;

                            if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                                foreach ($arrProductBought as $arrProduct) {
                                    $productColumnName = $arrProduct[1];
                                    $iSale = isset($row[$productColumnName]) ? floatval($row[$productColumnName]) : 0;

                                    if ($iSale > 0) {
                                        $allProductsSold = true;
                                    }
                                }
                            }

                            $sellIinOrder = $allProductsSold ? "Yes" : "No";
                        } else {
                            $sellIinOrder = $row["ques_4"];
                        }

                        // $arrDownload["competition"][$index] = array();
                        $arrDownload["sale"][$index][] = $proId;
                        $arrDownload["sale"][$index][] = $captureDate;
                        $arrDownload["sale"][$index][] = $week;
                        $arrDownload["sale"][$index][] = currentDateTime($row["capture_datetime"], "d-m-Y h:i:s A");
                        $arrDownload["sale"][$index][] = $row["lt"];
                        $arrDownload["sale"][$index][] = $row["lg"];
                        $arrDownload["sale"][$index][] = $row["district"];
                        $arrDownload["sale"][$index][] = $row["main_branch"];
                        $arrDownload["sale"][$index][] = $row["branch_name"];
                        $arrDownload["sale"][$index][] = $row["circle"];
                        $arrDownload["sale"][$index][] = $row["section"];
                        $arrDownload["sale"][$index][] = $row["wd_code"];
                        $arrDownload["sale"][$index][] = $row["team_id"];
                        $arrDownload["sale"][$index][] = $row["is_type"] != "" ? $arrTeamType[$row["is_type"]] : "";
                        $arrDownload["sale"][$index][] = $row["team_name"];
                        $arrDownload["sale"][$index][] = $isMdo;
                        $arrDownload["sale"][$index][] = $mdoId;
                        $arrDownload["sale"][$index][] = $mdoName;
                        $arrDownload["sale"][$index][] = $row["ques_0"];
                        // USE PRE-DECODED JSON (no need to decode again!)
                        $arrDownload["sale"][$index][] = htmlspecialchars_decode($row['_ques_1_decoded']);
                        // $arrDownload["sale"][$index][] = htmlspecialchars_decode($feederMarketName);
                        $arrDownload["sale"][$index][] = htmlspecialchars_decode($shopName);
                        // $arrDownload["sale"][$index][] = htmlspecialchars_decode($goiMarketId);
                        // $arrDownload["sale"][$index][] = htmlspecialchars_decode($goiPopGroup);
                        $arrDownload["sale"][$index][] = htmlspecialchars_decode($shopUniqCode);  // ← OUTLET ID
                        $arrDownload["sale"][$index][] = $mobileNumber;
                        $arrDownload["sale"][$index][] = $shopType;
                        $arrDownload["sale"][$index][] = $sellIinOrder;
                        $arrDownload["sale"][$index][] = $roundedMeters;
                        $arrDownload["sale"][$index][] = $timeSpent;
                        $arrDownload["sale"][$index][] = "";

                        if ($sellIinOrder === "Yes") {
                            // get Stock in Products Bought
                            // $arrStock = getRowColumns($this->_dbConn, $stockSummaryTable, $sReplacedProductSaleColumns, "stock_type = 2 AND rec_id = $proId", array(), true, 2);

                            // $competitionDetails = getGridDataAsArray(
                            //     json_decode($row["ques_7"], true),
                            //     2,
                            //     isNonEmptyArray($arrCompetition) ? count($arrCompetition) : 1
                            // );
                            $productCount = 0; // Initialize product count before the loop
                            // get each product sale
                            if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                                foreach ($arrProductBought as $productIndex => $arrProduct) {
                                    $productName = strtoupper($arrProduct[0]);
                                    $productColumnName = $arrProduct[1];
                                    $pktSize = $arrProduct[2];
                                    $iSale = isset($row[$productColumnName]) ? $row[$productColumnName] : 0;
                                    // Ensure the product value exists and is strictly greater than 0
                                    if (isset($iSale) && floatval($iSale) > 0) {
                                        $productCount++; // Count only if the value is greater than 0
                                    }

                                    $arrDownload["sale"][$index][$cftIndex + 1] = isset($productCount) ? $productCount : 0;
                                    // get index of product and insert sale
                                    $iProductIndex = $arrProductIndex[$productName];
                                    if ($currentBranchId == 40) {
                                        $arrDownload["sale"][$index][$iProductIndex] = $pktSize ? round(floatval($iSale) / $pktSize, 2) : floatval($iSale);
                                    } else {
                                        $arrDownload["sale"][$index][$iProductIndex] = floatval($iSale);
                                    }
                                    // insert Stock in Products Bought
                                    // $iStock = isset($arrStock[$productColumnName]) && floatval($arrStock[$productColumnName]) ? floatval($arrStock[$productColumnName]) : 0;
                                    // $arrDownload["sale"][$index][$iProductIndex + 1] = $iStock;
                                }
                            }

                            // get each competition
                            // if ($arrCompetition && isNonEmptyArray($arrCompetition)) {
                            //     foreach ($arrCompetition as $compIndex => $competition) {
                            //         $iCompetitionAvgSale = isset($competitionDetails[0][$compIndex]) ? $competitionDetails[0][$compIndex] : 0;
                            //         $iCompetitionStock = isset($competitionDetails[1][$compIndex]) ? $competitionDetails[1][$compIndex] : 0;

                            //         // get index of competition
                            //         $competition = strtoupper($competition);
                            //         $iCompetitionIndex = $arrCompetitionIndex[$competition];
                            //         $arrDownload["competition"][$index][$iCompetitionIndex] = floatval($iCompetitionAvgSale);
                            //         $arrDownload["competition"][$index][$iCompetitionIndex + 1] = floatval($iCompetitionStock);
                            //     }
                            // }
                        }
                        $index++;
                    }
                }
            }
        }

        // CSV GENERATION WITH SPECIAL CHARACTER HANDLING
        $fileName = "VanDs_Report_$currentDateTime.csv";

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }

        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        // Open file for writing
        $fp = fopen($filename, 'w');

        if ($fp === false) {
            $arrMessage = responseMessage(array("Failed to create CSV file"), 0);
            echo json_encode($arrMessage);
            return;
        }

        // Write UTF-8 BOM for proper Excel compatibility with special characters
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $iNofOfProductsColumn = count($arrDownload["sale"][0]) - $iStartofProductsColumn;

        // Process and write data
        foreach ($arrDownload["sale"] as $index => $arrBody) {
            if ($index === 0) {
                // Write header
                $this->writeCsvRow($fp, $arrBody);
            } else {
                // Prepare body data
                $arrValues = array_slice($arrBody, 0, $iStartofProductsColumn);

                // Add product data
                for ($productIndex = 0; $productIndex < $iNofOfProductsColumn; $productIndex++) {
                    $arrValues[] = isset($arrBody[$iStartofProductsColumn + $productIndex]) ? $arrBody[$iStartofProductsColumn + $productIndex] : '';
                }

                // Write row
                $this->writeCsvRow($fp, $arrValues);
            }
        }

        fclose($fp);

        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        echo json_encode($arrMessage);
    }

    private function writeCsvRow($fileHandle, $data)
    {
        $escapedData = array();

        foreach ($data as $field) {
            // Convert to string
            $field = (string)$field;

            // Escape double quotes by doubling them (CSV standard)
            $field = str_replace('"', '""', $field);

            // Always quote fields to prevent issues with:
            // - Commas (,)
            // - Double quotes (")
            // - Newlines (\n, \r)
            // - Leading/trailing spaces
            // - Special characters from other languages
            $escapedData[] = '"' . $field . '"';
        }

        // Write the row
        fwrite($fileHandle, implode(',', $escapedData) . "\n");
    }

    final public function getDownloadSummary()
    {
        $arrTeamType = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $stockSummaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $constantsTable = $this->_tables["CONSTANTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];
        // $respTable = getRespTable(1, $this->_projectId);
        // Recommended indexes for speed: response (dstatus, ques_0, team_id, capture_date); stock_summary (dstatus, stock_type, team_id, capture_date); vands_summary (dstatus, team_id, activity_date).
        $where = "";
        $where2 = "";
        // filter query
        $where .= $this->getCondition(true);
        $where2 .= $this->getCondition(true);
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.activity_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $stockWhere = str_replace(array("a.activity_date", "a.team_id"), array("capture_date", "team_id"), $where);

        // prepare missing team condition
        $sTeamCond = getFilterResult(
            $this->_data['searchbar'],
            array("dsName" => array("team_id", 0, true, true)),
            $this->_dbConn
        );
        // $branch = array();
        $branch = getFormData($this->_data['searchbar'], "branch");
        $teamType = getFormData($this->_data['searchbar'], "dsType");

        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }
        // $minTotalShops =  (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
        // $minQualifiedAttendanceTimeInMin =  (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
        // $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;

        // create 2 arrays for sale and pickup stock so that pickup stock columns can be appended after sale columns
        $arrSummary = array(
            "sale" => array(
                array(),    // header
            ),
            "stock" => array(
                array(),
            ),
        );

        // create header
        $arrSummary["sale"][0][] = "District";
        $arrSummary["sale"][0][] = "Branch";
        $arrSummary["sale"][0][] = "Region";
        $arrSummary["sale"][0][] = "Circle";
        $arrSummary["sale"][0][] = "Section";
        $arrSummary["sale"][0][] = "WD Code";
        $arrSummary["sale"][0][] = "DS ID";
        $arrSummary["sale"][0][] = "DS Name";
        $arrSummary["sale"][0][] = "DS Type";
        $arrSummary["sale"][0][] = "Accompanied by MDO";
        $arrSummary["sale"][0][] = "MDO ID";
        $arrSummary["sale"][0][] = "MDO Name";
        $arrSummary["sale"][0][] = "Date";
        $arrSummary["sale"][0][] = "Week";
        $arrSummary["sale"][0][] = "Present";
        $arrSummary["sale"][0][] = "Start Time";
        $arrSummary["sale"][0][] = "End Time";
        $arrSummary["sale"][0][] = "Day End Time";
        $arrSummary["sale"][0][] = "First Outlet Visit Time";
        $arrSummary["sale"][0][] = "Last Outlet Visit Time";
        $arrSummary["sale"][0][] = "Total Time Spent (Mins)";
        $arrSummary["sale"][0][] = "Time in Market (Mins)";
        $arrSummary["sale"][0][] = "KM Travelled";
        $arrSummary["sale"][0][] = "Total CFT (mins)";
        $arrSummary["sale"][0][] = "Avg CFT/OL (mins)";
        $arrSummary["sale"][0][] = "Qualified Attendance";
        $arrSummary["sale"][0][] = "Ideal Route";
        $arrSummary["sale"][0][] = "Route Taken";
        $arrSummary["sale"][0][] = "Route Day";
        $arrSummary["sale"][0][] = "Route Adherence";
        $arrSummary["sale"][0][] = "Reason For Non-Adherence";
        $arrSummary["sale"][0][] = "Planned Outlets";
        $arrSummary["sale"][0][] = "Outlets Visited";
        $arrSummary["sale"][0][] = "Outlets billed";
        $arrSummary["sale"][0][] = "Outlets added";
        $outletAddedIndex = array_search("Outlets added", $arrSummary["sale"][0]);
        array_splice($arrSummary["sale"][0], $outletAddedIndex + 1, 0, "Total Stock Carried (M)");
        array_splice($arrSummary["sale"][0], $outletAddedIndex + 2, 0, "Total Sale (M)");

        $iStartofProductsColumn = count($arrSummary["sale"][0]);

        // get branchwise products and competition of all branches
        $this->getBranchWiseProducts(null, isNonEmptyArray($teamType) ? $teamType : null);

        // // get branch wise pickup stock products
        // $this->getBranchWiseStockPickupProducts(null, $teamType);

        // Store index of each product and stock to increment quantity in that column
        $arrProductIndex = array();
        $arrStockIndex = array();

        // Loop through each brach data
        foreach ($branch as $branchId) {
            $branchCond = "";
            if ($branchId) {
                $matchAll = checkIfAllSelected($branchId);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchId)) {
                        $branchIds = implode(",", $branchId);
                        $branchCond = " AND a.branch_id IN ($branchIds)";
                    } else {
                        $branchCond = " AND a.branch_id = $branchId";
                    }
                }
            }

            // create header with product list sold
            $arrProductBought = $this->getBranchWiseProducts($branchId, isNonEmptyArray($teamType) ? $teamType : null);
            $sProductSaleColumns = "";
            if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                foreach ($arrProductBought as $arrProduct) {
                    $productName = strtoupper($arrProduct[0]);
                    $productColumnName = $arrProduct[1];

                    if (!isset($arrProductIndex[$productName])) {
                        $arrProductIndex[$productName] = count($arrSummary["sale"][0]);
                        $arrSummary["sale"][0][] = "$productName - Qty (M) Sold";
                    }

                    $sProductSaleColumns .= ", SUM(a.$productColumnName) AS $productColumnName";
                }
            }

            // create header with product list carried
            $arrStockProducts = $this->getBranchWiseProducts($branchId, isNonEmptyArray($teamType) ? $teamType : null);
            $sStockColumns = "";
            if ($arrStockProducts && isNonEmptyArray($arrStockProducts)) {
                foreach ($arrStockProducts as $stockProduct) {
                    $stockProductName = strtoupper($stockProduct[0]);
                    if (!isset($arrStockIndex[$stockProductName])) {
                        $arrStockIndex[$stockProductName] = count($arrSummary["stock"][0]);
                        $arrSummary["stock"][0][] = "{$stockProductName} - Qty (M) Carried";
                        // $arrSummary["stock"][0][] = "{$stockProductName} - Readystock Avg Sale";
                    }

                    $sStockColumns .= ", {$stockProduct[1]}";
                }
            }

            // get sales first; stock loaded only for (team_id, capture_date) in result (fast: chunked IN)
            $arrTeamWiseStock = array();
            // Don't use b.dstatus = 0
            // $where .= getFilterResult(
            //     $this->_data['searchbar'],
            //     array(
            //         "dsType" => array("b.is_type", -1),
            //     )
            // );

            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.route, a.activity_date, a.dayend_datetime, a.start_datetime, a.end_datetime, a.resp_startdatetime, a.resp_enddatetime, a.is_beat_adherence,a.beat_adherence_reason, a.planned_outlets, SUM(a.total_sales_deliveries) AS total_sales_deliveries" .
                ", SUM(a.total_sellin_shops) AS total_sellin_shops, SUM(a.total_other_shops) AS total_other_shops, a.total_meter_travelled, a.uni_total_sellin_shops, b.team_id, b.team_name, b.is_type, b.circle, b.section, b.branch_id, b.wd_code, a.is_qualified $sProductSaleColumns" .
                " FROM $summaryTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.s_id = '99' AND b.branch_id = $branchId $where GROUP BY a.activity_date, a.team_id ORDER BY a.activity_date DESC, b.team_name";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                $arrBranchDetails = getRowsColumns($this->_dbConn, $branchTable, "branch_id, branch_name, main_branch, district");

                $allRows = [];
                $teamDatePairs = [];
                $mdoFilters = [];
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $allRows[] = $row;
                    $teamDatePairs[] = ['team_id' => $row["team_id"], 'date' => $row["activity_date"]];
                    $mdoFilters[] = ['ds_id' => $row["team_id"], 'capture_date' => $row["activity_date"]];
                }

                $mdoCache = $this->getBatchMDODetails($mdoFilters);

                // Fast: Load stock only for (team_id, capture_date) pairs in this branch result (chunked IN)
                if ($arrStockProducts && isNonEmptyArray($arrStockProducts) && isNonEmptyArray($teamDatePairs)) {
                    $stockChunkSize = 200;
                    $stockChunks = array_chunk($teamDatePairs, $stockChunkSize);
                    foreach ($stockChunks as $chunk) {
                        $inList = array();
                        foreach ($chunk as $p) {
                            $tid = (int) $p['team_id'];
                            $d = addslashes($p['date']);
                            $inList[] = "($tid, '$d')";
                        }
                        $inClause = implode(",", $inList);
                        $sStockQuery = "SELECT team_id, capture_date, stock_type $sStockColumns FROM $stockSummaryTable WHERE dstatus = 0 AND stock_type IN (0) AND (team_id, capture_date) IN ($inClause)";
                        $sStockAction = null;
                        $iStockRows = 0;
                        $this->_dbConn->ExecuteSelectQuery($sStockQuery, $sStockAction, $iStockRows);
                        if ($iStockRows > 0) {
                            while ($rowStock = $this->_dbConn->GetData($sStockAction)) {
                                $teamId = $rowStock["team_id"];
                                $captureDate = $rowStock["capture_date"];
                                $stockType = $rowStock["stock_type"];
                                $arrTeamWiseStock[$captureDate][$teamId][$stockType] = array();
                                foreach ($arrStockProducts as $product) {
                                    $arrTeamWiseStock[$captureDate][$teamId][$stockType][$product[1]] = $rowStock[$product[1]];
                                }
                            }
                        }
                    }
                }

                foreach ($allRows as $row) {
                    $index = count($arrSummary["sale"]);
                    $routeName = $row["route"];
                    $date = $row["activity_date"];
                    $week = $this->getWeekNumber($date);
                    $dayOfWeek = date('D', strtotime($date));
                    $teamId = $row["team_id"];
                    $dateEsc = addslashes($date);

                    // getRowColumn for call time and outlet counts (per team/date)
                    $totalCallMs = (float) getRowColumn($this->_dbConn, $respTable, "COALESCE(SUM(call_time), 0)", "ques_0 IN ('Outlet Order', 'Add Outlet') AND dstatus = '0' AND capture_date = '$dateEsc' AND team_id = '$teamId'");
                    $time = $totalCallMs / 1000;
                    $totalTimeSpent = $time > 0 ? gmdate("H:i:s", (int) round($time)) : "";
                    $totalMinutes = $time > 0 ? floor($time / 60) : "";

                    $orderShop = (int) getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Outlet Order' AND dstatus = '0' AND capture_date = '$dateEsc' AND team_id = '$teamId'");
                    $addShop = (int) getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' AND capture_date = '$dateEsc' AND team_id = '$teamId'");

                    $totalSale = 0;
                    if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                        foreach ($arrProductBought as $productIndex => $arrProduct) {
                            $productName = strtoupper($arrProduct[0]);
                            $iSale = isset($row[$arrProduct[1]]) ? $row[$arrProduct[1]] : 0;
                            $totalSale += $iSale;
                        }
                    }

                    $mdoKey = $teamId . "|" . $date;
                    if (isset($mdoCache[$mdoKey])) {
                        $isMdo = "1";
                        $mdoId = $mdoCache[$mdoKey][0];
                        $mdoName = $mdoCache[$mdoKey][1];
                    } else {
                        $isMdo = "0";
                        $mdoId = "";
                        $mdoName = "";
                    }

                    $arrPlannedOutlet = $row["planned_outlets"];
                    $branchId = $row["branch_id"];
                    if ($row["is_beat_adherence"] == "Yes") {
                        $isBeatAdher = "1";
                    } elseif ($row["is_beat_adherence"] == "No") {
                        $isBeatAdher = "0";
                    }
                    $reason = $row["beat_adherence_reason"];
                    if ($branchId == 40) {
                        $idealRoute = $dayOfWeek;
                        // $arrPlannedOutlet = getRowColumn($this->_dbConn, "tblroute_details_delhi", "COUNT(shop_uniq_code)", "dstatus = '0' AND route_name = '$routeName' AND team_id = $teamId");
                        $arrPlannedOutletBeatDay = array(1 => $dayOfWeek);

                        $routeDays = explode('-', strtolower($row["route"]));
                        // $arrLtLg = getRowsColumns(
                        //     $this->_dbConn,
                        //     $respTable,
                        //     "lt, lg",
                        //     "dstatus = '0' AND capture_date = '$date' AND team_id = '$teamId' ORDER BY pro_id ASC"
                        // );

                        // // Calculate record-to-record distance
                        // $totalDistance = 0;
                        // for ($i = 0; $i < count($arrLtLg) - 1; $i++) {
                        //     $lat1 = $arrLtLg[$i][0];
                        //     $lon1 = $arrLtLg[$i][1];
                        //     $lat2 = $arrLtLg[$i + 1][0];
                        //     $lon2 = $arrLtLg[$i + 1][1];

                        //     $distance = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
                        //     $totalDistance += $distance;

                        //     // If totalDistance is more than 80 km, set it to random value between 70–80
                        //     if ($totalDistance > 80) {
                        //         $totalDistance = rand(70, 80);
                        //         break; // stop further calculation if you want max 80
                        //     }
                        // }

                        // // Final distance in KM (rounded 2 decimals)
                        // $distanceInKm = round($totalDistance, 2);
                    } else {
                        $idealRoute = getRowColumn($this->_dbConn, $routeTable, "route_name", "dstatus = '0' AND beat_day = '$dayOfWeek' AND team_id = '$teamId'");
                        // for planned outlets count don't use dstatus condition
                        $arrPlannedOutletBeatDay = getRowColumns($this->_dbConn, $routeTable, "COUNT(shop_uniq_code), beat_day", "route_name = '$routeName' AND team_id = $teamId");
                    }
                    $sellInShop = $row['uni_total_sellin_shops'];
                    $distanceInKm = isset($row["total_meter_travelled"]) ? round($row["total_meter_travelled"] / 1000, 2) : 0;

                    $mainBranch = $branchName = $district = "";
                    if (($branchIndex = array_search($branchId, array_column($arrBranchDetails, 0))) !== false) {
                        $branchName = $arrBranchDetails[$branchIndex][1];
                        $mainBranch = $arrBranchDetails[$branchIndex][2];
                        $district = $arrBranchDetails[$branchIndex][3];
                    }

                    $totalShops = $orderShop + $addShop;

                    // Divide by total shops
                    $timePerShop = ($totalShops > 0) ? ($time / $totalShops) : 0;
                    list($min, $sec) = explode(':', gmdate("i:s", (int) round($timePerShop)));
                    $timePerShopFormatted = $min . '.' . $sec;

                    // Convert back to i:s format
                    // $timePerShopFormatted = gmdate("i:s", (int) round($timePerShop));
                    // $totalShops = $row["total_sales_deliveries"] + $row["total_other_shops"];
                    $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
                    // $isQualifiedAttendance = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? "1" : "0";
                    $isQualifiedAttendance = (string) $row["is_qualified"];

                    $arrSummary["stock"][$index] = array();
                    $arrSummary["sale"][$index][] = $district;
                    $arrSummary["sale"][$index][] = $mainBranch;
                    $arrSummary["sale"][$index][] = $branchName;
                    $arrSummary["sale"][$index][] = $row["circle"];
                    $arrSummary["sale"][$index][] = $row["section"];
                    $arrSummary["sale"][$index][] = $row["wd_code"];
                    $arrSummary["sale"][$index][] = $teamId;
                    $arrSummary["sale"][$index][] = $row["team_name"];
                    $arrSummary["sale"][$index][] = $row["is_type"] != "" ? $arrTeamType[$row["is_type"]] : "";
                    $arrSummary["sale"][$index][] = $isMdo;
                    $arrSummary["sale"][$index][] = $mdoId;
                    $arrSummary["sale"][$index][] = $mdoName;
                    $arrSummary["sale"][$index][] = currentDate($date, "d-m-Y");
                    $arrSummary["sale"][$index][] = $week;
                    $arrSummary["sale"][$index][] = "1";
                    $arrSummary["sale"][$index][] = currentDateTime($row["start_datetime"], "H:i:s");
                    $arrSummary["sale"][$index][] = currentDateTime($row["end_datetime"], "H:i:s");
                    $arrSummary["sale"][$index][] = $row["dayend_datetime"] ? currentDateTime($row["dayend_datetime"], "H:i:s") : "";
                    $arrSummary["sale"][$index][] = isset($row["resp_startdatetime"]) ? currentDateTime($row["resp_startdatetime"], "H:i:s") : "";
                    $arrSummary["sale"][$index][] = isset($row["resp_enddatetime"]) ? currentDateTime($row["resp_enddatetime"], "H:i:s") : "";
                    $arrSummary["sale"][$index][] = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], false, false, true);
                    $arrSummary["sale"][$index][] = getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);
                    $arrSummary["sale"][$index][] = $distanceInKm;
                    $arrSummary["sale"][$index][] = $totalMinutes;
                    $arrSummary["sale"][$index][] = (float) $timePerShopFormatted;
                    $arrSummary["sale"][$index][] = $isQualifiedAttendance;
                    $arrSummary["sale"][$index][] = $idealRoute;
                    $arrSummary["sale"][$index][] = $row["route"];
                    $arrSummary["sale"][$index][] = isset($arrPlannedOutletBeatDay[1]) ? $arrPlannedOutletBeatDay[1] : "";
                    $arrSummary["sale"][$index][] = $isBeatAdher;
                    $arrSummary["sale"][$index][] = $reason;
                    $arrSummary["sale"][$index][] = isset($arrPlannedOutlet) ? $arrPlannedOutlet : "";
                    $arrSummary["sale"][$index][] = $totalShops;
                    $arrSummary["sale"][$index][] = $sellInShop;
                    $arrSummary["sale"][$index][] = $addShop;

                    // insert sale
                    $totalProductSale = 0; // Initialize total product sale
                    if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                        foreach ($arrProductBought as $productIndex => $arrProduct) {
                            $productName = strtoupper($arrProduct[0]);
                            $iSale = isset($row[$arrProduct[1]]) ? $row[$arrProduct[1]] : 0;
                            // $iSale = $row[$arrProduct[1]];
                            // Accumulate the product sale
                            $totalProductSale += $iSale;


                            // get index of product
                            $iProductIndex = $arrProductIndex[$productName];
                            $arrSummary["sale"][$index][$iProductIndex] = floatval($iSale);
                        }
                    }

                    $totalReadyStockPickup = 0; // Initialize total ready stock pickup
                    // insert pickup stock Qty and Avg sale
                    foreach ($arrStockProducts as $stockProduct) {
                        $arrStock = isset($arrTeamWiseStock[$date][$teamId]) ? $arrTeamWiseStock[$date][$teamId] : array();
                        $iStockQty = isset($arrStock[0][$stockProduct[1]]) ? $arrStock[0][$stockProduct[1]] : 0;

                        // Accumulate the ready stock pickup
                        $totalReadyStockPickup += $iStockQty;

                        // get index of product
                        $stockProductName = strtoupper($stockProduct[0]);
                        $iStockIndex = $arrStockIndex[$stockProductName];
                        $arrSummary["stock"][$index][$iStockIndex] = floatval($iStockQty);
                    }
                    $arrSummary["sale"][$index][$outletAddedIndex + 1] = $totalReadyStockPickup;
                    $arrSummary["sale"][$index][$outletAddedIndex + 2] = $totalProductSale;
                    $index++;
                }
            }

            $dateFrom = isset($this->_data["searchbar"]['dateFrom']) ? $this->_data["searchbar"]['dateFrom'] : $this->_data['dateFrom'];
            $dateTo = isset($this->_data["searchbar"]['dateTo']) ? $this->_data["searchbar"]['dateTo'] : $this->_data['dateTo'];

            // $dateFrom = sprintf('%04d-%02d-%02d', $dateFrom['year'], $dateFrom['month'], $dateFrom['day']);
            // $dateTo = sprintf('%04d-%02d-%02d', $dateTo['year'], $dateTo['month'], $dateTo['day']);

            if (is_array($dateFrom)) {
                $dateFrom = sprintf('%04d-%02d-%02d', $dateFrom['year'], $dateFrom['month'], $dateFrom['day']);
            }
            if (is_array($dateTo)) {
                $dateTo = sprintf('%04d-%02d-%02d', $dateTo['year'], $dateTo['month'], $dateTo['day']);
            }

            // Convert date strings to DateTime objects
            $startDate = new DateTime($dateFrom);
            $endDate = new DateTime($dateTo);

            while ($startDate <= $endDate) {
                $index = count($arrSummary["sale"]);
                $date = $startDate->format('Y-m-d'); // Format the current date
                $week = $this->getWeekNumber($date);
                $arrDates = array();

                if (!in_array($date, $arrDates)) {
                    $arrDates[] = $date;

                    $teamTypeCon = getFilterResult(
                        $this->_data['searchbar'],
                        array(
                            "dsType" => array("a.is_type", 0, true, true),
                        )
                    );
                    // Query to get teams who have not uploaded any record on that date
                    $iTeamRows = 0;
                    $rsTeamAction = 0;
                    $sTeamQuery = "SELECT a.team_id, a.team_name, a.is_type, a.circle, a.section, a.wd_code, b.district, b.branch_name, b.main_branch FROM $projectTeamTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND a.s_id = '99' AND a.branch_id = b.branch_id $branchCond" .
                        " AND a.team_id NOT IN (SELECT DISTINCT team_id FROM $summaryTable WHERE dstatus = 0 AND activity_date = '$date') $where2 $sTeamCond $teamTypeCon ORDER BY a.team_name";
                    $this->_dbConn->ExecuteSelectQuery($sTeamQuery, $rsTeamAction, $iTeamRows);

                    if ($iTeamRows) {
                        while ($rowTeam = $this->_dbConn->GetData($rsTeamAction)) {
                            $arrSummary["stock"][$index] = array();
                            $arrSummary["sale"][$index][] = $rowTeam["district"];
                            $arrSummary["sale"][$index][] = $rowTeam["main_branch"];
                            $arrSummary["sale"][$index][] = $rowTeam["branch_name"];
                            $arrSummary["sale"][$index][] = $rowTeam["circle"];
                            $arrSummary["sale"][$index][] = $rowTeam["section"];
                            $arrSummary["sale"][$index][] = $rowTeam["wd_code"];
                            $arrSummary["sale"][$index][] = $rowTeam["team_id"];
                            $arrSummary["sale"][$index][] = $rowTeam["team_name"];
                            $arrSummary["sale"][$index][] = $rowTeam["is_type"] != "" ? $arrTeamType[$rowTeam["is_type"]] : "";
                            $arrSummary["sale"][$index][] = "0";
                            $arrSummary["sale"][$index][] = "";
                            $arrSummary["sale"][$index][] = "";
                            $arrSummary["sale"][$index][] = currentDate($date, "d-m-Y");
                            $arrSummary["sale"][$index][] = $week;
                            $arrSummary["sale"][$index][] = "0";
                            $index++;
                        }
                    }
                }

                // Move to the next date
                $startDate->modify('+1 day');
            }
        }

        $fileName = "VanDs_Summary_$currentDateTime.csv";
        $iNofOfProductsColumn = count($arrSummary["sale"][0]) - $iStartofProductsColumn;
        $iNofOfStockColumn = count($arrSummary["stock"][0]);

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        $fp = fopen($filename, 'w');
        if ($fp === false) {
            $arrMessage = responseMessage(array("Failed to create CSV file"), 0);
            echo json_encode($arrMessage);
            return;
        }

        // UTF-8 BOM for Excel compatibility
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($arrSummary["sale"] as $index => $arrBody) {
            if ($index === 0) {
                $arrValues = array();
                foreach ($arrBody as $body) {
                    $arrValues[] = $body;
                }
                foreach ($arrSummary["stock"][$index] as $stock) {
                    $arrValues[] = $stock;
                }
            } else {
                ksort($arrBody);
                $arrValues = array_slice($arrBody, 0, $iStartofProductsColumn);
                for ($productIndex = 0; $productIndex < $iNofOfProductsColumn; $productIndex++) {
                    $val = isset($arrBody[$iStartofProductsColumn + $productIndex]) ? $arrBody[$iStartofProductsColumn + $productIndex] : 0;
                    $arrValues[] = ($val === 0 || $val === 0.0 || (is_numeric($val) && floatval($val) == 0)) ? '' : $val;
                }
                if (isNonEmptyArray($arrSummary["stock"][$index])) {
                    for ($stockIndex = 0; $stockIndex < $iNofOfStockColumn; $stockIndex++) {
                        $val = isset($arrSummary["stock"][$index][$stockIndex]) ? $arrSummary["stock"][$index][$stockIndex] : 0;
                        $arrValues[] = ($val === 0 || $val === 0.0 || (is_numeric($val) && floatval($val) == 0)) ? '' : $val;
                    }
                }
            }
            $this->writeCsvRow($fp, $arrValues);
        }

        fclose($fp);

        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );
        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        echo json_encode($arrMessage);
    }

    public function getDownloadBinderReport()
    {
        $where = "";
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // Filter query
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $district = getFormData($this->_data['searchbar'], "district");
        $branch = getFormData($this->_data['searchbar'], "branch");
        $circle = getFormData($this->_data['searchbar'], "circle");
        $section = getFormData($this->_data['searchbar'], "section");
        $wdCode = getFormData($this->_data['searchbar'], "wdCode");
        $dsType = getFormData($this->_data['searchbar'], "dsType");
        $dsName = getFormData($this->_data['searchbar'], "dsName");

        // ADD THIS: Convert "all" to actual branch IDs
        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $Cond = "";
        $teamTypeCond = "";

        if ($dsType) {
            $matchAll = checkIfAllSelected($dsType);
            if (!$matchAll) {
                if (isNonEmptyArray($dsType)) {
                    $dsTypes = "'" . implode("','", $dsType) . "'";
                    $teamTypeCond .= " AND team_type IN ($dsTypes)";
                    $Cond .= " AND b.is_type IN ($dsTypes)";
                } else {
                    $teamTypeCond .= " AND team_type = $dsType";
                    $Cond .= " AND b.is_type = $dsType";
                }
            }
        }

        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchs = "'" . implode("','", $branch) . "'";
                    $Cond .= " AND b.branch_id IN ($branchs)";
                } else {
                    $Cond .= " AND b.branch_id = $branch";
                }
            } elseif ($district) {
                $districts = "'" . implode("','", $district) . "'";
                $distCond = " AND district IN ($districts)";
                $branch = getRowsColumn($this->_dbConn, "tblbranch", 'branch_id', "dstatus = 0 $distCond");
            }
        }

        if ($circle) {
            $matchAll = checkIfAllSelected($circle);
            if (!$matchAll) {
                if (isNonEmptyArray($circle)) {
                    $circles = "'" . implode("','", $circle) . "'";
                    $Cond .= " AND b.circle IN ($circles)";
                } else {
                    $Cond .= " AND b.circle = $circle";
                }
            }
        }

        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $sections = "'" . implode("','", $section) . "'";
                    $Cond .= " AND b.section IN ($sections)";
                } else {
                    $Cond .= " AND b.section = $section";
                }
            }
        }

        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCodes = "'" . implode("','", $wdCode) . "'";
                    $Cond .= " AND b.wd_code IN ($wdCodes)";
                } else {
                    $Cond .= " AND b.wd_code = $wdCode";
                }
            }
        }

        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $Cond .= " AND b.team_id IN ($dsNames)";
                } else {
                    $Cond .= " AND b.team_id = $dsName";
                }
            }
        }

        $allCond = "";
        if ($Cond) {
            $allCond .= " AND a.team_id IN (SELECT team_id FROM $projectTeamTable WHERE dstatus = 0  $Cond)";
        }

        // Create header
        $header = [];
        $header[] = [
            "Branch",
            "Region",
            "Circle",
            "Section",
            "WD Code",
            "DS Type",
            "DS ID",
            "DS Name",
            "Accompanied by MDO",
            "MDO ID",
            "MDO Name",
            "Date",
            "Week",
            "Route",
            "Outlet Name",
            "Owner Moblie Number",
            "Outlet ID",
            "Outlet Type",
            "Category",
            "Variant",
            "Sales Qty (M)"
        ];

        $arrDataHolder = [];

        foreach ($branch as $branchId) {
            $sProductQuery = "SELECT DISTINCT product_name, summary_column_name, category_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId $teamTypeCond ORDER BY product_name";
            $sProductAction = null;
            $iProductRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                $summaryColName = [];
                $productNames = [];
                $category_name = [];

                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $summaryColName[] = $rowProduct["summary_column_name"];
                    $productNames[] = $rowProduct["product_name"];
                    $category_name[$rowProduct["summary_column_name"]] = $rowProduct["category_name"];
                }
                $sProductSaleColumns = implode(",", $summaryColName);

                $isType = [0 => "Van DS", 1 => "Niches", 5 => "NPSR", 2 => "Town SWD"];
                $rsAction = null;
                $iRows = 0;

                // Fetch all records (no grouping)
                $sQuery = "SELECT a.capture_datetime, a.capture_date, a.ques_0, a.ques_1, a.ques_3, b.team_id, b.team_name, b.is_type, b.circle, b.section, b.wd_code, c.branch_name, c.main_branch, $sProductSaleColumns" .
                    " FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c Where a.team_id = b.team_id AND b.branch_id = c.branch_id AND a.dstatus = 0 AND ques_0 IN ('Outlet Order', 'Add Outlet')" .
                    " $where $allCond AND b.branch_id = $branchId ORDER BY a.capture_date DESC, capture_datetime DESC";

                $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

                if ($iRows > 0) {
                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        $mainBranchName = $row['main_branch'];
                        $branchName = $row['branch_name'];
                        $teamId = $row['team_id'];
                        $teamName = $row['team_name'];
                        $circle = $row['circle'];
                        $outletId = $row['ques_3'];
                        $section = $row['section'];
                        $dsType = $row['is_type'] != "" ? $isType[$row['is_type']] : "";
                        $wdCode = $row['wd_code'];
                        $date = $row['capture_date'];
                        $week = $this->getWeekNumber($date);
                        $route = htmlspecialchars_decode(json_decode($row["ques_1"], true)[0]);
                        $outletData = getRowColumns(
                            $this->_dbConn,
                            $routeTable,
                            "outlet_name, shop_uniq_code, outlet_type, outlet_mobile",
                            "rec_id = '$outletId' AND team_id = $teamId "
                        );
                        $isMdoWorks = getRowColumns($this->_dbConn, "tblmdo_summary", "mdo_id, mdo_name", "ds_id = $teamId AND capture_date = '$date'");
                        if (isNonEmptyArray($isMdoWorks)) {
                            $isMdo = "1";
                            $mdoId = isset($isMdoWorks[0]) ? $isMdoWorks[0] : "";
                            $mdoName = isset($isMdoWorks[1]) ? $isMdoWorks[1] : "";
                        } else {
                            $isMdo = "0";
                            $mdoId = "";
                            $mdoName = "";
                        }

                        $outletName = isset($outletData[0]) ? htmlentities($outletData[0]) : "";
                        $shopUniqueCode = $outletData[1] ?? "";
                        $outletType = $outletData[2] ?? "";
                        $mobileNo = $outletData[3] ?? "";

                        foreach ($summaryColName as $colName) {
                            // Get sales quantity for each product variant per transaction
                            $salesQty = $row[$colName] ?? 0;

                            if ($salesQty > 0) {
                                $arrDataHolder[] = [
                                    cleanCSVValue($mainBranchName),
                                    cleanCSVValue($branchName),
                                    cleanCSVValue($circle),
                                    cleanCSVValue($section),
                                    cleanCSVValue($wdCode),
                                    cleanCSVValue($dsType),
                                    cleanCSVValue($teamId),
                                    cleanCSVValue($teamName),
                                    cleanCSVValue($isMdo),
                                    cleanCSVValue($mdoId),
                                    cleanCSVValue($mdoName),
                                    cleanCSVValue($date),
                                    cleanCSVValue($week),
                                    cleanCSVValue($route),
                                    cleanCSVValue($outletName),
                                    cleanCSVValue($mobileNo),
                                    cleanCSVValue($shopUniqueCode),
                                    cleanCSVValue($outletType),
                                    cleanCSVValue($category_name[$colName] ?? ''),
                                    cleanCSVValue($productNames[array_search($colName, $summaryColName)]),
                                    cleanCSVValue($salesQty)
                                ];
                            }
                        }
                    }
                }
            }
        }

        $arrResult = formatDownloadData("BINDER_REPORT", $header, $arrDataHolder);
        $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
        echo json_encode($arrMessage);
    }

    //Download PDF
    final public function getDownloadPDFReport()
    {
        global  $CUST_FOLDER_PATH;
        global  $UPLOAD_URL;
        $arrTeamType = array(0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
        // $arrInfraType = array(7 => "MDO", 10 => "FSO");
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // filter query
        $where = $this->getCondition();
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $branch = getFormData($this->_data['searchbar'], "branch");
        $dsType = getFormData($this->_data['searchbar'], "dsType");
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $this->getBranchWiseProducts(null, isNonEmptyArray($dsType) ? $dsType : null);
        // Initialize PDF
        $pdf = new Pdf();

        // Create title page
        $pdf->createPage();
        $pdf->addTitle("VAN DS REPORT", 28, array(138, 51, 255));

        $hasData = false;
        $recordCount = 0;

        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }

        // Loop through each branch data
        foreach ($branch as $branchId) {
            $branchCond = "";
            if ($branchId) {
                $matchAll = checkIfAllSelected($branchId);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchId)) {
                        $branchIds = implode(",", $branchId);
                        $branchCond = " AND b.branch_id IN ($branchIds)";
                    } else {
                        $branchCond = " AND b.branch_id = $branchId";
                    }
                }
            }

            $arrProductBought = $this->getBranchWiseProducts($branchId, isNonEmptyArray($dsType) ? $dsType : null);
            $saleColumns = "";
            $productColumnNames = [];
            if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                $productColumnName = [];
                foreach ($arrProductBought as $arrProduct) {
                    if (is_array($arrProduct) && count($arrProduct) >= 2) {
                        if (is_string($arrProduct[1]) && !empty($arrProduct[1])) {
                            $productColumnName[] = $arrProduct[1];
                        }
                    }
                }
                if (!empty($productColumnName)) {
                    $productColumnName = array_unique($productColumnName);
                    $productColumnNames = $productColumnName;
                    $selectColumns = array_map(function ($col) {
                        return "a." . $col;
                    }, $productColumnName);
                    $saleColumns = ", " . implode(", ", $selectColumns);
                }
            }
            $dsTypeCond = "";
            if ($dsType) {
                $matchAll = checkIfAllSelected($dsType);
                if (!$matchAll) {
                    if (isNonEmptyArray($dsType)) {
                        $dsTypes = implode(",", $dsType);
                        $dsTypeCond = " AND b.is_type IN ($dsTypes)";
                    } else {
                        $dsTypeCond = " AND b.is_type = $dsType";
                    }
                }
            }
            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.pro_id, a.uni_id, a.call_time, a.capture_date, a.capture_datetime, a.lt, a.lg,
           a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7,
           a.ques_8, a.ques_9, a.ques_10, a.distance_in_meter,
           b.team_id, b.team_name, b.branch_id, b.is_type, b.circle, b.wd_code, b.section,
           c.district, c.branch_name, c.main_branch
           $saleColumns
           FROM tblsurvey_response_details AS a, $projectTeamTable AS b, $branchTable AS c
           WHERE a.dstatus = 0
           AND a.team_id = b.team_id
           AND b.branch_id = c.branch_id
           $dsTypeCond
           $where
           $branchCond
           ORDER BY capture_datetime DESC";

            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
            if ($iRows) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $uniId = $row["uni_id"];
                    $shopFrontPicture = $row["ques_6"];
                    $branchId = $row["branch_id"];
                    $captureDate = $row["capture_date"];
                    $week = $this->getWeekNumber($captureDate);
                    $workWdCode = $row["wd_code"];  // wd code
                    $WdName = "";
                    $WdMarket = "";
                    $WdPopGroup = "";
                    $allImages = array();
                    $rsAllImages = null;
                    $iAllRows = 0;
                    $sImageQuery = "SELECT b.mob_img_id, b.file_name as name, b.file_path as filepath, b.file_domain FROM tblsurvey_response_file_new AS b WHERE b.dstatus = '0' AND b.uni_id = '$uniId' AND b.mob_img_id = '$shopFrontPicture' ORDER BY b.mob_img_id";
                    $this->_dbConn->ExecuteSelectQuery($sImageQuery, $rsAllImages, $iAllRows);

                    if ($iAllRows > 0) {
                        if (!isset($allImages[$uniId])) {
                            $allImages[$uniId] = array();
                        }
                        while ($imgRow = $this->_dbConn->GetData($rsAllImages)) {
                            $allImages[$uniId][$imgRow['mob_img_id']] = $imgRow;
                        }
                    }

                    if (!empty($workWdCode)) {
                        $arrWdDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$workWdCode'");
                        $WdName = $arrWdDetails[0] ?? "";
                        $WdMarket = $arrWdDetails[1] ?? "";
                        $WdPopGroup = $arrWdDetails[2] ?? "";
                    }
                    $team_id = $row['team_id'];
                    $mdoName = $row["team_name"];
                    $shopId = $row["ques_3"]; // outlet id
                    $dsType = $row["is_type"];
                    $dsTypeValue = isset($arrTeamType[$dsType]) ? $arrTeamType[$dsType] : "";
                    $captureDate = $row["capture_date"];
                    $captureDateTime = $row["capture_datetime"];
                    $outlet_name = "";
                    if (!empty($shopId) && is_numeric($shopId)) {
                        $outletResult = getRowColumns($this->_dbConn, "tblroute_details", "outlet_name", "rec_id = " . intval($shopId));
                        $outlet_name = is_array($outletResult) ? ($outletResult[0] ?? "") : "";
                    }
                    $district = $row["district"];
                    $branchName = $row["branch_name"];
                    $mainBranch = $row["main_branch"];
                    $circle = $row["circle"];
                    $section = $row["section"];
                    $lt = $row["lt"];
                    $lg = $row["lg"];
                    // Initialize total product sale, ulc, alc
                    $totalProductSale = 0;
                    $uniqueProductsSold = [];
                    if (!empty($productColumnNames)) {
                        foreach ($productColumnNames as $columnName) {
                            // $totalProductSale += isset($row[$columnName]) ? floatval($row[$columnName]) : 0;
                            $productValue = isset($row[$columnName]) ? floatval($row[$columnName]) : 0;
                            $totalProductSale += $productValue;

                            // Count unique products sold
                            if ($productValue > 0) {
                                $uniqueProductsSold[] = $columnName;
                            }
                        }
                    }
                    $totalULC = 0;
                    $avgULC = 0;
                    if (!empty($productColumnNames) && !empty($shopId) && !empty($team_id)) {
                        $columnsStr = implode(", ", $productColumnNames);

                        $sQueryULC = "SELECT $columnsStr
                                    FROM tblsurvey_response_details
                                    WHERE dstatus = 0
                                    AND ques_3 = " . intval($shopId) . "
                                    AND team_id = " . intval($team_id) . "
                                    AND capture_date LIKE '$captureDate%'";

                        $rsULC = null;
                        $iRowsULC = 0;
                        $this->_dbConn->ExecuteSelectQuery($sQueryULC, $rsULC, $iRowsULC);

                        $totalUniqueProducts = [];
                        $visitCount = 0;

                        if ($iRowsULC > 0) {
                            while ($rowULC = $this->_dbConn->GetData($rsULC)) {
                                $visitCount++;

                                foreach ($productColumnNames as $columnName) {
                                    $value = floatval($rowULC[$columnName] ?? 0);

                                    if ($value > 0) {
                                        if (!in_array($columnName, $totalUniqueProducts)) {
                                            $totalUniqueProducts[] = $columnName;
                                        }
                                    }
                                }
                            }
                        }

                        $totalULC = count($totalUniqueProducts);
                        // $avgULC = $visitCount > 0 ? round($totalULC / $visitCount, 2) : 0;
                    }
                    //Image
                    $getCorrectImage = function ($arrImages, $mobImgId) {
                        if (isset($arrImages[$mobImgId])) {
                            return $arrImages[$mobImgId];
                        }
                        return null;
                    };
                    // $arrImages2 = isset($allImages[$uniId]) ? $allImages[$uniId] : array();
                    // $images = array();
                    // if (isset($shopFrontPicture) && $shopFrontPicture) {
                    //     $storePhoto = $getCorrectImage($arrImages2, $shopFrontPicture);
                    //     // print_r($storePhoto);die;
                    //     if ($storePhoto && !empty($storePhoto['filepath']) && !empty($storePhoto['name'])) {
                    //         $destImage = $CUST_FOLDER_PATH . $storePhoto['filepath'] . $storePhoto['name'];
                    //         // $destImage = $storePhoto['filedomain']
                    //         //     . PRODS_ANY_FOLDER
                    //         //     . $storePhoto['filepath']
                    //         //     . $storePhoto['name'];
                    //             // echo $destImage;die;
                    //         if (file_exists($destImage) && is_file($destImage) && is_readable($destImage)) {
                    //             $images[] = array(
                    //                 'path' => $destImage,
                    //                 'label' => "Outlet Visibility Picture"
                    //             );
                    //         }
                    //     }
                    // }
                    $arrImages2 = isset($allImages[$uniId]) ? $allImages[$uniId] : array();
                    $images = array();

                    if (isset($shopFrontPicture) && $shopFrontPicture) {
                        $storePhoto = $getCorrectImage($arrImages2, $shopFrontPicture);

                        if ($storePhoto && !empty($storePhoto['filepath']) && !empty($storePhoto['name'])) {
                            if ($captureDate >= '2026-03-14') {
                                $imageUrl = $storePhoto['file_domain']
                                    . PRODS_ANY_FOLDER
                                    . $storePhoto['filepath']
                                    . $storePhoto['name'];

                                $urlMap = [
                                    $uniId . "_" . $shopFrontPicture => $imageUrl
                                ];
                                $downloadedImages = $pdf->downloadMultipleImagesToTemp($urlMap, 35);
                                if (!empty($downloadedImages)) {
                                    foreach ($downloadedImages as $tmpPath) {
                                        if (file_exists($tmpPath)) {
                                            $images[] = [
                                                'path' => $tmpPath,
                                                'label' => "Outlet Visibility Picture"
                                            ];
                                        }
                                    }
                                }
                            } else {
                                $destImage = $CUST_FOLDER_PATH . $storePhoto['filepath'] . $storePhoto['name'];
                                if (file_exists($destImage) && is_file($destImage) && is_readable($destImage)) {
                                    $images[] = [
                                        'path' => $destImage,
                                        'label' => "Outlet Visibility Picture"
                                    ];
                                }
                            }
                        }
                    }

                    if (!empty($images)) {
                        $hasData = true;
                        $pdf->createPage();

                        // First table
                        $tableData1 = array(
                            array("DISTRICT", "BRANCH", "REGION", "CIRCLE", "SECTION", "VAN DS ID", "VAN DS NAME", "DS TYPE", "DATE", "WEEK"),
                            array($district, $mainBranch, $branchName, $circle, $section, $team_id, $mdoName, $dsTypeValue, $captureDate, $week)
                        );
                        $pdf->addTable($tableData1, 2, 9, 5, 10, 287, 7, array(138, 51, 255), array(255, 255, 255), array(0, 0, 0));
                        $pdf->Ln(3);

                        // Second table
                        $tableData2 = array(
                            array("WD CODE", "WD NAME", "WD MARKET", "WD POP GROUP", "OUTLET ID", "OUTLET NAME", "TIMESTAMP", "SALES(M)", "ULC"),
                            array($workWdCode, $WdName, $WdMarket, $WdPopGroup, $shopId, $outlet_name, $captureDateTime, $totalProductSale, $totalULC)
                        );
                        $pdf->addTable($tableData2, 2, 9, 5, $pdf->GetY(), 287, 7, array(138, 51, 255), array(255, 255, 255), array(0, 0, 0));

                        $imageY = $pdf->GetY() + 5;
                        $imgWidth = 130;
                        $imgSpacing = 10;
                        $numImages = count($images);
                        $totalImagesWidth = ($numImages * $imgWidth) + (($numImages - 1) * $imgSpacing);
                        $centeredX = ((277 - $totalImagesWidth) / 2) + 10;
                        $pdf->addImages($images, $centeredX, $imageY, $imgWidth, 130, $imgSpacing);
                        // Cleanup temp files downloaded from CDN (only /tmp files, not local server files)
                        foreach ($images as $img) {
                            if (
                                isset($img['path']) &&
                                strpos($img['path'], sys_get_temp_dir()) !== false &&
                                file_exists($img['path'])
                            ) {
                                @unlink($img['path']);
                            }
                        }
                        $images = []; // reset for next iteration
                    }
                    // else {
                    //     $pdf->Ln(5);
                    //     $pdf->SetFont('Arial', 'I', 10);
                    //     $pdf->Cell(287, 10, 'No Image Available', 0, 1, 'C');
                    // }

                    $recordCount++;
                    if ($recordCount % 50 == 0) {
                        gc_collect_cycles();
                    }
                }
            }
        }

        // Check if we have data to generate PDF
        if (!$hasData) {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
            echo json_encode($arrMessage);
            return;
        }

        // Save PDF
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
        $fileName = "VANDS_$currentDateTime.pdf";

        $fileDetails = $pdf->savePdf($fileName, false);
        $arrResponse = array(
            "filePath" => $fileDetails["downloadUrl"],
            "fileName" => $fileName,
        );

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $arrResponse);
        echo json_encode($arrMessage);
    }
}
