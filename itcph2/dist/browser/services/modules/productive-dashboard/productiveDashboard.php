<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ProductiveDashboard.php";

if (!isEmptyString($requestAction)) {
    $dashboard = new ProductiveDashboard($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $dashboard->getData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $dashboard->getBranch();
            break;
        case $ACTION_LIST['GET_PRODUCT']:
            $dashboard->getProduct();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $dashboard->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $dashboard->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $dashboard->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $dashboard->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $dashboard->getTeamList();
            break;
        case $ACTION_LIST['GET_CM_LM']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_CM_LM_FOCUS']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_CM_LYM']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_CM_LYM_FOCUS']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_CY_LY']:
            $dashboard->getCardData();
            break;
        case $ACTION_LIST['GET_CY_LY_FOCUS']:
            $dashboard->getCardData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
