<?php

// phpcs:ignore
class SnplUpdateMobileSummary
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
        $this->dbName = $GLOBALS["SNPL_DB"];
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

    final public function updateSummary()
    {
        // Delete old data for all teams
        $this->deleteBranchSummary();

        // Get time spent by each team
        $this->getTimeSpentTeamwise();

        // Get other outlet count shoptype wise by each team
        $this->getOtherShopTypeCountTeamwise();

        // Get ROC sell-in shops count by each team
        $this->getROCSellInShopsCountTeamwise();

        // Get Other Covered shops count by each team
        $this->getOtherCoveredShopsCountTeamwise();

        // Get assigned ROC Shops count to each team
        $this->getAssignedROCShopsCountTeamwise();

        // Get covered ROC Shops count by each team
        $this->getROCCoveredShopsCountTeamwise();

        // Add new data for all teams
        $this->addBranchSummary();
    }

    private function deleteBranchSummary()
    {
        $this->tableUtil->permanentDeleteRecord("{$this->dbName}.{$this->mobileSummaryTable}", "dstatus = 0");
    }

    private function addBranchSummary()
    {
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id FROM {$this->dbName}.{$this->projectTeamTable} WHERE dstatus = 0";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $cols = "team_id, time_spent_today, grocery_count_today, retail_count_today, wholesale_count_today" .
                ", roc_sell_in_shops_count_today, other_covered_today, other_covered_mtd, roc_total_shops_count" .
                ", roc_covered_today, roc_covered_mtd, rcd, rdt";
            $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                // Get required figures from all variables
                $timeSpentToday = isset($this->arrTimeSpentTeamWise[$teamId]) ?
                    $this->arrTimeSpentTeamWise[$teamId] : null;
                $todayOtherOutletMadiraCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["मदिरा पसल"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["मदिरा पसल"] : 0;
                $todayOtherOutletRetailCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["खुद्रा बिक्रेता"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["खुद्रा बिक्रेता"] : 0;
                $todayOtherOutletWholesaleCount = isset($this->arrOtherShopTypeCountTeamWise[$teamId]["थोक बिक्रेता"]) ?
                    $this->arrOtherShopTypeCountTeamWise[$teamId]["थोक बिक्रेता"] : 0;
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
                    $teamId, $timeSpentToday, $todayOtherOutletMadiraCount,
                    $todayOtherOutletRetailCount, $todayOtherOutletWholesaleCount, $todayROCSellinShopsCount,
                    $otherCoveredShopsTodayCount, $otherCoveredShopsMtdCount, $assignedROCShopsCount,
                    $rocCoveredShopsTodayCount, $rocCoveredShopsMtdCount, $this->currentDate, $this->currentDateTime
                );
                $this->tableUtil->addRecord("{$this->dbName}.{$this->mobileSummaryTable}", $cols, $vals, $arrParams);
            }
        }
    }

    private function getTimeSpentTeamwise()
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, start_datetime, end_datetime FROM {$this->dbName}.tblvands_summary" .
            " WHERE dstatus = 0 AND activity_date = '$sDate'";
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

    private function getOtherShopTypeCountTeamwise()
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;

        $sQuery = "SELECT team_id, ques_5, COUNT(pro_id) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'अरु पसलको बिक्री' AND ques_5 IN" .
            " ('मदिरा पसल', 'खुद्रा बिक्रेता', 'थोक बिक्रेता') {$this->otherSummaryCond} GROUP BY team_id, ques_5";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];
                $shopType = $row["ques_5"];

                $this->arrOtherShopTypeCountTeamWise[$teamId][$shopType] = $row["total"];
            }
        }
    }

    private function getROCSellInShopsCountTeamwise()
    {
        $sDate = $this->currentDate;

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(pro_id) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'रुटको बिक्री' AND ques_5 = 'चाहिन्छ'" .
            " {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrROCSellinShopsCountTeamWise[$teamId] = $row["total"];
            }
        }
    }

    private function getOtherCoveredShopsCountTeamwise()
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        // Today's count
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(DISTINCT ques_2, ques_3) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'अरु पसलको बिक्री'" .
            " {$this->otherSummaryCond} GROUP BY team_id";
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
        $sMtdQuery = "SELECT team_id, COUNT(DISTINCT ques_2, ques_3) AS total FROM" .
            " {$this->dbName}.{$this->respTable} WHERE dstatus = 0 AND capture_date LIKE '$sMonth'" .
            " AND ques_0 = 'अरु पसलको बिक्री' {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrOtherCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getAssignedROCShopsCountTeamwise()
    {
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(outlet_name) AS total FROM {$this->dbName}.{$this->routeDetailsTable}" .
            " WHERE dstatus = 0 AND outlet_type = 'ROC' {$this->otherSummaryCond} GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $teamId = $row["team_id"];

                $this->arrAssignedROCShopsCountTeamwise[$teamId] = $row["total"];
            }
        }
    }

    private function getROCCoveredShopsCountTeamwise()
    {
        $sDate = $this->currentDate;
        $sMonth = $this->currentMonth;

        // Today's count
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT team_id, COUNT(DISTINCT ques_2) AS total FROM {$this->dbName}.{$this->respTable}" .
            " WHERE dstatus = 0 AND capture_date = '$sDate' AND ques_0 = 'रुटको बिक्री' {$this->otherSummaryCond}" .
            " GROUP BY team_id";
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
            " WHERE dstatus = 0 AND capture_date LIKE '$sMonth' AND ques_0 = 'रुटको बिक्री' {$this->otherSummaryCond}" .
            " GROUP BY team_id";
        $this->dbConn->ExecuteSelectQuery($sMtdQuery, $rsMtdAction, $iMtdActionRows);

        if ($iMtdActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsMtdAction)) {
                $teamId = $row["team_id"];
                $this->arrROCCoveredShopsMtdCountTeamwise[$teamId] = $row["total"];
            }
        }
    }
}
