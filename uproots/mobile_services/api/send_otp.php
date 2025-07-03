<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/Otp.php";

$otp = new Otp($dbConn, $tableUtil, $commonFunctions);
$otp->getUserAndSendOtp();
$dbConn->Close();
