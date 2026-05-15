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
class UpdateDataCronjob
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

    final public function updateDistance()
    {
        $currentDate = currentDate();
        $sDateCond = "AND activity_date = '$currentDate'";

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT summary_id, start_datetime, end_datetime FROM tblvands_summary WHERE dstatus = 0" .
            " $sDateCond LIMIT 5000";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $summary_id = $row["summary_id"];
                $start_datetime = $row["start_datetime"];
                $end_datetime = $row["end_datetime"];

                if (isset($start_datetime) && isset($end_datetime)) {
                    $total_time_in_min = getTimeDifferenceInString($start_datetime, $end_datetime, false, false, true);

                    updateRecord(
                        $this->_dbConn,
                        "tblvands_summary",
                        "total_time = ?",
                        "summary_id = $summary_id",
                        [$total_time_in_min]
                    );
                }
            }
        }
    }
}

$updateDataCronjob = new UpdateDataCronjob($dbConn);
$updateDataCronjob->updateDistance();
