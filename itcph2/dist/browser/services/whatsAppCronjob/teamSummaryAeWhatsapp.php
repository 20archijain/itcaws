<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class VanDswhatsAppSummary
{
    private $_dbConn = null;
    private $_tables = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
    }

    public function sendTeamSummary()
    {
        $currentDate = currentDate();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $constantsTable = $this->_tables["CONSTANTS_TABLE"];

        $minTotalShops = (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
        $minQualifiedAttendanceTimeInMin = (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
        $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.start_datetime, a.end_datetime, a.dayend_datetime, a.resp_startdatetime, a.resp_enddatetime, a.total_roc_deliveries, a.total_sellin_shops, a.total_other_shops" .
            ", a.total_meter_travelled, b.team_id, b.team_name, b.wd_code, b.ae_name, b.ae_number FROM $summaryTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND a.team_id = b.team_id AND a.activity_date = '$currentDate'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $teamId = $row["team_id"];
                $aeNumber = $row["ae_number"];
                $totalDistanceTravelled = isset($row["total_meter_travelled"]) ? round($row["total_meter_travelled"] / 1000, 2) : 0;
                $totalShopsDone = $row["total_roc_deliveries"] + $row["total_other_shops"];
                $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
                $isQualifiedAttendance = $totalShopsDone >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? "Yes" : "No";
                $marketTime = getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);
                // Convert $marketTime from minutes to hours and minutes
                $hours = floor($marketTime / 60);
                $minutes = $marketTime % 60;

                // Format the time string
                if ($hours > 0 && $minutes > 0) {
                    $marketTimeFormatted = $hours . " hr " . $minutes . " min";
                } elseif ($hours > 0) {
                    $marketTimeFormatted = $hours . " hr";
                } else {
                    $marketTimeFormatted = $minutes . " min";
                }

                $dayEndMarked = (isset($row["dayend_datetime"]) && $row["dayend_datetime"] != null) ? "Yes" : "No";

                // Get covered routes
                $coveredRoutesQuery = "SELECT DISTINCT SUBSTRING(ques_1, 3, LENGTH(ques_1)-4) AS covered_route FROM tblsurvey_response_details WHERE dstatus = 0 AND capture_date = '$currentDate' AND team_id = $teamId";
                $coveredRoutesAction = null;
                $coveredRoutesRows = 0;
                $coveredRoutes = [];
                $this->_dbConn->ExecuteSelectQuery($coveredRoutesQuery, $coveredRoutesAction, $coveredRoutesRows);
                if ($coveredRoutesRows > 0) {
                    while ($coveredRow = $this->_dbConn->GetData($coveredRoutesAction)) {
                        $coveredRoutes[] = $coveredRow['covered_route'];
                    }
                }
                $routeNamesString = "'" . implode("','", $coveredRoutes) . "'";

                $TotalShops = getRowColumn($this->_dbConn, "tblroute_details", "COUNT(DISTINCT outlet_name) AS total", "dstatus = 0 AND team_id = $teamId AND route_name IN ($routeNamesString)");

                // Get covered outlets
                // $coveredOutletsQuery = "SELECT DISTINCT SUBSTRING(ques_2, 3, LENGTH(ques_2)-4) AS covered_outlet FROM tblsurvey_response_details WHERE dstatus = 0 AND capture_date = '$currentDate' AND team_id = $teamId";
                // $coveredOutletsAction = null;
                // $coveredOutletsRows = 0;
                // $coveredOutlets = [];
                // $this->_dbConn->ExecuteSelectQuery($coveredOutletsQuery, $coveredOutletsAction, $coveredOutletsRows);
                // if ($coveredOutletsRows > 0) {
                //     while ($coveredOutletRow = $this->_dbConn->GetData($coveredOutletsAction)) {
                //         $coveredOutlets[] = $coveredOutletRow['covered_outlet'];
                //     }
                // }
                // $outletsNamesString = "'" . implode("','", $coveredOutlets) . "'";

                // Get not done shops
                // $notDoneShopsQuery = "SELECT DISTINCT outlet_name FROM tblroute_details WHERE dstatus = 0 AND route_name IN ($routeNamesString) AND rec_id NOT IN ($outletsNamesString) AND team_id = $teamId";
                // $notDoneShopsAction = null;
                // $notDoneShopsRows = 0;
                // $notDoneShops = [];
                // $this->_dbConn->ExecuteSelectQuery($notDoneShopsQuery, $notDoneShopsAction, $notDoneShopsRows);
                // if ($notDoneShopsRows > 0) {
                //     while ($notDoneShopsRow = $this->_dbConn->GetData($notDoneShopsAction)) {
                //         // Check if outlet_name is empty, if yes, assign "NA" to it
                //         $outletName = !empty($notDoneShopsRow['outlet_name']) ? $notDoneShopsRow['outlet_name'] : "NA";
                //         $notDoneShops[] = $outletName;
                //     }
                // }
                // $notDoneShopsString = implode(", ", $notDoneShops);


                // Prepare data for WhatsApp API call
                $phoneNumber = $aeNumber;
                $payload = array(
                    'name' => 'ae_summary_utility',
                    'components' => array(
                        array(
                            'type' => 'body',
                            'parameters' => array(
                                array('type' => 'text', 'text' => $row['ae_name']),
                                array('type' => 'text', 'text' => $row['team_name']),
                                array('type' => 'text', 'text' => $row['wd_code']),
                                array('type' => 'text', 'text' => $isQualifiedAttendance),
                                array('type' => 'text', 'text' => $marketTimeFormatted),
                                array('type' => 'text', 'text' => $dayEndMarked),
                                array('type' => 'text', 'text' => $totalShopsDone . '/' . $TotalShops),
                                array('type' => 'text', 'text' => $totalDistanceTravelled)
                            )
                        )
                    ),
                    'language' => array(
                        'code' => 'en_US',
                        'policy' => 'deterministic'
                    ),
                    'namespace' => 'a7a46341_4176_4bd7_b74f_bf560970e605'
                );

                // Encode the data as JSON
                $jsonData = json_encode(array('phoneNumber' => $phoneNumber, 'payload' => $payload));

                // Check if summary already exists, if not, add summary else don't add summary
                $isExist = isRecordExist($this->_dbConn, "whatsapp_summary_logs", "id", "team_id = $teamId AND capture_date = '$currentDate'");

                if (!$isExist && !empty($phoneNumber)) { // Check if ae_number is not null
                    // API endpoint and authorization token
                    $url = 'https://api.wab.ai/whatsapp-api/v1.0/customer/95755/bot/b9b57bd0131f43fb/template';
                    $authorizationToken = '6f088510-93ef-41a2-b9d4-cf7570fbcbe1-Hswi54Q';

                    // Initialize cURL session
                    $curl = curl_init();

                    // Set the cURL options
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $jsonData,
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Basic ' . $authorizationToken,
                            'Content-Type: application/json'
                        ),
                    ));

                    // Execute the cURL request
                    $response = curl_exec($curl);

                    // Check for errors
                    if (curl_errno($curl)) {
                        echo 'Error: ' . curl_error($curl);
                    } else {
                        // Display the response
                        echo $response;

                        $tableName = "whatsapp_summary_logs";
                        $columns = "ae_name, team_name, team_id, wd_code, qualified_attendance, market_time_formatted, day_end_marked, shops_summary, not_visited_shops, api_response, capture_date";

                        $values = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

                        $params = array(
                            $row['ae_name'],
                            $row['team_name'],
                            $row['team_id'],
                            $row['wd_code'],
                            $isQualifiedAttendance,
                            $marketTimeFormatted,
                            $dayEndMarked,
                            $totalShopsDone . '/' . $TotalShops,
                            $totalDistanceTravelled,
                            $response,
                            $currentDate
                        );

                        $result = addRecord($this->_dbConn, $tableName, $columns, $values, $params);

                        // Check the result of the insert operation
                        if ($result === 2) {
                            echo "Record inserted successfully.";
                        } elseif ($result === 1) {
                            echo "Record already exists.";
                        } else {
                            echo "Failed to insert record.";
                        }
                    }

                    // Close the cURL session
                    curl_close($curl);
                }
            }
        }
    }
}

// Create instance of VanDswhatsAppSummary class
$vanDswhatsAppSummary = new VanDswhatsAppSummary($dbConn);

// Send team summary
$vanDswhatsAppSummary->sendTeamSummary();
