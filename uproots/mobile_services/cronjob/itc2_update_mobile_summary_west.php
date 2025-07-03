<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/Itc2UpdateMobileSummary.php";

$itc2UpdateMobileSummary = new Itc2UpdateMobileSummary($dbConn, $tableUtil, $commonFunctions);

// update summary for branch 23, 24, 27, 28
$itc2UpdateMobileSummary->updateSummary(array(23, 24, 27, 28));
