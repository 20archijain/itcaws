<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/AssignTarget.php";

if (!isEmptyString($requestAction)) {
    $target = new AssignTarget($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $target->getData();
            break;
        case $ACTION_LIST['ADD_DATA']:
            $target->addData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
