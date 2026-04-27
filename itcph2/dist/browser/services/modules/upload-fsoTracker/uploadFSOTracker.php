<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/UploadFSOTracker.php";

if (!isEmptyString($requestAction)) {
    $reporting = new UploadFSOTracker($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $reporting->uploadData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $reporting->downloadExcelHeaders();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
