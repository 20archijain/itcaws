<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ClientManagement.php";

if (!isEmptyString($requestAction)) {
    $client = new ClientManagement($dbConn, $requestData, $arrAccessInfo, "", $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $client->getData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $client->viewClients();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $client->editClient();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            deleteListingRecord($dbConn, $TABLES["CLIENTS_TABLE"], "client_id", $iUserId, "", $requestData, "id");
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
