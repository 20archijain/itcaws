<?php

// phpcs:ignore
class ItcPh2UpdateMobileSummary
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $dbName = null;
    private $mobileSummaryTable = null;
    private $projectTeamTable = null;
    private $routeDetailsTable = null;
    private $respTable = null;
    private $branchProductTable = null;
    private $vanDsSummaryTable = null;
    private $attendanceTable = null;
    private $mobilecalendarsummarykeyTable = null;
    private $otherSummaryCond = "";
    private $currentDate = null;
    private $currentDateTime = null;
    private $currentMonth = null;

    // data captured from table
    private $arrTimeSpentTeamWise = array();
    private $arrSellinShopsCountTeamWise = array();
    private $arrOtherSellinShopsCountTeamWise = array();
    private $arrOtherCoveredShopsTodayCountTeamwise = array();
    private $arrOtherCoveredShopsMtdCountTeamwise = array();
    private $arrCoveredShopsTodayCountTeamwise = array();
    private $arrCoveredShopsMtdCountTeamwise = array();
    private $arrTotalSalesTeamwise = array();
    private $arrTotalSalesMtdTeamwise = array();
    private $arrTotalPlannedTeamwise = array();
    private $arrTotalPlannedMtdTeamwise = array();
    private $arrSellinShopsCountMtdTeamWise = array();
    private $arrOtherSellinShopsCountMtdTeamWise = array();
    private $arrTotalSalesCountOfCurrentMonthTeamWise = array();
    private $arrTotalSalesCountOfPreviousMonthTeamWise = array();
    private $arrBranchwiseProducts = [];

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->currentDate = $this->commonFunctions->currentDate();
        $this->currentDateTime = $this->commonFunctions->currentDateTime();
        $this->currentMonth = date("Y-m-") . "%";
        $this->dbName = $GLOBALS["ITCPH2_DB"];
        $this->mobileSummaryTable = $GLOBALS["TBL_MOBILE_SUMMARY"];
        $this->projectTeamTable = $GLOBALS["TBL_PROJECT_TEAM"];
        $this->routeDetailsTable = $GLOBALS["TBL_ROUTE_DETAILS"];
        $this->respTable = $GLOBALS["TBL_SURVEY_RESPONSE"];
        $this->attendanceTable = $GLOBALS["TBL_ATTENDANCE"];
        $this->branchProductTable = $GLOBALS["TBL_BRANCH_PICKUPSTOCK_PRODUCTS"];
        $this->vanDsSummaryTable = $GLOBALS["TBL_VANDS_SUMMARY"];
        $this->mobilecalendarsummarykeyTable = $GLOBALS["TBL_MOBILE_CALENDAR_SUMMARY_KEYDETAILS"];

        $sClient_ID = 1;
        $sProject_ID = 1;
        $arrDBProjectDetails = $GLOBALS["arrDBProjectDetails"];

        $arrProjectSummaryDetails = isset($arrDBProjectDetails[$this->dbName][$sClient_ID][$sProject_ID]["summary"]) &&
            $this->commonFunctions->isNonEmptyArray($arrDBProjectDetails[$this->dbName][$sClient_ID][$sProject_ID]["summary"]) ?
            $arrDBProjectDetails[$this->dbName][$sClient_ID][$sProject_ID]["summary"]
            : (isset($arrDBProjectDetails[$this->dbName][0][0]["summary"]) &&
                $this->commonFunctions->isNonEmptyArray($arrDBProjectDetails[$this->dbName][0][0]["summary"]) ?
                $arrDBProjectDetails[$this->dbName][0][0]["summary"] : null);

        $this->otherSummaryCond = isset($arrProjectSummaryDetails["otherSummaryCond"]) &&
            $arrProjectSummaryDetails["otherSummaryCond"] ? $arrProjectSummaryDetails["otherSummaryCond"] : "";
    }

    final public function updateSummary($arrBranch)
    {
        $arrBranch = $arrBranch ? (is_string($arrBranch) ? array($arrBranch) : $arrBranch) : array();

        if ($this->commonFunctions->isNonEmptyArray($arrBranch)) {
            $sBranch = implode(",", $arrBranch);

            // Delete old data for all teams under given branches
            $this->deleteBranchSummary($sBranch);

            // Get time spent by each team under given branches
            $this->getTimeSpentTeamwise($sBranch);

            // Get ROC sell-in shops count by each team under given branches
            $this->getSellInShopsCountTeamwise($sBranch);

            // Get covered ROC Shops count by each team under given branches
            $this->getCoveredShopsCountTeamwise($sBranch);

            // Get Other sell-in shops count by each team under given branches
            $this->getOtherSellInShopsCountTeamwise($sBranch);

            // Get Other Covered shops count by each team under given branches
            $this->getOtherCoveredShopsCountTeamwise($sBranch);

            // Get Total Sales by each team under given branches
            $this->getTotalSalesCountTeamWise($sBranch);

            // Get Total Planned Outlets by each team under given branches
            $this->getPlannedOutletsTeamwise($sBranch);

            // Get sale of each day of current month by each team under given branches
            $this->getTotalSalesCountOfCurrentMonthTeamWise($sBranch);

            // // Get sale of each day of Previous month by each team under given branches
            $this->getTotalSalesCountOfPreviousMonthTeamWise($sBranch);

            // Add new data for all teams under given branches
            foreach ($arrBranch as $branchId) {
                $this->addBranchSummary($branchId);
            }
        }
    }

    private function deleteBranchSummary($sBranch)
    {
        $isBranchDataExist = $this->tableUtil->isRecordExist(
            "{$this->dbName}.{$this->mobileSummaryTable}",
            "ms_id",
            "dstatus = 0 AND branch_id IN ($sBranch)"
        );

        if ($isBranchDataExist > 0) {
            $this->tableUtil->permanentDeleteRecord("{$this->dbName}.{$this->mobileSummaryTable}", "dstatus = 0 AND branch_id IN ($sBranch)");
        }
    }

    private function addBranchSummary($branchId)
    {
        $sDate = $this->currentDate;
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id = " .
            $branchId;
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $cols = "branch_id, team_id, total_meter_travelled, time_spent_today, planned_outlets, planned_outlets_mtd, sell_in_shops_count_today, sell_in_shops_count_mtd, oulet_covered_today, oulet_covered_mtd, other_sell_in_shops_count_today, other_sell_in_shops_count_mtd" .
                ", add_oulet_covered_today, add_oulet_covered_mtd, total_sales_today, total_sales_mtd, chart_response_current_month, chart_response_previous_month, rcd, rdt";
            $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $meterTravelled = $this->tableUtil->getRowColumn("{$this->dbName}.{$this->vanDsSummaryTable}", "total_meter_travelled", "team_id = $teamId AND activity_date = '$sDate'");

                // Get required figures from all variables
                $timeSpentToday = isset($this->arrTimeSpentTeamWise[$teamId]) ?
                    $this->arrTimeSpentTeamWise[$teamId] : null;
                $kMeterTravelled = isset($meterTravelled) ?
                    $meterTravelled : 0;
                $todaySellinShopsCount = isset($this->arrSellinShopsCountTeamWise[$teamId]) ?
                    $this->arrSellinShopsCountTeamWise[$teamId] : 0;
                $todayOtherSellinShopsCount = isset($this->arrOtherSellinShopsCountTeamWise[$teamId]) ?
                    $this->arrOtherSellinShopsCountTeamWise[$teamId] : 0;
                $otherCoveredShopsTodayCount = isset($this->arrOtherCoveredShopsTodayCountTeamwise[$teamId]) ?
                    $this->arrOtherCoveredShopsTodayCountTeamwise[$teamId] : 0;
                $otherCoveredShopsMtdCount = isset($this->arrOtherCoveredShopsMtdCountTeamwise[$teamId]) ?
                    $this->arrOtherCoveredShopsMtdCountTeamwise[$teamId] : 0;
                $CoveredShopsTodayCount = isset($this->arrCoveredShopsTodayCountTeamwise[$teamId]) ?
                    $this->arrCoveredShopsTodayCountTeamwise[$teamId] : 0;
                $CoveredShopsMtdCount = isset($this->arrCoveredShopsMtdCountTeamwise[$teamId]) ?
                    $this->arrCoveredShopsMtdCountTeamwise[$teamId] : 0;
                $totalSalesTodayCount = isset($this->arrTotalSalesTeamwise[$teamId]) ?
                    $this->arrTotalSalesTeamwise[$teamId] : 0;
                $totalSalesMtdCount = isset($this->arrTotalSalesMtdTeamwise[$teamId]) ?
                    $this->arrTotalSalesMtdTeamwise[$teamId] : 0;
                $totalPlannedCount = isset($this->arrTotalPlannedTeamwise[$teamId]) ?
                    $this->arrTotalPlannedTeamwise[$teamId] : 0;
                $totalPlannedMtdCount = isset($this->arrTotalPlannedMtdTeamwise[$teamId]) ?
                    $this->arrTotalPlannedMtdTeamwise[$teamId] : 0;
                $totalSellInMtdCount = isset($this->arrSellinShopsCountMtdTeamWise[$teamId]) ?
                    $this->arrSellinShopsCountMtdTeamWise[$teamId] : 0;
                $totalSellInAddOutletMtdCount = isset($this->arrOtherSellinShopsCountMtdTeamWise[$teamId]) ?
                    $this->arrOtherSellinShopsCountMtdTeamWise[$teamId] : 0;
                $totalSellCountOfCurrentMonth = json_encode(isset($this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId]) ?
                    $this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId] : 0, true);
                $totalSellCountOfPreviousMonth = json_encode(isset($this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId]) ?
                    $this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId] : 0, true);

                // Add record in table
                $arrParams = array(
                    $branchId,
                    $teamId,
                    $kMeterTravelled,
                    $timeSpentToday,
                    $totalPlannedCount,
                    $totalPlannedMtdCount,
                    $todaySellinShopsCount,
                    $totalSellInMtdCount,
                    $CoveredShopsTodayCount,
                    $CoveredShopsMtdCount,
                    $todayOtherSellinShopsCount,
                    $totalSellInAddOutletMtdCount,
                    $otherCoveredShopsTodayCount,
                    $otherCoveredShopsMtdCount,
                    $totalSalesTodayCount,
                    $totalSalesMtdCount,
                    $totalSellCountOfCurrentMonth,
                    $totalSellCountOfPreviousMonth,
                    $sDate,
                    $this->currentDateTime
                );
                $this->tableUtil->addRecord("{$this->dbName}.{$this->mobileSummaryTable}", $cols, $vals, $arrParams);

                $status = $this->tableUtil->isRecordExist("{$this->dbName}.{$this->mobilecalendarsummarykeyTable}", "ms_id", "branch_id = $branchId AND team_id = $teamId AND rcd = '$sDate'");
                $arrParams2 = array($kMeterTravelled, $timeSpentToday, $totalPlannedCount, $todaySellinShopsCount, $CoveredShopsTodayCount, $todayOtherSellinShopsCount, $otherCoveredShopsTodayCount, $totalSalesTodayCount);
                if ($status == 1) {
                    $this->tableUtil->updateRecord(
                        "{$this->dbName}.{$this->mobilecalendarsummarykeyTable}",
                        "total_meter_travelled = ?, time_spent_today = ?, planned_outlets = ?, sell_in_shops_count_today = ?, oulet_covered_today = ?, other_sell_in_shops_count_today = ?, add_oulet_covered_today = ?, total_sales_today = ?",
                        "branch_id = $branchId AND team_id = $teamId AND rcd = '$sDate'",
                        $arrParams2
                    );
                } else {
                    $columns = "branch_id, team_id, total_meter_travelled, time_spent_today, planned_outlets, sell_in_shops_count_today, oulet_covered_today, other_sell_in_shops_count_today, add_oulet_covered_today, total_sales_today, rcd, rdt";
                    $valus = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                    $arrParams3 = array($branchId, $teamId, $kMeterTravelled, $timeSpentToday, $totalPlannedCount, $todaySellinShopsCount, $CoveredShopsTodayCount, $todayOtherSellinShopsCount, $otherCoveredShopsTodayCount, $totalSalesTodayCount, $sDate, $this->currentDateTime);
                    $this->tableUtil->addRecord("{$this->dbName}.{$this->mobilecalendarsummarykeyTable}", $columns, $valus, $arrParams3);
                }
            }
        }
    }

    private function getTimeSpentTeamwise($sBranch)
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, start_datetime, end_datetime FROM {$this->dbName}.tblvands_summary WHERE" .
            " dstatus = 0 AND activity_date = '$sDate' AND team_id IN (SELECT team_id FROM" .
            " {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id IN ($sBranch))";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $arrTime = array($row["start_datetime"], $row["end_datetime"]);
                $timeSpent = $this->commonFunctions->getTimeDifference($arrTime[0], $arrTime[1], false, false, true);

                $this->arrTimeSpentTeamWise[$teamId] = $timeSpent;
            }
        }
    }

    private function getPlannedOutletsTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, other_details FROM {$this->dbName}.{$this->attendanceTable} WHERE" .
            " dstatus = 0 AND capture_date = '$sDate'" .
            " AND team_id IN (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond}";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $otherDetails = $row["other_details"];
                $todayRoute = json_decode($otherDetails, true);
                $route = $todayRoute["route"][0];
                $routeCount = $this->tableUtil->getRowColumn("{$this->dbName}.{$this->routeDetailsTable}", "COUNT(rec_id)", "dstatus = 0 AND route_name = '$route' AND team_id = $teamId");

                $this->arrTotalPlannedTeamwise[$teamId] = $routeCount;
            }
        }

        $rsActionMtd = null;
        $iActionRowsMtd = 0;
        $sQueryMtd = "SELECT team_id, COUNT(rec_id) AS total FROM {$this->dbName}.{$this->routeDetailsTable} WHERE" .
            " dstatus = 0 AND team_id IN (SELECT team_id FROM" .
            " {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";

        $this->dbConn->ExecuteSelectQuery($sQueryMtd, $rsActionMtd, $iActionRowsMtd);
        if ($iActionRowsMtd > 0) {
            while ($row = $this->dbConn->GetData($rsActionMtd)) {
                $teamId = $row["team_id"];
                $routeCount = $row["total"];

                // Store the total count for the team
                $this->arrTotalPlannedMtdTeamwise[$teamId] = $routeCount;
            }
        }
    }

    private function getSellInShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        $summaryCols = $this->tableUtil->getRowsColumn("{$this->dbName}.{$this->branchProductTable}", "summary_column_name", "dstatus = 0 AND branch_id IN ($sBranch)");
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(a.", $summaryCols);
        $sumColumns = "SUM(a.$summaryColumns)";
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total, $sumColumns AS total_sales FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE" .
            " a.dstatus = 0 AND a.capture_date = '$sDate' AND a.ques_0 = 'Outlet Order' AND a.ques_4 = 'Yes'" .
            " AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id HAVING total_sales > 0";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrSellinShopsCountTeamWise[$teamId] = $row["total"];
            }
        }

        $rsActionMtd = null;
        $iActionRowsMtd = 0;
        $sQueryMtd = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total, $sumColumns AS total_sales FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE" .
            " a.dstatus = 0 AND a.capture_date LIKE '$sMonth' AND a.ques_0 = 'Outlet Order' AND a.ques_4 = 'Yes'" .
            " AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id HAVING total_sales > 0";
        $this->dbConn->ExecuteSelectQuery($sQueryMtd, $rsActionMtd, $iActionRowsMtd);

        if ($iActionRowsMtd > 0) {
            while ($row = $this->dbConn->GetData($rsActionMtd)) {
                $teamId = $row["team_id"];

                $this->arrSellinShopsCountMtdTeamWise[$teamId] = $row["total"];
            }
        }
    }

    private function getCoveredShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        // Today's count
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b" .
            " WHERE a.dstatus = 0 AND a.capture_date = '$sDate' AND a.ques_0 = 'Outlet Order' AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $this->arrCoveredShopsTodayCountTeamwise[$teamId] = $row["total"];
            }
        }

        // MTD count
        $rsMtdAction = null;
        $iMtdActionRows = 0;
        $sMtdQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b" .
            " WHERE a.dstatus = 0 AND a.capture_date LIKE '$sMonth' AND a.ques_0 = 'Outlet Order' AND a.team_id= b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getOtherSellInShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        $summaryCols = $this->tableUtil->getRowsColumn("{$this->dbName}.{$this->branchProductTable}", "summary_column_name", "dstatus = 0 AND branch_id IN ($sBranch)");
        // Prepare SUM columns for SQL
        $summaryColumns = implode(") + SUM(a.", $summaryCols);
        $sumColumns = "SUM(a.$summaryColumns)";
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total, $sumColumns AS total_sales FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE" .
            " a.dstatus = 0 AND a.capture_date = '$sDate' AND a.ques_0 = 'Add Outlet' AND a.ques_4 = 'Yes'" .
            " AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id HAVING total_sales > 0";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrOtherSellinShopsCountTeamWise[$teamId] = $row["total"];
            }
        }

        $rsActionMtd = null;
        $iActionRowsMtd = 0;
        $sQueryMtd = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total, $sumColumns AS total_sales FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE" .
            " a.dstatus = 0 AND a.capture_date LIKE '$sMonth' AND a.ques_0 = 'Add Outlet' AND a.ques_4 = 'Yes'" .
            " AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id HAVING total_sales > 0";
        $this->dbConn->ExecuteSelectQuery($sQueryMtd, $rsActionMtd, $iActionRowsMtd);

        if ($iActionRowsMtd > 0) {
            while ($row = $this->dbConn->GetData($rsActionMtd)) {
                $teamId = $row["team_id"];

                $this->arrOtherSellinShopsCountMtdTeamWise[$teamId] = $row["total"];
            }
        }
    }

    private function getOtherCoveredShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        // Today's count
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b" .
            " WHERE a.dstatus = 0 AND a.capture_date = '$sDate' AND a.ques_0 = 'Add Outlet' AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $this->arrOtherCoveredShopsTodayCountTeamwise[$teamId] = $row["total"];
            }
        }

        // MTD count
        $rsMtdAction = null;
        $iMtdActionRows = 0;
        $sMtdQuery = "SELECT a.team_id, COUNT(DISTINCT a.ques_3) AS total FROM {$this->dbName}.{$this->respTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b" .
            " WHERE a.dstatus = 0 AND a.capture_date LIKE '$sMonth' AND a.ques_0 = 'Add Outlet'" .
            " AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrOtherCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getTotalSalesCountTeamWise($sBranch)
    {
        $allBrandCols = $this->tableUtil->getRowsColumn("{$this->dbName}.{$this->branchProductTable}", "summary_column_name", "dstatus = 0 AND branch_id IN ($sBranch)", array(), true);
        $summaryColumns = implode(") + SUM(a.", $allBrandCols);
        $sumColumns = "SUM(a.$summaryColumns)";

        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.team_id, $sumColumns AS totalSum FROM {$this->dbName}.{$this->vanDsSummaryTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} AND a.activity_date = '$sDate' GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                $teamId = $row["team_id"];
                $totalSum = floatval($row['totalSum']);

                $this->arrTotalSalesTeamwise[$teamId] = $totalSum;
            }
        }

        $sMtdAction = null;
        $iMtdRows = 0;
        $sMtdQuery = "SELECT a.team_id, $sumColumns AS totalSum FROM {$this->dbName}.{$this->vanDsSummaryTable} AS a, {$this->dbName}.{$this->projectTeamTable} AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id" .
            " AND b.branch_id IN ($sBranch) {$this->otherSummaryCond} AND a.activity_date LIKE '$sMonth' GROUP BY a.team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $sMtdAction, $iMtdRows);

        if ($iMtdRows > 0) {
            $totalProductSale = 0;
            while ($row = $this->dbConn->GetData($sMtdAction)) {
                $teamId = $row["team_id"];
                $totalSum = floatval($row['totalSum']);

                $this->arrTotalSalesMtdTeamwise[$teamId] = $totalSum;
            }
        }
    }

    private function getTotalSalesCountOfCurrentMonthTeamWise($sBranch)
    {

        $allBrandCols = $this->tableUtil->getRowsColumn("{$this->dbName}.{$this->branchProductTable}", "summary_column_name", "dstatus = 0 AND branch_id IN ($sBranch)", array(), true);
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";
        // Get current month, year, and today's date
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDay = date('d'); // Get today's day number

        // Initialize sales data for all days with 0 sales for all teams
        $teamIds = $this->getAllTeams($sBranch);
        foreach ($teamIds as $teamId) {
            for ($day = 1; $day <= $currentDay; $day++) { // Iterate only up to the current day
                $formattedDay = $day;
                if (!isset($this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId])) {
                    $this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId] = [];
                }
                // Initialize each day's sales with 0
                $this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId][] = array(
                    "x" => $formattedDay,
                    "y" => 0  // Default value
                );
            }
        }

        // Maintain cumulative sales totals for each team
        $cumulativeSales = [];

        // Loop through all days up to the current day to get actual sales
        for ($day = 1; $day <= $currentDay; $day++) {
            $formattedDay = $day;
            $date = $currentYear . '-' . $currentMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT); // Format date as YYYY-MM-DD

            $sAction = null;
            $iRows = 0;
            // Query to get total sales for the current day
            $sQuery = "SELECT team_id, $sumColumns AS totalSum FROM {$this->dbName}.{$this->vanDsSummaryTable} WHERE dstatus = 0 AND team_id IN" .
                " (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
                " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} AND activity_date = '$date' GROUP BY team_id, activity_date";
            $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                $totalProductSale = 0;
                while ($row = $this->dbConn->GetData($sAction)) {
                    $teamId = $row['team_id'];
                    $totalProductSale = $row['totalSum'];

                    // Add sales of the current day to the cumulative sales
                    if (!isset($cumulativeSales[$teamId])) {
                        $cumulativeSales[$teamId] = 0; // Initialize cumulative sales for the team
                    }
                    $cumulativeSales[$teamId] += $totalProductSale;
                }
            }

            foreach ($teamIds as $teamId) {
                if (!isset($cumulativeSales[$teamId])) {
                    $cumulativeSales[$teamId] = 0;
                }
                // Update the sales value in the initialized array for the current day
                foreach ($this->arrTotalSalesCountOfCurrentMonthTeamWise[$teamId] as &$dayEntry) {
                    if ($dayEntry['x'] === $formattedDay) {
                        $dayEntry['y'] = round($cumulativeSales[$teamId], 0);
                        break;
                    }
                }
            }
        }

        // Now $this->arrTotalSalesCountOfCurrentMonthTeamWise will hold cumulative data for all teams up to the current date
        return $this->arrTotalSalesCountOfCurrentMonthTeamWise;
    }

    private function getTotalSalesCountOfPreviousMonthTeamWise($sBranch)
    {
        $allBrandCols = $this->tableUtil->getRowsColumn("{$this->dbName}.{$this->branchProductTable}", "summary_column_name", "dstatus = 0 AND branch_id IN ($sBranch)", array(), true);
        $summaryColumns = implode(") + SUM(", $allBrandCols);
        $sumColumns = "SUM($summaryColumns)";

        // Calculate the previous month and year
        $previousMonth = date('m', strtotime("first day of previous month"));
        $previousYear = date('Y', strtotime("first day of previous month"));

        // Get the number of days in the previous month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $previousMonth, $previousYear);

        // Initialize sales data for all days with 0 sales for all teams
        $teamIds = $this->getAllTeams($sBranch);
        foreach ($teamIds as $teamId) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $formattedDay = $day;
                if (!isset($this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId])) {
                    $this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId] = [];
                }
                // Initialize each day's sales with 0
                $this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId][] = array(
                    "x" => $formattedDay,
                    "y" => 0  // Default value
                );
            }
        }

        // Maintain cumulative sales totals for each team
        $cumulativeSales = [];

        // Loop through all days of the previous month to get actual sales
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $formattedDay = $day;
            $date = $previousYear . '-' . $previousMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT); // Format date as YYYY-MM-DD

            $sAction = null;
            $iRows = 0;
            // Query to get total sales for the current day
            $sQuery = "SELECT team_id, $sumColumns AS totalSum FROM {$this->dbName}.{$this->vanDsSummaryTable} WHERE dstatus = 0 AND team_id IN" .
                " (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
                " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} AND activity_date = '$date' GROUP BY team_id";
            $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                $totalProductSale = 0;
                while ($row = $this->dbConn->GetData($sAction)) {
                    $teamId = $row['team_id'];
                    $totalProductSale = $row['totalSum'];

                    // Add sales of the current day to the cumulative sales
                    if (!isset($cumulativeSales[$teamId])) {
                        $cumulativeSales[$teamId] = 0; // Initialize cumulative sales for the team
                    }
                    $cumulativeSales[$teamId] += $totalProductSale;
                }
            }

            // Update cumulative sales for teams with no sales on the current day
            foreach ($teamIds as $teamId) {
                if (!isset($cumulativeSales[$teamId])) {
                    $cumulativeSales[$teamId] = 0;
                }

                // Find the matching day entry in the initialized array and update the sales value
                foreach ($this->arrTotalSalesCountOfPreviousMonthTeamWise[$teamId] as &$dayEntry) {
                    if ($dayEntry['x'] === $formattedDay) {
                        $dayEntry['y'] = round($cumulativeSales[$teamId]); // Carry forward cumulative sales
                        break;
                    }
                }
            }
        }

        // Now $this->arrTotalSalesCountOfPreviousMonthTeamWise will hold data for all teams and all days of the previous month
        return $this->arrTotalSalesCountOfPreviousMonthTeamWise;
    }

    private function getAllTeams($sBranch)
    {
        $sQuery = "SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id IN ($sBranch)";
        $sAction = null;
        $iRows = 0;
        $teamIds = [];
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                $teamIds[] = $row['team_id'];
            }
        }

        return $teamIds;
    }

    private function getBranchWiseProducts($branchId = null, $productsList = true, $teamType = null)
    {
        if ($branchId) {
            if ($teamType !== null && $teamType !== "") {
                if ($productsList) {
                    return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
                } else {
                    return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
                }
            } else {
                if ($productsList) {
                    return $this->arrBranchwiseProducts[$branchId] ?? [];
                } else {
                    return $this->arrBranchwiseProducts[$branchId] ?? [];
                }
            }
        } else {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = $this->tableUtil->getRowsColumns(
                    "{$this->dbName}.{$this->branchProductTable}",
                    "branch_id, product_name, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' ORDER BY product_name",
                    array(),
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $productName = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->arrBranchwiseProducts[$branchId][$teamType])) {
                        $this->arrBranchwiseProducts[$branchId][$teamType] = [];
                    }
                    $this->arrBranchwiseProducts[$branchId][$teamType][] = [$productName, $summaryColumnName];
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name FROM {$this->dbName}.{$this->branchProductTable} WHERE dstatus = 0 ORDER BY product_name";
                $this->dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];

                        if (!isset($this->arrBranchwiseProducts[$branchId])) {
                            $this->arrBranchwiseProducts[$branchId] = [];
                        }
                        $this->arrBranchwiseProducts[$branchId][] = [$rowProduct["product_name"], $rowProduct["summary_column_name"]];
                    }
                }
            }
        }
    }
}
