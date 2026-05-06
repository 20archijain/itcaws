<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class LineCutReport
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
            "monthList" => $this->monthLabelAndValue(),
            "reportTypeList" => array(
                array(
                    "label" => "DS Level",
                    "value" => "1",
                ),
                array(
                    "label" => "Outlet Level",
                    "value" => "2",
                )
            )
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function monthLabelAndValue($count = 12)
    {
        $months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        $arrData = [];
        $currentMonth  = date('n'); // 1–12
        $currentYear = date('Y');

        // Start from next month
        $nextMonth = $currentMonth + 1;
        $nextYear = $currentYear;

        // Adjust if next month exceeds December
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        for ($i = 0; $i < $count; $i++) {
            $targetMonth = $nextMonth - $i;
            $targetYear = $nextYear;

            // Adjust if month goes below 1
            while ($targetMonth <= 0) {
                $targetMonth += 12;
                $targetYear--;
            }

            $monthName = $months[$targetMonth - 1];
            $shortLabel = date('M', mktime(0, 0, 0, $targetMonth, 10)) . ' ' . substr($targetYear, 2);

            $arrData[] = [
                "label" => $shortLabel,
                "value" => $monthName . ' ' . $targetYear
            ];
        }

        return $arrData;
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
        $reportType = $this->_data['reportType'];
        if ($reportType == 1) {
            $this->getDownloadDataDsWise();
        } else {
            $this->getDownloadDataOutletWise();
        }
    }

    public function getDownloadDataDsWise()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // Filter query
        $whereFilter = $this->getConditionFilter();
        $branch = getFormData($this->_data, "branch");
        $product = getFormData($this->_data, "product");
        $teamType = getFormData($this->_data, "dsType");

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $Cond = "";
        $teamTypeCond = "";
        if ($teamType) {
            if ($teamType != 'all') {
                $teamTypeCond .= " AND b.is_type = $teamType";
            }
        }


        $arrExcelData = [];
        $arrExcelData[] = [
            "Month", "District", "Branch", "Region", "Circle", "Section", "WD Code", "WD Name", "WD Pop Group", "WD Market",
            "DS Type", "DS Id", "DS Name", "Total SKU Mapped", "Total Line Cut", "Total Outlet Mapped",  "Total Outlet Visited", "Unique Billed Outlet", "ALC", "ULC/Month"
        ];

        // $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                $branchIds = $branch;
            } else {
                $branchIds = $this->getBranchListWithoutAll();
            }
        }

        foreach ($branchIds as $branchId) {
            //Team Query
            $sAction4 = null;
            $iRows4 = 0;
            $sQuery4 = "SELECT b.is_type, b.team_name, b.team_id, c.main_branch, c.branch_name, a.wd_code, a.wd_firm_name, a.wd_market, a.wd_pop_group, a.district, a.branch, a.circle_name, a.circle, a.section_name, a.section" .
                " FROM tblmapping_wd as a, tblproject_team AS b, tblbranch as c WHERE a.wd_code = b.wd_code AND b.branch_id = c.branch_id AND a.dstatus = 0 AND c.dstatus = 0 AND b.dstatus = 0" .
                " AND b.is_type NOT IN (100, 101) AND b.branch_id = $branchId $teamTypeCond $whereFilter";
            // echo $sQuery4;die;
            $this->_dbConn->ExecuteSelectQuery($sQuery4, $sAction4, $iRows4);

            if ($iRows4 > 0) {
                while ($row4 = $this->_dbConn->GetData($sAction4)) {
                    $wd_code = $row4["wd_code"];
                    $wd_pop_group = $row4["wd_pop_group"];
                    $wd_market = $row4["wd_market"];
                    $wd_firm_name = $row4["wd_firm_name"];
                    $team_name = $row4["team_name"];
                    $team_id = $row4["team_id"];
                    $district = $row4["district"];
                    $branch = $row4["main_branch"];
                    $region = $row4["branch_name"];
                    $circle_name = $row4["circle_name"];
                    $circle = $row4["circle"];
                    $section_name = $row4["section_name"];
                    $section = $row4["section"];
                    $teamType = $row4['is_type'];
                    $showSection = $section . ' - ' . $section_name;
                    $showCircle = $circle . ' - ' . $circle_name;

                    //All Brand Query
                    $sAction3 = null;
                    $iRows3 = 0;
                    $sQuery3 = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
                        "  AND a.branch_id = $branchId $teamTypeCond
                        $Cond $whereFilter AND a.team_type = '$teamType' ORDER BY a.category_name, a.product_name";
                    // echo $sQuery3;
                    // die;
                    $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

                    $arrProductColumnsAllProduct = array();
                    $arrColumnsAllProduct = array();
                    if ($iRows3 > 0) {
                        while ($row3 = $this->_dbConn->GetData($sAction3)) {
                            // $arrProductColumnsAllProduct[] = "SUM(a.{$row3["summary_column_name"]}) AS {$row3["summary_column_name"]}";
                            $arrProductColumnsAllProduct[] = "a.{$row3["summary_column_name"]} AS {$row3["summary_column_name"]}";
                            $arrColumnsAllProduct[] = "{$row3["summary_column_name"]}";
                        }
                    }

                    $skuForQuery = "";
                    if (!empty($arrProductColumnsAllProduct)) {
                        $skuForQuery = implode(", ", $arrProductColumnsAllProduct);
                        $skuForQuery = $skuForQuery . ", ";
                    }


                    if ($branchId == 40) {
                        $allShops = getRowColumn(
                            $this->_dbConn,
                            "tblroute_details_delhi",
                            "COUNT(rec_id) AS total",
                            "dstatus = 0 AND ho_team_id = $team_id"
                        );
                    } else {
                        $allShops = getRowColumn(
                            $this->_dbConn,
                            "tblroute_details",
                            "COUNT(rec_id) AS total",
                            "dstatus = 0 AND team_id = $team_id"
                        );
                    }

                    $showTeamType = "";
                    if ($row4['is_type'] == 0) {
                        $showTeamType = "Van DS";
                    } elseif ($row4['is_type'] == 1) {
                        $showTeamType = "Niche";
                    } elseif ($row4['is_type'] == 2) {
                        $showTeamType = "Town SWD";
                    } elseif ($row4['is_type'] == 3) {
                        $showTeamType = "Hybrid";
                    } elseif ($row4['is_type'] == 4) {
                        $showTeamType = "SCP";
                    } elseif ($row4['is_type'] == 5) {
                        $showTeamType = "NPSR";
                    }


                    $arrMonth = $this->_data['month'];
                    foreach ($arrMonth as $month) {
                        $firstDate = date('Y-m-01', strtotime($month));
                        $lastDate  = date('Y-m-t', strtotime($month));


                        $queryNew = "SELECT $skuForQuery a.pro_id, a.ques_3, a.team_id FROM tblsurvey_response_details AS a" .
                            " WHERE a.dstatus = 0 AND a.team_id = '$team_id' AND a.capture_date BETWEEN '$firstDate' AND '$lastDate'" .
                            "";
                        // echo $queryNew;die;

                        $extractedData = [];

                        $rsAction1 = null;
                        $iActionRows1 = 0;
                        $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);


                        if ($iActionRows1 > 0) {
                            while ($row1 = $this->_dbConn->GetData($rsAction1)) {
                                $pro_id = $row1['pro_id'];
                                $shopID = $row1['ques_3'];


                                foreach ($arrColumnsAllProduct as $col) {
                                    if (array_key_exists($col, $row1)) {
                                        $extractedData[$shopID][$pro_id][$col] = $row1[$col];
                                    } else {
                                        $extractedData[$shopID][$pro_id][$col] = $row1[$col];
                                    }
                                }
                            }
                        }

                        $productsWithSales = [];

                        $totalVisitedShop = 0;
                        $totalLineCut = 0;
                        if (isset($extractedData) && !empty($extractedData)) {
                            foreach ($extractedData as $shop => $shopArr) {
                                foreach ($shopArr as $pro => $proArr) {
                                    $totalVisitedShop++;
                                    foreach ($proArr as $index => $lastData) {
                                        if ($lastData > 0) {
                                            $totalLineCut++;
                                            $productsWithSales[$index] = true;
                                        }
                                    }
                                }
                            }
                        }

                        $shopCount = 0;
                        if (isset($extractedData) && !empty($extractedData)) {
                            foreach ($extractedData as $shop => $shopArr) {
                                $hasSale = false;
                                foreach ($shopArr as $pro => $proArr) {
                                    foreach ($proArr as $index => $lastData) {
                                        if ($lastData > 0) {
                                            $hasSale = true;
                                            break 2;
                                        }
                                    }
                                }
                                if ($hasSale) {
                                    $shopCount++;
                                }
                            }
                        }

                        $arrExcelData[] = [
                            $month,
                            $district,
                            $branch,
                            $region,
                            $showCircle,
                            $showSection,
                            $wd_code,
                            $wd_firm_name,
                            $wd_pop_group,
                            $wd_market,
                            $showTeamType,
                            $team_id,
                            $team_name,
                            count($arrColumnsAllProduct),
                            $totalLineCut,
                            $allShops,
                            $totalVisitedShop,
                            $shopCount,
                            $shopCount > 0 ? round((float) ($totalLineCut / $shopCount), 2) : 0,
                            isset($productsWithSales) && !empty($productsWithSales) ? count($productsWithSales) : 0

                        ];
                    }
                }
            }
        }

        $fileName = "DS_WISE_Report_$currentDateTime.xlsx";
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


    public function getDownloadDataOutletWise()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // Filter query
        $whereFilter = $this->getConditionFilter();
        $branch = getFormData($this->_data, "branch");
        $product = getFormData($this->_data, "product");
        $teamType = getFormData($this->_data, "dsType");

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $Cond = "";
        $teamTypeCond = "";
        if ($teamType) {
            if ($teamType != 'all') {
                $teamTypeCond .= " AND b.is_type = $teamType";
            }
        }


        $arrExcelData = [];
        $arrExcelData[] = ["Month", "District", "Branch", "Region", "Circle", "Section", "WD Code", "WD Name", "WD Pop Group", "WD Market", "DS Type", "DS Id", "DS Name", "Route Name", "Outlet Name", "Outlet ID", "Total SKU", "Total LIne Cut", "Total Transaction", "ALC", "ULC"];

        // $branchCond = "";
        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                $branchIds = $branch;
            } else {
                $branchIds = $this->getBranchListWithoutAll();
            }
        }

        foreach ($branchIds as $branchId) {
            //Team Query
            $sAction4 = null;
            $iRows4 = 0;
            $sQuery4 = "SELECT b.is_type, b.team_name, b.team_id, c.main_branch, c.branch_name, a.wd_code, a.wd_firm_name, a.wd_market, a.wd_pop_group, a.district, a.branch, a.circle_name, a.circle, a.section_name, a.section" .
                " FROM tblmapping_wd as a, tblproject_team AS b, tblbranch as c WHERE a.wd_code = b.wd_code AND b.branch_id = c.branch_id AND a.dstatus = 0 AND c.dstatus = 0 AND b.dstatus = 0" .
                " AND b.branch_id = $branchId $teamTypeCond $whereFilter";
            // echo $sQuery4;die;
            $this->_dbConn->ExecuteSelectQuery($sQuery4, $sAction4, $iRows4);

            if ($iRows4 > 0) {
                while ($row4 = $this->_dbConn->GetData($sAction4)) {
                    $wd_code = $row4["wd_code"];
                    $wd_pop_group = $row4["wd_pop_group"];
                    $wd_market = $row4["wd_market"];
                    $wd_firm_name = $row4["wd_firm_name"];
                    $team_name = $row4["team_name"];
                    $team_id = $row4["team_id"];
                    $district = $row4["district"];
                    $branch = $row4["main_branch"];
                    $region = $row4["branch_name"];
                    $circle_name = $row4["circle_name"];
                    $circle = $row4["circle"];
                    $section_name = $row4["section_name"];
                    $section = $row4["section"];
                    $isType = $row4["is_type"];
                    $showSection = $section . ' - ' . $section_name;
                    $showCircle = $circle . ' - ' . $circle_name;

                    $showTeamType = "";
                    if ($row4['is_type'] == 0) {
                        $showTeamType = "Van DS";
                    } elseif ($row4['is_type'] == 1) {
                        $showTeamType = "Niche";
                    } elseif ($row4['is_type'] == 2) {
                        $showTeamType = "Town SWD";
                    } elseif ($row4['is_type'] == 3) {
                        $showTeamType = "Hybrid";
                    } elseif ($row4['is_type'] == 4) {
                        $showTeamType = "SCP";
                    } elseif ($row4['is_type'] == 5) {
                        $showTeamType = "NPSR";
                    }

                    $arrMonth = $this->_data['month'];
                    foreach ($arrMonth as $month) {
                        $firstDate = date('Y-m-01', strtotime($month));
                        $lastDate  = date('Y-m-t', strtotime($month));

                        if ($firstDate > '2025-12-31') {
                            $date = DateTime::createFromFormat('F Y', $month);

                            $numericMonth = $date->format('m');
                            $numericYear  = $date->format('Y');
                        } else {
                            $numericMonth = 12;
                            $numericYear  = 2025;
                        }

                        //All Brand Query
                        $sAction3 = null;
                        $iRows3 = 0;
                        $sQuery3 = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products_allocation as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
                            "  AND a.branch_id = $branchId $teamTypeCond AND a.team_type = '$isType' AND a.month = '$numericMonth' AND a.year = '$numericYear'
                        $Cond $whereFilter ORDER BY a.category_name, a.product_name";
                        // echo $sQuery3;die;
                        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

                        $arrProductColumnsAllProduct = array();
                        $arrColumnsAllProduct = array();
                        if ($iRows3 > 0) {
                            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                                // $arrProductColumnsAllProduct[] = "SUM(a.{$row3["summary_column_name"]}) AS {$row3["summary_column_name"]}";
                                $arrProductColumnsAllProduct[] = "a.{$row3["summary_column_name"]} AS {$row3["summary_column_name"]}";
                                $arrColumnsAllProduct[] = "{$row3["summary_column_name"]}";
                            }
                        }

                        $skuForQuery = "";
                        if (!empty($arrProductColumnsAllProduct)) {
                            $skuForQuery = implode(", ", $arrProductColumnsAllProduct);
                        }

                        if (isset($arrColumnsAllProduct) && !empty($arrColumnsAllProduct)) {
                            if ($branchId == 40) {
                                $routeTable = "tblroute_details_delhi";
                            } else {
                                $routeTable = "tblroute_details";
                            }
                            $queryNew = "SELECT $skuForQuery, a.pro_id, b.shop_uniq_code, b.route_name, b.outlet_name FROM tblsurvey_response_details AS a, $routeTable as b" .
                                " WHERE a.dstatus = 0 AND a.team_id = '$team_id' AND a.capture_date BETWEEN '$firstDate' AND '$lastDate'" .
                                " AND a.ques_3 = b.rec_id AND b.dstatus = 0";
                            // echo $queryNew;die;

                            $extractedData = [];
                            $outletArray = array();

                            $rsAction1 = null;
                            $iActionRows1 = 0;
                            $this->_dbConn->ExecuteSelectQuery($queryNew, $rsAction1, $iActionRows1);


                            if ($iActionRows1 > 0) {
                                while ($row1 = $this->_dbConn->GetData($rsAction1)) {
                                    $pro_id = $row1['pro_id'];
                                    $shopID = $row1['shop_uniq_code'];
                                    $outlet_name = $row1['outlet_name'];
                                    $route_name = $row1['route_name'];
                                    $outletArray[$shopID] = array(
                                        $outlet_name,
                                        $route_name,
                                    );


                                    foreach ($arrColumnsAllProduct as $col) {
                                        if (array_key_exists($col, $row1)) {
                                            $extractedData[$shopID][$pro_id][$col] = $row1[$col];
                                        } else {
                                            $extractedData[$shopID][$pro_id][$col] = $row1[$col];
                                        }
                                    }
                                }
                            }

                            $totalLineCutArr = array();
                            $productsWithSales = array();

                            if (isset($extractedData) && !empty($extractedData)) {
                                foreach ($extractedData as $shop => $shopArr) {
                                    $totalLineCut = 0;
                                    foreach ($shopArr as $pro => $proArr) {
                                        foreach ($proArr as $index => $lastData) {
                                            if ($lastData > 0) {
                                                $totalLineCut++;
                                                $totalLineCutArr[$shop] = $totalLineCut;
                                                $productsWithSales[$shop][$index] = true;
                                            }
                                        }
                                    }
                                }
                            }


                            foreach ($extractedData as $index => $shopArr) {
                                $totalLine = $totalLineCutArr[$index] ?? 0;
                                $billed = count($shopArr) ?? 0;
                                $arrExcelData[] = [
                                    $month,
                                    $district,
                                    $branch,
                                    $region,
                                    $showCircle,
                                    $showSection,
                                    $wd_code,
                                    $wd_firm_name,
                                    $wd_pop_group,
                                    $wd_market,
                                    $showTeamType,
                                    $team_id,
                                    $team_name,
                                    $outletArray[$index][1],
                                    $outletArray[$index][0],
                                    $index,
                                    count($arrColumnsAllProduct) ?? 0,
                                    $totalLine,
                                    $billed,
                                    round((float) ($totalLine / $billed), 2),
                                    isset($productsWithSales[$index]) && !empty($productsWithSales[$index]) ? count($productsWithSales[$index]) : 0,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $fileName = "OUTLET_WISE_Report_$currentDateTime.xlsx";
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


    final public function getBranchListWithoutAll($cond = "")
    {
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
                $arrData[] = $row['branch_id'];
            }
        }

        return $arrData;
    }


    final public function getResult($table, $products, $where)
    {
        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT $products from $table WHERE dstatus = 0 $where ";
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);
        $result = "";
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $result = array_values($row3);  // push full row (associative array) into result
            }
        }
        return $result;
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );

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
        $arrData[] = array(
            "label" => "All",
            "value" => "all",
        );

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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $arrData[] = array(
            "label" => "All",
            "value" => "all"
        );
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
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by b.is_type";
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
                } elseif ($row['is_type'] == 4) {
                    $teamType = "SCP";
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
