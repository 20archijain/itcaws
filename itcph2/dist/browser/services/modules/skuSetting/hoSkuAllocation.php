<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/skuSetting/HoSkuAllocation.php";

if (!isEmptyString($requestAction)) {
    $team = new HoSkuAllocation($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewSKUData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $team->getDefaultData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $team->downloadMasterData();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $team->editData();
            break;
        case $ACTION_LIST['GET_BRANCH']:
            $team->getRegion();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $team->getTeamType();
            break;
        case $ACTION_LIST['SUBMIT_DATA']:
            $team->submitData();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
