<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/SitesOnMapManagement.php";

if (!isEmptyString($requestAction)) {
    $sites = new SitesOnMapManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $sites->getData(true);
            break;
        case $ACTION_LIST['GET_LIST']:
            $sites->getLocationCoveredData();
            break;
        case $ACTION_LIST['GET_ROUTE_DATA']:
            $sites->getRouteTrackerData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $sites->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $sites->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $sites->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $sites->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $sites->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $sites->getTeamList();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
