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
            "focusTypeList" => array(
                array("label" => "No", "value" => "No"),
                array("label" => "Yes", "value" => "Yes"),
            ),
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
                "Incentive Brand",
                "app.reporting.activeUSers.skucategory",
                "app.reporting.activeUSers.skuName",
                "app.reporting.activeUSers.baseRate",
            ),
            "viewBody" => array(
                "region",
                "branchName",
                "dsType",
                "focusBrand",
                "incentiveBrand",
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
        $where = "";
        $branchCond = "";
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd", $this->_data["sort"]);
        // user has some specific permission
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
            $branchIds = getRowsColumn($this->_dbConn, $projectTeamTable, "branch_id", "dstatus = 0 $where", array(), true);
            if (isNonEmptyArray($branchIds)) {
                if (!is_array($branchIds)) {
                    $branchIds = array($branchIds);
                }
                $branchIds = "'" . implode("','", $branchIds) . "'";
                $branchCond .= " AND b.branch_id IN ($branchIds)";
            }
        }


        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $currentMonth = date('m'); // 01 to 12
        $currentYear  = date('Y'); // 4-digit year (e.g., 2026)
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $focusType = array(0 => "No", 1 => "Yes");
        $sQuery = "SELECT a.rec_id, a.branch_id, a.summary_column_name, a.team_type, a.is_focusbrand, a.category_name,a.product_name,a.net_rate, a.rcd, b.branch_name,b.main_branch  FROM $branchPickupTable AS a, $branchTable AS b" .
            " WHERE a.dstatus = 0  AND a.branch_id = b.branch_id $branchCond  $searchCondition $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $branchId = $arrData["branch_id"];
                $summary_column_name = $arrData["summary_column_name"];
                $incentive = isRecordExist($this->_dbConn, "tblbranch_products_month_wise", "rec_id", "branch_id = $branchId AND summary_column_name = '$summary_column_name' AND month = '$currentMonth' AND year = '$currentYear' AND dstatus = 0");
                if ($incentive == 1) {
                    $incentiveBrand = "Yes";
                } else {
                    $incentiveBrand = "No";
                }
                $focusBrand = $arrData["is_focusbrand"];
                $dsType = $arrData["team_type"];
                $category = $arrData["category_name"];
                $skuName = $arrData["product_name"];
                $mainBranch = $arrData["main_branch"];
                $creationDate = !empty($arrData["rcd"]) ? date("Y-m-d", strtotime($arrData["rcd"])) : null;
                $prodRate = $arrData["net_rate"];

                $arrResult[] = array(
                    "recId" => $arrData["rec_id"],
                    "branchName" => $arrData["branch_name"],
                    "region" => $mainBranch,
                    "dsType" =>  $types[$dsType],
                    "focusBrand" => $focusType[$focusBrand],
                    "incentiveBrand" => $incentiveBrand,
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

    //CSV Report
    final public function downloadMasterData()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
         $currentMonth = date('m'); // 01 to 12
        $currentYear  = date('Y'); // 4-digit year (e.g., 2026)

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        // Create header
        $header = [];
        $header[] = [
            "District",
            "Branch",
            "Region",
            "DS Type",
            "Focus Brand",
            "Incentive Brand",
            "SKU Category",
            "SKU Name",
            "Base Rate (M)"
        ];

        $arrDataHolder = [];
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $focusType = array(0 => "No", 1 => "Yes");

        $sQuery = "SELECT a.branch_id, a.summary_column_name, a.team_type, a.is_focusbrand, a.category_name, a.product_name, a.net_rate, a.rcd, b.district, b.branch_name, b.main_branch FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND a.branch_id = b.branch_id $dwnCond $sOrderCond";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $branchId = $arrData["branch_id"];
                $summary_column_name = $arrData["summary_column_name"];
                $incentive = isRecordExist($this->_dbConn, "tblbranch_products_month_wise", "rec_id", "branch_id = $branchId AND summary_column_name = '$summary_column_name' AND month = '$currentMonth' AND year = '$currentYear' AND dstatus = 0");
                if ($incentive == 1) {
                    $incentiveBrand = "Yes";
                } else {
                    $incentiveBrand = "No";
                }
                $focusBrand = $arrData["is_focusbrand"];
                $dsType = $arrData["team_type"];
                $district = $arrData["district"];
                $mainBranch = $arrData["main_branch"];
                $branchName = $arrData["branch_name"];
                $categoryName = $arrData["category_name"];
                $prodName = $arrData["product_name"];
                $prodRate = $arrData["net_rate"];

                $arrDataHolder[] = [
                    $district,
                    $mainBranch,
                    $branchName,
                    $types[$dsType],
                    $focusType[$focusBrand],
                    $incentiveBrand,
                    $categoryName,
                    $prodName,
                    $prodRate,
                ];
            }
        }
        $currentDateTime = currentDateTime();
        $fileName = "Active_SKU_" . str_replace(":", "_", $currentDateTime) . ".csv";
        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        $fp = fopen($filename, 'w');

        if ($fp === false) {
            $arrMessage = responseMessage(array("Failed to create CSV file"), 0);
            echo json_encode($arrMessage);
            return;
        }

        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($header as $headerRow) {
            $cleanRow = array_map('cleanCSVValue', $headerRow);
            fputs($fp, implode(",", $cleanRow) . "\n");
        }

        foreach ($arrDataHolder as $row) {
            $cleanRow = array_map('cleanCSVValue', $row);
            fputs($fp, implode(",", $cleanRow) . "\n");
        }

        fclose($fp);

        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );

        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        echo json_encode($arrMessage);
    }

    final public function editData()
    {
        $focusBrand = getFormData($this->_data, "focusBrand");
        $recId = getFormData($this->_data, "recId");

        if ($focusBrand == "Yes") {
            $isFocus = "1";
        } else {
            $isFocus = "0";
        }
        $cols = "is_focusbrand = ?";
        $arrParams = array($isFocus, $recId);

        $iStatus = updateRecord($this->_dbConn, "tblbranch_pickupstock_products", $cols, " rec_id = ?", $arrParams);

        if ($iStatus === 1) {
            $arrMessage = responseMessage(array($GLOBALS['DATA_UPDATED_SUCCESSFULL']), 1);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_UPDATED']));
        }
        echo json_encode($arrMessage);
    }
}
