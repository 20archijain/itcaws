<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/RouteDataUpload.php";

if (!isEmptyString($requestAction)) {
    $routeData = new RouteDataUpload($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $routeData->uploadData();
            break;
        case $ACTION_LIST['GET_HEADER']:
            $routeData->getHeaderData();
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
