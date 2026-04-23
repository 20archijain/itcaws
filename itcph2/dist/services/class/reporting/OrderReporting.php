<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class OrderReporting
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
        $userBranch = "";

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

        $respTable = getRespTable(1, $this->_projectId);
        $orderDetailsTable = "tblsurvey_response_details_orders";
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

        $partialQuery = "FROM $orderDetailsTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id $where";

        // Don't use b.dstatus = 0 AND c.dstatus = 0
        $arrData = array();
        $rsAction = null;
        $iRows = 0;
        // use a.pro_id > 0 to include primary column as index while calculating no of rows
        $sQuery = "SELECT a.pro_id $partialQuery AND a.pro_id > 0";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);

        $sQuery = "SELECT a.pro_id, a.uni_id, a.team_id, a.s_id, a.capture_datetime, a.lt, a.lg, a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, a.ques_10, b.team_id, b.team_name" .
            ", b.circle, b.section, b.branch_id, b.wd_code, c.branch_name $partialQuery ORDER BY capture_datetime DESC";
        $sQuery .= " " . $limit["limit"];
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $i = 0;
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $proId = $row["pro_id"];
                $deliveryDate = getRowColumn($this->_dbConn, 'tblsurvey_response_details_delivery', "capture_datetime", " pro_id = $proId");
                $deliveryTime = currentDateTime($deliveryDate, "d-m-Y h:i:s A") ?? '';
                $branchId = $row["branch_id"];
                $shopId = $row["ques_3"];
                $shopDetails = is_numeric($shopId) ? getRowColumns($this->_dbConn, $routeDetailsTable, "shop_type, outlet_name, outlet_mobile", "rec_id = $shopId") : array("", "");
                $shopType = $shopDetails[0];
                $shopName = isset($shopDetails[1]) ? removeSpecialCharFromString($shopDetails[1]) : "";
                $mobileNumber = $shopDetails[2];
                $sellIinOrder = $row["ques_4"];
                $route = json_decode($row["ques_1"], true)[0];
                $wdCode = $row["wd_code"];
                $timeStamp = currentDateTime($row["capture_datetime"], "d-m-Y h:i:s A");
                $team_id = $row["team_id"];
                $team_name = $row["team_name"];
                $branch_name = $row["branch_name"];
                $circle = $row["circle"];
                $section = $row["section"];
                $lt = $row["lt"];
                $lg = $row["lg"];

                $arrData[$i] = array(
                    "pro_id" => $proId,
                    "wdCode" => $wdCode,
                    "route" => $route,
                    "shopName" => $shopName,
                    "mobileNumber" => $mobileNumber,
                    "shopType" => $shopType,
                    "sellIinOrder" => $sellIinOrder,
                    "timestamp" => $timeStamp,
                    "team_id" => $team_id,
                    "team" => $team_name,
                    "branchName" => $branch_name,
                    "circle" => $circle,
                    "section" => $section,
                    "lt" => $lt,
                    "lg" => $lg,
                    "deliveryTime" => $deliveryTime,
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

        $branch = getFormData($this->_data['searchbar'], "branch");
        $teamType = getFormData($this->_data['searchbar'], "dsType");
        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }

        $orderDetailsTable = "tblsurvey_response_details_orders";
        $deliveryDetailsTable = "tblsurvey_response_details_delivery";
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        $arrBaseDetails = [
            "Order Id",
            "Date",
            "Week",
            "Timestamp",
            "Lt",
            "Lg",
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "WD Code",
            "DS ID",
            "DS Type",
            "DS Name",
            "Route",
            "Outlet Name",
            "Owner Mobile Number",
            "Outlet Type",
            "Delivery Time",
        ];

        $arrAllProducts = array();
        $arrDownloadData = array();

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

            // Get dynamic product list from tblbranch_pickupstock_products
            $rsProducts = null;
            $iProductRows = 0;
            $productQuery = "SELECT DISTINCT p.product_name, p.summary_column_name, p.sort_order
                FROM tblbranch_pickupstock_products p
                INNER JOIN $projectTeamTable b ON p.branch_id = b.branch_id
                    AND p.team_type = b.is_type
                    AND p.json_id = b.s_id
                WHERE p.dstatus = 0 $branchCond
                ORDER BY p.sort_order";

            $this->_dbConn->ExecuteSelectQuery($productQuery, $rsProducts, $iProductRows);

            $arrProductList = array();
            if ($iProductRows > 0) {
                while ($prodRow = $this->_dbConn->GetData($rsProducts)) {
                    $productName = strtoupper($prodRow["product_name"]);
                    $columnName = $prodRow["summary_column_name"];

                    if (!isset($arrAllProducts[$productName])) {
                        $arrAllProducts[$productName] = array(
                            'column' => $columnName,
                            'sort_order' => $prodRow["sort_order"]
                        );
                    }

                    $arrProductList[$productName] = $columnName;
                }
            }

            $sProductColumns = "";
            if (!empty($arrProductList)) {
                foreach ($arrProductList as $productName => $columnName) {
                    $sProductColumns .= ", a.$columnName";
                }
            }

            // Main query to get order data from tblsurvey_response_details_orders
            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.pro_id, a.capture_date, a.capture_datetime, a.lt, a.lg,
           a.ques_0, a.ques_1, a.ques_2, a.ques_3, a.ques_4,
           b.team_id, b.team_name, b.branch_id, b.is_type, b.circle, b.section,
           b.wd_code, c.district, c.branch_name, c.main_branch $sProductColumns
           FROM $orderDetailsTable AS a
           INNER JOIN $projectTeamTable AS b ON a.team_id = b.team_id
           INNER JOIN $branchTable AS c ON b.branch_id = c.branch_id
           WHERE a.dstatus = 0 AND b.s_id = '99' AND a.pro_id > 0
           $where $branchCond
           ORDER BY a.capture_datetime DESC";

            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                $allOrderIds = [];
                $allShopIds = [];
                $orderData = [];

                $tempIndex = 0;
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $allOrderIds[] = $row["pro_id"];

                    $shopId = $row["ques_3"];
                    if (is_numeric($shopId)) {
                        $allShopIds[] = $shopId;
                    }
                    $ques1Decoded = json_decode($row["ques_1"], true);
                    $row['_ques_1_decoded'] = isset($ques1Decoded[0]) ? $ques1Decoded[0] : '';

                    $orderData[$tempIndex] = $row;
                    $tempIndex++;
                }

                $shopCache = $this->getBatchShopDetails($allShopIds, $branchId);

                $deliveryCache = array();
                if (!empty($allOrderIds)) {
                    $orderIdsStr = implode(",", $allOrderIds);
                    $columnNames = array_values($arrProductList);
                    $selectColumns = !empty($columnNames) ? implode(", ", $columnNames) : "";

                    $rsDelivery = null;
                    $iDeliveryRows = 0;
                    $sDeliveryQuery = "SELECT order_id, pro_id, capture_datetime" .
                        (!empty($selectColumns) ? ", $selectColumns" : "") . "
                          FROM $deliveryDetailsTable
                          WHERE order_id IN ($orderIdsStr) AND dstatus = 0
                          ORDER BY capture_datetime DESC";

                    $this->_dbConn->ExecuteSelectQuery($sDeliveryQuery, $rsDelivery, $iDeliveryRows);

                    if ($iDeliveryRows > 0) {
                        while ($delRow = $this->_dbConn->GetData($rsDelivery)) {
                            $orderId = $delRow["order_id"];
                            if (!isset($deliveryCache[$orderId])) {
                                $deliveryCache[$orderId] = $delRow;
                            }
                        }
                    }
                }

                // Process each row
                foreach ($orderData as $row) {
                    $proId = $row["pro_id"];
                    $currentBranchId = $row["branch_id"];
                    $captureDate = $row["capture_date"];
                    $week = $this->getWeekNumber($captureDate);

                    $shopId = $row["ques_3"];
                    if (is_numeric($shopId) && isset($shopCache[$shopId])) {
                        $shopDetails = $shopCache[$shopId];
                    } else {
                        $shopDetails = ["", "", "", "", ""];
                    }

                    $shopName = isset($shopDetails[0]) ? $shopDetails[0] : "";
                    $shopUniqCode = isset($shopDetails[1]) ? $shopDetails[1] : "";
                    $mobileNumber = isset($shopDetails[2]) ? $shopDetails[2] : "";
                    $shopType = isset($shopDetails[4]) ? $shopDetails[4] : "";

                    $deliveryTime = "";
                    if (isset($deliveryCache[$proId]) && isset($deliveryCache[$proId]['capture_datetime'])) {
                        $deliveryTime = currentDateTime($deliveryCache[$proId]['capture_datetime'], "d-m-Y h:i:s A");
                    }

                    $rowData = array();
                    $rowData['base'] = array();
                    $rowData['base'][] = $proId;
                    $rowData['base'][] = $captureDate;
                    $rowData['base'][] = $week;
                    $rowData['base'][] = currentDateTime($row["capture_datetime"], "d-m-Y h:i:s A");
                    $rowData['base'][] = $row["lt"];
                    $rowData['base'][] = $row["lg"];
                    $rowData['base'][] = $row["district"];
                    $rowData['base'][] = $row["main_branch"];
                    $rowData['base'][] = $row["branch_name"];
                    $rowData['base'][] = $row["circle"];
                    $rowData['base'][] = $row["section"];
                    $rowData['base'][] = $row["wd_code"];
                    $rowData['base'][] = $row["team_id"];
                    $rowData['base'][] = $row["is_type"] != "" ? $arrTeamType[$row["is_type"]] : "";
                    $rowData['base'][] = $row["team_name"];
                    $rowData['base'][] = htmlspecialchars_decode($row['_ques_1_decoded']); // Route
                    $rowData['base'][] = htmlspecialchars_decode($shopName);
                    $rowData['base'][] = $mobileNumber;
                    $rowData['base'][] = $shopType;
                    $rowData['base'][] = $deliveryTime;

                    $rowData['products'] = array();

                    foreach ($arrProductList as $productName => $columnName) {
                        $orderedQty = 0;
                        if (isset($row[$columnName])) {
                            $orderedQty = floatval($row[$columnName]);
                        }

                        $deliveredQty = 0;
                        if (isset($deliveryCache[$proId]) && isset($deliveryCache[$proId][$columnName])) {
                            $deliveredQty = floatval($deliveryCache[$proId][$columnName]);
                        }

                        $rowData['products'][$productName] = array(
                            'ordered' => $orderedQty,
                            'delivered' => $deliveredQty
                        );
                    }

                    $arrDownloadData[] = $rowData;
                }
            }
        }

        // Sort products by sort_order
        uasort($arrAllProducts, function ($a, $b) {
            return $a['sort_order'] - $b['sort_order'];
        });

        // BUILD TWO-ROW HEADER STRUCTURE
        $arrHeaderRow1 = array();
        $arrHeaderRow2 = array();

        foreach ($arrBaseDetails as $detailHeader) {
            $arrHeaderRow1[] = "";
            $arrHeaderRow2[] = $detailHeader;
        }

        $productCount = count($arrAllProducts);
        if ($productCount > 0) {
            $isFirst = true;
            foreach ($arrAllProducts as $productName => $productInfo) {
                if ($isFirst) {
                    $arrHeaderRow1[] = "Order";
                    $isFirst = false;
                } else {
                    $arrHeaderRow1[] = "";
                }
                $arrHeaderRow2[] = $productName;
            }

            $isFirst = true;
            foreach ($arrAllProducts as $productName => $productInfo) {
                if ($isFirst) {
                    $arrHeaderRow1[] = "Delivery";
                    $isFirst = false;
                } else {
                    $arrHeaderRow1[] = "";
                }
                $arrHeaderRow2[] = $productName;
            }
        }

        if (!empty($arrDownloadData)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $baseColCount = count($arrBaseDetails);

            // Calculate column letters for merged headers
            $orderStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($baseColCount + 1);
            $orderEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($baseColCount + $productCount);
            $deliveryStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($baseColCount + $productCount + 1);
            $deliveryEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($baseColCount + ($productCount * 2));

            $mergedHeaders = [
                ['range' => $orderStartCol . '1:' . $orderEndCol . '1', 'title' => 'Order Details'],
                ['range' => $deliveryStartCol . '1:' . $deliveryEndCol . '1', 'title' => 'Delivery Details']
            ];

            foreach ($mergedHeaders as $header) {
                $sheet->mergeCells($header['range']);
                $sheet->setCellValue(explode(':', $header['range'])[0], $header['title']);

                $style = $sheet->getStyle($header['range']);
                $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            foreach ($arrHeaderRow2 as $key => $header) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($key + 1) . '2';
                $sheet->setCellValue($cell, $header);
            }

            // Fill data starting from Row 3
            foreach ($arrDownloadData as $rowIndex => $rowData) {
                $row = $rowIndex + 3;
                $columnKey = 0;

                // Write base data
                foreach ($rowData['base'] as $value) {
                    $sheet->setCellValueByColumnAndRow($columnKey + 1, $row, $value);
                    $columnKey++;
                }

                // Write ORDERED products
                foreach ($arrAllProducts as $productName => $productInfo) {
                    if (isset($rowData['products'][$productName])) {
                        $sheet->setCellValueByColumnAndRow($columnKey + 1, $row, $rowData['products'][$productName]['ordered']);
                    } else {
                        $sheet->setCellValueByColumnAndRow($columnKey + 1, $row, '');
                    }
                    $columnKey++;
                }

                // Write DELIVERED products
                foreach ($arrAllProducts as $productName => $productInfo) {
                    if (isset($rowData['products'][$productName])) {
                        $sheet->setCellValueByColumnAndRow($columnKey + 1, $row, $rowData['products'][$productName]['delivered']);
                    } else {
                        $sheet->setCellValueByColumnAndRow($columnKey + 1, $row, '');
                    }
                    $columnKey++;
                }
            }

            // Auto-size columns
            foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Center align all cells
            $allStyle = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())
                ->applyFromArray($allStyle);

            // Save the spreadsheet
            $fileName = "Order_Report_$currentDateTime.xlsx";
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
            } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
                $arrMessage = responseMessage(array("Error saving spreadsheet: " . $e->getMessage()), 0);
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }
        echo json_encode($arrMessage);
    }


    public function getDeliveredData()
    {
        $orderDetailsTable = "tblsurvey_response_details_orders";
        $deliveryDetailsTable = "tblsurvey_response_details_delivery";
        $responseOrderId = getFormData($this->_data, 'orderId');

        $rsAction = null;
        $iActionRows = 0;
        $rsAction1 = null;
        $iActionRows1 = 0;
        $rsAction2 = null;
        $iActionRows2 = 0;
        $arrPro = array();

        $team_id = getRowColumn($this->_dbConn, 'tblsurvey_response_details_orders', "team_id", " pro_id = $responseOrderId");
        $teamDetails = getRowColumns($this->_dbConn, 'tblproject_team', "is_type, s_id, branch_id", " team_id = $team_id");
        $is_type = $teamDetails[0];
        $json_id = $teamDetails[1];
        $branch_id = $teamDetails[2];

        // Get product details from tblbranch_pickupstock_products
        $query = "SELECT rec_id, product_name, summary_column_name, category_name, sort_order FROM tblbranch_pickupstock_products WHERE branch_id = ? AND json_id = ? AND team_type = ? AND dstatus = 0 ORDER BY sort_order";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows, array($branch_id, $json_id, $is_type));

        if ($iActionRows > 0) {
            $arrProducts = array();
            $arrSummaryColumnNames = array();

            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrProducts[] = array(
                    "category_used_in_app" => $row["category_name"],
                    "sku_details" => $row["product_name"],
                    "sort_order" => $row["sort_order"],
                    "ordered_qty" => 0,
                    "delivered_qty" => 0,
                );
                $arrSummaryColumnNames[] = $row["summary_column_name"];
            }

            // Get order details from tblsurvey_response_details_orders
            $sOrderQuery = "SELECT ques_3, pro_id, capture_datetime, order_status, " . implode(", ", $arrSummaryColumnNames) .
                " FROM $orderDetailsTable WHERE team_id = ? AND dstatus = 0";

            $queryParams = array($team_id);

            if ($responseOrderId !== null) {
                $sOrderQuery .= " AND pro_id = ?";
                $queryParams[] = $responseOrderId;
            }

            // $sOrderQuery .= " ORDER BY capture_datetime DESC LIMIT 1";

            $this->_dbConn->ExecuteSelectQuery($sOrderQuery, $rsAction1, $iActionRows1, $queryParams);

            if ($iActionRows1 > 0) {
                $row1 = $this->_dbConn->GetData($rsAction1);
                $orderIdValue = (int)$row1["pro_id"];
                $order_status = (int)$row1["order_status"];
                $shopId = (int)$row1["ques_3"];
                $orderDateFormatted = date("Y-m-d H:i", strtotime($row1["capture_datetime"]));

                // Get delivery details from tblsurvey_response_details_delivery
                $sDeliveryQuery = "SELECT ques_3, pro_id, capture_datetime," . implode(", ", $arrSummaryColumnNames) .
                    " FROM $deliveryDetailsTable WHERE order_id = ? AND dstatus = 0";

                $queryParams = array($responseOrderId);
                $sDeliveryQuery .= " ORDER BY capture_datetime DESC LIMIT 1";
                $this->_dbConn->ExecuteSelectQuery($sDeliveryQuery, $rsAction2, $iActionRows2, $queryParams);

                if ($iActionRows2 > 0) {
                    $row2 = $this->_dbConn->GetData($rsAction2);
                    $orderIdValue = (int)$row2["pro_id"];
                    $shopId = (int)$row2["ques_3"];
                    $orderDateFormatted = date("Y-m-d H:i", strtotime($row2["capture_datetime"]));
                }

                foreach ($arrProducts as $index => $arrProduct) {
                    $colName = $arrSummaryColumnNames[$index];
                    $orderedQty = isset($row1[$colName]) ? (float)$row1[$colName] : 0;
                    $arrProducts[$index]["ordered_qty"] = (int)$orderedQty;
                    $delivered_qty = isset($row2[$colName]) ? (float)$row2[$colName] : 0;
                    $arrProducts[$index]["delivered_qty"] = (int)$delivered_qty;
                }

                // Filter products to only include those with ordered_qty > 0
                foreach ($arrProducts as $arrProduct) {
                    if ($arrProduct["ordered_qty"] > 0) {
                        $arrPro[] = $arrProduct;
                    }
                }
                // print_r($arrPro);
                // die;
            }
        }

        $arrResponse = array(
            "deliveredData" => $arrPro,
        );

        $arrMessage = responseMessage(array(), 1, $arrResponse, true);
        echo json_encode($arrMessage);
    }
}
