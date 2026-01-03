<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ViewFocusBrandReporting.php";

if (!isEmptyString($requestAction)) {
    $team = new ViewFocusBrandReporting($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewTeamData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $team->viewTeams();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $team->editTeam();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
