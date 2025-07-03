<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/StoreWeeklySalesSummary.php";

$salesSummary = new StoreWeeklySalesSummary($dbConn, $tableUtil, $commonFunctions);
$salesSummary->updateSalesSummary($SOUTH_DB, "AND ques_0 = 'Order Capture'");
$dbConn->Close();
