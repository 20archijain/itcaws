<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/downloadReports/ProductivityReport.php";

if (!isEmptyString($requestAction)) {
    $download = new ProductivityReport($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $download->getData();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $download->getDownloadData();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
