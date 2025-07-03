<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/reporting/MasterDataReporting.php";

if (!isEmptyString($requestAction)) {
    $team = new ActiveSKUReporting($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewSKUData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $team->viewSKUData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $team->downloadMasterData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
