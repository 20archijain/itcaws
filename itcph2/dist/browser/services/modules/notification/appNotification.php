<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/notification/AppNotification.php";

if (!isEmptyString($requestAction)) {
    $notification = new AppNotification($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $notification->addNotification();
            break;
        case $ACTION_LIST['GET_DATA']:
            $notification->getData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $notification->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $notification->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $notification->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $notification->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $notification->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $notification->getTeamList();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
