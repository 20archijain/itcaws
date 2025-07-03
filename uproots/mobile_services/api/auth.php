<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

$login = new AppLogin($dbConn, $tableUtil, $commonFunctions);
$login->loginViaUsername();
$dbConn->Close();
