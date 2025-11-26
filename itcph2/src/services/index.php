<?php

ini_set('memory_limit', -1);
date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . 'includes/error_messages.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/validation_list.php';
require_once $include_path . 'includes/actions_list.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/Validation.php';
require_once $include_path . "class/UploadAndThumbnail.php";
require_once $include_path . "class/SessionManagement.php";
require_once $include_path . "class/Ppt.php";
require_once $include_path . "class/Pdf.php";

// generate session
$sessionMgmt = new SessionManagement();
$sessionMgmt->startSession();

//image data
if (isset($_FILES) && isset($_FILES["file"])) {
    $jsondata = json_decode($_POST["data"], true);
    $jsonfiles = $_FILES["file"];
} else {
    //static data
    $jsondata = file_get_contents("php://input");
    $jsondata = json_decode($jsondata, true);
    $jsonfiles = "";
}

//check if data is available
if (isNonEmptyArray($jsondata)) {
    //DB connection
    include_once $include_path . 'class/DBConnection.php';

    //static modules (login, forgot, logout, captcha)
    if (matchValue($jsondata['staticModule'], true, true)) {
        //check if valid module request
        if (isNonEmptyArray($jsondata['request_info']) && !isEmptyString($jsondata['request_info']['module'])) {
            //check if data present
            if (isNonEmptyArray($jsondata['request_info']['data'])) {
                $requestData = $jsondata['request_info'];
                $requestModule = $requestData['module'];
                $requestAction = $requestData['action'];
                $requestData = $requestData['data'];

                switch ($requestModule) {
                    case $ACTION_LIST['CAPTCHA']:
                        include_once $include_path . 'modules/auth/captcha.php';
                        break;
                    case $ACTION_LIST['LOGIN']:
                        include_once $include_path . 'modules/auth/login.php';
                        break;
                    case $ACTION_LIST['FORGOT']:
                        include_once $include_path . 'modules/auth/forgot.php';
                        break;
                    case $ACTION_LIST['LOGOUT']:
                        include_once $include_path . 'modules/auth/logout.php';
                        break;
                    default:
                        $errorMessage = responseMessage(array($GLOBALS['INVALID_MODULE_REQUEST']));
                        echo json_encode($errorMessage);
                }
            } else {
                $errorMessage = responseMessage(array($GLOBALS['NO_MODULE_DATA']));
                echo json_encode($errorMessage);
            }
        } else {
            $errorMessage = responseMessage(array($GLOBALS['NO_MODULE_REQUESTED']));
            echo json_encode($errorMessage);
        }
    } else {
        //dynamic modules
        //check if token is coming or not
        if (isEmptyString($jsondata['auth_token']) || strlen($jsondata['auth_token']) !== 64) {
            $errorMessage = responseMessage(array($GLOBALS['INVALID_SESSION']));
            echo json_encode($errorMessage);
        } else {
            $sToken = $jsondata['auth_token'];

            //check if valid token and user exists
            $userSessionTokenTable = $TABLES["USER_SESSION_TOKEN_TABLE"];
            $sQuery = "SELECT user_id, permitted_ids FROM $userSessionTokenTable WHERE csrf_token = ? AND dstatus = 0 LIMIT 1";
            $arrParams = array($sToken);
            $dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

            //User found
            if ($iRows === 1) {
                $arResult = $dbConn->GetData($sAction);
                $iUserId = $arResult["user_id"];

                //check if valid module request
                if (
                    isNonEmptyArray($jsondata['request_info']) &&
                    isNonEmptyArray($jsondata['request_info']['module']) &&
                    !isEmptyString($jsondata['request_info']['action'])
                ) {
                    $requestData = $jsondata['request_info'];
                    $requestModule = $requestData['module'];
                    $requestAction = $requestData['action'];
                    $requestData = $requestData['data'];

                    //check if valid module and permission exists
                    $modulesTable = $TABLES["MODULES_TABLE"];
                    $sQuery2 = "SELECT module_id, module_url_link FROM $modulesTable WHERE module_code = ? AND parent_module_code = ? AND dstatus = 0 LIMIT 1";
                    $arrParams2 = array($requestModule['modc'], $requestModule['pmodc']);
                    $dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2, $arrParams2);

                    //module exists
                    if ($iRows2 === 1) {
                        $arResult2 = $dbConn->GetData($sAction2);
                        $iModuleId = $arResult2["module_id"];
                        $iModuleFile = $arResult2["module_url_link"];

                        //check permission
                        $userGroupRoleView = $TABLES["USERGROUPROLE_VIEW"];
                        $sQuery3 = "SELECT role_permission FROM $userGroupRoleView WHERE user_id = ? AND dstatus = 0 LIMIT 1";
                        $arrParams3 = array($iUserId);
                        $dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3, $arrParams3);

                        if ($iRows3 === 1) {
                            $arResult3 = $dbConn->GetData($sAction3);
                            $sModuleIds = $arResult3["role_permission"];

                            $arrModuleIds = explode(",", $sModuleIds);

                            //permission granted
                            if (in_array($iModuleId, $arrModuleIds)) {
                                // get allowed access
                                $arrAccessInfo = $arResult["permitted_ids"] ?
                                    json_decode($arResult["permitted_ids"], true) : array();

                                include_once $include_path . 'modules/' . $iModuleFile;
                                die;
                            } else {
                                $errorMessage = responseMessage(array($GLOBALS['NO_MODULE_PERMISSION']));
                                echo json_encode($errorMessage);
                            }
                        } else {
                            $errorMessage = responseMessage(array($GLOBALS['NO_ROLE_ASSIGNED']));
                            echo json_encode($errorMessage);
                        }
                    } else {
                        $errorMessage = responseMessage(array($GLOBALS['INVALID_MODULE_REQUEST']));
                        echo json_encode($errorMessage);
                    }
                } else {
                    $errorMessage = responseMessage(array($GLOBALS['INVALID_MODULE_REQUEST']));
                    echo json_encode($errorMessage);
                }
            } else {
                $errorMessage = responseMessage(array($GLOBALS['UNAUTHORIZED_ACCESS']));
                echo json_encode($errorMessage);
            }
        }
    }
} else {
    $errorMessage = responseMessage(array($GLOBALS['INVALID_MODULE_REQUEST']));
    echo json_encode($errorMessage);
}
