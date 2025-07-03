<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/MoveImagesToAnotherServer.php";

$moveImages = new MoveImagesToAnotherServer($dbConn, $tableUtil);
$moveImages->moveImagesOfProject(
    $ZX_DB,
    "AND dstatus = 0 AND pid = 83",
    $OUTABOX_SERVER_UPIMG_URL,
    $OUTABOX_SERVER_API_URL
);
$dbConn->Close();
