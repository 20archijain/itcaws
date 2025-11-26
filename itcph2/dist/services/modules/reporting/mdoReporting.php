<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/reporting/MdoReporting.php";

if (!isEmptyString($requestAction)) {
    $reporting = new MdoReporting($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $reporting->getData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $reporting->getReportingData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $reporting->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $reporting->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $reporting->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $reporting->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $reporting->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $reporting->getTeamList();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $reporting->getDownloadData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_SUMMARY']:
            $reporting->getDownloadSummary();
            break;
        case $ACTION_LIST['ATTENDANCE_REPORT']:
            $reporting->attendanceDayEndReport();
            break;
        case $ACTION_LIST['PDF_REPORT']:
            $reporting->getDownloadPDFReport();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
