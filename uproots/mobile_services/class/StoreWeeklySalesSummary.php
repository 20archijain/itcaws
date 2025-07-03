<?php

// phpcs:ignore
class StoreWeeklySalesSummary
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $rcd;
    private $branchProductsTable;
    private $respTable;
    private $weeklySalesSummaryTable;
    private $arrTeamBranch = [];
    private $arrBranchAndDateWiseSellingPrice = [];
    private $arrBranchWiseCurrentSellingPrice = [];
    private $arrrShopNames = [];

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->branchProductsTable = $GLOBALS["TBL_BRANCH_PRODUCTS"];
        $this->respTable = $GLOBALS["TBL_SURVEY_RESPONSE"];
        $this->weeklySalesSummaryTable = $GLOBALS["TBL_WEEKLY_SALES_SUMMARY"];
        $this->rcd = $this->commonFunctions->currentDate();
    }

    private function getProductColumns($dbName)
    {
        $arrSummaryColumns = array();
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT summary_column_name FROM $dbName.{$this->branchProductsTable}" .
            " WHERE dstatus = 0";
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                if ($row["summary_column_name"]) {
                    $arrSummaryColumns[] = $row["summary_column_name"];
                }
            }
        }

        return array(
            implode(", ", $arrSummaryColumns),
            implode(" + ", $arrSummaryColumns),
            $arrSummaryColumns
        );
    }

    private function getTeamsBranch($dbName)
    {
        $this->arrTeamBranch = $this->tableUtil->getRowsColumns(
            "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
            "team_id, branch_id",
            "",
            array(),
            false,
            true
        );
    }

    private function getShopName($dbName, $shopId)
    {
        if (!isset($this->arrrShopNames[$shopId])) {
            if ($shopId && is_numeric($shopId)) {
                $this->arrrShopNames[$shopId] = $this->tableUtil->getRowColumn(
                    "$dbName.{$GLOBALS["TBL_ROUTE_DETAILS"]}",
                    "outlet_name",
                    "rec_id = ?",
                    array($shopId)
                );
            } else {
                $this->arrrShopNames[$shopId] = $shopId;
            }
        }

        return $this->arrrShopNames[$shopId];
    }

    private function getBranchSellingPriceOfDay($dbName, $branchId, $captureDate, $sEachSummaryColumn)
    {
        // Get current selling price to use if no selling price found for branch and date
        if (!isset($arrBranchWiseCurrentSellingPrice[$branchId])) {
            $arrBranchWiseCurrentSellingPrice[$branchId] = $this->tableUtil->getRowsColumns(
                "$dbName.{$GLOBALS["TBL_BRANCH_PICKUPSTOCK_PRODUCTS"]}",
                "summary_column_name, selling_price",
                "dstatus = 0 AND branch_id = $branchId AND json_id = 11",
                array(),
                false,
                true
            );
        }

        if (!isset($this->arrBranchAndDateWiseSellingPrice[$branchId][$captureDate])) {
            $arrSellingPrices = $this->tableUtil->getRowColumns(
                "$dbName.{$GLOBALS["TBL_STOCK_SUMMARY"]}",
                $sEachSummaryColumn,
                "dstatus = 0 AND branch_id = $branchId AND capture_date = '$captureDate' AND stock_type = 1",
                array(),
                false,
                2
            );

            if (!($arrSellingPrices && $this->commonFunctions->isNonEmptyArray($arrSellingPrices))) {
                $arrSellingPrices = $arrBranchWiseCurrentSellingPrice[$branchId];
            }

            $this->arrBranchAndDateWiseSellingPrice[$branchId][$captureDate] = $arrSellingPrices;
        }

        return $this->arrBranchAndDateWiseSellingPrice[$branchId][$captureDate];
    }

    private function updateSaleInTable(
        $dbName,
        $sRouteName,
        $sMarketName,
        $shopId,
        $sShopName,
        $year,
        $month,
        $week,
        $salesAmount,
        $salesVolume,
        $billsCut
    ) {
        $shopId = is_numeric($shopId) ? $shopId : 0;

        // Check if record exists or not, if not, then add else update
        $ssId = $this->tableUtil->getRowColumn(
            "$dbName.$this->weeklySalesSummaryTable",
            "ss_id",
            "dstatus = 0 AND route = ? AND market = ? AND shop_id = ? AND year = ? AND month = ?",
            array($sRouteName, $sMarketName, $shopId, $year, $month)
        );

        // Not exist, so add
        if (!$ssId) {
            // Add in table
            $this->tableUtil->addRecord(
                "$dbName.{$this->weeklySalesSummaryTable}",
                "route, market, shop_id, shop_name, year, month, sales_amount_$week" .
                    ", sales_volume_$week, bills_cut_$week, rcd, rdt",
                "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
                array(
                    $sRouteName, $sMarketName, $shopId, $sShopName, $year,
                    $month, $salesAmount, $salesVolume, $billsCut, $this->rcd, $this->commonFunctions->currentDateTime()
                )
            );
        } elseif ($ssId) {
            // exists so update existing record

            // Update in table
            $this->tableUtil->updateRecord(
                "$dbName.{$this->weeklySalesSummaryTable}",
                "sales_amount_$week = (sales_amount_$week + ?)" .
                    ", sales_volume_$week = (sales_volume_$week + ?), bills_cut_$week = (bills_cut_$week + ?)",
                "ss_id = ?",
                array($salesAmount, $salesVolume, $billsCut, $ssId)
            );
        }
    }

    public function updateSalesSummary($dbName, $cond = "")
    {
        // Get product summary column names
        list($sEachSummaryColumn, $sSumOfAllSummaryColumns, $arrSummaryColumns) = $this->getProductColumns($dbName);

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT pro_id, team_id, capture_date, ques_1, ques_2, ques_4, $sEachSummaryColumn" .
            ", ($sSumOfAllSummaryColumns) AS salesVolume FROM $dbName.{$this->respTable}" .
            " WHERE dstatus = 0 AND update_sales_summary = 0 $cond LIMIT 100";
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $this->getTeamsBranch($dbName);

            while ($row = $this->dbConn->GetData($sAction)) {
                $proId = $row["pro_id"];
                $teamId = $row["team_id"];
                $captureDate = $row["capture_date"];
                $sRoute = $row["ques_1"];
                $arrRoute = json_decode($sRoute, true);
                $sRouteName = $arrRoute[0];
                $sMarketAndShop = $row["ques_2"];
                $arrMarketAndShop = json_decode($sMarketAndShop, true);
                $sMarketName = $arrMarketAndShop[0];
                $shopId = $arrMarketAndShop[1];
                $sShopName = $this->getShopName($dbName, $shopId);

                $sellInOrder = $row["ques_4"];
                $salesVolume = $row["salesVolume"];
                $year = date("Y", strtotime($captureDate));
                $month = date("n", strtotime($captureDate));
                $day = date("j", strtotime($captureDate));

                if ($day >= 1 && $day <= 7) {
                    $week = "week1";
                } elseif ($day >= 8 && $day <= 14) {
                    $week = "week2";
                } elseif ($day >= 15 && $day <= 21) {
                    $week = "week3";
                } else {
                    $week = "week4";
                }

                $salesAmount = 0;
                if ($salesVolume > 0) {
                    $branchId = $this->arrTeamBranch[$teamId];
                    $arrSellingPrices = $this->getBranchSellingPriceOfDay(
                        $dbName,
                        $branchId,
                        $captureDate,
                        $sEachSummaryColumn
                    );

                    // Calculate total sales on this shop
                    foreach ($arrSummaryColumns as $summaryColumnName) {
                        $iQty = isset($row[$summaryColumnName]) && floatval($row[$summaryColumnName]) ?
                            (float) $row[$summaryColumnName] : 0;
                        $iAmount = isset($arrSellingPrices[$summaryColumnName]) ?
                            $arrSellingPrices[$summaryColumnName] : 0;

                        $salesAmount += ($iQty * $iAmount);
                    }
                }

                // Add/Update sale in table
                $this->updateSaleInTable(
                    $dbName,
                    $sRouteName,
                    $sMarketName,
                    $shopId,
                    $sShopName,
                    $year,
                    $month,
                    $week,
                    $salesAmount,
                    $salesVolume,
                    strtolower($sellInOrder) == "yes" ? 1 : 0
                );

                // Update record status
                $this->tableUtil->updateRecord(
                    "$dbName.{$this->respTable}",
                    "update_sales_summary = 1",
                    "pro_id = $proId"
                );
            }
        }
    }
}
