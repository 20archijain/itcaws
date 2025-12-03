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
class AddFocusBrand
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

    final public function addDataForFocus()
    {
        $currentMonth = date("m");
        $currentYear  = date("Y");
        $prevMonth = date("m", strtotime("first day of previous month"));
        $prevYear  = date("Y", strtotime("first day of previous month"));
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT distinct branch_id FROM tblbranch_products_month_wise WHERE dstatus = 0 AND month = '$prevMonth' AND year = '$prevYear'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {

                $branch_id = $row['branch_id'];
                $sAction1 = null;
                $iRows1 = 0;
                $sQuery1 = "SELECT json_id, team_type, is_focusbrand, category_name, product_name, summary_column_name, net_rate,  sort_order FROM tblbranch_products_month_wise WHERE dstatus = 0" .
                    " AND branch_id = '$branch_id' AND month = '$prevMonth' AND year = '$prevYear'";
                // echo $sQuery;die;
                $this->_dbConn->ExecuteSelectQuery($sQuery1, $sAction1, $iRows1);

                if ($iRows1 > 0) {
                    while ($row1 = $this->_dbConn->GetData($sAction1)) {

                        $summary_column_name = $row1['summary_column_name'];
                        $arrList = array(
                            $row1['json_id'],
                            $row1['team_type'],
                            $row1['is_focusbrand'],
                            $row1['category_name'],
                            $row1['product_name'],
                            $row1['summary_column_name'],
                            $row1['net_rate'],
                            $row1['sort_order'],
                            $branch_id,
                            $currentMonth,
                            $currentYear
                        );

                        $recId = getRowColumn($this->_dbConn, "tblbranch_products_month_wise", "rec_id", "dstatus = 0 AND branch_id = $branch_id AND month = '$currentMonth' AND year = '$currentYear' AND summary_column_name = '$summary_column_name'");

                        if($recId == 0)
                        {
                             addRecord($this->_dbConn, "tblbranch_products_month_wise", "json_id, team_type, is_focusbrand, category_name, product_name, summary_column_name, net_rate, sort_order, branch_id, month, year", "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",  $arrList);
                        }
                    };
                }
            }
        }
    }
}

$updateDataCronjob = new AddFocusBrand($dbConn);
$updateDataCronjob->addDataForFocus();
