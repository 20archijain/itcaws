<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class BreezeDashboard
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
        $query = "select Distinct a.district from tblbranch as a, tblbreeze_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblbreeze_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by a.branch_name";
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
        $query = "select Distinct c.circle, c.circle_name from tblbranch as a, tblbreeze_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.wd_code = c.wd_code" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.circle";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['circle_name'],
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
        $query = "select Distinct c.section, c.section_name from tblbranch as a, tblbreeze_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.wd_code = c.wd_code" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = 99 $where order by c.section";
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
        $query = "select Distinct b.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblbreeze_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
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
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblbreeze_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
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
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblbreeze_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
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
        $query = "select Distinct b.is_type from tblbranch as a, tblbreeze_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = "";
                if ($row['is_type'] == 6) {
                    $teamType = "RMD";
                } elseif ($row['is_type'] == 8) {
                    $teamType = "Stockiest DS";
                } elseif ($row['is_type'] == 9) {
                    $teamType = "Common FMCG Lite DS";
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblbreeze_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.team_name";
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
        $arrResult = array(
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
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition()
    {
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
        return $condition;
    }

    final public function getConditionForYearAndMonth($column = "a.capture_date")
    {
        $monthCondArray = array();
        $month = getFormData($this->_data, "month");
        if ($month) {
            foreach ($month as $m) {
                $firstDate = date("Y-m-01", strtotime($m));
                $lastDate  = date("Y-m-t", strtotime($m));
                $monthCondArray[] = "($column BETWEEN '$firstDate' AND '$lastDate')";
            }

            if (!empty($monthCondArray)) {
                $condition = " AND (" . implode(" OR ", $monthCondArray) . ")";
            }
        } else {
            $firstDay = date('Y-m-01');
            $lastDay = date('Y-m-t');

            $condition = " AND $column Between '$firstDay' AND '$lastDay'";
        }
        return $condition;
    }

    final public function getDate()
    {
        $months = getFormData($this->_data, "month");
        $totalDays = 0;

        if (is_array($months) && !empty($months)) {
            foreach ($months as $month) {
                $date = DateTime::createFromFormat('F Y', $month);
                if ($date) {
                    $totalDays += (int)$date->format('t');
                }
            }
        } else {
            $date = new DateTime();
            $totalDays = (int)$date->format('t');
        }

        return $totalDays;
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
                $districtCond = " AND d.district IN ($district)";
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
                    $circleCond = " AND a.circle IN ($circle)";
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
        $monthlySalesData      = $this->getMonthlySalesData();
        $monthlySalesGraphData = $this->getMonthlySalesGraphData($monthlySalesData);

        $arrResult = array(
            "monthlySalesData"      => $monthlySalesData,
            "monthlySalesGraphData" => $monthlySalesGraphData,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    public function getMonthlySalesData()
    {
        $where = $this->getCondition();
        $yearAndMonthCond = $this->getConditionForYearAndMonth();
        $yearAndMonthCond2 = $this->getConditionForYearAndMonth("a.capture_date");
        $getDate = $this->getDate();

        if ($where) {
            $where = str_replace(" team_id", " b.team_id", $where);
        }
        $arrResponse = array();
        $arrTeamType = array(6 => "RMD", 8 => "Stockiest DS", 9 => "Common FMCG Lite DS");
        $monthWiseSales = array();
        $totalSumDistrictLevelSale = [
            "Avg Start Time" => 0,
            "Avg End Time" => 0,
            "Avg Time Spent" => 0,
            "Avg km Travelled" => 0,
            "Planned Outlets" => 0,
            "Outlets ReVisit" => 0,
            "Total Sales" => 0,
        ];

        $labelsToBePrint = [
            "Avg Start Time",
            "Avg End Time",
            "Avg Time Spent",
            "Avg km Travelled",
            "Planned Outlets",
            "Outlets ReVisit",
            "Total Sales",
        ];

        $labels = [
            "Avg Start Time",      // index = 0, $value = "Avg Start Time"
            "Avg End Time",        // index = 1, $value = "Avg End Time"
            "Avg Time Spent",      // index = 2, $value = "Avg Time Spent"
            "Avg km Travelled",    // index = 3, $value = "Avg km Travelled"
            "Planned Outlets",     // index = 4, $value = "Planned Outlets"
            "Outlets ReVisit",     // index = 5, $value = "Outlets ReVisit"
            "Total Sales",         // index = 6, $value = "Total Sales"
            "Total Transaction",         // index = 7, $value = "Total Transaction"
            "Breeze Users",         // index = 8, $value = "Breeze Users"
            "Value M",         // index = 9, $value = "Value M"
        ];

        $query = "SELECT DISTINCT d.main_branch
        FROM tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
          WHERE  b.wd_code = e.wd_code AND b.branch_id = d.branch_id
            AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
            $where";
        // echo $query;die;
        $rsAction = null;
        $iActionRows = 0;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $main_branch = $row["main_branch"];
                // }
                // // Process sales data per branch and month
                // foreach ($arrMainBranches as $main_branch) {
                foreach ($labels as $index => $value) {
                    if ($index == 0) {
                        // Avg Start Time
                        $queryNew = "SELECT SUM(TIME_TO_SEC(a.start_time))  as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                         FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                        WHERE a.ds_id = b.team_id
                        AND b.branch_id = d.branch_id
                        AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                        AND b.s_id = 99 AND b.wd_code = e.wd_code
                         AND e.circle IS NOT NULL
                       AND e.circle != ''
                        AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                        GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                        ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                        // echo $queryNew;die;
                    } elseif ($index == 1) {
                        // Avg End Time
                        $queryNew = "SELECT SUM(TIME_TO_SEC(a.end_time))  as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                        FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                        WHERE a.ds_id = b.team_id
                        AND b.branch_id = d.branch_id
                        AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                        AND b.s_id = 99 AND b.wd_code = e.wd_code
                         AND e.circle IS NOT NULL
                       AND e.circle != ''
                        AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                        GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                        ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 2) {
                        // Avg Time Spent
                        $queryNew = "SELECT SUM(TIME_TO_SEC(a.total_time_spent))  as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                        FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                        WHERE a.ds_id = b.team_id
                        AND b.branch_id = d.branch_id
                        AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                        AND b.s_id = 99 AND b.wd_code = e.wd_code
                         AND e.circle IS NOT NULL
                       AND e.circle != ''
                        AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                        GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                        ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 3) {
                        // Avg km Travelled
                        $queryNew = "SELECT SuM(a.total_km_travelled) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                        AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 4) {
                        // Planned Outlets
                        $queryNew = "SELECT SUM(a.planned_outlets) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                        AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 5) {
                        // Outlets ReVisit
                        $queryNew = "SELECT SUM(a.outlet_re_visit) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                        AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 6) {
                        // Total Sales
                        $queryNew = "SELECT SUM(a.total_sale) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                       AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 7) {
                        // Total Transaction
                        $queryNew = "SELECT COUNT(a.sum_id) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                       AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    } elseif ($index == 8) {
                        // Breeze Users
                        $queryNew = "SELECT COUNT(distinct a.ds_id) as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                       AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                        // echo $queryNew;
                        // die;
                    } elseif ($index == 9) {
                        // Value M
                        $queryNew = "SELECT DISTINCT a.value_m as totalSales,
                        e.circle, e.section, b.wd_code, b.team_name, b.team_id, b.is_type, d.district, d.main_branch
                     FROM tblbreeze_response_data AS a, tblbreeze_team AS b, tblbranch as d, tblmapping_wd as e
                     WHERE a.ds_id = b.team_id
                       AND b.branch_id = d.branch_id
                       AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0
                       AND b.s_id = 99 AND b.wd_code = e.wd_code
                       AND e.circle IS NOT NULL
                       AND e.circle != ''
                       AND d.main_branch = '$main_branch' $where $yearAndMonthCond
                     GROUP BY b.team_id, d.main_branch, e.circle, e.section, b.wd_code
                     ORDER BY d.district, d.main_branch, e.circle, e.section, b.wd_code, b.team_name";
                    }

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
                            $team_id = $row1['team_id'];
                            $team_type = $arrTeamType[$row1['is_type']];
                            $totalSales = round($row1['totalSales'] ? (float) $row1['totalSales'] : 0, 0);

                            // District Level Sales
                            $districtIndex = array_search($district, array_column($monthWiseSales, "district"));
                            if ($districtIndex === false) {
                                $monthWiseSales[] = array(
                                    "district" => $district,
                                    "districtLevelSale" => array(),
                                    "branchData" => array()
                                );
                                $districtIndex = count($monthWiseSales) - 1;
                            }
                            $monthWiseSales[$districtIndex]["districtLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["districtLevelSale"][$value] ?? 0) + $totalSales, 2);

                            //Branch Level Sales
                            $branchIndex = array_search($branch_id, array_column($monthWiseSales[$districtIndex]["branchData"], "branch_id"));
                            if ($branchIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][] = array(
                                    "branch_id" => $branch_id,
                                    "branch_name" => $branch_name,
                                    "branchLevelSale" => array(),
                                    "circleData" => array()
                                );
                                $branchIndex = count($monthWiseSales[$districtIndex]["branchData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["branchLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["branchLevelSale"][$value] ?? 0) + $totalSales, 2);

                            // Circle Level Sales
                            $circleIndex = array_search($circle, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"], "circle"));
                            if ($circleIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][] = array(
                                    "circle" => $circle,
                                    "circleLevelSale" => array(),
                                    "sectionData" => array()
                                );
                                $circleIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["circleLevelSale"][$value] ?? 0) + $totalSales, 2);

                            // Section Level Sales
                            $sectionIndex = array_search($section, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"], "section"));
                            if ($sectionIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][] = array(
                                    "section" => $section,
                                    "sectionLevelSale" => array(),
                                    "wdData" => array(),
                                );
                                $sectionIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["sectionLevelSale"][$value] ?? 0) + $totalSales, 2);

                            // WD Level Sales
                            $WDIndex = array_search($wd_code, array_column(
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"],
                                "wd_code"
                            ));
                            if ($WDIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][] = array(
                                    "wd_code"    => $wd_code,
                                    "wdCode"     => $wd_code,
                                    "wdLevelSale" => array(),
                                    "teamTypeData" => array(),
                                );
                                $WDIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$value] ?? 0) + $totalSales, 2);

                            // Team Type Level
                            $teamTypeIndex = array_search($team_type, array_column(
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"],
                                "team_type"
                            ));
                            if ($teamTypeIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][] = array(
                                    "team_type"        => $team_type,
                                    "teamTypeLevelSale" => array(),
                                    "teamData"         => array(),
                                );
                                $teamTypeIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamTypeLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamTypeLevelSale"][$value] ?? 0) + $totalSales, 2);

                            // Team Name Level
                            $teamIndex = array_search($team_name, array_column(
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamData"],
                                "team_name"
                            ));
                            if ($teamIndex === false) {
                                $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamData"][] = array(
                                    "team_name"     => $team_name,
                                    "team_id"       => $team_id,
                                    "team_type"     => $team_type,
                                    "teamLevelSale" => array(),
                                );
                                $teamIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamData"]) - 1;
                            }
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamData"][$teamIndex]["teamLevelSale"][$value] =
                                round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamTypeData"][$teamTypeIndex]["teamData"][$teamIndex]["teamLevelSale"][$value] ?? 0) + $totalSales, 2);
                        }
                    }
                }
            }
            // echo "<pre>";
            // print_r($monthWiseSales);die;
            foreach ($monthWiseSales as &$arrDistrictData) {
                // Format Avg Start Time
                if (isset($arrDistrictData['districtLevelSale']['Avg Start Time'])) {
                    $arrDistrictData['districtLevelSale']['ACT Start Time'] = $arrDistrictData['districtLevelSale']['Avg Start Time'];
                    $time = ($arrDistrictData['districtLevelSale']['Avg Start Time'] / $arrDistrictData['districtLevelSale']['Total Transaction']);
                    // echo $time;die;
                    // $hours = floor($time / 3600) ?? 00;
                    // $minutes = floor(($time % 3600) / 60) ?? 00;
                    // $secs = floor($time % 60) ?? 00;
                    $timeDisplay = gmdate("H:i:s", $time);
                    $arrDistrictData['districtLevelSale']['Avg Start Time'] = $timeDisplay;
                    // print_r($arrDistrictData);die;
                }

                // Format Avg End Time
                if (isset($arrDistrictData['districtLevelSale']['Avg End Time'])) {
                    $arrDistrictData['districtLevelSale']['ACT End Time'] = $arrDistrictData['districtLevelSale']['Avg End Time'];
                    $time = ($arrDistrictData['districtLevelSale']['Avg End Time'] / $arrDistrictData['districtLevelSale']['Total Transaction']);
                    $timeDisplay = gmdate("H:i:s", $time);
                    $arrDistrictData['districtLevelSale']['Avg End Time'] = "$timeDisplay";
                }

                // Format Avg Time Spent
                if (isset($arrDistrictData['districtLevelSale']['Avg Time Spent'])) {
                    $arrDistrictData['districtLevelSale']['ACT Time Spent'] = $arrDistrictData['districtLevelSale']['Avg Time Spent'];
                    $time = ($arrDistrictData['districtLevelSale']['Avg Time Spent'] / $arrDistrictData['districtLevelSale']['Total Transaction']);
                    $timeDisplay = gmdate("H:i:s", $time);
                    $arrDistrictData['districtLevelSale']['Avg Time Spent'] = "$timeDisplay";
                }

                // Avg km Travelled
                if (isset($arrDistrictData['districtLevelSale']['Avg km Travelled'])) {
                    $arrDistrictData['districtLevelSale']['ACT km Travelled'] = $arrDistrictData['districtLevelSale']['Avg km Travelled'];
                    $arrDistrictData['districtLevelSale']['Avg km Travelled'] = round(($arrDistrictData['districtLevelSale']['Avg km Travelled'] / $arrDistrictData['districtLevelSale']['Breeze Users']) / $getDate, 2);
                }

                // Total Sales
                if (isset($arrDistrictData['districtLevelSale']['Total Sales'])) {
                    $arrDistrictData['districtLevelSale']['ACT Total Sales'] = $arrDistrictData['districtLevelSale']['Total Sales'];
                    $arrDistrictData['districtLevelSale']['Total Sales'] = round(($arrDistrictData['districtLevelSale']['Total Sales'] / $arrDistrictData['districtLevelSale']['Value M']), 2);
                }

                foreach ($arrDistrictData['branchData'] as &$arrBranchData) {
                    if (isset($arrBranchData['branchLevelSale']['Avg Start Time'])) {
                        $time = ($arrBranchData['branchLevelSale']['Avg Start Time'] / $arrBranchData['branchLevelSale']['Total Transaction']);
                        $timeDisplay = gmdate("H:i:s", $time);
                        $arrBranchData['branchLevelSale']['Avg Start Time'] = "$timeDisplay";
                    }
                    if (isset($arrBranchData['branchLevelSale']['Avg End Time'])) {
                        $time = ($arrBranchData['branchLevelSale']['Avg End Time'] / $arrBranchData['branchLevelSale']['Total Transaction']);
                        $timeDisplay = gmdate("H:i:s", $time);
                        $arrBranchData['branchLevelSale']['Avg End Time'] = "$timeDisplay";
                    }
                    if (isset($arrBranchData['branchLevelSale']['Avg Time Spent'])) {
                        $time = ($arrBranchData['branchLevelSale']['Avg Time Spent'] / $arrBranchData['branchLevelSale']['Total Transaction']) / $getDate;
                        $timeDisplay = gmdate("H:i:s", $time);
                        $arrBranchData['branchLevelSale']['Avg Time Spent'] = "$timeDisplay";
                    }
                    if (isset($arrBranchData['branchLevelSale']['Avg km Travelled'])) {
                        $arrBranchData['branchLevelSale']['Avg km Travelled'] = round(($arrBranchData['branchLevelSale']['Avg km Travelled'] / $arrBranchData['branchLevelSale']['Breeze Users']) / $getDate, 2);
                    }
                    if (isset($arrBranchData['branchLevelSale']['Total Sales'])) {
                        $arrBranchData['branchLevelSale']['Total Sales'] = round(($arrBranchData['branchLevelSale']['Total Sales'] / $arrBranchData['branchLevelSale']['Value M']), 2);
                    }

                    foreach ($arrBranchData['circleData'] as &$arrCircleData) {
                        if (isset($arrCircleData['circleLevelSale']['Avg Start Time'])) {
                            $time = ($arrCircleData['circleLevelSale']['Avg Start Time'] / $arrCircleData['circleLevelSale']['Total Transaction']);
                            $timeDisplay = gmdate("H:i:s", $time);
                            $arrCircleData['circleLevelSale']['Avg Start Time'] = "$timeDisplay";
                        }
                        if (isset($arrCircleData['circleLevelSale']['Avg End Time'])) {
                            $time = ($arrCircleData['circleLevelSale']['Avg End Time'] / $arrCircleData['circleLevelSale']['Total Transaction']);
                            $timeDisplay = gmdate("H:i:s", $time);
                            $arrCircleData['circleLevelSale']['Avg End Time'] = "$timeDisplay";
                        }
                        if (isset($arrCircleData['circleLevelSale']['Avg Time Spent'])) {
                            $time = ($arrCircleData['circleLevelSale']['Avg Time Spent'] / $arrCircleData['circleLevelSale']['Total Transaction']);
                            $timeDisplay = gmdate("H:i:s", $time);
                            $arrCircleData['circleLevelSale']['Avg Time Spent'] = "$timeDisplay";
                        }
                        if (isset($arrCircleData['circleLevelSale']['Avg km Travelled'])) {
                            $arrCircleData['circleLevelSale']['Avg km Travelled'] = round(($arrCircleData['circleLevelSale']['Avg km Travelled'] / $arrCircleData['circleLevelSale']['Breeze Users']) / $getDate, 2);
                        }
                        if (isset($arrCircleData['circleLevelSale']['Total Sales'])) {
                            $arrCircleData['circleLevelSale']['Total Sales'] = round(($arrCircleData['circleLevelSale']['Total Sales'] / $arrCircleData['circleLevelSale']['Value M']), 2);
                        }

                        foreach ($arrCircleData['sectionData'] as &$arrSectionData) {
                            if (isset($arrSectionData['sectionLevelSale']['Avg Start Time'])) {
                                $time = ($arrSectionData['sectionLevelSale']['Avg Start Time'] / $arrSectionData['sectionLevelSale']['Total Transaction']);
                                $timeDisplay = gmdate("H:i:s", $time);
                                $arrSectionData['sectionLevelSale']['Avg Start Time'] = "$timeDisplay";
                            }
                            if (isset($arrSectionData['sectionLevelSale']['Avg End Time'])) {
                                $time = ($arrSectionData['sectionLevelSale']['Avg End Time'] / $arrSectionData['sectionLevelSale']['Total Transaction']);
                                $timeDisplay = gmdate("H:i:s", $time);
                                $arrSectionData['sectionLevelSale']['Avg End Time'] = "$timeDisplay";
                            }
                            if (isset($arrSectionData['sectionLevelSale']['Avg Time Spent'])) {
                                $time = ($arrSectionData['sectionLevelSale']['Avg Time Spent'] / $arrSectionData['sectionLevelSale']['Total Transaction']);
                                $timeDisplay = gmdate("H:i:s", $time);
                                $arrSectionData['sectionLevelSale']['Avg Time Spent'] = "$timeDisplay";
                            }
                            if (isset($arrSectionData['sectionLevelSale']['Avg km Travelled'])) {
                                $arrSectionData['sectionLevelSale']['Avg km Travelled'] = round(($arrSectionData['sectionLevelSale']['Avg km Travelled'] / $arrSectionData['sectionLevelSale']['Breeze Users']) / $getDate, 2);
                            }
                            if (isset($arrSectionData['sectionLevelSale']['Total Sales'])) {
                                $arrSectionData['sectionLevelSale']['Total Sales'] = round(($arrSectionData['sectionLevelSale']['Total Sales'] / $arrSectionData['sectionLevelSale']['Value M']), 2);
                            }

                            foreach ($arrSectionData['wdData'] as &$arrWdCodeData) {
                                if (isset($arrWdCodeData['wdLevelSale']['Avg Start Time'])) {
                                    $time = ($arrWdCodeData['wdLevelSale']['Avg Start Time'] / $arrWdCodeData['wdLevelSale']['Total Transaction']);
                                    $timeDisplay = gmdate("H:i:s", $time);
                                    $arrWdCodeData['wdLevelSale']['Avg Start Time'] = "$timeDisplay";
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['Avg End Time'])) {
                                    $time = ($arrWdCodeData['wdLevelSale']['Avg End Time'] / $arrWdCodeData['wdLevelSale']['Total Transaction']);
                                    $timeDisplay = gmdate("H:i:s", $time);
                                    $arrWdCodeData['wdLevelSale']['Avg End Time'] = "$timeDisplay";
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['Avg Time Spent'])) {
                                    $time = ($arrWdCodeData['wdLevelSale']['Avg Time Spent'] / $arrWdCodeData['wdLevelSale']['Total Transaction']);
                                    $timeDisplay = gmdate("H:i:s", $time);
                                    $arrWdCodeData['wdLevelSale']['Avg Time Spent'] = "$timeDisplay";
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['Avg km Travelled'])) {
                                    $arrWdCodeData['wdLevelSale']['Avg km Travelled'] = round(($arrWdCodeData['wdLevelSale']['Avg km Travelled'] / $arrWdCodeData['wdLevelSale']['Breeze Users']) / $getDate, 2);
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['Total Sales'])) {
                                    $arrWdCodeData['wdLevelSale']['Total Sales'] = round(($arrWdCodeData['wdLevelSale']['Total Sales'] / $arrWdCodeData['wdLevelSale']['Value M']), 2);
                                }

                                foreach ($arrWdCodeData['teamTypeData'] as &$arrTeamTypeData) {
                                    if (isset($arrTeamTypeData['teamTypeLevelSale']['Avg Start Time'])) {
                                        $time = ($arrTeamTypeData['teamTypeLevelSale']['Avg Start Time'] / $arrTeamTypeData['teamTypeLevelSale']['Total Transaction']);
                                        $timeDisplay = gmdate("H:i:s", $time);
                                        $arrTeamTypeData['teamTypeLevelSale']['Avg Start Time'] = "$timeDisplay";
                                    }
                                    if (isset($arrTeamTypeData['teamTypeLevelSale']['Avg End Time'])) {
                                        $time = ($arrTeamTypeData['teamTypeLevelSale']['Avg End Time'] / $arrTeamTypeData['teamTypeLevelSale']['Total Transaction']);
                                        $timeDisplay = gmdate("H:i:s", $time);
                                        $arrTeamTypeData['teamTypeLevelSale']['Avg End Time'] = "$timeDisplay";
                                    }
                                    if (isset($arrTeamTypeData['teamTypeLevelSale']['Avg Time Spent'])) {
                                        $time = ($arrTeamTypeData['teamTypeLevelSale']['Avg Time Spent'] / $arrTeamTypeData['teamTypeLevelSale']['Total Transaction']);
                                        $timeDisplay = gmdate("H:i:s", $time);
                                        $arrTeamTypeData['teamTypeLevelSale']['Avg Time Spent'] = "$timeDisplay";
                                    }
                                    if (isset($arrTeamTypeData['teamTypeLevelSale']['Avg km Travelled'])) {
                                        $arrTeamTypeData['teamTypeLevelSale']['Avg km Travelled'] = round(($arrTeamTypeData['teamTypeLevelSale']['Avg km Travelled'] / $arrTeamTypeData['teamTypeLevelSale']['Breeze Users']) / $getDate, 2);
                                    }
                                    if (isset($arrTeamTypeData['teamTypeLevelSale']['Total Sales'])) {
                                        $arrTeamTypeData['teamTypeLevelSale']['Total Sales'] = round(($arrTeamTypeData['teamTypeLevelSale']['Total Sales'] / $arrTeamTypeData['teamTypeLevelSale']['Value M']), 2);
                                    }

                                    foreach ($arrTeamTypeData['teamData'] as &$arrTeamData) {
                                        if (isset($arrTeamData['teamLevelSale']['Avg Start Time'])) {
                                            $time = ($arrTeamData['teamLevelSale']['Avg Start Time'] / $arrTeamData['teamLevelSale']['Total Transaction']);
                                            $timeDisplay = gmdate("H:i:s", $time);
                                            $arrTeamData['teamLevelSale']['Avg Start Time'] = "$timeDisplay";
                                        }
                                        if (isset($arrTeamData['teamLevelSale']['Avg End Time'])) {
                                            $time = ($arrTeamData['teamLevelSale']['Avg End Time'] / $arrTeamData['teamLevelSale']['Total Transaction']);
                                            $timeDisplay = gmdate("H:i:s", $time);
                                            $arrTeamData['teamLevelSale']['Avg End Time'] = "$timeDisplay";
                                        }
                                        if (isset($arrTeamData['teamLevelSale']['Avg Time Spent'])) {
                                            $time = ($arrTeamData['teamLevelSale']['Avg Time Spent'] / $arrTeamData['teamLevelSale']['Total Transaction']);
                                            $timeDisplay = gmdate("H:i:s", $time);
                                            $arrTeamData['teamLevelSale']['Avg Time Spent'] = "$timeDisplay";
                                        }
                                        if (isset($arrTeamData['teamLevelSale']['Avg km Travelled'])) {
                                            $arrTeamData['teamLevelSale']['Avg km Travelled'] = round(($arrTeamData['teamLevelSale']['Avg km Travelled'] / $arrTeamData['teamLevelSale']['Breeze Users']) / $getDate, 2);
                                        }
                                        if (isset($arrTeamData['teamLevelSale']['Total Sales'])) {
                                            $arrTeamData['teamLevelSale']['Total Sales'] = round(($arrTeamData['teamLevelSale']['Total Sales'] / $arrTeamData['teamLevelSale']['Value M']), 2);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $sumOfNoOfUsers = 0;
            $sumStartTime = 0;
            $sumEndTime   = 0;
            $sumTimeSpent = 0;
            $sumActKmTravelled  = 0;
            $sumPlanned   = 0;
            $sumRevisit   = 0;
            $sumTotalSales   = 0;
            $sumValueM   = 0;
            $districtCount = count($monthWiseSales);

            foreach ($monthWiseSales as $districtRow) {
                $sumOfNoOfUsers += $districtRow["districtLevelSale"]["Breeze Users"] ?? 0;
                $sumStartTime += $districtRow["districtLevelSale"]["ACT Start Time"] ?? 0;
                $sumEndTime += $districtRow["districtLevelSale"]["ACT End Time"] ?? 0;
                $sumTimeSpent += $districtRow["districtLevelSale"]["ACT Time Spent"] ?? 0;
                $sumActKmTravelled  += $districtRow["districtLevelSale"]["ACT km Travelled"] ?? 0;
                $sumPlanned  += $districtRow["districtLevelSale"]["Planned Outlets"] ?? 0;
                $sumRevisit  += $districtRow["districtLevelSale"]["Outlets ReVisit"] ?? 0;
                $sumTotalSales  += $districtRow["districtLevelSale"]["ACT Total Sales"] ?? 0;
                $sumValueM  += $districtRow["districtLevelSale"]["Value M"] ?? 0;
            }
            if ($sumStartTime > 0) {
                $sumStartTime = round((int)($sumStartTime / $sumOfNoOfUsers), 0);
                // echo $sumStartTime;die;
                // $tthours = round((int)$sumStartTime / 60);
                // $ttminutes = ((int)$sumStartTime) % 60;
                $ttimeDisplay = gmdate("H:i:s", $sumStartTime);
                $totalSumDistrictLevelSale['Avg Start Time'] = "$ttimeDisplay";
            }
            if ($sumEndTime > 0) {
                $sumEndTime = round((int)($sumEndTime / $sumOfNoOfUsers), 0);
                $ttimeDisplay = gmdate("H:i:s", $sumEndTime);
                $totalSumDistrictLevelSale['Avg End Time'] = "$ttimeDisplay";
            }
            if ($sumActKmTravelled > 0) {
                $totalSumDistrictLevelSale['Avg km Travelled'] =
                    round((int)($sumActKmTravelled / $sumOfNoOfUsers) / $getDate, 0);
                // $totalSumDistrictLevelSale['Avg km Travelled'] = $sumActKmTravelled;
            }
            if ($sumTimeSpent > 0) {
                $sumTimeSpent = round((int)($sumTimeSpent / $sumOfNoOfUsers), 0);
                $ttimeDisplay = gmdate("H:i:s", $sumTimeSpent);
                $totalSumDistrictLevelSale['Avg Time Spent'] = "$ttimeDisplay";
            }
            if ($sumPlanned > 0) {
                $totalSumDistrictLevelSale['Planned Outlets'] = $sumPlanned;
            }
            if ($sumRevisit > 0) {
                $totalSumDistrictLevelSale['Outlets ReVisit'] = $sumRevisit;
            }
            if ($sumTotalSales > 0) {
                $totalSumDistrictLevelSale['Total Sales'] = round((int)($sumTotalSales / $sumValueM), 2);
                // $totalSumDistrictLevelSale['Total Sales'] = $sumTotalSales;
            }
        }

        // Response Data
        $arrResponse = array(
            "MonthsAndYears" => $labelsToBePrint,
            "districtData" => $monthWiseSales,
            "TotalSum" => $totalSumDistrictLevelSale,
            "Title" => "Survey"
        );

        return $arrResponse;
    }

    final public function getMonthlySalesGraphData($monthlySalesData = null)
    {
        if ($monthlySalesData === null) {
            $monthlySalesData = $this->getMonthlySalesData();
        }
        $level = getFormData($this->_data, "graphLevel");
        if (!$level) {
            $level = "district";
        }
        $districtData = $monthlySalesData['districtData'] ?? [];
        $flatEntries = [];

        foreach ($districtData as $districtRow) {
            if ($level === 'district') {
                $sale = $districtRow['districtLevelSale'] ?? [];
                $flatEntries[] = [
                    'label'   => $districtRow['district'] ?? 'N/A',
                    'planned' => (float)($sale['Planned Outlets'] ?? 0),
                    'revisit' => (float)($sale['Outlets ReVisit'] ?? 0),
                    'sales'   => (float)($sale['Total Sales']     ?? 0),
                ];
                continue;
            }

            foreach ($districtRow['branchData'] ?? [] as $branchRow) {
                if ($level === 'branch') {
                    $sale = $branchRow['branchLevelSale'] ?? [];
                    $flatEntries[] = [
                        'label'   => $branchRow['branch_name'] ?? 'N/A',
                        'planned' => (float)($sale['Planned Outlets'] ?? 0),
                        'revisit' => (float)($sale['Outlets ReVisit'] ?? 0),
                        'sales'   => (float)($sale['Total Sales']     ?? 0),
                    ];
                    continue;
                }

                foreach ($branchRow['circleData'] ?? [] as $circleRow) {
                    if ($level === 'circle') {
                        $sale = $circleRow['circleLevelSale'] ?? [];
                        $flatEntries[] = [
                            'label'   => $circleRow['circle'] ?? 'N/A',
                            'planned' => (float)($sale['Planned Outlets'] ?? 0),
                            'revisit' => (float)($sale['Outlets ReVisit'] ?? 0),
                            'sales'   => (float)($sale['Total Sales']     ?? 0),
                        ];
                        continue;
                    }

                    foreach ($circleRow['sectionData'] ?? [] as $sectionRow) {
                        if ($level === 'section') {
                            $sale = $sectionRow['sectionLevelSale'] ?? [];
                            $flatEntries[] = [
                                'label'   => $sectionRow['section'] ?? 'N/A',
                                'planned' => (float)($sale['Planned Outlets'] ?? 0),
                                'revisit' => (float)($sale['Outlets ReVisit'] ?? 0),
                                'sales'   => (float)($sale['Total Sales']     ?? 0),
                            ];
                            continue;
                        }

                        foreach ($sectionRow['wdData'] ?? [] as $wdRow) {
                            if ($level === 'wdCode') {
                                $sale = $wdRow['wdLevelSale'] ?? [];
                                $flatEntries[] = [
                                    'label'   => $wdRow['wd_code'] ?? 'N/A',
                                    'planned' => (float)($sale['Planned Outlets'] ?? 0),
                                    'revisit' => (float)($sale['Outlets ReVisit'] ?? 0),
                                    'sales'   => (float)($sale['Total Sales']     ?? 0),
                                ];
                            }
                        }
                    }
                }
            }
        }

        $merged = [];
        foreach ($flatEntries as $entry) {
            $lbl = $entry['label'];
            if (!isset($merged[$lbl])) {
                $merged[$lbl] = ['planned' => 0, 'revisit' => 0, 'sales' => 0];
            }
            $merged[$lbl]['planned'] += $entry['planned'];
            $merged[$lbl]['revisit'] += $entry['revisit'];
            $merged[$lbl]['sales']   += $entry['sales'];
        }

        $xAxisLabels  = [];
        $plannedArr   = [];
        $revisitArr   = [];
        $salesArr     = [];

        foreach ($merged as $label => $vals) {
            $xAxisLabels[] = $label;
            $plannedArr[]  = round($vals['planned'], 2);
            $revisitArr[]  = round($vals['revisit'], 2);
            $salesArr[]    = round($vals['sales'],   2);
        }

        $graphData = [
            'seriesData' => [
                ['name' => 'Planned Outlets', 'data' => $plannedArr],
                ['name' => 'Outlets ReVisit', 'data' => $revisitArr],
                ['name' => 'Total Sales',     'data' => $salesArr],
            ],
            'xAxisLabels' => $xAxisLabels,
            'level'       => $level,
        ];

        return [
            "salesGraphData" => $graphData,
            "height"         => "800px",
        ];
    }

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
