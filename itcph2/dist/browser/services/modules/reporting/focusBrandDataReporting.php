<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/reporting/FocusBrandDataReporting.php";

if (!isEmptyString($requestAction)) {
    $team = new FocusBrandDataReporting($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewSKUData();
            break;
            // case $ACTION_LIST['GET_LIST']:
            //     $team->viewSKUData();
            //     break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $team->downloadMasterData();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
