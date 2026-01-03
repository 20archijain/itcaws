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
        $mdoType = $this->tableUtil->getRowColumn("$dbName.tblproject_team", "is_type", "team_id = $teamId");
        $accessTeamList = "'" . implode("','", $arrTeamIds) . "'";

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
                "$dbName.tblmdo_offline_data",
                "route_name",
                "ds_id = '$dsId' AND dstatus = 0",
                array(),
                true
            );

            // ðŸ§¹ Filter out empty or null route names
            $routes = array_filter($routes, function ($r) {
                return $r !== null && trim($r) !== '';
            });

            // Merge the routes
            if (!empty($routes)) {
                $getRoutes[] = $routes;
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
        // ✅ Flatten $getRoutes into one single array & clean it
        $allRoutes = [];

        foreach ($getRoutes as $routes) {
            foreach ($routes as $r) {
                if (!empty($r) && strtoupper($r) !== 'NA') {
                    $allRoutes[] = $r;
                }
            }
        }

        // After the loop, process all collected routes
        if (!empty($allRoutes) && $mdoType == 7) {
            // Build comma-separated route list for SQL IN()
            $routeList = "'" . implode("','", $allRoutes) . "'";

            // Fetch routes present in tblmdo_summary (last 3 months)
            $query = "SELECT DISTINCT route_name FROM $dbName.tblmdo_summary WHERE mdo_id = $teamId AND route_name IN ($routeList) AND ds_id IN ($accessTeamList) AND capture_date BETWEEN '$dateFrom' AND '$dateTo'";
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
            $missingRoutes = array_diff($allRoutes, $arrRespRoutes);

            if (!empty($missingRoutes)) {
                // For each missing route, get wd_code & ds_name
                foreach ($missingRoutes as $route) {
                    // 1️⃣ Try finding from tblroute_details
                    $queryRoute = "SELECT DISTINCT route_name, wd_code, team_id FROM $dbName.tblroute_details rd WHERE rd.route_name = '" . addslashes($route) . "' AND rd.team_id IN ($accessTeamList)";
                    $rsActionRoute = [];
                    $rowsRoute = 0;
                    $this->dbConn->ExecuteSelectQuery($queryRoute, $rsActionRoute, $rowsRoute);

                    if ($rowsRoute > 0) {
                        while ($rowRoute = $this->dbConn->GetData($rsActionRoute)) {
                            $vandDsId = $rowRoute['team_id'];
                            $dsName = $this->tableUtil->getRowColumn(
                                "$dbName.tblproject_team",
                                "team_name",
                                "team_id = '$vandDsId'"
                            );
                            $arrRoutes[] = [
                                "route_name" => $rowRoute['route_name'],
                                "wd_code" => $rowRoute['wd_code'],
                                "ds_name" => $dsName
                            ];
                        }
                    } else {
                        // 2️⃣ If not found in tblroute_details, try tblroute_details_breeze
                        $queryRouteBreeze = "SELECT DISTINCT route_name, wd_code, team_id FROM $dbName.tblroute_details_breeze rd WHERE rd.route_name = '" . addslashes($route) . "' AND rd.team_id IN ($accessTeamList)";
                        $rsActionRoute2 = [];
                        $rowsRoute2 = 0;
                        $this->dbConn->ExecuteSelectQuery($queryRouteBreeze, $rsActionRoute2, $rowsRoute2);

                        if ($rowsRoute2 > 0) {
                            while ($rowRoute2 = $this->dbConn->GetData($rsActionRoute2)) {
                                $vandDsId = $rowRoute2['team_id'];
                                $dsName = $this->tableUtil->getRowColumn(
                                    "$dbName.tblbreeze_team",
                                    "team_name",
                                    "team_id = '$vandDsId'"
                                );
                                $arrRoutes[] = [
                                    "route_name" => $rowRoute2['route_name'],
                                    "wd_code" => $rowRoute2['wd_code'],
                                    "ds_name" => $dsName
                                ];
                            }
                        }
                    }
                }

                $arrAllRouteDetails = array();

                foreach ($arrRoutes as $index => $details) {
                    $arrAllRouteDetails[] = array(
                        "cardHeading" => "Pending Route " . $index + 1,
                        "kpis" => array(
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

        if (!empty($getTeams) && $mdoType == 7) {
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
                        "kpis" => array(
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

        if ($mdoType == 10) {
            // if ($teamId == 22701) {
            //     $bustarget = 40.00;
            //     $busMtdAch = 8.34;
            //     $busAch = 21 . " %";
            //     $markTarget = 134;
            //     $markMtdAch = 4;
            //     $markAch = 3 . " %";
            //     $UOBTarget = 600;
            //     $UOBkMtdAch = 219;
            //     $UOBAch = 37 . " %";
            // } elseif ($teamId == 22693) {
            //     $bustarget = 50.00;
            //     $busMtdAch = 13.40;
            //     $busAch = 27 . " %";
            //     $markTarget = 120;
            //     $markMtdAch = 43;
            //     $markAch = 36 . " %";
            //     $UOBTarget = 750;
            //     $UOBkMtdAch = 390;
            //     $UOBAch = 52 . " %";
            // } elseif ($teamId == 22713) {
            //     $bustarget = 30.00;
            //     $busMtdAch = 11.05;
            //     $busAch = 37 . " %";
            //     $markTarget = 124;
            //     $markMtdAch = 4;
            //     $markAch = 3 . " %";
            //     $UOBTarget = 450;
            //     $UOBkMtdAch = 275;
            //     $UOBAch = 61 . " %";
            // } elseif ($teamId == 22716) {
            //     $bustarget = 40.00;
            //     $busMtdAch = 5.39;
            //     $busAch = 13 . " %";
            //     $markTarget = 134;
            //     $markMtdAch = 5;
            //     $markAch = 4 . " %";
            //     $UOBTarget = 600;
            //     $UOBkMtdAch = 294;
            //     $UOBAch = 49 . " %";
            // }
            $arrTrackerDetails =  $this->tableUtil->getRowsColumns(
                "$dbName.tbl_fso_tracker",
                "parameters, target, mtd_ach, ach_per",
                "fso_id = $teamId AND dstatus = 0"
            );

            $arrProductivityDetails = array();
            foreach ($arrTrackerDetails as $trackerData) {
                $arrProductivityDetails[] = array(
                    "cardHeading" => $trackerData[0],
                    "kpis" => array(
                        array("label" => "Target", "value" => (string)$trackerData[1]),
                        array("label" => "MTD Ach", "value" => (string)$trackerData[2]),
                        array("label" => "Ach%", "value" => $trackerData[3]),
                    )
                );
            }

            $arrNotifications[] = [
                "id" => 1,
                "title" => "Productivity",
                "shortMessage" => null,
                "fullMessage" => null,
                "metadata" => $arrProductivityDetails
            ];
        }


        $response = $this->response->sendResponse(array("message" => "", "response" => $arrNotifications ? $arrNotifications : array()), 1);
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
