<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

// phpcs:ignore
class MasterDataDownload
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $projectId = 1;
    private $_arrAccessInfo = [];
    private $_iUserId = null;

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
    }

    // private function getCondition()
    // {
    //     $where = "";
    //     $branch = getFormData($this->_data, "branch");
    //     $type = getFormData($this->_data, "dsType");
    //     $team = getFormData($this->_data, "dsName");
    //     $circle = getFormData($this->_data, "circle");
    //     $section = getFormData($this->_data, "section");
    //     $wdCode = getFormData($this->_data, "wdCode");

    //     $teamList = $this->_arrAccessInfo["user_teams"];
    //     if ($teamList) {
    //         $where .= " AND a.team_id IN $teamList";
    //     }

    //     if ($branch) {
    //         $branch = getFormData($branch);
    //         $matchAll = checkIfAllSelected($branch);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($branch)) {
    //                 $branch = "'" . implode("','", $branch) . "'";
    //                 $where .= " AND c.branch_id IN ($branch)";
    //             } else {
    //                 $where .= " AND c.branch_id = '$branch'";
    //             }
    //         }
    //     }
    //     if ($circle) {
    //         $matchAll = checkIfAllSelected($circle);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($circle)) {
    //                 $circle = "'" . implode("','", $circle) . "'";
    //                 $where .= " AND b.circle IN ($circle)";
    //             } else {
    //                 $where .= " AND b.circle = '$circle'";
    //             }
    //         }
    //     }
    //     if ($section) {
    //         $matchAll = checkIfAllSelected($section);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($section)) {
    //                 $section = "'" . implode("','", $section) . "'";
    //                 $where .= " AND b.section IN ($section)";
    //             } else {
    //                 $where .= " AND b.section = '$section'";
    //             }
    //         }
    //     }
    //     if ($wdCode) {
    //         $matchAll = checkIfAllSelected($wdCode);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($wdCode)) {
    //                 $wdCode = "'" . implode("','", $wdCode) . "'";
    //                 $where .= " AND b.wd_code IN ($wdCode)";
    //             } else {
    //                 $where .= " AND b.wd_code = '$wdCode'";
    //             }
    //         }
    //     }
    //     if ($type) {
    //         $matchAll = checkIfAllSelected($type);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($type)) {
    //                 $type = "'" . implode("','", $type) . "'";
    //                 $where .= " AND b.is_type IN ($type)";
    //             } else {
    //                 $where .= " AND b.is_type = '$type'";
    //             }
    //         }
    //     }
    //     if ($team) {
    //         $team = getFormData($team);
    //         $matchAll = checkIfAllSelected($team);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($team)) {
    //                 $team = implode(",", $team);
    //                 $where .= " AND a.team_id IN ($team)";
    //             } else {
    //                 $where .= " AND a.team_id = $team";
    //             }
    //         }
    //     }

    //     return $where;
    // }

    //SEARCH CONDITION
    private function getCondition()
    {
        // filter query
        $searchCond = getFilterResult(
            $this->_data["searchbar"] ?? $this->_data,
            array(
                // "dateFrom" => array($capDate, 4, "dateTo", true),
                "district" => array("c.district", 0, true, true),
                "branch" => array("b.branch_id", 0, true, true),
                "circle" => array("b.circle", 0, true, true),
                "section" => array("b.section", 0, true, true),
                "dsName" => array("b.team_id", 0, true, true),
                "wdCode" => array("b.wd_code", 0, true, true),
                "wdMarket" => array("d.wd_market", 0, true, true),
                "wdPopGroup" => array("d.wd_pop_group", 0, true, true),
                // "dsType" => array("a.is_type", ),
            ),
            $this->_dbConn
        );

        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if (isset($teamType) && $teamType != "" && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $searchCond .= " AND b.is_type IN ($teamTypes)";
                } else {
                    $searchCond .= " AND b.is_type = $teamType";
                }
            }
        }

        return $searchCond;
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

    final public function getData()
    {
        // $rsAction = null;
        // $iRows = 0;
        // $arrBranch = [];
        // $project = 1;

        // $sAllowedProjects = $GLOBALS["arrAccessInfo"]["user_projects"];
        // $sAllowedTeams = $GLOBALS["arrAccessInfo"]["user_teams"];

        // $where = "";
        // if ($sAllowedProjects) {
        //     $where = " AND project_id IN $sAllowedProjects";
        // }
        // if ($project) {
        //     $where .= $where ? " AND project_id = $project" : " AND project_id = $project";
        // }
        // if ($sAllowedTeams) {
        //     $where .= $where ? " AND team_id IN $sAllowedTeams" : "team_id IN $sAllowedTeams";
        // }

        // $sQuery = "SELECT DISTINCT a.main_branch FROM tblbranch AS a, tblproject_team AS b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.branch_id = b.branch_id $where ORDER BY a.main_branch DESC";
        // $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        // if ($iRows > 0) {
        //     while ($row = $this->_dbConn->GetData($rsAction)) {
        //         $arrBranch[] = array(
        //             "label" => $row['main_branch'],
        //             "value" => $row['main_branch'],
        //         );
        //     }
        // }
        // $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        // $user_id = $this->_iUserId;
        // $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        // if ($groupId == 1) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } elseif ($groupId == 2) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = false;
        // }

        $arrResult = array(
            "branchFilter" => true,
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "teamType" => $this->getDsTypeList(),
            "teamList" => $this->getTeamsList(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
            "monthList" => getMonthList(),
            "yearList" => getYearList(),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getMasterData()
    {
        $where = $this->getCondition();
        $respTable = getRespTable(1, $this->projectId);

        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];

        // Get month and year from form data
        $month = getFormData($this->_data, "month");
        $month = $month ? $month : "";
        $year = getFormData($this->_data, "year");

        $arrHeader = array(
            "Rec Id",
            "DS Id",
            "DS Name",
            "District",
            "Branch",
            "Region",
            "Circle",
            "Section",
            "WD Code",
            "WD Town",
            "State",
            "District",
            "Sub District GOI",
            "Week Day",
            "Route Name",
            "Market Name",
            "GOI Market Id",
            "Outlet Name",
            "Outlet Mobile",
            "Goi Pop Group",
            "DS Sify Id",
            "DS Mobile",
            "Outlet Type",
            "Outlet Type",
            "Outlet ID",
            "KYC Done",
            "Lt",
            "Lg",
            "Outlet Last Visited",
            "Billing Status"
        );

        $captureDateCondition = "";
        if ($year && $month) {
            $firstDate = "$year-$month-01";
            $lastDate = date("Y-m-t", strtotime($firstDate)); // Gets the last date of the month
            $captureDateCondition = "capture_date BETWEEN '$firstDate' AND '$lastDate'";
            $arrHeader[] = "No of Times Visited";
            $arrHeader[] = "Billing Status";
        }

        // Create header array for CSV
        $header = [];
        $header[] = $arrHeader;

        $arrDataHolder = [];
        $iRows = 0;
        $rsAction = null;

        $partialQuery = "FROM $routeDetailsTable AS a, $projectTeamTable AS b, $branchTable AS c, $wdMappingTable as d WHERE a.team_id = b.team_id AND b.s_id = 99 AND b.branch_id = c.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.wd_code = d.wd_code $where";

        $sQuery = "SELECT DISTINCT a.rec_id, b.section, b.circle, a.wd_code, a.wd_town, a.state, a.district, a.sub_district_goi, a.route_name, a.market_name, a.goi_market_id, a.outlet_name, a.outlet_mobile, a.goi_pop_group" .
            ", a.ds_sify_id, a.ds_mobile, a.outlet_type, a.shop_type, a.shop_uniq_code, a.lt, a.lg, a.team_id, b.team_name, a.kyc_done, c.district, c.branch_name, c.main_branch $partialQuery ORDER BY a.capture_datetime DESC";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $route_name = $row["route_name"];
                $shopId = $row["rec_id"];
                $arrParts = explode('_', $route_name);
                $dayName = isset($arrParts[0]) ? $arrParts[0] : "";
                $kyc = (!empty($row["kyc_done"]) && $row["kyc_done"] == 1) ? "Yes" : "No";

                // shopId condition
                $cond = "AND ques_3 = '$shopId'";

                if ($captureDateCondition) {
                    $arrDataLastVisitedAndCountofVisited = getRowColumns(
                        $this->_dbConn,
                        $respTable,
                        "MAX(capture_date), COUNT(pro_id)",
                        "dstatus = 0 $cond AND $captureDateCondition"
                    );
                    $noOfTimesVisited = isset($arrDataLastVisitedAndCountofVisited[1]) ? $arrDataLastVisitedAndCountofVisited[1] : 0;
                } else {
                    $arrDataLastVisitedAndCountofVisited = getRowColumns(
                        $this->_dbConn,
                        $respTable,
                        "MAX(capture_date)",
                        "dstatus = 0 $cond"
                    );
                    $noOfTimesVisited = "";
                }

                // If no month selected → use current month
                if (!$month || !$year) {
                    $year = date("Y");
                    $month = date("m");
                }

                $firstDate = "$year-$month-01";
                $lastDate = date("Y-m-t", strtotime($firstDate));

                // Count inside selected/current month
                $billingCountArr = getRowColumns(
                    $this->_dbConn,
                    $respTable,
                    "COUNT(pro_id)",
                    "dstatus = 0 $cond AND capture_date BETWEEN '$firstDate' AND '$lastDate'"
                );
                $billingCount = isset($billingCountArr[0]) ? $billingCountArr[0] : 0;

                // Default blank
                $billingStatus = "";

                if ($billingCount > 0) {
                    $billingStatus = "Billed";
                } else {
                    // Check ANY visit Exist?
                    $anyVisit = getRowColumns($this->_dbConn, $respTable, "COUNT(pro_id)", "dstatus = 0 $cond");

                    if ($anyVisit[0] > 0) {
                        // Shop exists but no visit in selected month
                        $billingStatus = "Unbilled";
                    } else {
                        // Shop never visited → blank
                        $billingStatus = "";
                    }
                }

                $rowData = array(
                    $row["rec_id"],
                    $row["team_id"],
                    $row["team_name"],
                    $row["district"],
                    $row["main_branch"],
                    $row["branch_name"],
                    $row["circle"],
                    $row["section"],
                    $row["wd_code"],
                    $row["wd_town"],
                    $row["state"],
                    $row["district"],
                    $row["sub_district_goi"],
                    $dayName,
                    $route_name,
                    $row["market_name"],
                    $row["goi_market_id"],
                    $row["outlet_name"],
                    $row["outlet_mobile"],
                    $row["goi_pop_group"],
                    $row["ds_sify_id"],
                    $row["ds_mobile"],
                    $row["outlet_type"],
                    $row["shop_type"],
                    $row["shop_uniq_code"],
                    $kyc,
                    $row["lt"],
                    $row["lg"],
                    isset($arrDataLastVisitedAndCountofVisited[0]) ? $arrDataLastVisitedAndCountofVisited[0] : "",
                    $billingStatus
                );

                if ($captureDateCondition) {
                    $rowData[] = $noOfTimesVisited;
                    $rowData[] = $billingStatus;
                }

                $arrDataHolder[] = $rowData;
            }
        }

        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
        $fileName = "Route_Report_$currentDateTime.csv";

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

        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        echo json_encode($arrMessage);
    }
}
