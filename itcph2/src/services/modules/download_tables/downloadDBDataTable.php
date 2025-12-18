<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/download_table/DownloadDBTableData.php";

if (!isEmptyString($requestAction)) {
    $download = new DownloadTable($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $download->getData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $download->getDownloadSqlTable();
            break;
        case $ACTION_LIST['GET_TABLES']:
            $download->getTableList();
            break;
        case $ACTION_LIST['GET_PROJECT_LIST']:
            $download->getProjectList();
            break;
        case $ACTION_LIST['GET_TABLE_COLUMNS']:  // New action for getting table columns
            $download->getTableColumns();
            break;
            // case $ACTION_LIST['PREVIEW_QUERY']:      // Optional: Preview query before download
            //     $download->previewQuery();
            //     break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
