<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/SnplUpdateMobileSummary.php";

$snplUpdateMobileSummary = new SnplUpdateMobileSummary($dbConn, $tableUtil, $commonFunctions);

// update summary
$snplUpdateMobileSummary->updateSummary();
