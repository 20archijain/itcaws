<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/WdMappingManagement.php";

if (!isEmptyString($requestAction)) {
    $wdMapping = new WdMappingManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_LIST']:
            $wdMapping->viewWdMapping();
            break;
        case $ACTION_LIST['GET_DATA']:
            $wdMapping->getViewWDMappingData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $wdMapping->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $wdMapping->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $wdMapping->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $wdMapping->getWDCode();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $wdMapping->exportWdMapping();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
