<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/downloadReports/MdoTargetReport.php";

if (!isEmptyString($requestAction)) {
    $download = new MdoTargetReport($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $download->getData();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $download->getBranchTeamTypeList();
            break;
        case $ACTION_LIST['GET_PRODUCT_LIST']:
            $download->getProducts();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $download->getDownloadData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $download->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $download->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $download->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $download->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $download->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $download->getTeamList();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
