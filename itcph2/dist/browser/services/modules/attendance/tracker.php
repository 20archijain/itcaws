<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/AttendanceManagement.php";

if (!isEmptyString($requestAction)) {
    $attendance = new AttendanceManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $attendance->getAttendanceTrackerData();
            break;
            // case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            //     $attendance->getBranchTeamTypeList();
            //     break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $attendance->getTeamList();
            break;
        case $ACTION_LIST['GET_LIST']:
            $attendance->viewAttendanceTracker();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $attendance->getDownloadData();
            break;
        case $ACTION_LIST['DELETE_IMAGE']:
            $attendance->deleteAttendance();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $attendance->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $attendance->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $attendance->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $attendance->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $attendance->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $attendance->getTeamList();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
