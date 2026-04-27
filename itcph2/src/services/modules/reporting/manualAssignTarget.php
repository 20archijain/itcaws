<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/reporting/ManualAssignTarget.php";

if (!isEmptyString($requestAction)) {
    $team = new ManualAssignTarget($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $team->getViewSKUData();
            break;
        case $ACTION_LIST['SUBMIT_DATA']:
            $team->submitData();
            break;
        case $ACTION_LIST['GET_PRODUCT']:
            $team->getProductList();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
