<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class ProductivityReport
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_projectId = 1;
    private $_arrAccessInfo = [];


    public function __construct($dbConn, $data, $arrAccessInfo)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
    }

    final public function getData()
    {
        $arrResult["yearList"] = getYearList();
        $arrResult["monthList"] = getMonthList();

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    public function getDownloadData()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        $month = getFormData($this->_data, "month");
        $month = $month ? $month : date("m");
        $year = getFormData($this->_data, "year");
        $year = $year ? $year : date("y");

        $where = "AND capture_date LIKE '$year-$month-%'";

        // $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $summaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];

        $arrExcelData = [];
        $arrExcelData[] = ["District", "Branch", "No of DS registered", " No of DS Using the App", "% of P1 DS", "Avg Route per Ds", "% of DS having >6 routes", "% of DS marking attendance (>=15 days)", "% of DS with qualified attendance (>=15 days)"];


        $sbranchQuery = "SELECT DISTINCT branch_name, branch_id, district FROM $branchTable WHERE dstatus = 0 ORDER BY branch_name";
        $sbranchAction = null;
        $ibranchRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sbranchQuery, $sbranchAction, $ibranchRows);

        if ($ibranchRows > 0) {
            while ($row = $this->_dbConn->GetData($sbranchAction)) {
                $district = $row['district'];
                $branchName = $row['branch_name'];
                $branchId = $row['branch_id'];
                $noOfDsRegistered = getRowColumn($this->_dbConn, "$projectTeamTable", "Count(Distinct team_id)", "dstatus = 0 AND branch_id = $branchId");
                $noOfDsActive = getRowColumn($this->_dbConn, "$summaryTable", "Count(Distinct team_id)", "dstatus = 0 AND team_id IN (SELECT team_id FROM $projectTeamTable WHERE branch_id = $branchId) $where");
                $totalRoutes = 0;
                $totalDsWithRoutes = 0;
                $routesPerDs = [];
                $outletsPerDs = [];
                $teamIds = getRowsColumn($this->_dbConn, "$projectTeamTable", "team_id", "dstatus = 0 AND branch_id = $branchId", [], true);
                foreach ($teamIds as $teamId) {
                    $routeCount = getRowColumn($this->_dbConn, "$routeTable", "COUNT(route_name)", "dstatus = 0 AND team_id = $teamId");
                    $outletCount = getRowColumn($this->_dbConn, "$routeTable", "COUNT(shop_uniq_code)", "dstatus = 0 AND team_id = $teamId");
                    if ($routeCount > 0) {
                        $totalRoutes += $routeCount;
                        $totalDsWithRoutes++;
                        $routesPerDs[$teamId] = $routeCount;
                    }
                    if ($outletCount > 0) {
                        $outletsPerDs[$teamId] = $outletCount;
                    }
                }
                $avgRoutePerDs = $totalDsWithRoutes > 0 ? $totalRoutes / $totalDsWithRoutes : 0;

                // Calculate % of DS having >6 routes
                $dsMoreThan6Routes = 0;
                foreach ($routesPerDs as $routeCount) {
                    if ($routeCount > 6) {
                        $dsMoreThan6Routes++;
                    }
                }
                $percentDsMoreThan6Routes = $noOfDsRegistered > 0 ? ($dsMoreThan6Routes / $noOfDsRegistered) * 100 : 0;

                // Calculate % of DS having >60 outlets
                $dsMoreThan6Outlets = 0;
                foreach ($outletsPerDs as $outletCount) {
                    if ($outletCount > 6) {
                        $dsMoreThan6Outlets++;
                    }
                }
                $percentDsMoreThan6Outlets = $noOfDsRegistered > 0 ? ($dsMoreThan6Outlets / $noOfDsRegistered) * 100 : 0;


                // Number of DS with attendance >= 15 days
                $noOfDsWithAttendanceGreaterthen15days = getRowColumn(
                    $this->_dbConn,
                    "$attendanceTable",
                    "COUNT(DISTINCT team_id)",
                    "dstatus = 0 AND team_id IN (SELECT team_id FROM $projectTeamTable WHERE branch_id = $branchId) AND capture_date LIKE '$year-$month-%' AND (SELECT COUNT(*) FROM $attendanceTable WHERE team_id = team_id AND capture_date LIKE '$year-$month-%') >= 15"
                );

                $percentDsWithAttendanceGreaterthen15days = 0;
                if ($noOfDsRegistered > 0) {
                    $percentDsWithAttendanceGreaterthen15days = ($noOfDsWithAttendanceGreaterthen15days / $noOfDsRegistered) * 100;
                }

                $ttlOutletMapped = getRowColumn($this->_dbConn, "$routeTable", "Count(Distinct shop_uniq_code)", "dstatus = 0 AND team_id IN (SELECT team_id FROM $projectTeamTable WHERE branch_id = $branchId)");


                $arrExcelData[] = [$district, $branchName, $noOfDsRegistered, $noOfDsActive, "", $avgRoutePerDs, $percentDsMoreThan6Routes, $percentDsWithAttendanceGreaterthen15days, "", $ttlOutletMapped];
            }
        }

        $fileName = "Productivity_Report_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrExcelData);

        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = [
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        ];
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        $arrMessage = responseMessage([$GLOBALS['FILE_DOWNLOADING']], 1, $fileDetails);

        echo json_encode($arrMessage);
    }
}
