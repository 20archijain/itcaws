<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/SuperUserManagement.php";

if (!isEmptyString($requestAction)) {
    $user = new SuperUserManagement($dbConn, $requestData, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $user->addModule();
            break;
        case $ACTION_LIST['GET_DATA']:
            $user->getModuleData();
            break;
        case $ACTION_LIST['GET_LIST']:
            $user->viewModules();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            deleteListingRecord($dbConn, $GLOBALS['TABLES']["MODULES_TABLE"], "module_id", $iUserId, "", $requestData, "id");
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $user->editModule();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
