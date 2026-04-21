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

    final public function getBranchProduct()
    {
        $arrData = array();
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT distinct product_name, summary_column_name from tblbranch_pickupstock_products" .
            " Where dstatus = 0 AND team_type = 5";
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
            "productList" => $this->getBranchProduct(),
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
        $id = getFormData($this->_data, "id");
        $summary_column_name = getFormData($this->_data, "summary_column_name");
        $monthWiseTable = "tblbranch_products_month_wise";
        $productTable = "tblbranch_pickupstock_products";

        $productAndCatogoryName = getRowColumns($this->_dbConn, $productTable, "category_name, product_name", "summary_column_name = '$summary_column_name'");

        if (isset($productAndCatogoryName[0]) && $productAndCatogoryName[0] && isset($productAndCatogoryName[1]) && $productAndCatogoryName[1]) {
            $cols = "category_name = ?, product_name = ?, summary_column_name = ?";
            $arrParams = array($productAndCatogoryName[0], $productAndCatogoryName[1], $summary_column_name, $id);

            $iStatus = updateRecord($this->_dbConn, $monthWiseTable, $cols, "dstatus = 0 AND rec_id = ?", $arrParams);

            // team modified
            if ($iStatus === 1) {
                $arrMessage = responseMessage(array($GLOBALS['DATA_EDITED_SUCCESSFULL']), 1);
            } else {
                $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']));
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']));
        }

        echo json_encode($arrMessage);
    }
}
