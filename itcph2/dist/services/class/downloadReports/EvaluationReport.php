<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// phpcs:ignore
class EvaluationReport
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
        if (isset($teamType) && $teamType != "" && $teamType >= 0 && isNonEmptyArray($teamType)) {
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
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b, tblbranch_pickupstock_products as c where a.branch_id = b.branch_id AND a.branch_id = c.branch_id AND b.is_type = c.team_type AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = "";
                if ($row['is_type'] == 0) {
                    $teamType = "DS";
                } elseif ($row['is_type'] == 1) {
                    $teamType = "Niche";
                } elseif ($row['is_type'] == 2) {
                    $teamType = "Town SWD";
                } elseif ($row['is_type'] == 3) {
                    $teamType = "Hybrid";
                } elseif ($row['is_type'] == 4) {
                    $teamType = "SCP";
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
            // "showTransactionDownloadBtn" => true,
            // "showSummaryDownloadBtn" => true,
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

    private function getBranches()
    {
        return getBranchList($this->_dbConn, false, "", "", 0, true);
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

    private function getTypes()
    {
        return getRowsColumn($this->_dbConn, "tblteams_types", "team_type");
    }

    private function getBranchWiseProducts($branchId = null, $teamType = null)
    {
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        if ($branchId && $teamType !== null && $teamType !== "") {
            return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
        }
        if ($branchId && ($teamType === null || $teamType === "")) {
            return $this->arrBranchwiseProducts[$branchId] ?? [];
        }

        if (!$branchId) {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = getRowsColumns(
                    $this->_dbConn,
                    $branchProductsTable,
                    "branch_id, product_name, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' AND is_focusbrand = '1' ORDER BY product_name",
                    array(),
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $productName = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->arrBranchwiseProducts[$branchId][$teamType])) {
                        $this->arrBranchwiseProducts[$branchId][$teamType] = [];
                    }
                    $this->arrBranchwiseProducts[$branchId][$teamType][] = [$productName, $summaryColumnName];
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT DISTINCT branch_id, team_type, product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 AND is_focusbrand = '1' ORDER BY branch_id, team_type, product_name";
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];
                        $teamType = $rowProduct["team_type"];

                        if (!isset($this->arrBranchwiseProducts[$branchId])) {
                            $this->arrBranchwiseProducts[$branchId] = [];
                        }
                        if (!isset($this->arrBranchwiseProducts[$branchId][$teamType])) {
                            $this->arrBranchwiseProducts[$branchId][$teamType] = [];
                        }
                        $this->arrBranchwiseProducts[$branchId][$teamType][] = [$rowProduct["product_name"], $rowProduct["summary_column_name"]];
                    }
                }
            }
        }

        if ($branchId && $teamType !== null && $teamType !== "") {
            return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
        } elseif ($branchId) {
            return $this->arrBranchwiseProducts[$branchId] ?? [];
        } else {
            return $this->arrBranchwiseProducts ?? [];
        }
    }

    final public function getDownloadEvaluationReport()
    {
        global $ARR_TEAM_TYPES;
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $where = "";
        $dateCond = "";
        $dateCond2 = "";
        $where .= $this->getCondition(true);
        $dateCond .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("a.activity_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );
        $dateCond2 .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $branch = getFormData($this->_data['searchbar'], "branch");
        $teamType = getFormData($this->_data['searchbar'], "dsType");
        if (checkIfAllSelected($teamType) || empty($teamType)) {
            $teamType = $this->getTypes();
        }
        $billed = getFormData($this->_data['searchbar'], "billed");
        $billedVal = isNonEmpty($billed) ? $billed : 0;
        if (checkIfAllSelected($branch)) {
            $branch = $this->getBranches();
        }

        $arrSummary = array(
            "sale" => array(
                array(), // header
            ),
        );

        // Create header
        $arrSummary["sale"][0] = [
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "WD Code",
            "WD POP Group",
            "WD Firm",
            "WD Market",
            "DS ID",
            "DS Name",
            "DS Type",
            "Evaluation Criteria",
            "Variant Name",
            "Total Actual Conversions",
            "Total Possible Conversions",
            "Conversion Rate",
        ];

        $this->getBranchWiseProducts();

        foreach ($branch as $branchId) {
            foreach ($teamType as $currentTeamType) {
                $branchCond = "";
                if ($branchId) {
                    $matchAll = checkIfAllSelected($branchId);
                    if (!$matchAll) {
                        $branchIds = isNonEmptyArray($branchId) ? implode(",", $branchId) : $branchId;
                        $branchCond = isNonEmptyArray($branchId) ? " AND a.branch_id IN ($branchIds)" : " AND a.branch_id = $branchId";
                    }
                }

                $arrProductBought = $this->getBranchWiseProducts($branchId, $currentTeamType);
                $sProductSaleColumns = "";
                if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                    foreach ($arrProductBought as $arrProduct) {
                        $productName = strtoupper($arrProduct[0]);
                        $productColumnName = $arrProduct[1];
                        $sProductSaleColumns .= ", SUM(a.$productColumnName) AS $productColumnName";
                    }
                }
                $teamTypeWhere = $where;
                $sQuery = "SELECT a.route, a.activity_date, a.total_sellin_shops, a.total_sales_deliveries, b.team_id, b.team_name, b.is_type, b.circle, b.section, b.wd_code $sProductSaleColumns FROM $summaryTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.s_id = '99' AND b.branch_id = $branchId AND b.is_type = '$currentTeamType' $dateCond $teamTypeWhere GROUP BY a.team_id ORDER BY b.team_name";
                $rsAction = null;
                $iRows = 0;
                $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
                $rsRoutes = null;

                if ($iRows > 0) {
                    $index = count($arrSummary["sale"]);
                    $arrBranchDetails = getRowsColumns($this->_dbConn, $branchTable, "branch_id, branch_name, main_branch, district");

                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        $routeName = $row["route"];
                        $teamId = $row["team_id"];
                        // $sellInShops = $row["total_sales_deliveries"];
                        $activity_date = $row["activity_date"];
                        $routeQuery = "SELECT  a.route FROM $summaryTable as a WHERE a.dstatus = 0 AND a.team_id = $teamId $dateCond";
                        $this->_dbConn->ExecuteSelectQuery($routeQuery, $rsRoutes, $iRows);

                        $totalPlannedOutlet = 0;
                        if ($iRows > 0) {
                            while ($routeRow = $this->_dbConn->GetData($rsRoutes)) {
                                $route = $routeRow["route"];
                                $plannedOutletCount = getRowColumn(
                                    $this->_dbConn,
                                    $routeTable,
                                    "COUNT(shop_uniq_code)",
                                    "dstatus = '0' AND route_name = '$route' AND team_id = $teamId"
                                );
                                $totalPlannedOutlet += intval($plannedOutletCount);
                            }
                        }

                        $date = $row["activity_date"];
                        $wdCode = $row["wd_code"];
                        $mainBranch = $branchName = $district = "";
                        if (($branchIndex = array_search($branchId, array_column($arrBranchDetails, 0))) !== false) {
                            $branchName = $arrBranchDetails[$branchIndex][1];
                            $mainBranch = $arrBranchDetails[$branchIndex][2];
                            $district = $arrBranchDetails[$branchIndex][3];
                        }

                        $orderShop = getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 IN ('Outlet Order','Outlet Survey') AND dstatus = '0' $dateCond2 AND team_id = $teamId");
                        $addShop = getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_0 = 'Add Outlet' AND dstatus = '0' $dateCond2 AND team_id = $teamId");
                        $sellInShops = getRowColumn($this->_dbConn, $respTable, "COUNT(DISTINCT ques_3)", "ques_4 = 'Yes' AND dstatus = '0' $dateCond2 AND team_id = $teamId");

                        $wdDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "wd_pop_group, wd_firm_name, wd_market", "wd_code = '$wdCode' AND dstatus = '0'");
                        $wdPopGroup = $wdDetails[0] ?? '';
                        $wdFirmName = $wdDetails[1] ?? '';
                        $wdMarket = $wdDetails[2] ?? '';
                        $scoreBilled = ($sellInShops > 0 && $totalPlannedOutlet > 0) ? round(($sellInShops / $totalPlannedOutlet), 4) : 0;
                        $totalShops = $orderShop + $addShop;
                        $scoreVisit = ($totalShops > 0 && $totalPlannedOutlet > 0) ? round(($totalShops / $totalPlannedOutlet), 4) : 0;

                        // Common row data
                        $rowData = [
                            $district,
                            $mainBranch,
                            $branchName,
                            $row["circle"],
                            $row["section"],
                            $row["wd_code"],
                            $wdPopGroup,
                            $wdFirmName,
                            $wdMarket,
                            $teamId,
                            $row["team_name"],
                            $row["is_type"] != "" ? $ARR_TEAM_TYPES[$row["is_type"]] : ""
                        ];

                        // Add rows for each evaluation criteria
                        $arrSummary["sale"][$index] = array_merge($rowData, ["Overall Visit", "", $totalShops, $totalPlannedOutlet, $scoreVisit]);
                        $index++;
                        $arrSummary["sale"][$index] = array_merge($rowData, ["Overall Billed", "", $sellInShops, $totalPlannedOutlet, $scoreBilled]);
                        $index++;

                        // Focus Brand Billed - ALWAYS show if there are focus products for this team type
                        if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                            $focusBrandBilled = 0;
                            $focusOutletCount = 0;
                            $productSumParts = [];
                            foreach ($arrProductBought as $arrProduct) {
                                $productColumnName = $arrProduct[1];
                                $productSumParts[] = "IFNULL($productColumnName, 0)";
                            }
                            $productSumExpr = implode(" + ", $productSumParts);

                            // Get outlet count where total sum of products > $billedVal
                            $focusOutletCount = getRowColumn(
                                $this->_dbConn,
                                $respTable,
                                "COUNT(DISTINCT ques_3)",
                                "ques_0 IN ('Outlet Order','Outlet Survey') AND ($productSumExpr) > $billedVal AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId"
                            );

                            // Optionally calculate total focus brand billed in PHP (optional if needed)
                            $focusBrandBilled = 0;
                            foreach ($arrProductBought as $arrProduct) {
                                $productColumnName = $arrProduct[1];
                                $iSale = floatval($row[$productColumnName] ?? 0);
                                if ($iSale > $billedVal) {
                                    $focusBrandBilled += $iSale;
                                }
                            }

                            // Focus Score
                            $focusScore = ($focusOutletCount > 0 && $totalPlannedOutlet > 0) ? round(($focusOutletCount / $totalPlannedOutlet), 4) : 0;

                            // Add to summary
                            $arrSummary["sale"][$index] = array_merge($rowData, ["Focus Brand Billed", "", $focusOutletCount, $totalPlannedOutlet, $focusScore]);

                            $index++;

                            $proCount = 1;
                            foreach ($arrProductBought as $arrProduct) {
                                $productName = strtoupper($arrProduct[0]);
                                $productColumnName = $arrProduct[1];
                                $iSale = floatval($row[$productColumnName] ?? 0);
                                $outletCount = 0;
                                $productScore = 0;
                                if ($iSale > $billedVal) {
                                    $outletCount = getRowColumn(
                                        $this->_dbConn,
                                        $respTable,
                                        "COUNT(DISTINCT ques_3)",
                                        "ques_0 IN ('Outlet Order','Outlet Survey') AND $productColumnName > $billedVal AND dstatus = '0' AND capture_date = '$date' AND team_id = $teamId"
                                    );
                                    $productScore = ($outletCount > 0 && $totalPlannedOutlet > 0) ? round(($outletCount / $totalPlannedOutlet), 4) : 0;
                                }

                                // Always add product row (even if 0 sales) - SHOW PRODUCT NAME
                                $arrSummary["sale"][$index] = array_merge($rowData, ["Focus Brand $proCount Billed", $productName, $outletCount, $totalPlannedOutlet, $productScore]);
                                $proCount++;
                                $index++;
                            }
                        } else {
                            // If no focus products defined for this team type, still show a placeholder
                            $arrSummary["sale"][$index] = array_merge($rowData, ["Focus Brand Billed", "No Focus Products", 0, $totalPlannedOutlet, 0]);
                            $index++;
                        }
                    }
                }
            }
        }

        $fileName = "CONVERSION_RATE_REPORT_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrSummary["sale"]);
        $sheet->getStyle("A1:Q1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:Q1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

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
