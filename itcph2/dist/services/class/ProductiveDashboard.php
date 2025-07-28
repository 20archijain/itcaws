<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ProductiveDashboard
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

    private function getDistrictList()
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

    private function getBranchList($cond = "")
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

    private function getCircleList($cond = "")
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
        $query = "select Distinct b.circle, c.circle_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND b.wd_code = c.wd_code AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.s_id = 99 $where order by b.circle";
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

    private function getSectionList($cond = "")
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
        $query = "select Distinct b.section, c.section_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND b.wd_code = c.wd_code AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.s_id = 99 $where order by b.section";
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

    private function getWdCodeList($cond = "")
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
        $query = "select Distinct b.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND b.wd_code = c.wd_code AND a.dstatus = 0" .
            " AND b.dstatus = 0 AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.s_id = 99 $where order by b.wd_code";
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

    private function getDsTypeList($cond = "")
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
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 $where order by b.is_type";
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

    private function getTeamsList($cond = "")
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

    private function getWdMarketList($cond = "")
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
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND b.wd_code = c.wd_code AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.s_id = 99 $where order by c.wd_market";
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

    private function getWdPopGroupList($cond = "")
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
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND b.wd_code = c.wd_code AND a.dstatus = 0" .
            " AND b.dstatus = 0 AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.s_id = 99 $where order by c.wd_pop_group";
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

    private function getCondition()
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

    final public function getConditionForCategoryAndProduct()
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
                $condition .= " AND a.category_name IN ($category)";
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
                $condition .= " AND a.product_name IN ($product)";
            }
        }

        return $condition;
    }

    final public function getConditionForYearAndMonth($column = "a.activity_date")
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
        $arrResult = array(
            "monthlySalesData" => $this->getMonthlySalesData(),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function getSumPart($arrSalesColumns)
    {
        $arrSum = array();
        foreach ($arrSalesColumns as $branch_id => $salesColumns) {
            $sSalesColumns = implode("+", $salesColumns);  // Create sum for current branch
            $arrSum[] = "SUM(IF(b.branch_id = $branch_id, $sSalesColumns, 0)) AS totalSales_$branch_id";
        }

        return implode(", ", $arrSum);
    }

    public function getMonthlySalesData()
    {
        $where = $this->getCondition();
        $whereCategoryAndProduct = $this->getConditionForCategoryAndProduct();
        $yearAndMonthCond = $this->getConditionForYearAndMonth();
        $yearAndMonthCond2 = $this->getConditionForYearAndMonth("a.capture_date");
        $getDate = $this->getDate();

        if ($where) {
            $where = str_replace(" team_id", " b.team_id", $where);
        }
        $arrResponse = array();
        $arrSalesColumns = array(); // Reset the sales columns array here
        // $arrMonthYear = array();
        $monthWiseSales = array();
        $totalSumDistrictLevelSale = array();
        // $finalArrayWithAvgDistrictLevelSale = array();

        $labelsToBePrint = [
            "No of Users",
            "No of days present",
            "No of days qualified",
            "No of days route adhered",
            "Planned Outlets",
            "Visited Outlets",
            "Productive Outlets",
            "Survey (M) (MTD)",
            "Survey (M) (Daily Average)",
            "KMs Travelled per Day",
            "Time in market",
            "CFT per outlet",
            "CFT per days",
        ];

        $labels = [
            "No of Users",
            "No of days present",
            "No of days qualified",
            "No of days route adhered",
            "Planned Outlets",
            "Visited Outlets",
            "Productive Outlets",
            "Survey (M) (MTD)",
            "Survey (M) (Daily Average)",
            "KMs Travelled per Day",
            "Time in market",
            "CFT per outlet",
            "CFT per days",
            "Total Transaction",
        ];

        // Get all sales-related columns
        $query = "SELECT a.summary_column_name, a.branch_id FROM tblbranch_pickupstock_products AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
            " WHERE a.branch_id = b.branch_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.is_type != 5 AND b.wd_code = e.wd_code $where $whereCategoryAndProduct GROUP BY a.branch_id, a.summary_column_name";
        $rsAction = null;
        $iActionRows = 0;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $arrBranchNames = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $branch_id = $row["branch_id"];
                // $circle = $row["circle"];
                // $section = $row["section"];
                $summary_column_name = $row["summary_column_name"];
                $arrSalesColumns[$branch_id][] = $summary_column_name;  // Store sales columns by branch
                // $BranchIds[] = $branch_id;
            }

            // Process sales data per branch and month
            // foreach ($arrSalesColumns as $branch_id => $salesColumns) {
            // Reset $arrSalesColumns for the next branch
            // $sSalesColumns = implode("+", $salesColumns);  // Create sum for current branch
            foreach ($labels as $index => $value) {
                $sSumPart = "";
                if ($index == 0) {
                    $queryNew = "SELECT COUNT(team_id) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE b.branch_id = d.branch_id AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 1) {
                    $queryNew = "SELECT count(a.team_id) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                    // echo $queryNew;
                    // die;
                } elseif ($index == 2) {
                    $queryNew = "SELECT count(a.is_qualified) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code AND is_qualified = '1'" .
                        " $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 3) {
                    $queryNew = "SELECT count(is_beat_adherence) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code AND a.is_beat_adherence = 'Yes' $where $yearAndMonthCond" .
                        " GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 4) {
                    $queryNew = "SELECT count(a.rec_id) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblroute_details AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 5) {
                    $queryNew = "SELECT count(distinct a.ques_3) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblsurvey_response_details AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond2 GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 6) {
                    $queryNew = "SELECT count(distinct a.ques_3) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblsurvey_response_details AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND a.ques_4 = 'Yes' AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond2" .
                        " GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 7) {
                    $sSumPart = $this->getSumPart($arrSalesColumns);
                    $queryNew = "SELECT $sSumPart, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 8) {
                    $sSumPart = $this->getSumPart($arrSalesColumns);
                    $queryNew = "SELECT $sSumPart, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 9) {
                    $queryNew = "SELECT SUM(total_meter_travelled / 1000) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 10) {
                    $queryNew = "SELECT SUM(time_in_market) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } elseif ($index == 11) {
                    $queryNew = "SELECT SUM(a.call_time) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblsurvey_response_details AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond2 GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                    // echo $queryNew;die;
                } elseif ($index == 12) {
                    $queryNew = "SELECT SUM(a.call_time) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblsurvey_response_details AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond2 GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                    // echo $queryNew;die;
                } elseif ($index == 13) {
                    $queryNew = "SELECT SUM(total_sales_deliveries + total_other_shops) as totalSales, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where $yearAndMonthCond GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                } else {
                    $sSumPart = $this->getSumPart($arrSalesColumns);
                    $queryNew = "SELECT $sSumPart, b.branch_id, b.circle, b.section, b.wd_code, b.team_name, d.district FROM tblvands_summary AS a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                        " WHERE a.team_id = b.team_id AND b.branch_id = d.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = '99' AND b.wd_code = e.wd_code $where GROUP BY b.team_id, b.branch_id, b.circle, b.section, b.wd_code" .
                        " ORDER BY d.district, d.main_branch, b.circle, b.section, b.wd_code, b.team_name";
                }

                $rsAction1 = null;
                $iActionRows1 = 0;
                $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);

                if ($iActionRows1 > 0) {
                    while ($row1 = $this->_dbConn->GetData($rsAction1)) {
                        if (!isset($arrBranchNames[$branch_id])) {
                            $branch_name = getRowColumn($this->_dbConn, $this->_tables["BRANCH_TABLE"], "main_branch", "branch_id = $branch_id");
                            $arrBranchNames[$branch_id] = $branch_name;
                        } else {
                            $branch_name = $arrBranchNames[$branch_id];
                        }
                        $district = $row1['district'];
                        $branch_id = $row1['branch_id'];
                        $circle = $row1['circle'];
                        $section = $row1['section'];
                        $wd_code = $row1['wd_code'];
                        $team_name = $row1['team_name'];
                        $totalSales = $sSumPart ? round($row1["totalSales_$branch_id"]) : round($row1['totalSales']);

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

                        // Branch Level Sales
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
                        $WDIndex = array_search($wd_code, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"], "wd_code"));
                        if ($WDIndex === false) {
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][] = array(
                                "wd_code" => $wd_code,
                                "wdCode" => $wd_code,
                                "wdLevelSale" => array(),
                                "teamData" => array(),
                            );
                            $WDIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"]) - 1;
                        }

                        $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$value] =
                            round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["wdLevelSale"][$value] ?? 0) + $totalSales, 2);

                        // Team Sales
                        $teamIndex = array_search($team_name, array_column($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"], "team_name"));
                        if ($teamIndex === false) {
                            $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][] = array(
                                "team_name" => $team_name,
                                "teamLevelSale" => array(),
                            );
                            $teamIndex = count($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"]) - 1;
                        }

                        $monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["teamLevelSale"][$value] =
                            round(($monthWiseSales[$districtIndex]["branchData"][$branchIndex]["circleData"][$circleIndex]["sectionData"][$sectionIndex]["wdData"][$WDIndex]["teamData"][$teamIndex]["teamLevelSale"][$value] ?? 0) + $totalSales, 2);
                    }
                }
            }
            // }

            foreach ($monthWiseSales as &$arrDistrictData) {
                if (isset($arrDistrictData['districtLevelSale']['CFT per days']) && isset($arrDistrictData['districtLevelSale']['No of days present'])) {
                    $time = $arrDistrictData['districtLevelSale']['No of days present'] > 0 ?
                        $arrDistrictData['districtLevelSale']['CFT per days'] / $arrDistrictData['districtLevelSale']['No of days present'] : 0;
                    $seconds = round((int)$time / 1000);
                    $minutes = round((int)$seconds / 60);
                    $hours = round((int)$minutes / 60);
                    // $minutes = $minutes % 60;
                    $arrDistrictData['districtLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                }

                if (isset($arrDistrictData['districtLevelSale']['No of days present'])) {
                    $arrDistrictData['districtLevelSale']['No of days present'] = $arrDistrictData['districtLevelSale']['No of Users'] > 0 ?
                        round($arrDistrictData['districtLevelSale']['No of days present'] / $arrDistrictData['districtLevelSale']['No of Users'], 0) : 0;
                }
                if (isset($arrDistrictData['districtLevelSale']['No of days qualified'])) {
                    $arrDistrictData['districtLevelSale']['No of days qualified'] = $arrDistrictData['districtLevelSale']['No of Users'] > 0 ?
                        round($arrDistrictData['districtLevelSale']['No of days qualified'] / $arrDistrictData['districtLevelSale']['No of Users'], 0) : 0;
                }
                if (isset($arrDistrictData['districtLevelSale']['No of days route adhered'])) {
                    $arrDistrictData['districtLevelSale']['No of days route adhered'] = $arrDistrictData['districtLevelSale']['No of Users'] > 0 ?
                        round($arrDistrictData['districtLevelSale']['No of days route adhered'] / $arrDistrictData['districtLevelSale']['No of Users'], 0) : 0;
                }
                if (isset($arrDistrictData['districtLevelSale']['KMs Travelled per Day'])) {
                    $arrDistrictData['districtLevelSale']['KMs Travelled per Day'] = $arrDistrictData['districtLevelSale']['No of Users'] > 0 ?
                        round(($arrDistrictData['districtLevelSale']['KMs Travelled per Day'] / $arrDistrictData['districtLevelSale']['No of Users']) / $getDate, 0) : 0;
                }
                if (isset($arrDistrictData['districtLevelSale']['CFT per day'])) {
                    $arrDistrictData['districtLevelSale']['CFT per day'] = $arrDistrictData['districtLevelSale']['No of Users'] > 0 ?
                        round($arrDistrictData['districtLevelSale']['CFT per day'] / $arrDistrictData['districtLevelSale']['No of Users'], 0) : 0;
                }
                if (isset($arrDistrictData['districtLevelSale']['Time in market'])) {
                    $time = ($arrDistrictData['districtLevelSale']['Time in market'] / $arrDistrictData['districtLevelSale']['No of Users']) / $getDate;
                    $hours = round((int)$time / 60);
                    $minutes = ((int)$time) % 60;
                    $arrDistrictData['districtLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                }

                if (isset($arrDistrictData['districtLevelSale']['Survey (M) (Daily Average)'])) {
                    $arrDistrictData['districtLevelSale']['Survey (M) (Daily Average)'] = round($arrDistrictData['districtLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                }

                if (isset($arrDistrictData['districtLevelSale']['CFT per outlet']) && isset($arrDistrictData['districtLevelSale']['Total Transaction'])) {
                    $time = $arrDistrictData['districtLevelSale']['CFT per outlet'] / $arrDistrictData['districtLevelSale']['Total Transaction'];
                    $seconds = round((int)$time / 1000);
                    $minutes = round((int)$seconds / 60);
                    $hours = round((int)$minutes / 60);
                    // $minutes = $minutes % 60;
                    $arrDistrictData['districtLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                }

                foreach ($arrDistrictData['branchData'] as &$arrBranchData) {
                    if (isset($arrBranchData['branchLevelSale']['CFT per days']) && isset($arrBranchData['branchLevelSale']['No of days present'])) {
                        $time = $arrBranchData['branchLevelSale']['CFT per days'] / $arrBranchData['branchLevelSale']['No of days present'];
                        $seconds = round((int)$time / 1000);
                        $minutes = round((int)$seconds / 60);
                        $hours = round((int)$minutes / 60);
                        // $minutes = $minutes % 60;
                        $arrBranchData['branchLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                    }

                    if (isset($arrBranchData['branchLevelSale']['No of days present'])) {
                        $arrBranchData['branchLevelSale']['No of days present'] = round($arrBranchData['branchLevelSale']['No of days present'] / $arrBranchData['branchLevelSale']['No of Users'], 0);
                    }
                    if (isset($arrBranchData['branchLevelSale']['No of days qualified'])) {
                        $arrBranchData['branchLevelSale']['No of days qualified'] = round($arrBranchData['branchLevelSale']['No of days qualified'] / $arrBranchData['branchLevelSale']['No of Users'], 0);
                    }
                    if (isset($arrBranchData['branchLevelSale']['No of days route adhered'])) {
                        $arrBranchData['branchLevelSale']['No of days route adhered'] = round($arrBranchData['branchLevelSale']['No of days route adhered'] / $arrBranchData['branchLevelSale']['No of Users'], 0);
                    }
                    if (isset($arrBranchData['branchLevelSale']['KMs Travelled per Day'])) {
                        $arrBranchData['branchLevelSale']['KMs Travelled per Day'] = round(($arrBranchData['branchLevelSale']['KMs Travelled per Day'] / $arrBranchData['branchLevelSale']['No of Users']) / $getDate, 0);
                    }
                    if (isset($arrBranchData['branchLevelSale']['CFT per day'])) {
                        $arrBranchData['branchLevelSale']['CFT per day'] = round($arrBranchData['branchLevelSale']['CFT per day'] / $arrBranchData['branchLevelSale']['No of Users'], 0);
                    }

                    if (isset($arrBranchData['branchLevelSale']['Time in market'])) {
                        $time = ($arrBranchData['branchLevelSale']['Time in market'] / $arrBranchData['branchLevelSale']['No of Users']) / $getDate;
                        $hours = round((int)$time / 60);
                        $minutes = ((int)$time) % 60;
                        $arrBranchData['branchLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                    }

                    if (isset($arrBranchData['branchLevelSale']['Survey (M) (Daily Average)'])) {
                        $arrBranchData['branchLevelSale']['Survey (M) (Daily Average)'] = round($arrBranchData['branchLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                    }

                    if (isset($arrBranchData['branchLevelSale']['CFT per outlet']) && isset($arrBranchData['branchLevelSale']['Total Transaction'])) {
                        $time = ($arrBranchData['branchLevelSale']['Total Transaction'] > 0) ? $arrBranchData['branchLevelSale']['CFT per outlet'] / $arrBranchData['branchLevelSale']['Total Transaction'] : $arrBranchData['branchLevelSale']['CFT per outlet'];
                        $seconds = round((int)$time / 1000);
                        $minutes = round((int)$seconds / 60);
                        $hours = round((int)$minutes / 60);
                        // $minutes = $minutes % 60;
                        $arrBranchData['branchLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                    }

                    foreach ($arrBranchData['circleData'] as &$arrCircleData) {
                        if (isset($arrCircleData['circleLevelSale']['CFT per days']) && isset($arrCircleData['circleLevelSale']['No of days present'])) {
                            $time = $arrCircleData['circleLevelSale']['CFT per days'] / $arrCircleData['circleLevelSale']['No of days present'];
                            $seconds = round($time / 1000);
                            $minutes = round($seconds / 60);
                            $hours = round($minutes / 60);
                            // $minutes = $minutes % 60;
                            $arrCircleData['circleLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                        }

                        if (isset($arrCircleData['circleLevelSale']['No of days present'])) {
                            $arrCircleData['circleLevelSale']['No of days present'] = round($arrCircleData['circleLevelSale']['No of days present'] / $arrCircleData['circleLevelSale']['No of Users'], 0);
                        }
                        if (isset($arrCircleData['circleLevelSale']['No of days qualified'])) {
                            $arrCircleData['circleLevelSale']['No of days qualified'] = round($arrCircleData['circleLevelSale']['No of days qualified'] / $arrCircleData['circleLevelSale']['No of Users'], 0);
                        }
                        if (isset($arrCircleData['circleLevelSale']['No of days route adhered'])) {
                            $arrCircleData['circleLevelSale']['No of days route adhered'] = round($arrCircleData['circleLevelSale']['No of days route adhered'] / $arrCircleData['circleLevelSale']['No of Users'], 0);
                        }
                        if (isset($arrCircleData['circleLevelSale']['KMs Travelled per Day'])) {
                            $arrCircleData['circleLevelSale']['KMs Travelled per Day'] = round(($arrCircleData['circleLevelSale']['KMs Travelled per Day'] / $arrCircleData['circleLevelSale']['No of Users']) / $getDate, 0);
                        }
                        if (isset($arrCircleData['circleLevelSale']['CFT per day'])) {
                            $arrCircleData['circleLevelSale']['CFT per day'] = round($arrCircleData['circleLevelSale']['CFT per day'] / $arrCircleData['circleLevelSale']['No of Users'], 0);
                        }

                        if (isset($arrCircleData['circleLevelSale']['Time in market'])) {
                            $time = ($arrCircleData['circleLevelSale']['Time in market'] / $arrCircleData['circleLevelSale']['No of Users']) / $getDate;
                            $hours = round((int)$time / 60);
                            $minutes = ((int)$time) % 60;
                            $arrCircleData['circleLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                        }

                        if (isset($arrCircleData['circleLevelSale']['Survey (M) (Daily Average)'])) {
                            $arrCircleData['circleLevelSale']['Survey (M) (Daily Average)'] = round($arrCircleData['circleLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                        }

                        if (isset($arrCircleData['circleLevelSale']['CFT per outlet']) && isset($arrCircleData['circleLevelSale']['Total Transaction'])) {
                            $time = ($arrCircleData['circleLevelSale']['Total Transaction'] > 0) ? $arrCircleData['circleLevelSale']['CFT per outlet'] / $arrCircleData['circleLevelSale']['Total Transaction'] : $arrCircleData['circleLevelSale']['CFT per outlet'];
                            $seconds = round($time / 1000);
                            $minutes = round($seconds / 60);
                            $hours = round($minutes / 60);
                            // $minutes = $minutes % 60;
                            $arrCircleData['circleLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                        }

                        foreach ($arrCircleData['sectionData'] as &$arrSectionData) {
                            if (isset($arrSectionData['sectionLevelSale']['CFT per days']) && isset($arrSectionData['sectionLevelSale']['No of days present'])) {
                                $time = $arrSectionData['sectionLevelSale']['CFT per days'] / $arrSectionData['sectionLevelSale']['No of days present'];
                                $seconds = round($time / 1000);
                                $minutes = round($seconds / 60);
                                $hours = round($minutes / 60);
                                // $minutes = $minutes % 60;
                                $arrSectionData['sectionLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                            }

                            if (isset($arrSectionData['sectionLevelSale']['No of days present'])) {
                                $arrSectionData['sectionLevelSale']['No of days present'] = round($arrSectionData['sectionLevelSale']['No of days present'] / $arrSectionData['sectionLevelSale']['No of Users'], 0);
                            }
                            if (isset($arrSectionData['sectionLevelSale']['No of days qualified'])) {
                                $arrSectionData['sectionLevelSale']['No of days qualified'] = round($arrSectionData['sectionLevelSale']['No of days qualified'] / $arrSectionData['sectionLevelSale']['No of Users'], 0);
                            }
                            if (isset($arrSectionData['sectionLevelSale']['No of days route adhered'])) {
                                $arrSectionData['sectionLevelSale']['No of days route adhered'] = round($arrSectionData['sectionLevelSale']['No of days route adhered'] / $arrSectionData['sectionLevelSale']['No of Users'], 0);
                            }
                            if (isset($arrSectionData['sectionLevelSale']['KMs Travelled per Day'])) {
                                $arrSectionData['sectionLevelSale']['KMs Travelled per Day'] = round(($arrSectionData['sectionLevelSale']['KMs Travelled per Day'] / $arrSectionData['sectionLevelSale']['No of Users']) / $getDate, 0);
                            }
                            if (isset($arrSectionData['sectionLevelSale']['CFT per day'])) {
                                $arrSectionData['sectionLevelSale']['CFT per day'] = round($arrSectionData['sectionLevelSale']['CFT per day'] / $arrSectionData['sectionLevelSale']['No of Users'], 0);
                            }

                            if (isset($arrSectionData['sectionLevelSale']['Time in market'])) {
                                $time = ($arrSectionData['sectionLevelSale']['Time in market'] / $arrSectionData['sectionLevelSale']['No of Users']) / $getDate;
                                $hours = round((int)$time / 60);
                                $minutes = ((int)$time) % 60;
                                $arrSectionData['sectionLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                            }

                            if (isset($arrSectionData['sectionLevelSale']['Survey (M) (Daily Average)'])) {
                                $arrSectionData['sectionLevelSale']['Survey (M) (Daily Average)'] = round($arrSectionData['sectionLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                            }

                            if (isset($arrSectionData['sectionLevelSale']['CFT per outlet']) && isset($arrSectionData['sectionLevelSale']['Total Transaction'])) {
                                $time = ($arrSectionData['sectionLevelSale']['Total Transaction'] > 0) ? $arrSectionData['sectionLevelSale']['CFT per outlet'] / $arrSectionData['sectionLevelSale']['Total Transaction'] : $arrSectionData['sectionLevelSale']['CFT per outlet'];
                                $seconds = round((int)$time / 1000);
                                $minutes = round((int)$seconds / 60);
                                $hours = round((int)$minutes / 60);
                                // $minutes = $minutes % 60;
                                $arrSectionData['sectionLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                            }

                            foreach ($arrSectionData['wdData'] as &$arrWdCodeData) {
                                if (isset($arrWdCodeData['wdLevelSale']['CFT per days']) && isset($arrWdCodeData['wdLevelSale']['No of days present'])) {
                                    $time = $arrWdCodeData['wdLevelSale']['CFT per days'] / $arrWdCodeData['wdLevelSale']['No of days present'];
                                    $seconds = round((int)$time / 1000);
                                    $minutes = round((int)$seconds / 60);
                                    $hours = round((int)$minutes / 60);
                                    // $minutes = $minutes % 60;
                                    $arrWdCodeData['wdLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                                }

                                if (isset($arrWdCodeData['wdLevelSale']['No of days present'])) {
                                    $arrWdCodeData['wdLevelSale']['No of days present'] = round($arrWdCodeData['wdLevelSale']['No of days present'] / $arrWdCodeData['wdLevelSale']['No of Users'], 0);
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['No of days qualified'])) {
                                    $arrWdCodeData['wdLevelSale']['No of days qualified'] = round($arrWdCodeData['wdLevelSale']['No of days qualified'] / $arrWdCodeData['wdLevelSale']['No of Users'], 0);
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['No of days route adhered'])) {
                                    $arrWdCodeData['wdLevelSale']['No of days route adhered'] = round($arrWdCodeData['wdLevelSale']['No of days route adhered'] / $arrWdCodeData['wdLevelSale']['No of Users'], 0);
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['KMs Travelled per Day'])) {
                                    $arrWdCodeData['wdLevelSale']['KMs Travelled per Day'] = round(($arrWdCodeData['wdLevelSale']['KMs Travelled per Day'] / $arrWdCodeData['wdLevelSale']['No of Users']) / $getDate, 0);
                                }
                                if (isset($arrWdCodeData['wdLevelSale']['CFT per day'])) {
                                    $arrWdCodeData['wdLevelSale']['CFT per day'] = round($arrWdCodeData['wdLevelSale']['CFT per day'] / $arrWdCodeData['wdLevelSale']['No of Users'], 0);
                                }

                                if (isset($arrWdCodeData['wdLevelSale']['Time in market'])) {
                                    $time = ($arrWdCodeData['wdLevelSale']['Time in market'] / $arrWdCodeData['wdLevelSale']['No of Users']) / $getDate;
                                    $hours = round((int)$time / 60);
                                    $minutes = ((int)$time) % 60;
                                    $arrWdCodeData['wdLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                                }

                                if (isset($arrWdCodeData['wdLevelSale']['Survey (M) (Daily Average)'])) {
                                    $arrWdCodeData['wdLevelSale']['Survey (M) (Daily Average)'] = round($arrWdCodeData['wdLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                                }

                                if (isset($arrWdCodeData['wdLevelSale']['CFT per outlet']) && isset($arrWdCodeData['wdLevelSale']['Total Transaction'])) {
                                    $time = ($arrWdCodeData['wdLevelSale']['Total Transaction'] > 0) ? $arrWdCodeData['wdLevelSale']['CFT per outlet'] / $arrWdCodeData['wdLevelSale']['Total Transaction'] : $arrWdCodeData['wdLevelSale']['CFT per outlet'];
                                    $seconds = round($time / 1000);
                                    $minutes = round($seconds / 60);
                                    $hours = round($minutes / 60);
                                    // $minutes = $minutes % 60;
                                    $arrWdCodeData['wdLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                                }

                                foreach ($arrWdCodeData['teamData'] as &$arrTeamData) {
                                    if (isset($arrTeamData['teamLevelSale']['CFT per days']) && isset($arrTeamData['teamLevelSale']['No of days present'])) {
                                        $time = $arrTeamData['teamLevelSale']['CFT per days'] / $arrTeamData['teamLevelSale']['No of days present'];
                                        $seconds = round((int)$time / 1000);
                                        $minutes = round((int)$seconds / 60);
                                        $hours = round((int)$minutes / 60);
                                        // $minutes = $minutes % 60;
                                        $arrTeamData['teamLevelSale']['CFT per days'] = "{$hours} h {$minutes} m";
                                    }

                                    if (isset($arrTeamData['teamLevelSale']['No of days present'])) {
                                        $arrTeamData['teamLevelSale']['No of days present'] = round($arrTeamData['teamLevelSale']['No of days present'] / $arrTeamData['teamLevelSale']['No of Users'], 0);
                                    }
                                    if (isset($arrTeamData['teamLevelSale']['No of days qualified'])) {
                                        $arrTeamData['teamLevelSale']['No of days qualified'] = round($arrTeamData['teamLevelSale']['No of days qualified'] / $arrTeamData['teamLevelSale']['No of Users'], 0);
                                    }
                                    if (isset($arrTeamData['teamLevelSale']['No of days route adhered'])) {
                                        $arrTeamData['teamLevelSale']['No of days route adhered'] = round($arrTeamData['teamLevelSale']['No of days route adhered'] / $arrTeamData['teamLevelSale']['No of Users'], 0);
                                    }
                                    if (isset($arrTeamData['teamLevelSale']['KMs Travelled per Day'])) {
                                        $arrTeamData['teamLevelSale']['KMs Travelled per Day'] = round(($arrTeamData['teamLevelSale']['KMs Travelled per Day'] / $arrTeamData['teamLevelSale']['No of Users']) / $getDate, 0);
                                    }
                                    if (isset($arrTeamData['teamLevelSale']['CFT per day'])) {
                                        $arrTeamData['teamLevelSale']['CFT per day'] = round($arrTeamData['teamLevelSale']['CFT per day'] / $arrTeamData['teamLevelSale']['No of Users'], 0);
                                    }

                                    if (isset($arrTeamData['teamLevelSale']['Time in market'])) {
                                        $time = ($arrTeamData['teamLevelSale']['Time in market'] / $arrTeamData['teamLevelSale']['No of Users']) / $getDate;
                                        $hours = round((int)$time / 60);
                                        $minutes = ((int)$time) % 60;
                                        $arrTeamData['teamLevelSale']['Time in market'] = "{$hours} h {$minutes} m";
                                    }

                                    if (isset($arrTeamData['teamLevelSale']['Survey (M) (Daily Average)'])) {
                                        $arrTeamData['teamLevelSale']['Survey (M) (Daily Average)'] = round($arrTeamData['teamLevelSale']['Survey (M) (Daily Average)'] / $getDate, 0);
                                    }

                                    if (isset($arrTeamData['teamLevelSale']['CFT per outlet']) && isset($arrTeamData['teamLevelSale']['Total Transaction'])) {
                                        $time = ($arrTeamData['teamLevelSale']['Total Transaction'] > 0) ? $arrTeamData['teamLevelSale']['CFT per outlet'] / $arrTeamData['teamLevelSale']['Total Transaction'] : $arrTeamData['teamLevelSale']['CFT per outlet'];
                                        $seconds = round((int)$time / 1000);
                                        $minutes = round((int)$seconds / 60);
                                        $hours = round((int)$minutes / 60);
                                        // $minutes = $minutes % 60;
                                        $arrTeamData['teamLevelSale']['CFT per outlet'] = "{$hours} h {$minutes} m";
                                    }
                                }
                            }
                        }
                    }
                }
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
}
