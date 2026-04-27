<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class VanDsMailer
{
    private $_dbConn = null;
    private $_tables = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
    }

    final public function sendMissingAttendanceList()
    {
        $currentDate = currentDate();
        $arrHeader = array("S.No", "WD Code", "District", "Team Name");
        $fileName = "VanDS_MissingAttendance_" . $currentDate . ".xlsx";

        $branchTable = $this->_tables["BRANCH_TABLE"];
        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];

        $sBranchAction = null;
        $iBranchRows = 0;
        $sBranchQuery = "SELECT branch_id, branch_name, to_email, cc_email FROM $branchTable WHERE dstatus = 0";
        $this->_dbConn->ExecuteSelectQuery($sBranchQuery, $sBranchAction, $iBranchRows);

        if ($iBranchRows > 0) {
            while ($branchRow = $this->_dbConn->GetData($sBranchAction)) {
                $branchId = $branchRow["branch_id"];
                $branchName = $branchRow["branch_name"];
                $arrTo = isset($branchRow["to_email"]) && $branchRow["to_email"] ? explode(",", $branchRow["to_email"]) : array();
                $arrCc = isset($branchRow["cc_email"]) && $branchRow["cc_email"] ? explode(",", $branchRow["cc_email"]) : array();

                if (isNonEmptyArray($arrTo) || isNonEmptyArray($arrCc)) {
                    $sAction = null;
                    $iRows = 0;
                    $sQuery = "SELECT a.team_name, a.wd_code, a.district FROM $projectTeamTable AS a WHERE a.dstatus = 0 AND a.project_id = 1 AND a.branch_id = $branchId" .
                        " AND a.team_id NOT IN (SELECT DISTINCT team_id FROM $attendanceTable WHERE dstatus = 0 AND project_id = 1 AND call_type = '0' AND capture_date = '$currentDate')";
                    $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

                    $arrData = array();
                    if ($iRows > 0) {
                        $i = 1;
                        while ($row = $this->_dbConn->GetData($sAction)) {
                            $arrData[] = array($i, $row["wd_code"], $row["district"], $row["team_name"]);
                            $i++;
                        }
                    }

                    if (isNonEmptyArray($arrData)) {
                        $subject = "$branchName - Van DS Missing Attendance report for " . currentDate($currentDate, "d-m-Y");
                        sendMail(array("pns29397@gmail.com"), $subject, "I am testing right now", array());
                    }
                }
            }
        }
    }
}

$vanDsMailer = new VanDsMailer($dbConn);
$vanDsMailer->sendMissingAttendanceList();
