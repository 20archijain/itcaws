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

// phpcs:ignore
class ProcessShopCode
{
    private $dbConn = null;
    private $tables = [];
    //private $commonSettings = [];

    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
        $this->tables = $GLOBALS['TABLES'];
        //$this->commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
    }

    final public function processShopUniqueCode()
    {
        $routeTable = $this->tables["ROUTE_DETAILS_TABLE"];
        $query = "SELECT rec_id, route_name, outlet_name, outlet_mobile FROM $routeTable WHERE shop_uniq_code_alpha IS NULL LIMIT 300";
        $iRows = 0;
        $sAction = null;
        $this->dbConn->ExecuteSelectQuery($query, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arData = $this->dbConn->GetData($sAction)) {
                $recId = $arData["rec_id"];
                $routeName = $arData["route_name"];
                $outletName = $arData["outlet_name"];
                $outletMobile = $arData["outlet_mobile"];

                $shopUniqCodeAlpha = $this->generateShopUniqCodeAlpha($routeName, $outletName, $outletMobile);

                // Check if shop_uniq_code_alpha already exists
                $checkQuery = "SELECT shop_uniq_code FROM $routeTable WHERE shop_uniq_code_alpha = ?";
                $checkRows = 0;
                $checkAction = null;
                $this->dbConn->ExecuteSelectQuery($checkQuery, $checkAction, $checkRows, [$shopUniqCodeAlpha]);

                if ($checkRows > 0) {
                    $existingData = $this->dbConn->GetData($checkAction);
                    $existingShopUniqCode = $existingData['shop_uniq_code'];
                    $updateValues = "shop_uniq_code_alpha = ?, shop_uniq_code = ?";
                    $condition = "rec_id = ?";
                    updateRecord($this->dbConn, $routeTable, $updateValues, $condition, [$shopUniqCodeAlpha, $existingShopUniqCode, $recId]);
                } else {
                    $updateValues = "shop_uniq_code_alpha = ?, shop_uniq_code = ?";
                    $condition = "rec_id = ?";
                    updateRecord($this->dbConn, $routeTable, $updateValues, $condition, [$shopUniqCodeAlpha, $recId, $recId]);
                }
            }
            echo "Processed $iRows rows.\n";
        } else {
            echo "No rows to process.\n";
        }
    }

    private function generateShopUniqCodeAlpha($routeName, $outletName, $outletMobile)
    {
        return substr($routeName, -10) . $outletName . $outletMobile;
    }
}

$processResponse = new ProcessShopCode($dbConn);
$processResponse->processShopUniqueCode();
