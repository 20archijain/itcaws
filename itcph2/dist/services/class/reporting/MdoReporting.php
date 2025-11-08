<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// phpcs:ignore
class MdoReporting
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
                    $condition .= " AND b.district IN ($districts)";
                } else {
                    $condition .= " AND b.district = $district";
                }
            }
        }
        $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = "'" . implode("','", $branch) . "'";
                    $condition .= " AND b.branch_id IN ($branchIds)";
                } else {
                    $condition .= " AND b.branch_id = $branch";
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
                    $condition .= " AND c.wd_code IN ($wdCodeIds)";
                } else {
                    $condition .= " AND c.wd_code = '$wdCode'";
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
                $condition .= " AND c.wd_market IN ($wdMarket)";
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
                $condition .= " AND c.wd_pop_group IN ($wdPopGroup)";
            }
        }

        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if (isset($teamType) && $teamType != "" && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $condition .= " AND a.is_type IN ($teamTypes)";
                }
            }
        }

        $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $condition .= " AND a.team_id IN ($dsNames)";
                } else {
                    $condition .= " AND a.team_id = $dsName";
                }
            }
        }

        $where = "";
        if ($condition && $andCondition) {
            $where .= " AND a.team_id IN (SELECT a.team_id FROM $teamTable as a, $branchTable as b, $mappingTable as c, tblmdo_wd_mapping AS d WHERE a.dstatus = '0' AND b.dstatus = '0' AND c.dstatus = '0' AND a.branch_id = b.branch_id AND a.team_id = d.mdo_id AND c.rec_id = d.wd_id $condition)";
        } elseif ($condition) {
            $where .= " AND a.team_id IN (SELECT a.ateam_id FROM $teamTable as a, $branchTable as b, $mappingTable as e, tblmdo_wd_mapping AS d WHERE a.dstatus = '0' AND b.dstatus = '0' AND c.dstatus = '0' AND a.branch_id = b.branch_id AND a.team_id = d.mdo_id AND c.rec_id = d.wd_id $condition)";
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '10' $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '10' $where order by a.branch_name";
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
        $query = "select Distinct b.circle, c.circle_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id AND b.s_id = '10' $where order by b.circle";
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
        $query = "select Distinct b.section, c.section_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id AND b.s_id = '10' $where order by b.section";
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
        $query = "select Distinct c.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id AND b.s_id = '10' $where order by b.wd_code";
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
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id AND b.s_id = '10' $where order by c.wd_market";
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
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id AND b.s_id = '10' $where order by c.wd_pop_group";
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
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.is_type IN (7, 10) AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id $where order by b.is_type";
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
                } elseif ($row['is_type'] == 7) {
                    $teamType = "MDO";
                } elseif ($row['is_type'] == 10) {
                    $teamType = "FSO";
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b, tblmapping_wd as c, tblmdo_wd_mapping AS d where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.team_name IS NOT NULL AND b.team_name != '' AND b.s_id = '10' AND b.team_id = d.mdo_id AND c.rec_id = d.wd_id $where order by b.team_name";
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
        $typeCond = "";
        if ($dsType) {
            if ($dsType) {
                if (!is_array($dsType)) {
                    $dsType = array($dsType);
                }
                if (in_array('all', $dsType)) {
                    $typeCond = ""; // No condition for 'all'
                } else {
                    $dsType = "'" . implode("','", $dsType) . "'";
                    $typeCond = " AND b.is_type IN ($dsType)";
                }
            }
            $arrResult = array(
                "teamList" => $this->getTeamsList($typeCond),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
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

        $partialQuery = "FROM tblsurvey_response_details_mdo AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = '10' $where";

        // Don't use b.dstatus = 0 AND c.dstatus = 0
        $arrData = array();
        $rsAction = null;
        $iRows = 0;
        // use a.pro_id > 0 to include primary column as index while calculating no of rows
        $sQuery = "SELECT a.pro_id $partialQuery AND a.pro_id > 0";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);

        $sQuery = "SELECT a.uni_id, a.capture_datetime, a.lt, a.lg, a.wd_code, a.ds_name, a.route_name, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, a.ques_10" .
            ", a.ques_11, b.team_id, b.team_name,b.circle,b.section,b.branch_id, c.branch_name $partialQuery ORDER BY capture_datetime DESC";
        $sQuery .= " " . $limit["limit"];
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $i = 0;
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $wdcode = $row["wd_code"];
                $dsName = $row["ds_name"];
                $route = $row["route_name"];
                $sellIinOrder = $row["ques_4"];
                $itcVisiblity = $row["ques_8"];
                if ($itcVisiblity == 'YES') {
                    $visibilityPicture = $row["ques_6"];
                } else {
                    $visibilityPicture = "";
                }
                $implimentItcVisiblity = $row["ques_10"];
                if ($implimentItcVisiblity == 'YES') {
                    $outletPicture = $row["ques_11"];
                } else {
                    $outletPicture = "";
                }

                $arrImages = getListingImages(
                    $this->_dbConn,
                    $row["uni_id"],
                    "",
                    array(
                        $visibilityPicture => "Outlet Visibility Picture Lt: {$row["lt"]} Lg: {$row["lg"]}",
                        $outletPicture => "Outlet Photo Lt: {$row["lt"]} Lg: {$row["lg"]}",
                    )
                );

                $arrData[$i] = array(
                    "reportingType" => $row["ques_0"],
                    "workType" => $row["ques_1"],
                    "wdCode" => $wdcode,
                    "route" => $route,
                    "dsName" => $dsName,
                    // "mobileNumber" => $mobileNumber,
                    // "shopType" => $shopType,
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
                );

                $i++;
            }
        }

        $arrResponse = array(
            "total" => $limit["total"],
            "listingData" => $arrData,
        );

        $arrMessage = responseMessage(array(), 1, $arrResponse, true);
        echo json_encode($arrMessage);
    }

    final public function getDownloadData()
    {
        global  $UPLOAD_URL;
        $arrTeamType = array(0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
        $arrInfraType = array(7 => "MDO", 10 => "FSO");
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

        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        // create header
        $arrExcelData = [];
        $arrExcelData[] = [
            "ProId",
            "Lt",
            "Lg",
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "Infra Type",
            "MDO ID",
            "MDO Name",
            "Date",
            "Week",
            "Type Of Market Work",
            "WD Code",
            "WD Name",
            "WD Market",
            "WD Pop Group",
            "DS Id",
            "DS Name",
            "DS Type",
            "Route Taken",
            "Outlet Id",
            "Outlet Name",
            "Timestamp",
            "Survey Volume (Ms)",
            "Survey Value (Rs)",
            "Lines Cut",
            "Does the outlet have ITC visibility",
            "Outlet Visibility Picture Link",
            "Did you implement ITC visibility",
            "Outlet Photo Link"
        ];

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

            // Don't use b.dstatus = 0 AND c.dstatus = 0
            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.pro_id, a.uni_id,a.call_time,a.capture_date, a.capture_datetime, a.lt, a.lg, a.wd_code, a.ds_name, a.type, a.route_name, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, a.ques_10, a.ques_11, a.distance_in_meter" .
                ", b.team_id, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.branch_id, c.district, c.branch_name, c.main_branch, a.lt,a.lg FROM tblsurvey_response_details_mdo AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = 10 AND a.pro_id > 0 $where $branchCond ORDER BY capture_datetime DESC";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $proId = $row["pro_id"];
                    $uniId = $row["uni_id"];
                    $branchId = $row["branch_id"];
                    $captureDate = $row["capture_date"];
                    $week = $this->getWeekNumber($captureDate);
                    $typeOfWork = $row["ques_1"];
                    $workWdCode = $row["wd_code"];
                    $arrWdDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$workWdCode'");
                    $mdoName = $row["team_name"];
                    $route = $row["route_name"];
                    $shopId = $row["ques_4"];
                    $dsType = $row["type"];
                    $infraType = $row["is_type"];
                    if ($dsType == 6 || $dsType == 8 || $dsType == 9) {
                        $arrRoute = $shopId ? getRowColumns($this->_dbConn, "tblroute_details_breeze", "team_id, outlet_name", "rec_id = $shopId") : "";
                    } else {
                        $arrRoute = $route && $shopId ? getRowColumns($this->_dbConn, "tblroute_details", "team_id, outlet_name", "rec_id = $shopId") : "";
                    }
                    $dsName = $row["ds_name"];
                    $parts = explode(" - ", $dsName, 2);
                    $dsNameOnly = $parts[0];
                    $surveyVol = $row["ques_5"];
                    $surveyVal = $row["ques_6"];
                    $lineCut = $row["ques_7"];
                    $itcVisibility = $row["ques_8"];
                    if ($itcVisibility == 'Yes') {
                        $itcVisibility = "1";
                        $visibilityPic = $row["ques_9"];
                    } else {
                        $itcVisibility = "0";
                        $visibilityPic = "";
                    }
                    $implementVisibility = $row["ques_10"];
                    if ($implementVisibility == 'Yes') {
                        $implementVisibility = "1";
                        $outletPic = $row["ques_11"];
                    } else {
                        $implementVisibility = "0";
                        $outletPic = "";
                    }
                    $arrImg1 = $visibilityPic ? getRowColumns($this->_dbConn, "tblsurvey_response_file_new", "file_path, file_name", "uni_id = '$uniId' AND mob_img_id = '$visibilityPic'") : null;

                    $filepath1 = isset($arrImg1, $arrImg1[0]) ? $arrImg1[0] : "";
                    $filename1 = isset($arrImg1, $arrImg1[1]) ? $arrImg1[1] : "";

                    $arrImg2 = $outletPic ? getRowColumns($this->_dbConn, "tblsurvey_response_file_new", "file_path, file_name", "uni_id = '$uniId' AND mob_img_id = '{$outletPic}'") : null;

                    $filepath2 = isset($arrImg2, $arrImg2[0]) ? $arrImg2[0] : "";
                    $filename2 = isset($arrImg2, $arrImg2[1]) ? $arrImg2[1] : "";

                    $link1 = $filepath1 ? $UPLOAD_URL . $filepath1 . $filename1 : "";
                    $link2 = $filepath2 ? $UPLOAD_URL . $filepath2 . $filename2 : "";

                    $arrExcelData[] = [
                        $proId,
                        $row["lt"],
                        $row["lg"],
                        $row["district"],
                        $row["main_branch"],
                        $row["branch_name"],
                        $row["circle"],
                        $row["section"],
                        isset($infraType) ? $arrInfraType[$infraType] : "",
                        $row["team_id"],
                        $mdoName,
                        $captureDate,
                        $week,
                        $typeOfWork,
                        $workWdCode,
                        isset($arrWdDetails[0]) ? $arrWdDetails[0] : "",
                        isset($arrWdDetails[1]) ? $arrWdDetails[1] : "",
                        isset($arrWdDetails[2]) ? $arrWdDetails[2] : "",
                        isset($arrRoute[0]) ? $arrRoute[0] : "",
                        $dsNameOnly,
                        isset($dsType) ? $arrTeamType[$dsType] : "",
                        $route,
                        $shopId,
                        isset($arrRoute[1]) ? $arrRoute[1] : "",
                        currentDateTime($row["capture_datetime"], "H:i:s"),
                        $surveyVol,
                        $surveyVal,
                        $lineCut,
                        $itcVisibility,
                        $link1,
                        $implementVisibility,
                        $link2
                    ];
                }
            }
        }

        $fileName = "MDO_Transaction_Report_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrExcelData);

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);

        echo json_encode($arrMessage);
    }

    final public function getDownloadSummary()
    {
        $arrTeamType = array(0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
        $arrInfraType = array(7 => "MDO", 10 => "FSO");
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $where = "";
        // filter query
        $where .= $this->getCondition(true);
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        // prepare missing team condition
        $sTeamCond = getFilterResult(
            $this->_data['searchbar'],
            array("dsName" => array("team_id", 0, true, true)),
            $this->_dbConn
        );
        // $branch = array();
        $branch = getFormData($this->_data['searchbar'], "branch");

        // create 2 arrays for sale and pickup stock so that pickup stock columns can be appended after sale columns
        $arrExcelData = array();

        // create header
        $arrExcelData[] = [
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "Infra Type",
            "MDO ID",
            "MDO Name",
            "Date",
            "Week",
            "Qualified Attendance",
            "Present",
            "Type Of Market Work",
            "WD Code",
            "WD Name",
            "WD Market",
            "WD Pop Group",
            "DS ID",
            "DS Type",
            "DS Name",
            "Route Taken",
            "Start Time",
            "End Time",
            "First Outlet Visit Time",
            "Last Outlet Visit Time",
            "Total Time Spent (Mins)",
            "Time in Market (Mins)",
            "KM Travelled",
            "Planned Outlets",
            "Surveyed Outlets (by MDO)",
            "Survey Volume (Ms) (Surveyed outlets)",
            "Survey Value (Rs) (Surveyed outlets)",
            "Lines Cut",
            "Outlet Visited (MDO Day-End)",
            "Sales Volume (Ms) (MDO Day-End)",
            "Sales Value (Rs) (MDO Day-End)",
            "Visited Outlets (by DS)",
            "Billed Outlets (by DS)",
            "Total Sale (M) (by DS)",
            "Sale (M) (by DS) (outlets which MDO surveyed)",
            "Total Lines Cut (by DS)"
        ];

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

            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.pro_id, a.uni_id, a.call_time, a.capture_date, MIN(a.capture_datetime) AS startMarket, MAX(a.capture_datetime) AS lastMarket, a.capture_datetime, a.lt, a.lg, a.wd_code, a.ds_name, a.type" .
                ", a.route_name, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, SUM(a.ques_5) AS surveyVol, SUM(a.ques_6) AS surveyVal, SUM(a.ques_7) AS lineCut, a.ques_8, a.ques_9, a.ques_10, a.ques_11" .
                ", a.distance_in_meter, b.team_id, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.branch_id, c.district, c.branch_name, c.main_branch, a.lt,a.lg FROM tblsurvey_response_details_mdo AS a" .
                ", $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = 10 AND a.pro_id > 0 $where $branchCond GROUP BY capture_date, team_id ORDER BY capture_datetime DESC";
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
                        $orderShop = "";
                        $addShop = "";
                        $totalShops = "";
                        $sellInShop = "";
                        $totalSale = "";
                        $totalUlc = "";
                        $arrMdoSurveyedOutlets = array();
                        $mdoSurveyedOutlets = "";
                        $sellbByDsMdoSurveyed = "";
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
                        $endTime = isset($arrDayEndDetails[0]) ? $arrDayEndDetails[0] : "";
                        $arrDayEndOtherDetails = json_decode($arrDayEndDetails[1], true);
                        $dayEndOutlet = $arrDayEndOtherDetails['outlet'];
                        $salesVol = $arrDayEndOtherDetails['SalesVolume'];
                        $salesValue = $arrDayEndOtherDetails['SalesValue'];
                        $distanceInKm = isset($arrDayEndDetails[2]) ? $arrDayEndDetails[2] : "";
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
                    $surveyVol = $row["surveyVol"];
                    $surveyVal = $row["surveyVal"];
                    $lineCut = $row["lineCut"];
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
                    // $distanceInKm = isset($distance) ? (string)round($distance / 1000, 2) : "0";

                    $arrExcelData[] = [
                        $row["district"],
                        $row["main_branch"],
                        $row["branch_name"],
                        $row["circle"],
                        $row["section"],
                        isset($infraType) ? $arrInfraType[$infraType] : "",
                        $teamId,
                        $mdoName,
                        currentDate($date, "d-m-Y"),
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
                        $startTime ? currentDateTime($startTime, "H:i:s") : "",
                        $endTime ? currentDateTime($endTime, "H:i:s") : "",
                        $firstOutletTime ? currentDateTime($firstOutletTime, "H:i:s") : "",
                        $lastOutletTime ? currentDateTime($lastOutletTime, "H:i:s") : "",
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
                        $sellInShop,
                        $totalSale,
                        $sellbByDsMdoSurveyed,
                        $totalUlc
                    ];
                }
            }

            $dateFrom = isset($this->_data["searchbar"]['dateFrom']) ? $this->_data["searchbar"]['dateFrom'] : $this->_data['dateFrom'];
            $dateTo = isset($this->_data["searchbar"]['dateTo']) ? $this->_data["searchbar"]['dateTo'] : $this->_data['dateTo'];

            $dateFrom = sprintf('%04d-%02d-%02d', $dateFrom['year'], $dateFrom['month'], $dateFrom['day']);
            $dateTo = sprintf('%04d-%02d-%02d', $dateTo['year'], $dateTo['month'], $dateTo['day']);

            // Convert date strings to DateTime objects
            $startDate = new DateTime($dateFrom);
            $endDate = new DateTime($dateTo);

            while ($startDate <= $endDate) {
                $date = $startDate->format('Y-m-d'); // Format the current date
                $week = $this->getWeekNumber($date);
                $arrDates = array();

                if (!in_array($date, $arrDates)) {
                    $arrDates[] = $date;
                    // Query to get teams who have not work with DS
                    $iTeamRows = 0;
                    $rsTeamAction = 0;
                    $sTeamQuery = "SELECT a.team_id, a.capture_date, a.capture_datetime, a.lt, a.work_with, a.other_details, a.lg, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.branch_id, c.district, c.branch_name" .
                        ", c.main_branch FROM tblattendance AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = 10" .
                        " AND a.capture_date = '$date' AND a.team_id NOT IN (SELECT team_id FROM tblsurvey_response_details_mdo WHERE dstatus = 0 AND capture_date = '$date') AND a.call_type = '0' $where $branchCond GROUP BY a.team_id ORDER BY a.capture_datetime DESC";
                    $this->_dbConn->ExecuteSelectQuery($sTeamQuery, $rsTeamAction, $iTeamRows);

                    if ($iTeamRows) {
                        while ($rowTeam = $this->_dbConn->GetData($rsTeamAction)) {
                            $date = $rowTeam["capture_date"];
                            $week = $this->getWeekNumber($date);
                            $teamId = $rowTeam["team_id"];
                            $mdoName = $rowTeam["team_name"];
                            $workWith = $rowTeam["work_with"];
                            $infraType = $rowTeam["is_type"];
                            $startTime = $rowTeam["capture_datetime"];
                            $endDetails = getRowColumns($this->_dbConn, "tblattendance", "MIN(capture_datetime), distance, other_details", "dstatus = 0 AND team_id = $teamId AND capture_date = '$date' AND call_type = '1'");
                            $endTime = isset($endDetails[0]) ? $endDetails[0] : "";
                            $timeSpent = $endDetails[0] ? getTimeDifferenceInString($startTime, $endTime, false, false, true) : 0;
                            $distanceInKm = $endDetails[1] ? $endDetails[1] : 0;
                            if (!empty($endDetails[2])) {
                                $arrDayEndOtherDetails = json_decode($endDetails[2], true);
                                $dayEndOutlet = $arrDayEndOtherDetails['outlet'];
                                $salesVol = $arrDayEndOtherDetails['SalesVolume'];
                                $salesValue = $arrDayEndOtherDetails['SalesValue'];
                            }
                            $arrAttenOtherDetails = json_decode($rowTeam["other_details"], true);
                            $dsDetails = $arrAttenOtherDetails['selectRouteYouAreGoingOn'];
                            if ($workWith == 0) {
                                $typeOfWork = "Market work with DS";
                                $wdCode = $dsDetails[0];
                                $attDsName = $dsDetails[1];
                                $parts = explode(" - ", $attDsName, 2);
                                $attDsNameOnly = $parts[0];
                                $attDsType = $parts[1];
                                $dsId = getRowColumn($this->_dbConn, "tblmdo_offline_data", "ds_id", "dstatus = 0 AND wd_code = '$wdCode' AND ds_name = '$attDsNameOnly'");
                                $routeName = $dsDetails[2];
                            } elseif ($workWith == 1) {
                                $typeOfWork = "Market work with AE";
                                $wdCode = $dsDetails[1];
                                $attDsNameOnly = "";
                                $attDsType = "";
                                $dsId = "";
                                $routeName = "";
                            } elseif ($workWith == 2) {
                                $typeOfWork = "Market work with GT TL";
                                $wdCode = $dsDetails[1];
                                $attDsNameOnly = "";
                                $attDsType = "";
                                $dsId = "";
                                $routeName = "";
                            } elseif ($workWith == 3) {
                                $typeOfWork = "Independent market work";
                                $wdCode = $dsDetails[1];
                                $attDsNameOnly = "";
                                $attDsType = "";
                                $dsId = "";
                                $routeName = "";
                            }
                            $arrWdDetails = $wdCode ? getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$wdCode'") : "";
                            // Convert 6 hours into minutes
                            $requiredMin = 360;
                            if ($infraType == 7) {
                                if ($timeSpent >= $requiredMin && $distanceInKm >= 10) {
                                    $attenqualified = '1';
                                } else {
                                    $attenqualified = '0';
                                }
                            } elseif ($infraType == 10) {
                                $attenqualified = '1';
                            }


                            $arrExcelData[] = [
                                $rowTeam["district"],
                                $rowTeam["main_branch"],
                                $rowTeam["branch_name"],
                                $rowTeam["circle"],
                                $rowTeam["section"],
                                isset($infraType) ? $arrInfraType[$infraType] : "",
                                $teamId,
                                $mdoName,
                                currentDate($date, "d-m-Y"),
                                $week,
                                $attenqualified,
                                "1",
                                $typeOfWork,
                                $wdCode,
                                isset($arrWdDetails[0]) ? $arrWdDetails[0] : "",
                                isset($arrWdDetails[1]) ? $arrWdDetails[1] : "",
                                isset($arrWdDetails[2]) ? $arrWdDetails[2] : "",
                                $dsId,
                                $attDsType,
                                $attDsNameOnly,
                                $routeName,
                                $startTime ? currentDateTime($startTime, "H:i:s") : "",
                                $endTime ? currentDateTime($endTime, "H:i:s") : "",
                                "",
                                "",
                                $timeSpent,
                                "",
                                $distanceInKm,
                                "",
                                "",
                                "",
                                "",
                                "",
                                isset($dayEndOutlet) ? $dayEndOutlet : 0,
                                isset($salesVol) ? $salesVol : 0,
                                isset($salesValue) ? $salesValue : 0,
                            ];
                        }
                    }

                    // Query to get teams who have not uploaded any record on that date

                    $iAbsentRows = 0;
                    $rsAbsentAction = 0;
                    $sAbsentQuery = "SELECT a.team_id, a.team_name, a.is_type, a.circle, a.section, a.wd_code, b.district, b.branch_name, b.main_branch FROM $projectTeamTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND a.s_id = '10' AND a.branch_id = b.branch_id $branchCond" .
                        " AND a.team_id NOT IN (SELECT DISTINCT team_id FROM tblattendance WHERE dstatus = 0 AND capture_date = '$date' AND call_type = '0') ORDER BY a.team_name";
                    $this->_dbConn->ExecuteSelectQuery($sAbsentQuery, $rsAbsentAction, $iAbsentRows);

                    if ($iAbsentRows) {
                        while ($rowAbsent = $this->_dbConn->GetData($rsAbsentAction)) {
                            $arrExcelData[] = [
                                $rowAbsent["district"],
                                $rowAbsent["main_branch"],
                                $rowAbsent["branch_name"],
                                $rowAbsent["circle"],
                                $rowAbsent["section"],
                                isset($rowAbsent['is_type']) ? $arrInfraType[$rowAbsent['is_type']] : "",
                                $rowAbsent["team_id"],
                                $rowAbsent["team_name"],
                                currentDate($date, "d-m-Y"),
                                "",
                                "",
                                "0"
                            ];
                        }
                    }
                }

                // Move to the next date
                $startDate->modify('+1 day');
            }
        }

        $fileName = "MDO_Summary_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // $mergedArrExcelData = array_merge($arrExcelData, $arrData);

        // Pass complete data
        $sheet->fromArray($arrExcelData);
        foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $headerRow = '1';
        $styleHeader = $sheet->getStyle('A' . $headerRow . ':' . $sheet->getHighestDataColumn() . $headerRow);
        $styleHeader->getFont()->setBold(true);
        $styleHeader->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $styleHeader->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);   // set border
        $styleHeader->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // center align text
        $styleHeader->getAlignment()->setVertical(Alignment::VERTICAL_CENTER); // center align text
        $styleHeader->getFill()->setFillType(Fill::FILL_SOLID);  // fill style
        $styleHeader->getFont()->getColor()->setARGB("FF000000"); // font color
        $styleHeader->getAlignment()->setWrapText(true);   // wrap text

        $allStyle = [
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())->applyFromArray($allStyle);

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);

        echo json_encode($arrMessage);
    }

    final public function attendanceDayEndReport()
    {
        $arrInfraType = array(7 => "MDO", 10 => "FSO");
        // $arrTeamType = array(0 => "VAN DS", 5 => "NPSR", 8 => "Stokiest DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $where = "";
        // filter query
        $where .= $this->getCondition(true);
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );
        // $branch = array();
        $branch = getFormData($this->_data['searchbar'], "branch");

        // create 2 arrays for sale and pickup stock so that pickup stock columns can be appended after sale columns
        $arrExcelData = array();

        // create header
        $arrExcelData[] = [
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "Infra Type",
            "MDO ID",
            "MDO Name",
            "Date",
            "Week",
            "WD Code",
            "WD Name",
            "WD Market",
            "WD Pop Group",
            "Type of Work",
            "DS ID",
            "DS Type",
            "DS Name",
            "Qualified",
            "Total Time",
            "KM Travelled",
            "Start Lt",
            "Start Lg",
            "Start Time",
            "Day Start Google Address",
            "State",
            "District",
            "City",
            "Pincode",
            "End Lt",
            "End Lg",
            "End Time",
            "Day End Google Address",
            "State",
            "District",
            "City",
            "Pincode",
            "Same Location"
        ];

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

            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.capture_date, a.capture_datetime, a.lt, a.lg, a.other_details, a.google_address, a.state, a.district AS googleDistrict, a.city, a.pincode" .
                ", b.team_id, b.team_name, b.branch_id, b.is_type,b.circle,b.section, b.branch_id, c.district, c.branch_name, c.main_branch FROM tblattendance AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.call_type = '0'" .
                " AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.s_id = 10 $where $branchCond GROUP BY capture_date, team_id ORDER BY capture_datetime DESC";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $branchId = $row["branch_id"];
                    $teamId = $row["team_id"];
                    $startLt = $row["lt"];
                    $startLg = $row["lg"];
                    $date = $row["capture_date"];
                    $infraType = $row["is_type"];
                    $week = $this->getWeekNumber($date);
                    $dayOfWeek = date('D', strtotime($date));
                    $mdoName = $row["team_name"];
                    $arrOtherDetails = json_decode($row["other_details"], true);
                    $workingWith = $arrOtherDetails['workingWith'];
                    if ($workingWith == 'Market work with DS') {
                        $arrRouteDetails = $arrOtherDetails["selectRouteYouAreGoingOn"];
                        $wdCode = $arrRouteDetails[0];
                        $dsName = $arrRouteDetails[1];
                        $routeName = $arrRouteDetails[2];
                        $parts = explode(" - ", $dsName, 2);
                        $dsNameOnly = $parts[0];
                        $dsType = $parts[1];
                        $arrWdDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "wd_firm_name, wd_market, wd_pop_group", "wd_code = '$wdCode'");
                        $dsId = getRowColumn($this->_dbConn, "tblmdo_offline_data", "ds_id", "dstatus = 0 AND route_name = '$routeName' AND wd_code = '$wdCode' AND team_id = $teamId");
                    } else {
                        $arrRouteDetails = "";
                        $wdCode = "";
                        $dsName = "";
                        $dsId = "";
                        $dsType = "";
                        $parts = "";
                        $dsNameOnly = "";
                        $arrWdDetails = array();
                    }
                    $startTime = $row['capture_datetime'];
                    $googleAddress = $row['google_address'];
                    $state = $row['state'];
                    $district = $row['googleDistrict'];
                    $city = $row['city'];
                    $pinCode = $row['pincode'];
                    $arrDayEndDetails = getRowColumns($this->_dbConn, "tblattendance", "capture_datetime, google_address, state, district, city, pincode, distance, lt, lg", "call_type = '1' AND team_id = $teamId AND capture_date = '$date'");
                    $endTime = $arrDayEndDetails[0] ?? null;
                    $endGoogleAddress = $arrDayEndDetails[1] ?? "";
                    $endState = $arrDayEndDetails[2] ?? "";
                    $endDistrict = $arrDayEndDetails[3] ?? "";
                    $endCity = $arrDayEndDetails[4] ?? "";
                    $endPinCode = $arrDayEndDetails[5] ?? "";
                    $timeSpent = $endTime ? getTimeDifferenceInString($startTime, $endTime, false, false, true) : 0;
                    $distanceInKm = $arrDayEndDetails[6] ?? 0;
                    // Convert 6 hours into Minutes
                    $requiredMin = 360;
                    if ($infraType == 7) {
                        if ($timeSpent >= $requiredMin && $distanceInKm >= 10) {
                            $qualified = '1';
                        } else {
                            $qualified = '0';
                        }
                    } elseif ($infraType == 10) {
                        $qualified = '1';
                    }

                    $endLt = $arrDayEndDetails[7] ?? "";
                    $endLg = $arrDayEndDetails[8] ?? "";

                    $arrExcelData[] = [
                        $row["district"],
                        $row["main_branch"],
                        $row["branch_name"],
                        $row["circle"],
                        $row["section"],
                        isset($infraType) ? $arrInfraType[$infraType] : "",
                        $teamId,
                        $mdoName,
                        $date,
                        $week,
                        $wdCode,
                        isset($arrWdDetails[0]) ? $arrWdDetails[0] : "",
                        isset($arrWdDetails[1]) ? $arrWdDetails[1] : "",
                        isset($arrWdDetails[2]) ? $arrWdDetails[2] : "",
                        $workingWith,
                        $dsId,
                        $dsType,
                        $dsNameOnly,
                        $qualified,
                        $timeSpent,
                        $distanceInKm,
                        $startLt,
                        $startLg,
                        currentDateTime($startTime, "H:i:s"),
                        $googleAddress,
                        $state,
                        $district,
                        $city,
                        $pinCode,
                        $endLt,
                        $endLg,
                        $endTime ? currentDateTime($endTime, "H:i:s") : "",
                        $endGoogleAddress,
                        $endState,
                        $endDistrict,
                        $endCity,
                        $endPinCode,
                    ];
                }
            }
        }

        $fileName = "Attendance_Report_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Pass complete data
        $sheet->fromArray($arrExcelData);
        foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $headerRow = '1';
        $styleHeader = $sheet->getStyle('A' . $headerRow . ':' . $sheet->getHighestDataColumn() . $headerRow);
        $styleHeader->getFont()->setBold(true);
        $styleHeader->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC000');
        $styleHeader->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);   // set border
        $styleHeader->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // center align text
        $styleHeader->getAlignment()->setVertical(Alignment::VERTICAL_CENTER); // center align text
        $styleHeader->getFill()->setFillType(Fill::FILL_SOLID);  // fill style
        $styleHeader->getFont()->getColor()->setARGB("FF000000"); // font color
        $styleHeader->getAlignment()->setWrapText(true);   // wrap text

        $allStyle = [
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())->applyFromArray($allStyle);

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);

        echo json_encode($arrMessage);
    }
}
