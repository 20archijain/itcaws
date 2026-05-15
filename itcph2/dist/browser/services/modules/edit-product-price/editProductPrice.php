<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/EditProductPrice.php";

if (!isEmptyString($requestAction)) {
    $reporting = new EditProductPrice($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        case $ACTION_LIST['GET_DATA']:
            $reporting->getData();
            break;
        case $ACTION_LIST['GET_TEAM_TYPE_LIST']:
            $reporting->getTeamType();
            break;
        case $ACTION_LIST['GET_LIST']:
            $reporting->getProductAndPrice();
            break;
        case $ACTION_LIST['ADD_DATA']:
            $reporting->updateSellingPrice();
            break;
            // case $ACTION_LIST['GET_DOWNLOAD_SUMMARY']:
            //     $reporting->getDownloadSummary();
            //     break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
