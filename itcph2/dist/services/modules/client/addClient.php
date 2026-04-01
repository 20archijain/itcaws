<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ClientManagement.php";

if (!isEmptyString($requestAction)) {
    $client = new ClientManagement($dbConn, $requestData, $arrAccessInfo, $jsonfiles, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $client->addClient();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
