<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/DBConnection.php';
// require_once $include_path . '../../../uproots/php_libs/fpdf186/fpdf.php';
require $PHP_FPDF_PATH;

// Custom PDF class with footer
class SalesReportPDF extends FPDF
{
    public function footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }
}

// phpcs:ignore
class generatePDFCronjob
{
    private $_dbConn = null;
    private $_tables = [];
    private $_commonSettings = [];
    private $_jsonWiseAndbranchWiseProductsColumns = [];
    private $_jsonWiseAndbranchWiseStockpickupProductsColumns = [];
    private $_projectId = 1;
    private $logFilename;

    public function __construct($dbConn, $logFilename = "")
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
        $this->logFilename = $logFilename ? $logFilename : "log_GeneratePDFData";
    }

    private function getWeekDateRange($date)
    {
        $day = (int)date('j', strtotime($date));
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));

        if ($day >= 1 && $day <= 7) {
            $start = "$year-$month-01";
            $end   = "$year-$month-07";
        } elseif ($day >= 8 && $day <= 14) {
            $start = "$year-$month-08";
            $end   = "$year-$month-14";
        } elseif ($day >= 15 && $day <= 21) {
            $start = "$year-$month-15";
            $end   = "$year-$month-21";
        } else {
            $start = "$year-$month-22";
            $end   = date("Y-m-t", strtotime($date));
        }

        return [$start, $end];
    }

    private function generatePDFForTeam($teamId, $startDatetime, $endDatetime, $summaryId)
    {
        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];

        // Get team information
        $teamInfo = getRowColumns(
            $this->_dbConn,
            $projectTeamTable,
            "team_id, team_name, is_type, circle, section, wd_code, branch_id",
            "team_id = $teamId AND dstatus = 0"
        );

        if (!isNonEmptyArray($teamInfo)) {
            // debug_log(
            //     "\r\nError: Team information not found for Team ID: $teamId, Summary ID: $summaryId\r\n",
            //     $this->logFilename
            // );
            return false;
        }

        $branchId = $teamInfo[6];
        $teamName = $teamInfo[1];
        $isType = $teamInfo[2];
        $circle = $teamInfo[3];
        $section = $teamInfo[4];
        $wdCode = $teamInfo[5];

        // Get branch information
        $branchInfo = getRowColumns($this->_dbConn, $branchTable, "branch_name, main_branch", "branch_id = $branchId AND dstatus = 0");

        $ProductQueryVarient = "SELECT DISTINCT product_name, summary_column_name, category_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId  AND team_type = $isType  ORDER BY product_name";  // get all the product from the query

        $variants = [];
        $varAction = null;
        $varRows = 0;

        $this->_dbConn->ExecuteSelectQuery($ProductQueryVarient, $varAction, $varRows);

        while ($rowVar = $this->_dbConn->GetData($varAction)) {
            $variants[] = [
                'name' => $rowVar['product_name'],
                'col'  => $rowVar['summary_column_name']
            ];
        }

        // if (!isNonEmptyArray($branchInfo)) {
        //     // debug_log(
        //     //     "\r\nError: Branch information not found for Branch ID: $branchId, Team ID: $teamId, Summary ID: $summaryId\r\n",
        //     //     $this->logFilename
        //     // );
        //     return false;
        // }

        // $branchName = $branchInfo[0] ?? 0;
        // $mainBranchName = $branchInfo[1] ?? 0;

        // Get product columns for this branch
        $sProductQuery = "SELECT DISTINCT product_name, summary_column_name, category_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId ORDER BY product_name";
        $sProductAction = null;
        $iProductRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

        if ($iProductRows == 0) {
            // debug_log(
            //     "\r\nError: No products found for Branch ID: $branchId, Team ID: $teamId, Summary ID: $summaryId\r\n",
            //     $this->logFilename
            // );
            return false;
        }

        $summaryColName = [];
        $productNames = [];
        $category_name = [];
        while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
            $summaryColName[] = $rowProduct["summary_column_name"];
            $productNames[] = $rowProduct["product_name"];
            $category_name[$rowProduct["summary_column_name"]] = $rowProduct["category_name"];
        }

        $sProductSaleColumns = implode(",", $summaryColName);
        $isTypeMap = [0 => "Van DS", 1 => "Niches", 5 => "NPSR"];
        $dsType = isset($isTypeMap[$isType]) ? $isTypeMap[$isType] : "";
        // $ProductQueryVarient = "SELECT DISTINCT product_name, summary_column_name, category_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId  AND team_type = $isType  ORDER BY product_name";  // get all the product from the query

        // Fetch sales data for this team within datetime range
        $sQuery = "SELECT a.capture_datetime, a.capture_date, a.ques_0, a.ques_1, a.ques_3, $sProductSaleColumns" .
            " FROM $respTable AS a WHERE a.team_id = $teamId AND a.dstatus = 0 AND a.ques_0 IN ('Outlet Order', 'Add Outlet')" .
            " AND a.capture_datetime >= '$startDatetime' AND a.capture_datetime <= '$endDatetime'" .
            " ORDER BY a.capture_date DESC, a.capture_datetime DESC";

        $rsAction = null;
        $iRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        // Group sales data by outlet
        $groupedSalesData = [];
        $commonDate = "";
        // $commonWeek = "";
        $commonRoute = "";
        // $isMdo = "0";
        // $mdoId = "";
        // $mdoName = "";

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $outletId = $row['ques_3'];
                $date = $row['capture_date'];
                $routeData = json_decode($row["ques_1"], true);
                $route = is_array($routeData) && isset($routeData[0]) ? htmlspecialchars_decode($routeData[0]) : "";

                // Set common values (use first record's values)
                if (empty($commonDate)) {
                    $commonDate = $date;
                    // $commonWeek = $week;
                    $commonRoute = $route;

                    // Check for MDO
                    $isMdoWorks = [];
                    if (isNonEmptyArray($isMdoWorks)) {
                        $isMdo = "1";
                        $mdoId = isset($isMdoWorks[0]) ? $isMdoWorks[0] : "";
                        $mdoName = isset($isMdoWorks[1]) ? $isMdoWorks[1] : "";
                    }
                }
                list($weekStart, $weekEnd) = $this->getWeekDateRange($commonDate);

                $outletData = getRowColumns(
                    $this->_dbConn,
                    $routeTable,
                    "outlet_name, shop_uniq_code, shop_type, outlet_mobile",
                    "rec_id = '$outletId' AND team_id = $teamId AND dstatus = 0"
                );
                $outletName = isset($outletData[0]) ? htmlentities($outletData[0]) : "";
                $shopUniqueCode = $outletData[1] ?? "";
                $outletType = $outletData[2] ?? "";
                $mobileNo = $outletData[3] ?? "";

                // Create unique key for outlet
                $outletKey = $outletId;

                // Initialize outlet data if not exists
                if (!isset($groupedSalesData[$outletKey])) {
                    $groupedSalesData[$outletKey] = [
                        'outlet_name' => $outletName,
                        'mobile' => $mobileNo,
                        'outlet_id' => $shopUniqueCode,
                        'outlet_type' => $outletType,
                        'product_quantities' => []
                    ];
                }

                // Aggregate product quantities for this outlet
                foreach ($summaryColName as $colName) {
                    $salesQty = $row[$colName] ?? 0;
                    $groupedSalesData[$outletKey]['product_quantities'][$colName] =
                        ($groupedSalesData[$outletKey]['product_quantities'][$colName] ?? 0) + $salesQty;
                }
            }
        }

        // Calculate total unique lines across all outlets and prepare outlet summaries
        $allProductCols = [];
        $totalSalesQty = 0;
        $totalOutlets = 0;
        $totalLineCut = 0; // Sum of all line cuts across outlets
        $totalShopsInData = count($groupedSalesData); // Total shops in data (including zero sales)

        foreach ($groupedSalesData as $outletKey => $outletData) {
            $validProducts = [];
            foreach ($outletData['product_quantities'] as $colName => $qty) {
                if ($qty > 0) {
                    $validProducts[] = $colName;
                    $totalSalesQty += $qty;
                }
            }
            $groupedSalesData[$outletKey]['valid_products'] = $validProducts;
            $groupedSalesData[$outletKey]['outlet_total'] = array_sum(array_filter($outletData['product_quantities'], function ($qty) {
                return $qty > 0;
            }));
            $groupedSalesData[$outletKey]['unique_lines'] = count($validProducts);

            // Only count outlets that have at least one product sale
            if (count($validProducts) > 0) {
                $allProductCols = array_merge($allProductCols, $validProducts);
                $totalOutlets++; // Only count shops with at least 1 sale
                $totalLineCut += count($validProducts); // Add this outlet's line cut to total
            }
        }

        // Calculate unique line cut: unique products / outlets with 1 or more product sale
        $totalUniqueLines = count(array_unique($allProductCols));
        $uniqueLineCut = $totalOutlets > 0 ? round($totalUniqueLines / $totalOutlets, 2) : 0;

        // Calculate average line cut per outlet: sum of all line cuts / outlets with 1 or more product sale
        $avgLineCutPerOutlet = $totalOutlets > 0 ? round($totalLineCut / $totalOutlets, 2) : 0;

        // Generate PDF with custom class for footer
        $pdf = new SalesReportPDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Title Section with colored background
        $pdf->SetFillColor(41, 128, 185);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'SURVEY REPORT', 0, 1, 'C', true);
        $pdf->Ln(2);

        // Subtitle
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 4, 'Generated on: ' . date('d-M-Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(3);

        // Header Table with common details
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 6, 'DS INFORMATION', 0, 1, 'L', true);
        $pdf->Ln(1);

        $headerData = [
            ['DS ID', $teamId],
            ['DS Name', $teamName],
            ['DS Type', $dsType],
            ['Date', $commonDate],
            ['Route', $commonRoute],
        ];

        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(245, 245, 250);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($headerData as $index => $headerRow) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(245, 245, 250);
            $pdf->Cell(65, 5, $headerRow[0], 1, 0, 'L', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetFillColor(255, 255, 255);
            $value = $headerRow[1] ? $headerRow[1] : "N/A";
            $pdf->Cell(125, 5, $value, 1, 1, 'L', false);
        }

        $pdf->Ln(3);

        // SUMMARY TOTALS SECTION - Show at the beginning
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(231, 76, 60);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 6, 'SUMMARY TOTALS', 0, 1, 'L', true);
        $pdf->Ln(1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(245, 245, 250);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(200, 200, 200);

        $summaryData = [
            ['Total Outlets Surveyed', $totalShopsInData],
            // ['Total Outlets with Sales', $totalOutlets],
            ['Total Volume (Ms)', number_format($totalSalesQty, 2)],
            // ['Total Unique Lines Cut', $totalUniqueLines],
            ['Unique Line Cut Per Outlet', $uniqueLineCut],
            ['Average Line Cut Per Outlet', $avgLineCutPerOutlet],
        ];

        foreach ($summaryData as $summaryRow) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(245, 245, 250);
            $pdf->Cell(95, 5, $summaryRow[0], 1, 0, 'L', true);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(231, 76, 60);
            $pdf->Cell(95, 5, $summaryRow[1], 1, 1, 'R', false);
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Ln(5);

        // Sales Data Table - Two Outlets Per Row
        if (count($groupedSalesData) > 0) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(46, 204, 113);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 6, 'SURVEY DETAILS', 0, 1, 'L', true);
            $pdf->Ln(1);

            $pageBreakMargin = 20;

            foreach ($groupedSalesData as $outletKey => $outletData) {
                $outletProducts = $outletData['product_quantities'] ?? [];
                $validProducts = array_filter($outletProducts, function ($qty) {
                    return $qty > 0;
                }, ARRAY_FILTER_USE_BOTH);
                $outletUniqueLines = count($validProducts);
                $outletTotal = array_sum($validProducts);

                // Check if we need a new page
                $currentY = $pdf->GetY();
                $pageHeight = $pdf->GetPageHeight();
                $bottomMargin = $pageHeight - $pageBreakMargin;

                // Estimate space needed: outlet header (7mm) + products (4.5mm each) + spacing + subtotals (8mm)
                $estimatedSpace = 7 + (count($validProducts) * 4.5) + 3 + 8;

                if ($currentY + $estimatedSpace > $bottomMargin) {
                    $pdf->AddPage();
                }

                // Outlet Header Section
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(200, 200, 200);

                $outletName = strlen($outletData['outlet_name']) > 35 ? substr($outletData['outlet_name'], 0, 32) . '...' : $outletData['outlet_name'];
                $pdf->Cell(95, 5, 'Outlet: ' . $outletName, 1, 0, 'L', true);
                $pdf->Cell(47.5, 5, 'Mobile: ' . ($outletData['mobile'] ?: 'N/A'), 1, 0, 'L', true);
                $pdf->Cell(47.5, 5, 'ID: ' . ($outletData['outlet_id'] ?: 'N/A'), 1, 1, 'L', true);

                // Product table headers
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->SetFillColor(149, 165, 166);
                $pdf->Cell(8, 5, 'Sr.', 1, 0, 'C', true);
                $pdf->Cell(65, 5, 'Category', 1, 0, 'C', true);
                $pdf->Cell(85, 5, 'Variant', 1, 0, 'C', true);
                $pdf->Cell(32, 5, 'Qty (M)', 1, 1, 'C', true);

                // Product rows
                $pdf->SetFont('Arial', '', 6);
                $pdf->SetTextColor(0, 0, 0);
                $productCount = 1;

                foreach ($validProducts as $colName => $totalQty) {
                    $pdf->SetFillColor(255, 255, 255);

                    $pdf->Cell(8, 4.5, $productCount++, 1, 0, 'C', false);

                    $category = strlen($category_name[$colName] ?? '') > 32 ? substr($category_name[$colName] ?? '', 0, 29) . '...' : ($category_name[$colName] ?? 'N/A');
                    $pdf->Cell(65, 4.5, $category, 1, 0, 'L', false);

                    $variantIndex = array_search($colName, $summaryColName);
                    $variant = $variantIndex !== false ? $productNames[$variantIndex] : 'N/A';
                    $variant = strlen($variant) > 42 ? substr($variant, 0, 39) . '...' : $variant;
                    $pdf->Cell(85, 4.5, $variant, 1, 0, 'L', false);

                    $pdf->SetFont('Arial', 'B', 6);
                    $pdf->Cell(32, 4.5, number_format($totalQty, 2), 1, 1, 'R', false);
                    $pdf->SetFont('Arial', '', 6);
                }

                // Outlet subtotals
                $pdf->Ln(1);
                $pdf->SetFont('Arial', 'B', 7);
                $pdf->SetFillColor(241, 196, 15);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(100, 5, 'Unique Lines Cuts : ' . $outletUniqueLines, 0, 0, 'L', true);
                $pdf->Cell(90, 5, 'Total Volume (M): ' . number_format($outletTotal, 2), 0, 1, 'R', true);
                $pdf->Ln(2);
            }
        } else {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(231, 76, 60);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 6, 'SURVEY DETAILS', 0, 1, 'L', true);
            $pdf->Ln(3);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 5, 'No Survey data found.', 0, 1, 'C');
        }

        $ProductQueryVarient = " SELECT DISTINCT product_name, summary_column_name  FROM $branchProductsTable  WHERE dstatus = 0  AND branch_id = $branchId  AND team_type = $isType ORDER BY product_name";

        $varAction = null;
        $varRows = 0;
        $this->_dbConn->ExecuteSelectQuery($ProductQueryVarient, $varAction, $varRows);

        if ($varRows > 0) {
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(155, 89, 182);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 6, 'VARIANT PORTFOLIO SUMMARY', 0, 1, 'L', true);
            $pdf->Ln(2);

            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell(30, 6, 'Variant', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Planned UO', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'UOB Today', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Vol Today', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'PDO Daily', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'UOB Week', 1, 0, 'C', true);
            $pdf->Cell(25, 6, 'Vol Week', 1, 0, 'C', true);
            $pdf->Cell(20, 6, 'PDO Weekly', 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 7);

            while ($rowVar = $this->_dbConn->GetData($varAction)) {
                $variantName = $rowVar['product_name'];
                $col = $rowVar['summary_column_name'];

                /* TODAY */
                $todayQuery = " SELECT COUNT(DISTINCT ques_3) as uob_today, SUM($col) as vol_today FROM $respTable WHERE team_id = $teamId AND capture_date = '$commonDate'
                                 AND dstatus = 0  AND $col > 0";

                $todayAction = null;
                $tmp = null;
                $this->_dbConn->ExecuteSelectQuery($todayQuery, $todayAction, $tmp);
                $todayData = $this->_dbConn->GetData($todayAction);

                $uob_today = (int)($todayData['uob_today'] ?? 0);
                $vol_today = (float)($todayData['vol_today'] ?? 0);

                /* WEEK */
                $weekQuery = " SELECT COUNT(DISTINCT ques_3) as uob_week, SUM($col) as vol_week FROM $respTable WHERE team_id = $teamId
                                 AND capture_date BETWEEN '$weekStart' AND '$weekEnd' AND dstatus = 0 AND $col > 0";

                $weekAction = null;
                $tmp2 = null;
                $this->_dbConn->ExecuteSelectQuery($weekQuery, $weekAction, $tmp2);
                $weekData = $this->_dbConn->GetData($weekAction);

                $uob_week = (int)($weekData['uob_week'] ?? 0);
                $vol_week = (float)($weekData['vol_week'] ?? 0);

                /* PDO */
                $pdo_daily  = ($uob_today > 0) ? round($vol_today / $uob_today, 2) : 0;
                $pdo_weekly = ($uob_week > 0) ? round($vol_week / $uob_week, 2) : 0;

                /* Planned Outlet */
                $plannedQuery = "SELECT COUNT(DISTINCT shop_uniq_code) as planned_uo FROM $routeTable WHERE team_id = $teamId AND dstatus = 0";

                $planAction = null;
                $tmp3 = null;
                $this->_dbConn->ExecuteSelectQuery($plannedQuery, $planAction, $tmp3);
                $planData = $this->_dbConn->GetData($planAction);
                $plannedUO = (int)($planData['planned_uo'] ?? 0);

                /* Print Row */
                $pdf->Cell(30, 6, $variantName, 1);
                $pdf->Cell(25, 6, $plannedUO, 1, 0, 'R');
                $pdf->Cell(20, 6, $uob_today, 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($vol_today, 2), 1, 0, 'R');
                $pdf->Cell(20, 6, $pdo_daily, 1, 0, 'R');
                $pdf->Cell(20, 6, $uob_week, 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($vol_week, 2), 1, 0, 'R');
                $pdf->Cell(20, 6, $pdo_weekly, 1, 1, 'R');
            }

            $pdf->Ln(3);
        }

        // Save PDF
        $currentDateTime = currentDateTime();
        $currentDate = date('Y-m-d');
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
        $fileName = "SURVEY_DATA_{$teamId}_{$currentDateTime}.pdf";
        $path = $GLOBALS["SAVE_PDF_PATH"] . "/" . $currentDate;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $filePath = $path . "/$fileName";

        try {
            $pdf->Output('F', $filePath);
            $istatus = updateRecord($this->_dbConn, "tblvands_summary", "pdf_generated = ?", "summary_id = $summaryId", array(1));
            if ($istatus == 1) {
                $currentDateTime = date("Y-m-d H:i:s");
                $downloadPath = $GLOBALS["SAVE_PDF_URL"] . "/" . $currentDate . "/" . $fileName;
                // debug_log(
                //     "\r\nPDF Generated Successfully for Summary ID: $summaryId , Team ID: $teamId\r\n",
                //     $this->logFilename
                // );
                return array(1, $downloadPath);
            } else {
                // debug_log(
                //     "\r\nError: PDF Generated but Database Update Failed for Summary ID: $summaryId\r\n",
                //     $this->logFilename
                // );
                return 0;
            }
        } catch (Exception $e) {
            // debug_log(
            //     "\r\nError Creating PDF File for Summary ID: $summaryId\r\n",
            //     $this->logFilename
            // );
            return false;
        }
    }

    final public function generatePDF()
    {
        $currentDate = currentDate();
        $sDateCond = "AND a.activity_date = '$currentDate'";

        $cDT = currentDateTime();
        $cD = $currentDate;
        $notificationTable = $this->_tables["APP_NOTIFICATION_TABLE"];

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.summary_id, a.team_id, a.attendance_datetime, a.dayend_datetime FROM tblvands_summary as a , tblproject_team as b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.team_id = b.team_id AND a.attendance_datetime is not null AND a.dayend_datetime is not null" .
            " AND b.is_type in (0,5) AND a.pdf_generated = '0' $sDateCond LIMIT 30";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $summary_id = $row["summary_id"];
                $start_datetime = $row["attendance_datetime"];
                $end_datetime = $row["dayend_datetime"];
                $team_id = $row["team_id"];
                $notificationTitle = "Survey Summary";

                if (isset($start_datetime) && isset($end_datetime) && !empty($start_datetime) && !empty($end_datetime)) {
                    $pdfGenerated = $this->generatePDFForTeam($team_id, $start_datetime, $end_datetime, $summary_id);
                    if ($pdfGenerated[0] == 1) {
                        $actualPdfUrl = $pdfGenerated[1];

                        // Create complete tracking URL - use summary_id as the tracking ID
                        $trackingUrl = "https://upimg2.radardashboard.com/mobile_services/api/custom/track_pdf_access.php?sid=" . $summary_id . "&url=" . urlencode($actualPdfUrl);

                        $notificationText = $trackingUrl;

                        // Add summary_id to the notification record
                        $cols = "team_id, summary_id, notification_type, notification_title, notification_text, notification_date, notification_datetime, rcd, rdt";
                        $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrParams = array($team_id, $summary_id, 1, $notificationTitle, $notificationText, $cD, $cDT, $cD, $cDT);
                        $iStatus = addRecord($this->_dbConn, $notificationTable, $cols, $vals, $arrParams);
                    } else {
                        // debug_log(
                        //     "\r\nData Not Added to Notification Table for Team ID: $team_id date - $currentDate\r\n" .
                        //         $this->logFilename
                        // );
                    }
                }
            }
        }
    }
}

$generatePDFCronjob = new generatePDFCronjob($dbConn);
$generatePDFCronjob->generatePDF();
