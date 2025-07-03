<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/StoreCalendarDataAndSummary.php";

$teamIds = isset($_GET["team_ids"]) && $_GET["team_ids"] ? $_GET["team_ids"] : "";
$teamCondition = $teamIds ? "AND team_id IN ($teamIds)" : "";

$bUpdateIfExists = true;
if (isset($_GET["update"]) && $_GET["update"] == 0) {
    $bUpdateIfExists = false;
}

$storeCalendar = new StoreCalendarDataAndSummary($dbConn, $tableUtil, $commonFunctions, $bUpdateIfExists);
$storeCalendar->storeDBWiseStatus($GLOBALS['DELHI_DB'], $teamCondition);
$storeCalendar->storeDBWiseStatus($GLOBALS['ITC_DB'], $teamCondition);
$storeCalendar->storeDBWiseStatus($GLOBALS['SOUTH_DB'], $teamCondition);
$dbConn->Close();
