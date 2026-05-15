<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ActiveUsersReporting
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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.branch_name";
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
        $query = "select Distinct b.circle, c.circle_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.circle";
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
        $query = "select Distinct b.section, c.section_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.section";
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
        $query = "select Distinct b.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.wd_code";
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
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_market";
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
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_pop_group";
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.team_name IS NOT NULL AND b.team_name != '' AND b.s_id = '99' $where order by b.team_name";
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
        $dsTypeCond = "";
        if (isset($dsType) && $dsType != "" && $dsType >= 0) {
            if (!is_array($dsType)) {
                $dsType = [$dsType];
            }
            if (in_array('all', $dsType)) {
                $dsTypeCond = ""; // No condition for 'all'
            } else {
                $dsType = "'" . implode("','", $dsType) . "'";
                $dsTypeCond = " AND b.is_type IN ($dsType)";
            }
            $arrResult = [
                "teamList" => $this->getTeamsList($dsTypeCond),
            ];
        } else {
            $arrResult = [
                "teamList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    //SEARCH CONDITION
    private function getCondition($capDate = "a.rcd")
    {
        // filter query
        $searchCond = getFilterResult(
            $this->_data["searchbar"] ?? $this->_data,
            [
                // "dateFrom" => array($capDate, 4, "dateTo", true),
                "district" => ["b.district", 0, true, true],
                "branch" => ["a.branch_id", 0, true, true],
                "circle" => ["a.circle", 0, true, true],
                "section" => ["a.section", 0, true, true],
                "dsName" => ["a.team_id", 0, true, true],
                "wdCode" => ["a.wd_code", 0, true, true],
                "wdMarket" => ["c.wd_market", 0, true, true],
                "wdPopGroup" => ["c.wd_pop_group", 0, true, true],
                // "dsType" => array("a.is_type", ),
            ],
            $this->_dbConn
        );

        $teamType = getFormData($this->_data['searchbar']['dsType'] ?? $this->_data['dsType'] ?? "");
        if (isset($teamType) && $teamType != "" && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $searchCond .= " AND a.is_type IN ($teamTypes)";
                } else {
                    $searchCond .= " AND a.is_type = $teamType";
                }
            }
        }

        return $searchCond;
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
        $arrResult = [
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
            "sortOptions" => [
                ["label" => "DS Name", "value" => "a.team_name"],
                ["label" => "Branch Name", "value" => "b.branch_name"],
                ["label" => "Date Created - ASC", "value" => "a.rcd"],
            ],
            "viewHeader" => [
                "app.reporting.activeUSers.dsId",
                "app.reporting.activeUSers.dsName",
                "app.reporting.activeUSers.region",
                "app.reporting.activeUSers.branch",
                "app.reporting.activeUSers.circle",
                "Circle Name",
                "app.reporting.activeUSers.section",
                "Section Name",
                "app.reporting.activeUSers.wdCode",
                "WD Market",
                "WD Firm Name",
                "app.reporting.activeUSers.dsType",
                "app.team.add.status",
                "app.reporting.activeUSers.creationDate"
            ],
            "viewBody" => [
                "id",
                "dsName",
                "region",
                "branchName",
                "circle",
                "circleName",
                "section",
                "sectionName",
                "wdCode",
                "wdMarket",
                "wdFirmName",
                "dsType",
                "status",
                "creationDate",
            ],
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }
    //DS DETAILS
    final public function viewDSDetails()
    {
        $searchCondition = $this->getCondition();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd", $this->_data["sort"]);

        // filter by search query
        $where = "";

        // user has some specific permission
        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        // Calculate date range: from first day of 2 months ago to last day of previous month
        // Example: If current month is February, range is Dec 1 to Jan 31
        // Example: If current month is March, range is Jan 1 to Feb 28/29
        $firstDateLastToLastMonth = date('Y-m-01', strtotime('first day of -2 month'));
        $lastDatePreviousMonth = date('Y-m-t', strtotime('last day of -1 month'));

        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $types = [0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR"];
        $sQuery = "SELECT c.circle_name, c.section_name, c.wd_market, c.wd_firm_name, a.project_id, a.team_id, a.is_type, a.team_name, a.ds_number, a.circle, a.section, a.wd_code, a.rcd, b.branch_name, b.main_branch FROM $projectTeamTable AS a, $branchTable AS b, $wdMappingTable as c" .
            " WHERE a.dstatus = 0  AND a.branch_id = b.branch_id AND a.wd_code = c.wd_code AND a.s_id = '99' $searchCondition $where $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        // Initialize result array
        $arrResult = [];

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $teamId = $arrData["team_id"];
                $teamType = $arrData["is_type"];
                $mainBranch = $arrData["main_branch"];
                $creationDate = date("Y-m-d", strtotime($arrData["rcd"]));
                $istatus = "";

                $isQualified = getRowColumn(
                    $this->_dbConn,
                    "tblvands_summary",
                    "summary_id",
                    "dstatus = 0 AND team_id = '$teamId' AND is_qualified = 1 AND activity_date BETWEEN '$firstDateLastToLastMonth' AND '$lastDatePreviousMonth'"
                );
                //    print_r($isQualified);die;

                if ($isQualified > 0) {
                    $istatus = "Qualified";
                } else {
                    $istatus = "Not Qualified";
                }

                $arrResult[] = [
                    "id" => $teamId,
                    "dsName" => $arrData["team_name"],
                    "region" => $arrData["branch_name"],
                    "branchName" => $mainBranch,
                    "circle" => $arrData["circle"],
                    "circleName" => $arrData["circle_name"],
                    "section" => $arrData["section"],
                    "sectionName" => $arrData["section_name"],
                    "wdCode" => $arrData["wd_code"],
                    "wdMarket" => $arrData['wd_market'],
                    "wdFirmName" => $arrData['wd_firm_name'],
                    "dsType" =>  $types[$teamType],
                    "dsNum" =>  $arrData["ds_number"],
                    "status" => $istatus,
                    "creationDate" => $creationDate,
                ];
            }
        }

        $arrResult[] = ["total" => $limit["total"]];

        $arrMessage = responseMessage([], 1, ["data0" => $arrResult], true);
        echo json_encode($arrMessage);
    }

    final public function downloadDSDetails()
    {
        $dwnCond = $this->getCondition();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $cloudDBName = $GLOBALS["DB_DBNAME_CLOUD"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        // filter by search query
        $where = "";

        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        // user has some specific permission
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrBody = [];
        $sAction = null;
        $iRows = 0;
        $types = [0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR"];
        $sQuery = "SELECT c.circle_name, c.section_name, c.wd_market, c.wd_firm_name, a.project_id, a.team_id,a.is_type, a.team_name,a.ds_number,a.circle,a.section" .
            ", a.wd_code,a.rcd, b.district, b.branch_name,b.main_branch FROM $projectTeamTable AS a, $branchTable AS b, $wdMappingTable as c WHERE a.dstatus = 0 AND a.branch_id = b.branch_id AND a.s_id = '99'  AND a.wd_code = c.wd_code $where $dwnCond $sOrderCond";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $teamType = $arrData["is_type"];
                $creationDate = date("Y-m-d", strtotime($arrData["rcd"]));
                $arrBody[] = [
                    $arrData["team_id"],
                    $arrData["team_name"],
                    $arrData["ds_number"],
                    $arrData["district"],
                    $arrData["branch_name"],
                    $arrData["main_branch"],
                    $arrData["circle"],
                    $arrData["circle_name"],
                    $arrData["section"],
                    $arrData["section_name"],
                    $arrData["wd_code"],
                    $arrData["wd_market"],
                    $arrData["wd_firm_name"],
                    $types[$teamType],
                    $creationDate,
                ];
            }
            $header = ["DS ID", "DS Name", "DS Mob No.", "District", "Region", "Branch", "Circle", "Circle Name", "Section", "Section Name", "WD Code", "WD Market", "WD Firm Name", "DS Type", "Date of Creation"];
            $arrResult = formatDownloadData("DS_Details", [$header], $arrBody);
            $arrMessage = responseMessage([$GLOBALS['DWN_CSV_SUCCESS']], 1, $arrResult);
            echo json_encode($arrMessage);
        } else {
            $arrMessage = responseMessage([$GLOBALS['NO_RECORD_FOUND']], 0);
            echo json_encode($arrMessage);
        }
    }
}
