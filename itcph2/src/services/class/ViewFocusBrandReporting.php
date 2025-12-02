<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ViewFocusBrandReporting
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

    final public function getBranchProduct($branchId = "")
    {
        $arrData = array();
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT distinct product_name, summary_column_name tblbranch_pickupstock_products" .
            " Where dstatus = 0 AND branch_id = '$branchId' AND team_type = 5";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $arrData[] = array(
                    "label" => $row['product_name'],
                    "value" => $row['summary_column_name'],
                );
            }
        }

        return $arrData;
    }

    final public function getViewTeamData()
    {
        $arrResult = array(
            // Don't use dstatus = 0
            "yearList" => getYearList(),
            "monthList" => getMonthList(),
            "branchList" => getBranchList($this->_dbConn),
            "viewHeader" => array(
                "app.focusBrandReporting.id",
                "app.focusBrandReporting.branch",
                "app.focusBrandReporting.year",
                "app.focusBrandReporting.month",
                "app.focusBrandReporting.category_name",
                "app.focusBrandReporting.product_name",
                "app.focusBrandReporting.summary_column_name",
            ),
            "viewBody" => array(
                "id",
                "branch",
                "year",
                "month",
                "category_name",
                "product_name",
                "summary_column_name",
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewTeams()
    {
        // order by condition
        $sOrderCond = getOrderByCond("a.rdt", $this->_data["sort"]);

        // filter by search query
        $where = getFilterResult(
            $this->_data['searchbar'],
            array(
                "branch" => array("branch_id", -1),
                "month" => array("month", -1),
                // "password" => array("c.password", 1),
                "year" => array("year", -1),
            )
        );

        // echo $where;die;

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT rec_id, category_name, product_name, summary_column_name, branch_id, month, year FROM tblbranch_products_month_wise" .
            " Where dstatus = 0 $where";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $branchId = $arrData["branch_id"];
                $branchName = getRowColumn($this->_dbConn, "tblbranch", "branch_name", "dstatus = 0 AND branch_id = $branchId");
                $arrResult[] = array(
                    "id" => $arrData['rec_id'],
                    "category_name" => $arrData["category_name"],
                    "product_name" => $arrData["product_name"],
                    "summary_column_name" => $arrData["summary_column_name"],
                    "branch" => $branchName,
                    "month" => $arrData["month"],
                    "year" => $arrData["year"],
                );
            }
        }

        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function editTeam()
    {
        $teamId = getFormData($this->_data, "id");
        $projectId = getFormData($this->_data, "projectId");
        $recId = getFormData($this->_data, "recId");
        $teamName = getFormData($this->_data, "teamName");
        $phone = getFormData($this->_data, "phone");
        // $password = getFormData($this->_data, "password");
        // $json = getFormData($this->_data, "json");

        $isValidated = "";

        //inputs validated
        if ($isValidated) {
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];

            //check if team or username exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $projectTeamTable, "team_id", "team_id != ? AND project_id = ? AND ds_number = ?", array($teamId, $projectId, $phone));
            // Don't use dstatus = 0
            $iStatusCloud = isRecordExist($this->_dbConn, $cloudAuthPinTable, "rec_id", "rec_id != ? AND mobile = ?", array($recId, $phone), true);

            // Team not exist, edit
            if ($iStatus === 0 && $iStatusCloud === 0) {
                $cols = "team_name = ?, ds_number = ?, modif_id = ?";
                $arrParams = array($teamName, $phone, $this->_iUserId, $teamId);

                $iStatus = updateRecord($this->_dbConn, $projectTeamTable, $cols, "dstatus = 0 AND team_id = ?", $arrParams);

                $colsCloud = "team_name = ?, mobile = ?, modif_id = ?";
                $arrParamsCloud = array($teamName, $phone, $this->_iUserId, $recId);

                $iStatusCloud = updateRecord($this->_dbConn, $cloudAuthPinTable, $colsCloud, "dstatus = 0 AND rec_id = ?", $arrParamsCloud, true);

                // team modified
                if ($iStatus === 1 || $iStatusCloud === 1) {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_EDITED_SUCCESSFULL']), 1);
                } else {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']));
                }
            } else {
                if ($iStatus === 1) {
                    $arrMessage = responseMessage(array($GLOBALS['TEAM_EXISTS']));
                } else {
                    $arrMessage = responseMessage(array($GLOBALS['USERNAME_EXISTS']));
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
