<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class WdMappingManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }

    final public function getCondition()
    {
        $condition = "";
        $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
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
        $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
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
        $circle = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "circle");
        if ($circle) {
            if (!is_array($circle)) {
                $circle = array($circle);
            }
            if (in_array('all', $circle)) {
                $condition .= " ";
            } else {
                $circle = "'" . implode("','", $circle) . "'";
                $condition .= " AND e.circle IN ($circle)";
            }
        }
        $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
        if ($section) {
            if (!is_array($section)) {
                $section = array($section);
            }
            if (in_array('all', $section)) {
                $condition .= " ";
            } else {
                $section = "'" . implode("','", $section) . "'";
                $condition .= " AND e.section IN ($section)";
            }
        }
        $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
        if ($wdCode) {
            if (!is_array($wdCode)) {
                $wdCode = array($wdCode);
            }
            if (in_array('all', $wdCode)) {
                $condition .= " ";
            } else {
                $wdCode = "'" . implode("','", $wdCode) . "'";
                $condition .= " AND e.wd_code IN ($wdCode)";
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
                $condition .= " AND e.wd_market IN ($wdMarket)";
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
                $condition .= " AND e.wd_pop_group IN ($wdPopGroup)";
            }
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $condition .= " AND b.team_id IN $teamList";
        }

        return $condition;
    }

    final public function getAddProjectData()
    {
        $arrResult = array(
            "districtList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['BRANCH_TABLE'], "district", "district", "dstatus = 0"),
            "branchList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['BRANCH_TABLE'], "main_branch", "main_branch", "dstatus = 0"),
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    final public function addProject()
    {
        $district = getFormData($this->_data, "district");
        $branch = getFormData($this->_data, "branch");
        $circle = html_entity_decode(getFormData($this->_data, "circle"));
        $circle_name = html_entity_decode(getFormData($this->_data, "circle_name"));
        $section = html_entity_decode(getFormData($this->_data, "section"));
        $section_name = html_entity_decode(getFormData($this->_data, "section_name"));
        $wdCode = html_entity_decode(getFormData($this->_data, "wd_code"));
        $wd_firm_name = html_entity_decode(getFormData($this->_data, "wd_firm_name"));
        $wd_market = html_entity_decode(getFormData($this->_data, "wd_market"));
        $wd_pop_group = html_entity_decode(getFormData($this->_data, "wd_pop_group"));

        $mappingTable = $this->_tables["WD_MAPPING_TABLE"];

        //check if wd exists
        // Don't use dstatus = 0
        $iStatus = isRecordExist($this->_dbConn, $mappingTable, "wd_code", "wd_code = ?", array($wdCode));

        // Not exist
        if ($iStatus == 0) {
            $cDT = currentDateTime();
            $cD = currentDate();

            $cols = "district, branch, circle, circle_name, section, section_name, wd_code, wd_firm_name, wd_market, wd_pop_group, rcd, rdt";
            $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            $arrParams = array($district, $branch, $circle, $circle_name, $section, $section_name, $wdCode, $wd_firm_name, $wd_market, $wd_pop_group, $cD, $cDT);

            // Add wd
            $iStatus = addRecord($this->_dbConn, $mappingTable, $cols, $vals, $arrParams);

            // wd added
            if ($iStatus == 2) {
                $arrMessage = responseMessage(array($GLOBALS['DATA_ADDED']), 1);
            } else {
                $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_ADDED']));
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['WD_EXISTS']));
        }


        echo json_encode($arrMessage);
    }


    final public function getViewWDMappingData()
    {
        $arrResult = array(
            // Don't use dstatus = 0
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
            "viewHeader" => array(
                "app.wdMapping.mappingID",
                "app.wdMapping.district",
                "app.wdMapping.branch",
                "app.wdMapping.circle",
                "app.wdMapping.circle_name",
                "app.wdMapping.section",
                "app.wdMapping.section_name",
                "app.wdMapping.wd_code",
                "app.wdMapping.wd_firm_name",
                "app.wdMapping.wd_market",
                "app.wdMapping.wd_pop_group",
                "WD Status"
            ),
            "viewBody" => array(
                "id",
                "district",
                "branch",
                "circle",
                "circle_name",
                "section",
                "section_name",
                "wd_code",
                "wd_firm_name",
                "wd_market",
                "wd_pop_group",
                "wdStatus",
            ),
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    final public function viewWdMapping()
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $arrResult = array();

        $where = $this->getCondition();

        // $where $sOrderCond
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT e.rec_id, e.district, e.branch, e.circle, e.circle_name, e.section, e.section_name, e.wd_code, e.wd_firm_name, e.wd_market, e.wd_pop_group, e.wd_active_status FROM $projectTeamTable AS b, tblbranch as d" .
            ", $wdMappingTable as e Where b.branch_id = d.branch_id AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0 AND b.wd_code = e.wd_code $where";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $wdStatus = $arrData['wd_active_status'];
                if($wdStatus == 0){
                    $status = "Active";
                }else{
                    $status = "Inactive";
                }
                $arrResult[] = array(
                    "id" => $arrData['rec_id'],
                    "district" => $arrData['district'],
                    "branch" => $arrData['branch'],
                    "circle" => $arrData['circle'],
                    "circle_name" => $arrData['circle_name'],
                    "section" => $arrData['section'],
                    "section_name" => $arrData['section_name'],
                    "wd_code" => $arrData['wd_code'],
                    "wd_firm_name" => $arrData['wd_firm_name'],
                    "wd_market" => $arrData['wd_market'],
                    "wd_pop_group" => $arrData['wd_pop_group'],
                    "wdStatus"=> $status,
                );
            }
        }
        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function exportWdMapping()
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $wdMappingTable = $this->_tables["WD_MAPPING_TABLE"];
        $arrBody = array();

        $where = $this->getCondition();

        // $where $sOrderCond
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT e.rec_id, e.district, e.branch, e.circle, e.circle_name, e.section, e.section_name, e.wd_code, e.wd_firm_name, e.wd_market, e.wd_pop_group FROM $projectTeamTable AS b, tblbranch as d, $wdMappingTable as e" .
            " Where b.branch_id = d.branch_id AND b.dstatus = 0 AND d.dstatus = 0 AND e.dstatus = 0 AND b.wd_code = e.wd_code $where";
        // echo $sQuery;die;

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $arrBody[] = array(
                    $arrData['rec_id'],
                    $arrData['district'],
                    $arrData['branch'],
                    $arrData['circle'],
                    $arrData['circle_name'],
                    $arrData['section'],
                    $arrData['section_name'],
                    $arrData['wd_code'],
                    $arrData['wd_firm_name'],
                    $arrData['wd_market'],
                    $arrData['wd_pop_group'],
                );
            }
        }

        $header = array("Mapping ID", "District", "Branch", "Circle", "Circle Name", "Section", "Section Name", "WD Code", "WD Firm Name", "WD Market", "WD POP Group");

        $arrResult = formatDownloadData("Mapping Details_details", array($header), $arrBody);
        $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
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
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where order by a.district";
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
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where order by a.branch_name";
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

    final public function getBranch()
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
                "wdMarketList" => $this->getWdMarketList($districtCond),
                "wdPopGroupList" => $this->getWdPopGroupList($districtCond),
            );
        } else {
            $arrResult = array(
                "branchList" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCircle()
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if (!empty($branch)) {
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
                "wdMarketList" => $this->getWdMarketList($branchCond),
                "wdPopGroupList" => $this->getWdPopGroupList($branchCond),
            );
        } else {
            $arrResult = array(
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection()
    {
        $circle = $this->_data['circle'];
        $circleCond = "";
        if (!empty($circle)) {
            if (!is_array($circle)) {
                $circle = array($circle);
            }
            if (in_array('all', $circle)) {
                $circleCond = ""; // No condition for 'all'
            } else {
                $circle = "'" . implode("','", $circle) . "'";
                $circleCond = " AND b.circle IN ($circle)";
            }

            $arrResult = array(
                "sectionList" => $this->getSectionList($circleCond),
                "wdCodeList" => $this->getWdCodeList($circleCond),
                "wdMarketList" => $this->getWdMarketList($circleCond),
                "wdPopGroupList" => $this->getWdPopGroupList($circleCond),
            );
        } else {
            $arrResult = array(
                "sectionList" => "",
                "wdCodeList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


    final public function getWdCode()
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if (!empty($section)) {
            if (!is_array($section)) {
                $section = array($section);
            }
            if (in_array('all', $section)) {
                $sectionCond = ""; // No condition for 'all'
            } else {
                $section = "'" . implode("','", $section) . "'";
                $sectionCond = " AND b.section IN ($section)";
            }

            $arrResult = array(
                "wdCodeList" => $this->getWdCodeList($sectionCond),
                "wdMarketList" => $this->getWdMarketList($sectionCond),
                "wdPopGroupList" => $this->getWdPopGroupList($sectionCond),
            );
        } else {
            $arrResult = array(
                "wdCodeList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }
}
