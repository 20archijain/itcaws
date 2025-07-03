<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/WdMappingManagement.php";

if (!isEmptyString($requestAction)) {
    $wdMapping = new WdMappingManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_LIST']:
            $wdMapping->viewProjects();
            break;
        case $ACTION_LIST['GET_DATA']:
            $wdMapping->getViewProjectData();
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
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
