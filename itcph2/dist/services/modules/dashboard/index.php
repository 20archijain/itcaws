<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/DashboardManagement.php";

if (!isEmptyString($requestAction)) {
    $dashboard = new DashboardManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DASHBOARD_DATA']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_DATA']:
            $dashboard->getData();
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
        case $ACTION_LIST['GET_GRAPH1']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH2']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH3']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH4']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH5']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH6']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH7']:
            $dashboard->getDashboardData();
            break;
        case $ACTION_LIST['GET_GRAPH8']:
            $dashboard->getDashboardData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
