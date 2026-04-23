<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class UpdatFocusBrandIfHOApproved
{
    private $_dbConn = null;
    private $_tables = [];
    private $_commonSettings = [];
    private $_jsonWiseAndbranchWiseProductsColumns = [];
    private $_jsonWiseAndbranchWiseStockpickupProductsColumns = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
    }

    final public function UpdatFocusBrandIfHOApproved()
    {
        $nextMonth = date("m", strtotime("+1 month"));
        $nextYear  = date("Y", strtotime("+1 month"));
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT distinct branch_id FROM tblbranch_pickupstock_products_allocation WHERE dstatus = 0 AND month = '$nextMonth' AND year = '$nextYear' AND team_type = 5 AND dspm_focus = 1 AND filled_by_ho = 1";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $branch_id = $row['branch_id'];
                $sAction1 = null;
                $iRows1 = 0;
                $arrListAllocation = array();
                $sQuery1 = "SELECT rec_id, category_name, product_name, summary_column_name FROM tblbranch_pickupstock_products_allocation WHERE dstatus = 0" .
                    "  AND branch_id = '$branch_id' AND month = '$nextMonth' AND year = '$nextYear' AND team_type = 5 AND dspm_focus = 1 AND filled_by_ho = 1";
                // echo $sQuery;die;
                $this->_dbConn->ExecuteSelectQuery($sQuery1, $sAction1, $iRows1);

                if ($iRows1 > 0) {
                    while ($row1 = $this->_dbConn->GetData($sAction1)) {
                        $arrListAllocation[] = array(
                            $row1['rec_id'],
                            $row1['category_name'],
                            $row1['product_name'],
                            $row1['summary_column_name'],
                        );
                    };
                }
                $sAction2 = null;
                $iRows2 = 0;
                $arrListMonth = array();
                $sQuery2 = "SELECT rec_id, category_name, product_name, summary_column_name FROM tblbranch_products_month_wise WHERE dstatus = 0" .
                    "  AND branch_id = '$branch_id' AND month = '$nextMonth' AND year = '$nextYear' AND team_type = 5 AND is_focusbrand = 1";
                // echo $sQuery;die;
                $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);

                if ($iRows2 > 0) {
                    while ($row2 = $this->_dbConn->GetData($sAction2)) {
                        $arrListMonth[] = array(
                            $row2['rec_id'],
                            $row2['category_name'],
                            $row2['product_name'],
                            $row2['summary_column_name'],
                        );
                    };
                }
                $arrUpdateList = array();
                if (isset($arrListAllocation) && count($arrListAllocation) == 2 && isset($arrListMonth) && count($arrListMonth) == 2) {
                    if ($arrListAllocation[0][3] != $arrListMonth[0][3]) {
                        $arrUpdateList[] = array(
                            $arrListMonth[0][0],
                            $arrListAllocation[0][1],
                            $arrListAllocation[0][2],
                            $arrListAllocation[0][3],
                        );
                    }
                    if ($arrListAllocation[1][3] != $arrListMonth[1][3]) {
                        $arrUpdateList[] = array(
                            $arrListMonth[1][0],
                            $arrListAllocation[1][1],
                            $arrListAllocation[1][2],
                            $arrListAllocation[1][3],
                        );
                    }

                    if (isset($arrUpdateList) && count($arrUpdateList) == 2) {
                        foreach ($arrUpdateList as $data) {
                            updateRecord($this->_dbConn, "tblbranch_products_month_wise", "category_name = '$data[1]', product_name = '$data[2]', summary_column_name = '$data[3]'", "rec_id = '$data[0]'");
                        }
                    }
                }
            }
        }
    }
}

$updateDataCronjob = new UpdatFocusBrandIfHOApproved($dbConn);
$updateDataCronjob->UpdatFocusBrandIfHOApproved();
