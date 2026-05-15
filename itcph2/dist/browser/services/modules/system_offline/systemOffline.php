<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/SystemOfflineManagement.php";

if (!isEmptyString($requestAction)) {
    $team = new SystemOfflineManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getData();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $team->getTeam();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            $team->deleteTeam();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
