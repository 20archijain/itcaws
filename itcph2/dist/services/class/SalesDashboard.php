<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class SalesDashboard
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
    }

    final public function getData()
    {
        // $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        // $user_id = $this->_iUserId;
        // $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", " user_id = $user_id");
        // if ($groupId == 1 || $groupId == 2) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } elseif ($groupId == 4) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $branchFilter = false;
        // }
        // $where = "";
        // $teamList = $this->_arrAccessInfo["user_teams"];
        // if ($teamList) {
        //     $where .= " AND team_id IN $teamList";
        // }
        $arrResult = array(
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
            "branchFilter" => true,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition($andCondition = true)
    {
        $teamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $condition = "";
        $district = getFormData($this->_data, "district");
        if ($district) {
            if (!is_array($district)) {
                $district = array($district);
            }
            if (in_array('all', $district)) {
                $condition .= " ";
            } else {
                $district = "'" . implode("','", $district) . "'";
                $condition .= " AND d.district IN ($district)";
            }
        }
        $branch = getFormData($this->_data, "branch");
        if ($branch) {
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            if (in_array('all', $branch)) {
                $condition .= " ";
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $condition .= " AND d.branch_id IN ($branch)";
            }
        }
        $circle = getFormData($this->_data, "circle");
        if ($circle) {
            if (!is_array($circle)) {
                $circle = array($circle);
            }
            if (in_array('all', $circle)) {
                $condition .= " ";
            } else {
                $circle = "'" . implode("','", $circle) . "'";
                $condition .= " AND b.circle IN ($circle)";
            }
        }
        $section = getFormData($this->_data, "section");
        if ($section) {
            if (!is_array($section)) {
                $section = array($section);
            }
            if (in_array('all', $section)) {
                $condition .= " ";
            } else {
                $section = "'" . implode("','", $section) . "'";
                $condition .= " AND b.section IN ($section)";
            }
        }
        $wdCode = getFormData($this->_data, "wdCode");
        if ($wdCode) {
            if (!is_array($wdCode)) {
                $wdCode = array($wdCode);
            }
            if (in_array('all', $wdCode)) {
                $condition .= " ";
            } else {
                $wdCode = "'" . implode("','", $wdCode) . "'";
                $condition .= " AND b.wd_code IN ($wdCode)";
            }
        }
        $wdMarket = getFormData($this->_data, "wdMarket");
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
        $wdPopGroup = getFormData($this->_data, "wdPopGroup");
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
        $teamType = getFormData($this->_data, "dsType");
        if ($teamType) {
            if (!is_array($teamType)) {
                $teamType = array($teamType);
            }
            if (in_array('all', $teamType)) {
                $condition .= " ";
            } else {
                $teamType = "'" . implode("','", $teamType) . "'";
                $condition .= " AND b.is_type IN ($teamType)";
            }
        }

        $dsName = getFormData($this->_data, "dsName");
        if ($dsName) {
            if (!is_array($dsName)) {
                $dsName = array($dsName);
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
            $where .= " AND team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND e.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
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
        $category = getFormData($this->_data, "category");
        if ($category) {
            if (!is_array($category)) {
                $category = array($category);
            }
            if (in_array('all', $category)) {
                $condition .= " ";
            } else {
                $category = "'" . implode("','", $category) . "'";
                $condition .= " AND $category_name IN ($category)";
            }
        }
        $product = getFormData($this->_data, "product");
        if ($product) {
            if (!is_array($product)) {
                $product = array($product);
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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.branch_name";
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

    final public function getCategoryList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
                $arrData[] = array(
                    "label" => $row['category_name'],
                    "value" => $row['category_name']
                );
            }
        }

        return $arrData;
    }


    final public function getProductList($cond = "")
    {
        $arrData = array();
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
                $arrData[] = array(
                    "label" => $row['product_name'],
                    "value" => $row['product_name']
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
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.circle";
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
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.section";
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
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.wd_code";
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
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.wd_market";
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
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.wd_pop_group";
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.team_name";
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
                "categoryList" => $this->getCategoryList($districtCond),
                "productList" => $this->getProductList($districtCond),
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
                "categoryList" => "",
                "productList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getProduct()
    {
        $category = $this->_data['category'];
        $categoryCond = "";
        if (!empty($category)) {
            if (!is_array($category)) {
                $category = array($category);
            }
            if (in_array('all', $category)) {
                $categoryCond = ""; // No condition for 'all'
            } else {
                $category = "'" . implode("','", $category) . "'";
                $categoryCond = " AND b.category_name IN ($category)";
            }

            $arrResult = array(
                "productList" => $this->getProductList($categoryCond),
            );
        } else {
            $arrResult = array(
                "productList" => "",
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
                "categoryList" => $this->getCategoryList($branchCond),
                "productList" => $this->getProductList($branchCond),
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
                "categoryList" => "",
                "productList" => "",
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

    final public function getCardData()
    {
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $currentDate = currentDate();
        // Set default start and end dates for current month-to-date
        $startDate = date("Y-m-01"); // First day of the current month
        // $branch = getFormData($this->_data, "branch");
        // $branchCond = "";
        // if ($branch) {
        //     $matchAll = checkIfAllSelected($branch);
        //     if (!$matchAll) {
        //         if (isNonEmptyArray($branch)) {
        //             $branchIds = implode(",", $branch);
        //             $branchCond .= " AND branch_id IN ($branchIds)";
        //         } else {
        //             $branchCond .= " AND branch_id = $branch";
        //         }
        //     }
        // }
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
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";

        // Process current Date data
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date = '$currentDate' $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        $todayTotalSale = 0;
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $todayTotalSale += $row['totalSum'];
            }
        }

        // Process current month data
        $sAction2 = null;
        $iRows2 = 0;
        $sQuery2 = "SELECT $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date BETWEEN '$startDate' AND '$currentDate' $where";
        $mtdTotalSale = 0;
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
        if ($iRows2 > 0) {
            while ($row2 = $this->_dbConn->GetData($sAction2)) {
                $mtdTotalSale += $row2['totalSum'];
            }
        }

        $tillSaleAmount = getRowsColumn($this->_dbConn, "$summaryTable", "SUM(netAmount) AS total", "dstatus = 0 AND activity_date BETWEEN '$startDate' AND '$currentDate' $where");
        $todaySaleAmount = getRowsColumn($this->_dbConn, "$summaryTable", "SUM(netAmount) AS total", "dstatus = 0 AND activity_date = '$currentDate' $where");

        // Convert amounts to crore
        $tillSaleAmountInCrore = $tillSaleAmount[0] / 10000000;
        $todaySaleAmountInCrore = $todaySaleAmount[0] / 10000000;

        $arrResult = array(
            "todaySaleDone" => round($todayTotalSale, 1),
            "todaySaleAmount" => number_format($todaySaleAmountInCrore, 2) . " cr", // Format to 2 decimal places
            "tillDateSaleDone" => round($mtdTotalSale, 1),
            "tillDateSaleAmount" => number_format($tillSaleAmountInCrore, 2)  . " cr", // Format to 2 decimal places
            "currentAndLastMonthData" => $this->getCurrentAndLastMonthSales(),
            "currentMonthVsLastYearMonthSales" => $this->getCurrentMonthVsLastYearMonthSales(),
            "currentYearLastYearMonthlySales" => $this->getCurrentYearLastYearMonthlySales(),
            "currentAndLastMonthFocusData" => $this->getCurrentAndLastMonthFocusSales(),
            "currentMonthVsLastYearMonthFocusSales" => $this->getCurrentMonthVsLastYearMonthFocusSales(),
            "currentYearLastYearMonthlyFocusSales" => $this->getCurrentYearLastYearMonthlyFocusSales(),
            "monthlySalesData" => $this->getMonthlySalesData(),
            // "outletVisitedTableData" => $this->getOutletVisitedTableData(),
            "chartsData" => $this->getChartData(),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    public function getChartData()
    {
        return array(
            // "cigSaleCategoryWise" => $this->getcigSaleCategoryWise(),
        );
    }

    // Function to calculate cumulative sum for each day
    final public function calculateCumulativeAmountSum(&$dataArray)
    {
        for ($day = 2; $day <= 31; $day++) {
            // Check if the previous day exists before adding
            if (isset($dataArray[$day - 1])) {
                $dataArray[$day] += $dataArray[$day - 1];
            }
        }
    }

    // Calculate cumulative sums for each period
    final public function calculateCumulativeSum(&$dataArray)
    {
        for ($month = 1; $month < count($dataArray); $month++) {
            $dataArray[$month] += $dataArray[$month - 1];
        }
    }

    final public function getCurrentAndLastMonthSales()
    {
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branch = getFormData($this->_data, "branch");
        $month = getFormData($this->_data, "month");

        // Ensure every day key exists in the arrays
        $branchDataLastMonth = array_fill(1, 31, 0);
        $branchDataThisMonth = array_fill(1, 31, 0);

        // Adjust current dates if a specific month is provided
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
            $currentStartDate = date("Y-m-01", strtotime($month)); // First day of the specified month
            $currentEndDate = date("Y-m-t", strtotime($month)); // Last day of the specified month
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            // Calculate the year for the last month
            $lastMonthTimestamp = strtotime("-1 month", strtotime($currentStartDate));
            $lastStartDate = date("Y-m-01", $lastMonthTimestamp); // First day of the last month
            $lastEndDate = date("Y-m-t", $lastMonthTimestamp); // Last day of the last month
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Default to current month-to-date if no specific month is provided
            $currentStartDate = date("Y-m-01"); // First day of the current month
            $currentEndDate = date("Y-m-d"); // Current date
            $maxDaysThisMonth = (int)date("t"); // Current month's max days

            // Calculate last month’s date range
            $lastMonthTimestamp = strtotime("first day of last month");
            $lastStartDate = date("Y-m-01", $lastMonthTimestamp);
            $lastEndDate = date("Y-m-t", $lastMonthTimestamp);
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Previous month's max days
        }

        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");

        $sAction = null;
        $iRows = 0;
        $branch = array();

        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";

        // Process current month data
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date BETWEEN '$currentStartDate' AND '$currentEndDate' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];

                $branchDataThisMonth[$day] += $totalProductSale; // Set total sales for the day only
            }
        }

        // Process last month data
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date BETWEEN '$lastStartDate' AND '$lastEndDate' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];

                $branchDataLastMonth[$day] += $totalProductSale;
            }
        }

        $this->calculateCumulativeAmountSum($branchDataLastMonth);
        $this->calculateCumulativeAmountSum($branchDataThisMonth);

        $currentYearMonth = date("Y-m");

        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentMonthSalesData = array_slice($branchDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentMonthSalesData = array_slice($branchDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastMonth, 0, $maxDaysLastMonth, true);

        // Return the result
        return [
            "seriesData" => [
                ["data" => array_values($lastMonthSalesData)],  // Last month
                ["data" => array_values($currentMonthSalesData)]  // Current month up to the current day
            ],
            "xAxisLabels" => range(1, max(31, $currentDay)),
            "lastMonthName" => date("F Y", $lastMonthTimestamp),  // Last month's name
            "currentMonthName" => date("F Y", strtotime($currentStartDate)),  // Current month's name
            "title" => "Cumulative Sales Comparison: Last Month vs This Month",
            "height" => "800px"
        ];
    }

    final public function getCurrentAndLastMonthFocusSales()
    {
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branch = getFormData($this->_data, "branch");
        $month = getFormData($this->_data, "month1");

        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");

        // Ensure every day key exists in the arrays
        $branchDataLastMonth = array_fill(1, 31, 0);
        $branchDataThisMonth = array_fill(1, 31, 0);

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
            $currentStartDate = date("Y-m-01", strtotime($month)); // First day of the specified month
            $currentEndDate = date("Y-m-t", strtotime($month)); // Last day of the specified month
            $maxDaysThisMonth = (int)date("t", strtotime($month)); // Max days in the selected month

            // Calculate the year for the last month
            $lastMonthTimestamp = strtotime("-1 month", strtotime($currentStartDate));
            $lastStartDate = date("Y-m-01", $lastMonthTimestamp); // First day of the last month
            $lastEndDate = date("Y-m-t", $lastMonthTimestamp); // Last day of the last month
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Default to current month-to-date if no specific month is provided
            $currentStartDate = date("Y-m-01"); // First day of the current month
            $currentEndDate = date("Y-m-d"); // Current date

            $lastMonthTimestamp = strtotime("first day of last month");
            // Calculate last month’s date range
            $lastStartDate = date("Y-m-01", strtotime("first day of last month"));
            $lastEndDate = date("Y-m-t", strtotime("last day of last month"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }

        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = 1 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date BETWEEN '$currentStartDate' AND '$currentEndDate' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];

                $branchDataThisMonth[$day] += $totalProductSale; // Set total sales for the day only
            }
        }

        // Process last month data
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
            " AND activity_date BETWEEN '$lastStartDate' AND '$lastEndDate' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];

                $branchDataLastMonth[$day] += $totalProductSale;
            }
        }

        $this->calculateCumulativeAmountSum($branchDataLastMonth);
        $this->calculateCumulativeAmountSum($branchDataThisMonth);

        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentMonthSalesData = array_slice($branchDataThisMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentMonthSalesData = array_slice($branchDataThisMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastMonth, 0, $maxDaysLastMonth, true);

        // Return the result
        return [
            "seriesData" => [
                ["data" => array_values($lastMonthSalesData)],  // Last month
                ["data" => array_values($currentMonthSalesData)]  // Current month up to the current day
            ],
            "xAxisLabels" => range(1, max(31, $currentDay)),
            "focusLastMonthName" => date("F Y", $lastMonthTimestamp),  // Last month's name
            "focusCurrentMonthName" => date("F Y", strtotime($currentStartDate)),  // Current month's name
            "title" => "Cumulative Sales Comparison: Last Month vs This Month",
            "height" => "800px"
        ];
    }

    final public function getCurrentMonthVsLastYearMonthSales()
    {
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branch = getFormData($this->_data, "branch");
        $month = getFormData($this->_data, "month2");
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
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

            $lastMonthTimestamp = strtotime("-1 month", strtotime($currentYearMonthStart));

            // Calculate start and end dates for the same month in the previous year
            $lastYearMonthStart = date("Y-m-01", strtotime("$month -1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("$month -1 year"));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Set start and end dates for the current month of this year
            $currentYearMonthStart = date("Y-m-01"); // First day of current month, current year
            $currentYearMonthEnd = date("Y-m-t"); // Last day of current month, current year
            $lastMonthTimestamp = strtotime("first day of last month");
            // Set start and end dates for the same month last year
            $lastYearMonthStart = date("Y-m-01", strtotime("-1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("-1 year"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }

        // Ensure every day key exists in the arrays
        $branchDataLastYearMonth = array_fill(1, 31, 0);
        $branchDataThisYearMonth = array_fill(1, 31, 0);

        // Branch condition setup
        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }
        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0 AND activity_date BETWEEN '$currentYearMonthStart' AND '$currentYearMonthEnd'" .
            " $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];
                $branchDataThisYearMonth[$day] += $totalProductSale;
            }
        }

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0 AND activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];
                $branchDataLastYearMonth[$day] += $totalProductSale;
            }
        }

        $this->calculateCumulativeAmountSum($branchDataLastYearMonth);
        $this->calculateCumulativeAmountSum($branchDataThisYearMonth);

        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisYearMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisYearMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastYearMonth, 0, $maxDaysLastMonth, true);

        return [
            "seriesData" => [
                ["data" => array_values($lastMonthSalesData)],  // Same month last year
                ["data" => array_values($currentYearMonthSalesData)]  // Current month this year
            ],
            "xAxisLabels" => range(1, max(31, $currentDay)),
            "lastYearMonthName" => date("F Y", strtotime($lastYearMonthStart)),  // Last month's name
            "currentYearMonthName" => date("F Y", strtotime($currentYearMonthStart)),  // Current month's name
            "title" => "Daily Sales Comparison: Same Month Last Year vs This Year",
            "height" => "800px"
        ];
    }

    final public function getCurrentMonthVsLastYearMonthFocusSales()
    {
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branch = getFormData($this->_data, "branch");
        $month = getFormData($this->_data, "month3");
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
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

            $lastMonthTimestamp = strtotime("-1 month", strtotime($currentYearMonthStart));

            // Calculate start and end dates for the same month in the previous year
            $lastYearMonthStart = date("Y-m-01", strtotime("$month -1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("$month -1 year"));
            $maxDaysLastMonth = (int)date("t", $lastMonthTimestamp); // Max days in the previous month
        } else {
            // Set start and end dates for the current month of this year
            $currentYearMonthStart = date("Y-m-01"); // First day of current month, current year
            $currentYearMonthEnd = date("Y-m-t"); // Last day of current month, current year
            $lastMonthTimestamp = strtotime("first day of last month");
            // Set start and end dates for the same month last year
            $lastYearMonthStart = date("Y-m-01", strtotime("-1 year"));
            $lastYearMonthEnd = date("Y-m-t", strtotime("-1 year"));
            $maxDaysThisMonth = (int)date("t"); // Current month's max days
            $maxDaysLastMonth = (int)date("t", strtotime("last day of last month")); // Previous month's max days
        }
        // Ensure every day key exists in the arrays
        $branchDataLastYearMonth = array_fill(1, 31, 0);
        $branchDataThisYearMonth = array_fill(1, 31, 0);

        // Branch condition setup
        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }

        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = 1 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0 AND activity_date BETWEEN '$currentYearMonthStart' AND '$currentYearMonthEnd'" .
            "$where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];
                $branchDataThisYearMonth[$day] += $totalProductSale;
            }
        }

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0 AND activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd' $where GROUP BY activity_date";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $day = (int)date("d", strtotime($row['activity_date']));
                $totalProductSale = 0;
                $totalProductSale += $row['totalSum'];
                $branchDataLastYearMonth[$day] += $totalProductSale;
            }
        }

        $this->calculateCumulativeAmountSum($branchDataLastYearMonth);
        $this->calculateCumulativeAmountSum($branchDataThisYearMonth);

        // Get the current day of the month
        $currentYearMonth = date("Y-m");
        // Check if the provided month matches the current month
        $isCurrentMonth = empty($month) || $month === $currentYearMonth;

        // Slice the current month's sales data up to the current day if it's the current month
        $currentDay = (int)date('j'); // Get the current day of the month
        if ($isCurrentMonth) {
            $currentYearMonthSalesData = array_slice($branchDataThisYearMonth, 0, $currentDay, true); // Retain keys
        } else {
            $currentYearMonthSalesData = array_slice($branchDataThisYearMonth, 0, $maxDaysThisMonth, true); // Use the full array
        }
        $lastMonthSalesData = array_slice($branchDataLastYearMonth, 0, $maxDaysLastMonth, true);

        return [
            "seriesData" => [
                ["data" => array_values($lastMonthSalesData)],  // Same month last year
                ["data" => array_values($currentYearMonthSalesData)]  // Current month this year
            ],
            "xAxisLabels" => range(1, max(31, $currentDay)),
            "lastYearFocusMonthName" => date("F Y", strtotime($lastYearMonthStart)),  // Last month's name
            "currentYearFocusMonthName" => date("F Y", strtotime($currentYearMonthStart)),  // Current month's name
            "title" => "Daily Sales Comparison: Same Month Last Year vs This Year",
            "height" => "800px"
        ];
    }

    final public function getCurrentYearLastYearMonthlySales()
    {
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branch = getFormData($this->_data, "branch");
        $year = getFormData($this->_data, "year");
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        if (!empty($year)) {
            $currentYear = $year;
        } else {
            $currentYear = date('Y');
        }
        $lastYear = $currentYear - 1;

        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }

        $sAction = null;
        $iRows = 0;
        $currentYearSales = array_fill(0, 12, 0);  // Monthly cumulative sales for current year
        $lastYearSales = array_fill(0, 12, 0);     // Monthly cumulative sales for last year

        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";
        foreach (range(1, 12) as $loopMonth) {
            $currentYearMonthStart = date("$currentYear-$loopMonth-01");
            $currentYearMonthEnd = date("$currentYear-$loopMonth-t");

            $lastYearMonthStart = date("$lastYear-$loopMonth-01");
            $lastYearMonthEnd = date("$lastYear-$loopMonth-t");

            // Fetch current year monthly sales
            $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
                " $where AND activity_date BETWEEN '$currentYearMonthStart' AND '$currentYearMonthEnd' GROUP BY MONTH(activity_date)";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($sAction)) {
                    $resultMonth = (int)date("m", strtotime($row['activity_date']));
                    $monthlyProductSale = 0;
                    $monthlyProductSale += $row['totalSum'];
                    $currentYearSales[$resultMonth - 1] += $monthlyProductSale; // Corrected index
                }
            }

            // Fetch last year monthly sales
            $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
                " $where AND activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd' GROUP BY MONTH(activity_date)";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($sAction)) {
                    $resultMonth = (int)date("m", strtotime($row['activity_date']));
                    $monthlyProductSale = 0;
                    $monthlyProductSale += $row['totalSum'];
                    $lastYearSales[$resultMonth - 1] += $monthlyProductSale; // Corrected index
                }
            }
        }


        $this->calculateCumulativeSum($lastYearSales);
        $this->calculateCumulativeSum($currentYearSales);

        $xAxisLabels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        $currentMonth = (int)date('m');
        // Slice the current month's sales data up to the current day
        $currentYearSalesData = array_slice($currentYearSales, 0, $currentMonth);
        return [
            "seriesData" => [
                ["data" => $lastYearSales],  // Last year
                ["data" => $currentYearSalesData]  // Current year
            ],
            "xAxisLabels" => $xAxisLabels,
            "lastYear" => (string)$lastYear,
            "currentYear" => (string)$currentYear,
            "title" => "Monthly Sales Comparison: Last Year vs This Year",
            "height" => "800px"
        ];
    }

    final public function getCurrentYearLastYearMonthlyFocusSales()
    {
        $branchPickupStockTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $summaryTable = $GLOBALS['TABLES']['VANDS_SUMMARY_TABLE'];
        $branch = getFormData($this->_data, "branch");
        $year = getFormData($this->_data, "year1");
        $where = $this->getCondition(true);
        $categoryAndProductCond = $this->getConditionForCategoryAndProduct("category_name", "product_name");
        if (!empty($year)) {
            $currentYear = $year;
        } else {
            $currentYear = date('Y');
        }
        $lastYear = $currentYear - 1;

        $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = implode(",", $branch);
                    $branchCond = " AND branch_id IN ($branchIds)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                }
            }
        }

        $sAction = null;
        $iRows = 0;
        $currentYearSales = array_fill(0, 12, 0);  // Monthly cumulative sales for current year
        $lastYearSales = array_fill(0, 12, 0);     // Monthly cumulative sales for last year
        $allBrandCols = getRowsColumn($this->_dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = 1 $branchCond $categoryAndProductCond", array(), true);
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";
        foreach (range(1, 12) as $month) {
            $currentYearMonthStart = date("$currentYear-$month-01");
            $currentYearMonthEnd = date("$currentYear-$month-t");

            $lastYearMonthStart = date("$lastYear-$month-01");
            $lastYearMonthEnd = date("$lastYear-$month-t");

            // Fetch current year monthly sales
            $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
                " $where AND activity_date BETWEEN '$currentYearMonthStart' AND '$currentYearMonthEnd' GROUP BY MONTH(activity_date)";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($sAction)) {
                    $month = (int)date("m", strtotime($row['activity_date']));
                    $monthlyProductSale = 0;
                    $monthlyProductSale += $row['totalSum'];
                    $currentYearSales[$month - 1] += $monthlyProductSale;
                }
            }

            // Fetch last year monthly sales
            $sQuery = "SELECT activity_date, $sumColumns AS totalSum FROM $summaryTable WHERE dstatus = 0" .
                " $where AND activity_date BETWEEN '$lastYearMonthStart' AND '$lastYearMonthEnd' GROUP BY MONTH(activity_date)";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($sAction)) {
                    $month = (int)date("m", strtotime($row['activity_date']));
                    $monthlyProductSale = 0;
                    $monthlyProductSale += $row['totalSum'];
                    $lastYearSales[$month - 1] += $monthlyProductSale;
                }
            }
        }

        $this->calculateCumulativeSum($lastYearSales);
        $this->calculateCumulativeSum($currentYearSales);
        $xAxisLabels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        $currentMonth = (int)date('m');
        // Slice the current month's sales data up to the current day
        $currentYearSalesData = array_slice($currentYearSales, 0, $currentMonth);
        return [
            "seriesData" => [
                ["data" => $lastYearSales],  // Last year
                ["data" => $currentYearSalesData]  // Current year
            ],
            "xAxisLabels" => $xAxisLabels,
            "focusLastYear" => (string)$lastYear,
            "focusCurrentYear" => (string)$currentYear,
            "title" => "Monthly Sales Comparison: Last Year vs This Year",
            "height" => "800px"
        ];
    }

    public function getMonthlySalesData()
    {
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $where = $this->getCondition(false);
        // if ($where) {
        //     $where = str_replace(" team_id", " b.team_id", $where);
        // }
        $arrResponse = array();
        $arrSalesColumns = array(); // Reset the sales columns array here
        $arrMonthYear = array();
        $monthWiseSales = array();
        $totalSumBranchLevelSale = array();

        // Get all sales-related columns
        $query = "SELECT a.summary_column_name, a.branch_id, b.team_id, b.circle, b.section, b.wd_code FROM tblbranch_pickupstock_products AS a, tblproject_team AS b, $branchTable as d, $mappingTable as e" .
            " WHERE b.branch_id = d.branch_id AND b.wd_code = e.wd_code AND a.dstatus = 0 $where GROUP BY a.branch_id, a.summary_column_name";

        $rsAction = null;
        $iActionRows = 0;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $branch_id = $row["branch_id"];
                $circle = $row["circle"];
                $section = $row["section"];
                $summary_column_name = $row["summary_column_name"];
                $arrSalesColumns[$branch_id][] = $summary_column_name;  // Store sales columns by branch
                $BranchIds[] = $branch_id;
            }

            // Generate Month-Year data for the last 12 months
            $arrMonthYear = [];
            $baseDate = new DateTimeImmutable(date('Y-m-01')); // Always start at the 1st of the current month
            for ($i = 0; $i < 12; $i++) {
                $date = $baseDate->modify("-$i months");
                $monthYear = $date->format('F Y');
                $arrMonthYear[] = $monthYear;
            }

            // Process sales data per branch and month
            foreach ($arrSalesColumns as $branch_id => $salesColumns) {
                // Reset $arrSalesColumns for the next branch
                $sSalesColumns = implode("+", $salesColumns);  // Create sum for current branch
                foreach ($arrMonthYear as $monthYear) {
                    // Get Monthly Sales Data
                    $queryNew = "SELECT SUM($sSalesColumns) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, DATE_FORMAT(a.activity_date, '%M %Y') AS monthYear FROM tblvands_summary AS a, tblproject_team AS b, $branchTable as d, $mappingTable as e WHERE a.team_id = b.team_id" .
                        " AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' AND DATE_FORMAT(a.activity_date, '%M %Y') = '$monthYear' AND b.branch_id = $branch_id AND b.branch_id = d.branch_id" .
                        " AND b.wd_code = e.wd_code $where GROUP BY monthYear, b.branch_id, b.circle, b.section, b.wd_code ORDER BY b.branch_id, b.circle, b.section, b.wd_code";

                    $rsAction1 = null;
                    $iActionRows1 = 0;
                    $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);

                    if ($iActionRows1 > 0) {
                        while ($row1 = $this->_dbConn->GetData($rsAction1)) {
                            $branch_id = $row1['branch_id'];
                            $branch_name = getRowColumn($this->_dbConn, $this->_tables["BRANCH_TABLE"], "main_branch", "branch_id = $branch_id");
                            $circle = $row1['circle'];
                            $section = $row1['section'];
                            $wd_code = $row1['wd_code'];
                            $totalSales = round($row1['totalSales'] ? (float) $row1['totalSales'] : 0, 2);  // Round to 2 decimal places

                            // Branch Level Sales
                            $branchIndex = array_search($branch_id, array_column($monthWiseSales, "branch_id"));
                            if ($branchIndex === false) {
                                $monthWiseSales[] = array(
                                    "branch_id" => $branch_id,
                                    "branch_name" => $branch_name,
                                    "branchLevelSale" => array(),
                                    "circleData" => array()
                                );
                                $branchIndex = count($monthWiseSales) - 1;
                            }
                            $monthWiseSales[$branchIndex]["branchLevelSale"][$monthYear] =
                                round(($monthWiseSales[$branchIndex]["branchLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                            // Circle Level Sales
                            $circleIndex = array_search($circle, array_column($monthWiseSales[$branchIndex]["circleData"], "circle"));
                            if ($circleIndex === false) {
                                $monthWiseSales[$branchIndex]["circleData"][] = array(
                                    "circle" => $circle,
                                    "circleLevelSale" => array(),
                                    "sectionData" => array()
                                );
                                $circleIndex = count($monthWiseSales[$branchIndex]["circleData"]) - 1;
                            }
                            $monthWiseSales[$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$monthYear] =
                                round(($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                            // Section Level Sales
                            $sectionIndex = array_search($section, array_column($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"], "section"));
                            if ($sectionIndex === false) {
                                $monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][] = array(
                                    "section" => $section,
                                    "sectionLevelSale" => array(),
                                    "wdData" => array(),
                                );
                                $sectionIndex = count($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"]) - 1;
                            }
                            $monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$monthYear] =
                                round(($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                            // WD Level Sales
                            $WDIndex = array_search($wd_code, array_column($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"], "wd_code"));
                            if ($WDIndex === false) {
                                $monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][] = array(
                                    "wd_code" => $wd_code,
                                    "wdCode" => $wd_code,
                                    "wdLevelSale" => array(),
                                );
                                $WDIndex = count($monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"]) - 1;
                            }
                            $monthWiseSales[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$monthYear] = round($totalSales, 2); // Round WD level sales
                        }
                    }
                }
            }

            // Calculate Total Sales at Branch Level
            foreach ($monthWiseSales as $branch) {
                foreach ($branch["branchLevelSale"] as $monthYear => $value) {
                    if (!isset($totalSumBranchLevelSale[$monthYear])) {
                        $totalSumBranchLevelSale[$monthYear] = 0;
                    }
                    $totalSumBranchLevelSale[$monthYear] = round($totalSumBranchLevelSale[$monthYear] + $value, 2);
                }
            }
        }

        // Response Data
        $arrResponse = array(
            "MonthsAndYears" => $arrMonthYear,
            "BranchData" => $monthWiseSales,
            "TotalSum" => $totalSumBranchLevelSale,
            "Title" => "Monthly Survey in M (Cigarettes)"
        );

        return $arrResponse;
    }


    // public function getcigSaleCategoryWise()
    // {
    //     $where = "";
    //     $currentDateTime = currentDateTime();
    //     $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

    //     $branch = getFormData($this->_data, "branch");
    //     $circle = getFormData($this->_data, "circle");
    //     $section = getFormData($this->_data, "section");
    //     $wdCode = getFormData($this->_data, "wdCode");
    //     $dsType = getFormData($this->_data, "dsType");
    //     $dsName = getFormData($this->_data, "dsName");

    //     $summaryTable = "tblvands_summary";
    //     $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
    //     $branchTable = $this->_tables["BRANCH_TABLE"];
    //     $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
    //     $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
    //     $Cond = "";
    //     $teamTypeCond = "";
    //     if ($dsType) {
    //         $teamTypeCond .= " AND team_type = $dsType";
    //         $Cond .= " AND b.is_type = $dsType";
    //     }

    //     if ($branch) {
    //         $matchAll = checkIfAllSelected($branch);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($branch)) {
    //                 $branchs = "'" . implode("','", $branch) . "'";
    //                 $branchCond = " AND branch_id IN ($branchs)";
    //                 $Cond .= " AND b.branch_id IN ($branchs)";
    //             } else {
    //                 $branchCond = " AND branch_id = $branch";
    //                 $Cond .= " AND b.branch_id = $branch";
    //             }
    //         } else {
    //             $branch = $this->getBranch();
    //         }
    //     } else {
    //         $branch = $this->getBranch();
    //     }

    //     $circleCond = "";
    //     if ($circle) {
    //         $matchAll = checkIfAllSelected($circle);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($circle)) {
    //                 $circles = "'" . implode("','", $circle) . "'";
    //                 $circleCond = " AND circle IN ($circles)";
    //                 $Cond .= " AND b.circle IN ($circles)";
    //             } else {
    //                 $circleCond = " AND circle = $circle";
    //                 $Cond .= " AND b.circle = $circle";
    //             }
    //         }
    //     }

    //     $sectionCond = "";
    //     if ($section) {
    //         $matchAll = checkIfAllSelected($section);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($section)) {
    //                 $sections = "'" . implode("','", $section) . "'";
    //                 $sectionCond = " AND section IN ($sections)";
    //                 $Cond .= " AND b.section IN ($sections)";
    //             } else {
    //                 $sectionCond = " AND section = $section";
    //                 $Cond .= " AND b.section = $section";
    //             }
    //         }
    //     }

    //     $wdCodeCond = "";
    //     if ($wdCode) {
    //         $matchAll = checkIfAllSelected($wdCode);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($wdCode)) {
    //                 $wdCodes = "'" . implode("','", $wdCode) . "'";
    //                 $wdCodeCond = " AND wd_code IN ($wdCodes)";
    //                 $Cond .= " AND b.wd_code IN ($wdCodes)";
    //             } else {
    //                 $wdCodeCond = " AND wd_code = $wdCode";
    //                 $Cond .= " AND b.wd_code = $wdCode";
    //             }
    //         }
    //     }

    //     $dsNameCond = "";
    //     if ($dsName) {
    //         $matchAll = checkIfAllSelected($dsName);
    //         if (!$matchAll) {
    //             if (isNonEmptyArray($dsName)) {
    //                 $dsNames = "'" . implode("','", $dsName) . "'";
    //                 $dsNameCond = " AND team_id IN ($dsNames)";
    //                 $Cond .= " AND b.team_id IN ($dsNames)";
    //             } else {
    //                 $dsNameCond = " AND team_id = $dsName";
    //                 $Cond .= " AND b.team_id = $dsName";
    //             }
    //         }
    //     }

    //     $allCond = "";
    //     if ($Cond) {
    //         $allCond .= " AND b.team_id IN (SELECT team_id FROM $projectTeamTable WHERE dstatus = 0  $Cond)";
    //     }

    //     $arrSalesData = [];

    //     $sProductQuery = "SELECT DISTINCT a.product_name, a.summary_column_name, a.category_name FROM $branchProductsTable as a, tblproject_team AS b WHERE a.branch_id = b.branch_id $allCond AND a.dstatus = 0 AND b.dstatus = 0 ORDER BY a.product_name";

    //     $sProductAction = null;
    //     $iProductRows = 0;
    //     $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

    //     if ($iProductRows > 0) {
    //         $summaryColName = [];
    //         $productNames = [];
    //         $categorySummaryMap = [];
    //         $sProductSaleColumns = "";

    //         $categorySummaryMap = [];

    //         while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
    //             $summaryColName[] = $rowProduct["summary_column_name"];
    //             $productNames[] = $rowProduct["product_name"];

    //             $category = $rowProduct["category_name"];
    //             $summaryColumn = $rowProduct["summary_column_name"];

    //             if (!isset($categorySummaryMap[$category])) {
    //                 $categorySummaryMap[$category] = [];
    //             }

    //             $categorySummaryMap[$category][] = $summaryColumn;
    //         }

    //         $sProductSaleColumns = "";

    //         foreach ($categorySummaryMap as $category => $summaryColumns) {
    //             // Enclose alias in backticks to handle spaces
    //             $aliasName = str_replace(" ", "_", $category); // Replace spaces with underscores (optional)
    //             $sProductSaleColumns .= "SUM(" . implode("+", $summaryColumns) . ") AS `$aliasName`, ";
    //         }

    //         // Remove the trailing comma and space
    //         $sProductSaleColumns = rtrim($sProductSaleColumns, ", ");


    //         $rsAction = null;
    //         $iRows = 0;

    //         // Fetch all records
    //         $sQuery = "SELECT $sProductSaleColumns FROM $summaryTable AS a, $projectTeamTable AS b, $branchTable AS c  WHERE a.team_id = b.team_id AND b.branch_id = c.branch_id $allCond AND a.dstatus = 0
    //          ORDER BY a.activity_date DESC";
    //         print_r($sQuery);
    //         die;


    //         $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

    //         if ($iRows > 0) {
    //             $categorySales = [];
    //             $totalCategorySales = 0; // Grand total
    //             $arrSalesData = [];

    //             while ($row = $this->_dbConn->GetData($rsAction)) {

    //                 foreach ($categorySummaryMap as $category => $summaryColumns) {
    //                     $aliasName = str_replace(" ", "_", $category); // Ensure alias matches generated SQL

    //                     $salesQty = $row[$aliasName] ?? 0;

    //                     if ($salesQty > 0) {
    //                         if (!isset($categorySales[$category])) {
    //                             $categorySales[$category] = 0;
    //                         }
    //                         $categorySales[$category] += round($salesQty, 2);
    //                         $totalCategorySales += $salesQty;
    //                     }
    //                 }
    //             }
    //         }

    //         $arrShowCategorySales = [];

    //         $arrShowCategorySales = array_values($categorySales);
    //         $arrSalesData[] = array(
    //             'name' => "Sales (M)",
    //             'data' => $arrShowCategorySales,
    //         );

    //         $arrCustomize = array(
    //             "height" => 300,

    //         );

    //         $report_type = array_keys($categorySales); // Get category names as an array

    //         return $this->getOutput($arrSalesData, "Category Wise Sales(M)", $report_type, $arrCustomize);
    //     }
    // }

    // private function getOutput($chartData, $title, $arrXAxisLabels, $chartCategories = array())
    // {
    //     return array(
    //         "chartData" => isset($chartData) ? $chartData : array(),
    //         "title" => isset($title) ? $title : "",
    //         "xAxisLabel1" => isset($arrXAxisLabels) ? $arrXAxisLabels : array(),
    //         "xAxisLabel2" => isset($chartCategories) ? $chartCategories : array(),
    //     );
    // }

    // public function getOutletVisitedTableData()
    // {

    //     $where = $this->getCondition(false);
    //     if ($where) {
    //         $where = str_replace(" team_id", " b.team_id", $where);
    //     }
    //     $arrResponse = array();
    //     $arrMonthYear = array();
    //     $monthVisited = array();
    //     $monthNotVisited = array();
    //     $totalSumBranchLevelVisited = array();
    //     $totalSumBranchLevelNotVisited = array();
    //     $routeAndTeamTable = "tblroute_details AS a, tblproject_team AS b";

    //     // Generate Month-Year data for the last 12 months
    //     for ($i = 0; $i < 12; $i++) {
    //         $date = new DateTime();
    //         $date->modify("-$i months");
    //         // First day of the month
    //         $firstDay = $date->format('Y-m-01');

    //         // Last day of the month
    //         $lastDay = $date->format('Y-m-t');
    //         $monthYear = $date->format('F Y'); // e.g., "February 2024"
    //         $monthYear = $date->format('F Y'); // e.g., "February 2024"
    //         $arrMonthYear[] = $monthYear;
    //         // Get last day of the current month
    //         $lastDayCurrentMonth = (new DateTime())->format('Y-m-t');

    //         // Get first day of 12 months ago
    //         $firstDay12MonthsAgo = (new DateTime())->modify('-11 months')->format('Y-m-01');
    //     }
    //     // Get Outlet Visited  Data
    //     foreach ($arrMonthYear as $monthYear) {
    // $queryNew = "SELECT COUNT(DISTINCT a.ques_3) as outletVisited, b.branch_id, b.circle, b.section, b.wd_code, b.team_id, DATE_FORMAT(a.capture_date, '%M %Y') AS monthYear FROM tblsurvey_response_details AS a, tblproject_team AS b WHERE a.team_id = b.team_id" .
    // " AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99'  AND DATE_FORMAT(a.capture_date, '%M %Y') = '$monthYear'  $where GROUP BY monthYear, b.branch_id, b.circle, b.section, b.wd_code ORDER BY b.branch_id, b.circle, b.section, b.wd_code";


    //         $rsAction1 = null;
    //         $iActionRows1 = 0;
    //         $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);

    //         if ($iActionRows1 > 0) {
    //             while ($row1 = $this->_dbConn->GetData($rsAction1)) {
    //                 $team = $row1['team_id'];
    //                 $branch_id = $row1['branch_id'];
    //                 $branch_name = getRowColumn($this->_dbConn, $this->_tables["BRANCH_TABLE"], "main_branch", "branch_id = $branch_id");
    //                 $activeOutlets = getRowColumn($this->_dbConn, $routeAndTeamTable, "COUNT(a.rec_id)", " a.team_id = b.team_id AND b.branch_id = $branch_id AND a.dstatus = 0 AND b.dstatus = 0 GROUP BY b.branch_id");
    //                 $circle = $row1['circle'];
    //                 $section = $row1['section'];
    //                 $wd_code = $row1['wd_code'];
    //                 $outletVisited = $row1['outletVisited'] ? (int) $row1['outletVisited'] : 0;

    //                 // Branch Level
    //                 $branchIndex = array_search($branch_id, array_column($monthVisited, "branch_id"));
    //                 if ($branchIndex === false) {
    //                     $monthVisited[] = array(
    //                         "branch_id" => $branch_id,
    //                         "branch_name" => $branch_name,
    //                         "active_outlets" => $activeOutlets,
    //                         "branchLevelVisited" => array(),
    //                         "circleData" => array()
    //                     );
    //                     $branchIndex = count($monthVisited) - 1;
    //                 }
    //                 $monthVisited[$branchIndex]["branchLevelVisited"][$monthYear] =
    //                     ($monthVisited[$branchIndex]["branchLevelVisited"][$monthYear] ?? 0) + $outletVisited;

    //                 // Circle Level
    //                 $circleIndex = array_search($circle, array_column($monthVisited[$branchIndex]["circleData"], "circle"));
    //                 if ($circleIndex === false) {
    //                     $monthVisited[$branchIndex]["circleData"][] = array(
    //                         "circle" => $circle,
    //                         "active_outlets" => $activeOutlets,
    //                         "circleLevelVisited" => array(),
    //                         "sectionData" => array()
    //                     );
    //                     $circleIndex = count($monthVisited[$branchIndex]["circleData"]) - 1;
    //                 }
    //                 $monthVisited[$branchIndex]["circleData"][$circleIndex]["circleLevelVisited"][$monthYear] =
    //                     ($monthVisited[$branchIndex]["circleData"][$circleIndex]["circleLevelVisited"][$monthYear] ?? 0) + $outletVisited;

    //                 // Section Level
    //                 $sectionIndex = array_search($section, array_column($monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"], "section"));
    //                 if ($sectionIndex === false) {
    //                     $monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][] = array(
    //                         "section" => $section,
    //                         "active_outlets" => $activeOutlets,
    //                         "sectionLevelVisited" => array(),
    //                         "wdData" => array(),
    //                     );
    //                     $sectionIndex = count($monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"]) - 1;
    //                 }
    //                 $monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelVisited"][$monthYear] =
    //                     ($monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelVisited"][$monthYear] ?? 0) + $outletVisited;

    //                 // WD Level
    //                 $WDIndex = array_search($wd_code, array_column($monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"], "wd_code"));
    //                 if ($WDIndex === false) {
    //                     $monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][] = array(
    //                         "wd_code" => $wd_code,
    //                         "wdCode" => $wd_code,
    //                         "active_outlets" => $activeOutlets,
    //                         "wdLevelVisited" => array(),
    //                     );
    //                     $WDIndex = count($monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"]) - 1;
    //                 }
    //                 $monthVisited[$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelVisited"][$monthYear] = $outletVisited;
    //             }
    //         }
    //     }

    //     // Calculate Total Visit at Branch Level
    //     foreach ($monthVisited as $branch) {
    //         foreach ($branch["branchLevelVisited"] as $monthYear => $value) {
    //             if (!isset($totalSumBranchLevelVisited[$monthYear])) {
    //                 $totalSumBranchLevelVisited[$monthYear] = 0;
    //             }
    //             $totalSumBranchLevelVisited[$monthYear] = $totalSumBranchLevelVisited[$monthYear] + $value;
    //         }
    //     }

    //     // GET ALL ROUTE OUTLETS
    //     $query = "SELECT a.rec_id, a.team_id, b.branch_id, b.circle, b.section, b.wd_code
    //       FROM tblroute_details AS a
    //       JOIN tblproject_team AS b ON a.team_id = b.team_id
    //       WHERE a.dstatus = 0 $where";

    //     $rsAction = null;
    //     $iActionRows = 0;
    //     $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    //     $arrAllOutlets = [];
    //     $BranchIds = [];

    //     if ($iActionRows > 0) {
    //         while ($row = $this->_dbConn->GetData($rsAction)) {
    //             $team_id = $row["team_id"];

    //             $arrAllOutlets[$team_id][] = $row["rec_id"];
    //             $BranchIds[$team_id] = [
    //                 "branch_id" => $row["branch_id"],
    //                 "circle" => $row["circle"],
    //                 "section" => $row["section"],
    //                 "wd_code" => $row["wd_code"]
    //             ];
    //         }


    //         // Batch fetch all visited outlets at once
    //         $allVisitedShops = [];
    //         $queryVisited = "SELECT a.team_id, a.ques_3 AS shop_id, DATE_FORMAT(a.capture_date, '%M %Y') AS monthYear
    //              FROM tblsurvey_response_details AS a
    //              WHERE a.dstatus = 0";

    //         $rsVisited = null;
    //         $iVisitedRows = 0;
    //         $this->_dbConn->ExecuteSelectQuery($queryVisited, $rsVisited, $iVisitedRows);

    //         if ($iVisitedRows > 0) {
    //             while ($row = $this->_dbConn->GetData($rsVisited)) {
    //                 $allVisitedShops[$row["team_id"]][$row["monthYear"]][$row["shop_id"]] = true;
    //             }
    //         }

    //         $monthNotVisited = [];

    //         foreach ($arrAllOutlets as $team_id => $allShops) {
    //             $branch_id = $BranchIds[$team_id]["branch_id"];
    //             $circle = $BranchIds[$team_id]["circle"];
    //             $section = $BranchIds[$team_id]["section"];
    //             $wd_code = $BranchIds[$team_id]["wd_code"];

    //             foreach ($arrMonthYear as $monthYear) {
    //                 $visitedShops = $allVisitedShops[$team_id][$monthYear] ?? [];
    //                 $notVisitedCount = count(array_filter($allShops, fn($shop) => !isset($visitedShops[$shop])));

    //                 // Initialize Hierarchical Data Structures
    //                 if (!isset($monthNotVisited[$branch_id])) {
    //                     $monthNotVisited[$branch_id] = ["branch_id" => $branch_id, "branchLevelNotVisited" => [], "circleData" => []];
    //                 }
    //                 if (!isset($monthNotVisited[$branch_id]["circleData"][$circle])) {
    //                     $monthNotVisited[$branch_id]["circleData"][$circle] = ["circle" => $circle, "circleLevelNotVisited" => [], "sectionData" => []];
    //                 }
    //                 if (!isset($monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section])) {
    //                     $monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section] = ["section" => $section, "sectionLevelNotVisited" => [], "wdData" => []];
    //                 }
    //                 if (!isset($monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["wdData"][$wd_code])) {
    //                     $monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["wdData"][$wd_code] = ["wd_code" => $wd_code, "wdLevelNotVisited" => []];
    //                 }

    //                 // Update counts
    //                 $monthNotVisited[$branch_id]["branchLevelNotVisited"][$monthYear] =
    //                     ($monthNotVisited[$branch_id]["branchLevelNotVisited"][$monthYear] ?? 0) + $notVisitedCount;
    //                 $monthNotVisited[$branch_id]["circleData"][$circle]["circleLevelNotVisited"][$monthYear] =
    //                     ($monthNotVisited[$branch_id]["circleData"][$circle]["circleLevelNotVisited"][$monthYear] ?? 0) + $notVisitedCount;
    //                 $monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["sectionLevelNotVisited"][$monthYear] =
    //                     ($monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["sectionLevelNotVisited"][$monthYear] ?? 0) + $notVisitedCount;
    //                 $monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["wdData"][$wd_code]["wdLevelNotVisited"][$monthYear] =
    //                     ($monthNotVisited[$branch_id]["circleData"][$circle]["sectionData"][$section]["wdData"][$wd_code]["wdLevelNotVisited"][$monthYear] ?? 0) + $notVisitedCount;
    //             }
    //         }
    //     }


    //     // Calculate Total NOt Visit at Branch Level
    //     // foreach ($monthVisited as $branch) {
    //     //     foreach ($branch["branchLevelNotVisited"] as $monthYear => $value) {
    //     //         if (!isset($totalSumBranchLevelNotVisited[$monthYear])) {
    //     //             $totalSumBranchLevelNotVisited[$monthYear] = 0;
    //     //         }
    //     //         $totalSumBranchLevelNotVisited[$monthYear] = $totalSumBranchLevelNotVisited[$monthYear] + $value;
    //     //     }
    //     // }


    //     // Response Data
    //     $arrResponse = array(
    //         "MonthsAndYears" => $arrMonthYear,
    //         "BranchVisitData" => $monthVisited,
    //         "BranchNotVisitData" => $monthNotVisited,
    //         "TotalSumVisited" => $totalSumBranchLevelVisited,
    //         // "TotalSumNotVisited" => $totalSumBranchLevelNotVisited,
    //         "Title" => "Monthly Visited/Not Visited Outlet Summary"
    //     );
    //     // print_r($arrResponse);
    //     // die;

    //     return $arrResponse;
    // }
}
