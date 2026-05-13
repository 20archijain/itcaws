<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/skuSetting/AllocationReport.php";

if (!isEmptyString($requestAction)) {
    $team = new AllocationReport($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getDefaultData();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
