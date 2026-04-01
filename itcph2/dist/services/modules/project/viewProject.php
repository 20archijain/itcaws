<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/ProjectManagement.php";

if (!isEmptyString($requestAction)) {
    $project = new ProjectManagement($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_LIST']:
            $project->viewProjects();
            break;
        case $ACTION_LIST['GET_DATA']:
            $project->getViewProjectData();
            break;
        case $ACTION_LIST['EDIT_DATA']:
            $project->editProject();
            break;
        case $ACTION_LIST['DELETE_DATA']:
            deleteListingRecord($dbConn, $TABLES["PROJECTS_TABLE"], "project_id", $iUserId, "", $requestData, "id");
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
