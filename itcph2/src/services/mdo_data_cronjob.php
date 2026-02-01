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
        $currentMonth = date("Y-m");
        $currentDate = date("Y-m-d");

        $monthStart = $currentMonth . "-01";
        $nextMonth = date("Y-m-01", strtotime($monthStart . " +1 month"));

        $branchPickupStockTable = $this->tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

        $type = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR", 6 => "RMD", 8 => "SCP DS", 9 => "Common FMCG Lite DS");
        $rsAction = null;
        $iRows = 0;

        $sQuery = "SELECT id, mdo_id, teams, is_type FROM tblmdo_access WHERE dstatus = 0 AND is_type NOT IN (2,5) AND updated_date != '$currentDate' LIMIT 60";
        // $sQuery = "SELECT id, mdo_id, teams, is_type FROM tblmdo_access WHERE dstatus = 0 AND is_type NOT IN (2,5) AND mdo_id IN ('22870') LIMIT 30";

        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $id = $row['id'];
                $mdoId = $row['mdo_id'];
                $team = $row['teams'];
                $teamType = $row['is_type'];
                $mdoName = getRowColumn($this->dbConn, "tblproject_team", "team_name", "dstatus = 0 AND team_id = $mdoId");

                if ($teamType == 6 || $teamType == 8 || $teamType == 9) {
                    $teamTable = "tblbreeze_team";
                    $routeTable = "tblroute_details_breeze";
                    $addressCol = "outlet_address";
                } else {
                    $teamTable = "tblproject_team";
                    $routeTable = "tblroute_details";
                    $addressCol = "market_name";
                }

                $teamDetails = getRowColumns($this->dbConn, $teamTable, "branch_id, team_name", " team_id = '$team'");
                $branchId = $teamDetails[0];
                $dsName = $teamDetails[1];

                // Fetch products ONCE per team, not per outlet
                $productCols = [];
                $productNames = [];
                $focusProd1 = null;
                $focusProd2 = null;

                if ($teamType != 6 && $teamType != 8 && $teamType != 9) {
                    $allBrandCols = getRowsColumns($this->dbConn, $branchPickupStockTable, "summary_column_name, product_name", "dstatus = 0 AND branch_id = $branchId", array(), true);
                    foreach ($allBrandCols as $colRow) {
                        $productCols[] = $colRow[0];
                        $productNames[] = $colRow[1];
                    }

                    $arrFocusProduct = getRowsColumn($this->dbConn, $branchPickupStockTable, "summary_column_name", "dstatus = 0 AND is_focusbrand = '1' AND branch_id = $branchId");
                    $focusProd1 = $arrFocusProduct[0] ?? null;
                    $focusProd2 = $arrFocusProduct[1] ?? null;
                }

                $getRouteDetails = getRowsColumns($this->dbConn, $routeTable, "rec_id, route_name, outlet_name, outlet_mobile, $addressCol, wd_code, lt, lg, sort_order, dstatus", "team_id = '$team' AND dstatus = 0");

                foreach ($getRouteDetails as $row) {
                    $shopId = $row[0];
                    $route = $row[1] && !empty($row[1]) ? $row[1] : "NA";
                    $outletName = $row[2];
                    $outletMobile = $row[3];
                    $market = $row[4];
                    $wdCode = $row[5];
                    $lt = $row[6];
                    $lg = $row[7];
                    $shortOrder = $row[8];
                    $dstatus = $row[9];

                    if ($teamType != 6 && $teamType != 8 && $teamType != 9) {
                        // Products already fetched above - build query columns
                        $summaryColumns = implode(") + SUM(", $productCols);
                        $sumColumns = "SUM($summaryColumns)";

                        $sQuery2 = "SELECT $sumColumns AS totalSum, COUNT(*) AS entryCount, MAX(capture_date) AS last_visit
                                    FROM tblsurvey_response_details
                                    WHERE dstatus = 0 AND ques_3 = $shopId AND team_id = $team
                                      AND capture_date >= '$monthStart' AND capture_date < '$nextMonth'";

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

                        $summaryColumnsUlc = implode(",", $productCols);
                        $sAction3 = null;
                        $iRows3 = 0;
                        $sQuery3 = "SELECT $summaryColumnsUlc
                                    FROM tblsurvey_response_details
                                    WHERE dstatus = 0 AND ques_3 = $shopId AND team_id = $team
                                      AND capture_date >= '$monthStart' AND capture_date < '$nextMonth'";

                        $totalUniqueProducts = [];
                        $recordCount = 0;
                        $perRecordUlc = [];

                        $this->dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

                        if ($iRows3 > 0) {
                            while ($row3 = $this->dbConn->GetData($sAction3)) {
                                $recordCount++;
                                $seenProducts = [];
                                $ulc = 0;

                                foreach ($productCols as $index => $colName) {
                                    $productName = $productNames[$index];
                                    $value = floatval($row3[$colName]);

                                    if ($value > 0) {
                                        if (!in_array($productName, $seenProducts)) {
                                            $seenProducts[] = $productName;
                                            $ulc++;
                                        }

                                        if (!in_array($productName, $totalUniqueProducts)) {
                                            $totalUniqueProducts[] = $productName;
                                        }
                                    }
                                }

                                $perRecordUlc[] = $ulc;
                            }
                        }

                        $totalUlc = count($totalUniqueProducts);
                        $ulcAvg   = $recordCount > 0 ? ($totalUlc / $recordCount) : 0;

                        $callTime = getRowColumn(
                            $this->dbConn,
                            "tblsurvey_response_details",
                            "SUM(call_time)",
                            "ques_0 IN ('Outlet Order', 'Add Outlet') AND dstatus = 0 AND ques_3 = $shopId AND team_id = $team AND capture_date >= '$monthStart' AND capture_date < '$nextMonth'"
                        );
                        $time = $callTime / 1000;
                        $cft = $entryCount > 0 ? ($time / $entryCount) : 0;
                        list($min, $sec) = explode(':', gmdate("i:s", (int) round($cft)));
                        $avegCft = $min . '.' . $sec;

                        $lastOrder = getRowColumn(
                            $this->dbConn,
                            "tblsurvey_response_details",
                            "MAX(capture_date)",
                            "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team AND capture_date >= '$monthStart' AND capture_date < '$nextMonth' HAVING $sumColumns > 0"
                        );

                        // OPTIMIZATION 2: Get max/min products in single query instead of looping
                        $maxQty = -1;
                        $minQty = PHP_FLOAT_MAX;
                        $maxProductName = '';
                        $minProductName = '';
                        $hasValidProductQty = false;

                        if (!empty($productCols)) {
                            // Build single query to get all product sums at once
                            $productSums = array_map(function ($col) {
                                return "SUM($col) as sum_$col";
                            }, $productCols);
                            $productSumsStr = implode(", ", $productSums);

                            $sQueryProducts = "SELECT $productSumsStr
                                              FROM tblsurvey_response_details
                                              WHERE dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes'
                                                AND team_id = $team
                                                AND capture_date >= '$monthStart' AND capture_date < '$nextMonth'";

                            $sActionProducts = null;
                            $iRowsProducts = 0;
                            $this->dbConn->ExecuteSelectQuery($sQueryProducts, $sActionProducts, $iRowsProducts);

                            if ($iRowsProducts > 0) {
                                $rowProducts = $this->dbConn->GetData($sActionProducts);

                                foreach ($productCols as $index => $productCol) {
                                    $totalQty = floatval($rowProducts["sum_$productCol"] ?? 0);

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
                            }
                        }

                        if (!$hasValidProductQty) {
                            $maxProductName = '';
                            $minProductName = '';
                        }

                        // Focus products already fetched above
                        $lastFocus1DateUnits = '';
                        $lastFocus2DateUnits = '';

                        if ($focusProd1) {
                            $lastPurchaseFocus1 = getRowColumns(
                                $this->dbConn,
                                "tblsurvey_response_details",
                                "MAX(capture_date) AS lastPurchase, $focusProd1",
                                "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team AND capture_date >= '$monthStart' AND capture_date < '$nextMonth' AND $focusProd1 > 0"
                            );
                            $lastFocus1DateUnits = $lastPurchaseFocus1[0] && $lastPurchaseFocus1[1] ? $lastPurchaseFocus1[0] . ", " . $lastPurchaseFocus1[1] : 0;
                        }

                        if ($focusProd2) {
                            $lastPurchaseFocus2 = getRowColumns(
                                $this->dbConn,
                                "tblsurvey_response_details",
                                "MAX(capture_date) AS lastPurchase, $focusProd2",
                                "dstatus = 0 AND ques_3 = $shopId AND ques_4 = 'Yes' AND team_id = $team AND capture_date >= '$monthStart' AND capture_date < '$nextMonth' AND $focusProd2 > 0"
                            );
                            $lastFocus2DateUnits = $lastPurchaseFocus2[0] && $lastPurchaseFocus2[1] ? $lastPurchaseFocus2[0] . ", " . $lastPurchaseFocus2[1] : 0;
                        }
                    } elseif ($teamType == 6 || $teamType == 8 || $teamType == 9) {
                        $valueM = getRowColumn($this->dbConn, "tblbreeze_response_data", "value_m", "team_id = '$team' AND dstatus = 0");
                        $sale = getRowColumn($this->dbConn, "tblbreeze_response_data", "SUM(total_sale)", "team_id = '$team' AND dstatus = 0 AND capture_date BETWEEN '$monthStart' AND '$currentDate'");
                        $totalSale = isNonEmpty($valueM) && $valueM != NULL ? round($sale / $valueM, 2) : 0;

                        // reset variables for team types 6,8,9
                        $averageSale = 0;
                        $avegCft = 0;
                        $totalUlc = 0;
                        $ulcAvg = 0;
                        $entryCount = 0;
                        $lastVisit = null;
                        $lastOrder = null;
                        $maxProductName = '';
                        $minProductName = '';
                        $lastFocus1DateUnits = '';
                        $lastFocus2DateUnits = '';
                    }

                    $iStatus = isRecordExist($this->dbConn, "tblmdo_offline_data", "id", "team_id = ? AND ds_id = ? AND outlet_id = ?", array($mdoId, $team, $shopId));

                    if ($iStatus === 1) {
                        $cols = "team_id = ?, team_name = ?, wd_code = ?, ds_id = ?, ds_name = ?, type = ?, type_name = ?, route_name = ?, outlet_name = ?, outlet_id = ?, address = ?, outlet_number = ?, total_survey_qty = ?, avg_survey_qty = ?, avg_cft = ?, ulc = ?, avg_ulc = ?" .
                            ", total_visits = ?, last_ds_visit = ?, last_order = ?, highest_survey_product = ?, lowest_survey_product = ?, focus1_last_purchase = ?, focus2_last_purchase = ?, sort_order =?, dstatus = ?";
                        $arrParams = array(
                            $mdoId, $mdoName, $wdCode, $team, $dsName, $teamType, $type[$teamType], $route, $outletName, $shopId, $market, $outletMobile, $totalSale, $averageSale, $avegCft,
                            $totalUlc, $ulcAvg, $entryCount, $lastVisit, $lastOrder, $maxProductName, $minProductName, $lastFocus1DateUnits, $lastFocus2DateUnits, $shortOrder, $dstatus, $mdoId, $team, $shopId
                        );

                        updateRecord($this->dbConn, "tblmdo_offline_data", $cols, "team_id = ? AND ds_id = ? AND outlet_id = ?", $arrParams);
                    } else {
                        $addCols = "team_id, team_name, wd_code, ds_id, ds_name, type, type_name, route_name, outlet_name, outlet_id, address, outlet_number, total_survey_qty, avg_survey_qty, avg_cft, ulc, avg_ulc" .
                            ", total_visits, last_ds_visit, last_order, highest_survey_product, lowest_survey_product, focus1_last_purchase, focus2_last_purchase, lt, lg, sort_order, dstatus";
                        $addVals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrAddParams = array(
                            $mdoId,
                            $mdoName,
                            $wdCode,
                            $team,
                            $dsName,
                            $teamType,
                            $type[$teamType],
                            $route,
                            $outletName,
                            $shopId,
                            $market,
                            $outletMobile,
                            $totalSale,
                            $averageSale,
                            $avegCft,
                            $totalUlc,
                            $ulcAvg,
                            $entryCount,
                            $lastVisit,
                            $lastOrder,
                            $maxProductName,
                            $minProductName,
                            $lastFocus1DateUnits,
                            $lastFocus2DateUnits,
                            $lt,
                            $lg,
                            $shortOrder,
                            $dstatus
                        );
                        addRecord($this->dbConn, "tblmdo_offline_data", $addCols, $addVals, $arrAddParams);
                    }
                }

                updateRecord($this->dbConn, "tblmdo_access", "is_previous_day_updated = 1, updated_date = '$currentDate'", "id = $id");
            }
        }
    }
}

$processResponse = new ProcessMdoData($dbConn);
$processResponse->processMdoData();
