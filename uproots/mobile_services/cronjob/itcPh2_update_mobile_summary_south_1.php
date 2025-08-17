<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/ItcPh2UpdateMobileSummary.php";

$itcPh2UpdateMobileSummary = new ItcPh2UpdateMobileSummary($dbConn, $tableUtil, $commonFunctions);

// update summary for branch 31, 32,33, 34
$itcPh2UpdateMobileSummary->updateSummary(array(31, 32, 33, 34));
