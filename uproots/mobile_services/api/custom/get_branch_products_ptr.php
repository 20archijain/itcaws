<?php

// Used in Delhi to display branch products details

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class GetStockProductsSellingPrice extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_branch_products_ptr";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function getProducts()
    {
        global $DELHI_DB;

        $dbName = $this->arrUserDetails["db_name"];

        if ($dbName == $DELHI_DB) {
            $rsProductsAction = null;
            $iProductsActionRows = 0;
            $sProductsQuery = "SELECT product_name, mrp, ptr, net_ptr FROM" .
                " $dbName.tblbranch_product_ptr WHERE dstatus = 0";
            $this->dbConn->ExecuteSelectQuery(
                $sProductsQuery,
                $rsProductsAction,
                $iProductsActionRows
            );

            // Products found
            $arrProducts = array();
            if ($iProductsActionRows > 0) {
                while ($rowProduct = $this->dbConn->GetData($rsProductsAction)) {
                    $arrProducts[] = $rowProduct;
                }

                $arrResponse = array(
                    "products" => $arrProducts,
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
    }

    private function sendNoProductErrorMsg()
    {
        // No product found
        $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST02"]));
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getBranchProducts()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            $this->getProducts();
        }
    }
}

$products = new GetStockProductsSellingPrice($dbConn, $tableUtil, $commonFunctions);
$products->getBranchProducts();
$dbConn->Close();
