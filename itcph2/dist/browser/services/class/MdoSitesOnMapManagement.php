<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class MdoSitesOnMapManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];
    private $_projectId = 1;
    private $session;
    private $_iUserId = null;
    private $arrBranchwiseProducts = [];
    private $_teamAllCond = 0;

    public function __construct($dbConn, $data, $arrAccessInfo = array(), $iUserId = null, $teamAllCond = 0)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_iUserId = $iUserId;
        $this->_teamAllCond = $teamAllCond;
    }

    // final public function getSitesOnMapCondition($andCondition = true)
    // {
    //     $teamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
    //     $condition = "";
    //     // $branch = getFormData($this->_data["searchbar"], "branch");
    //     // if ($branch) {
    //     //     $matchAll = checkIfAllSelected($branch);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($branch)) {
    //     //             $branchIds = "'" . implode("','", $branch) . "'";
    //     //             $condition .= " AND branch_id IN ($branchIds)";
    //     //         } else {
    //     //             $condition .= " AND branch_id = $branch";
    //     //         }
    //     //     }
    //     // }
    //     // $circle = getFormData($this->_data["searchbar"], "circle");
    //     // if ($circle) {
    //     //     $matchAll = checkIfAllSelected($circle);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($circle)) {
    //     //             $circleIds = "'" . implode("','", $circle) . "'";
    //     //             $condition .= " AND circle IN ($circleIds)";
    //     //         } else {
    //     //             $condition .= " AND circle = '$circle'";
    //     //         }
    //     //     }
    //     // }
    //     // $section = getFormData($this->_data["searchbar"], "section");
    //     // if ($section) {
    //     //     $matchAll = checkIfAllSelected($section);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($section)) {
    //     //             $sectionIds = "'" . implode("','", $section) . "'";
    //     //             $condition .= " AND section IN ($sectionIds)";
    //     //         } else {
    //     //             $condition .= " AND section = '$section'";
    //     //         }
    //     //     }
    //     // }
    //     // $wdCode = getFormData($this->_data["searchbar"], "wdCode");
    //     // if ($wdCode) {
    //     //     $matchAll = checkIfAllSelected($wdCode);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($wdCode)) {
    //     //             $wdCodeIds = "'" . implode("','", $wdCode) . "'";
    //     //             $condition .= " AND wd_code IN ($wdCodeIds)";
    //     //         } else {
    //     //             $condition .= " AND wd_code = '$wdCode'";
    //     //         }
    //     //     }
    //     // }
    //     // $teamType = getFormData($this->_data["searchbar"], "dsType");
    //     // if ($teamType) {
    //     //     $matchAll = checkIfAllSelected($teamType);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($teamType)) {
    //     //             $teamTypes = "'" . implode("','", $teamType) . "'";
    //     //             $condition .= " AND is_type IN ($teamTypes)";
    //     //         } else {
    //     //             $condition .= " AND is_type = $teamType";
    //     //         }
    //     //     }
    //     // }
    //     $teamName = getFormData($this->_data["searchbar"], "dsName");
    //     if ($teamName) {
    //         $matchAll = checkIfAllSelected($teamName);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($teamName)) {
    //                 $teamNames = "'" . implode("','", $teamName) . "'";
    //                 $condition .= " AND team_id IN ($teamNames)";
    //             } else {
    //                 $condition .= " AND team_id = $teamName";
    //             }
    //         }
    //     }

    //     $clientList = $this->_arrAccessInfo["user_clients"];
    //     $projectList = $this->_arrAccessInfo["user_projects"];
    //     $teamList = $this->_arrAccessInfo["user_teams"];

    //     $where = "";
    //     // user has some specific permission
    //     if ($clientList) {
    //         $where = "AND a.client_id IN $clientList";
    //     }
    //     if ($projectList) {
    //         $where .= " AND a.project_id IN $projectList";
    //     }
    //     if ($teamList) {
    //         $where .= " AND a.team_id IN $teamList";
    //     }
    //     // if (isset($type) && $type != "" && $type >= 0) {
    //     //     $where .= " AND a.is_type = '$type'";
    //     // }

    //     $where = "";
    //     // $district = getFormData($this->_data["searchbar"], "district");
    //     // if ($district) {
    //     //     $matchAll = checkIfAllSelected($district);
    //     //     if (!$matchAll) {
    //     //         if (isNonEmptyArray($district)) {
    //     //             $districts = "'" . implode("','", $district) . "'";
    //     //             $where .= " AND c.district IN ($districts)";
    //     //         } else {
    //     //             $where .= " AND c.district = $district";
    //     //         }
    //     //     }
    //     // }
    //     if ($condition && $andCondition) {
    //         $where .= " AND a.team_id IN (SELECT team_id FROM $teamTable WHERE dstatus = '0' $condition)";
    //     } elseif ($condition) {
    //         $where .= " $condition";
    //     }

    //     // echo $where;die;
    //     return $where;
    // }



    final public function getSitesOnMapCondition($andCondition = true)
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

    final public function getSitesOnMapConditionForTeams($andCondition = true)
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
            $where .= " AND mdo_id IN (SELECT a.team_id FROM $teamTable as a, $branchTable as b, $mappingTable as c, tblmdo_wd_mapping AS d WHERE a.dstatus = '0' AND b.dstatus = '0' AND c.dstatus = '0' AND a.branch_id = b.branch_id AND a.team_id = d.mdo_id AND c.rec_id = d.wd_id $condition)";
        } elseif ($condition) {
            $where .= " AND mdo_id IN (SELECT a.ateam_id FROM $teamTable as a, $branchTable as b, $mappingTable as e, tblmdo_wd_mapping AS d WHERE a.dstatus = '0' AND b.dstatus = '0' AND c.dstatus = '0' AND a.branch_id = b.branch_id AND a.team_id = d.mdo_id AND c.rec_id = d.wd_id $condition)";
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND mdo_id IN $teamList";
        }

        return $where;
    }

    final public function getData($getAllOptionInTeam = false)
    {
        $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $user_id = $this->_iUserId;
        $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        if ($groupId == 1 || $groupId == 2) {
            $branchFilter = true;
        } else {
            $branchFilter = false;
        }
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
        }
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
            "branchFilter" => $branchFilter,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
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

    final public function getTeamsList($cond = "")
    {
        $arrData = array();
        if ($this->_teamAllCond == 2) {
            $arrData[] = array(
                "label" => "All",
                "value" => "all"
            );
        }
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
                "wdMarketList" => $this->getWdMarketList($wdCodeCond),
                "wdPopGroupList" => $this->getWdPopGroupList($wdCodeCond),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getLocationCoveredData()
    {
        global $ARR_TEAM_TYPES;
        $arrData = array();
        $arrData["columnSize"] = 12; // size of map column, values can be 1 to 12
        $arrData["repeatMapBy"] = 1; // times to repeat same map, values can be >=1
        $arrData["markers"] = array();
        $respTable = getRespTable(1, $this->_projectId);
        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $imagesTable = getImageTable();
        $no_img_path = $GLOBALS["LOGO_URL"];
        // Define available marker colors
        $greenFlag = "/green-flag.png";
        $redFlag = "/red-flag.png";
        // $redDot = "/red-dot.png";
        $greenDot = "/green-dot.png";
        // $orangeDot = "/orange-dot.png";

        // filter by search query and Condition
        $where = getFilterResult(
            $this->_data,
            array(
                "dateFrom" => array("a.capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );
        $where .= $this->getSitesOnMapCondition();

        // Take 1 attendance and 1 dayend location for each team for each day
        // Don't use b.dstatus = 0
        $sActionAtt = null;
        $iRowsAtt = 0;
        $sQueryAtt = "SELECT a.uni_id, a.mob_img_id, a.capture_datetime, a.capture_date, a.lt, a.lg,a.call_type, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM $attendanceTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0" .
            " AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND a.lt != 0 $where GROUP BY a.team_id, a.call_type, a.capture_date";
        $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

        $types = array(0 => "VAN DS", 1 => "Hybrid", 2 => "Town SWD");
        if ($iRowsAtt > 0) {
            while ($arDataAtt = $this->_dbConn->GetData($sActionAtt)) {
                $uniId = $arDataAtt["uni_id"];
                $mobImgId = $arDataAtt["mob_img_id"];
                $branchId = $arDataAtt["branch_id"];
                $callType = $arDataAtt["call_type"];
                $attDate = $arDataAtt["capture_date"];
                // Get Image
                $arrImage = getRowColumns($this->_dbConn, $imagesTable, "file_domain, file_path, file_name", "dstatus = 0 AND uni_id = '$uniId' AND mob_img_id = '$mobImgId'");
                if ($callType == 0) {
                    $flagColor = $greenFlag;
                    $attTime = "Attendance";
                } elseif ($callType == 1) {
                    $flagColor = $redFlag;
                    $attTime = "Day End";
                }
                $dsType = $arDataAtt["is_type"];
                $ARR_TEAM_TYPES[$dsType];
                $time = currentDateTime($arDataAtt["capture_datetime"], "h:i:s A");
                $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");
                if (isset($arrImage)) {
                    $imgPath = $arrImage[0] . constant("PRODS_ANY_FOLDER") . $arrImage[1];
                    $imgName = $arrImage[2];
                    $thumbImg = $imgPath . "thumb_" . $imgName;
                } else {
                    $imgPath = $no_img_path . "/";
                    $imgName = "no-image.jpg";
                    $thumbImg = $imgPath . $imgName;
                }
                $trackerDescription = "<div class='attendance-marker'><p class='attendance-label'>" . "<b>" . $attTime . "</b>" . "<br> Date - " . $attDate . "<br>" . "</p> <p class='attendance-label'>" . $arDataAtt["team_name"] . "<br>" . "Time - " . $time .
                    "<br> Branch - " . $branchName . "<br>" . "Circle - " . $arDataAtt["circle"] . "<br>" . "Section - " . $arDataAtt["section"] . "<br>" . "WDCode - " . $arDataAtt["wd_code"] . "<br>" . "DS Type - " . $ARR_TEAM_TYPES[$dsType] . "</p></div>";
                $arrData["markers"][] = array(
                    "date" => $arDataAtt["capture_datetime"],
                    "latitude" => $arDataAtt["lt"],
                    "longitude" => $arDataAtt["lg"],
                    "markerUrl" => $GLOBALS['MARKER_URL'] . $flagColor, // default icon is green
                    "markerTitle" => "", // text to display on hover of marker
                    "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                );
            }
        }

        // Take shops location for each day
        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.uni_id, a.capture_datetime, a.capture_date, a.lt, a.lg, a.ques_3, a.ques_6, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code  FROM $respTable AS a" .
            ", $projectTeamTable AS b, $branchTable AS c WHERE a.team_id = b.team_id AND b.branch_id = c.branch_id AND a.dstatus = 0  AND a.lt != 0 $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            // $i = 0;
            // $responseCount = $iRows;
            while ($arData = $this->_dbConn->GetData($sAction)) {
                // Assign Pins dynamically based on index
                // $icon = ($i == 0) ? $greenDot : (($i == $responseCount - 1) ? $redDot : $orangeDot);
                $uniId = $arData["uni_id"];
                $branchId = $arData["branch_id"];
                $mobImgId = $arData["ques_6"];
                $shopId = $arData["ques_3"];
                $respDate = $arData["capture_date"];
                $OutletName = getRowColumn($this->_dbConn, "tblroute_details", "outlet_name", "dstatus = 0 AND rec_id = '$shopId' ");
                $dsType = $arData["is_type"];
                $ARR_TEAM_TYPES[$dsType];
                $time = currentDateTime($arData["capture_datetime"], "h:i:s A");
                $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");
                // Get Image
                $arrImage = getRowColumns($this->_dbConn, $imagesTable, "file_domain, file_path, file_name", "dstatus = 0 AND uni_id = '$uniId' AND mob_img_id = '$mobImgId'");
                if (isset($arrImage)) {
                    $imgPath = $arrImage[0] . constant("PRODS_ANY_FOLDER") . $arrImage[1];
                    $imgName = $arrImage[2];
                    $thumbImg = $imgPath . "thumb_" . $imgName;
                } else {
                    $imgPath = $no_img_path . "/";
                    $imgName = "no-image.jpg";
                    $thumbImg = $imgPath . $imgName;
                }
                $trackerDescription = "<div class='attendance-marker'><p class='attendance-label'>" . "<b>" . " Outlet Name - " . $OutletName . "</b> <br>" . "Date - " . $respDate . "<br>" . "</p> <p class='attendance-label'>" . $arData["team_name"] . "<br>" . "Time - " . $time .
                    "<br> Branch - " . $branchName . "<br>" . "Circle - " . $arData["circle"] . "<br>" . "Section - " . $arData["section"] . "<br>" . "WDCode - " . $arData["wd_code"] . "<br>" . "DS Type - " . $ARR_TEAM_TYPES[$dsType] . "</p></div>";
                $arrData["markers"][] = array(
                    "date" => $arData["capture_datetime"],
                    "latitude" => $arData["lt"],
                    "longitude" => $arData["lg"],
                    "markerUrl" => $GLOBALS['MARKER_URL'] . $greenDot, //  Assigning Pin Colours
                    "markerTitle" => "", // text to display on click of marker
                    "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                );
                // $i++;
            }
        }

        if (isNonEmptyArray($arrData["markers"])) {
            usort($arrData["markers"], "sortArrayByDate");
            $arrMessage = responseMessage(array(), 1, $arrData, true);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }

    final public function getRouteTrackerData()
    {
        global $ARR_TEAM_TYPES;
        $arrData = array();
        $arrData["columnSize"] = 12;
        $arrData["repeatMapBy"] = 1;
        $arrData["markers"] = array();
        $respTable = "tblsurvey_response_details_mdo";
        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $imagesTable = getImageTable();
        $no_img_path = $GLOBALS["LOGO_URL"];

        // Define available marker colors
        $greenFlag = "/green-flag.png";
        $redFlag = "/red-flag.png";
        $redDot = "/red-dot.png";
        $greenDot = "/green-dot.png";
        $orangeDot = "/orange-dot.png";

        $where = $this->getSitesOnMapCondition();
        $date = getFormData($this->_data, "date");
        $date = $date ? currentDate(getValidDate($date)) : currentDate();
        if ($date) {
            $where .= " AND a.capture_date = '$date'";
        }
        // Attendance Data
        // Don't use b.dstatus = 0
        $sActionAtt = null;
        $iRowsAtt = 0;
        $sQueryAtt = "SELECT a.other_details, a.uni_id, a.mob_img_id, a.capture_datetime, a.lt, a.lg,a.call_type, b.team_id, b.team_name, b.is_type, b.branch_id,e.circle,e.section,e.wd_code FROM $attendanceTable AS a" .
            ", $projectTeamTable AS b, $branchTable AS c, tblmdo_wd_mapping as d, tblmapping_wd as e WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.team_id = d.mdo_id AND d.wd_id = e.rec_id AND a.lt != 0 $where GROUP BY a.team_id, a.call_type, a.capture_date";
        $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

        // $types = array(0 => "VAN DS", 1 => "Hybrid", 2 => "Town SWD");
        if ($iRowsAtt > 0) {
            while ($arDataAtt = $this->_dbConn->GetData($sActionAtt)) {
                $uniId = $arDataAtt["uni_id"];
                $mobImgId = $arDataAtt["mob_img_id"];
                $attbranchId = $arDataAtt["branch_id"];
                $callType = $arDataAtt["call_type"];
                $other_details = json_decode($arDataAtt["other_details"], true);
                $arrData['workingWith'] = "Route Follow - " . $other_details['workingWith'];
                if ($callType == 0) {
                    $flagColor = $greenFlag;
                    $attTime = "Attendance";
                } elseif ($callType == 1) {
                    $flagColor = $redFlag;
                    $attTime = "Day End";
                }
                $dsType = $arDataAtt["is_type"];
                $ARR_TEAM_TYPES[$dsType];
                $arrImage = getRowColumns($this->_dbConn, $imagesTable, "file_domain, file_path, file_name", "dstatus = 0 AND uni_id = '$uniId' AND mob_img_id = '$mobImgId'");
                if (isset($arrImage)) {
                    $imgPath = $arrImage[0] . constant("PRODS_ANY_FOLDER") . $arrImage[1];
                    $imgName = $arrImage[2];
                    $thumbImg = $imgPath . "thumb_" . $imgName;
                } else {
                    $imgPath = $no_img_path . "/";
                    $imgName = "no-image.jpg";
                    $thumbImg = $imgPath . $imgName;
                }
                $time = currentDateTime($arDataAtt["capture_datetime"], "h:i:s A");
                $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$attbranchId' ");

                $trackerDescription = "
                <div class='attendance-marker' style='font-size:12px;'>
                    <table border='1' cellspacing='0' cellpadding='2' style='border-collapse:collapse; font-size:8px;'>
                        <tr>
                            <th style='padding:2px 4px;'>Attendance Type</th>
                            <td style='padding:2px 4px;'><b>" . $attTime . "</b></td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Surveyor Name</th>
                            <td style='padding:2px 4px;'>" . $arDataAtt["team_name"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Time</th>
                            <td style='padding:2px 4px;'>" . $time . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Branch</th>
                            <td style='padding:2px 4px;'>" . $branchName . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Circle</th>
                            <td style='padding:2px 4px;'>" . $arDataAtt["circle"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Section</th>
                            <td style='padding:2px 4px;'>" . $arDataAtt["section"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>WDCode</th>
                            <td style='padding:2px 4px;'>" . $arDataAtt["wd_code"] . "</td>
                        </tr>
                    </table>
                </div>";

                $arrData["markers"][] = array(
                    "date" => $arDataAtt["capture_datetime"],
                    "latitude" => $arDataAtt["lt"],
                    "longitude" => $arDataAtt["lg"],
                    "markerUrl" => $GLOBALS['MARKER_URL'] . $flagColor, // default icon is green
                    "markerTitle" => "", // text to display on hover of marker
                    "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                );
            }
        }

        // Take shops location for each day
        // Don't use b.dstatus = 0
        $productSaleSum = 0;
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.type, a.uni_id, a.team_id, a.ques_4, a.ques_5, a.ques_6, a.ques_7, a.ques_8, a.ques_9, a.ques_11, a.capture_datetime, a.capture_date, a.lt, a.lg, b.team_id, b.team_name, b.branch_id, e.circle, e.section, e.wd_code, c.main_branch FROM $respTable AS a" .
            ", $projectTeamTable AS b, $branchTable AS c, tblmdo_wd_mapping as d, tblmapping_wd as e WHERE a.team_id = b.team_id AND b.team_id = d.mdo_id AND b.branch_id = c.branch_id AND d.wd_id = e.rec_id AND a.dstatus = 0 AND a.lt != 0 $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $i = 0;
            $shopCount = 1;
            $responseCount = $iRows;
            while ($arData = $this->_dbConn->GetData($sAction)) {
                // Assign Pins dynamically based on index
                $icon = ($i == 0) ? $greenDot : (($i == $responseCount - 1) ? $redDot : $orangeDot);
                $shopId = $arData["ques_4"];
                $ques_5 = $arData["ques_5"];
                $ques_6 = $arData["ques_6"];
                $ques_7 = $arData["ques_7"];
                $branchName = $arData["main_branch"];
                $condForImg = $arData["ques_8"];
                $type = $arData["type"];
                if ($condForImg == 'YES') {
                    $mobImgId = $arData["ques_9"];
                } else {
                    $mobImgId = $arData["ques_11"];
                }

                if ($type == 6 || $type == 8 || $type == 9) {
                    $outletDetails = getRowColumns($this->_dbConn, "tblroute_details_breeze", "outlet_name, shop_uniq_code", "dstatus = 0 AND rec_id = '$shopId' ");
                    $OutletName = $outletDetails[0];
                    $OutletID = $outletDetails[1];
                } else {
                    $outletDetails = getRowColumns($this->_dbConn, "tblroute_details", "outlet_name, shop_uniq_code", "dstatus = 0 AND rec_id = '$shopId' ");
                    $OutletName = $outletDetails[0];
                    $OutletID = $outletDetails[1];
                }


                $uniId = $arData["uni_id"];

                $date = $arData["capture_date"];

                $time = currentDateTime($arData["capture_datetime"], "h:i:s A");
                $arrImage = getRowColumns($this->_dbConn, $imagesTable, "file_domain, file_path, file_name", "dstatus = 0 AND uni_id = '$uniId' AND mob_img_id = '$mobImgId'");



                if (isset($arrImage)) {
                    $imgPath = $arrImage[0] . constant("PRODS_ANY_FOLDER") . $arrImage[1];
                    $imgName = $arrImage[2];
                    $thumbImg = $imgPath . "thumb_" . $imgName;
                } else {
                    $imgPath = $no_img_path . "/";
                    $imgName = "no-image.jpg";
                    $thumbImg = $imgPath . $imgName;
                }

                $trackerDescription = "
                <div class='attendance-marker' style='font-size:12px;'>
                    <table border='1' cellspacing='0' cellpadding='2' style='border-collapse:collapse; font-size:8px;'>
                        <tr>
                            <th style='padding:2px 4px;'>Outlet</th>
                            <td style='padding:2px 4px;'><b>" . $shopCount . " - " . $OutletName . "</b></td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Outlet ID</th>
                            <td style='padding:2px 4px;'><b>" . $OutletID . "</b></td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Surveyor Name</th>
                            <td style='padding:2px 4px;'>" . $arData["team_name"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Time</th>
                            <td style='padding:2px 4px;'>" . $time . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Branch</th>
                            <td style='padding:2px 4px;'>" . $branchName . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Circle</th>
                            <td style='padding:2px 4px;'>" . $arData["circle"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Section</th>
                            <td style='padding:2px 4px;'>" . $arData["section"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>WDCode</th>
                            <td style='padding:2px 4px;'>" . $arData["wd_code"] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Infra Type</th>
                            <td style='padding:2px 4px;'>" . $ARR_TEAM_TYPES[$dsType] . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Survey Volume</th>
                            <td style='padding:2px 4px;'>" . $ques_5 . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Survey Value</th>
                            <td style='padding:2px 4px;'>" . $ques_6 . "</td>
                        </tr>
                        <tr>
                            <th style='padding:2px 4px;'>Linecut</th>
                            <td style='padding:2px 4px;'>" . $ques_7 . "</td>
                        </tr>
                    </table>
                </div>";

                $arrData["markers"][] = array(
                    "date" => $arData["capture_datetime"],
                    "latitude" => $arData["lt"],
                    "longitude" => $arData["lg"],
                    "markerUrl" => $GLOBALS['MARKER_URL'] . $icon, //  Assigning Pin Colours
                    "markerTitle" => "", // text to display on click of marker
                    "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                );
                $i++;
                $shopCount++;
            }
        }


        if (isNonEmptyArray($arrData["markers"])) {
            usort($arrData["markers"], "sortArrayByDate");
            $arrMessage = responseMessage(array(), 1, $arrData, true);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }

    // To Get BranchWise Products
    private function getBranchWiseProducts($branchId = null, $productsList = true)
    {
        if ($branchId) {
            if ($productsList) {
                return isset($this->arrBranchwiseProducts[$branchId]) ?
                    $this->arrBranchwiseProducts[$branchId] : array();
            }
        } else {
            $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

            // Get distinct product names for each branch irrespective of Json id
            $sProductAction = null;
            $iProductRows = 0;
            $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0  ORDER BY product_name";
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $branchId = $rowProduct["branch_id"];

                    if (!isset($this->arrBranchwiseProducts[$branchId])) {
                        $this->arrBranchwiseProducts[$branchId] = array();
                    }
                    $this->arrBranchwiseProducts[$branchId][] = array($rowProduct["product_name"], $rowProduct["summary_column_name"]);
                }
            }
        }
    }

    final public function getUniverseData()
    {
        global $ARR_TEAM_TYPES;
        $arrData = array();
        $arrData["columnSize"] = 12; // size of map column, values can be 1 to 12
        $arrData["repeatMapBy"] = 1; // times to repeat same map, values can be >=1
        $arrData["markers"] = array();
        $respTable = "tblsurvey_response_details_mdo";
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        // Define available marker colors
        $redDot = "/red-dot.png";
        $greenDot = "/green-dot.png";
        $orangeDot = "/orange-dot.png";

        // // filter by search query and Condition
        $dateCond = getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("capture_date", 2, "dateTo"),
            ),
            $this->_dbConn
        );

        $where = $this->getSitesOnMapCondition();
        $where2 = $this->getSitesOnMapConditionForTeams();
        // echo "<pre>";
        // print_r($where);die;

        // Don't use b.dstatus = 0
        $sActionAtt = null;
        $iRowsAtt = 0;
        $sActionAtt2 = null;
        $iRowsAtt2 = 0;

        $sQueryAtt = "SELECT teams, is_type FROM tblmdo_access where dstatus = 0 $where2";
        $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);
        // echo $sQueryAtt;die;
        if ($iRowsAtt > 0) {
            while ($arDataAtt = $this->_dbConn->GetData($sActionAtt)) {
                // $mdoId = $arDataAtt['mdo_id'];
                $teams = $arDataAtt['teams'];
                $is_type = $arDataAtt['is_type'];

                if ($is_type == 6 || $is_type == 8 || $is_type == 9) {
                    $teamTable = "tblbreeze_team";
                    $routeTable = "tblroute_details_breeze";
                } else {
                    $teamTable = "tblproject_team";
                    $routeTable = "tblroute_details";
                }


                $sQueryAtt2 = "SELECT b.lt, b.lg, c.main_branch, b.rec_id, b.outlet_name, a.team_id, a.team_name, a.branch_id, d.circle, d.section, d.wd_code FROM $teamTable AS a, $routeTable as b, $branchTable AS c, tblmapping_wd as d" .
                    " WHERE a.team_id = b.team_id AND a.branch_id = c.branch_id AND a.wd_code = d.wd_code AND a.team_id = '$teams'";
                // echo $sQueryAtt2;die;
                $this->_dbConn->ExecuteSelectQuery($sQueryAtt2, $sActionAtt2, $iRowsAtt2);

                // // $types = array(0 => "VAN DS", 1 => "Hybrid", 2 => "Town SWD");
                if ($iRowsAtt2 > 0) {
                    while ($arDataAtt2 = $this->_dbConn->GetData($sActionAtt2)) {
                        $recId = $arDataAtt2["rec_id"];
                        $branchName = $arDataAtt2["main_branch"];
                        $dsType = $arDataAtt["is_type"];
                        $ARR_TEAM_TYPES[$dsType];
                        $shopDone = getRowColumn($this->_dbConn, $respTable, "pro_id", "dstatus = 0 AND ques_4 = '$recId' $dateCond");
                        if ($shopDone) {
                            // $isBilled = getRowColumn($this->_dbConn, $respTable, "pro_id", "dstatus = 0 AND ques_3 = '$recId' AND ques_4 = 'Yes' $dateCond");
                            // if ($isBilled) {
                            //     $flagColor = $greenDot;
                            // } else {
                            $flagColor = $orangeDot;
                            // }
                        } else {
                            $flagColor = $redDot;
                        }

                        $shopLastVisted = getRowColumn($this->_dbConn, $respTable, "MAX(capture_date)", "dstatus = 0 AND ques_4 = '$recId'");


                        $trackerDescription = "<div class='attendance-marker'>
                                <p class='attendance-label'>
                                DS ID - " . $arDataAtt2["team_id"] . "<br>
                                DS Name - " . $arDataAtt2["team_name"] . "<br>
                                </p>
                                <p class='attendance-label'>
                                    Outlet Name - " . $arDataAtt2["outlet_name"] . "<br>
                                    Branch - " . $branchName . "<br>
                                    Circle - " . $arDataAtt2["circle"] . "<br>
                                    Section - " . $arDataAtt2["section"] . "<br>
                                    WDCode - " . $arDataAtt2["wd_code"] . "<br>
                                    DS Type - " . $ARR_TEAM_TYPES[$dsType] . "<br>
                                    Outlet Last Visited - " . $shopLastVisted . "<br>
                                </p>
                                </div>";

                        $arrData["markers"][] = array(
                            "latitude" => $arDataAtt2["lt"],
                            "longitude" => $arDataAtt2["lg"],
                            "markerUrl" => $GLOBALS['MARKER_URL'] . $flagColor, // default icon is green
                            "markerTitle" => "", // text to display on hover of marker
                            "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                        );
                    }
                }
            }
        }


        if (isNonEmptyArray($arrData["markers"])) {
            $arrMessage = responseMessage(array(), 1, $arrData, true);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }
}
