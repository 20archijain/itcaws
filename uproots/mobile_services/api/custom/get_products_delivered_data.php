<?php

// Used to get order and delivery data for products from TBL_ORDER_DETAILS table for ITCPH2

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetProductsDeliveredData extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_products_delivered_data";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getOrderAndDeliveryData()
    {
        global $ITCPH2_DB, $TBL_PROJECT_TEAM, $TBL_ORDER_DETAILS, $TBL_DELIVERY_DETAILS;

        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"] ?? 3;

        $jsonId = $this->requestGetData["json_id"] ?? 99;
        // $orderId = isset($this->requestGetData["order_id"]) ? $this->requestGetData["order_id"] : null;

        $branchId = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "branch_id",
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
            // ITCPH2
            if ($dbName == $ITCPH2_DB) {
                // Get product details from tblbranch_pickupstock_products
                $rsProductsAction = null;
                $iProductsActionRows = 0;
                $sProductsQuery = "SELECT rec_id, product_name, summary_column_name, category_name, sort_order" .
                    " FROM $dbName.tblbranch_pickupstock_products WHERE branch_id = ?" .
                    " AND json_id = ? AND team_type = ? AND dstatus = 0 ORDER BY sort_order";
                $this->dbConn->ExecuteSelectQuery(
                    $sProductsQuery,
                    $rsProductsAction,
                    $iProductsActionRows,
                    array($branchId, $jsonId, $teamType)
                );

                // Products found
                if ($iProductsActionRows > 0) {
                    $arrProducts = array();
                    $arrSummaryColumnNames = array();

                    while ($rowProduct = $this->dbConn->GetData($rsProductsAction)) {
                        $arrProducts[] = array(
                            // "product_id" => (string)$rowProduct["rec_id"],
                            "category_used_in_app" => $rowProduct["category_name"],
                            "sku_details" => $rowProduct["product_name"],
                            "sort_order" => $rowProduct["sort_order"],
                            "ordered_qty" => 0,
                            "delivered_qty" => 0,
                        );
                        $arrSummaryColumnNames[] = $rowProduct["summary_column_name"];
                    }

                    // Get order details from tblsurvey_response_details_orders
                    $sOrderQuery = "SELECT ques_3, pro_id, capture_datetime, order_status, " . implode(", ", $arrSummaryColumnNames) .
                        " FROM $dbName.$TBL_ORDER_DETAILS WHERE team_id = ? AND dstatus = 0";

                    $queryParams = array($teamId);
                    $sOrderQuery .= " ORDER BY capture_datetime DESC LIMIT 1";

                    $rsOrderDetails = null;
                    $iOrderDetailsRows = 0;
                    $this->dbConn->ExecuteSelectQuery(
                        $sOrderQuery,
                        $rsOrderDetails,
                        $iOrderDetailsRows,
                        $queryParams
                    );

                    // Order found
                    if ($iOrderDetailsRows > 0) {
                        $rowOrder = $this->dbConn->GetData($rsOrderDetails);
                        $orderIdValue = (int)$rowOrder["pro_id"];
                        $order_status = (int)$rowOrder["order_status"];
                        $shopId = (int)$rowOrder["ques_3"];
                        $orderDateFormatted = date("Y-m-d H:i", strtotime($rowOrder["capture_datetime"]));

                        // if delivery found
                        if ($order_status === 1) {
                            //Get delivery details from tblsurvey_response_details_delivery
                            $sDeliveryQuery = "SELECT pro_id, capture_datetime, " . implode(", ", $arrSummaryColumnNames) .
                                " FROM $dbName.$TBL_DELIVERY_DETAILS WHERE order_id = ? AND dstatus = 0";

                            $queryDeliveryParams = array($orderIdValue);
                            $sDeliveryQuery .= " ORDER BY capture_datetime DESC LIMIT 1";

                            $rsDeliveryDetails = null;
                            $iDeliveryDetailsRows = 0;
                            $this->dbConn->ExecuteSelectQuery(
                                $sDeliveryQuery,
                                $rsDeliveryDetails,
                                $iDeliveryDetailsRows,
                                $queryDeliveryParams
                            );

                            // if delivery found
                            if ($iDeliveryDetailsRows > 0) {
                                $rowDelivery = $this->dbConn->GetData($rsDeliveryDetails);
                                // $deliveryIdValue = (int)$rowDelivery["pro_id"];
                                // $deliveryShopId = (int)$rowDelivery["ques_3"];
                                // $deliveryDateFormatted = date("Y-m-d H:i", strtotime($rowDelivery["capture_datetime"]));
                            }
                        }

                        // Update ordered_qty & delivery_qty for each product from summary_column_name values
                        foreach ($arrProducts as $index => $arrProduct) {
                            $colName = $arrSummaryColumnNames[$index];
                            $orderedQty = isset($rowOrder[$colName]) ? (float)$rowOrder[$colName] : 0;
                            $arrProducts[$index]["ordered_qty"] = (int)$orderedQty;
                            $delivered_qty = isset($rowDelivery[$colName]) ? (float)$rowDelivery[$colName] : 0;
                            $arrProducts[$index]["delivered_qty"] = (int)$delivered_qty;
                        }

                        // Filter products to only include those with ordered_qty > 0
                        $arrFilteredProducts = array();
                        foreach ($arrProducts as $arrProduct) {
                            if ($arrProduct["ordered_qty"] > 0) {
                                $arrFilteredProducts[] = $arrProduct;
                            }
                        }


                        $arrResponse = array(
                            'listOfStages' => [
                                ["stage" => 0, "title" => "Ordered", "color" => "#007bff"],
                                ["stage" => 1, "title" => "Delivered", "color" => "#ff4000"],
                            ],
                            "orderId" => $orderIdValue,
                            "orderStatus" => $order_status,
                            "shopId" => $shopId,
                            "orderDate" => $orderDateFormatted,
                            "products" => $arrFilteredProducts,
                        );

                        $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
                        $this->logOutput($response, $this->sExtraLogData);
                    } else {
                        $this->sendNoOrderErrorMsg();
                    }
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

    private function sendNoOrderErrorMsg()
    {
        // No order found
        $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST02"]));
        $this->logOutput($response, $this->sExtraLogData);
    }

    private function sendNoProductErrorMsg()
    {
        // No product found
        $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST02"]));
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getDeliveredData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->getOrderAndDeliveryData();
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$orderData = new GetProductsDeliveredData($dbConn, $tableUtil, $commonFunctions);
$orderData->getDeliveredData();
$dbConn->Close();
