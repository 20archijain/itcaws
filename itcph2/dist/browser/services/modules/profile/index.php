<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ProfileManagement.php";

if (!isEmptyString($requestAction)) {
    $profile = new ProfileManagement($dbConn, $requestData, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['EDIT_DATA']:
            $profile->editProfile();
            break;
        case $ACTION_LIST['CHANGE_PASSWORD']:
            $profile->changePassword();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
