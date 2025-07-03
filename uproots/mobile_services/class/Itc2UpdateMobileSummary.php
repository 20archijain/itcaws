<?php

// phpcs:ignore
class Itc2UpdateMobileSummary
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $dbName = null;
    private $mobileSummaryTable = null;
    private $projectTeamTable = null;
    private $routeDetailsTable = null;
    private $respTable = null;
    private $otherSummaryCond = "";
    private $currentDate = null;
    private $currentDateTime = null;
    private $currentMonth = null;

    // data captured from table
    private $arrTimeSpentTeamWise = array();
    private $arrOtherShopTypeCountTeamWise = array();
    private $arrROCSellinShopsCountTeamWise = array();
    private $arrOtherCoveredShopsTodayCountTeamwise = array();
    private $arrOtherCoveredShopsMtdCountTeamwise = array();
    private $arrAssignedROCShopsCountTeamwise = array();
    private $arrROCCoveredShopsTodayCountTeamwise = array();
    private $arrROCCoveredShopsMtdCountTeamwise = array();

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->currentDate = $this->commonFunctions->currentDate();
        $this->currentDateTime = $this->commonFunctions->currentDateTime();
        $this->currentMonth = date("Y-m-") . "%";
        $this->dbName = $GLOBALS["ITC_DB"];
        $this->mobileSummaryTable = $GLOBALS["TBL_MOBILE_SUMMARY"];
        $this->projectTeamTable = $GLOBALS["TBL_PROJECT_TEAM"];
        $this->routeDetailsTable = $GLOBALS["TBL_ROUTE_DETAILS"];
        $this->respTable = $GLOBALS["TBL_SURVEY_RESPONSE"];

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

            // Get other outlet count shoptype wise by each team under given branches
            $this->getOtherShopTypeCountTeamwise($sBranch);

            // Get ROC sell-in shops count by each team under given branches
            $this->getROCSellInShopsCountTeamwise($sBranch);

            // Get Other Covered shops count by each team under given branches
            $this->getOtherCoveredShopsCountTeamwise($sBranch);

            // Get assigned ROC Shops count to each team under given branches
            $this->getAssignedROCShopsCountTeamwise($sBranch);

            // Get covered ROC Shops count by each team under given branches
            $this->getROCCoveredShopsCountTeamwise($sBranch);

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
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id = " .
            $branchId;
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $cols = "branch_id, team_id, time_spent_today, grocery_count_today, retail_count_today" .
                ", wholesale_count_today, roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd" .
                ", roc_total_shops_count, roc_covered_today, roc_covered_mtd, rcd, rdt";
            $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                // Get required figures from all variables
                $timeSpentToday = isset($this->arrTimeSpentTeamWise[$teamId]) ?
                    $this->arrTimeSpentTeamWise[$teamId] : null;
                $todayOtherOutletGroceryCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["Grocery"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["Grocery"] : 0;
                $todayOtherOutletRetailCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["Retail"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["Retail"] : 0;
                $todayOtherOutletWholesaleCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["Wholesale"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["Wholesale"] : 0;
                $todayROCSellinShopsCount = isset($this->arrROCSellinShopsCountTeamWise[$teamId]) ?
                    $this->arrROCSellinShopsCountTeamWise[$teamId] : 0;
                $otherCoveredShopsTodayCount = isset($this->arrOtherCoveredShopsTodayCountTeamwise[$teamId]) ?
                    $this->arrOtherCoveredShopsTodayCountTeamwise[$teamId] : 0;
                $otherCoveredShopsMtdCount = isset($this->arrOtherCoveredShopsMtdCountTeamwise[$teamId]) ?
                    $this->arrOtherCoveredShopsMtdCountTeamwise[$teamId] : 0;
                $assignedROCShopsCount = isset($this->arrAssignedROCShopsCountTeamwise[$teamId]) ?
                    $this->arrAssignedROCShopsCountTeamwise[$teamId] : 0;
                $rocCoveredShopsTodayCount = isset($this->arrROCCoveredShopsTodayCountTeamwise[$teamId]) ?
                    $this->arrROCCoveredShopsTodayCountTeamwise[$teamId] : 0;
                $rocCoveredShopsMtdCount = isset($this->arrROCCoveredShopsMtdCountTeamwise[$teamId]) ?
                    $this->arrROCCoveredShopsMtdCountTeamwise[$teamId] : 0;

                // Add record in table
                $arrParams = array(
                    $branchId, $teamId, $timeSpentToday, $todayOtherOutletGroceryCount,
                    $todayOtherOutletRetailCount, $todayOtherOutletWholesaleCount, $todayROCSellinShopsCount,
                    $otherCoveredShopsTodayCount, $otherCoveredShopsMtdCount, $assignedROCShopsCount,
                    $rocCoveredShopsTodayCount, $rocCoveredShopsMtdCount, $this->currentDate, $this->currentDateTime
                );
                $this->tableUtil->addRecord("{$this->dbName}.{$this->mobileSummaryTable}", $cols, $vals, $arrParams);
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

    private function getOtherShopTypeCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, ques_5, COUNT(pro_id) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'Other Outlet' AND ques_5 IN" .
            " ('Grocery', 'Retail', 'Wholesale') AND team_id IN (SELECT team_id FROM" .
            " {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id IN ($sBranch))" .
            " {$this->otherSummaryCond} GROUP BY team_id, ques_5";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $shopType = $row["ques_5"];

                $this->arrOtherShopTypeCountTeamWise[$teamId][$shopType] = $row["total"];
            }
        }
    }

    private function getROCSellInShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(pro_id) AS total FROM {$this->dbName}.{$this->respTable} WHERE" .
            " dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'ROC Delivery' AND ques_5 = 'Yes'" .
            " AND team_id IN (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrROCSellinShopsCountTeamWise[$teamId] = $row["total"];
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
        $sQuery = "SELECT team_id, COUNT(DISTINCT ques_2, ques_3) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'Other Outlet' AND team_id IN" .
            " (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";
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
        $sMtdQuery = "SELECT team_id, COUNT(DISTINCT ques_2, ques_3) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date LIKE '$sMonth' AND ques_0 = 'Other Outlet'" .
            " AND team_id IN (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrOtherCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getAssignedROCShopsCountTeamwise($sBranch)
    {
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(outlet_name) AS total FROM {$this->dbName}.{$this->routeDetailsTable}" .
            " WHERE dstatus = 0 AND outlet_type = 'ROC' AND team_id IN (SELECT team_id FROM" .
            " {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0 AND branch_id IN ($sBranch))" .
            " {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrAssignedROCShopsCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getROCCoveredShopsCountTeamwise($sBranch)
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        // Today's count
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(DISTINCT ques_2) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'ROC Delivery' AND team_id IN" .
            " (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $this->arrROCCoveredShopsTodayCountTeamwise[$teamId] = $row["total"];
            }
        }

        // MTD count
        $rsMtdAction = null;
        $iMtdActionRows = 0;
        $sMtdQuery = "SELECT team_id, COUNT(DISTINCT ques_2) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date LIKE '$sMonth' AND ques_0 = 'ROC Delivery' AND team_id IN" .
            " (SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0" .
            " AND branch_id IN ($sBranch)) {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrROCCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }
}
