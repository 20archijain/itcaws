<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/Auth.php";

if (!isEmptyString($requestAction)) {
    switch ($requestAction) {
        case $ACTION_LIST['LOGOUT']:
            $logout = new Auth($dbConn, $sessionMgmt, $requestData);
            $logout->logout();
            // destroy current session
            $sessionMgmt->destroySession();

            // start new session
            $sessionMgmt->startSession();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
