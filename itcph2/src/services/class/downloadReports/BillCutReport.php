<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class BillCutReport
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_projectId = 1;
    private $_arrAccessInfo = [];
    private $arrBranchwiseProducts = [];


    public function __construct($dbConn, $data, $arrAccessInfo)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
    }

    private function getCondition()
    {
        $arrSearchParams = array(
            "dateFrom" => array("capture_date", 4, "dateTo", true),
        );

        // filter query
        $where = getFilterResult(
            $this->_data,
            $arrSearchParams,
            $this->_dbConn
        );

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        return $where;
    }

    final public function getData()
    {
        $arrResult = array(
            // Don't use dstatus = 0
            "branchList" => getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch"),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getProducts()
    {
        $arrResult = array(
            "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"], $this->_data["type"]),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getBranchTeamTypeList()
    {
        if ($this->_data["branch"]) {
            $arrResult = array(
                "teamType" => getTeamType($this->_dbConn, $this->_data["branch"]),
                "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"]),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "productList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    public function getDownloadData()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // Filter query
        $where = $this->getCondition();
        $branch = getFormData($this->_data, "branch");
        $product = getFormData($this->_data, "product");
        $teamType = getFormData($this->_data, "teamType");

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $Cond = "";
        $teamTypeCond = "";
        if ($teamType) {
            $teamTypeCond .= " AND team_type = $teamType";
            $Cond .= " AND b.is_type = $teamType";
        }

        $productCond = "";
        if ($product) {
            if (isNonEmptyArray($product)) {
                $products = "'" .  implode("','", $product)  . "'";
                $productCond = " AND product_name IN ($products)";
            } else {
                $productCond = " AND product_name = '$product'";
            }
        }

        $arrExcelData = [];
        $arrExcelData[] = ["District", "Branch", "Region", "Circle", "Section", "WD Code", "WD Name", "WD Pop Group", "DS Type", "DS Id", "DS Name", "Brand Family", "Variant", "Focus Variant", "Total Outlets Mapped", "Variant UOB", "Variant UOB%", 'Overall UOB'];

        $branchCond = "";
        // if ($branch) {
        //     $matchAll = checkIfAllSelected($branch);
        //     if (!$matchAll) {
        //         if (isNonEmptyArray($branch)) {
        //             $branchs = implode(",", $branch);
        //             $branchCond = " AND branch_id IN ($branchs)";
        //             $Cond .= " AND b.branch_id IN ($branchs)";
        //         } else {
        //             $branchCond = " AND branch_id = $branch";
        //             $Cond .= " AND b.branch_id = $branch";
        //         }
        //     }
        // }

        foreach ($branch as $branchId) {
            $sProductQuery = "SELECT DISTINCT rec_id, product_name, summary_column_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId $productCond $teamTypeCond ORDER BY product_name";
            $sProductAction = null;
            $iProductRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                $summaryColName = [];
                $productNames = [];
                $productFamilies = []; // key = summary_column_name
                $productFocuses = [];  // key = summary_column_name
                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $recId = $rowProduct["rec_id"];
                    $summaryCol = $rowProduct["summary_column_name"];
                    $summaryColName[] = $rowProduct["summary_column_name"];
                    $productNames[] = $rowProduct["product_name"];
                    $arrProductDetails = getRowColumns($this->_dbConn, "$branchProductsTable", "category_name, is_focusbrand", "dstatus = 0 AND rec_id = $recId");
                    $productFamilies[$summaryCol] = $arrProductDetails[0];
                    $productFocuses[$summaryCol] = $arrProductDetails[1];
                }

                $sProductSaleColumns = implode(",", $summaryColName);

                $isType = array(0 => "Van DS", 1 => "Niches", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR", 6 => "RMD");
                $rsAction = null;
                $iRows = 0;
                $sQuery = "SELECT a.capture_datetime, a.ques_0, b.team_id, b.team_name, b.is_type, b.wd_code, c.district, c.branch_name, c.main_branch, $sProductSaleColumns FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id" .
                    " AND b.branch_id = c.branch_id AND ques_0 IN ('Outlet Order','Add Outlet') $where AND b.branch_id = $branchId GROUP BY a.team_id ORDER BY capture_datetime DESC";
                $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

                if ($iRows > 0) {
                    $shopCount = [];
                    $totalShopCount = [];
                    $OverallShopCount = [];
                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        $mainBranchName = $row['main_branch'];
                        $district = $row['district'];
                        $branchName = $row['branch_name'];
                        $teamId = $row['team_id'];
                        $teamName = $row['team_name'];
                        $teamType = $isType[$row['is_type']];
                        $wdCode = $row['wd_code'];
                        $arrDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "circle,section,wd_firm_name,wd_pop_group", "dstatus = 0 AND wd_code = '$wdCode'");
                        $circle     = isset($arrDetails[0]) ? $arrDetails[0] : "";
                        $section    = isset($arrDetails[1]) ? $arrDetails[1] : "";
                        $wdFirmName = isset($arrDetails[2]) ? $arrDetails[2] : "";
                        $wdpopGroup = isset($arrDetails[3]) ? $arrDetails[3] : "";
                        foreach ($summaryColName as $index => $colName) {
                            $allShops = getRowColumn($this->_dbConn, "$respTable AS a", "COUNT(DISTINCT a.ques_3) AS total", "a.dstatus = 0 AND a.$colName > 0 AND a.team_id = $teamId $where");
                            if (!isset($shopCount[$district][$mainBranchName][$branchName][$circle][$section][$wdFirmName][$wdpopGroup][$teamId][$teamName][$wdCode][$teamType][$colName])) {
                                $shopCount[$district][$mainBranchName][$branchName][$circle][$section][$wdFirmName][$wdpopGroup][$teamId][$teamName][$wdCode][$teamType][$colName] = 0;
                            }
                            $shopCount[$district][$mainBranchName][$branchName][$circle][$section][$wdFirmName][$wdpopGroup][$teamId][$teamName][$wdCode][$teamType][$colName] = $allShops;
                        }

                        $totalShopCount[$teamId] = getRowColumn($this->_dbConn, $routeTable, "COUNT(outlet_name) AS total", "dstatus = 0 AND team_id = $teamId", array(), true);

                        // Array of column names for total sales
                        $totalSaleColumns = [];
                        for ($i = 1; $i <= 78; $i++) {
                            $totalSaleColumns[] = "`total_sale_product$i`";
                        }

                        // Join the columns with '+' for summation
                        $totalSaleSum = implode(" + ", $totalSaleColumns);

                        $overallshop = getRowColumn(
                            $this->_dbConn,
                            "$respTable AS a",
                            "COUNT(DISTINCT a.ques_3) AS total",
                            "a.dstatus = 0 AND a.team_id = $teamId $where AND ($totalSaleSum) > 0"
                        );

                        $OverallShopCount[$teamId] = $overallshop;
                    }

                    foreach ($shopCount as $district => $arrDsitrict) {
                        foreach ($arrDsitrict as $mainBranchName => $arrbranchData) {
                            foreach ($arrbranchData as $branchName => $arrbranch) {
                                foreach ($arrbranch as $circle => $arrcircle) {
                                    foreach ($arrcircle as $section => $arrSection) {
                                        foreach ($arrSection as $wdFirmName => $arrWdFirmName) {
                                            foreach ($arrWdFirmName as $wdpopGroup => $arrWdpopGroup) {
                                                foreach ($arrWdpopGroup as $teamId => $arrteams) {
                                                    foreach ($arrteams as $teamName => $arrwdCode) {
                                                        foreach ($arrwdCode as $wdCode => $arrTeamType) {
                                                            foreach ($arrTeamType as $teamType => $arrProduct) {
                                                                foreach ($arrProduct as $colName => $shops) {
                                                                    $distinctShops = $shops;
                                                                    $totalShops = $totalShopCount[$teamId];
                                                                    $overallUob = $OverallShopCount[$teamId];
                                                                    // Calculate UOB%
                                                                    $uobPercentage = $totalShops > 0 ? $distinctShops / $totalShops : 0;
                                                                    $arrExcelData[] = [
                                                                        'District' => $district,
                                                                        'Branch' => $mainBranchName,
                                                                        'Region' => $branchName,
                                                                        'Circle' => $circle,
                                                                        'Section' => $section,
                                                                        'WD Code' => $wdCode,
                                                                        'WD Name' => $wdFirmName,
                                                                        'WD Pop Group' => $wdpopGroup,
                                                                        'DS Type' => $teamType,
                                                                        'DS Id' => $teamId,
                                                                        'DS Name' => $teamName,
                                                                        'Brand Family' => $productFamilies[$colName] ?? '',
                                                                        'Variant' => $productNames[array_search($colName, $summaryColName)],
                                                                        'Focus Variant' => (string) ($productFocuses[$colName] ?? ''),
                                                                        'Total Outlets Mapped' => $totalShops,
                                                                        'Variant UOB' => $distinctShops,
                                                                        'Variant UOB%' => number_format($uobPercentage, 2),
                                                                        'Overall UOB' => $overallUob,
                                                                    ];
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $fileName = "Bill_Cut_Report_$currentDateTime.xlsx";
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

        echo json_encode($arrMessage);
    }
}
