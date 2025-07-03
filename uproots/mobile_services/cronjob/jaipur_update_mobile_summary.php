<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/JaipurUpdateMobileSummary.php";

$jaipurUpdateMobileSummary = new JaipurUpdateMobileSummary($dbConn, $tableUtil, $commonFunctions);

// update summary for branch 1
$jaipurUpdateMobileSummary->updateSummary(array(1));
