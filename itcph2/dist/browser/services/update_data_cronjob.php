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

    final public function updateAttendanceProductWiseStock()
    {
        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $stockSummaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];

        // get stock pickup products
        $this->getBranchWiseStockPickupProducts();

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT att_id, team_id, s_id, other_details, capture_date, rcd, rdt FROM $attendanceTable WHERE dstatus = 0 AND call_type = '0' AND pickup_stock_updated = 0 LIMIT 200";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $arrTeamBranch = [];
            while ($row = $this->_dbConn->GetData($sAction)) {
                $attId = $row["att_id"];
                $teamId = $row["team_id"];
                $jsonId = $row["s_id"];
                $otherDetails = $row["other_details"];
                $captureDate = $row["capture_date"];
                $rcd = $row["rcd"];
                $rdt = $row["rdt"];

                // Check if summary already exists, if not, add summary else don't add summary
                $isExist = isRecordExist($this->_dbConn, $stockSummaryTable, "sp_id", "team_id = $teamId AND capture_date = '$captureDate' AND stock_type = 0 AND dstatus = 0");

                if ($isExist == 0) {
                    // get team branch
                    if (isset($arrTeamBranch[$teamId])) {
                        $branchId = $arrTeamBranch[$teamId];
                    } else {
                        $arrTeamDetails = getRowColumns($this->_dbConn, $projectTeamTable, "branch_id, is_type", "team_id = $teamId");
                        $branchId = $arrTeamDetails[0] ? $arrTeamDetails[0] : 1;
                        $teamType = $arrTeamDetails[1] ? $arrTeamDetails[1] : 0;
                        $arrTeamBranch[$teamId] = $branchId;
                        $arrTeamBranch[$teamType] = $arrTeamDetails[1];
                    }

                    $colsQty = "team_id, capture_date, stock_type, rec_id, rcd, rdt";
                    $valsQty = "?, ?, 0, ?, ?, ?";
                    $arrParamsQty = [$teamId, $captureDate, $attId, $rcd, $rdt];

                    $arrOtherDetails = $otherDetails ? json_decode($otherDetails, true) : [];
                    $arrPickupDetails = isset($arrOtherDetails["pickupDetails"]) ? $arrOtherDetails["pickupDetails"] : [];
                    // Get branch stock pickup products
                    $arrStockProductColumns = $this->getBranchWiseStockPickupProducts($branchId, $jsonId, $teamType);
                    // print_r($arrOtherDetails);die;
                    // Get stock
                    $arrStock = getGridDataAsArray($arrPickupDetails["ansGrid"], 2, count($arrStockProductColumns));
                    // Add stock for each product
                    if (isNonEmptyArray($arrStockProductColumns)) {
                        $arrUsedColumns = [];
                        foreach ($arrStockProductColumns as $productIndex => $productSummaryColumn) {
                            $iQty = isset($arrStock[1][$productIndex]) && floatval($arrStock[1][$productIndex]) ? floatval($arrStock[1][$productIndex]) : 0;

                            if ($iQty > 0 && !in_array($productSummaryColumn, $arrUsedColumns)) {
                                $colsQty .= ", $productSummaryColumn";
                                $valsQty .= ", ?";
                                $arrParamsQty[] = $iQty;
                                $arrUsedColumns[] = $productSummaryColumn;
                            }
                        }
                    }

                    addRecord($this->_dbConn, $stockSummaryTable, $colsQty, $valsQty, $arrParamsQty);
                }

                updateRecord($this->_dbConn, $attendanceTable, "pickup_stock_updated = 1", "att_id = $attId");
            }
        }
    }

    // private function getBranchWiseStockPickupProducts($branchId = null, $jsonId = null)
    // {
    //     $branchPickupstockProducts = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

    //     if ($branchId) {
    //         return isset($this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId]) &&
    //             $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId] ?
    //             $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId] : array();
    //     } else {
    //         // ORDER BY is important
    //         $arrProductSummaryColumns = getRowsColumns($this->_dbConn, $branchPickupstockProducts, "branch_id, json_id, summary_column_name", "dstatus = 0 ORDER BY json_id, sort_order");

    //         foreach ($arrProductSummaryColumns as $arrProduct) {
    //             $branchId = $arrProduct[0];
    //             $jsonId = $arrProduct[1];
    //             $summaryColumnName = $arrProduct[2];

    //             if (!isset($this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId])) {
    //                 $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId] = array();
    //             }
    //             $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][] = $summaryColumnName;
    //         }
    //     }
    // }

    private function getBranchWiseStockPickupProducts($branchId = null, $jsonId = null, $teamType = null)
    {
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        if ($branchId) {
            $teamTypeKey = ($teamType !== null && $teamType !== "") ? $teamType : 'default';

            // Avoid undefined index warning
            $value = $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$teamTypeKey] ?? [];
            return (array)$value;
        } else {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = getRowsColumns(
                    $this->_dbConn,
                    $branchProductsTable,
                    "branch_id, json_id, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' AND branch_id = '$branchId' ORDER BY json_id, sort_order",
                    [],
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $jsonId = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$teamType])) {
                        $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$teamType] = [];
                    }
                    $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$teamType][] = $summaryColumnName;
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT branch_id, json_id, summary_column_name, team_type FROM $branchProductsTable  WHERE dstatus = 0 ORDER BY json_id, sort_order";
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];
                        $jsonId = $rowProduct["json_id"];
                        $team_type = $rowProduct["team_type"];
                        if (!isset($this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$team_type])) {
                            $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$team_type] = [];
                        }
                        $this->_jsonWiseAndbranchWiseStockpickupProductsColumns[$jsonId][$branchId][$team_type][] = $rowProduct["summary_column_name"];
                    }
                }
            }
        }
    }

    final public function updateUobSalesData()
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $respTable = "tblsurvey_response_details";

        $sProductQuery = "SELECT branch_id, team_type, product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 ORDER BY branch_id, team_type, product_name";
        $sProductAction = null;
        $iProductRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

        $arrBranchWiseProducts = [];

        if ($iProductRows > 0) {
            while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                $branchId = $rowProduct["branch_id"];
                $teamType = $rowProduct["team_type"];
                $productName = $rowProduct["product_name"];
                $summaryColName = $rowProduct["summary_column_name"];

                // Initialize arrays if they do not exist
                if (!isset($arrBranchWiseProducts[$branchId])) {
                    $arrBranchWiseProducts[$branchId] = [];
                }
                if (!isset($arrBranchWiseProducts[$branchId][$teamType])) {
                    $arrBranchWiseProducts[$branchId][$teamType] = [];
                }

                // Assign product name to summary column in the respective branch/team array
                $arrBranchWiseProducts[$branchId][$teamType][$productName] = $summaryColName;
            }
        }

        $captureDate = date('Y-m-d');
        $currentMonthName = date('F'); // Gets the current month name (e.g., 'October')
        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT a.team_id, b.branch_id, b.is_type FROM $respTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND a.capture_date = '$captureDate' ORDER BY a.team_id DESC";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $branchId = $row["branch_id"];
                $teamType = $row["is_type"];

                // Step 3: Check if products are available for the team's branch and team type
                if (isset($arrBranchWiseProducts[$branchId][$teamType])) {
                    $teamProducts = $arrBranchWiseProducts[$branchId][$teamType];

                    // Step 4: Loop through each product and perform the sales count and sum query
                    foreach ($teamProducts as $productName => $summaryColName) {
                        $productQuery = "SELECT COUNT(DISTINCT ques_3) AS DistinctShops, SUM($summaryColName) AS TotalSale  FROM tblsurvey_response_details
                        WHERE $summaryColName > 0 AND capture_date = '$captureDate' AND team_id = $teamId";

                        $productAction = null;
                        $productRows = 0;
                        $this->_dbConn->ExecuteSelectQuery($productQuery, $productAction, $productRows);

                        if ($productRows > 0) {
                            $productData = $this->_dbConn->GetData($productAction);
                            $distinctShops = $productData['DistinctShops'];
                            $totalSale = $productData['TotalSale'];

                            // Check if summary already exists, if not, add summary else don't add summary
                            $isExist = isRecordExist($this->_dbConn, "tblwdapp_uob_sales_data", "ms_id", "team_id = $teamId AND rcd = '$captureDate' AND product_name = '$productName'");

                            if ($isExist > 0) {
                                $values = "uob = ?, sales = ?, rdt = ?";
                                $condition = "team_id = ? AND rcd = ? AND product_name = ?";
                                $arrParamsUpdate = [$distinctShops, $totalSale, date('Y-m-d H:i:s'), $teamId, $captureDate, $productName];
                                // Update existing record
                                updateRecord($this->_dbConn, "tblwdapp_uob_sales_data", $values, $condition, $arrParamsUpdate);
                            } else {
                                $colsQty = "team_id, month, product_name, uob, sales, rcd, rdt";
                                $valsQty = "?, ?, ?, ?, ?, ?, ?";
                                $arrParamsQty = [$teamId, $currentMonthName, $productName, $distinctShops, $totalSale, $captureDate, date('Y-m-d H:i:s')];
                                addRecord($this->_dbConn, "tblwdapp_uob_sales_data", $colsQty, $valsQty, $arrParamsQty);
                            }
                        }
                    }
                }
            }
        }
    }

    final public function updateWeeklyUobSalesData()
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $respTable = "tblsurvey_response_details";

        $sProductQuery = "SELECT branch_id, team_type, product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 ORDER BY branch_id, team_type, product_name";
        $sProductAction = null;
        $iProductRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

        $arrBranchWiseProducts = [];

        if ($iProductRows > 0) {
            while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                $branchId = $rowProduct["branch_id"];
                $teamType = $rowProduct["team_type"];
                $productName = $rowProduct["product_name"];
                $summaryColName = $rowProduct["summary_column_name"];

                if (!isset($arrBranchWiseProducts[$branchId])) {
                    $arrBranchWiseProducts[$branchId] = [];
                }
                if (!isset($arrBranchWiseProducts[$branchId][$teamType])) {
                    $arrBranchWiseProducts[$branchId][$teamType] = [];
                }

                $arrBranchWiseProducts[$branchId][$teamType][$productName] = $summaryColName;
            }
        }

        $currentMonthName = date('F');
        $currentYear = date('Y');
        $weeks = [
            ['start' => '01', 'end' => '07', 'week' => 1],
            ['start' => '08', 'end' => '14', 'week' => 2],
            ['start' => '15', 'end' => '21', 'week' => 3],
            ['start' => '22', 'end' => date('t'), 'week' => 4] // Adjust for the number of days in the month
        ];

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT a.team_id, b.branch_id, b.is_type FROM $respTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND MONTH(a.capture_date) = MONTH(CURDATE()) ORDER BY a.team_id DESC";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $branchId = $row["branch_id"];
                $teamType = $row["is_type"];

                if (isset($arrBranchWiseProducts[$branchId][$teamType])) {
                    $teamProducts = $arrBranchWiseProducts[$branchId][$teamType];

                    foreach ($teamProducts as $productName => $summaryColName) {
                        $weeklyData = [
                            'week_1uob' => 0,
                            'week_1sales' => 0,
                            'week_2uob' => 0,
                            'week_2sales' => 0,
                            'week_3uob' => 0,
                            'week_3sales' => 0,
                            'week_4uob' => 0,
                            'week_4sales' => 0,
                            'monthy_uob' => 0,
                            'monthy_sales' => 0
                        ];

                        foreach ($weeks as $week) {
                            $startDate = $currentYear . '-' . date('m') . '-' . $week['start'];
                            $endDate = $currentYear . '-' . date('m') . '-' . $week['end'];

                            $productQuery = "SELECT COUNT(DISTINCT ques_3) AS DistinctShops, SUM($summaryColName) AS TotalSale FROM tblsurvey_response_details
                            WHERE $summaryColName > 0 AND capture_date BETWEEN '$startDate' AND '$endDate' AND team_id = $teamId";

                            $productAction = null;
                            $productRows = 0;
                            $this->_dbConn->ExecuteSelectQuery($productQuery, $productAction, $productRows);

                            if ($productRows > 0) {
                                $productData = $this->_dbConn->GetData($productAction);
                                $distinctShops = $productData['DistinctShops'] ?? 0; // Use 0 if NULL
                                $totalSale = $productData['TotalSale'] ?? 0; // Use 0 if NULL

                                $weeklyData["week_{$week['week']}uob"] = $distinctShops;
                                $weeklyData["week_{$week['week']}sales"] = $totalSale;

                                // Accumulate monthly sales
                                $weeklyData['monthy_sales'] += $totalSale;
                            }
                        }

                        // Calculate monthly UOB (distinct shops for the entire month)
                        $monthlyUobQuery = "SELECT COUNT(DISTINCT ques_3) AS DistinctMonthlyShops FROM tblsurvey_response_details WHERE $summaryColName > 0 AND MONTH(capture_date) = MONTH(CURDATE()) AND YEAR(capture_date) = YEAR(CURDATE()) AND team_id = $teamId";

                        $monthlyUobAction = null;
                        $monthlyUobRows = 0;
                        $this->_dbConn->ExecuteSelectQuery($monthlyUobQuery, $monthlyUobAction, $monthlyUobRows);

                        if ($monthlyUobRows > 0) {
                            $monthlyUobData = $this->_dbConn->GetData($monthlyUobAction);
                            $weeklyData['monthy_uob'] = $monthlyUobData['DistinctMonthlyShops'] ?? 0;
                        }

                        $isExist = isRecordExist($this->_dbConn, "tblwdapp_uob_sales_data_weekly", "ms_id", "team_id = $teamId AND month = '$currentMonthName' AND product_name = '$productName'");

                        if ($isExist > 0) {
                            $values = "week_1uob = ?, week_1sales = ?, week_2uob = ?, week_2sales = ?,
                                   week_3uob = ?, week_3sales = ?, week_4uob = ?, week_4sales = ?,
                                   monthy_uob = ?, monthy_sales = ?, rdt = ?";
                            $condition = "team_id = ? AND month = ? AND product_name = ?";
                            $arrParamsUpdate = [
                                $weeklyData['week_1uob'],
                                $weeklyData['week_1sales'],
                                $weeklyData['week_2uob'],
                                $weeklyData['week_2sales'],
                                $weeklyData['week_3uob'],
                                $weeklyData['week_3sales'],
                                $weeklyData['week_4uob'],
                                $weeklyData['week_4sales'],
                                $weeklyData['monthy_uob'],
                                $weeklyData['monthy_sales'],
                                date('Y-m-d H:i:s'),
                                $teamId,
                                $currentMonthName,
                                $productName
                            ];
                            updateRecord($this->_dbConn, "tblwdapp_uob_sales_data_weekly", $values, $condition, $arrParamsUpdate);
                        } else {
                            $colsQty = "team_id, month, product_name, week_1uob, week_1sales, week_2uob, week_2sales,
                                    week_3uob, week_3sales, week_4uob, week_4sales, monthy_uob, monthy_sales, rcd, rdt";
                            $valsQty = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                            $arrParamsQty = [
                                $teamId,
                                $currentMonthName,
                                $productName,
                                $weeklyData['week_1uob'],
                                $weeklyData['week_1sales'],
                                $weeklyData['week_2uob'],
                                $weeklyData['week_2sales'],
                                $weeklyData['week_3uob'],
                                $weeklyData['week_3sales'],
                                $weeklyData['week_4uob'],
                                $weeklyData['week_4sales'],
                                $weeklyData['monthy_uob'],
                                $weeklyData['monthy_sales'],
                                date('Y-m-d'),
                                date('Y-m-d H:i:s')
                            ];
                            addRecord($this->_dbConn, "tblwdapp_uob_sales_data_weekly", $colsQty, $valsQty, $arrParamsQty);
                        }
                    }
                }
            }
        }
    }

    final public function updateDistance()
    {
        $respTable = "tblsurvey_response_details";
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        $currentDate = currentDate();
        $sDateCond = "AND capture_date = '$currentDate'";

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT pro_id, ques_3, lt, lg FROM $respTable WHERE dstatus = 0 AND update_distance = 0 ORDER BY capture_datetime DESC LIMIT 500";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $arrShops = [];
            while ($row = $this->_dbConn->GetData($sAction)) {
                $proId = $row["pro_id"];
                $shopDoneLt = $row["lt"];
                $shopDoneLg = $row["lg"];
                $shopId = $row["ques_3"];

                $updateDistance = 0;
                $distanceInM = 0;
                if ($shopId > 0 && $shopDoneLt > 0) {
                    // get shop coordinates
                    if (!isset($arrShops[$shopId])) {
                        $arrShops[$shopId] = getRowColumns(
                            $this->_dbConn,
                            $routeDetailsTable,
                            "lt, lg",
                            "rec_id = ?",
                            [$shopId]
                        );
                    }

                    $shopActualLt = isset($arrShops[$shopId][0]) ? $arrShops[$shopId][0] : 0;
                    $shopActualLg = isset($arrShops[$shopId][1]) ? $arrShops[$shopId][1] : 0;

                    if ($shopActualLt > 0) {
                        $distanceInM = calculateDistanceBwCoordinates(
                            $shopActualLt,
                            $shopActualLg,
                            $shopDoneLt,
                            $shopDoneLg
                        );
                        $updateDistance = 1;
                    } else {
                        $updateDistance = 2;
                    }
                } else {
                    $updateDistance = 2;
                }

                updateRecord(
                    $this->_dbConn,
                    $respTable,
                    "update_distance = ?, distance_in_meter = ?",
                    "pro_id = $proId",
                    [$updateDistance, $distanceInM]
                );
            }
        }
    }
}

$updateDataCronjob = new UpdateDataCronjob($dbConn);
$updateDataCronjob->updateAttendanceProductWiseStock();
// $updateDataCronjob->updateUobSalesData();
// $updateDataCronjob->updateWeeklyUobSalesData();
$updateDataCronjob->updateDistance();
