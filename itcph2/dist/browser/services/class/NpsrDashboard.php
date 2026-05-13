<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class NpsrDashboard
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
        $query = "select Distinct b.wd_code, c.wd_firm_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by b.wd_code";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = [
                    "label" => $row['wd_code'] . ' - ' . $row['wd_firm_name'],
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.team_name IS NOT NULL AND b.team_name != '' AND b.s_id = 99 $where order by b.team_name";
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
        // $districtList = ;
        // $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        // $user_id = $this->_iUserId;
        // $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", " user_id = $user_id");
        // if ($groupId == 1 || $groupId == 2) {
        // $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
        //     $branchFilter = true;
        // } elseif ($groupId == 4) {
        //     $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
        //     $branchFilter = true;
        // } else {
        //     $districtList = array();
        //     $branchFilter = false;
        // }
        // $where = "";
        // $teamList = $this->_arrAccessInfo["user_teams"];
        // if ($teamList) {
        //     $where .= " AND team_id IN $teamList";
        // }

        // echo "<pre>";
        // print_r($branchList );die;
        $arrResult = [
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "teamType" => $this->getDsTypeList(),
            "teamList" => $this->getTeamsList(),
            "monthList" => $this->monthLabelAndValue(),
            "yearList" => getYearListNew(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
            "branchFilter" => true,
            "showMapStyleDropdown" => true,
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition()
    {
        $condition = "";
        $district = getFormData($this->_data, "district");
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
        $branch = getFormData($this->_data, "branch");
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
        $circle = getFormData($this->_data, "circle");
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
        $section = getFormData($this->_data, "section");
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
        $wdCode = getFormData($this->_data, "wdCode");
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
        $wdMarket = getFormData($this->_data, "wdMarket");
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
        $wdPopGroup = getFormData($this->_data, "wdPopGroup");
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
        $teamType = getFormData($this->_data, "dsType");
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

        $dsName = getFormData($this->_data, "dsName");
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

        return $condition;
    }

    final public function getConditionForCategoryAndProduct()
    {
        $condition = "";
        $category = getFormData($this->_data, "category");
        if ($category) {
            if (!is_array($category)) {
                $category = [$category];
            }
            if (in_array('all', $category)) {
                $condition .= " ";
            } else {
                $category = "'" . implode("','", $category) . "'";
                $condition .= " AND a.category_name IN ($category)";
            }
        }
        $product = getFormData($this->_data, "product");
        if ($product) {
            if (!is_array($product)) {
                $product = [$product];
            }
            if (in_array('all', $product)) {
                $condition .= " ";
            } else {
                $product = "'" . implode("','", $product) . "'";
                $condition .= " AND a.product_name IN ($product)";
            }
        }

        return $condition;
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

    final public function getCardData()
    {
        $arrResult = [
            "monthlySalesData" => $this->getMonthlySalesData(),
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    public function getMonthlySalesData()
    {
        $where = $this->getCondition();
        $whereCategoryAndProduct = $this->getConditionForCategoryAndProduct();
        // echo $where;die;

        if ($where) {
            $where = str_replace(" team_id", " b.team_id", $where);
        }
        $arrResponse = [];
        $arrSalesColumns = []; // Reset the sales columns array here
        $arrMonthYear = [];
        $monthWiseSales = [];
        $totalSumDistrictLevelSale = [];
        $arrWeekWiselabel = [];

        // Get all sales-related columns
        $query = "SELECT a.summary_column_name, d.main_branch FROM tblbranch_pickupstock_products AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
            " WHERE a.branch_id = b.branch_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0 AND b.wd_code = e.wd_code $where $whereCategoryAndProduct GROUP BY d.main_branch, a.summary_column_name";

        $rsAction = null;
        $iActionRows = 0;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $main_branch = $row["main_branch"];
                // $circle = $row["circle"];
                // $section = $row["section"];
                $summary_column_name = $row["summary_column_name"];
                $arrSalesColumns[$main_branch][] = $summary_column_name;  // Store sales columns by branch
                // $BranchIds[] = $main_branch;
            }

            $months = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December"
            ];

            $currentMonth  = date('n');
            $currentYear = date('Y');

            // Generate Month-Year data for the last 3 months
            // for ($i = 0; $i < 3; $i++) {
            //     $targetMonth = $currentMonth - $i;
            //     $targetYear = $currentYear;

            //     if ($targetMonth <= 0) {
            //         $targetMonth += 12;
            //         $targetYear--;
            //     }

            //     $arrMonthYear[] = $months[$targetMonth - 1] . " " . $targetYear;
            // }

            $yearFilled = $this->_data['year'];
            $monthFilled = $this->_data['month'];

            if (isset($monthFilled) && $monthFilled) {
                $arrMonthYear = $monthFilled;
                // $start = new DateTime("$yearFilled-$monthFilled-01");

                // for ($i = 2; $i >= 0; $i--) {
                //     $clone = clone $start;
                //     $clone->sub(new DateInterval("P{$i}M"));
                //     $arrMonthYear[] = $clone->format('F Y');
                // }
            } else {
                // Generate Month-Year data for the last 3 months in reverse (oldest to newest)
                for ($i = 2; $i >= 0; $i--) {
                    $targetMonth = $currentMonth - $i;
                    $targetYear = $currentYear;

                    if ($targetMonth <= 0) {
                        $targetMonth += 12;
                        $targetYear--;
                    }

                    $arrMonthYear[] = $months[$targetMonth - 1] . " " . $targetYear;
                }
            }

            // Process sales data per branch and month
            foreach ($arrSalesColumns as $main_branch => $salesColumns) {
                // Reset $arrSalesColumns for the next branch
                $sSalesColumns = implode("+", $salesColumns);  // Create sum for current branch
                $arrWeekWiselabel = [];
                foreach ($arrMonthYear as $monthAndYear) {
                    list($monthAlphabetic, $year) = explode(" ", $monthAndYear);
                    $shortMonth = substr($monthAlphabetic, 0, 3);

                    $month = sprintf('%02d', array_search($monthAlphabetic, $months) + 1);

                    // echo "<pre>";
                    // print_r($monthAndYear);
                    // Create a DateTime object from the string
                    // $date = DateTime::createFromFormat('F Y', $monthAndYear);
                    // // Extract month and year
                    // $month = $date->format('m');
                    // $year = $date->format('Y');
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i == 1) {
                            $monthYear = $monthAndYear;
                            $weekStartDate = "$year-$month-01";
                            $weekEndDate = date("Y-m-d", strtotime("last day of $weekStartDate"));
                            $cond = " AND a.capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                            $arrWeekWiselabel[] = $monthAndYear;
                        } elseif ($i == 2) {
                            $monthYear =  $shortMonth . " - W1";
                            $weekStartDate = "$year-$month-01";
                            $weekEndDate = "$year-$month-07";
                            $cond = " AND a.capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                            $arrWeekWiselabel[] = $monthYear;
                        } elseif ($i == 3) {
                            $monthYear = $shortMonth . " - W2";
                            $weekStartDate = "$year-$month-08";
                            $weekEndDate = "$year-$month-14";
                            $cond = " AND a.capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                            $arrWeekWiselabel[] = $monthYear;
                        } elseif ($i == 4) {
                            $monthYear = $shortMonth . " - W3";
                            $weekStartDate = "$year-$month-15";
                            $weekEndDate = "$year-$month-21";
                            $cond = " AND a.capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                            $arrWeekWiselabel[] = $monthYear;
                        } elseif ($i == 5) {
                            $monthYear = $shortMonth . " - W4";
                            $weekStartDate = "$year-$month-22";
                            $weekEndDate = date("Y-m-d", strtotime("last day of $weekStartDate"));
                            $cond = " AND a.capture_date BETWEEN '$weekStartDate' AND '$weekEndDate'";
                            $arrWeekWiselabel[] = $monthYear;
                        }

                        // Get Monthly Sales Data
                        // $queryNew = "SELECT SUM($sSalesColumns) as totalSales, c.branch_id, c.circle, c.section, c.wd_code, c.team_name, b.outlet_name, b.route_name FROM tblsurvey_response_details AS a" .
                        //     ", tblroute_details as b, tblproject_team AS c WHERE a.ques_3 = b.rec_id AND b.team_id = c.team_id AND a.dstatus = 0 AND b.dstatus = 0 AND c.s_id = 99" .
                        //     " AND c.branch_id = $branch_id $where $cond GROUP BY a.ques_3, b.route_name, c.team_id, c.branch_id, c.circle, c.section, c.wd_code ORDER BY c.branch_id, c.circle, c.section, c.wd_code";

                        $queryNew = "SELECT SUM($sSalesColumns) as totalSales, e.circle, e.section, b.wd_code, b.team_name, c.outlet_name, c.route_name, d.district, d.main_branch FROM tblsurvey_response_details AS a, tblproject_team AS b, tblroute_details as c, tblbranch as d, tblmapping_wd as e" .
                            " WHERE a.team_id = b.team_id AND a.ques_3 = c.rec_id AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code AND b.s_id = 99 AND d.main_branch = '$main_branch' AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0 $where $cond" .
                            " GROUP BY c.rec_id, c.route_name, b.team_id, d.main_branch, e.circle, e.section, b.wd_code ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name, c.sort_order";
                        // echo $queryNew;die;
                        $rsAction1 = null;
                        $iActionRows1 = 0;
                        $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);

                        if ($iActionRows1 > 0) {
                            while ($row1 = $this->_dbConn->GetData($rsAction1)) {
                                $branch_id = $row1['main_branch'];
                                $district = $row1['district'];
                                $branch_name = $row1['main_branch'];
                                $circle = $row1['circle'];
                                $section = $row1['section'];
                                $wd_code = $row1['wd_code'];
                                $team_name = $row1['team_name'];
                                $route_name = $row1['route_name'];
                                $outlet_name = $row1['outlet_name'];
                                $totalSales = round($row1['totalSales'] ? (float) $row1['totalSales'] : 0, 2);  // Round to 2 decimal places

                                // District Level Sales
                                $districtIndex = array_search($district, array_column($monthWiseSales, "district"));
                                if ($districtIndex === false) {
                                    $monthWiseSales[] = [
                                        "district" => $district,
                                        "districtLevelSale" => [],
                                        "branchData" => []
                                    ];
                                    $districtIndex = count($monthWiseSales) - 1;
                                }
                                $monthWiseSales[$districtIndex]["districtLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["districtLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Branch Level Sales
                                $branchIndex = array_search($branch_id, array_column($monthWiseSales[$districtIndex]["branchData"], "branch_id"));
                                if ($branchIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][] = [
                                        "branch_id" => $branch_id,
                                        "branch_name" => $branch_name,
                                        "branchLevelSale" => [],
                                        "circleData" => []
                                    ];
                                    $branchIndex = count($monthWiseSales[$districtIndex]["branchData"]) - 1;
                                }
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["branchLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["branchLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Circle Level Sales
                                $circleIndex = array_search($circle, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"], "circle"));
                                if ($circleIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][] = [
                                        "circle" => $circle,
                                        "circleLevelSale" => [],
                                        "sectionData" => []
                                    ];
                                    $circleIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"]) - 1;
                                }
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Section Level Sales
                                $sectionIndex = array_search($section, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"], "section"));
                                if ($sectionIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][] = [
                                        "section" => $section,
                                        "sectionLevelSale" => [],
                                        "wdData" => [],
                                    ];
                                    $sectionIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"]) - 1;
                                }
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // WD Level Sales
                                $WDIndex = array_search($wd_code, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"], "wd_code"));
                                if ($WDIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][] = [
                                        "wd_code" => $wd_code,
                                        "wdCode" => $wd_code,
                                        "wdLevelSale" => [],
                                        "teamData" => [],
                                    ];
                                    $WDIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"]) - 1;
                                }
                                // $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$monthYear] = round($totalSales, 2); // Round WD level sales

                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Team Sales
                                $teamIndex = array_search($team_name, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"], "team_name"));
                                if ($teamIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][] = [
                                        "team_name" => $team_name,
                                        "teamLevelSale" => [],
                                        "routeData" => [],
                                    ];
                                    $teamIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"]) - 1;
                                }
                                // $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["teamLevelSale"][$monthYear] = round($totalSales, 2); // Round WD level sales

                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["teamLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["teamLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Route Sales
                                $routeIndex = array_search($route_name, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"], "route_name"));
                                if ($routeIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][] = [
                                        "route_name" => $route_name,
                                        "routeLevelSale" => [],
                                        "outletData" => [],
                                    ];
                                    $routeIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"]) - 1;
                                }
                                // Round WD level sales
                                // $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["routeLevelSale"][$monthYear] = round($totalSales, 2);

                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["routeLevelSale"][$monthYear] =
                                    round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["routeLevelSale"][$monthYear] ?? 0) + $totalSales, 2);

                                // Outlet Sales
                                $outletIndex = array_search(
                                    $outlet_name,
                                    array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"], "outlet_name")
                                );
                                if ($outletIndex === false) {
                                    $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"][] = [
                                        "outlet_name" => $outlet_name,
                                        "outletLevelSale" => [],
                                    ];
                                    $outletIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"]) - 1;
                                }
                                // Round WD level sales
                                // $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"][$outletIndex]["outletLevelSale"][$monthYear] =
                                //     round($totalSales, 2);

                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"][$outletIndex]["outletLevelSale"][$monthYear] =
                                    round(
                                        ($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["routeData"][$routeIndex]["outletData"][$outletIndex]["outletLevelSale"][$monthYear] ?? 0)
                                            + $totalSales,
                                        2
                                    );
                            }
                        }
                    }
                }
            }
            // die;

            // echo "<pre>";
            // print_r($arrWeekWiselabel);die;

            // Calculate Total Sales at Branch Level
            foreach ($monthWiseSales as $district) {
                foreach ($district["districtLevelSale"] as $monthYear => $value) {
                    if (!isset($totalSumDistrictLevelSale[$monthYear])) {
                        $totalSumDistrictLevelSale[$monthYear] = 0;
                    }
                    $totalSumDistrictLevelSale[$monthYear] = round($totalSumDistrictLevelSale[$monthYear] + $value, 2);
                }
            }
        }

        // Response Data
        $arrResponse = [
            "MonthsAndYears" => $arrWeekWiselabel,
            "districtData" => $monthWiseSales,
            "TotalSum" => $totalSumDistrictLevelSale,
            "Title" => "Survey in M"
        ];

        return $arrResponse;
    }

    final public function getMapData()
    {
        global $ARR_TEAM_TYPES;
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $no_img_path = $GLOBALS["LOGO_URL"];
        // Define available marker colors
        $greenFlag = "/green-flag.png";
        $redFlag = "/red-flag.png";
        // $redDot = "/red-dot.png";
        $greenDot = "/yellow-dot.png";
        $orangeDot = "/orange-dot.png";

        $where = "";
        $arrData = [];
        $arrData["columnSize"] = 12; // size of map column, values can be 1 to 12
        $arrData["repeatMapBy"] = 1; // times to repeat same map, values can be >=1
        $arrData["markers"] = [];
        $branch = $this->_data['branch_id'] ?? "";
        $circle = $this->_data['circle'] ?? "";
        $section = $this->_data['section'] ?? "";
        $dsName = $this->_data['team_name'] ?? "";
        $district = $this->_data['district'] ?? "";
        $districtLevelSale = $this->_data['districtLevelSale'] ?? "";
        $branchLevelSale = $this->_data['branchLevelSale'] ?? "";
        $circleLevelSale = $this->_data['circleLevelSale'] ?? "";
        $sectionLevelSale = $this->_data['sectionLevelSale'] ?? "";
        $wdCodeLevelSale = $this->_data['wdLevelSale'] ?? "";
        $teamLevelSale = $this->_data[0]['teamLevelSale'] ?? "";
        $routeLevelSale = $this->_data[0]['routeLevelSale'] ?? "";
        $outletLevelSale = $this->_data[0]['outletLevelSale'] ?? "";

        if (isset($districtLevelSale) && is_array($districtLevelSale) && !empty($districtLevelSale) && isset($district) && $district) {
            $branch = getRowsColumn($this->_dbConn, $branchTable, "branch_id", " dstatus = '0' AND district = '$district'");
            $branches = "'" . implode("','", $branch) . "'";
            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND branch_id IN ($branches)");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'> District Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($branchLevelSale) && is_array($branchLevelSale) && !empty($branchLevelSale) && isset($branch) && $branch) {
            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND branch_id IN ('$branch')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>Branch Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($circleLevelSale) && is_array($circleLevelSale) && !empty($circleLevelSale) && isset($circle) && $circle) {
            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND circle IN ('$circle')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>Circle Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($sectionLevelSale) && is_array($sectionLevelSale) && !empty($sectionLevelSale) && isset($section) && $section) {
            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND section IN ('$section')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>Section Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($wdCodeLevelSale) && is_array($wdCodeLevelSale) && !empty($wdCodeLevelSale)) {
            $wdCode = $this->_data['wdCode'] ?? "";
            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND wd_code IN ('$wdCode')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>WD Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b></b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($teamLevelSale) && is_array($teamLevelSale) && !empty($teamLevelSale)) {
            $teamData = $this->_data[0] ?? [];
            $wdData = $this->_data[1] ?? [];
            $dsName = $teamData['team_name'] ?? '';
            $wdCode = $wdData['wdCode'] ?? '';

            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND wd_code IN ('$wdCode') AND team_name IN ('$dsName')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>DS Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($routeLevelSale) && is_array($routeLevelSale) && !empty($routeLevelSale)) {
            $route_name = $this->_data[0]['route_name'] ?? "";
            $wd_code = $this->_data[1]['wd_code'] ?? "";
            $team_name = $this->_data[2]['team_name'] ?? "";

            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND wd_code IN ('$wd_code') AND team_name IN ('$team_name')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND route_name IN ('$route_name') AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>Route Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        } elseif (isset($outletLevelSale) && is_array($outletLevelSale) && !empty($outletLevelSale)) {
            $outlet_name = $this->_data[0]['outlet_name'] ?? "";
            $team_name = $this->_data[1]['team_name'] ?? "";
            $wd_code = $this->_data[2]['wd_code'] ?? "";
            $route_name = $this->_data[3]['route_name'] ?? "";

            $teams = getRowsColumn($this->_dbConn, $projectTeamTable, "team_id", " dstatus = '0' AND wd_code IN ('$wd_code') AND team_name IN ('$team_name')");
            $teamIds = "'" . implode("','", $teams) . "'";
            // Don't use b.dstatus = 0
            $sActionAtt = null;
            $iRowsAtt = 0;
            $sQueryAtt = "SELECT a.rec_id, a.lt, a.lg, b.team_id, b.team_name, b.is_type, b.branch_id, b.circle, b.section, b.wd_code FROM  $routeTable AS a, $projectTeamTable as b WHERE a.dstatus = 0" .
                " AND a.team_id = b.team_id AND a.team_id IN ($teamIds) AND route_name IN ('$route_name') AND outlet_name IN ('$outlet_name') AND a.lt != 0 and b.dstatus = 0";
            $this->_dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                while ($row = $this->_dbConn->GetData($sActionAtt)) {
                    // $uniId = $row["uni_id"];
                    $team_id = $row["team_id"];
                    $branchId = $row["branch_id"];
                    $team_name = $row["team_name"];

                    $dsType = $row["is_type"];
                    $ARR_TEAM_TYPES[$dsType];
                    $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                    $trackerDescription = "
                <div class='attendance-marker'>
                  <table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; border: 1px solid black;'>
                        <thead>
                        <tr>
                      <th colspan='2' style='text-align: center; font-weight: bold;'>Outlet Summary</th>
                        </tr>
                        </thead>
                    <tbody>
                      <tr><td><b>Team Name</b></td><td>{$row['team_name']}</td></tr>
                      <tr><td><b>Branch</b></td><td>$branchName</td></tr>
                      <tr><td><b>Circle</b></td><td>{$row['circle']}</td></tr>
                      <tr><td><b>Section</b></td><td>{$row['section']}</td></tr>
                      <tr><td><b>WD Code</b></td><td>{$row['wd_code']}</td></tr>
                      <tr><td><b>Surveyor Type</b></td><td>{$ARR_TEAM_TYPES[$dsType]}</td></tr>
                    </tbody>
                  </table>
                </div>";

                    $arrData["markers"][] = [
                        "latitude" => $row["lt"],
                        "longitude" => $row["lg"],
                        "markerUrl" => $GLOBALS['MARKER_URL'] . $orangeDot, // default icon is green
                        "markerTitle" => "", // text to display on hover of marker
                        "windowTitle" => $trackerDescription, // text to display on click of marker (can contain HTML)
                    ];
                }
            }
        }

        if (isNonEmptyArray($arrData["markers"])) {
            $arrMessage = responseMessage([], 1, $arrData, true);
        } else {
            $arrMessage = responseMessage([$GLOBALS['NO_RECORD_FOUND']]);
        }

        echo json_encode($arrMessage);
    }

    private function getLastMonthsData($months = 12)
    {
        $arrData = [];
        $current = new DateTime();

        for ($i = 0; $i < $months; $i++) {
            $label = $current->format('M y');
            $value = $current->format('F Y');

            $arrData[] = [
                "label" => $label,
                "value" => $value,
            ];

            // Move to previous month
            $current->modify('-1 month');
        }

        return $arrData;
    }

    // function monthLabelAndValue()
    // {
    //     $months = [
    //             "January",
    //             "February",
    //             "March",
    //             "April",
    //             "May",
    //             "June",
    //             "July",
    //             "August",
    //             "September",
    //             "October",
    //             "November",
    //             "December"
    //         ];

    //     $arrMonthYear = array();

    //     $currentMonth  = date('n');
    //     $currentYear = date('Y');
    //     for ($i = 2; $i >= 0; $i--) {
    //         $targetMonth = $currentMonth - $i;
    //         $targetYear = $currentYear;

    //         if ($targetMonth <= 0) {
    //             $targetMonth += 12;
    //             $targetYear--;
    //         }

    //         $arrMonthYear[] = $months[$targetMonth - 1] . " " . $targetYear;
    //     }

    //     return $arrMonthYear;
    // }

    private function monthLabelAndValue($count = 12)
    {
        $months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        $arrData = [];
        $currentMonth  = date('n'); // 1 to 12
        $currentYear = date('Y');

        for ($i = 0; $i < $count; $i++) {
            $targetMonth = $currentMonth - $i;
            $targetYear = $currentYear;

            // Adjust if month goes below 1
            while ($targetMonth <= 0) {
                $targetMonth += 12;
                $targetYear--;
            }

            $monthName = $months[$targetMonth - 1];
            $shortLabel = date('M', mktime(0, 0, 0, $targetMonth, 10)) . ' ' . substr($targetYear, 2);

            $arrData[] = [
                "label" => $shortLabel,
                "value" => $monthName . ' ' . $targetYear
            ];
        }

        return $arrData;
    }
}
