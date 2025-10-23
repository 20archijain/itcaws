<?php

// Used in ITC Phase 2 setup to get the notification in MDO APP

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetMdoNotification extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_mdo_notification";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getMdoNotification()
    {
        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];

        // Fetch all teams mapped to this MDO
        $arrTeamIds = $this->tableUtil->getRowsColumn("$dbName.tblmdo_access", "teams", "mdo_id = $teamId");

        $arrNotifications = [];
        $arrRoutes = [];
        $getRoutes = [];
        $getTeams = [];
        $arrTeams = [];

        // ✅ Date filter: last 3 months
        $dateFrom = date("Y-m-d", strtotime("-3 months"));
        $dateTo = date("Y-m-d");
        $firstDayLastMonth = date("Y-m-01", strtotime("first day of -1 month"));
        $lastDayThisMonth = date("Y-m-t");

        // ðŸ”¹ Collect all route names (from tblroute_details or tblroute_details_breeze)
        foreach ($arrTeamIds as $dsId) {
            // Try fetching from tblroute_details first
            $routes = $this->tableUtil->getRowsColumn(
                "$dbName.tblroute_details",
                "route_name",
                "team_id = '$dsId'",
                array(),
                true
            );

            // If not found, try from tblroute_details_breeze
            if (empty($routes)) {
                $routes = $this->tableUtil->getRowsColumn(
                    "$dbName.tblroute_details_breeze",
                    "route_name",
                    "team_id = '$dsId'",
                    array(),
                    true
                );
            }

            // ðŸ§¹ Filter out empty or null route names
            $routes = array_filter($routes, function ($r) {
                return $r !== null && trim($r) !== '';
            });

            // Merge the routes
            if (!empty($routes)) {
                $getRoutes = array_merge($getRoutes, $routes);
            }

            // Try fetching from project team table first
            $teamName = $this->tableUtil->getRowColumn(
                "$dbName.tblproject_team",
                "team_name",
                "team_id = '$dsId'"
            );

            // If not found, check in breeze team
            if (empty($teamName)) {
                $teamName = $this->tableUtil->getRowColumn(
                    "$dbName.tblbreeze_team",
                    "team_name",
                    "team_id = '$dsId'"
                );
            }

            if (!empty($teamName)) {
                $getTeams[] = $teamName;
            }
        }

        // After the loop, process all collected routes
        if (!empty($getRoutes)) {
            // Build comma-separated route list for SQL IN()
            $routeList = "'" . implode("','", array_map('addslashes', $getRoutes)) . "'";

            // Fetch routes present in tblmdo_summary (last 3 months)
            $query = "SELECT DISTINCT route_name FROM $dbName.tblmdo_summary WHERE mdo_id = $teamId AND route_name IN ($routeList) AND capture_date BETWEEN '$dateFrom' AND '$dateTo'";
            $rsAction = [];
            $iRows = 0;
            $this->dbConn->ExecuteSelectQuery($query, $rsAction, $iRows);

            $arrRespRoutes = [];
            if ($iRows > 0) {
                while ($row = $this->dbConn->GetData($rsAction)) {
                    $arrRespRoutes[] = $row['route_name'];
                }
            }

            // 🔸 Find missing routes
            $missingRoutes = array_diff($getRoutes, $arrRespRoutes);

            if (!empty($missingRoutes)) {
                // For each missing route, get wd_code & ds_name
                foreach ($missingRoutes as $route) {
                    // 1️⃣ Try finding from tblroute_details
                    $queryRoute = "SELECT DISTINCT route_name, wd_code, (SELECT team_name FROM $dbName.tblproject_team WHERE team_id = rd.team_id) AS ds_name FROM $dbName.tblroute_details rd WHERE rd.route_name = '" . addslashes($route) . "'";
                    $rsActionRoute = [];
                    $rowsRoute = 0;
                    $this->dbConn->ExecuteSelectQuery($queryRoute, $rsActionRoute, $rowsRoute);

                    if ($rowsRoute > 0) {
                        while ($rowRoute = $this->dbConn->GetData($rsActionRoute)) {
                            $arrRoutes[] = [
                                "route_name" => $rowRoute['route_name'],
                                "wd_code" => $rowRoute['wd_code'],
                                "ds_name" => $rowRoute['ds_name']
                            ];
                        }
                    } else {
                        // 2️⃣ If not found in tblroute_details, try tblroute_details_breeze
                        $queryRouteBreeze = "SELECT DISTINCT route_name, wd_code, (SELECT team_name FROM $dbName.tblbreeze_team WHERE team_id = rd.team_id) AS ds_name FROM $dbName.tblroute_details_breeze rd WHERE rd.route_name = '" . addslashes($route) . "'";
                        $rsActionRoute2 = [];
                        $rowsRoute2 = 0;
                        $this->dbConn->ExecuteSelectQuery($queryRouteBreeze, $rsActionRoute2, $rowsRoute2);

                        if ($rowsRoute2 > 0) {
                            while ($rowRoute2 = $this->dbConn->GetData($rsActionRoute2)) {
                                $arrRoutes[] = [
                                    "route_name" => $rowRoute2['route_name'],
                                    "wd_code" => $rowRoute2['wd_code'],
                                    "ds_name" => $rowRoute2['ds_name']
                                ];
                            }
                        }
                    }
                }

                $arrAllRouteDetails = array();

                foreach ($arrRoutes as $index => $details) {
                    $arrAllRouteDetails[] = array(
                        "cardHeading" => "Pending Route " . $index + 1,
                        array(
                            "label" => "Route",
                            "value" => $details['route_name'],
                        ),
                        array(
                            "label" => "WD Code",
                            "value" => $details['wd_code'],
                        ),
                        array(
                            "label" => "DS Name",
                            "value" => $details['ds_name'],
                        ),
                    );
                }

                $arrNotifications[] = [
                    "id" => 1,
                    "title" => "Pending Routes",
                    "shortMessage" => "Last 3 Months Pending Routes",
                    "fullMessage" => null,
                    // phpcs:ignore
                    "metadata" => $arrAllRouteDetails
                ];
            }
        }

        if (!empty($getTeams)) {
            // Build comma-separated team list for SQL IN()
            $dsList = "'" . implode("','", $getTeams) . "'";

            // Fetch teams present in tblsurvey_response_details_mdo (last month and current month)
            $query = "SELECT DISTINCT ds_name, ds_id FROM $dbName.tblmdo_summary WHERE mdo_id = $teamId AND ds_name IN ($dsList) AND capture_date BETWEEN '$firstDayLastMonth' AND '$lastDayThisMonth'";

            $rsAction = [];
            $iRows = 0;
            $this->dbConn->ExecuteSelectQuery($query, $rsAction, $iRows);

            $arrRespTeams = [];
            $arrRespTeamIds = [];
            if ($iRows > 0) {
                while ($row = $this->dbConn->GetData($rsAction)) {
                    $arrRespTeams[] = $row['ds_name'];
                    $arrRespTeamIds[] = $row['ds_id'];
                }
            }

            // Find missing routes (not yet in response table)
            $missingTeams = array_diff($getTeams, $arrRespTeams);

            if (!empty($missingTeams)) {
                $arrType = array(0 => "VAN DS", 5 => "NPSR", 8 => "SCP DS", 2 => "SWD", 6 => "RMD", 9 => "Common FMCG Lite DS");
                // For each missing team, get wd_code & type
                foreach ($arrRespTeamIds as $team) {
                    // 1️⃣ Try finding from tblroute_details
                    $queryTeam = "SELECT team_name, wd_code, is_type FROM $dbName.tblproject_team WHERE team_id = '$team'";
                    $rsActionTeam = [];
                    $rowsTeam = 0;
                    $this->dbConn->ExecuteSelectQuery($queryTeam, $rsActionTeam, $rowsTeam);

                    if ($rowsTeam > 0) {
                        while ($rowTeam = $this->dbConn->GetData($rsActionTeam)) {
                            $arrTeams[] = [
                                "ds_name" => $rowTeam['team_name'],
                                "wd_code" => $rowTeam['wd_code'],
                                "ds_type" => $arrType[$rowTeam['is_type']],
                            ];
                        }
                    } else {
                        // 2️⃣ If not found in tblproject_team, try tblbreeze_team
                        $queryTeamBreeze = "SELECT team_name, wd_code, is_type FROM $dbName.tblbreeze_team WHERE team_id = '$team'";
                        $rsActionTeam2 = [];
                        $rowsTeam2 = 0;
                        $this->dbConn->ExecuteSelectQuery($queryTeamBreeze, $rsActionTeam2, $rowsTeam2);

                        if ($rowsTeam2 > 0) {
                            while ($rowTeam2 = $this->dbConn->GetData($rsActionTeam2)) {
                                $arrTeams[] = [
                                    "ds_name" => $rowTeam2['team_name'],
                                    "wd_code" => $rowTeam2['wd_code'],
                                    "ds_type" => $arrType[$rowTeam2['is_type']],
                                ];
                            }
                        }
                    }
                }

                $arrAllTeamsDetails = array();

                foreach ($arrTeams as $index => $details) {
                    $arrAllTeamsDetails[] = array(
                        "cardHeading" => "Pending DS " . $index + 1,
                        array(
                            "label" => "WD Code",
                            "value" => $details['wd_code'],
                        ),
                        array(
                            "label" => "DS Name",
                            "value" => $details['ds_name'],
                        ),
                        array(
                            "label" => "DS Type",
                            "value" => $details['ds_type'],
                        ),
                    );
                }

                $arrNotifications[] = [
                    "id" => 2,
                    "title" => "Pending DS",
                    "shortMessage" => "Last Months & Current Month Pending DS",
                    "fullMessage" => null,
                    // phpcs:ignore
                    "metadata" => $arrAllTeamsDetails
                ];
            }
        }

        $response = $this->response->sendResponse(array("message" => "", "response" => $arrNotifications), 1);
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getAlert()
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
                $this->getMdoNotification();
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$notification = new GetMdoNotification($dbConn, $tableUtil, $commonFunctions);
$notification->getAlert();
$dbConn->Close();
