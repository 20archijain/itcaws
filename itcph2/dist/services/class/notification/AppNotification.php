<?php

require_once $include_path . "defined_index.php";
// Include Composer autoloader
// require_once __DIR__ . '/../../../vendor/autoload.php';

// // Initialize Firebase
// use Kreait\Firebase\Factory;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification;

// phpcs:ignore
class AppNotification
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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0  $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0  $where order by a.branch_name";
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
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code  $where order by b.circle";
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
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code  $where order by b.section";
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
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code  $where order by b.wd_code";
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
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code  $where order by c.wd_market";
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
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code  $where order by c.wd_pop_group";
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
        global $ARR_TEAM_TYPES; // use your global team types mapping
        $arrData = array(
            array("label" => "All", "value" => "all")
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
        $query = "SELECT DISTINCT b.is_type FROM tblbranch AS a, tblproject_team AS b WHERE a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where ORDER BY b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);
        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $isType = $row['is_type'];
                $label = isset($ARR_TEAM_TYPES[$isType]) ? $ARR_TEAM_TYPES[$isType] : "Unknown";

                $arrData[] = array(
                    "label" => $label,
                    "value" => $isType
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
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0  $where order by b.team_name";
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


    final public function addNotification()
    {
        $where = $this->getCondition();
        $teamIds = [];
        if (isset($where) && $where != "") {
            $query = "SELECT b.team_id FROM tblbranch as a, tblproject_team AS b, tblbranch as d, tblmapping_wd as e" .
                " WHERE a.branch_id = b.branch_id AND b.branch_id = d.branch_id AND a.dstatus = 0  AND b.wd_code = e.wd_code $where";
            $rsAction = null;
            $iActionRows = 0;
            $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);
            if ($iActionRows > 0) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $teamIds[] = $row["team_id"];
                }
            }
        } elseif ($where == "") {
            $teamIds[] = 0;
        }

        if (empty($teamIds)) {
            $arrMessage = responseMessage(array($GLOBALS['DS_NOT_FOUND']), 2);
            echo json_encode($arrMessage);
            return;
        }

        $notificationTitle = getFormData($this->_data, "notificationTitle");
        $notificationText = getFormData($this->_data, "notificationText");
        // $sendType = getFormData($this->_data, "sendType");
        $notificationType =  1;

        $cDT = currentDateTime();
        $cD = currentDate();
        $notificationTable = $this->_tables["APP_NOTIFICATION_TABLE"];

        // Initialize Firebase
        // try {
        //     $firebase = (new Factory())
        //         ->withServiceAccount(__DIR__ . '/../../../../uproots/mobile_services/includes/firebase-service-account.json')
        //         ->createMessaging();
        // } catch (Exception $e) {
        //     $arrMessage = responseMessage(array("Firebase initialization failed: " . $e->getMessage()), 2);
        //     echo json_encode($arrMessage);
        //     return;
        // }

        $arrStatus = [];
        foreach ($teamIds as $teamId) {
            // Add to database
            $cols = "team_id, notification_type, notification_title, notification_text, notification_date, notification_datetime, rcd, rdt";
            $vals = "?, ?, ?, ?, ?, ?, ?, ?";
            $arrParams = array($teamId, $notificationType, $notificationTitle, $notificationText, $cD, $cDT, $cD, $cDT);

            $this->_dbConn->BeginTransaction();
            $iStatus = addRecord($this->_dbConn, $notificationTable, $cols, $vals, $arrParams);
            $arrStatus[] = $iStatus;

            if (in_array(0, $arrStatus)) {
                $this->_dbConn->RollbackTransaction();
                $arrMessage = responseMessage(array($GLOBALS['NOTIFICATION_NOT_ADDED']), 2);
                echo json_encode($arrMessage);
                return;
            }

            // Send Firebase notification
            // try {
            // Individual notification: Send to devices with team_id
            // $deviceTokens = getRowsColumn($this->_dbConn, $this->_tables["PROJECT_TEAM_TABLE"], "fcm_token", "AND team_id = ?", array($teamId));

            // if (!empty($deviceTokens)) {
            //     $message = CloudMessage::new()
            //         ->withNotification(Notification::create($notificationTitle, $notificationText))
            //         ->withData(['type' => (string)$notificationType, 'team_id' => (string)$teamId]);
            //     $response = $firebase->sendMulticast($message, $deviceTokens);
            //     error_log("Individual notification sent to team_id $teamId: " . json_encode($response));
            // } else {
            //     error_log("No device tokens found for team_id $teamId");
            // }
            // }
            // } catch (Exception $e) {
            // $this->_dbConn->RollbackTransaction();
            // $arrMessage = responseMessage(array("Firebase notification failed: " . $e->getMessage()), 2);
            // error_log("Firebase error for team_id $teamId: " . $e->getMessage());
            // echo json_encode($arrMessage);
            // return;
            // }

            $this->_dbConn->CommitTransaction();
        }

        $arrMessage = responseMessage(array($GLOBALS['NOTIFICATION_ADDED']), 1);
        echo json_encode($arrMessage);
    }
}
