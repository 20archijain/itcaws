<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

// phpcs:ignore
class MdoPerformanceReport
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
                $wdMarket = [$wdMarket];
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
                $wdPopGroup = [$wdPopGroup];
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
        } else {
            $condition .= " AND a.is_type IN (7,10)";
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
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];

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
                $arrData[] = [
                    "label" => $row['district'],
                    "value" => $row['district']
                ];
            }
        }

        return $arrData;
    }

    final public function getBranchList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all",
        ];

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
                $arrData[] = [
                    "label" => $row['branch_name'],
                    "value" => $row['branch_id'],
                    "mainBranch" => $row['main_branch']
                ];
            }
        }

        return $arrData;
    }

    final public function getCircleList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['circle'] . " - " . $row['circle_name'],
                    "value" => $row['circle']
                ];
            }
        }

        return $arrData;
    }

    final public function getSectionList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['section'] . " - " . $row['section_name'],
                    "value" => $row['section']
                ];
            }
        }

        return $arrData;
    }

    final public function getWdCodeList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['wd_code'] . ' - ' . $row['wd_market'] . ' - ' . $row['wd_firm_name'],
                    "value" => $row['wd_code']
                ];
            }
        }

        return $arrData;
    }

    final public function getWdMarketList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['wd_market'],
                    "value" => $row['wd_market']
                ];
            }
        }

        return $arrData;
    }

    final public function getWdPopGroupList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['wd_pop_group'],
                    "value" => $row['wd_pop_group']
                ];
            }
        }

        return $arrData;
    }

    final public function getDsTypeList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $teamType,
                    "value" => $row['is_type']
                ];
            }
        }

        return $arrData;
    }

    final public function getTeamsList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
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
                $arrData[] = [
                    "label" => $row['team_name'],
                    "value" => $row['team_id']
                ];
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
        // if ($groupId == 1 && $groupId == 2) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // }
        $arrResult = [
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
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getBranch($district = "district")
    {
        $district = $this->_data['district'];
        $districtCond = "";
        if (!empty($district)) {
            if (!is_array($district)) {
                $district = [$district];
            }
            if (in_array('all', $district)) {
                $districtCond = ""; // No condition for 'all'
            } else {
                $district = "'" . implode("','", $district) . "'";
                $districtCond = " AND a.district IN ($district)";
            }

            $arrResult = [
                "branchList" => $this->getBranchList($districtCond),
                "circleList" => $this->getCircleList($districtCond),
                "sectionList" => $this->getSectionList($districtCond),
                "wdCodeList" => $this->getWdCodeList($districtCond),
                "teamType" => $this->getDsTypeList($districtCond),
                "teamList" => $this->getTeamsList($districtCond),
                "wdMarketList" => $this->getWdMarketList($districtCond),
                "wdPopGroupList" => $this->getWdPopGroupList($districtCond),
            ];
        } else {
            $arrResult = [
                "branchList" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            ];
        }
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCircle($branch = "branch_id")
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if ($branch) {
            if (!is_array($branch)) {
                $branch = [$branch];
            }
            if (in_array('all', $branch)) {
                $branchCond = ""; // No condition for 'all'
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $branchCond = " AND a.branch_id IN ($branch)";
            }

            $arrResult = [
                "circleList" => $this->getCircleList($branchCond),
                "sectionList" => $this->getSectionList($branchCond),
                "wdCodeList" => $this->getWdCodeList($branchCond),
                "teamType" => $this->getDsTypeList($branchCond),
                "teamList" => $this->getTeamsList($branchCond),
                "wdMarketList" => $this->getWdMarketList($branchCond),
                "wdPopGroupList" => $this->getWdPopGroupList($branchCond),
            ];
        } else {
            $arrResult = [
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            ];
        }
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection($circle = "circle")
    {
        $circle = $this->_data['circle'];
        $circleCond = "";
        if ($circle) {
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = [$circle];
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND b.circle IN ($circle)";
                }
            }
            $arrResult = [
                "sectionList" => $this->getSectionList($circleCond),
                "wdCodeList" => $this->getWdCodeList($circleCond),
                "teamType" => $this->getDsTypeList($circleCond),
                "teamList" => $this->getTeamsList($circleCond),
                "wdMarketList" => $this->getWdMarketList($circleCond),
                "wdPopGroupList" => $this->getWdPopGroupList($circleCond),
            ];
        } else {
            $arrResult = [
                "teamType" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getWDCode($section = "section")
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if ($section) {
            if ($section) {
                if (!is_array($section)) {
                    $section = [$section];
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND b.section IN ($section)";
                }
            }

            $arrResult = [
                "wdCodeList" => $this->getWdCodeList($sectionCond),
                "teamType" => $this->getDsTypeList($sectionCond),
                "teamList" => $this->getTeamsList($sectionCond),
                "wdMarketList" => $this->getWdMarketList($sectionCond),
                "wdPopGroupList" => $this->getWdPopGroupList($sectionCond),
            ];
        } else {
            $arrResult = [
                "teamType" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $wdCode = $this->_data['wdCode'];
        $wdCodeCond = "";
        if ($wdCode) {
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = [$wdCode];
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND b.wd_code IN ($wdCode)";
                }
            }
            $arrResult = [
                "teamType" => $this->getDsTypeList($wdCodeCond),
                "teamList" => $this->getTeamsList($wdCodeCond),
            ];
        } else {
            $arrResult = [
                "teamType" => "",
                "teamList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamList()
    {
        $dsType = $this->_data['dsType'];
        $typeCond = "";
        if ($dsType) {
            if ($dsType) {
                if (!is_array($dsType)) {
                    $dsType = [$dsType];
                }
                if (in_array('all', $dsType)) {
                    $typeCond = ""; // No condition for 'all'
                } else {
                    $dsType = "'" . implode("','", $dsType) . "'";
                    $typeCond = " AND b.is_type IN ($dsType)";
                }
            }
            $arrResult = [
                "teamList" => $this->getTeamsList($typeCond),
            ];
        } else {
            $arrResult = [
                "teamList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getDownloadData()
    {
        global $UPLOAD_URL;
        $arrTeamType = [0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS"];
        $arrInfraType = [7 => "MDO", 10 => "FSO"];
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // filter query
        $where = $this->getCondition();
        $where2 = "";
        $where2 = getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            [
                "dateFrom" => ["capture_date", 2, "dateTo"],
            ],
            $this->_dbConn
        );

        $dateFrom = isset($this->_data["searchbar"]['dateFrom']) ? $this->_data["searchbar"]['dateFrom'] : $this->_data['dateFrom'];
        $dateTo = isset($this->_data["searchbar"]['dateTo']) ? $this->_data["searchbar"]['dateTo'] : $this->_data['dateTo'];

        $dateFrom = sprintf('%04d-%02d-%02d', $dateFrom['year'], $dateFrom['month'], $dateFrom['day']);
        $dateTo = sprintf('%04d-%02d-%02d', $dateTo['year'], $dateTo['month'], $dateTo['day']);

        // Convert date strings to DateTime objects
        $startDate = $dateFrom;
        $endDate = $dateTo;
        $begin = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end = $end->modify('+1 day'); // include end date

        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($begin, $interval, $end);

        $arrDates = [];
        foreach ($daterange as $date) {
            $arrDates[] = $date->format("Y-m-d");
        }

        $dates = "'" . implode("','", $arrDates) . "'";

        $branch = getFormData($this->_data, "branch");

        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $respTable = $this->_tables["RESPONSE_DETAILS_TABLE"];

        // Create header
        $header = [];
        $header[] = [
            "MDO Name",
            "DS Type",
            "DS Name",
            "Unacompanied Sales/Day",
            "Accompanied Sales/Day",
        ];

        $arrDataHolder = [];

        // Loop through each branch data
        foreach ($branch as $branchId) {
            $branchCond = "";
            if ($branchId) {
                $matchAll = checkIfAllSelected($branchId);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchId)) {
                        $branchIds = implode(",", $branchId);
                        $branchCond = " AND branch_id IN ($branchIds)";
                    } else {
                        $branchCond = " AND branch_id = $branchId";
                    }
                }
            }

            $rsAction = null;
            $iRows = 0;
            $sQuery = "SELECT a.team_id, a.team_name, b.teams, b.is_type FROM $projectTeamTable as a, tblmdo_access AS b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.team_id = b.mdo_id $where";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $mdoId = $row["team_id"];
                    $mdoName = $row["team_name"];
                    $dsId = $row["teams"];
                    $dsType = $row["is_type"];

                    if ($dsType == 6 || $dsType == 8 || $dsType == 9) {
                        $teamTable = "tblbreeze_team";
                    } else {
                        $teamTable = $projectTeamTable;
                    }

                    $arrTeamDeatils = getRowColumns(
                        $this->_dbConn,
                        $teamTable,
                        "team_name, team_id",
                        "team_id = '$dsId' AND dstatus = 0"
                    );

                    $allBrandCols = getRowsColumns($this->_dbConn, $branchPickupStockTable, "summary_column_name, product_name", "dstatus = 0 $branchCond", [], true);
                    $productCols = [];
                    $productNames = [];

                    foreach ($allBrandCols as $colRow) {
                        $productCols[] = $colRow[0];
                        $productNames[] = $colRow[1];
                    }
                    $summaryColumns = implode(") + SUM(", $productCols);
                    $sumColumns = "SUM($summaryColumns)";
                    $totalSale = 0;

                    $dsName = isset($arrTeamDeatils[0]) ? $arrTeamDeatils[0] : "";
                    $dsId = isset($arrTeamDeatils[1]) ? $arrTeamDeatils[1] : "";

                    // 1️⃣ Get the accompanied sale (only one record per ds_id)
                    $sQueryAcc = "SELECT total_sale, capture_date FROM tblmdo_summary WHERE ds_id = '$dsId' AND dstatus = 0 AND capture_date IN ($dates)";
                    $rsAcc = null;
                    $iRowsAcc = 0;
                    $this->_dbConn->ExecuteSelectQuery($sQueryAcc, $rsAcc, $iRowsAcc);
                    $acompaniedSale = 0;
                    $acompaniedDate = null;

                    if ($iRowsAcc > 0) {
                        while ($rowAcc = $this->_dbConn->GetData($rsAcc)) {
                            $acompaniedSale = $rowAcc['total_sale'] ?? 0;
                            $acompaniedDate = $rowAcc['capture_date'];
                        };
                    }

                    // 2️⃣ Determine unaccompanied dates (exclude accompanied date)
                    $arrUnaccompaniedDates = [];
                    foreach ($arrDates as $d) {
                        if ($d !== $acompaniedDate) {
                            $arrUnaccompaniedDates[] = $d;
                        }
                    }

                    $unaccompaniedDatesStr = "'" . implode("','", $arrUnaccompaniedDates) . "'";
                    $unaccompaniedDays = count($arrUnaccompaniedDates);
                    $acompaniedDays = $acompaniedDate ? 1 : 0; // only one record per DS

                    // 3️⃣ Get unaccompanied sale from response table
                    $unacompaniedSale = 0;
                    if (!empty($arrUnaccompaniedDates)) {
                        if ($dsType == 6 && $dsType == 8 && $dsType == 9) {
                            // Get the unaccompanied sale (only one record per RMD, STOKIEST, FMCG DS)
                            $sQueryUnacc = "SELECT total_sale FROM tblbreeze_response_data WHERE dstatus = 0 AND team_id = '$dsId' AND capture_date IN($unaccompaniedDatesStr)";
                            $rsUnacc = null;
                            $iRowsUnacc = 0;
                            $this->_dbConn->ExecuteSelectQuery($sQueryUnacc, $rsUnacc, $iRowsUnacc);
                            if ($iRowsUnacc > 0) {
                                while ($rowUnacc = $this->_dbConn->GetData($rsUnacc)) {
                                    $unacompaniedSale = $rowUnacc['total_sale'] ?? 0;
                                };
                            }
                        } else {
                            $sQueryUnacc = "SELECT $sumColumns AS totalSum FROM $respTable WHERE dstatus = 0 AND team_id = '$dsId' AND capture_date IN($unaccompaniedDatesStr)";
                            $rsUnacc = null;
                            $iRowsUnacc = 0;
                            $this->_dbConn->ExecuteSelectQuery($sQueryUnacc, $rsUnacc, $iRowsUnacc);
                            if ($iRowsUnacc > 0) {
                                while ($rowUnacc = $this->_dbConn->GetData($rsUnacc)) {
                                    $unacompaniedSale = $rowUnacc['totalSum'] ?? 0;
                                };
                            }
                        }
                    }

                    // 4️⃣ Calculate per-day averages
                    $acompaniedSalePerDay = $acompaniedDays > 0 ? ($acompaniedSale / $acompaniedDays) : 0;
                    $unacompaniedSalePerDay = $unaccompaniedDays > 0 ? ($unacompaniedSale / $unaccompaniedDays) : 0;

                    // 5️⃣ Add into data holder array
                    $arrDataHolder[] =  [
                        $mdoName,
                        isset($arrTeamType[$dsType]) ? $arrTeamType[$dsType] : "",
                        $dsName,
                        round($unacompaniedSalePerDay, 2),
                        round($acompaniedSalePerDay, 2),
                    ];
                }
            }
        }

        $fileName = "MDO_Performance_Report_$currentDateTime.csv";
        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        $fp = fopen($filename, 'w');

        if ($fp === false) {
            $arrMessage = responseMessage(["Failed to create CSV file"], 0);
            echo json_encode($arrMessage);
            return;
        }

        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($header as $headerRow) {
            $cleanRow = array_map('cleanCSVValue', $headerRow);
            fputs($fp, implode(",", $cleanRow) . "\n");
        }

        foreach ($arrDataHolder as $row) {
            $cleanRow = array_map('cleanCSVValue', $row);
            fputs($fp, implode(",", $cleanRow) . "\n");
        }

        fclose($fp);

        $fileDetails = [
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        ];

        $arrMessage = responseMessage([$GLOBALS['FILE_DOWNLOADING']], 1, $fileDetails);
        echo json_encode($arrMessage);
    }
}
