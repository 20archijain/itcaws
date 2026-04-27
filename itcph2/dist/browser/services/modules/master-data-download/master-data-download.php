<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/MasterDataDownload.php";

if (!isEmptyString($requestAction)) {
    $masterData = new MasterDataDownload($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $masterData->getData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $masterData->getMasterData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $masterData->getBranch();
            break;
        case $ACTION_LIST['GET_CIRCLE']:
            $masterData->getCircle();
            break;
        case $ACTION_LIST['GET_SECTION']:
            $masterData->getSection();
            break;
        case $ACTION_LIST['GET_WD_CODE']:
            $masterData->getWDCode();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $masterData->getTeamType();
            break;
        case $ACTION_LIST['GET_TEAM_LIST']:
            $masterData->getTeamList();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
