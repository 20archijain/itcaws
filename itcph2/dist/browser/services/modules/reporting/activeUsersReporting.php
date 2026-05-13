<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/reporting/ActiveUsersReporting.php";

if (!isEmptyString($requestAction)) {
    $team = new ActiveUsersReporting($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewDSData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $team->viewDSDetails();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $team->downloadDSDetails();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $team->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $team->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $team->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $team->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $team->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $team->getTeamList();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
