<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/BreezeResponseUpload.php";

if (!isEmptyString($requestAction)) {
    $routeData = new BreezeResponseUpload($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $routeData->validateAndUploadData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $routeData->getDownloadData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
