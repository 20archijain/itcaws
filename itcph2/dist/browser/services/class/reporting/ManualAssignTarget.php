<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class ManualAssignTarget
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

    final public function getBranchList($cond = "")
    {
        $arrData = array();
        $where = "";

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.branch_name, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' AND b.is_type = 5 $where order by a.branch_name";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['branch_name'],
                    "value" => $row['branch_id'],
                );
            }
        }

        return $arrData;
    }

    // MASTER DATA
    final public function getViewSKUData()
    {
        $month = date('m');
        $year = date('Y');
        $arrTeams = getRowsColumn($this->_dbConn, "tblassign_target", "team_id", " dstatus = 0 AND month = '$month' AND year = '$year'");
        $cond = "";
        if (isset($arrTeams)) {
            $totalTeamsQyery = implode(',', $arrTeams);
            $cond .= " AND b.team_id NOT IN ($totalTeamsQyery)";
        }

        $branchList = $this->getBranchList($cond);

        $arrResult = array(
            "branchList" => $branchList,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


    final public function getProductList()
    {
        $month = date('m');
        $year = date('Y');
        $branch = $this->_data['branch'];
        $productList = getRowsColumns($this->_dbConn, "tblbranch_products_month_wise", "product_name, summary_column_name", " branch_id = '$branch' AND month = '$month' AND year = '$year' order by is_focusbrand", array(), true);
        $arrResult = array(
            "productList" => $productList,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }



    final public function submitData()
    {
        $branch = $this->_data['branch'];
        $quantity = $this->_data['quantity'];
        $month = date('m');
        $year = date('Y');

        $arrTotalTeams = getRowsColumn($this->_dbConn, "tblproject_team", "team_id", " branch_id = '$branch' AND dstatus = 0 AND is_type = 5");
        $totalTeamsQyery = implode(',', $arrTotalTeams);

        $arrTotalAlreadySubmiitedTeams = getRowsColumn($this->_dbConn, "tblassign_target", "team_id", " team_id in ($totalTeamsQyery) AND dstatus = 0 AND month = '$month' AND year = '$year' ");
        $arrAllTeamsAfterCheck = array_diff($arrTotalTeams, $arrTotalAlreadySubmiitedTeams);
        $arrAllTeamsAfterCheck = array_values($arrAllTeamsAfterCheck);


        if (count($arrTotalTeams) != count($arrTotalAlreadySubmiitedTeams) && isset($quantity)) {
            $status = 0;
            if (isset($arrAllTeamsAfterCheck)) {
                foreach ($arrAllTeamsAfterCheck as $teamId) {
                    $col = "";
                    $val = "";
                    $arrParams = array();
                    foreach ($quantity as $product => $value) {
                        $col .= $product . ",";
                        $val .= "?,";
                        $arrParams[] = $value;
                    }

                    $col .= "team_id, month, year";
                    $val .= "?, ?, ?";
                    $arrParamsNext = array($teamId, $month, $year);

                    $arrParams = array_merge($arrParams, $arrParamsNext);

                    $status = addRecord($this->_dbConn, "tblassign_target", $col, $val, $arrParams);
                }
            }

            if ($status == 2) {
                $arrMessage = responseMessage([$GLOBALS['TARGET_ASSIGNED']], 1);
            } else {
                $arrMessage = responseMessage(array($GLOBALS['TARGET_NOT_ASSIGNED']), 0);
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['TARGET_ALREADY_ASSIGNED']), 0);
        }
        echo json_encode($arrMessage);
    }
}
