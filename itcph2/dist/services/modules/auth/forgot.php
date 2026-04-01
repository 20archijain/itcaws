<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/Auth.php";

if (!isEmptyString($requestAction)) {
    switch ($requestAction) {
        case $ACTION_LIST['FORGOT']:
            $forgot = new Auth($dbConn, $sessionMgmt, $requestData);
            $forgot->forgot();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
