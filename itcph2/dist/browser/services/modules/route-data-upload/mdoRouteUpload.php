<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/MdoRouteUpload.php";

if (!isEmptyString($requestAction)) {
    $routeData = new MdoRouteUpload($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $routeData->uploadData();
            break;
        case $ACTION_LIST['GET_HEADER']:
            $routeData->getHeaderData();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
