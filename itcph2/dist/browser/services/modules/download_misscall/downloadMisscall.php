<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/DownloadMisscall.php";

if (!isEmptyString($requestAction)) {
    $download = new DownloadMisscall($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $download->getData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $download->getDownloadMissCallReport();
            break;
        case $ACTION_LIST['GET_PROJECT_LIST']:
            $download->getProjectList();
            break;
        case $ACTION_LIST['GET_LIST']:
            $download->viewData();
            break;
        case $ACTION_LIST['DELETE_WITH_FORM_DATA']:
            $download->deleteData($requestData, $iUserId);
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
