<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/downloadReports/LeaderboardReport.php";

if (!isEmptyString($requestAction)) {
    $leaderboard = new LeaderboardData($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $leaderboard->getData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $leaderboard->downloadDSDetails();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $leaderboard->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $leaderboard->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $leaderboard->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $leaderboard->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $leaderboard->getTeamList();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
