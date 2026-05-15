<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/AiInsights.php";

if (!isEmptyString($requestAction)) {
    $aiInsights = new AiInsights($dbConn, $requestData, $arrAccessInfo, $iUserId);
    switch ($requestAction) {
        case $ACTION_LIST['GET_AI_INSIGHTS']:
            $aiInsights->getInsights();
            break;
        case $ACTION_LIST['GET_AI_SCOPE_OPTIONS']:
            $aiInsights->getScopeOptions();
            break;
        default:
            $arrMessage = responseMessage([$INVALID_ACTION]);
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage([$NO_ACTION_FOUND]);
    echo json_encode($arrMessage);
}
