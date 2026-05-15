<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/RouteManagement.php";

if (!isEmptyString($requestAction)) {
    $route = new RouteManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $route->getViewRouteData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $route->viewRoute();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $route->getTeam();
            break;
        case $ACTION_LIST['GET_ROUTE_LIST']:
            $route->getRoute();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            $route->deleteData($requestData, $iUserId);
            break;
        case $ACTION_LIST['RESTORE_DATA']:
            $route->restoredata($requestData, $iUserId);
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $route->editRoutedata($requestData, $iUserId);
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
