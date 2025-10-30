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

        // $teamList = $this->_arrAccessInfo["user_teams"];
        // if ($teamList) {
        //     $where .= " AND a.team_id IN $teamList";
        // }

        return $where;
    }

    final public function getConditionFilter($summary = false, $andCondition = true)
    {
        $teamTable = $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'];
        $branchTable = $GLOBALS['TABLES']['BRANCH_TABLE'];
        $mappingTable = $GLOBALS['TABLES']['WD_MAPPING_TABLE'];
        $condition = "";
        $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
        if ($district) {
            $matchAll = checkIfAllSelected($district);
            if (!$matchAll) {
                if (isNonEmptyArray($district)) {
                    $districts = "'" . implode("','", $district) . "'";
                    $condition .= " AND d.district IN ($districts)";
                } else {
                    $condition .= " AND d.district = $district";
                }
            }
        }
        $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchIds = "'" . implode("','", $branch) . "'";
                    $condition .= " AND d.branch_id IN ($branchIds)";
                } else {
                    $condition .= " AND d.branch_id = $branch";
                }
            }
        }
        $circle = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "circle");
        if ($circle) {
            $matchAll = checkIfAllSelected($circle);
            if (!$matchAll) {
                if (isNonEmptyArray($circle)) {
                    $circleIds = "'" . implode("','", $circle) . "'";
                    $condition .= " AND b.circle IN ($circleIds)";
                } else {
                    $condition .= " AND b.circle = '$circle'";
                }
            }
        }
        $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $sectionIds = "'" . implode("','", $section) . "'";
                    $condition .= " AND b.section IN ($sectionIds)";
                } else {
                    $condition .= " AND b.section = '$section'";
                }
            }
        }
        $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCodeIds = "'" . implode("','", $wdCode) . "'";
                    $condition .= " AND b.wd_code IN ($wdCodeIds)";
                } else {
                    $condition .= " AND b.wd_code = '$wdCode'";
                }
            }
        }
        $wdMarket = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdMarket");
        if ($wdMarket) {
            if (!is_array($wdMarket)) {
                $wdMarket = array($wdMarket);
            }
            if (in_array('all', $wdMarket)) {
                $condition .= " ";
            } else {
                $wdMarket = "'" . implode("','", $wdMarket) . "'";
                $condition .= " AND e.wd_market IN ($wdMarket)";
            }
        }
        $wdPopGroup = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdPopGroup");
        if ($wdPopGroup) {
            if (!is_array($wdPopGroup)) {
                $wdPopGroup = array($wdPopGroup);
            }
            if (in_array('all', $wdPopGroup)) {
                $condition .= " ";
            } else {
                $wdPopGroup = "'" . implode("','", $wdPopGroup) . "'";
                $condition .= " AND e.wd_pop_group IN ($wdPopGroup)";
            }
        }
        $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
        if (isset($teamType) && $teamType != "" && isNonEmptyArray($teamType) && $teamType >= 0) {
            $matchAll = checkIfAllSelected($teamType);
            if (!$matchAll) {
                if (isNonEmptyArray($teamType)) {
                    $teamTypes = "'" . implode("','", $teamType) . "'";
                    $condition .= " AND b.is_type IN ($teamTypes)";
                } else {
                    $condition .= " AND b.is_type = $teamType";
                }
            }
        } else {
            // No team type filter → include all team types
            $condition = " AND b.is_type IN (0,5)"; // or remove condition entirely
        }

        $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $condition .= " AND b.team_id IN ($dsNames)";
                } else {
                    $condition .= " AND b.team_id = $dsName";
                }
            }
        }

        $where = "";
        if ($condition && $andCondition) {
            $where .= " AND b.team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND e.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
        } elseif ($condition) {
            $where .= " AND b.team_id IN (SELECT b.team_id FROM $teamTable as b, $branchTable as d, $mappingTable as e WHERE b.dstatus = '0' AND d.dstatus = '0' AND e.dstatus = '0' AND b.branch_id = d.branch_id AND b.wd_code = e.wd_code $condition)";
        }

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        return $where;
    }



    final public function getData()
    {
        $arrResult = array(
            // Don't use dstatus = 0
            "districtList" => $this->getDistrictList(),
            "branchList" => $this->getBranchList(),
            "circleList" => $this->getCircleList(),
            "sectionList" => $this->getSectionList(),
            "wdCodeList" => $this->getWdCodeList(),
            "teamType" => $this->getDsTypeList(),
            "teamList" => $this->getTeamsList(),
            "wdMarketList" => $this->getWdMarketList(),
            "wdPopGroupList" => $this->getWdPopGroupList(),
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

        $where = $this->getCondition();
        $whereFilter = $this->getConditionFilter();
        $branch = getFormData($this->_data, "branch");
        $product = getFormData($this->_data, "product");
        $teamTypeFilter = getFormData($this->_data, "dsType");

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        $Cond = "";
        $teamTypeCond = "";

        // ✅ FIX: Handle single or both team types properly
        if (isset($teamTypeFilter) && $teamTypeFilter !== "" && $teamTypeFilter >= 0) {
            $teamTypeCond = " AND team_type = $teamTypeFilter";
            $Cond .= " AND b.is_type = $teamTypeFilter";
            $teamTypes = [$teamTypeFilter];
        } else {
            // ✅ No filter selected → include both 0 and 5 team types
            $teamTypeCond = " AND team_type IN (0,5)";
            $teamTypes = [0, 5];
        }

        $productCond = "";
        if ($product) {
            if (isNonEmptyArray($product)) {
                $products = "'" . implode("','", $product) . "'";
                $productCond = " AND product_name IN ($products)";
            } else {
                $productCond = " AND product_name = '$product'";
            }
        }

        $arrExcelData = [];
        $arrExcelData[] = ["District", "Branch", "Region", "Circle", "Section", "WD Code", "WD Name", "WD Pop Group", "DS Type", "DS Id", "DS Name", "Brand Family", "Variant", "Focus Variant", "Total Outlets Mapped", "Variant UOB", "Variant UOB%", 'Overall UOB'];

        foreach ($branch as $branchId) {
            // ✅ Fetch all products for both team types for this branch
            $sProductQuery = "SELECT DISTINCT rec_id, product_name, summary_column_name, team_type FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId $productCond $teamTypeCond ORDER BY team_type, product_name";

            $sProductAction = null;
            $iProductRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            // Group products by team_type
            $branchProductsByTeamType = [];
            $productNamesByTeamType = [];
            $productFamiliesByTeamType = [];
            $productFocusByTeamType = [];

            if ($iProductRows > 0) {
                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $recId = $rowProduct["rec_id"];
                    $summaryCol = $rowProduct["summary_column_name"];
                    $teamType = (int)$rowProduct["team_type"];

                    if (!isset($branchProductsByTeamType[$teamType])) {
                        $branchProductsByTeamType[$teamType] = [];
                        $productNamesByTeamType[$teamType] = [];
                        $productFamiliesByTeamType[$teamType] = [];
                        $productFocusByTeamType[$teamType] = [];
                    }

                    $branchProductsByTeamType[$teamType][] = $summaryCol;
                    $productNamesByTeamType[$teamType][] = $rowProduct["product_name"];

                    $arrProductDetails = getRowColumns($this->_dbConn, $branchProductsTable, "category_name, is_focusbrand", "dstatus = 0 AND rec_id = $recId");

                    $productFamiliesByTeamType[$teamType][$summaryCol] = $arrProductDetails[0];
                    $productFocusByTeamType[$teamType][$summaryCol] = $arrProductDetails[1];
                }
            }

            // ✅ Get DS details
            $isType = [0 => "Van DS", 1 => "Niches", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR", 6 => "RMD"];
            $rsAction = null;
            $iRows = 0;

            $sQuery = "SELECT a.capture_datetime, a.ques_0, b.team_id, b.team_name, b.is_type, b.wd_code, c.district, c.branch_name, c.main_branch FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.dstatus = 0 AND a.team_id = b.team_id AND b.branch_id = c.branch_id  AND a.ques_0 IN ('Outlet Order','Add Outlet') $where $whereFilter AND b.branch_id = $branchId $Cond GROUP BY a.team_id ORDER BY a.capture_datetime DESC ";

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
                    $teamType = (int)$row['is_type'];
                    $teamTypeName = $isType[$teamType] ?? '';
                    $wdCode = $row['wd_code'];

                    $arrDetails = getRowColumns($this->_dbConn, "tblmapping_wd", "circle,section,wd_firm_name,wd_pop_group", "dstatus = 0 AND wd_code = '$wdCode'");
                    $circle = $arrDetails[0] ?? "";
                    $section = $arrDetails[1] ?? "";
                    $wdFirmName = $arrDetails[2] ?? "";
                    $wdpopGroup = $arrDetails[3] ?? "";

                    // ✅ Use only products belonging to the team’s type
                    $teamProducts = $branchProductsByTeamType[$teamType] ?? [];

                    foreach ($teamProducts as $colName) {
                        $allShops = getRowColumn($this->_dbConn, "$respTable AS a", "COUNT(DISTINCT a.ques_3) AS total", "a.dstatus = 0 AND a.$colName > 0 AND a.team_id = $teamId $where");
                        $shopCount[$district][$mainBranchName][$branchName][$circle][$section][$wdFirmName][$wdpopGroup][$teamId][$teamName][$wdCode][$teamType][$colName] = $allShops;
                    }

                    $totalShopCount[$teamId] = getRowColumn($this->_dbConn, $routeTable, "COUNT(outlet_name) AS total", "dstatus = 0 AND team_id = $teamId", [], true);

                    $totalSaleColumns = [];
                    for ($i = 1; $i <= 78; $i++) {
                        $totalSaleColumns[] = "`total_sale_product$i`";
                    }

                    $totalSaleSum = implode(" + ", $totalSaleColumns);

                    $overallshop = getRowColumn($this->_dbConn, "$respTable AS a", "COUNT(DISTINCT a.ques_3) AS total", "a.dstatus = 0 AND a.team_id = $teamId $where AND ($totalSaleSum) > 0");

                    $OverallShopCount[$teamId] = $overallshop;
                }

                // ✅ Generate Excel rows
                foreach ($shopCount as $district => $arrDistrict) {
                    foreach ($arrDistrict as $mainBranchName => $arrBranchData) {
                        foreach ($arrBranchData as $branchName => $arrBranch) {
                            foreach ($arrBranch as $circle => $arrCircle) {
                                foreach ($arrCircle as $section => $arrSection) {
                                    foreach ($arrSection as $wdFirmName => $arrWdFirmName) {
                                        foreach ($arrWdFirmName as $wdpopGroup => $arrWdPopGroup) {
                                            foreach ($arrWdPopGroup as $teamId => $arrTeams) {
                                                foreach ($arrTeams as $teamName => $arrWdCode) {
                                                    foreach ($arrWdCode as $wdCode => $arrTeamType) {
                                                        foreach ($arrTeamType as $teamType => $arrProduct) {
                                                            foreach ($arrProduct as $colName => $shops) {
                                                                $distinctShops = $shops;
                                                                $totalShops = $totalShopCount[$teamId];
                                                                $overallUob = $OverallShopCount[$teamId];
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
                                                                    'DS Type' => $isType[$teamType] ?? '',
                                                                    'DS Id' => $teamId,
                                                                    'DS Name' => $teamName,
                                                                    'Brand Family' => $productFamiliesByTeamType[$teamType][$colName] ?? '',
                                                                    'Variant' => $productNamesByTeamType[$teamType][array_search($colName, $branchProductsByTeamType[$teamType])],
                                                                    'Focus Variant' => (string)($productFocusByTeamType[$teamType][$colName] ?? ''),
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

        // ✅ Excel output
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

    final public function getBranch($district = "district")
    {
        $district = $this->_data['district'];
        $districtCond = "";
        if (!empty($district)) {
            if (!is_array($district)) {
                $district = array($district);
            }
            if (in_array('all', $district)) {
                $districtCond = ""; // No condition for 'all'
            } else {
                $district = "'" . implode("','", $district) . "'";
                $districtCond = " AND a.district IN ($district)";
            }

            $arrResult = array(
                "branchList" => $this->getBranchList($districtCond),
                "circleList" => $this->getCircleList($districtCond),
                "sectionList" => $this->getSectionList($districtCond),
                "wdCodeList" => $this->getWdCodeList($districtCond),
                "teamType" => $this->getDsTypeList($districtCond),
                "teamList" => $this->getTeamsList($districtCond),
                "wdMarketList" => $this->getWdMarketList($districtCond),
                "wdPopGroupList" => $this->getWdPopGroupList($districtCond),
            );
        } else {
            $arrResult = array(
                "branchList" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCircle($branch = "branch_id")
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if ($branch) {
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            if (in_array('all', $branch)) {
                $branchCond = ""; // No condition for 'all'
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $branchCond = " AND a.branch_id IN ($branch)";
            }

            $arrResult = array(
                "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"]),
                "circleList" => $this->getCircleList($branchCond),
                "sectionList" => $this->getSectionList($branchCond),
                "wdCodeList" => $this->getWdCodeList($branchCond),
                "teamType" => $this->getDsTypeList($branchCond),
                "teamList" => $this->getTeamsList($branchCond),
                "wdMarketList" => $this->getWdMarketList($branchCond),
                "wdPopGroupList" => $this->getWdPopGroupList($branchCond),
            );
        } else {
            $arrResult = array(
                "productList" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamType" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection($circle = "circle")
    {
        $circle = $this->_data['circle'];
        $circleCond = "";
        if ($circle) {
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND b.circle IN ($circle)";
                }
            }
            $arrResult = array(
                "sectionList" => $this->getSectionList($circleCond),
                "wdCodeList" => $this->getWdCodeList($circleCond),
                "teamType" => $this->getDsTypeList($circleCond),
                "teamList" => $this->getTeamsList($circleCond),
                "wdMarketList" => $this->getWdMarketList($circleCond),
                "wdPopGroupList" => $this->getWdPopGroupList($circleCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getWDCode($section = "section")
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if ($section) {
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND b.section IN ($section)";
                }
            }

            $arrResult = array(
                "wdCodeList" => $this->getWdCodeList($sectionCond),
                "teamType" => $this->getDsTypeList($sectionCond),
                "teamList" => $this->getTeamsList($sectionCond),
                "wdMarketList" => $this->getWdMarketList($sectionCond),
                "wdPopGroupList" => $this->getWdPopGroupList($sectionCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "wdCodeList" => "",
                "teamList" => "",
                "wdMarketList" => "",
                "wdPopGroupList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $wdCode = $this->_data['wdCode'];
        $wdCodeCond = "";
        if ($wdCode) {
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND b.wd_code IN ($wdCode)";
                }
            }
            $arrResult = array(
                "teamType" => $this->getDsTypeList($wdCodeCond),
                "teamList" => $this->getTeamsList($wdCodeCond),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamList()
    {
        $dsType = $this->_data['dsType'];
        $dsTypeCond = "";
        if (isset($dsType) && $dsType != "" && $dsType >= 0) {
            if (!is_array($dsType)) {
                $dsType = array($dsType);
            }
            if (in_array('all', $dsType)) {
                $dsTypeCond = ""; // No condition for 'all'
            } else {
                $dsType = "'" . implode("','", $dsType) . "'";
                $dsTypeCond = " AND b.is_type IN ($dsType)";
            }
            $arrResult = array(
                "teamList" => $this->getTeamsList($dsTypeCond),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getDistrictList()
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.district from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.district";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['district'],
                    "value" => $row['district']
                );
            }
        }

        return $arrData;
    }

    final public function getBranchList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all",
        // );

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.branch_name, a.main_branch, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by a.branch_name";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['branch_name'],
                    "value" => $row['branch_id'],
                    "mainBranch" => $row['main_branch']
                );
            }
        }

        return $arrData;
    }

    final public function getCircleList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.circle, c.circle_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.circle IS NOT NULL AND b.circle != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.circle";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['circle'] . " - " . $row['circle_name'],
                    "value" => $row['circle']
                );
            }
        }

        return $arrData;
    }

    final public function getSectionList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.section, c.section_name from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.section IS NOT NULL AND b.section != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.section";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['section'] . " - " . $row['section_name'],
                    "value" => $row['section']
                );
            }
        }

        return $arrData;
    }


    final public function getWdCodeList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.wd_code, c.wd_firm_name, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND b.wd_code IS NOT NULL AND b.wd_code != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by b.wd_code";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_code'] . ' - ' . $row['wd_market'] . ' - ' . $row['wd_firm_name'],
                    "value" => $row['wd_code']
                );
            }
        }

        return $arrData;
    }


    final public function getWdMarketList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct c.wd_market, c.wd_market from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_market IS NOT NULL AND c.wd_market != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_market";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_market'],
                    "value" => $row['wd_market']
                );
            }
        }

        return $arrData;
    }

    final public function getWdPopGroupList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct c.wd_pop_group, c.wd_pop_group from tblbranch as a, tblproject_team as b, tblmapping_wd as c where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0" .
            " AND c.wd_pop_group IS NOT NULL AND c.wd_pop_group != '' AND b.wd_code = c.wd_code AND b.s_id = '99' $where order by c.wd_pop_group";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['wd_pop_group'],
                    "value" => $row['wd_pop_group']
                );
            }
        }

        return $arrData;
    }


    final public function getDsTypeList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND b.is_type != 4 AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = "";
                if ($row['is_type'] == 0) {
                    $teamType = "Van DS";
                } elseif ($row['is_type'] == 1) {
                    $teamType = "Niche";
                } elseif ($row['is_type'] == 2) {
                    $teamType = "Town SWD";
                } elseif ($row['is_type'] == 3) {
                    $teamType = "Hybrid";
                } elseif ($row['is_type'] == 5) {
                    $teamType = "NPSR";
                }
                $arrData[] = array(
                    "label" => $teamType,
                    "value" => (string)$row['is_type']
                );
            }
        }

        return $arrData;
    }


    final public function getTeamsList($cond = "")
    {
        $arrData = array();
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.team_name, b.team_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.team_name IS NOT NULL AND b.team_name != '' AND b.s_id = '99' $where order by b.team_name";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['team_name'],
                    "value" => $row['team_id']
                );
            }
        }

        return $arrData;
    }
}
