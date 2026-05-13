<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/SuperUserManagement.php";

if (!isEmptyString($requestAction)) {
    $user = new SuperUserManagement($dbConn, $requestData, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $user->addUser();
            break;
        case $ACTION_LIST['GET_DATA']:
            $user->getUserData(isset($requestData, $requestData["fromListing"]) ? $requestData["fromListing"] : false);
            break;
        case $ACTION_LIST['GET_LIST']:
            $user->viewUsers();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            deleteListingRecord($dbConn, $GLOBALS['TABLES']["USER_AUTHDETAILS_TABLE"], "user_id", $iUserId, "", $requestData, "id", false, false);
            deleteListingRecord($dbConn, $GLOBALS['TABLES']["USER_ACCESS_TABLE"], "user_id", $iUserId, "", $requestData, "id");
            break;
        case $ACTION_LIST['UNLOCK_DATA']:
            $user->unlockUsers();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $user->editUser();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
