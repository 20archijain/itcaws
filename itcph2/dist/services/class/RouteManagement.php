<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class RouteManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];
    private $_arrSeperator = array(
        1 => " ",
        2 => "-",
        3 => "_",
    );

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }


    final public function getAddTeamData()
    {
        $arrResult = array(
            "branchList" => getBranchList($this->_dbConn, true, "dstatus = 0"),
            "teamList" => getTeamsOptions($this->_dbConn, true, "dstatus = 0"),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeam($branch = "branch_id")
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if ($branch) {
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            $branch = "'" . implode("','", $branch) . "'";
            $branchCond .= "branch_id IN ($branch)";

            $arrResult = array(
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "$branchCond"),

            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


    final public function getViewRouteData()
    {
        $arrResult = array(
            "branchList" => getBranchList($this->_dbConn),
            "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id"),
            "sortOptions" => array(
                array("label" => "Team Name", "value" => "b.team_name"),
                array("label" => "Team ID", "value" => "a.team_id"),
                array("label" => "Date Created - ASC", "value" => "a.rlm"),
            ),
            "viewHeader" => array(
                "Rec_ID",
                "Team ID",
                "Wd Code",
                "Team Name",
                "District",
                "Route Name",
                "Outlet Name",
                "Outlet Mobile"
            ),
            "viewBody" => array(
                "id",
                "teamId",
                "wdCode",
                "teamName",
                "district",
                "routeName",
                "outletName",
                "outletMobile",
            ),
        );


        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewRoute()
    {
        $RouteTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $teamId = '';
        $where = '';

        // order by condition
        $sOrderCond = getOrderByCond("a.rlm", $this->_data["sort"]);

        // filter by search query
        $branch = isset($this->_data['searchbar']['branch']) ? $this->_data['searchbar']['branch'] : [];
        if (!empty($branch) && is_array($branch)) {
            $branchId = implode(',', $branch);
            $where .= "AND b.branch_id IN ($branchId)";
        }

        $recIds = isset($this->_data['searchbar']['recIds']) ? $this->_data['searchbar']['recIds'] : [];
        if (!empty($recIds)) {
            $where .= "AND a.rec_id IN ($recIds)";
        }

        $team = isset($this->_data['searchbar']['team']) ? $this->_data['searchbar']['team'] : [];
        // print_r($team);die;

        if (!empty($team) && is_array($team)) {
            $teamId = implode(',', $team);
            $where .= "AND a.team_id IN ($teamId)";
        }

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.team_id, a.rec_id, b.team_name, a.wd_code, a.district, a.route_name, a.outlet_name,
              a.outlet_mobile FROM $RouteTable AS a LEFT JOIN $projectTeamTable AS b ON a.team_id = b.team_id AND b.dstatus = 0 WHERE a.dstatus = 0 $where $sOrderCond";

        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        // print_r($sQuery);die;

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $recId = $arrData["rec_id"];
                $teamId = $arrData["team_id"];

                $arrResult[] = array(
                    "id" => $recId,
                    "teamId" => $teamId,
                    "wdCode" => $arrData["wd_code"],
                    "teamName" => $arrData["team_name"],
                    "district" => $arrData["district"],
                    "routeName" => $arrData["route_name"],
                    "outletName" => $arrData["outlet_name"],
                    "outletMobile" => $arrData["outlet_mobile"],
                );
            }
        }

        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function deleteData($data, $iUserId)
    {
        $requestData = $data;
        $assign_id = $iUserId;
        $where = "";
        $whereMob = "";
        $istatus = array();
        $rec_id = implode(',', $requestData['id']);
        if ($rec_id) {
            $where .= "rec_id IN ($rec_id) AND dstatus = 0";
        };
        $mobNo = getRowsColumn($this->_dbConn, "tblroute_details", "outlet_mobile", $where);
        if (isset($mobNo) && isNonEmptyArray($mobNo)) {
            // remove empty values
            $mobNo = array_filter($mobNo, function ($v) {
                return !empty($v);
            });

            if (!empty($mobNo)) {
                // wrap values in quotes if rec_who is varchar
                $mobImplode = implode(',', array_map('intval', $mobNo)); // for numeric
                // $mobImplode = "'" . implode("','", $mobNo) . "'";    // for string

                $whereMob = "rec_who IN ($mobImplode)";
                $istatus[] = updateRecord(
                    $this->_dbConn,
                    "tblcloudring_live",
                    "dstatus = 1, modif_id = $assign_id",
                    $whereMob
                );
            }
        }
        $istatus[] = updateRecord($this->_dbConn, "tblroute_details", "dstatus = 1 ,modif_id = $assign_id ", "$where");
        if (in_array(0, $istatus)) {
            $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']), 1);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['DATA_EDITED_SUCCESSFULL']), 1);
        }
        echo json_encode($arrMessage);
    }
}
