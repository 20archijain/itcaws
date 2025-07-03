<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AwsRequest.php";
require_once "../class/MoveImagesToDigitalOcean.php";

$moveImagesToDigitalOcean = new MoveImagesToDigitalOcean($dbConn, $tableUtil, $commonFunctions);
$moveImagesToDigitalOcean->moveImagesOfDB($ITCNEW_DB, true);
$dbConn->Close();
