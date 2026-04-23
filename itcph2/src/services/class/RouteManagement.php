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
            "routeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['ROUTE_DETAILS_TABLE'], "route_name", "rec_id"),
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
                "routeList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getRoute($team = "team_id")
    {
        $team = $this->_data['team'];
        $teamCond = "";
        if ($team) {
            if (!is_array($team)) {
                $team = array($team);
            }
            $team = "'" . implode("','", $team) . "'";
            $teamCond .= "team_id IN ($team) AND dstatus = 0";

            $arrResult = array(
                "routeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['ROUTE_DETAILS_TABLE'], "route_name", "route_name", "$teamCond"),
            );
        } else {
            $arrResult = array(
                "routeList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getViewRouteData()
    {
        $arrResult = array(
            "branchList" => getBranchList($this->_dbConn),
            "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "dstatus = 0"),
            "routeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['ROUTE_DETAILS_TABLE'], "route_name", "route_name", "dstatus = 0"),
            "sortOptions" => array(
                array("label" => "Team Name", "value" => "b.team_name"),
                array("label" => "Team ID", "value" => "a.team_id"),
                array("label" => "Date Created - ASC", "value" => "a.rlm"),
            ),
            "statusList" => array(
                array("label" => "Active", "value" => '0'),
                array("label" => "Deleted", "value" => '1'),
            ),
            "viewHeader" => array(
                "Rec_ID",
                "Team ID",
                "Wd Code",
                "Team Name",
                "District",
                "Route Name",
                "Outlet Name",
                "Outlet Mobile",
                "KYC Status",
                "Status"
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
                "kycStatus",
                "deleteStatus"
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

        //Phone number
        $phoneNumber = isset($this->_data['searchbar']['phoneNumber'])
            ? $this->_data['searchbar']['phoneNumber']
            : '';
        if (!empty(trim($phoneNumber))) {
            $phoneArray = preg_split('/[\s,]+/', trim($phoneNumber));
            $phoneArray = array_filter($phoneArray);
            $phoneArray = array_map('addslashes', $phoneArray);
            $PhoneSql = "'" . implode("','", $phoneArray) . "'";
            $where .= "AND a.outlet_mobile IN ($PhoneSql) ";
        }

        $branch = isset($this->_data['searchbar']['branch']) ? $this->_data['searchbar']['branch'] : [];
        if (!empty($branch) && is_array($branch)) {
            $branchId = implode(',', $branch);
            $where .= "AND b.branch_id IN ($branchId)";
        }

        // Convert to `'val1','val2','val3'`
        //Shop Ids
        $recIds = isset($this->_data['searchbar']['recIds']) ? $this->_data['searchbar']['recIds'] : '';
        if (!empty(trim($recIds))) {
            $recIdsArray = preg_split('/[\s,]+/', trim($recIds));
            $recIdsArray = array_filter($recIdsArray);
            $recIdsArray = array_map('addslashes', $recIdsArray);
            $recIdsSql = "'" . implode("','", $recIdsArray) . "'";
            $where .= " AND a.rec_id IN ($recIdsSql) ";
        }

        $team = isset($this->_data['searchbar']['team']) ? $this->_data['searchbar']['team'] : [];
        if (!empty($team) && is_array($team)) {
            $teamId = implode(',', $team);
            $where .= "AND a.team_id IN ($teamId)";
        }

        $routeName = isset($this->_data['searchbar']['routeName']) ? $this->_data['searchbar']['routeName'] : [];
        if (!empty($routeName) && is_array($routeName)) {
            $routeName = array_map('addslashes', $routeName);
            $routeNameFind = "'" . implode("','", $routeName) . "'";
            $where .= " AND a.route_name IN ($routeNameFind)";
        }

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.team_id, a.rec_id, b.team_name, a.wd_code, a.district, a.route_name, a.outlet_name, a.dstatus,
              a.outlet_mobile, a.kyc_done FROM $RouteTable AS a, $projectTeamTable AS b WHERE a.team_id = b.team_id $where $sOrderCond";
        // print_r($sQuery);
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $recId = $arrData["rec_id"];
                $teamId = $arrData["team_id"];
                $kycDone = (isset($arrData['kyc_done']) && $arrData['kyc_done'] == '1') ? 'Yes' : 'No';
                $arrResult[] = array(
                    "id" => $recId,
                    "teamId" => $teamId,
                    "wdCode" => $arrData["wd_code"],
                    "teamName" => $arrData["team_name"],
                    "district" => $arrData["district"],
                    "routeName" => $arrData["route_name"],
                    "outletName" => $arrData["outlet_name"],
                    "outletMobile" => $arrData["outlet_mobile"],
                    "kycStatus" => $kycDone,
                    "dstatus" => (int)$arrData['dstatus'],
                    "deleteStatus" => $GLOBALS["ARR_DELETE_STATUS"][$arrData['dstatus']],
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
        $istatus = [];

        // Normalize id(s)
        $ids = $requestData['id'];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_filter($ids, function ($v) {
            return !empty($v);
        });
        $rec_id = implode(',', $ids);

        if ($rec_id) {
            $where .= "rec_id IN ($rec_id) AND dstatus = 0";
        }

        // Fetch outlet mobile numbers linked to these rec_ids
        $mobNo = getRowsColumn($this->_dbConn, "tblroute_details", "outlet_mobile", $where);
        if (isset($mobNo) && isNonEmptyArray($mobNo)) {
            $mobNo = array_filter($mobNo, function ($v) {
                return !empty($v);
            });

            if (!empty($mobNo)) {
                // Assuming outlet_mobile and rec_who are numeric
                $mobImplode = implode(',', array_map('intval', $mobNo));
                $whereMob = "rec_who IN ($mobImplode)";

                $statusCloud = updateRecord(
                    $this->_dbConn,
                    "tblcloudring_live",
                    "dstatus = 1, modif_id = $assign_id",
                    $whereMob
                );
                $istatus[] = $statusCloud;
            }
        }

        $statusRoute = updateRecord(
            $this->_dbConn,
            "tblroute_details",
            "dstatus = 1 ,modif_id = $assign_id",
            $where
        );
        $istatus[] = $statusRoute;

        if (in_array(1, $istatus, true)) {
            $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']], 2);
        }

        echo json_encode($arrMessage);
    }

    final public function restoredata($data, $iUserId)
    {
        $requestData = $data;
        $assign_id = $iUserId;
        $where = "";
        $whereMob = "";
        $istatus = [];

        $ids = $requestData['id'];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_filter($ids, function ($v) {
            return !empty($v);
        });
        $rec_id = implode(',', $ids);

        if ($rec_id) {
            $where .= "rec_id IN ($rec_id) AND dstatus = 1";
        }

        $mobNo = getRowsColumn($this->_dbConn, "tblroute_details", "outlet_mobile", $where);
        if (isset($mobNo) && isNonEmptyArray($mobNo)) {
            $mobNo = array_filter($mobNo, function ($v) {
                return !empty($v);
            });

            if (!empty($mobNo)) {
                $mobImplode = implode(',', array_map('intval', $mobNo));
                $whereMob = "rec_who IN ($mobImplode) AND dstatus = 1";

                $statusCloud = updateRecord(
                    $this->_dbConn,
                    "tblcloudring_live",
                    "dstatus = 0, modif_id = $assign_id",
                    $whereMob
                );
                $istatus[] = $statusCloud;
            }
        }

        $statusRoute = updateRecord(
            $this->_dbConn,
            "tblroute_details",
            "dstatus = 0, modif_id = $assign_id",
            $where
        );
        $istatus[] = $statusRoute;

        if (in_array(1, $istatus, true)) {
            $arrMessage = responseMessage([$GLOBALS['DATA_RESTORED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_RESTORED']], 2);
        }

        echo json_encode($arrMessage);
    }

    final public function editRoutedata($data, $iUserId)
    {
        $teamId = isset($data['teamName']) ? $data['teamName'] : '';
        $oldRouteName = isset($data['routeName']) ? trim($data['routeName']) : '';
        $newRouteName = isset($data['newRouteName']) ? trim($data['newRouteName']) : '';
        $errors = [];

        // Validate inputs
        if (empty($teamId)) {
            $errors[] = "Team is required";
        }
        if (empty($oldRouteName)) {
            $errors[] = "Route name is required";
        }
        if (empty($newRouteName)) {
            $errors[] = "New route name is required";
        }
        if (strlen($newRouteName) < 3) {
            $errors[] = "New route name must be at least 3 characters";
        }

        if (!empty($errors)) {
            $arrMessage = responseMessage($errors, 2);
            echo json_encode($arrMessage);
            return;
        }

        // Check if the old route exists and is active (dstatus = 0)
        $where = "team_id = '$teamId' AND route_name = '" . addslashes($oldRouteName) . "' AND dstatus = 0";
        // print_r($where);die;
        $existingRoute = getRowsColumn(
            $this->_dbConn,
            $this->_tables['ROUTE_DETAILS_TABLE'],
            "rec_id",
            $where
        );

        if (empty($existingRoute)) {
            $arrMessage = responseMessage(["Active route not found for the selected team. Only active routes (not deleted) can be edited."], 2);
            echo json_encode($arrMessage);
            return;
        }

        // Check if new route name already exists for this team (only check active routes)
        $checkDuplicateWhere = "team_id = '$teamId' AND route_name = '" . addslashes($newRouteName) . "' AND dstatus = 0";
        $duplicateRoute = getRowsColumn(
            $this->_dbConn,
            $this->_tables['ROUTE_DETAILS_TABLE'],
            "rec_id",
            $checkDuplicateWhere
        );

        if (!empty($duplicateRoute)) {
            $arrMessage = responseMessage(["New route name already exists for this team"], 2);
            echo json_encode($arrMessage);
            return;
        }

        // Update only routes with dstatus = 0
        $updateData = "route_name = '" . addslashes($newRouteName) . "', modif_id = $iUserId, rlm = NOW()";
        $updateWhere = "team_id = '$teamId' AND route_name = '" . addslashes($oldRouteName) . "' AND dstatus = 0";

        $status = updateRecord(
            $this->_dbConn,
            $this->_tables['ROUTE_DETAILS_TABLE'],
            $updateData,
            $updateWhere
        );

        if ($status == 1) {
            $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']], 2);
        }

        echo json_encode($arrMessage);
    }
}
