<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class VanDsMailer
{
    private $_dbConn = null;
    private $_tables = [];
    private $arrBranchWiseStockProducts = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
    }

    final public function sendSummary()
    {
        $currentDate = currentDate();
        $currentDatetime = currentDateTime();

        // get branch wise pickup stock products
        $this->getBranchWiseStockPickupProducts();

        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PRODUCTS_TABLE"];
        $stockSummaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];
        $constantsTable = $this->_tables["CONSTANTS_TABLE"];

        $minTotalShops =  (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
        $minQualifiedAttendanceTimeInMin =  (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
        $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;

        $fileName = "VanDS_Summary_" . str_replace(array(" ", ":"), "_", $currentDatetime) . ".xlsx";

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
                    // create header based on branch
                    $arrHeader = array("WD Code", "Team Name", "Start Time", "End Time", "Total Time Spent", "ROC Deliveries", "Sell-in Shops", "Other Shops", "KM Travelled", "Total Shops (ROC + Other)", "Qualified Attendance");

                    // Get products
                    $arrProducts = array();
                    $arrProductsColumn = array();
                    $arrQueryProductsColumn = array();
                    $sProductAction = null;
                    $iProductRows = 0;
                    $sProductQuery = "SELECT DISTINCT product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId AND product_type = 0 ORDER BY product_name";
                    $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                    if ($iProductRows > 0) {
                        while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                            $productName = $rowProduct["product_name"];
                            $summaryColumnName = $rowProduct["summary_column_name"];

                            $arrProducts[] = $productName;
                            $arrProductsColumn[] = $summaryColumnName;
                            $arrQueryProductsColumn[] = "SUM(a.$summaryColumnName) AS $summaryColumnName";
                        }
                    }

                    foreach ($arrProducts as $product) {
                        $arrHeader[] = "$product - Qty (Pkt) Bought";
                    }

                    $arrStockProducts = $this->getBranchWiseStockPickupProducts($branchId);
                    $sStockColumns = "";
                    foreach ($arrStockProducts as $product) {
                        $stockProductName = strtoupper($product[0]);
                        $arrHeader[] = "{$stockProductName} - Readystock Qty (Pkt)";
                        // $arrHeader[] = "{$stockProductName} - Readystock Avg Sale";
                        $sStockColumns .= ", {$product[1]}";
                    }

                    // get today's stock for each team
                    $arrTeamWiseStock = array();
                    $sStockAction = null;
                    $iStockRows = 0;
                    $sStockQuery = "SELECT team_id, stock_type $sStockColumns FROM $stockSummaryTable WHERE dstatus = 0 AND capture_date = '$currentDate' AND stock_type IN (0, 1)";
                    $this->_dbConn->ExecuteSelectQuery($sStockQuery, $sStockAction, $iStockRows);

                    if ($iStockRows > 0) {
                        while ($rowStock = $this->_dbConn->GetData($sStockAction)) {
                            $teamId = $rowStock["team_id"];
                            $stockType = $rowStock["stock_type"];

                            $arrTeamWiseStock[$teamId][$stockType] = array();
                            foreach ($arrStockProducts as $product) {
                                $arrTeamWiseStock[$teamId][$stockType][$product[1]] = $rowStock[$product[1]];
                            }
                        }
                    }

                    // get today's sales for each team
                    // Don't use b.dstatus = 0
                    $sProductColumns = implode(",", $arrQueryProductsColumn);
                    $sAction = null;
                    $iRows = 0;
                    $sQuery = "SELECT a.start_datetime, a.end_datetime, SUM(a.total_roc_deliveries) AS total_roc_deliveries, SUM(a.total_sellin_shops) AS total_sellin_shops, SUM(a.total_other_shops) AS total_other_shops, a.total_meter_travelled, $sProductColumns" .
                        ", b.team_id, b.team_name, b.wd_code FROM $summaryTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND a.activity_date = '$currentDate' AND b.branch_id = $branchId GROUP BY a.team_id ORDER BY b.team_name";
                    $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

                    $arrData = array();
                    if ($iRows > 0) {
                        $i = 0;
                        while ($row = $this->_dbConn->GetData($sAction)) {
                            $teamId = $row["team_id"];
                            $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
                            $totalShops = $row["total_roc_deliveries"] + $row["total_other_shops"];
                            $isQualifiedAttendance = $totalShops >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? "Yes" : "No";

                            $arrData[$i] = array(
                                $row["wd_code"],
                                $row["team_name"],
                                currentDateTime($row["start_datetime"], "h:i:s A"),
                                currentDateTime($row["end_datetime"], "h:i:s A"),
                                getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"]),
                                $row["total_roc_deliveries"],
                                $row["total_sellin_shops"],
                                $row["total_other_shops"],
                                isset($row["total_meter_travelled"]) ? round($row["total_meter_travelled"] / 1000, 2) : 0,
                                $totalShops,
                                $isQualifiedAttendance,
                            );

                            // insert sale
                            foreach ($arrProductsColumn as $column) {
                                $arrData[$i][] = isset($row[$column]) ? $row[$column] : 0;
                            }

                            // insert pickup stock Qty and Avg sale
                            foreach ($arrStockProducts as $product) {
                                $arrStock = isset($arrTeamWiseStock[$teamId]) ? $arrTeamWiseStock[$teamId] : array();
                                $arrData[$i][] = isset($arrStock[0][$product[1]]) ? $arrStock[0][$product[1]] : "";
                                // $arrData[$i][] = isset($arrStock[1][$product[1]]) ? $arrStock[1][$product[1]] : "";
                            }

                            $i++;
                        }
                    }

                    // Insert teams who have not uploaded any record today
                    $iTeamRows = 0;
                    $rsTeamAction = 0;
                    $sTeamQuery = "SELECT a.team_name, a.wd_code FROM $projectTeamTable AS a WHERE a.dstatus = 0 AND a.branch_id = $branchId AND a.team_id NOT IN (SELECT DISTINCT team_id FROM $summaryTable WHERE dstatus = 0 AND activity_date = '$currentDate') ORDER BY a.team_name";
                    $this->_dbConn->ExecuteSelectQuery($sTeamQuery, $rsTeamAction, $iTeamRows);

                    if ($iTeamRows) {
                        while ($rowTeam = $this->_dbConn->GetData($rsTeamAction)) {
                            $arrData[] = array(
                                $rowTeam["wd_code"],
                                $rowTeam["team_name"],
                            );
                        }
                    }

                    $subject = "$branchName - Van DS Summary report for " . currentDate($currentDate, "d-m-Y");
                    sendMailWithCSVOrXlsxAttached(false, $fileName, $arrHeader, $arrData, $subject, $arrTo, $arrCc);
                }
            }
        }
    }

    private function getBranchWiseStockPickupProducts($branchId = null)
    {
        if ($branchId) {
            return isset($this->arrBranchWiseStockProducts[$branchId]) && $this->arrBranchWiseStockProducts[$branchId] ?
                $this->arrBranchWiseStockProducts[$branchId] : array();
        } else {
            $branchPickupstockProducts = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

            $sProductAction = null;
            $iProductRows = 0;
            $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name FROM $branchPickupstockProducts WHERE dstatus = 0 ORDER BY product_name";
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $branchId = $rowProduct["branch_id"];
                    $productName = $rowProduct["product_name"];
                    $summaryColumnName = $rowProduct["summary_column_name"];

                    if (!isset($this->arrBranchWiseStockProducts[$branchId])) {
                        $this->arrBranchWiseStockProducts[$branchId] = array();
                    }
                    $this->arrBranchWiseStockProducts[$branchId][] = array($productName, $summaryColumnName);
                }
            }
        }
    }
}

$vanDsMailer = new VanDsMailer($dbConn);
$vanDsMailer->sendSummary();
