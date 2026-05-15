<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class DashboardManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_tables = [];
    private $_arrAccessInfo = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_dbConn = $dbConn;
        $this->_data = $data;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_iUserId = $iUserId;

        // get branch wise teams
        // $this->getBranchWiseTeams();
    }

    final public function getCondition($andCondition = true)
    {
        $teamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $condition = "";
        $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
        if ($district) {
            if (!is_array($district)) {
                $district = [$district];
            }
            if (in_array('all', $district)) {
                $condition .= " ";
            } else {
                $district = "'" . implode("','", $district) . "'";
                $condition .= " AND d.district IN ($district)";
            }
        }
        $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
        if ($branch) {
            if (!is_array($branch)) {
                $branch = [$branch];
            }
            if (in_array('all', $branch)) {
                $condition .= " ";
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $condition .= " AND d.branch_id IN ($branch)";
            }
        }
        $circle = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "circle");
        if ($circle) {
            if (!is_array($circle)) {
                $circle = [$circle];
            }
            if (in_array('all', $circle)) {
                $condition .= " ";
            } else {
                $circle = "'" . implode("','", $circle) . "'";
                $condition .= " AND b.circle IN ($circle)";
            }
        }
        $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
        if ($section) {
            if (!is_array($section)) {
                $section = [$section];
            }
            if (in_array('all', $section)) {
                $condition .= " ";
            } else {
                $section = "'" . implode("','", $section) . "'";
                $condition .= " AND b.section IN ($section)";
            }
        }
        $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
        if ($wdCode) {
            if (!is_array($wdCode)) {
                $wdCode = [$wdCode];
            }
            if (in_array('all', $wdCode)) {
                $condition .= " ";
            } else {
                $wdCode = "'" . implode("','", $wdCode) . "'";
                $condition .= " AND b.wd_code IN ($wdCode)";
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
                $condition .= " AND e.wd_market IN ($wdMarket)";
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
                $condition .= " AND e.wd_pop_group IN ($wdPopGroup)";
            }
        }
        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if ($teamType) {
            if (!is_array($teamType)) {
                $teamType = [$teamType];
            }
            if (in_array('all', $teamType)) {
                $condition .= " ";
            } else {
                $teamType = "'" . implode("','", $teamType) . "'";
                $condition .= " AND b.is_type IN ($teamType)";
            }
        }

        $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
        if ($dsName) {
            if (!is_array($dsName)) {
                $dsName = [$dsName];
            }
            if (in_array('all', $dsName)) {
                $condition .= " ";
            } else {
                $dsName = "'" . implode("','", $dsName) . "'";
                $condition .= " AND b.team_id  IN ($dsName)";
            }
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $condition .= " AND b.team_id IN $teamList";
        }

        $where = "";
        if ($condition && $andCondition) {
            $where .= " AND team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
        } elseif ($condition) {
            // $where .= " $condition";
            $where .= " $condition";
        }

        // echo $where;die;
        return $where;
    }

    final public function getConditionForCategoryAndProduct($category_name = "a.category_name", $product_name = "a.product_name")
    {
        $condition = "";
        $category = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "category");

        if ($category) {
            if (!is_array($category)) {
                $category = [$category];
            }
            if (in_array('all', $category)) {
                $condition .= " ";
            } else {
                $category = "'" . implode("','", $category) . "'";
                $condition .= " AND $category_name IN ($category)";
            }
        }
        $product = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "product");
        if ($product) {
            if (!is_array($product)) {
                $product = [$product];
            }
            if (in_array('all', $product)) {
                $condition .= " ";
            } else {
                $product = "'" . implode("','", $product) . "'";
                $condition .= " AND $product_name IN ($product)";
            }
        }

        return $condition;
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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.branch_name";
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

    final public function getCategoryList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
        $where = "";
        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.category_name from tblbranch as a, tblbranch_pickupstock_products as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = [
                    "label" => $row['category_name'],
                    "value" => $row['category_name']
                ];
            }
        }

        return $arrData;
    }

    final public function getProductList($cond = "")
    {
        $arrData = [];
        $arrData[] = [
            "label" => "All",
            "value" => "all"
        ];
        $where = "";
        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.product_name from tblbranch as a, tblbranch_pickupstock_products as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = [
                    "label" => $row['product_name'],
                    "value" => $row['product_name']
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
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.circle";
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
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.section";
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
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.wd_code";
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
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.wd_market";
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
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.wd_pop_group";
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
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND b.is_type != 4 AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.is_type";
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.team_name";
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
        // $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        // $user_id = $this->_iUserId;
        // $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        // if ($groupId == 1 || $groupId == 2) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } elseif ($groupId == 4) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $branchFilter = false;
        // }
        // $teamList = $this->_arrAccessInfo["user_teams"];
        // $where = "";
        // if ($teamList) {
        //     $where .= " AND team_id IN $teamList";
        // }
        $arrResult = [
            "branchFilter" => true,
            "monthList" => getMonthList(),
            "yearList" => getYearList(),
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "teamType" => $this->getDsTypeList(),
            "teamList" => $this->getTeamsList(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
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
                "categoryList" => $this->getCategoryList($districtCond),
                "productList" => $this->getProductList($districtCond),
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
                "categoryList" => "",
                "productList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            ];
        }
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getProduct()
    {
        $category = $this->_data['category'];
        $categoryCond = "";
        if (!empty($category)) {
            if (!is_array($category)) {
                $category = [$category];
            }
            if (in_array('all', $category)) {
                $categoryCond = ""; // No condition for 'all'
            } else {
                $category = "'" . implode("','", $category) . "'";
                $categoryCond = " AND b.category_name IN ($category)";
            }

            $arrResult = [
                "productList" => $this->getProductList($categoryCond),
            ];
        } else {
            $arrResult = [
                "productList" => "",
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
                "categoryList" => $this->getCategoryList($branchCond),
                "productList" => $this->getProductList($branchCond),
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
                "categoryList" => "",
                "productList" => "",
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

    final public function getSlideCardData()
    {
        $where = "";
        $where .= $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        $currentDate = currentDate();
        $mobileSummaryTable = $this->_tables["MOBILE_SUMMARY_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $projectTeamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        // $branch = getFormData($this->_data, "branch");
        // $branchCond = "";
        // if ($branch) {
        //     $matchAll = checkIfAllSelected($branch);
        //     if (!$matchAll) {
        //         if (isNonEmptyArray($branch)) {
        //             $branchIds = implode(",", $branch);
        //             $branchCond = " AND branch_id IN ($branchIds)";
        //         } else {
        //             $branchCond = " AND branch_id = $branch";
        //         }
        //     }
        // }

        $planned = getRowColumn($this->_dbConn, $mobileSummaryTable, "SUM(planned_outlets) AS planned", "dstatus = 0 AND rcd = '$currentDate' $where");
        $covered = getRowColumn($this->_dbConn, $mobileSummaryTable, "SUM(oulet_covered_today) AS coverd", "dstatus = 0 AND rcd = '$currentDate' $where");
        $addOutletcovered = getRowColumn($this->_dbConn, $mobileSummaryTable, "SUM(add_oulet_covered_today) AS Addcoverd", "dstatus = 0 AND rcd = '$currentDate' $where");
        $outletCoverd = $covered + $addOutletcovered;
        $rsAction = null;
        $iRows = 0;
        $rsAction2 = null;
        $iRows2 = 0;
        $rsAction3 = null;
        $iRows3 = 0;

        $focusBilled = 0;
        $allSellInShops = 0;
        $sQuery = "SELECT branch_id, team_id FROM $projectTeamTable WHERE dstatus = 0 $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $branchId = $row['branch_id'];
                $teamId = $row['team_id'];
                $focusBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' AND branch_id = $branchId $categoryAndProductCond");
                $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND branch_id = $branchId $categoryAndProductCond");
                if (!empty($focusBrandCols) && is_array($focusBrandCols)) {
                    // Prepare SUM columns for SQL
                    $sumFocusColumns = implode(") + SUM(", $focusBrandCols);
                    $sumFocusColumns = "SUM($sumFocusColumns)";

                    // Query to count rows where the sum of focus brand columns is greater than 0
                    $sQuery2 = "SELECT COUNT(DISTINCT ques_3) as focusBilledCount FROM tblsurvey_response_details WHERE dstatus = 0 AND ques_4 = 'Yes' AND capture_date = '$currentDate' AND team_id = $teamId $where HAVING $sumFocusColumns > 0";
                    $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2);

                    if ($iRows2 > 0) {
                        while ($row1 = $this->_dbConn->GetData($rsAction2)) {
                            $focusBilled += $row1['focusBilledCount'];
                        }
                    }
                }
                if (!empty($allBrandCols) && is_array($allBrandCols)) {
                    // Prepare SUM columns for SQL
                    $sumAllColumns = implode(") + SUM(", $allBrandCols);
                    $sumAllColumns = "SUM($sumAllColumns)";
                    // Query to count rows where the sum of brand columns is greater than 0
                    $sQuery3 = "SELECT COUNT(DISTINCT ques_3) as allSellInShops FROM tblsurvey_response_details WHERE dstatus = 0 AND ques_4 = 'Yes' AND capture_date = '$currentDate' AND team_id = $teamId $where HAVING $sumAllColumns > 0";
                    $this->_dbConn->ExecuteSelectQuery($sQuery3, $rsAction3, $iRows3);

                    if ($iRows3 > 0) {
                        while ($row2 = $this->_dbConn->GetData($rsAction3)) {
                            $allSellInShops += $row2['allSellInShops'];
                        }
                    }
                }
            }
        }

        if ($planned > 0) {
            $percentageCovered = round(($outletCoverd / $planned) * 100, 2);
            $percentageBilled = round(($allSellInShops / $planned) * 100, 2);
            $percentageFocusBilled = round(($focusBilled / $planned) * 100, 2);
        } else {
            $percentageCovered = 0;
            $percentageBilled = 0;
            $percentageFocusBilled = 0;
        }

        $cardsData = [
            [
                'value1' => isset($planned) ? $planned : 0,
                'text1' => 'Target',
                'value1Class' => 'text-c-black',
                'value2' => isset($outletCoverd) ? $outletCoverd : 0,
                'text2' => 'Visited',
                'value2Class' => 'text-c-black',
                'value3' => $percentageCovered . ' %',
                'text3' => 'Percent',
                'iconClass' => 'feather icon-layout f-18 text-black m-r-10',
                'iconText' => '',
                'title' => 'Outlets Visited',
                'color' => 'light-grey',
                'color1' => ''
            ],
            [
                'value1' => isset($planned) ? $planned : 0,
                'text1' => 'Target',
                'value1Class' => 'text-c-black',
                'value2' => isset($allSellInShops) ? $allSellInShops : 0,
                'text2' => ' Billed',
                'value2Class' => 'text-c-black',
                'value3' => $percentageBilled . ' %',
                'text3' => 'Percent',
                'iconClass' => 'feather icon-layout f-18 text-black m-r-10',
                'iconText' => '',
                'title' => 'Outlets Billed',
                'color' => 'light-grey',
                'color1' => ''
            ],
            [
                'value1' => isset($planned) ? $planned : 0,
                'text1' => 'Target',
                'value1Class' => 'text-c-black',
                'value2' => $focusBilled,
                'text2' => ' Billed',
                'value2Class' => 'text-c-black',
                'value3' => $percentageFocusBilled . ' %',
                'text3' => 'Percent',
                'iconClass' => 'feather icon-layout f-18 text-black m-r-10',
                'iconText' => '',
                'title' => 'Focus Brands Billed',
                'color' => 'text-black',
                'color1' => ''
            ],
        ];

        return $cardsData;
    }

    // Dashboard  Data
    public function getDashboardData()
    {
        $arrResponse = [
            "attCardData" => $this->getAttendanceCardData(),
            "beatAdherenceCardData" => $this->getBeatAdherenceCardData(),
            // "todaySalesAmountCardData" => $this->getTodaySalesAmountCardData(),
            "qualifiedAttData" => $this->getQualifiedAttendanceCardData(),
            "outletVisitedCardData" => $this->getOutletVisitedCardData(),
            // "todayOutletVisitedCardData" => $this->getTodayOutletVisitedCardData(),
            "focusVisitTillDateAmountCardData" => $this->getFocusVisitTillDateAmountCardData(),
            "slideCardData" => $this->getSlideCardData(),
            "graphs" => [
                "outletVisitedMonthlyComparisonData" => $this->getShopVisitedComparisonData(),
                "getOutletBilledComparisonData" => $this->getShopBilledComparisonData(),
                "getShopVisitedSPLYComparisonData" => $this->getShopVisitedSPLYComparisonData(),
                "getShopVisitedYTDLYTDComparisonDataMonthly" => $this->getShopVisitedYTDLYTDComparisonDataMonthly(),
                "getShopBilledSPLYComparisonData" => $this->getShopBilledSPLYComparisonData(),
                "getShopBilledYTDLYTDComparisonData" => $this->getShopBilledYTDLYTDComparisonData(),
                "getFocusCMLMOutletBilledComparisonData" => $this->getFocusCMLMOutletBilledComparisonData(),
                "getFocusSPLYOutletBilledComparisonData" => $this->getFocusSPLYOutletBilledComparisonData(),
            ],
            // $this->getCardsData(),
        ];

        $arrMessage = responseMessage([], 1, $arrResponse, true);
        echo json_encode($arrMessage);
    }

    // ATTENDANCE CARD DATA
    public function getAttendanceCardData()
    {
        $attTable = $this->_tables["ATTENDANCE_TABLE"];
        $teamsTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $currentDate = currentDate();
        $morningAttData = 0;
        $where = $this->getCondition(true);

        $allTeams = getRowColumn($this->_dbConn, $teamsTable, "COUNT(DISTINCT team_id)", "dstatus = 0 AND s_id = 99  $where");
        $morningAttData = getRowColumn($this->_dbConn, $attTable, "COUNT(DISTINCT team_id) AS distinct_team_count", "dstatus = 0 AND call_type = '0'  AND capture_date = '$currentDate' $where ");
        $notPresent = getRowColumn($this->_dbConn, $teamsTable, "COUNT(DISTINCT team_id)", "dstatus = 0 AND team_id NOT IN (SELECT team_id FROM $attTable WHERE dstatus = 0 AND call_type = '0'  AND capture_date = '$currentDate' $where) AND s_id = 99 $where");

        $percentAttendance = 0;
        if ($morningAttData > 0) {
            $percentAttendance =  $morningAttData / $allTeams * 100;
            $percentAttendance = round($percentAttendance, 2);
        } else {
        }

        $attCardData = [
            "allTeams" => $allTeams,
            "morningAttData" => $morningAttData,
            "percentAttendance" => $percentAttendance,
            "notPresent" => $notPresent,
        ];
        // print_r($attCardData);die;
        return $attCardData;
    }

    // BEAT ADHERENCE CARD DATA
    public function getBeatAdherenceCardData()
    {
        $rsAction = null;
        $iRows = null;
        // $arrData = [];
        $currentDate = currentDate();
        $currentDay = date('D', strtotime($currentDate));
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $attTable = $this->_tables["ATTENDANCE_TABLE"];
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $where = "";
        $where = $this->getCondition();

        $todayActiveDS = getRowColumn($this->_dbConn, $attTable, "COUNT(DISTINCT team_id) AS distinct_team_count", "dstatus = 0 AND call_type = '0'  AND capture_date = '$currentDate' $where");
        $sQuery = "SELECT COUNT(is_beat_adherence) AS adherence FROM $summaryTable WHERE dstatus = 0 AND is_beat_adherence = 'Yes' AND activity_date = '$currentDate' $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        $adherence = 0;

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $adherence = $row['adherence'];
            }
        }

        $unAdherence = getRowColumn($this->_dbConn, $summaryTable, "COUNT(is_beat_adherence) AS unAdherence", "dstatus = 0 AND is_beat_adherence = 'No' AND activity_date = '$currentDate' $where");

        // AVG BEAT ADHERENCE
        if ($todayActiveDS > 0) {
            $avgCompRoute =  round(($adherence / $todayActiveDS) * 100, 2);
        } else {
            $avgCompRoute = 0;
        }

        $beatAdhrerenceCardData = [
            "todayActiveDS" => $todayActiveDS,
            "adherence" => $adherence,
            "unAdherence" => $unAdherence,
            "avgCompRoute" => $avgCompRoute,
        ];
        return $beatAdhrerenceCardData;
    }

    // QUALIFIED ATTENDANCE CARD DATA
    public function getQualifiedAttendanceCardData()
    {
        $currentDate = currentDate(); // Current date in 'Y-m-d' format
        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $attTable = $this->_tables["ATTENDANCE_TABLE"];
        $minQualifiedAttendanceTimeInMin = 240; // Minimum required attendance time in minutes
        $qualifiedCount = 0;
        $mornAttendance = 0;
        $UnqualifiedCount = 0;
        $where = "";
        $where .= $this->getCondition(true);

        // Get the start and end datetime for each team for the current date
        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT start_datetime, end_datetime FROM $vanDsSummaryTable WHERE activity_date = '$currentDate' AND dstatus = 0 $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        $mornAttendance = getRowColumn($this->_dbConn, $attTable, "COUNT(DISTINCT team_id) AS distinct_team_count", "dstatus = 0 AND call_type = '0'  AND capture_date = '$currentDate' $where ");
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $startDateTime = $row['start_datetime'];
                $endDateTime = $row['end_datetime'];
                $timeSpentInMin = getTimeDifferenceInMinutes($startDateTime, $endDateTime);
                if ($timeSpentInMin >= $minQualifiedAttendanceTimeInMin) {
                    $qualifiedCount++;
                } elseif ($timeSpentInMin < $minQualifiedAttendanceTimeInMin) {
                    $UnqualifiedCount++;
                }
            }
        }

        // Calculate average qualified percentage if there are active records
        if ($mornAttendance > 0) {
            $avgQualified = $qualifiedCount / $mornAttendance * 100;
            $avgQualified = round($avgQualified, 2);
        } else {
            $avgQualified = 0;
        }

        $qualifiedAttCardData = [
            "todayActiveDS" => $mornAttendance,
            "qualifiedCount" => $qualifiedCount,
            "UnqualifiedCount" => $UnqualifiedCount,
            "avgQualified" => $avgQualified,
        ];

        return $qualifiedAttCardData;
    }

    // TODAY OUTLET VISITED  AND BILLED CARD  DATA
    public function getTodayOutletVisitedCardData()
    {
        $rsAction2 = null;
        $iRows2 = null;
        $currentDate = currentDate();
        $where = $this->getCondition();
        $currentDay = date('D', strtotime($currentDate));
        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        // Get minimum supposed shop count
        $minimumSupposedShop = getRowColumn($this->_dbConn, $routeTable, "COUNT(rec_id)", "dstatus = 0 $where AND route_name LIKE '%$currentDay%'");

        // If minimumSupposedShop is 0, set all other values to 0 and return early
        if ($minimumSupposedShop == 0) {
            return [
                "totalShopsVisitedToday" => 0,
                "supposedShop" => 0,
                "totalSellinShopsToday" => 0,
            ];
        }

        // Proceed only if there are supposed shops
        $sQuery2 = "SELECT COUNT(team_id) as teamCounts, total_sellin_shops, total_sales_deliveries, total_other_shops
                FROM $vanDsSummaryTable
                WHERE dstatus = 0 $where AND activity_date = '$currentDate'";
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2);

        $totalSalesDeliveryShops = 0;
        $totalSellinShops = 0;
        $totalOtherShops = 0;
        $teamCounts = 0;

        if ($iRows2 > 0) {
            while ($row = $this->_dbConn->GetData($rsAction2)) {
                $teamCounts = $row['teamCounts'];
                $totalSalesDeliveryShops += $row['total_sales_deliveries'];
                // $totalOtherShops += $row['total_other_shops'];
                $totalSellinShops += $row['total_sellin_shops'];
            }
        }

        $totalShopsVisited = $totalSalesDeliveryShops;

        return [
            "totalShopsVisitedToday" => $totalShopsVisited,
            "supposedShop" => $minimumSupposedShop,
            "totalSellinShopsToday" => $totalSellinShops,
        ];
    }

    // THIS MONTH OUTLET VISITED  AND BILLED CARD  DATA
    public function getOutletVisitedCardData()
    {
        $rsAction = null;
        $iRows = null;
        $rsAction2 = null;
        $iRows2 = null;
        $currentDate = currentDate();
        $where = "";
        $where .= $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        // $startDate = date('Y-m-01', strtotime($currentDate));
        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $responseDetail = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $projectTeamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $startDate = date('Y-m-01', strtotime($currentDate));
        // $lastMonthStart = date("Y-m-01", strtotime("first day of last month"));
        // $lastMonthEnd = date("Y-m-t", strtotime("last day of last month"));
        $branch = getFormData($this->_data, "branch");
        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond .= " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond .= " AND branch_id = $branch";
                }
            }
        }

        // Get minimum supposed shop count
        $arrRoute = [];
        $sQuery = "SELECT DISTINCT ques_1, team_id FROM $responseDetail WHERE capture_date BETWEEN '$startDate' AND '$currentDate' AND  dstatus = 0 $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamId = $row['team_id'];
                $arrRoute[] = isNonEmptyArray(json_decode($row['ques_1'], true)) ? getStringFromEncodedArray($row['ques_1']) : $row['ques_1'];
            }
        }
        $routes = "'" . implode("','", $arrRoute) . "'";

        $plannedOutlets = getRowColumn($this->_dbConn, $routeTable, "COUNT(rec_id)", "dstatus = 0 AND route_name IN ($routes) $where");

        // THIS MONTH TOTAL Visited
        $sQuery2 = "SELECT SUM(total_sellin_shops) AS sellInShop, SUM(total_sales_deliveries) AS visitShop, SUM(total_other_shops) AS otherVisitShop FROM $vanDsSummaryTable WHERE dstatus = 0 $where AND activity_date BETWEEN '$startDate' AND '$currentDate'";
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2);

        if ($iRows2 > 0) {
            while ($row = $this->_dbConn->GetData($rsAction2)) {
                $totalSalesDeliveryShops = $row['visitShop'];
                $totalOtherShops = $row['otherVisitShop'];
                $totalSellinShops = $row['sellInShop'];
                $totalShopsVisited = $totalSalesDeliveryShops + $totalOtherShops;
            }
        }

        $rsAction2 = null;
        $iRows2 = 0;
        $focusBilled = 0;
        $focusBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' $categoryAndProductCond $branchCond");
        if (!empty($focusBrandCols) && is_array($focusBrandCols)) {
            // Prepare SUM columns for SQL
            $sumColumns = implode(") + SUM(", $focusBrandCols);
            $sumColumns = "SUM($sumColumns)";

            // Query to count rows where the sum of focus brand columns is greater than 0
            $sQuery2 = "SELECT COUNT(DISTINCT ques_3) as focusBilledCount FROM tblsurvey_response_details WHERE dstatus = 0 AND ques_4 = 'Yes' AND capture_date BETWEEN '$startDate' AND '$currentDate' $where GROUP BY capture_date HAVING $sumColumns > 0";
            $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2);

            if ($iRows2 > 0) {
                while ($row1 = $this->_dbConn->GetData($rsAction2)) {
                    $focusBilled += $row1['focusBilledCount'];
                }
            }
        }

        $shopVisitedCardData = [
            "totalShopsVisitedTillDate" => $totalShopsVisited,
            "supposedShopCounts" => $plannedOutlets,
            "totalSellinShopsTillDate" => $totalSellinShops,
            "focusBilled" => $focusBilled
        ];
        return $shopVisitedCardData;
    }

    // FOCUS SALES TILL DATE AMOUNT CARD DATA
    public function getFocusVisitTillDateAmountCardData()
    {
        $rsAction2 = null;
        $iRows2 = null;
        $currentDate = currentDate();
        $where = $this->getCondition();
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        $startDate = date('Y-m-01', strtotime($currentDate));
        $startDate = date('Y-m-01', strtotime($currentDate));
        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        $targetShop = getRowColumn($this->_dbConn, $routeTable, "COUNT(rec_id)", "dstatus = 0 $where");

        $focusBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' $categoryAndProductCond");

        if (!empty($focusBrandCols)) {
            // Prepare SUM columns for SQL
            $sumColumns = implode(") + SUM(", $focusBrandCols);
            $sumColumns = "SUM($sumColumns)";

            // Query to get the sum for each row
            $sQuery2 = "SELECT $sumColumns as thisMonthFocus FROM $vanDsSummaryTable WHERE dstatus = 0 AND activity_date BETWEEN  '$startDate' AND '$currentDate' $where";

            $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2);
            $totalSaleAmount = 0;
            $shopsCount = 0;

            if ($iRows2 > 0) {
                while ($row = $this->_dbConn->GetData($rsAction2)) {
                    $productSum = $row['thisMonthFocus'];
                    // Add to total sale amount
                    $totalSaleAmount += $productSum;
                    // Increment count if the sum is not zero
                    if ($productSum > 0) {
                        $shopsCount++;
                    }
                }
            }

            // Print results
            // echo "Total Sale Amount: " . $totalSaleAmount . "\n";
            // echo "Count of Non-Zero Sums: " . $shopsCount;
            $tilldatetodayFocusBilledCardData = [
                "totalFocusBilled" => $shopsCount,
                "minimumSupposedShop" => $targetShop,
            ];
            return $tilldatetodayFocusBilledCardData;
        }
    }

    // Function to calculate cumulative sum for each day
    final public function calculateCumulativeSum(&$dataArray)
    {
        for ($day = 2; $day <= 31; $day++) {
            $dataArray[$day] += $dataArray[$day - 1];
        }
    }

    // GRAPH DATA------------------------------------------------------------------------------------------------
    //TOTAL SHOP VISITED LAST MONTH / CURRENT MONTH COMPARISON GRAPH
    public function getShopVisitedComparisonData()
    {
        $branchDataLastMonth = array_fill(1, 31, 0); // For last month's data
        $branchDataThisMonth = array_fill(1, 31, 0); // For this month's data
        $where = $this->getCondition(true);
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month");
        $year = date("Y"); // Default year is the current year

        if (!empty($month)) {
            if (strpos($month, '-') !== false) {
                $year = explode('-', $month)[0];
            } else {
                $month = "$year-$month";
            }
            $thisMonthStart = date("Y-m-01", strtotime($month));
            $thisMonthEnd = date("Y-m-t", strtotime($month));
            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));
            $lastMonthStart = date("Y-m-01", $lastMonthTimestamp);
            $lastMonthEnd = date("Y-m-t", $lastMonthTimestamp);
            $maxDaysThisMonth = (int)date("t", strtotime($month));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp);
        } else {
            $thisMonthStart = date("Y-m-01");
            $thisMonthEnd = date("Y-m-d");
            $lastMonthTimestamp = strtotime("first day of last month");
            $lastMonthStart = date("Y-m-01", strtotime("first day of last month"));
            $lastMonthEnd = date("Y-m-t", strtotime("last day of last month"));
            $maxDaysThisMonth = (int)date("t");
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month"));
        }

        $dateCondLastMonth = "AND activity_date BETWEEN '$lastMonthStart' AND '$lastMonthEnd'";
        $dateCondThisMonth = "AND activity_date BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";

        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];

        // Fetch sales amount for last month
        $sQueryLastMonth = "SELECT SUM(total_sales_deliveries) AS totalShopVisitedLM, SUM(total_other_shops) AS totalOtherShopVisitedLM, activity_date
                        FROM $vanDsSummaryTable
                        WHERE dstatus = 0 $where $dateCondLastMonth
                        GROUP BY activity_date ORDER BY activity_date";
        $rsActionLastMonth = null;
        $iRowsLastMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryLastMonth, $rsActionLastMonth, $iRowsLastMonth);

        if ($iRowsLastMonth > 0) {
            while ($rowLastMonth = $this->_dbConn->GetData($rsActionLastMonth)) {
                $sumShopVisit = (float)$rowLastMonth['totalShopVisitedLM'];
                $sumOtherShopVisit = (float)$rowLastMonth['totalOtherShopVisitedLM'];
                $totalShopSum = $sumShopVisit + $sumOtherShopVisit;
                $day = (int)date("d", strtotime($rowLastMonth['activity_date']));

                $branchDataLastMonth[$day] = $totalShopSum;
            }
        }

        // Fetch sales amount for this month
        $sQueryThisMonth = "SELECT SUM(total_sales_deliveries) AS totalShopVisitedCM, SUM(total_other_shops) AS totalOtherShopVisitedCM, activity_date
                        FROM $vanDsSummaryTable
                        WHERE dstatus = 0 $where $dateCondThisMonth
                        GROUP BY activity_date ORDER BY activity_date";
        $rsActionThisMonth = null;
        $iRowsThisMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryThisMonth, $rsActionThisMonth, $iRowsThisMonth);

        if ($iRowsThisMonth > 0) {
            while ($rowThisMonth = $this->_dbConn->GetData($rsActionThisMonth)) {
                $sumShopVisit = (float)$rowThisMonth['totalShopVisitedCM'];
                $sumOtherShopVisit = (float)$rowThisMonth['totalOtherShopVisitedCM'];
                $totalShopSum = $sumShopVisit + $sumOtherShopVisit;
                $day = (int)date("d", strtotime($rowThisMonth['activity_date']));

                $branchDataThisMonth[$day] = $totalShopSum;
            }
        }

        // Adjust arrays for slicing
        $currentYearMonth = date("Y-m");
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;
        $currentDay = (int)date('j');

        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $currentDay, true);
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $maxDaysThisMonth, true);
        }

        $branchDataLastMonth = array_slice($branchDataLastMonth, 0, $maxDaysLastMonth, true);

        $xAxisLabels = range(1, max($maxDaysLastMonth, $maxDaysThisMonth));
        // $xAxisLabels = [1, 2, 3, 4, 5, 6, 7,8 ,9,10,11,12, 13,14,15,16,17,18];

        $salesData = [
            'seriesData' => [
                ['data' => array_values($branchDataLastMonth)],
                ['data' => array_values($currentYearMonthSalesData)]
            ],
            'xAxisLabels' => $xAxisLabels,
            'lastMonthName' => date("F Y", $lastMonthTimestamp),
            'currentMonthName' => date("F Y", strtotime($thisMonthStart)),
        ];

        return [
            "salesData" => $salesData,
            "height" => "800px"
        ];
    }

    //TOTAL SHOP VISITED SPLY MONTH COMPARISON GRAPH
    public function getShopVisitedSPLYComparisonData()
    {
        $visitDataLastYear = array_fill(1, 31, 0); // For last year's same month data
        $visitDataThisMonth = array_fill(1, 31, 0);     // For this year's current month data
        $where = $this->getCondition(true);
        $dateCondThisMonth = "";
        $dateCondLastYearMonth = "";
        $where = $this->getCondition(false);
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month1");
        // Adjust dates if a specific month is provided
        if (!empty($month)) {
            // Ensure the month includes the year, defaulting to the current year if not provided
            $year = date("Y"); // Default to the current year
            if (strpos($month, '-') !== false) {
                // If the input includes a year (e.g., "2024-11"), use it directly
                $year = explode('-', $month)[0];
            } else {
                // If only the month is provided (e.g., "11"), append the current year
                $month = "$year-$month";
            }

            // Calculate start and end dates for the current month of the specified year
            $currentYearMonthStart = date("Y-m-01", strtotime($month));
            $currentYearMonthEnd = date("Y-m-t", strtotime($month));
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            // Calculate start and end dates for the same month in the previous year
            $lastMonthTimestamp = strtotime("-1 month", strtotime($currentYearMonthStart));
            $lastYearMonthStart = date("Y-m-01", strtotime("$month -1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("$month -1 year"));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Set start and end dates for the current month of this year
            $currentYearMonthStart = date("Y-m-01"); // First day of current month, current year
            $currentYearMonthEnd = date("Y-m-t"); // Last day of current month, current year

            // Set start and end dates for the same month last year
            $lastYearMonthStart = date("Y-m-01", strtotime("-1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("-1 year"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        $dateCondThisMonth .= "AND a.activity_date BETWEEN '$currentYearMonthStart' AND '$currentYearMonthEnd'";
        $dateCondLastYearMonth .= "AND a.activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd'";
        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $mappingTable = $this->_tables["WD_MAPPING_TABLE"];

        // Fetch sales amount for last year's same month
        $sQueryLastYearMonth = "SELECT SUM(a.total_sales_deliveries) AS totalShopVisitedLYM, SUM(a.total_other_shops) AS totalOtherShopVisitedLYM, a.activity_date FROM $vanDsSummaryTable as a" .
            ", $projectTeamTable as b, $branchTable as d, $mappingTable as e WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $dateCondLastYearMonth $where GROUP BY a.activity_date ORDER BY a.activity_date";
        $rsActionLastYearMonth = null;
        $iRowsLastYearMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryLastYearMonth, $rsActionLastYearMonth, $iRowsLastYearMonth);

        if ($iRowsLastYearMonth > 0) {
            while ($rowLastYearMonth = $this->_dbConn->GetData($rsActionLastYearMonth)) {
                $sumShopVisit = (float)$rowLastYearMonth['totalShopVisitedLYM'];
                $sumOtherShopVisit = (float)$rowLastYearMonth['totalOtherShopVisitedLYM'];
                $totalShopVisit = $sumShopVisit + $sumOtherShopVisit;
                $day = (int)date("d", strtotime($rowLastYearMonth['activity_date']));

                $visitDataLastYear[$day] = $totalShopVisit;
            }
        }

        // Fetch sales amount for this year's current month
        $sQueryThisMonth = "SELECT SUM(a.total_sales_deliveries) AS totalShopVisitedCM, SUM(a.total_other_shops) AS totalOtherShopVisitedCYM, a.activity_date FROM $vanDsSummaryTable as a" .
            ", $projectTeamTable as b, $branchTable as d, $mappingTable as e WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $dateCondThisMonth $where GROUP BY a.activity_date ORDER BY a.activity_date";
        $rsActionThisMonth = null;
        $iRowsThisMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryThisMonth, $rsActionThisMonth, $iRowsThisMonth);

        if ($iRowsThisMonth > 0) {
            while ($rowThisMonth = $this->_dbConn->GetData($rsActionThisMonth)) {
                $sumShopVisit = (float)$rowThisMonth['totalShopVisitedCM'];
                $sumOtherShopVisit = (float)$rowThisMonth['totalOtherShopVisitedCYM'];
                $totalShopVisit = $sumShopVisit + $sumOtherShopVisit;
                $day = (int)date("d", strtotime($rowThisMonth['activity_date']));

                $visitDataThisMonth[$day] = $totalShopVisit;
            }
        }

        // Get the current day of the month (1 to 31)
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($visitDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($visitDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($visitDataLastYear, 0, $maxDaysLastMonth, true);

        $visitData = [
            'seriesData' => [
                ['data' => array_values($lastMonthSalesData)], // Last year's month
                ['data' => array_values($currentYearMonthSalesData)]      // This year's month till today
            ],
            'xAxisLabels' => range(1, max(31, $currentDay)), // Days 1 to 31 for both months
            "lastYearMonthName" => date("F Y", strtotime($lastYearMonthStart)),  // Last month's name
            "currentYearMonthName" => date("F Y", strtotime($currentYearMonthStart)),  // Current month's name
        ];

        return [
            "visitData" => $visitData,
            "height" => "800px"
        ];
    }

    // Outlet Visited YTD/LTD COMPARISON GRAPH
    public function getShopVisitedYTDLYTDComparisonDataMonthly()
    {
        $lastYearData = array_fill(1, 12, 0); // Actual data for each month of last year
        $thisYearData = array_fill(1, 12, 0); // Actual data for each month of this year up to the current month

        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $where = $this->getCondition(true);

        // Explicitly define years
        $year = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "year");
        if (!empty($year)) {
            $currentYear = $year;
        } else {
            $currentYear = date('Y');
        }
        $lastYear = $currentYear - 1;

        for ($month = 1; $month <= 12; $month++) {
            // Define date ranges for last year's same month
            $lastYearStart = date("Y-m-01", strtotime("$lastYear-$month-01"));
            $lastYearEnd = date("Y-m-t", strtotime("$lastYear-$month-01"));
            $dateCondLastYear = "AND activity_date BETWEEN '$lastYearStart' AND '$lastYearEnd'";

            // Fetch last year's data
            $sQueryLastYear = "SELECT SUM(total_sales_deliveries) AS totalShopVisitedLY, SUM(total_other_shops) AS totalOtherShopVisitedLY
                           FROM $vanDsSummaryTable
                           WHERE dstatus = 0 $dateCondLastYear $where";
            $rsActionLastYear = null;
            $iRowsLastYear = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryLastYear, $rsActionLastYear, $iRowsLastYear);

            if ($iRowsLastYear > 0) {
                $rowLastYear = $this->_dbConn->GetData($rsActionLastYear);
                $totalVisited = (float)$rowLastYear['totalShopVisitedLY'] + (float)$rowLastYear['totalOtherShopVisitedLY'];
                $lastYearData[$month] = $totalVisited; // Store actual value for the month
            }

            // Define date ranges for this year's same month, but only up to the current month
            if ($month <= date("n")) { // Process only up to the current month
                $thisYearStart = date("Y-m-01", strtotime("$currentYear-$month-01"));
                $thisYearEnd = date("Y-m-t", strtotime("$currentYear-$month-01"));
                $dateCondThisYear = "AND activity_date BETWEEN '$thisYearStart' AND '$thisYearEnd'";

                // Fetch this year's data
                $sQueryThisYear = "SELECT SUM(total_sales_deliveries) AS totalShopVisitedTY, SUM(total_other_shops) AS totalOtherShopVisitedTY
                               FROM $vanDsSummaryTable
                               WHERE dstatus = 0 $dateCondThisYear $where";
                $rsActionThisYear = null;
                $iRowsThisYear = 0;
                $this->_dbConn->ExecuteSelectQuery($sQueryThisYear, $rsActionThisYear, $iRowsThisYear);

                if ($iRowsThisYear > 0) {
                    $rowThisYear = $this->_dbConn->GetData($rsActionThisYear);
                    $totalVisited = (float)$rowThisYear['totalShopVisitedTY'] + (float)$rowThisYear['totalOtherShopVisitedTY'];
                    $thisYearData[$month] = $totalVisited; // Store actual value for the month
                }
            }
        }

        // Prepare data structure for chart
        $YTDLYTDVISIT = [
            'seriesData' => [
                ['data' => array_values($lastYearData)], // Actual data for last year by month
                ['data' => array_slice(array_values($thisYearData), 0, date("n"))] // This year's actual data up to the current month
            ],
            'xAxisLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            "lastYear" => (string)$lastYear,
            "currentYear" => (string)$currentYear,
        ];

        return [
            "ytdlytdVisit" => $YTDLYTDVISIT,
            "height" => "800px"
        ];
    }

    //TOTAL SHOP BILLED LAST MONTH / CURRENT MONTH COMPARISON GRAPH
    public function getShopBilledComparisonData()
    {
        $branchDataLastMonth = array_fill(1, 31, 0); // For last month's data
        $branchDataThisMonth = array_fill(1, 31, 0); // For this month's data
        $where = $this->getCondition(true);
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month2");
        $year = date("Y"); // Default year is the current year

        if (!empty($month)) {
            if (strpos($month, '-') !== false) {
                $year = explode('-', $month)[0];
            } else {
                $month = "$year-$month";
            }
            $thisMonthStart = date("Y-m-01", strtotime($month)); // First day of the selected month
            $thisMonthEnd = date("Y-m-t", strtotime($month)); // Last day of the selected month
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));
            $lastMonthStart = date("Y-m-01", $lastMonthTimestamp); // First day of the previous month
            $lastMonthEnd = date("Y-m-t", $lastMonthTimestamp); // Last day of the previous month
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            $thisMonthStart = date("Y-m-01");
            $thisMonthEnd = date("Y-m-d");
            $lastMonthTimestamp = strtotime("first day of last month");
            $lastMonthStart = date("Y-m-01", strtotime("first day of last month"));
            $lastMonthEnd = date("Y-m-t", strtotime("last day of last month"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        // Define last month's condition
        $dateCondLastMonth = "AND activity_date BETWEEN '$lastMonthStart' AND '$lastMonthEnd'";

        // Define current month's condition
        $dateCondThisMonth = "AND activity_date BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";

        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];

        // Fetch sales amount for last month
        $sQueryLastMonth = "SELECT SUM(total_sellin_shops) AS totalShopBilledLM, activity_date FROM $vanDsSummaryTable WHERE dstatus = 0 $where $dateCondLastMonth  GROUP BY activity_date ORDER BY activity_date";
        $rsActionLastMonth = null;
        $iRowsLastMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryLastMonth, $rsActionLastMonth, $iRowsLastMonth);

        $cumulativeSumLastMonth = 0; // Initialize cumulative sum for last month

        if ($iRowsLastMonth > 0) {
            while ($rowLastMonth = $this->_dbConn->GetData($rsActionLastMonth)) {
                $totalForDate = (float)$rowLastMonth['totalShopBilledLM']; // Get the total for the date
                $day = (int)date("d", strtotime($rowLastMonth['activity_date']));

                // If the total is 0, inherit previous day's cumulative sum
                if ($totalForDate == 0) {
                    $branchDataLastMonth[$day] = $cumulativeSumLastMonth;
                } else {
                    // Add the previous day's cumulative sum to today's total
                    $cumulativeSumLastMonth = $totalForDate;
                    $branchDataLastMonth[$day] = $cumulativeSumLastMonth;
                }
            }
        }

        // Fetch sales amount for this month
        $sQueryThisMonth = "SELECT SUM(total_sellin_shops) AS totalShopBilledCM, activity_date FROM $vanDsSummaryTable WHERE dstatus = 0 $where $dateCondThisMonth  GROUP BY activity_date ORDER BY activity_date";
        $rsActionThisMonth = null;
        $iRowsThisMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryThisMonth, $rsActionThisMonth, $iRowsThisMonth);

        $cumulativeSumThisMonth = 0; // Initialize cumulative sum for this month

        if ($iRowsThisMonth > 0) {
            while ($rowThisMonth = $this->_dbConn->GetData($rsActionThisMonth)) {
                $totalForDate = (float)$rowThisMonth['totalShopBilledCM']; // Get the total for the date
                $day = (int)date("d", strtotime($rowThisMonth['activity_date']));

                // If the total is 0, inherit previous day's cumulative sum
                if ($totalForDate == 0) {
                    $branchDataThisMonth[$day] = $cumulativeSumThisMonth;
                } else {
                    // Add the previous day's cumulative sum to today's total
                    $cumulativeSumThisMonth = $totalForDate;
                    $branchDataThisMonth[$day] = $cumulativeSumThisMonth;
                }
            }
        }

        $this->calculateCumulativeSum($branchDataLastMonth);
        $this->calculateCumulativeSum($branchDataThisMonth);

        // Get the current day of the month (1 to 31)
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastMonth, 0, $maxDaysLastMonth, true);

        $billedData = [
            'seriesData' => [
                ['data' => array_values($lastMonthSalesData)], // Full month for last month
                ['data' => array_values($currentYearMonthSalesData)]  // Till today for this month
            ],
            'xAxisLabels' => range(1, max(31, $currentDay)), // Days 1 to 31 for last month, or 1 to today for this month
            "lastMonthName" => date("F Y", $lastMonthTimestamp),  // Last month's name
            "currentMonthName" => date("F Y", strtotime($thisMonthStart)),  // Current month's name
        ];

        return [
            "billedData" => $billedData,
            // "title" => "Total Sales Amount Comparison: Cumulative Last Month vs This Month (Date-wise)",
            "height" => "800px"
        ];
    }

    //TOTAL SHOP BILLED SPLY COMPARISON GRAPH
    public function getShopBilledSPLYComparisonData()
    {
        $visitDataLastYear = array_fill(1, 31, 0); // For last year's same month data
        $visitDataThisMonth = array_fill(1, 31, 0);     // For this year's current month data
        $where = $this->getCondition(true);
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month3");
        // Adjust dates if a specific month is provided
        if (!empty($month)) {
            // Ensure the month includes the year, defaulting to the current year if not provided
            $year = date("Y"); // Default to the current year
            if (strpos($month, '-') !== false) {
                // If the input includes a year (e.g., "2024-11"), use it directly
                $year = explode('-', $month)[0];
            } else {
                // If only the month is provided (e.g., "11"), append the current year
                $month = "$year-$month";
            }

            // Calculate start and end dates for the current month of the specified year
            $thisMonthStart = date("Y-m-01", strtotime($month));
            $thisMonthEnd = date("Y-m-t", strtotime($month));
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            // Calculate start and end dates for the same month in the previous year
            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));
            $lastYearMonthStart = date("Y-m-01", strtotime("$month -1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("$month -1 year"));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Set start and end dates for the current month of this year
            $thisMonthStart = date("Y-m-01"); // First day of current month, current year
            $thisMonthEnd = date("Y-m-t"); // Last day of current month, current year

            // Set start and end dates for the same month last year
            $lastYearMonthStart = date("Y-m-01", strtotime("-1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("-1 year"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        // Define last year's same month condition
        $dateCondLastYearMonth = "AND activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd'";

        // Define this year's current month condition
        $dateCondThisMonth = "AND activity_date BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";

        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];

        // Fetch sales amount for last year's same month
        $sQueryLastYearMonth = "SELECT SUM(total_sellin_shops) AS totalShopSellInLYM, activity_date FROM $vanDsSummaryTable WHERE dstatus = 0 $where $dateCondLastYearMonth GROUP BY activity_date ORDER BY activity_date";
        $rsActionLastYearMonth = null;
        $iRowsLastYearMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryLastYearMonth, $rsActionLastYearMonth, $iRowsLastYearMonth);

        $cumulativeSumLastYearMonth = 0; // Initialize cumulative sum for last year's month

        if ($iRowsLastYearMonth > 0) {
            while ($rowLastYearMonth = $this->_dbConn->GetData($rsActionLastYearMonth)) {
                $totalForDate = (float)$rowLastYearMonth['totalShopSellInLYM'];
                $day = (int)date("d", strtotime($rowLastYearMonth['activity_date']));

                if ($totalForDate == 0) {
                    $visitDataLastYear[$day] = $cumulativeSumLastYearMonth;
                } else {
                    $cumulativeSumLastYearMonth = $totalForDate;
                    $visitDataLastYear[$day] = $cumulativeSumLastYearMonth;
                }
            }
        }

        // Fetch sales amount for this year's current month
        $sQueryThisMonth = "SELECT SUM(total_sellin_shops) AS totalShopVisitedInCM, activity_date FROM $vanDsSummaryTable WHERE dstatus = 0 $where $dateCondThisMonth  GROUP BY activity_date ORDER BY activity_date";
        $rsActionThisMonth = null;
        $iRowsThisMonth = 0;
        $this->_dbConn->ExecuteSelectQuery($sQueryThisMonth, $rsActionThisMonth, $iRowsThisMonth);

        $cumulativeSumThisMonth = 0; // Initialize cumulative sum for this month

        if ($iRowsThisMonth > 0) {
            while ($rowThisMonth = $this->_dbConn->GetData($rsActionThisMonth)) {
                $totalForDate = (float)$rowThisMonth['totalShopVisitedInCM'];
                $day = (int)date("d", strtotime($rowThisMonth['activity_date']));

                if ($totalForDate == 0) {
                    $visitDataThisMonth[$day] = $cumulativeSumThisMonth;
                } else {
                    $cumulativeSumThisMonth = $totalForDate;
                    $visitDataThisMonth[$day] = $cumulativeSumThisMonth;
                }
            }
        }

        $this->calculateCumulativeSum($visitDataLastYear);
        $this->calculateCumulativeSum($visitDataThisMonth);

        // Limit the current month's data to only up to the current day
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($visitDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($visitDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($visitDataLastYear, 0, $maxDaysLastMonth, true);

        $visitData = [
            'seriesData' => [
                ['data' => array_values($lastMonthSalesData)], // Last year's month
                ['data' => array_values($currentYearMonthSalesData)]      // This year's month till today
            ],
            'xAxisLabels' => range(1, max(31, $currentDay)), // Days 1 to 31 for both months
            "lastYearMonthName" => date("F Y", strtotime($lastYearMonthStart)),  // Last month's name
            "currentYearMonthName" => date("F Y", strtotime($thisMonthStart)),  // Current month's name
        ];

        return [
            "billedSPLYData" => $visitData,
            "height" => "800px"
        ];
    }

    // Outlet Visited YTD/LTD COMPARISON GRAPH
    public function getShopBilledYTDLYTDComparisonData()
    {
        $lastYearData = array_fill(1, 12, 0);
        $thisYearData = array_fill(1, 12, 0);

        $vanDsSummaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $where = $this->getCondition(true);
        $year = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "year1");
        if (!empty($year)) {
            $currentYear = $year;
        } else {
            $currentYear = date('Y');
        }
        $lastYear = $currentYear - 1;

        $cumulativeSumLastYear = 0;
        $cumulativeSumThisYear = 0;

        for ($month = 1; $month <= 12; $month++) {
            // Define date ranges for last year's same month
            $lastYearStart = date("Y-m-01", strtotime("$lastYear-$month-01"));
            $lastYearEnd = date("Y-m-t", strtotime("$lastYear-$month-01"));
            $dateCondLastYear = "AND activity_date BETWEEN '$lastYearStart' AND '$lastYearEnd'";

            // Fetch last year's data
            $sQueryLastYear = "SELECT SUM(total_sellin_shops) AS totalShopBilledLY FROM $vanDsSummaryTable WHERE dstatus = 0  $dateCondLastYear $where";
            $rsActionLastYear = null;
            $iRowsLastYear = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryLastYear, $rsActionLastYear, $iRowsLastYear);

            if ($iRowsLastYear > 0) {
                $rowLastYear = $this->_dbConn->GetData($rsActionLastYear);
                $cumulativeSumLastYear += (float)$rowLastYear['totalShopBilledLY'];
            }
            $lastYearData[$month] = $cumulativeSumLastYear; // Store cumulative sum for each month

            if ($month <= date("n")) {
                $thisYearStart = date("Y-m-01", strtotime("$currentYear-$month-01"));
                $thisYearEnd = date("Y-m-t", strtotime("$currentYear-$month-01"));
                // print_r($thisYearStart);die;
                $dateCondThisYear = "AND activity_date BETWEEN '$thisYearStart' AND '$thisYearEnd'";

                // Fetch this year's data
                $sQueryThisYear = "SELECT SUM(total_sellin_shops) AS totalShopBilledTY FROM $vanDsSummaryTable WHERE dstatus = 0  $dateCondThisYear $where";
                $rsActionThisYear = null;
                $iRowsThisYear = 0;
                $this->_dbConn->ExecuteSelectQuery($sQueryThisYear, $rsActionThisYear, $iRowsThisYear);

                if ($iRowsThisYear > 0) {
                    $rowThisYear = $this->_dbConn->GetData($rsActionThisYear);
                    $cumulativeSumThisYear += (float)$rowThisYear['totalShopBilledTY'];
                }
                $thisYearData[$month] = $cumulativeSumThisYear; // Store cumulative sum for each month up to the current month
            }
        }

        // Prepare data structure for chart
        $YTDLYTDBILLED = [
            'seriesData' => [
                ['data' => array_values($lastYearData)], // Full cumulative data for last year by month
                ['data' => array_slice(array_values($thisYearData), 0, date("n"))] // This year's cumulative data up to the current month
            ],
            'xAxisLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            "lastYear" => (string)$lastYear,
            "currentYear" => (string)$currentYear,
        ];

        return [
            "ytdlytdBilled" => $YTDLYTDBILLED,
            "height" => "800px"
        ];
    }

    // CM LM Focus Billed Data
    public function getFocusCMLMOutletBilledComparisonData()
    {
        $branchDataLastMonth = array_fill(1, 31, 0); // Cumulative count for last month
        $branchDataThisMonth = array_fill(1, 31, 0); // Cumulative count for this month
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month4");

        $branch = getFormData($this->_data, "branch");
        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond .= " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond .= " AND branch_id = $branch";
                }
            }
        }
        if (!empty($month)) {
            // Ensure the month includes the year, defaulting to the current year if not provided
            $year = date("Y"); // Default to the current year
            if (strpos($month, '-') !== false) {
                // If the input includes a year (e.g., "2024-11"), use it directly
                $year = explode('-', $month)[0];
            } else {
                // If only the month is provided (e.g., "11"), append the current year
                $month = "$year-$month";
            }
            $thisMonthStart = date("Y-m-01", strtotime($month)); // First day of the specified month
            $thisMonthEnd = date("Y-m-t", strtotime($month)); // Last day of the specified month
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));

            // Calculate the year for the last month
            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));
            $lastMonthStart = date("Y-m-01", $lastMonthTimestamp); // First day of the last month
            $lastMonthEnd = date("Y-m-t", $lastMonthTimestamp); // Last day of the last month
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Default to current month-to-date if no specific month is provided
            $thisMonthStart = date("Y-m-01"); // First day of the current month
            $thisMonthEnd = date("Y-m-d"); // Current date
            $lastMonthTimestamp = strtotime("first day of last month");
            // Calculate last month’s date range
            $lastMonthStart = date("Y-m-01", strtotime("first day of last month"));
            $lastMonthEnd = date("Y-m-t", strtotime("last day of last month"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        // Define last and current month conditions
        $dateCondLastMonth = "AND capture_date BETWEEN '$lastMonthStart' AND '$lastMonthEnd'";
        $dateCondThisMonth = "AND capture_date BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";

        $responseDetail = $this->_tables["RESPONSE_DETAILS_TABLE"];

        // Get focus brand columns
        $focusBrandCols = getRowsColumn($this->_dbConn, $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"], "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' $branchCond $categoryAndProductCond");
        if (!empty($focusBrandCols)) {
            $sumColumns = implode(") + SUM(", $focusBrandCols);
            $sumColumns = "SUM($sumColumns)";
            $cumulativeCountLastMonth = 0;
            $cumulativeCountThisMonth = 0;

            // Last Month Query - Count rows where sumColumns > 0
            $sQueryLastMonth = "SELECT COUNT(DISTINCT ques_3) as lastMonthCount, capture_date  FROM $responseDetail  WHERE dstatus = 0 AND ques_4 = 'Yes' $where  $dateCondLastMonth GROUP BY capture_date HAVING $sumColumns > 0  ORDER BY capture_date";
            $rsActionLastMonth = null;
            $iRowsLastMonth = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryLastMonth, $rsActionLastMonth, $iRowsLastMonth);

            if ($iRowsLastMonth > 0) {
                while ($rowLastMonth = $this->_dbConn->GetData($rsActionLastMonth)) {
                    $countForDate = (int)$rowLastMonth['lastMonthCount'];
                    $day = (int)date("d", strtotime($rowLastMonth['capture_date']));

                    $cumulativeCountLastMonth = $countForDate;
                    $branchDataLastMonth[$day] = $cumulativeCountLastMonth;
                }
            }

            // This Month Query - Count rows where sumColumns > 0
            $sQueryThisMonth = "SELECT COUNT(DISTINCT ques_3) as thisMonthCount, capture_date  FROM $responseDetail  WHERE dstatus = 0 AND ques_4 = 'Yes' $where  $dateCondThisMonth  GROUP BY capture_date  HAVING $sumColumns > 0  ORDER BY capture_date";
            $rsActionThisMonth = null;
            $iRowsThisMonth = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryThisMonth, $rsActionThisMonth, $iRowsThisMonth);

            if ($iRowsThisMonth > 0) {
                while ($rowThisMonth = $this->_dbConn->GetData($rsActionThisMonth)) {
                    $countForDate = (int)$rowThisMonth['thisMonthCount'];
                    $day = (int)date("d", strtotime($rowThisMonth['capture_date']));

                    $cumulativeCountThisMonth = $countForDate;
                    $branchDataThisMonth[$day] = $cumulativeCountThisMonth;
                }
            }
        }

        $this->calculateCumulativeSum($branchDataLastMonth);
        $this->calculateCumulativeSum($branchDataThisMonth);

        // Limit the current month's data to only up to the current day
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastMonth, 0, $maxDaysLastMonth, true);

        $focusbilledData = [
            'seriesData' => [
                ['data' => array_values($lastMonthSalesData)],
                ['data' => array_values($currentYearMonthSalesData)]
            ],
            'xAxisLabels' => range(1, max(31, $currentDay)),
            "lastMonthName" => date("F Y", $lastMonthTimestamp),  // Last month's name
            "currentMonthName" => date("F Y", strtotime($thisMonthStart)),  // Current month's name
        ];

        return [
            "focusbilledData" => $focusbilledData,
            "height" => "800px"
        ];
    }

    //  Focus SPLY Billed Data
    public function getFocusSPLYOutletBilledComparisonData()
    {
        $month = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "month5");
        // Adjust dates if a specific month is provided
        if (!empty($month)) {
            // Ensure the month includes the year, defaulting to the current year if not provided
            $year = date("Y"); // Default to the current year
            if (strpos($month, '-') !== false) {
                // If the input includes a year (e.g., "2024-11"), use it directly
                $year = explode('-', $month)[0];
            } else {
                // If only the month is provided (e.g., "11"), append the current year
                $month = "$year-$month";
            }

            // Calculate start and end dates for the current month of the specified year
            $thisMonthStart = date("Y-m-01", strtotime($month));
            $thisMonthEnd = date("Y-m-t", strtotime($month));
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            $lastMonthTimestamp = strtotime("-1 month", strtotime($thisMonthStart));

            // Calculate start and end dates for the same month in the previous year
            $lastYearStart = date("Y-m-01", strtotime("$month -1 year"));
            $lastYearEnd = date("Y-m-t", strtotime("$month -1 year"));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Set start and end dates for the current month of this year
            $thisMonthStart = date("Y-m-01"); // First day of current month, current year
            $thisMonthEnd = date("Y-m-t"); // Last day of current month, current year

            // Set start and end dates for the same month last year
            $lastYearStart = date("Y-m-01", strtotime("-1 year"));
            $lastYearEnd = date("Y-m-t", strtotime("-1 year"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }
        // Define last year's same month condition
        $dateCondLastYearMonth = "AND capture_date BETWEEN '$lastYearStart' AND '$lastYearEnd'";

        // Define this year's current month condition
        $dateCondThisMonth = "AND capture_date BETWEEN '$thisMonthStart' AND '$thisMonthEnd'";
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");

        // Table definitions
        $responseDetail = $this->_tables["RESPONSE_DETAILS_TABLE"];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

        // Initialize arrays for cumulative sums and row counts
        $branchDataLastYear = array_fill(1, 31, 0);
        $branchDataThisYear = array_fill(1, 31, 0);

        $branch = getFormData($this->_data, "branch");
        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond .= " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond .= " AND branch_id = $branch";
                }
            }
        }

        // Retrieve focus brand columns for the sum calculation
        $focusBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' $branchCond $categoryAndProductCond");
        if (!empty($focusBrandCols)) {
            $sumColumns = "SUM(" . implode(") + SUM(", $focusBrandCols) . ")";
            // Query for last year, same month
            $sQueryLastYear = "SELECT COUNT(DISTINCT ques_3) as lastYearFocus, capture_date FROM $responseDetail WHERE dstatus = 0 AND ques_4 = 'Yes' $where $dateCondLastYearMonth
            GROUP BY capture_date HAVING $sumColumns > 0 ORDER BY capture_date";
            $rsActionLastYear = null;
            $iRowsLastYear = 0;
            $cumulativeSumLastYear = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryLastYear, $rsActionLastYear, $iRowsLastYear);

            if ($iRowsLastYear > 0) {
                while ($rowLastYear = $this->_dbConn->GetData($rsActionLastYear)) {
                    $totalForDate = (float)$rowLastYear['lastYearFocus']; // Use 'lastYearFocus' alias here
                    $day = (int)date("d", strtotime($rowLastYear['capture_date']));

                    $cumulativeSumLastYear = $totalForDate;
                    $branchDataLastYear[$day] = $cumulativeSumLastYear;
                }
            }

            // Query for this year, current month
            $sQueryThisYear = "SELECT COUNT(DISTINCT ques_3) as thisYearFocus, capture_date FROM $responseDetail WHERE dstatus = 0 AND ques_4 = 'Yes' $where $dateCondThisMonth
            GROUP BY capture_date HAVING $sumColumns > 0 ORDER BY capture_date";
            $rsActionThisYear = null;
            $iRowsThisYear = 0;
            $cumulativeSumThisYear = 0;
            $this->_dbConn->ExecuteSelectQuery($sQueryThisYear, $rsActionThisYear, $iRowsThisYear);

            if ($iRowsThisYear > 0) {
                while ($rowThisYear = $this->_dbConn->GetData($rsActionThisYear)) {
                    $totalForDate = (float)$rowThisYear['thisYearFocus']; // Use 'thisYearFocus' alias here
                    $day = (int)date("d", strtotime($rowThisYear['capture_date']));

                    $cumulativeSumThisYear = $totalForDate;
                    $branchDataThisYear[$day] = $cumulativeSumThisYear;
                }
            }
        }

        // Calculate cumulative sums for both years
        $this->calculateCumulativeSum($branchDataLastYear);
        $this->calculateCumulativeSum($branchDataThisYear);

        // Limit the current month's data to only up to the current day
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisYear, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisYear, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastYear, 0, $maxDaysLastMonth, true);

        // Return data structured for charting or further processing
        $focusSPLYbilledData = [
            'seriesData' => [
                ['data' => array_values($lastMonthSalesData)],
                ['data' => array_values($currentYearMonthSalesData)]
            ],
            'xAxisLabels' => range(1, max(31, $currentDay)),
            "lastYearMonthName" => date("F Y", strtotime($lastYearStart)),  // Last month's name
            "currentYearMonthName" => date("F Y", strtotime($thisMonthStart)),  // Current month's name
        ];

        return [
            "focusSPLYbilledData" => $focusSPLYbilledData,
            "height" => "800px"
        ];
    }
}
