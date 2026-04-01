<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/TeamManagement.php";

if (!isEmptyString($requestAction)) {
    $team = new TeamManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $team->addTeam();
            break;
        case $ACTION_LIST['GET_DATA']:
            $team->getAddTeamData();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $team->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $team->getSection();
            break;
        case $ACTION_LIST['GET_AE_NAME']:
            $team->getAeName();
            break;
        case $ACTION_LIST['GET_JSON']:
            $team->getJsonName();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
