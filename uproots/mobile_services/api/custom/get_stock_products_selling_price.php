<?php

// Used in ITC Phase 2 setup to get category wise stock products and selling price of all products
// Used in South setup to get stock products selling price of all products
// Used in Delhi setup to get stock products selling price, today's and MTD pickup stock qty,
// product wise MTD sale, last 30 days product wise AVG sale,
// and whether to call stock API or not

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetStockProductsSellingPrice extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_stock_products_selling_price";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getProductsAndSellingPrice()
    {
        global $DELHI_DB, $SOUTH_DB, $ITCPH2_DB, $TBL_PROJECT_TEAM, $TBL_VANDS_SUMMARY;

        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];

        $jsonId = $this->requestGetData["json_id"];
        $branchId = ($dbName == $SOUTH_DB || $dbName == $DELHI_DB || $dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "branch_id",
                "team_id = $teamId"
            ) : null;
        $wdCode = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "wd_code",
                "team_id = $teamId"
            ) : null;
        $teamType = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "is_type",
                "team_id = $teamId"
            ) : null;

        // Branch found
        if ($branchId) {
            // South
            if ($dbName == $SOUTH_DB) {
                $rsProductsAction = null;
                $iProductsActionRows = 0;
                $sProductsQuery = "SELECT product_name, selling_price FROM $dbName.tblbranch_pickupstock_products" .
                    " WHERE branch_id = ? AND json_id = ? AND dstatus = 0 ORDER BY sort_order";
                $this->dbConn->ExecuteSelectQuery(
                    $sProductsQuery,
                    $rsProductsAction,
                    $iProductsActionRows,
                    array($branchId, $jsonId)
                );

                // Products found
                if ($iProductsActionRows > 0) {
                    $arrProductsSellingPrice = array();
                    while ($rowProduct = $this->dbConn->GetData($rsProductsAction)) {
                        $arrProductsSellingPrice[] = array(
                            "product_name" => $rowProduct["product_name"],
                            "selling_price" => floatval($rowProduct["selling_price"]),
                        );
                    }

                    $arrResponse = array(
                        "products_selling_price" => $arrProductsSellingPrice,
                    );

                    $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    $this->sendNoProductErrorMsg();
                }
            } elseif ($dbName == $DELHI_DB) {
                // Delhi
                $rsProductsAction = null;
                $iProductsActionRows = 0;
                $sProductsQuery = "SELECT product_name, summary_column_name, s_inhand_summary_column_name" .
                    ", selling_price FROM $dbName.tblbranch_pickupstock_products WHERE branch_id = ?" .
                    " AND json_id = ? AND dstatus = 0 ORDER BY sort_order";
                $this->dbConn->ExecuteSelectQuery(
                    $sProductsQuery,
                    $rsProductsAction,
                    $iProductsActionRows,
                    array($branchId, $jsonId)
                );

                // Products found
                if ($iProductsActionRows > 0) {
                    $arrProductsSellingPrice = array();
                    $arrPickupStockSumColumnNames = $arrPickupStockColumnNames = array();

                    while ($rowProduct = $this->dbConn->GetData($rsProductsAction)) {
                        $arrProductsSellingPrice[] = array(
                            "product_name" => $rowProduct["product_name"],
                            "selling_price" => floatval($rowProduct["selling_price"]),
                            "pickup_qty" => 0,
                            "mtd_pickup_qty" => 0,
                        );
                        $arrPickupStockColumnNames[] = $rowProduct["s_inhand_summary_column_name"];
                        $arrPickupStockSumColumnNames[] = "SUM({$rowProduct["summary_column_name"]})" .
                            " AS {$rowProduct["summary_column_name"]}";
                    }

                    // Get today's pickup stock qty of team
                    // $arrTodayPickupStock = $tableUtil->getRowColumns(
                    //     "$dbName.tblstock_summary",
                    //     implode(",", $arrPickupStockColumnNames),
                    //     "dstatus = 0 AND team_id = $teamId AND capture_date = '$currentDate' AND stock_type = 0"
                    // );
                    // Get inhand stock
                    $arrInhandStock = $this->tableUtil->getRowColumns(
                        "$dbName.tblstock_inhand",
                        implode(", ", $arrPickupStockColumnNames),
                        "dstatus = 0 AND team_id = $teamId"
                    );

                    // update is_updated_in_app flag so that App don't call this API again to get the stock
                    // This API is only called when is_updated_in_app = 0 sent in summary API
                    $this->tableUtil->updateRecord(
                        "$dbName.tblstock_inhand",
                        "is_updated_in_app = 1",
                        "dstatus = 0 AND team_id = $teamId"
                    );

                    // This flag tells whether to call stock API on click of + button in app or not.
                    // If true, call stock API else don't call. We set this property as false if qty is assigned
                    $bCallStockApi = true;
                    // Update today's pickup qty for each product
                    foreach ($arrProductsSellingPrice as $index => $arrProduct) {
                        $iQty = isset($arrInhandStock[$index]) ?
                            (float) round($arrInhandStock[$index], 2) : 0;
                        $arrProductsSellingPrice[$index]["pickup_qty"] = $iQty;

                        if ($bCallStockApi && $iQty > 0) {
                            $bCallStockApi = false;
                        }
                    }

                    $currentMonth = date("Y-m-") . "%";
                    $dateBefore30Days = date("Y-m-d", strtotime("-30 days"));

                    // Get MTD pickup stock qty of team
                    $arrMtdPickupStock = $this->tableUtil->getRowColumns(
                        "$dbName.tblstock_summary",
                        implode(", ", $arrPickupStockSumColumnNames),
                        "dstatus = 0 AND team_id = $teamId AND capture_date LIKE '$currentMonth' AND stock_type = 0"
                    );

                    // Update MTD pickup qty for each product
                    foreach ($arrProductsSellingPrice as $index => $arrProduct) {
                        $arrProductsSellingPrice[$index]["mtd_pickup_qty"] = isset($arrMtdPickupStock[$index]) ?
                            (float) $arrMtdPickupStock[$index] : 0;
                    }

                    // Get Sale products
                    $rsSaleProductsAction = null;
                    $iSaleProductsActionRows = 0;
                    $sSaleProductsQuery = "SELECT product_name, summary_column_name FROM" .
                        " $dbName.tblbranch_products WHERE branch_id = ? AND json_id = ? AND dstatus = 0" .
                        " ORDER BY sort_order";
                    $this->dbConn->ExecuteSelectQuery(
                        $sSaleProductsQuery,
                        $rsSaleProductsAction,
                        $iSaleProductsActionRows,
                        array($branchId, $jsonId)
                    );

                    $arrSaleProducts = array();
                    $arrSaleSumColumnNames = $arrSaleAvgColumnNames = array();
                    if ($iSaleProductsActionRows > 0) {
                        while ($rowSaleProduct = $this->dbConn->GetData($rsSaleProductsAction)) {
                            $arrSaleProducts[] = array(
                                "product_name" => $rowSaleProduct["product_name"],
                                "mtd_sale" => 0,
                                "avg_sale" => 0,
                            );
                            $arrSaleSumColumnNames[] = "SUM({$rowSaleProduct["summary_column_name"]})" .
                                " AS {$rowSaleProduct["summary_column_name"]}";
                            $arrSaleAvgColumnNames[] = "AVG({$rowSaleProduct["summary_column_name"]})" .
                                " AS {$rowSaleProduct["summary_column_name"]}";
                        }
                    }

                    // Get MTD Sale of team
                    $arrMtdSale = $this->tableUtil->getRowColumns(
                        "$dbName.$TBL_VANDS_SUMMARY",
                        implode(", ", $arrSaleSumColumnNames),
                        "dstatus = 0 AND team_id = $teamId AND activity_date LIKE '$currentMonth'"
                    );

                    // Update MTD sale for each product
                    foreach ($arrSaleProducts as $index => $arrProduct) {
                        $arrSaleProducts[$index]["mtd_sale"] = isset($arrMtdSale[$index]) ?
                            (float) $arrMtdSale[$index] : 0;
                    }

                    // Get Avg Sale in last 30 days of team
                    $arrAvgSale = $this->tableUtil->getRowColumns(
                        "$dbName.$TBL_VANDS_SUMMARY",
                        implode(", ", $arrSaleAvgColumnNames),
                        "dstatus = 0 AND team_id = $teamId AND activity_date > '$dateBefore30Days'"
                    );

                    // Update Avg Sale for each product
                    foreach ($arrSaleProducts as $index => $arrProduct) {
                        $arrSaleProducts[$index]["avg_sale"] = isset($arrAvgSale[$index]) ?
                            round($arrAvgSale[$index], 2) : 0;
                    }

                    // Get if stock is allocated or not
                    // $isStockAllocated = $commonFunctions->isNonEmptyArray($arrTodayPickupStock) ? 1 : 0;

                    $arrResponse = array(
                        "products_selling_price" => $arrProductsSellingPrice,
                        "products_sale" => $arrSaleProducts,
                        "is_stock_assigned" => 1, //$isStockAllocated,
                        "call_stock_api" => $bCallStockApi,
                    );

                    $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    $this->sendNoProductErrorMsg();
                }
            } elseif ($dbName == $ITCPH2_DB) {
                // ITC Phase 2
                $rsProductsAction = null;
                $iProductsActionRows = 0;
                $rsNetRateUpdate = null;
                $iNetRateRows = 0;
                // did this to show products in the WD app
                if ($jsonId == 101) {
                    $jsondata = file_get_contents("php://input");
                    $jsondata = json_decode($jsondata, true);
                    $wdCode = isset($_GET['wdlist']) && $_GET['wdlist'] !== 'null' ? $_GET['wdlist'] : null;
                }
                // Query to check if there is any record in tblwd_product_net_rate_update
                $sNetRateUpdateQuery = "SELECT product_name, net_rate FROM $dbName.tblwd_product_net_rate_update WHERE wd_code = ? AND dstatus = 0";
                $this->dbConn->ExecuteSelectQuery(
                    $sNetRateUpdateQuery,
                    $rsNetRateUpdate,
                    $iNetRateRows,
                    array($wdCode)  // Assuming $wdcode is the variable holding the wdcode value
                );
                // Prepare an array to store product net rates from tblwd_product_net_rate_update
                $arrUpdatedNetRates = array();
                if ($iNetRateRows > 0) {
                    while ($rowNetRateUpdate = $this->dbConn->GetData($rsNetRateUpdate)) {
                        $productName = $rowNetRateUpdate["product_name"];
                        $newNetRate = $rowNetRateUpdate["net_rate"];
                        // Store the updated net rates using product_id as the key
                        $arrUpdatedNetRates[$productName] = (string)$newNetRate;
                    }
                }

                $sProductsQuery = "SELECT category_name, product_name, is_focusbrand, net_rate, sort_order FROM $dbName.tblbranch_pickupstock_products WHERE branch_id = ? AND team_type = ? AND dstatus = 0 ORDER BY sort_order";
                $this->dbConn->ExecuteSelectQuery(
                    $sProductsQuery,
                    $rsProductsAction,
                    $iProductsActionRows,
                    array($branchId, $teamType)
                );

                // Products found
                if ($iProductsActionRows > 0) {
                    $arrProductsSellingPrice = array();
                    $arrCategories = array();
                    while ($rowProduct = $this->dbConn->GetData($rsProductsAction)) {
                        $categoryName = $rowProduct["category_name"];
                        $productName = $rowProduct["product_name"];
                        $focusBrand = (string)$rowProduct["is_focusbrand"];
                        $netRate = (string)$rowProduct["net_rate"];
                        $sortOrder = (string)$rowProduct["sort_order"];

                        // Check if there's an updated net rate for this product in tblwd_product_net_rate_update
                        if (isset($arrUpdatedNetRates[$productName])) {
                            $netRate = $arrUpdatedNetRates[$productName];  // Use updated net rate if available
                        }
                        if (!isset($arrCategories[$categoryName])) {
                            $arrCategories[$categoryName] = array(
                                "categoryName" => $categoryName,
                                "productList" => array()
                            );
                        }
                        if ($jsonId == 100 || $jsonId == 101) {
                            $baseRate = (string)$rowProduct["net_rate"];  // Original net_rate stored as base_rate
                            // Set net_rate to null if $wdCode is not provided
                            $netRateToSend = ($wdCode === null) ? "" : $netRate;
                            $arrCategories[$categoryName]["productList"][] = array(
                                "product_name" => $productName,
                                "is_focusbrand" => $focusBrand,
                                "net_rate" => $netRateToSend,
                                "base_rate" => $baseRate,  // Add base_rate only for jsonId 100
                                "sortorder" => $sortOrder,
                            );
                        } else {
                            $arrCategories[$categoryName]["productList"][] = array(
                                "product_name" => $productName,
                                "is_focusbrand" => $focusBrand,
                                "net_rate" => $netRate,
                                "sortorder" => $sortOrder,
                            );
                        }
                    }
                    foreach ($arrCategories as $category) {
                        $arrProductsSellingPrice[] = $category;
                    }

                    $arrResponse = array(
                        "products_selling_price" => $arrProductsSellingPrice,
                    );

                    $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    $this->sendNoProductErrorMsg();
                }
            } else {
                // API is not applicable
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST04"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        } else {
            // Branch not found
            $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST03"]));
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    private function sendNoProductErrorMsg()
    {
        // No product found
        $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST02"]));
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getStockData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData($dbName)) {
                $this->getProductsAndSellingPrice();
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new GetStockProductsSellingPrice($dbConn, $tableUtil, $commonFunctions);
$stock->getStockData();
$dbConn->Close();
