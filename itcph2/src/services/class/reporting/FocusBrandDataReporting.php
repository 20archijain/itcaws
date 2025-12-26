<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class FocusBrandDataReporting
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
            "reportTypeList" => array(
                array(
                    "label" => "District-wise",
                    "value" => "1",
                ),
                array(
                    "label" => "Region-wise",
                    "value" => "2",
                ),
                array(
                    "label" => "Branch-wise",
                    "value" => "3",
                ),
            ),
            "brandTypeList" => array(
                array(
                    "label" => "Focus Brands",
                    "value" => "1",
                ),
                array(
                    "label" => "OverAll",
                    "value" => "2",
                ),
            ),
            "dsTypeList" => getTeamType($this->_dbConn),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


    final public function downloadMasterData()
    {
        $reportType =  $this->_data["searchbar"]['reportType'] ?? $this->_data['reportType'];

        if ($reportType == 1) {
            $this->downloadMasterDataDistrict();
        } elseif($reportType == 2) {
            $this->downloadMasterDataRegion();
        } else {
            $this->downloadMasterDataBranch();
        }
    }

    final public function downloadMasterDataDistrict()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $brandType = $this->_data['brandType'];
        if($brandType == 1)
        {
            $brandCond = ' AND a.is_focusbrand = 1';
            $excelHeader = "Focus Brand";
        }else
        {
            $brandCond = '';
            $excelHeader = "Variant";
        }

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        $arrExcelHeaderCheck = array();
        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrExcelData = [];
        $arrExcelData[] = array("District", "DS Type");
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $sQuery = "SELECT distinct a.team_type, a.product_name, b.district FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.branch_id = b.branch_id $brandCond $dwnCond $sOrderCond";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $district = $arrData["district"];
                $dsType = $arrData["team_type"];
                $prodName = $arrData["product_name"];
                $arrBody[$district][$dsType][] = $prodName;
            }
        }
        foreach ($arrBody as $exDistrict => $arrDistrict) {
            foreach ($arrDistrict as $exDsType => $arrDsType) {
                $arrExcelHeaderCheck[] = $arrDsType;
                $localArr = array($exDistrict, $types[$exDsType]);
                $arrExcelData[] = array_merge($localArr, $arrDsType);
            }
        }

        $max = 0;
        foreach ($arrExcelHeaderCheck as $item) {
            $count = count($item);
            if ($count > $max) {
                $max = $count;
            }
        }
        for ($i = 1; $i <= $max; $i++) {
            $arrExcelData[0][] = $excelHeader." " . $i;
        }

        // echo $max;die;
        $currentDateTime = currentDateTime();
        $fileName = $excelHeader."_District_Wise_" . str_replace(":", "_", $currentDateTime) . ".xlsx";

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

    final public function downloadMasterDataRegion()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $brandType = $this->_data['brandType'];
        if($brandType == 1)
        {
            $brandCond = ' AND a.is_focusbrand = 1';
            $excelHeader = "Focus Brand";
        }else
        {
            $brandCond = '';
            $excelHeader = "Variant";
        }

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        $arrExcelHeaderCheck = array();
        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrExcelData = [];
        $arrExcelData[] = array("District", "Branch", "Region", "DS Type");
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $sQuery = "SELECT distinct a.team_type, a.product_name, b.district,  b.branch_name, b.main_branch FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND b.dstatus = 0  AND a.branch_id = b.branch_id $brandCond $dwnCond $sOrderCond";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $district = $arrData["district"];
                $dsType = $arrData["team_type"];
                $mainBranch = $arrData["main_branch"];
                $branchName = $arrData["branch_name"];
                $prodName = $arrData["product_name"];
                $arrBody[$district][$mainBranch][$branchName][$dsType][] = $prodName;
            }
        }

        foreach ($arrBody as $exDistrict => $arrDistrict) {
            foreach ($arrDistrict as $exMainBranch => $arrMainBranch) {
                foreach ($arrMainBranch as $exBranchName => $arrBranchName) {
                    foreach ($arrBranchName as $exDsType => $arrDsType) {
                        $arrExcelHeaderCheck[] = $arrDsType;
                        $localArr = array($exDistrict, $exMainBranch, $exBranchName, $types[$exDsType]);
                        $arrExcelData[] = array_merge($localArr, $arrDsType);
                    }
                }
            }
        }

        $max = 0;
        foreach ($arrExcelHeaderCheck as $item) {
            $count = count($item);
            if ($count > $max) {
                $max = $count;
            }
        }
        for ($i = 1; $i <= $max; $i++) {
            $arrExcelData[0][] = $excelHeader." " . $i;
        }

        // echo $max;die;
        $currentDateTime = currentDateTime();
        $fileName = $excelHeader."_Region_Wise_" . str_replace(":", "_", $currentDateTime) . ".xlsx";

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


    final public function downloadMasterDataBranch()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $brandType = $this->_data['brandType'];
        if($brandType == 1)
        {
            $brandCond = ' AND a.is_focusbrand = 1';
            $excelHeader = "Focus Brand";
        }else
        {
            $brandCond = '';
            $excelHeader = "Variant";
        }

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        $arrExcelHeaderCheck = array();
        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrExcelData = [];
        $arrExcelData[] = array("District", "Branch", "DS Type");
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        $types = array(0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR");
        $sQuery = "SELECT distinct a.team_type, a.product_name, b.district, b.main_branch FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.branch_id = b.branch_id $brandCond $dwnCond $sOrderCond";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $district = $arrData["district"];
                $dsType = $arrData["team_type"];
                $mainBranch = $arrData["main_branch"];
                $prodName = $arrData["product_name"];
                $arrBody[$district][$mainBranch][$dsType][] = $prodName;
            }
        }

        foreach ($arrBody as $exDistrict => $arrDistrict) {
            foreach ($arrDistrict as $exMainBranch => $arrMainBranch) {
                foreach ($arrMainBranch as $exDsType => $arrDsType) {
                        $arrExcelHeaderCheck[] = $arrDsType;
                        $localArr = array($exDistrict, $exMainBranch, $types[$exDsType]);
                        $arrExcelData[] = array_merge($localArr, $arrDsType);
                    }
                }
            }

        $max = 0;
        foreach ($arrExcelHeaderCheck as $item) {
            $count = count($item);
            if ($count > $max) {
                $max = $count;
            }
        }
        for ($i = 1; $i <= $max; $i++) {
            $arrExcelData[0][] = $excelHeader." " . $i;
        }

        // echo $max;die;
        $currentDateTime = currentDateTime();
        $fileName = $excelHeader."_Branch_Wise_" . str_replace(":", "_", $currentDateTime) . ".xlsx";

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
