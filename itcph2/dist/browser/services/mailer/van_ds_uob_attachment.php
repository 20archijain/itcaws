<?php

ini_set('memory_limit', '-1');  // Unlimited memory
ini_set('max_execution_time', '1200');  // 20 minutes

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'includes/reporting_functions.php';
include_once $CLASSES_PATH . '/CommonFunctions.php';
include_once $CLASSES_PATH . '/Response.php';
include_once $CLASSES_PATH . '/DBConnection.php';
require_once $CLASSES_PATH . '/TableUtil.php';

// phpcs:ignore
class VanDsMailer
{
    private $_dbConn = null;
    private $commonFunctions;
    private $tableUtil;
    private $_tables = [];

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->_dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->_tables = $GLOBALS['TABLES'];
    }

    public function sendUOBReport()
    {
        $dateCond = "";
        // Check if today is the first day of the month
        if (date('j') == 1) {
            // It's the first day of the month, fetch data for the whole last month
            $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
            $previousMonthEnd = date('Y-m-t', strtotime('last day of last month')); // End of the previous month
            $dateCond .= "AND capture_date BETWEEN '$previousMonthStart' AND '$previousMonthEnd'";
            $fileName = "VanDS_UOB_LastMonth_" . $previousMonthEnd . ".xlsx";
            $subject = "Van DS - Consolidated UOB report for North (From $previousMonthStart To $previousMonthEnd)";
        } else {
            // It's not the first day of the month, fetch data for the current month till yesterday
            $currentMonthStart = date('Y-m-01'); // Start of the month
            $currentMonthEnd = date('Y-m-d', strtotime('-1 day')); // One day before today
            $dateCond .= "AND capture_date BETWEEN '$currentMonthStart' AND '$currentMonthEnd'";
            $fileName = "VanDS_UOB_MTD_" . $currentMonthEnd . ".xlsx";
            $subject = "Van DS - Consolidated UOB report for North (From $currentMonthStart To $currentMonthEnd)";
        }

        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $respTable = getRespTable(1, 1);

        $arrExcelData = ["Branch", "Region", "WD Code", "DS Type", "DS Id", "DS Name", "SKU", "Total Outlets Mapped", 'Overall UOB', "Brand UOB", "Brand UOB%"];
        $arrHeaders = $arrExcelData;

        // Query to fetch all branches
        $sBranchAction = null;
        $iBranchRows = 0;
        $sBranchQuery = "SELECT branch_id, branch_name FROM $branchTable WHERE dstatus = 0";
        $this->_dbConn->ExecuteSelectQuery($sBranchQuery, $sBranchAction, $iBranchRows);

        if ($iBranchRows > 0) {
            while ($branchRow = $this->_dbConn->GetData($sBranchAction)) {
                $branchName = $branchRow["branch_name"];
                $branchId = $branchRow["branch_id"];

                // Get product columns for the current branch
                $sProductQuery = "SELECT DISTINCT product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 AND product_type = 0 AND branch_id = $branchId ORDER BY product_name";
                $sProductAction = null;
                $iProductRows = 0;
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    $summaryColName = [];
                    $productNames = [];
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $summaryColName[] = $rowProduct["summary_column_name"];
                        $productNames[] = $rowProduct["product_name"];
                    }
                    $sProductSaleColumns = implode(",", $summaryColName);

                    $isType = [0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "MDO"];
                    $rsAction = null;
                    $iRows = 0;
                    $sQuery = "SELECT a.capture_datetime, a.ques_0, b.team_id, b.team_name, b.is_type, b.wd_code, c.branch_name, c.main_branch, $sProductSaleColumns
                              FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c
                              WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id
                              AND ques_0 IN ('ROC Delivery','Other Outlet') $dateCond
                              AND b.branch_id = $branchId GROUP BY a.team_id ORDER BY capture_datetime DESC";
                    $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

                    if ($iRows > 0) {
                        $shopCount = [];
                        $totalShopCount = [];
                        $OverallShopCount = [];
                        while ($row = $this->_dbConn->GetData($rsAction)) {
                            $mainBranchName = $row['main_branch'];
                            $branchName = $row['branch_name'];
                            $teamId = $row['team_id'];
                            $teamName = $row['team_name'];
                            $teamType = $isType[$row['is_type']];
                            $wdCode = $row['wd_code'];
                            foreach ($summaryColName as $index => $colName) {
                                $shopRoc = $this->tableUtil->getRowColumn("$respTable", "COUNT(DISTINCT ques_2) AS total", "ques_0 = 'ROC Delivery' AND dstatus = 0 AND $colName > 0 AND team_id = $teamId $dateCond", [], true);
                                $shopOther = $this->tableUtil->getRowColumn("$respTable AS a", "COUNT(DISTINCT a.ques_3) AS total", "a.ques_0 = 'Other Outlet' AND a.dstatus = 0 AND a.$colName > 0 AND a.team_id = $teamId $dateCond", [], true);
                                $allShops = ($shopRoc ? $shopRoc : 0) + ($shopOther ? $shopOther : 0);
                                $shopCount[$mainBranchName][$branchName][$teamId][$teamName][$wdCode][$teamType][$colName] = $allShops;
                            }

                            $totalShopCount[$teamId] = $this->tableUtil->getRowColumn($routeTable, "COUNT(outlet_name) AS total", "dstatus = 0 AND team_id = $teamId", [], true);

                            $totalSaleColumns = [];
                            for ($i = 1; $i <= 30; $i++) {
                                $totalSaleColumns[] = "`total_sale_product$i`";
                            }
                            $totalSaleSum = implode(" + ", $totalSaleColumns);

                            $overallshopRoc = $this->tableUtil->getRowColumn(
                                "$respTable AS a",
                                "COUNT(DISTINCT a.ques_2) AS total",
                                "a.ques_0 = 'ROC Delivery' AND a.dstatus = 0 AND a.team_id = $teamId $dateCond AND ($totalSaleSum) > 0",
                                [],
                                true
                            );

                            $overallshopOther = $this->tableUtil->getRowColumn(
                                "$respTable AS a",
                                "COUNT(DISTINCT a.ques_3) AS total",
                                "a.ques_0 = 'Other Outlet' AND a.dstatus = 0 AND a.team_id = $teamId $dateCond AND ($totalSaleSum) > 0",
                                [],
                                true
                            );

                            $OverallShopCount[$teamId] = ($overallshopRoc ? $overallshopRoc : 0) + ($overallshopOther ? $overallshopOther : 0);
                        }

                        foreach ($shopCount as $mainBranchName => $branchData) {
                            foreach ($branchData as $branchName => $teams) {
                                foreach ($teams as $teamId => $teamData) {
                                    foreach ($teamData as $teamName => $wdCodeData) {
                                        foreach ($wdCodeData as $wdCode => $teamTypeData) {
                                            foreach ($teamTypeData as $teamType => $productData) {
                                                foreach ($productData as $colName => $distinctShops) {
                                                    $totalShops = $totalShopCount[$teamId];
                                                    $overallUob = $OverallShopCount[$teamId];
                                                    $uobPercentage = $totalShops > 0 ? ($distinctShops / $totalShops) * 100 : 0;

                                                    $arrExcelData[] = [
                                                        'Branch' => $mainBranchName,
                                                        'Region' => $branchName,
                                                        'WD Code' => $wdCode,
                                                        'DS Type' => $teamType,
                                                        'DS Id' => $teamId,
                                                        'DS Name' => $teamName,
                                                        'SKU' => $productNames[array_search($colName, $summaryColName)],
                                                        'Total Outlets Mapped' => $totalShops,
                                                        'Overall UOB' => $overallUob,
                                                        'Brand UOB' => $distinctShops,
                                                        'Brand UOB%' => number_format($uobPercentage, 2) . '%',
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($arrExcelData)) {
            // Check for blank rows before sending the data
            $arrExcelData = array_filter($arrExcelData, function ($row) {
                // Ensure each row has meaningful data; adjust conditions based on your data structure
                return !empty($row['DS Name']) && !empty($row['Total Outlets Mapped']);
            });
            $subject = "Van DS - Consolidated UOB report for North (MTD)";
            $this->commonFunctions->sendMailWithAttachment($fileName, $subject, ["SMIS.RPA@ITC.IN"], ["appilary@gmail.com", "Sanjeev.Kr@itc.in", "Akhilesh.chourasia@itc.in", "Tmnd.Rpa@itc.in", "Koyel.Guha@itc.in"], $arrHeaders, $arrExcelData);
        }
    }
}

$commonFunctions = new CommonFunctions();
$dbConn = new DBConnection($DB_DBNAME, $DB_USERNAME, $DB_PASSWORD, $commonFunctions, true);
$tableUtil = new TableUtil($dbConn, $commonFunctions);
$vanDsMailer = new VanDsMailer($dbConn, $tableUtil, $commonFunctions);
$vanDsMailer->sendUOBReport();
