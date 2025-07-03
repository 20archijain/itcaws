<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class ActiveSKUReporting
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];


    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
    }

    //SEARCH CONDITION
    private function getCondition()
    {
        // filter query
        $searchCond = getFilterResult(
            $this->_data["searchbar"] ?? $this->_data,
            array(
                "branch" => array("a.branch_id", 0, true, true),
                "dsType" => array("a.team_type", 1),
            ),
            $this->_dbConn
        );

        return $searchCond;
    }

    // MASTER DATA
    final public function getViewSKUData()
    {
        $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $user_id = $this->_iUserId;
        $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        if ($groupId == 1 || $groupId == 2) {
            $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
            $branchFilter = true;
        } else {
            $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
            $branchFilter = true;
        }
        $arrResult = array(
            "branchFilter" => $branchFilter,
            // Don't use dstatus = 0
            "branchList" => $branchList,
            "dsTypeList" => getTeamType($this->_dbConn),
            "isSelectable" => false,
            "sortOptions" => array(
                array("label" => "Focus Brand", "value" => "a.is_focusbrand"),
                array("label" => "DS Type", "value" => "a.team_type"),
                array("label" => "SKU Name", "value" => "a.product_name"),
            ),
            "viewHeader" => array(
                "app.reporting.activeUSers.branch",
                "app.reporting.activeUSers.region",
                "app.reporting.activeUSers.dsType",
                "app.reporting.activeUSers.focusBrand",
                "app.reporting.activeUSers.skucategory",
                "app.reporting.activeUSers.skuName",
                "app.reporting.activeUSers.baseRate",
            ),
            "viewBody" => array(
                "region",
                "branchName",
                "dsType",
                "focusBrand",
                "category",
                "skuName",
                "prodRate",
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }
    //DS DETAILS
    final public function viewSKUData()
    {
        $searchCondition = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd", $this->_data["sort"]);


        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $focusType = array(0 => "No", 1 => "Yes");
        $sQuery = "SELECT a.branch_id, a.team_type, a.is_focusbrand, a.category_name,a.product_name,a.net_rate, a.rcd, b.branch_name,b.main_branch  FROM $branchPickupTable AS a, $branchTable AS b" .
            " WHERE a.dstatus = 0  AND a.branch_id = b.branch_id  $searchCondition $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                // $teamId = $arrData["branch_id"];
                $focusBrand = $arrData["is_focusbrand"];
                $dsType = $arrData["team_type"];
                $category = $arrData["category_name"];
                $skuName = $arrData["product_name"];
                $mainBranch = $arrData["main_branch"];
                $creationDate = date("Y-m-d", strtotime($arrData["rcd"]));
                $prodRate = $arrData["net_rate"];

                $arrResult[] = array(
                    "branchName" => $arrData["branch_name"],
                    "region" => $mainBranch,
                    "dsType" =>  $types[$dsType],
                    "focusBrand" => $focusType[$focusBrand],
                    "category" =>  $category,
                    "skuName" =>  $skuName,
                    "creationDate" => $creationDate,
                    "prodRate" => $prodRate,
                );
            }
        }

        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function downloadMasterData()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrExcelData = [];
        $arrExcelData[] = array("District", "Branch", "Region", "DS Type", "Focus Brand", "SKU Category", "SKU Name", "Base Rate (M)");
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $focusType = array(0 => "No", 1 => "Yes");
        $sQuery = "SELECT a.branch_id, a.team_type,a.is_focusbrand, a.category_name,a.product_name,a.net_rate, a.rcd, b.district,  b.branch_name,b.main_branch  FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0  AND a.branch_id = b.branch_id  $dwnCond $sOrderCond";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $focusBrand = $arrData["is_focusbrand"];
                $dsType = $arrData["team_type"];
                $district = $arrData["district"];
                $mainBranch = $arrData["main_branch"];
                $branchName = $arrData["branch_name"];
                $categoryName = $arrData["category_name"];
                $prodName = $arrData["product_name"];
                $prodRate = $arrData["net_rate"];

                $arrExcelData[] = array(
                    $district,
                    $mainBranch,
                    $branchName,
                    $types[$dsType],
                    $focusType[$focusBrand],
                    $categoryName,
                    $prodName,
                    $prodRate,
                );
            }
        }
        $currentDateTime = currentDateTime();
        $fileName = "Active_SKU_" . str_replace(":", "_", $currentDateTime) . ".xlsx";

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
        // } else {
        //     $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']), 0);
        // }
        echo json_encode($arrMessage);
    }
}
