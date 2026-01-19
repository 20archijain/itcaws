<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/TeamManagement.php";

if (!isEmptyString($requestAction)) {
    $team = new TeamManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewTeamData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $team->viewTeams();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $team->exportTeams();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $team->editTeam();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            deleteListingRecord($dbConn, $TABLES["CLOUD_AUTHPIN_TABLE"], "team_id", $iUserId, "AND db_name = '{$GLOBALS['DB_DBNAME']}'", $requestData, "id", true, false);
            deleteListingRecord($dbConn, $TABLES["PROJECT_TEAM_TABLE"], "team_id", $iUserId, "", $requestData, "id");
            deleteListingRecord($dbConn, "tblmdo_access", "teams", $iUserId, "", $requestData, "id");
            break;
        case $ACTION_LIST['RESTORE_DATA']:
            restoreListingRecord($dbConn, $TABLES["CLOUD_AUTHPIN_TABLE"], "team_id", $iUserId, "AND db_name = '{$GLOBALS['DB_DBNAME']}'", $requestData, "id", true, false);
            restoreListingRecord($dbConn, $TABLES["PROJECT_TEAM_TABLE"], "team_id", $iUserId, "", $requestData, "id");
            restoreListingRecord($dbConn, "tblmdo_access", "teams", $iUserId, "", $requestData, "id");
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
