<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// phpcs:ignore
class PDFAccessReport
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

        if (isset($teamType) && $teamType != ""  && $teamType >= 0) {
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
            "binderReportDownloadDays" => 5
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

    final public function getDownloadData()
    {
        $dwnCond = $this->getCondition();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $tblpdf_access_log = "tblpdf_access_log";

        // order by condition
        $sOrderCond = getOrderByCond("c.rcd");

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
        $sQuery = "SELECT c.rcd, c.team_id, c.access_datetime, a.is_type, a.team_name, a.circle, a.section, a.wd_code, b.district, b.branch_name,b.main_branch" .
            " FROM $projectTeamTable AS a, $branchTable AS b,$tblpdf_access_log as c WHERE a.dstatus = 0 AND a.branch_id = b.branch_id AND a.team_id = c.team_id AND c.dstatus = 0 $where $dwnCond $sOrderCond";
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
                    "PDF Accessed",
                    "PDF Access DateTime",

                ),
            );
            while ($row = $this->_dbConn->GetData($sAction)) {
                $teamId = $row['team_id'];
                $captureDate = $row['rcd'];
                $is_type = $row['is_type'];
                $access_datetime = $row['access_datetime'];
                if ($access_datetime == null) {
                    $pdfAccessed = "No";
                } else {
                    $pdfAccessed = "Yes";
                }
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
                    $pdfAccessed,
                    $access_datetime,
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
            $fileName = "PDF_ACCESS_REPORT_" . date('Y-m-d_H-i-s') . ".xlsx";
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
