<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/SitesOnMapManagement.php";

if (!isEmptyString($requestAction)) {
    $sites = new SitesOnMapManagement($dbConn, $requestData, $arrAccessInfo);
    switch ($requestAction) {
        // case $ACTION_LIST['GET_DATA']:
        //     $sites->getRmdData();
        //     break;
        // case $ACTION_LIST['GET_LIST']:
        //     $sites->getRmdLocationCoveredData();
        //     break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
