<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/downloadReports/BillCutReport.php";

if (!isEmptyString($requestAction)) {
    $download = new BillCutReport($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $download->getData();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $download->getBranchTeamTypeList();
            break;
        case $ACTION_LIST['GET_PRODUCT_LIST']:
            $download->getProducts();
            break;
        case $ACTION_LIST['GET_DOWNLOAD_DATA']:
            $download->getDownloadData();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
