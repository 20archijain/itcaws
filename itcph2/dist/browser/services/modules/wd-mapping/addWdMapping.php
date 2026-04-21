<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/WdMappingManagement.php";

if (!isEmptyString($requestAction)) {
    $project = new WdMappingManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['ADD_DATA']:
            $project->addProject();
            break;
        case $ACTION_LIST['GET_DATA']:
            $project->getAddProjectData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
