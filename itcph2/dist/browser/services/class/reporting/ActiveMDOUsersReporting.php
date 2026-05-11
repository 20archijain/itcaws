<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

// phpcs:ignore
class ActiveMdoUsersReporting
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
    final public function getCondition()
    {
        $projectTeamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $breezeTeamTable = $GLOBALS['TABLES']['BREEZE_TEAM_TABLE'];
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $unionTeamQuery = "(SELECT team_id, team_name, branch_id, circle, section, wd_code, is_type, dstatus FROM $projectTeamTable WHERE dstatus = 0" .
            " UNION ALL SELECT team_id, team_name, branch_id, circle, section, wd_code, is_type, dstatus FROM $breezeTeamTable WHERE dstatus = 0)";
        $condition = "";
        $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
        if ($district) {
            $matchAll = checkIfAllSelected($district);
            if (!$matchAll) {
                if (isNonEmptyArray($district)) {
                    $districts = "'" . implode("','", $district) . "'";
                    $condition .= " AND a.district IN ($districts)";
                } else {
                    $condition .= " AND a.district = '$district'";
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
                    $condition .= " AND a.circle IN ($circleIds)";
                } else {
                    $condition .= " AND a.circle = '$circle'";
                }
            }
        }
        $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $sectionIds = "'" . implode("','", $section) . "'";
                    $condition .= " AND a.section IN ($sectionIds)";
                } else {
                    $condition .= " AND a.section = '$section'";
                }
            }
        }
        $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCodeIds = "'" . implode("','", $wdCode) . "'";
                    $condition .= " AND a.wd_code IN ($wdCodeIds)";
                } else {
                    $condition .= " AND a.wd_code = '$wdCode'";
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
                $condition .= " AND a.wd_market IN ($wdMarket)";
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
                $condition .= " AND a.wd_pop_group IN ($wdPopGroup)";
            }
        }

        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if (isset($teamType) && $teamType != "" && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $condition .= " AND c.is_type IN ($teamTypes)";
                }
            }
        }

        $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $condition .= " AND c.team_id IN ($dsNames)";
                } else {
                    $condition .= " AND c.team_id = $dsName";
                }
            }
        }

        $where = "";
        if ($condition) {
            $where .= " AND ma.teams IN (SELECT c.team_id FROM $unionTeamQuery AS c LEFT JOIN $branchTable AS d ON c.branch_id = d.branch_id" .
                " LEFT JOIN $mappingTable AS a ON c.wd_code = a.wd_code WHERE c.dstatus = 0 $condition)";
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND ma.mdo_id IN $teamList";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT a.district FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, dstatus FROM tblbreeze_team) AS b WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND d.teams = b.team_id AND a.branch_id = b.branch_id $where ORDER BY a.district";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT a.branch_name, a.main_branch, a.branch_id FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, dstatus FROM tblbreeze_team) AS b WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND d.teams = b.team_id AND a.branch_id = b.branch_id $where ORDER BY a.branch_name";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT b.circle, c.circle_name FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, circle, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, circle, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY b.circle";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT b.section, c.section_name FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, section, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, section, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY b.section";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT c.wd_code, c.wd_firm_name, c.wd_market FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY b.wd_code";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT c.wd_market, c.wd_market FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY c.wd_market";
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT c.wd_pop_group, c.wd_pop_group FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY c.wd_pop_group";
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
        global $ARR_TEAM_TYPES;
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT b.is_type FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, branch_id, wd_code, is_type, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, branch_id, wd_code, is_type, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.is_type IN (0, 6, 7, 8, 10) AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = isset($ARR_TEAM_TYPES[$row['is_type']]) ? $ARR_TEAM_TYPES[$row['is_type']] : (string) $row['is_type'];
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
            $where .= " AND d.mdo_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "SELECT DISTINCT b.team_name, b.team_id FROM tblmdo_access AS d, tblbranch AS a, (SELECT team_id, team_name, branch_id, wd_code, dstatus FROM tblproject_team" .
            " UNION ALL SELECT team_id, team_name, branch_id, wd_code, dstatus FROM tblbreeze_team) AS b, tblmapping_wd AS c WHERE d.dstatus = 0 AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.team_name IS NOT NULL AND b.team_name != '' AND d.teams = b.team_id AND a.branch_id = b.branch_id AND b.wd_code = c.wd_code $where ORDER BY b.team_name";
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
                    $wdCodeCond = " AND c.wd_code IN ($wdCode)";
                }
            }
            $arrResult = array(
                "teamList" => $this->getTeamsList($wdCodeCond),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // DS DATA
    final public function getViewDSData()
    {
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
        // $where = "";
        // $teamList = $this->_arrAccessInfo["user_teams"];
        // if ($teamList) {
        //     $where .= " AND team_id IN $teamList";
        // }
        $arrResult = array(
            "branchFilter" => true,
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
            "isSelectable" => false,
            "sortOptions" => array(
                array("label" => "DS Name", "value" => "c.team_name"),
                array("label" => "Branch Name", "value" => "a.branch"),
                array("label" => "Date Created - ASC", "value" => "c.rcd"),
            ),
            "viewHeader" => array(
                "app.reporting.activeUSers.branch",
                "app.reporting.activeUSers.region",
                "app.reporting.activeUSers.circle",
                "Circle Name",
                "app.reporting.activeUSers.section",
                "Section Name",
                "app.reporting.activeUSers.wdCode",
                "WD Market",
                "WD Firm Name",
                "AE Name",
                "AE Number",
                "MDO ID",
                "MDO Name",
                "app.reporting.activeUSers.dsId",
                "app.reporting.activeUSers.dsName",
                "app.reporting.activeUSers.dsType",
                // "app.reporting.activeUSers.creationDate"
            ),
            "viewBody" => array(
                "branchName",
                "region",
                "circle",
                "circleName",
                "section",
                "sectionName",
                "wdCode",
                "wdCodeName",
                "wdFirmName",
                "aeName",
                "aeNumber",
                "mdoId",
                "mdoName",
                "id",
                "dsName",
                "dsType",
                // "creationDate"
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    //DS DETAILS
    final public function viewDSDetails()
    {
        global $ARR_TEAM_TYPES;
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $breezeTeamTable = $this->_tables["BREEZE_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $arrResult = array();
        $where = "";
        $where = $this->getCondition();


        $accessAction = null;
        $accessRows = 0;
        $accessQuery = "SELECT ma.mdo_id, ma.teams, ma.is_type FROM tblmdo_access AS ma WHERE ma.dstatus = 0 AND ma.is_type IN (0, 6, 7, 8, 10) $where";
        $this->_dbConn->ExecuteSelectQuery($accessQuery, $accessAction, $accessRows);

        if ($accessRows > 0) {
            while ($accessData = $this->_dbConn->GetData($accessAction)) {
                $mdoTeamsId = trim($accessData["mdo_id"]);
                $teams = trim($accessData["teams"]);
                $teamType = (int) $accessData["is_type"];
                $mdoId = getRowColumn($this->_dbConn, $projectTeamTable, "team_id", "dstatus = 0 AND team_id = '$mdoTeamsId'");

                if ($mdoId === "" || $teams === "") {
                    continue;
                }
                if ($mdoId && !empty($mdoId)) {
                    $teamTable = ($teamType == 6 || $teamType == 8) ? $breezeTeamTable : $projectTeamTable;
                    $mdoName = getRowColumn($this->_dbConn, $projectTeamTable, "team_name", "dstatus = 0 AND team_id = '$mdoId'");

                    $arrTeamIds = explode(",", $teams);
                    foreach ($arrTeamIds as $dsId) {
                        $dsId = trim($dsId);
                        if ($dsId === "") {
                            continue;
                        }

                        $dsAction = null;
                        $dsRows = 0;
                        $dsQuery = "SELECT c.team_id, c.team_name, c.ds_number, c.rcd, c.branch_id, d.branch_name, d.main_branch, a.circle, a.circle_name, a.section, a.section_name, a.wd_code, a.wd_market, a.wd_firm_name" .
                            " FROM $teamTable AS c LEFT JOIN $branchTable AS d ON c.branch_id = d.branch_id LEFT JOIN $wdMappingTable AS a ON c.wd_code = a.wd_code" .
                            " WHERE c.dstatus = 0 AND c.team_id = '$dsId' AND c.dstatus = 0 LIMIT 1";
                        $this->_dbConn->ExecuteSelectQuery($dsQuery, $dsAction, $dsRows);

                        if ($dsRows > 0) {
                            $arrData = $this->_dbConn->GetData($dsAction);
                            $creationDate = $arrData["rcd"] ? date("Y-m-d", strtotime($arrData["rcd"])) : "";
                            $wdCode = $arrData["wd_code"];
                            $aeName = getRowColumn($this->_dbConn, $projectTeamTable, "ae_name", "wd_code = '$wdCode'");
                            $aeNumber = getRowColumn($this->_dbConn, $projectTeamTable, "ae_number", "wd_code = '$wdCode'");
                            $arrResult[] = array(
                                "id" => $arrData["team_id"],
                                "mdoId" => $mdoId,
                                "aeName" => $aeName,
                                "aeNumber" => $aeNumber,
                                "mdoName" => $mdoName ? $mdoName : "",
                                "dsName" => $arrData["team_name"],
                                "region" => $arrData["branch_name"],
                                "branchName" => $arrData["main_branch"],
                                "circle" => trim($arrData["circle"]),
                                "circleName" => $arrData["circle_name"],
                                "section" => trim($arrData["section"]),
                                "sectionName" => $arrData["section_name"],
                                "wdCode" => trim($arrData["wd_code"]),
                                "wdCodeName" => $arrData["wd_market"],
                                "wdFirmName" => $arrData["wd_firm_name"],
                                "dsType" => isset($ARR_TEAM_TYPES[$teamType]) ? $ARR_TEAM_TYPES[$teamType] : (string) $teamType,
                                "dsNum" => $arrData["ds_number"],
                                "creationDate" => $creationDate
                            );
                        }
                    }
                }
            }
        }

        $total = count($arrResult);
        $pageNo = (int) getFormData($this->_data, "page");
        if ($pageNo < 1) {
            $pageNo = (int) getFormData($this->_data, "pageNo");
        }
        $limit = (int) getFormData($this->_data, "limit");
        if ($pageNo < 1) {
            $pageNo = 1;
        }
        if ($limit < 1) {
            $limit = 10;
        }
        $start = ($pageNo - 1) * $limit;
        $arrResult = array_slice($arrResult, $start, $limit);
        $arrResult[] = array("total" => $total);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    private function getMdoNameMap()
    {
        $arrProjectMdoIds = array();
        $arrBreezeMdoIds = array();
        $arrMdoNames = array();

        $sAction = null;
        $iRows = 0;
        $query = "SELECT a.mdo_id, a.teams, a.is_type FROM tblmdo_access AS a WHERE a.dstatus = 0 AND a.is_type IN (0, 6, 7, 8, 10)";
        $this->_dbConn->ExecuteSelectQuery($query, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $mdoId = trim($arrData["mdo_id"]);
                $teamType = (int) $arrData["is_type"];
                if ($mdoId === "") {
                    continue;
                }

                if ($teamType == 6 || $teamType == 8) {
                    $arrBreezeMdoIds[$mdoId] = 1;
                } else {
                    $arrProjectMdoIds[$mdoId] = 1;
                }
            }
        }

        $arrProjectMdoIdList = array_keys($arrProjectMdoIds);
        if (isNonEmptyArray($arrProjectMdoIdList)) {
            $mdoIdStr = "'" . implode("','", $arrProjectMdoIdList) . "'";
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $projectAction = null;
            $projectRows = 0;
            $projectQuery = "SELECT team_id, team_name FROM $projectTeamTable WHERE dstatus = 0 AND team_id IN ($mdoIdStr)";
            $this->_dbConn->ExecuteSelectQuery($projectQuery, $projectAction, $projectRows);
            if ($projectRows > 0) {
                while ($projectData = $this->_dbConn->GetData($projectAction)) {
                    $arrMdoNames[$projectData["team_id"]] = $projectData["team_name"];
                }
            }
        }

        $arrBreezeMdoIdList = array_keys($arrBreezeMdoIds);
        if (isNonEmptyArray($arrBreezeMdoIdList)) {
            $mdoIdStr = "'" . implode("','", $arrBreezeMdoIdList) . "'";
            $breezeTeamTable = $this->_tables["BREEZE_TEAM_TABLE"];
            $breezeAction = null;
            $breezeRows = 0;
            $breezeQuery = "SELECT team_id, team_name FROM $breezeTeamTable WHERE dstatus = 0 AND team_id IN ($mdoIdStr)";
            $this->_dbConn->ExecuteSelectQuery($breezeQuery, $breezeAction, $breezeRows);
            if ($breezeRows > 0) {
                while ($breezeData = $this->_dbConn->GetData($breezeAction)) {
                    $arrMdoNames[$breezeData["team_id"]] = $breezeData["team_name"];
                }
            }
        }

        return $arrMdoNames;
    }


    final public function downloadDSDetails()
    {
        global $ARR_TEAM_TYPES;
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $breezeTeamTable = $this->_tables["BREEZE_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $arrBody = array();

        $accessAction = null;
        $accessRows = 0;
        $accessQuery = "SELECT ma.mdo_id, ma.teams, ma.is_type FROM tblmdo_access AS ma WHERE ma.dstatus = 0 AND ma.is_type IN (0, 6, 7, 8, 10)";
        $this->_dbConn->ExecuteSelectQuery($accessQuery, $accessAction, $accessRows);

        if ($accessRows > 0) {
            while ($accessData = $this->_dbConn->GetData($accessAction)) {
                $mdoTeamsId = trim($accessData["mdo_id"]);
                $teams = trim($accessData["teams"]);
                $teamType = (int) $accessData["is_type"];
                $mdoId = getRowColumn($this->_dbConn, $projectTeamTable, "team_id", "dstatus = 0 AND team_id = '$mdoTeamsId'");

                if ($mdoId === "" || $teams === "") {
                    continue;
                }

                if ($mdoId && !empty($mdoId)) {
                    $teamTable = ($teamType == 6 || $teamType == 8) ? $breezeTeamTable : $projectTeamTable;
                    $mdoName = getRowColumn($this->_dbConn, $projectTeamTable, "team_name", "dstatus = 0 AND team_id = '$mdoId'");

                    $arrTeamIds = explode(",", $teams);
                    foreach ($arrTeamIds as $dsId) {
                        $dsId = trim($dsId);
                        if ($dsId === "") {
                            continue;
                        }

                        $dsAction = null;
                        $dsRows = 0;
                        $dsQuery = "SELECT c.team_id, c.team_name, c.ds_number, c.rcd, d.branch_name, d.main_branch, a.district, a.circle, a.circle_name, a.section, a.section_name, a.wd_code, a.wd_market, a.wd_firm_name" .
                            " FROM $teamTable AS c LEFT JOIN $branchTable AS d ON c.branch_id = d.branch_id LEFT JOIN $wdMappingTable AS a ON c.wd_code = a.wd_code" .
                            " WHERE c.dstatus = 0 AND c.team_id = '$dsId' AND c.dstatus = 0  LIMIT 1";
                        $this->_dbConn->ExecuteSelectQuery($dsQuery, $dsAction, $dsRows);

                        if ($dsRows > 0) {
                            $arrData = $this->_dbConn->GetData($dsAction);
                            $creationDate = $arrData["rcd"] ? date("Y-m-d", strtotime($arrData["rcd"])) : "";
                            $wdCode = $arrData["wd_code"];
                            $aeName = getRowColumn($this->_dbConn, $projectTeamTable, "ae_name", "wd_code = '$wdCode'");
                            $aeNumber = getRowColumn($this->_dbConn, $projectTeamTable, "ae_number", "wd_code = '$wdCode'");
                            $arrBody[] = array(
                                $arrData["main_branch"],
                                $arrData["branch_name"],
                                trim($arrData["circle"]),
                                $arrData["circle_name"],
                                trim($arrData["section"]),
                                $arrData["section_name"],
                                trim($arrData["wd_code"]),
                                $arrData["wd_market"],
                                $arrData["wd_firm_name"],
                                $aeName,
                                $aeNumber,
                                $mdoId,
                                $mdoName ? $mdoName : "",
                                $arrData["team_id"],
                                $arrData["team_name"],
                                isset($ARR_TEAM_TYPES[$teamType]) ? $ARR_TEAM_TYPES[$teamType] : (string) $teamType,
                                // $creationDate,
                            );
                        }
                    }
                }
            }
            $header = array("Branch", "Region",  "Circle", "Circle Name", "Section", "Section Name", "WD Code", "WD Market", "WD Firm Name", "AE Name", "AE Number", "MDO ID", "MDO Name", "DS ID", "DS Name", "DS Type",);
            $arrResult = formatDownloadData("DS_Details", array($header), $arrBody);
            $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
            echo json_encode($arrMessage);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']), 0);
            echo json_encode($arrMessage);
        }
    }
}
