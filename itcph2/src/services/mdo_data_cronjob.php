<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

class ProcessMdoData
{
    private $dbConn = null;
    private $tables = [];

    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
        $this->tables = $GLOBALS['TABLES'];
    }

    final public function processMdoData()
    {
        $branchPickupStockTable = $this->tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT mdo_id, teams FROM tblmdo_access WHERE dstatus = 0";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $mdoId = $row['mdo_id'];
                $team = $row['teams'];

                $arrTeamDetails = getRowColumns($this->dbConn, "tblproject_team", "is_type, branch_id", "dstatus = 0 AND team_id = $team");
                if (empty($arrTeamDetails)) {
                    continue;
                }

                $teamType = $arrTeamDetails[0];
                $branchId = $arrTeamDetails[1];
                $mdoName = getRowColumn($this->dbConn, "tblproject_team", "team_name", "dstatus = 0 AND team_id = $mdoId");

                if ($teamType == 4 || $teamType == 6) {
                    $getRouteDetails = getRowsColumns($this->dbConn, "tblscp_rmd_route_details", "rec_id, route_name, outlet_name, outlet_mobile, market_name, wd_code, lt, lg, ds_name", "dstatus = 0 AND team_id = $team");
                } else {
                    $getRouteDetails = getRowsColumns($this->dbConn, "tblroute_details", "rec_id, route_name, outlet_name, outlet_mobile, market_name, wd_code, lt, lg", "dstatus = 0 AND team_id = $team");
                }

                foreach ($getRouteDetails as $row) {
                    $shopId = $row[0];
                    $route = $row[1];
                    $outletName = $row[2];
                    $outletMobile = $row[3];
                    $market = $row[4];
                    $wdCode = $row[5];
                    $lt = $row[6];
                    $lg = $row[7];
                    $dsName = $teamType == 4 || $teamType == 6 ? $row[8] : getRowColumn($this->dbConn, "tblproject_team", "team_name", "dstatus = 0 AND team_id = $team");

                    $allBrandCols = getRowsColumns($this->dbConn, $branchPickupStockTable, "summary_column_name, product_name", "dstatus = 0 AND branch_id = $branchId", array(), true);
                    $productCols = [];
                    $productNames = [];

                    foreach ($allBrandCols as $colRow) {
                        $productCols[] = $colRow[0];
                        $productNames[] = $colRow[1];
                    }

                    $summaryColumns = implode(") + SUM(", $productCols);
                    $sumColumns = "SUM($summaryColumns)";
                    $sQuery2 = "SELECT $sumColumns AS totalSum, COUNT(*) AS entryCount, MAX(capture_date) AS last_visit FROM tblsurvey_response_details WHERE dstatus = 0 AND ques_3 = $shopId AND team_id = $team";

                    $sAction2 = null;
                    $iRows2 = 0;
                    $totalSale = 0;
                    $entryCount = 0;
                    $lastVisit = null;

                    $this->dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
                    if ($iRows2 > 0) {
                        while ($row2 = $this->dbConn->GetData($sAction2)) {
                            $totalSale = $row2['totalSum'] ?? 0;
                            $entryCount = $row2['entryCount'];
                            $lastVisit = $row2['last_visit'];
                        }
                    }

                    $averageSale = $entryCount > 0 ? ($totalSale / $entryCount) : 0;
                    $arrcallTime = getRowColumn($this->dbConn, "tblsurvey_response_details", "SUM(call_time)", "dstatus = 0 AND ques_3 = $shopId AND team_id = $team");
                    $avegCft = $entryCount > 0 ? ($arrcallTime / $entryCount) : 0;
                    $lastOrder = getRowColumn($this->dbConn, "tblsurvey_response_details", "MAX(capture_date)", "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team");

                    $maxQty = -1;
                    $minQty = PHP_FLOAT_MAX;
                    $maxProductName = '';
                    $minProductName = '';
                    $hasValidProductQty = false;

                    foreach ($productCols as $index => $productCol) {
                        $totalQty = getRowColumn(
                            $this->dbConn,
                            "tblsurvey_response_details",
                            "SUM($productCol)",
                            "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team"
                        );
                        $totalQty = floatval($totalQty);

                        if ($totalQty > 0) {
                            $hasValidProductQty = true;

                            if ($totalQty > $maxQty) {
                                $maxQty = $totalQty;
                                $maxProductName = $productNames[$index];
                            }

                            if ($totalQty < $minQty) {
                                $minQty = $totalQty;
                                $minProductName = $productNames[$index];
                            }
                        }
                    }

                    if (!$hasValidProductQty) {
                        $maxProductName = '';
                        $minProductName = '';
                    }

                    $arrFocusProduct = getRowsColumn($this->dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' AND branch_id = $branchId");
                    $focusProd1 = $arrFocusProduct[0] ?? null;
                    $focusProd2 = $arrFocusProduct[1] ?? null;

                    $lastFocus1DateUnits = '';
                    $lastFocus2DateUnits = '';

                    if ($focusProd1) {
                        $lastPurchaseFocus1 = getRowColumns($this->dbConn, "tblsurvey_response_details", "MAX(capture_date) AS lastPurchase, $focusProd1", "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team AND $focusProd1 > 0");
                        $lastFocus1DateUnits = $lastPurchaseFocus1[0] . ", " . $lastPurchaseFocus1[1] . "Units";
                    }

                    if ($focusProd2) {
                        $lastPurchaseFocus2 = getRowColumns($this->dbConn, "tblsurvey_response_details", "MAX(capture_date) AS lastPurchase, $focusProd2", "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team AND $focusProd2 > 0");
                        $lastFocus2DateUnits = $lastPurchaseFocus2[0] . ", " . $lastPurchaseFocus2[1] . "Units";
                    }

                    $iStatus = isRecordExist($this->dbConn, "tblmdo_offline_data", "id", "team_id = ? AND ds_id = ? AND outlet_id = ?", array($mdoId, $team, $shopId));

                    if ($iStatus === 1) {
                        $cols = "total_survey_qty = ?, avg_survey_qty = ?, avg_cft = ?, total_visits = ?, last_ds_visit = ?, last_order = ?, highest_survey_product = ?, lowest_survey_product = ?, focus1_last_purchase = ?, focus2_last_purchase = ?";
                        $arrParams = array($totalSale, $averageSale, $avegCft, $entryCount, $lastVisit, $lastOrder, $maxProductName, $minProductName, $lastFocus1DateUnits, $lastFocus2DateUnits, $mdoId, $team, $shopId);

                        updateRecord($this->dbConn, "tblmdo_offline_data", $cols, "team_id = ? AND ds_id = ? AND outlet_id = ?", $arrParams);
                    } else {
                        $addCols = "team_id, team_name, wd_code, ds_id, ds_name, type, route_name, outlet_name, outlet_id, address, outlet_number, total_survey_qty, avg_survey_qty, avg_cft" .
                            ", total_visits, last_ds_visit, last_order, highest_survey_product, lowest_survey_product, focus1_last_purchase, focus2_last_purchase, lt, lg";
                        $addVals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrAddParams = array(
                            $mdoId, $mdoName, $wdCode, $team, $dsName, $teamType, $route, $outletName, $shopId, $market, $outletMobile, $totalSale,
                            $averageSale, $avegCft, $entryCount, $lastVisit, $lastOrder, $maxProductName, $minProductName, $lastFocus1DateUnits, $lastFocus2DateUnits, $lt, $lg
                        );
                        addRecord($this->dbConn, "tblmdo_offline_data", $addCols, $addVals, $arrAddParams);
                    }
                }
            }
        }
    }
}

$processResponse = new ProcessMdoData($dbConn);
$processResponse->processMdoData();
