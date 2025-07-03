<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/GetOfflineDropdownOptions.php";
require_once "../class/StoreOfflineDropdownOptions.php";

$teamIds = isset($_GET["team_ids"]) && $_GET["team_ids"] ? $_GET["team_ids"] : "";
$teamCondition = $teamIds ? "AND team_id IN ($teamIds)" : "";

$bUpdateIfExists = false;
if ($teamIds) {
    $bUpdateIfExists = true;
}

$storeOptions = new StoreOfflineDropdownOptions($dbConn, $tableUtil, $commonFunctions, $bUpdateIfExists);
$storeOptions->storeDBWiseOptions($GLOBALS['ITCPH2_DB'], $teamCondition);
$dbConn->Close();
