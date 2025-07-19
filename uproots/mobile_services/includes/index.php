<?php

$isLocalServer = isset($_SERVER["SERVER_NAME"]) && $_SERVER["SERVER_NAME"] == "localhost" ? true : false;

// LOCAL
if ($isLocalServer) {
    // For file include/upload
    $SITE_PATH = "C:/xampp/htdocs/work/itcawsportal";
} else {
    // PROD

    // For file include/upload
    $SITE_PATH = '/home/itcawsportal/public_html';
}

require_once "common_functions.php";
require_once "stdsettings.php";
require_once "messages.php";
require_once "labels.php";

$CLASSES_PATH = "$SITE_PATH/uproots/classes";
require_once $CLASSES_PATH . "/CommonFunctions.php";
require_once $CLASSES_PATH . "/DBConnection.php";
require_once $CLASSES_PATH . "/Response.php";
require_once $CLASSES_PATH . "/Utilities.php";
require_once $CLASSES_PATH . "/TableUtil.php";

// Create CommonFunctions object
$commonFunctions = new CommonFunctions();

// Create DB Connection
$dbConn = new DBConnection(constant("DB_NAME"), $DB_USERNAME, $DB_PASSWORD, $commonFunctions);

// Create TableUtil object
$tableUtil = new TableUtil($dbConn, $commonFunctions);

require_once "db_wise_details.php";
