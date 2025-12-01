<?php

// Used in ITC Phase 2 setup to get the Route on map in app

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetRouteTracker extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_app_route_tracker";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getRouteTracker()
    {
        global  $TBL_ATTENDANCE, $TBL_SURVEY_RESPONSE, $TBL_ROUTE_DETAILS;

        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $teamId = isset($_GET['team_name']) ? $_GET['team_name'] : null;
        $datetime = isset($_GET['date']) ? trim($_GET['date'], '"') : null;
        $dbName = $this->arrUserDetails["db_name"];
        $date = (new DateTime($datetime))->format('Y-m-d');

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
                        "team_id = '$teamId' AND rec_id = '$RespData[3]'"
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
    }



    final public function getRouteTrackerData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData($dbName)) {
                $this->getRouteTracker();
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new GetRouteTracker($dbConn, $tableUtil, $commonFunctions);
$stock->getRouteTrackerData();
$dbConn->Close();
