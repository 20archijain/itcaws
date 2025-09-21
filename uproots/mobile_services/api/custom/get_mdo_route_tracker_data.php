<?php

// Used in ITC Phase 2 setup to get the Route on map in app

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetMdoAppRouteTracker extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_mdo_app_route_tracker";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    // Send Dropdown Values
    private function getData($arrUserDetails)
    {
        // Team Types
        $ARR_TEAM_TYPES = array(
            0 => "Van DS",
            1 => "Niche",
            2 => "Town SWD",
            3 => "Hybrid",
            4 => "SCP",
            5 => "NPSR",
            6 => "RMD",
            7 => "MDO",
            8 => "SCP DS",
            9 => "Commonn FMCG Lite DS",
        );
        global $TBL_ATTENDANCE, $TBL_SURVEY_RESPONSE, $TBL_ROUTE_DETAILS, $TBL_PROJECT_TEAM;
        $mdoId = isset($arrUserDetails['team_id']) ? $arrUserDetails['team_id'] : null;
        $dbName = $this->arrUserDetails["db_name"];


        $teams = $this->tableUtil->getRowsColumns(
            "$dbName.tblmdo_access",
            "teams, is_type",
            "dstatus = 0 AND mdo_id = '$mdoId'"
        );

        $arrResponse = [];
        if (is_array($teams)) {
            foreach ($teams as $team) {
                $teamId = $team[0];
                $isType = (int)$team[1];

                if (in_array($isType, [6, 8, 9])) {
                    $sQuery = "SELECT team_id, team_name, wd_code, is_type FROM $dbName.tblbreeze_team WHERE dstatus = 0 AND team_id = '$teamId'";
                } elseif (in_array($isType, [0, 2, 5, 7, 10])) {
                    $sQuery = "SELECT team_id, team_name, wd_code, is_type FROM $dbName.$TBL_PROJECT_TEAM  WHERE dstatus = 0 AND team_id = '$teamId'";
                }
                $sAction = null;
                $iRows = 0;
                $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

                while ($row = $this->dbConn->GetData($sAction)) {
                    $wdCode = $row["wd_code"];
                    $isTypeVal = $row["is_type"];

                    if (!isset($arrResponse[$wdCode])) {
                        $arrResponse[$wdCode] = [];
                    }

                    if (!isset($arrResponse[$wdCode][$isTypeVal])) {
                        $arrResponse[$wdCode][$isTypeVal] = [];
                    }

                    $arrResponse[$wdCode][$isTypeVal][] = [
                        "team_id"   => $row["team_id"],
                        "team_name" => $row["team_name"]
                    ];
                }
            }
            // dropdown structure
            $dropdown = [
                "dropDownItemList" => [
                    [
                        "label"   => "Please select",
                        "value"   => ""
                    ]
                ]
            ];
            foreach ($arrResponse as $wdCode => $dsTypes) {
                $wdItem = [
                    "label"   => $wdCode,
                    "value"   => $wdCode,
                    "options" => []
                ];

                foreach ($dsTypes as $isType => $teams) {
                    $dsTypeItem = [
                        "label"   => $ARR_TEAM_TYPES[$isType],
                        "value"   => (string)$isType
                    ];

                    foreach ($teams as $team) {
                        $dsTypeItem["options"][] = [
                            "label"   => $team["team_name"],
                            "value"   => $team["team_id"]
                        ];
                    }

                    $wdItem["options"][] = $dsTypeItem;
                }

                $dropdown["dropDownItemList"][] = $wdItem;
            }

            // response
            if (!empty($dropdown["dropDownItemList"])) {
                $arrFinalResponse = $dropdown;
                $response = $this->response->sendResponse(
                    ["message" => "", "response" => $arrFinalResponse],
                    1
                );
                $this->logOutput($response, $this->sExtraLogData);
            } else {
                // No dropdown data
                $response = $this->response->sendResponse(
                    ["message" => $this->arrCustomMessages["CUST05"]]
                );
                $this->logOutput($response, $this->sExtraLogData);
            }
        } else {
            // No teams found
            $response = $this->response->sendResponse(
                ["message" => $this->arrCustomMessages["CUST05"]]
            );
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    //ROute Tracker Data
    private function getRouteTracker()
    {
        global  $TBL_ATTENDANCE, $TBL_SURVEY_RESPONSE, $TBL_ROUTE_DETAILS;
        $dbName = $this->arrUserDetails["db_name"];
        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        $wd_code = isset($_GET['wd_code']) ? $_GET['wd_code'] : null;
        $team_type = isset($_GET['team_type']) ? trim($_GET['team_type'], '"') : null;
        $datetime = isset($_GET['date']) ? trim($_GET['date'], '"') : null;
        $date = (new DateTime($datetime))->format('Y-m-d');

        if (isset($team_type) && ($team_type == 0 || $team_type == 2 || $team_type == 5 || $team_type == 7 || $team_type == 10)) {
            $sQueryAtt = "SELECT MIN(capture_datetime) AS attDateTime, lt, lg FROM $dbName.$TBL_ATTENDANCE Where dstatus = 0 AND team_id = '$teamId' AND capture_date = '$date' AND call_type = '0' LIMIT 1";

            $sActionAtt = null;
            $iRowsAtt = 0;
            $this->dbConn->ExecuteSelectQuery($sQueryAtt, $sActionAtt, $iRowsAtt);

            if ($iRowsAtt > 0) {
                $arrRouteTrackerData = [];
                $idCounter = 1;

                // Attendance Data
                while ($arDataAtt = $this->dbConn->GetData($sActionAtt)) {
                    if (!empty($arDataAtt["attDateTime"]) && !empty($arDataAtt["lt"]) && !empty($arDataAtt["lg"])) {
                        $attCaptureTime = date("h:i A", strtotime($arDataAtt["attDateTime"]));
                        $arrRouteTrackerData[] = [
                            "id" => (string)$idCounter++,
                            "icon" => "green_flag",
                            "shopName" => "Attendance",
                            "dateTime" => $attCaptureTime,
                            "lt" => (string)$arDataAtt["lt"],
                            "lg" => (string)$arDataAtt["lg"]
                        ];
                    }
                }
                // Get Response Data
                $responseData = $this->tableUtil->getRowsColumns(
                    "$dbName.$TBL_SURVEY_RESPONSE",
                    "capture_datetime AS respDateTime, lt, lg, ques_3",
                    "dstatus = 0 AND team_id = '$teamId' AND capture_date = '$date' ORDER BY capture_datetime"
                );
                $index = 0;
                $shopCount = 1;

                if ($responseData) {
                    // Getting response count to assign icon
                    $responseCount = count($responseData);
                    foreach ($responseData as $RespData) {
                        $shopName = $this->tableUtil->getRowColumn(
                            "$dbName.$TBL_ROUTE_DETAILS",
                            "outlet_name",
                            "dstatus = 0  AND team_id = '$teamId' AND rec_id = '$RespData[3]'"
                        );
                        // Assign icon dynamically based on index
                        $icon = ($index === 0) ? "green_pin" : (($index === $responseCount - 1) ? "red_pin" : "orange_pin");
                        $respCaptureDatetime = $RespData[0];
                        $time = date("h:i A", strtotime($respCaptureDatetime));
                        $lt = $RespData[1];
                        $lg = $RespData[2];
                        $arrRouteTrackerData[] = [
                            "id" => (string)$idCounter++,
                            "icon" => $icon,
                            "shopName" => $shopCount . "-" . $shopName,
                            "dateTime" => $time,
                            "lt" => (string)$lt,
                            "lg" => (string)$lg
                        ];
                        $index++;
                        $shopCount++;
                    }
                }

                // Dayend Data
                $sQueryDayend = "SELECT MIN(capture_datetime) AS attDateTime, lt, lg FROM $dbName.$TBL_ATTENDANCE Where dstatus = 0 AND team_id = '$teamId' AND capture_date = '$date' AND call_type = '1' LIMIT 1";

                $sActionDayend = null;
                $iRowsDayend = 0;
                $this->dbConn->ExecuteSelectQuery($sQueryDayend, $sActionDayend, $iRowsDayend);
                if ($iRowsDayend > 0) {
                    // Dayend Data
                    while ($arDataDayend = $this->dbConn->GetData($sActionDayend)) {
                        if (!empty($arDataDayend["attDateTime"]) && !empty($arDataDayend["lt"]) && !empty($arDataDayend["lg"])) {
                            $attCaptureTime = date("h:i A", strtotime($arDataDayend["attDateTime"]));
                            $arrRouteTrackerData[] = [
                                "id" => (string)$idCounter++,
                                "icon" => "red_flag",
                                "shopName" => "Dayend",
                                "dateTime" => $attCaptureTime,
                                "lt" => (string) $arDataDayend["lt"],
                                "lg" => (string)$arDataDayend["lg"]
                            ];
                        }
                    }
                }

                // **Add this check to ensure data exists**
                if (!empty($arrRouteTrackerData)) {
                    $arrResponse = ["routetrackerData" => ["dataset" => $arrRouteTrackerData]];
                    $response = $this->response->sendResponse(["message" => "", "response" => $arrResponse], 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    // No route tracker data
                    $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
                    $this->logOutput($response, $this->sExtraLogData);
                }
            } else {
                // No data found
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        } elseif (isset($team_type) && ($team_type == 6 || $team_type == 8 || $team_type == 9)) {
            // Get Response Data
            $responseData = $this->tableUtil->getRowsColumns(
                "$dbName.tblroute_details_breeze",
                "team_id, outlet_name, lt, lg",
                "dstatus = 0 AND team_id = '$teamId' ORDER BY capture_datetime"
            );
            $index = 0;
            $shopCount = 1;

            if ($responseData) {
                $arrRouteTrackerData = [];
                $idCounter = 1;
                // Getting response count to assign icon
                $responseCount = count($responseData);
                foreach ($responseData as $RespData) {
                    // Assign icon dynamically based on index
                    // $icon = ($index === 0) ? "green_pin" : (($index === $responseCount - 1) ? "red_pin" : "orange_pin");
                    $teamId = $RespData[0];
                    $shopName = $RespData[1];
                    $lt = $RespData[2];
                    $lg = $RespData[3];
                    $arrRouteTrackerData[] = [
                        "id" => (string)$idCounter++,
                        "icon" => "green_pin",
                        "shopName" => $shopCount . "-" . $shopName,
                        "dateTime" => "",
                        "lt" => (string)$lt,
                        "lg" => (string)$lg
                    ];
                    // $index++;
                    $shopCount++;
                }
                // **Add this check to ensure data exists**
                if (!empty($arrRouteTrackerData)) {
                    $arrResponse = ["routetrackerData" => ["dataset" => $arrRouteTrackerData]];
                    $response = $this->response->sendResponse(["message" => "", "response" => $arrResponse], 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    // No route tracker data
                    $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
                    $this->logOutput($response, $this->sExtraLogData);
                }
            } else {
                // No route tracker data
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        } else {
            // No route tracker data
            $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }


    final public function getRouteTrackerData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            // Set logfile name as per DB as single log file may be huge
            $jsondata = file_get_contents("php://input");
            $jsondata = json_decode($jsondata, true);
            $this->setLogFileName($this->localLogFileName . "_$dbName");
            $teamId = $this->arrUserDetails["team_id"];
            if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
                $this->requestGetData = $_GET;
            }
            $teamName = isset($_GET['team_name']) ? $_GET['team_name'] : null;
            $wd_code = isset($_GET['wd_code']) ? $_GET['wd_code'] : null;
            $team_type = isset($_GET['team_type']) ? trim($_GET['team_type'], '"') : null;
            $datetime = isset($_GET['date']) ? trim($_GET['date'], '"') : null;

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";
            if ($this->validateData($dbName)) {
                if (isset($_GET['json_id']) && empty($teamName) && empty($team_type) && empty($wd_code) && empty($datetime)) {
                    $this->getData($this->arrUserDetails);
                } elseif (isset($_GET['json_id']) && isset($teamName) && isset($team_type) && isset($wd_code) && isset($datetime)) {
                    $this->getRouteTracker();
                } else {
                    // No  data
                    $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
                    $this->logOutput($response, $this->sExtraLogData);
                }
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$route = new GetMdoAppRouteTracker($dbConn, $tableUtil, $commonFunctions);
$route->getRouteTrackerData();
$dbConn->Close();
