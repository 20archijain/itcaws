<?php

// phpcs:ignore
class StoreOfflineDropdownOptions
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $bUpdateIfExists = false;
    private $cloudTable = null;
    private $offlineDropdownTable = null;
    private $currentDate = null;
    private $currentDatetime = null;
    private $previousDayDate = null;
    private $arrDBProjectDetails = array();
    private $arrDBWiseTeamsDone = array();

    public function __construct($dbConn, $tableUtil, $commonFunctions, $bUpdateIfExists = false)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->bUpdateIfExists = $bUpdateIfExists;
        $this->cloudTable = $GLOBALS["TBL_CLOUD_AUTH_PIN"];
        $this->offlineDropdownTable = $GLOBALS["TBL_OFFLINE_DROPDOWN_OPTIONS"];
        $this->currentDate = $this->commonFunctions->currentDate();
        $this->currentDatetime = $this->commonFunctions->currentDateTime();
        $this->previousDayDate = date("Y-m-d", strtotime("-1 day"));
        $this->arrDBProjectDetails = $GLOBALS["arrDBProjectDetails"];
    }

    private function clearOfflineDropdownTable($dbName)
    {
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT odo_id FROM $dbName.{$this->offlineDropdownTable} WHERE dstatus = 0" .
            " AND activity_date = '{$this->previousDayDate}'";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $rsTruncateAction = null;
            $iTruncateActionRows = 0;
            $sTruncateQuery = "TRUNCATE $dbName.{$this->offlineDropdownTable}";
            $this->dbConn->ExecuteQuery($sTruncateQuery, $rsTruncateAction, $iTruncateActionRows);
        }
    }

    private function storeTeamDropdownOptions($dbName, $clientId, $projectId, $teamId)
    {
        // Check if record exists or not, if not, then add else update
        $odoId = $this->tableUtil->getRowColumn(
            "$dbName.{$this->offlineDropdownTable}",
            "odo_id",
            "dstatus = 0 AND client_id = ? AND project_id = ? AND team_id = ? AND activity_date = ?",
            array($clientId, $projectId, $teamId, $this->currentDate)
        );

        // Not exist, so add
        if (!$odoId) {
            // get dropdown options
            $arrOptions = $this->getTeamDropdownOptions($dbName, $clientId, $projectId, $teamId);

            // Add in table
            $this->tableUtil->addRecord(
                "$dbName.{$this->offlineDropdownTable}",
                "client_id, project_id, team_id, options_list, activity_date, activity_datetime",
                "?, ?, ?, ?, ?, ?",
                array(
                    $clientId,
                    $projectId,
                    $teamId,
                    $arrOptions ? json_encode($arrOptions) : null,
                    $this->currentDate,
                    $this->currentDatetime
                )
            );
        } elseif ($odoId && $this->bUpdateIfExists) {
            // exists so update existing record
            // get dropdown options
            $arrOptions = $this->getTeamDropdownOptions($dbName, $clientId, $projectId, $teamId);

            // Update in table
            $this->tableUtil->updateRecord(
                "$dbName.{$this->offlineDropdownTable}",
                "options_list = ?",
                "odo_id = ?",
                array(json_encode($arrOptions), $odoId)
            );
        }
    }

    private function getTeamDropdownOptions($dbName, $clientId, $projectId, $teamId)
    {
        $arrConfig = null;
        $arrOptions = null;

        if ($dbName === $GLOBALS["ITCPH2_DB"]) {
            $todayDate = date('Y-m-d'); // Today's date
            $twoMonthsAgoDate = date('Y-m-d', strtotime('-1 months')); // Date one month ago
            $arrIds = $this->tableUtil->getRowsColumn(
                "$dbName.tblsurvey_response_details",
                "ques_3",
                "dstatus = 0" .
                    " AND capture_date BETWEEN '$twoMonthsAgoDate' AND '$todayDate' AND team_id = ?",
                array($teamId),
                true
            );
            $jsonId = $this->tableUtil->getRowColumn(
                "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
                "s_id",
                "team_id = ?",
                array($teamId)
            );
            if ($jsonId == 10) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "mdoDataList",
                        "condition" => "dstatus = 0 AND team_id = $teamId",
                        "tableName" => "$dbName.tblmdo_offline_data",
                        "tableConfig" => array(
                            "labelColumn" => "wd_code",
                            "orderByColumn" => "wd_code",
                            "sublevelConfig" => array(
                                "labelColumn" => "CONCAT(ds_name, ' - ', type_name)",
                                "orderByColumn" => "ds_name",
                                "valueColumn" => "COALESCE(type, '')",
                                "sublevelConfig" => array(
                                    "labelColumn" => "route_name",
                                    "orderByColumn" => "sort_order",
                                    "optionOptionsKey" => array("outletOptions"),
                                    "sublevelConfig" => array(
                                        "outletOptions" => array(
                                            "labelColumn" => "outlet_name",
                                            "valueColumn" => "outlet_id",
                                            "orderByColumn" => "outlet_name",
                                            "addSubLevelOptions" => false,
                                            "otherDetails" => array(
                                                "outletIdColumn" => "outlet_id",
                                                "addressColumn" => "address",
                                                "landmarkColumn" => "address",
                                                "contactNoColumn" => "outlet_number",
                                                "datetimeInMilisec" => "date_time",
                                                "showMapIcon" => true,
                                                "outletOuterDetails" => array(
                                                    array(
                                                        "label" => "Last Visit Date",
                                                        "value" => "last_ds_visit",
                                                    ),
                                                    array(
                                                        "label" => "Last Billed Date",
                                                        "value" => "last_order",
                                                    ),
                                                ),
                                                "listKpiFirst" => array(
                                                    array(
                                                        "label" => "Avg ULC per transaction",
                                                        "value" => "ulc",
                                                    ),
                                                    array(
                                                        "label" => "Avg Survey (M) per transaction",
                                                        "value" => "avg_survey_qty",
                                                    ),
                                                    array(
                                                        "label" => "Total Survey (M)",
                                                        "value" => "total_survey_qty",
                                                    ),
                                                    // array(
                                                    //     "label" => "Avg CFT",
                                                    //     "value" => "avg_cft",
                                                    // ),
                                                    array(
                                                        "label" => "No. of times visited",
                                                        "value" => "total_visits",
                                                    ),
                                                ),
                                                "listKpiSecond" => array(
                                                    array(
                                                        "label" => "Last Visit Date",
                                                        "value" => "last_ds_visit",
                                                    ),
                                                    array(
                                                        "label" => "Last Billed Date",
                                                        "value" => "last_order",
                                                    ),
                                                    array(
                                                        "label" => "Highest Survey Variant",
                                                        "value" => "highest_survey_product",
                                                    ),
                                                    array(
                                                        "label" => "Lowest Survey Variant",
                                                        "value" => "lowest_survey_product",
                                                    ),
                                                    array(
                                                        "label" => "GF Indie Mint Last Purchase",
                                                        "value" => "focus1_last_purchase",
                                                    ),
                                                    array(
                                                        "label" => "GF Neo Smart Last Purchase",
                                                        "value" => "focus2_last_purchase",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } else {
                $arrConfig = array(
                    array(
                        "jsonKey" => "routeList",
                        "condition" => "dstatus = 0 AND team_id = $teamId",
                        "tableName" => "$dbName.{$GLOBALS["TBL_ROUTE_DETAILS"]}",
                        "tableConfig" => array(
                            "labelColumn" => "route_name",
                            "orderByColumn" => "sort_order",
                            "optionOptionsKey" => array("outletOptions"),
                            "sublevelConfig" => array(
                                "outletOptions" => array(
                                    "labelColumn" => "outlet_name",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "sort_order",
                                    "addSubLevelOptions" => false,
                                    "otherDetails" => array(
                                        "contactNoColumn" => "outlet_mobile",
                                        "kyc_done" => "kyc_done",
                                        "showMapIcon" => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            }
        }

        if ($arrConfig && $this->commonFunctions->isNonEmptyArray($arrConfig)) {
            $offlineDropdownOptions = new GetOfflineDropdownOptions($this->dbConn, $this->commonFunctions, $arrConfig);
            $arrOptions = $offlineDropdownOptions->getDropdownOptions();
        }

        // Special post-generation modifications for ITCPH2_DB + jsonId == 10
        if ($dbName === $GLOBALS["ITCPH2_DB"] && isset($jsonId) && $jsonId == 10 && $arrOptions && $this->commonFunctions->isNonEmptyArray($arrOptions)) {
            // Query to determine isRoutePending and routeInfo for each route
            $routeData = [];
            $rsRouteRes = null;
            $iRouteRows = 0;
            $sRouteQuery = "SELECT route_name, SUM(total_survey_qty) as total_sale, MAX(last_ds_visit) as last_market_visit, " .
                "AVG(total_survey_qty) as avg_sale, AVG(ulc) as avg_ulc, COUNT(*) as outlet_count " .
                "FROM $dbName.tblmdo_offline_data WHERE dstatus = 0 AND team_id = ? GROUP BY route_name";
            $this->dbConn->ExecuteSelectQuery($sRouteQuery, $rsRouteRes, $iRouteRows, array($teamId));

            if ($iRouteRows > 0) {
                while ($row = $this->dbConn->GetData($rsRouteRes)) {
                    $routeName = $row['route_name'] ?? '';
                    $totalSale = floatval($row['total_sale'] ?? 0);
                    $lastMarketVisit = $row['last_market_visit'] ?? null;
                    $avgSale = floatval($row['avg_sale'] ?? 0);
                    $avgUlc = floatval($row['avg_ulc'] ?? 0);
                    $outletCount = intval($row['outlet_count'] ?? 1); // Avoid division by zero
                    $routeData[$routeName] = [
                        'isRoutePending' => ($totalSale > 0) ? 0 : 1,
                        'lastMarketVisit' => $lastMarketVisit ? $lastMarketVisit : '0000-00-00',
                        'avgSalePerOutlet' => $outletCount > 0 ? number_format($avgSale, 1, '.', '') : '0.0',
                        'avgUlcPerOutlet' => $outletCount > 0 ? number_format($avgUlc, 1, '.', '') : '0.0',
                    ];
                }
            }

            // Add isRoutePending and routeInfo to routes
            if ($arrOptions && is_array($arrOptions) && count($arrOptions) > 0) {
                foreach ($arrOptions as $optionIndex => &$arrOption) {
                    if (isset($arrOption['dropDownItemList']) && is_array($arrOption['dropDownItemList'])) {
                        foreach ($arrOption['dropDownItemList'] as $wdIndex => &$wdItem) {
                            if (isset($wdItem['options']) && is_array($wdItem['options'])) {
                                foreach ($wdItem['options'] as $dsIndex => &$dsItem) {
                                    if (isset($dsItem['options']) && is_array($dsItem['options'])) {
                                        foreach ($dsItem['options'] as $routeIndex => &$routeItem) {
                                            $routeName = $routeItem['label'] ?? '';
                                            $decodedRouteName = html_entity_decode($routeName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                            $routeInfo = isset($routeData[$decodedRouteName]) ? $routeData[$decodedRouteName] : [
                                                'isRoutePending' => 1,
                                                'lastMarketVisit' => '0000-00-00',
                                                'avgSalePerOutlet' => '0.0',
                                                'avgUlcPerOutlet' => '0.0',
                                            ];
                                            $routeItem['isRoutePending'] = $routeInfo['isRoutePending'];
                                            $routeItem['routeInfo'] = [
                                                [
                                                    'label' => 'Last Mkt Visit',
                                                    'value' => $routeInfo['lastMarketVisit'],
                                                ],
                                                [
                                                    'label' => 'Avg Sale/OL',
                                                    'value' => $routeInfo['avgSalePerOutlet'],
                                                ],
                                                [
                                                    'label' => 'Avg ULC/OL',
                                                    'value' => $routeInfo['avgUlcPerOutlet'],
                                                ],
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // Add today's date for jsonId == 10
            if (isset($arrOptions[0])) {
                $arrOptions[0]["today_date"] = date("Y-m-d");
            }
        }

        // Post-generation modifications for ITCPH2_DB + jsonId != 10
        if ($dbName === $GLOBALS["ITCPH2_DB"] && (!isset($jsonId) || $jsonId != 10) && $arrOptions && $this->commonFunctions->isNonEmptyArray($arrOptions)) {
            // Set additional properties
            $branchId = $this->tableUtil->getRowColumn(
                "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
                "branch_id",
                "team_id = ?",
                array($teamId)
            );
            $allowedBranchIds = [100];
            // $allowedBranchIds = [1, 2, 3, 4, 5, 30, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 20];
            if (in_array($branchId, $allowedBranchIds) && isset($arrOptions[0])) {
                $arrOptions[0]["allowed_distance_in_mtr"] = 700;
            }
            // Add today's date in 'Y-m-d' format
            if (isset($arrOptions[0])) {
                $arrOptions[0]["today_date"] = date("Y-m-d");
                $arrOptions[0]["today_day"] = date("D");
            }

            // Set backGroundColour for outlets
            foreach ($arrOptions as &$arrRoute) {
                if (isset($arrRoute["dropDownItemList"]) && is_array($arrRoute["dropDownItemList"])) {
                    foreach ($arrRoute["dropDownItemList"] as &$item) {
                        if (isset($item["outletOptions"]) && is_array($item["outletOptions"])) {
                            foreach ($item["outletOptions"] as &$arrOutlet) {
                                if (!isset($arrOutlet["otherDetails"])) {
                                    $arrOutlet["otherDetails"] = [];
                                }
                                if (!empty($arrOutlet["value"]) && !in_array($arrOutlet["value"], $arrIds)) {
                                    $arrOutlet["otherDetails"]["backGroundColour"] = "#FF0000";
                                } else {
                                    $arrOutlet["otherDetails"]["backGroundColour"] = "";
                                }
                            }
                        }
                    }
                }
            }
        }

        return $arrOptions && $this->commonFunctions->isNonEmptyArray($arrOptions) ? $arrOptions : null;
    }

    private function getTeamDistrictCondition($dbName, $teamId)
    {
        // Find district of team where he is linked to
        $sDistrict = $this->tableUtil->getRowColumn(
            "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
            "district",
            "team_id = ?",
            array($teamId)
        );
        $arrDistrict = $sDistrict ? explode(",", $sDistrict) : array();
        $sDistrictList = $this->commonFunctions->isNonEmptyArray($arrDistrict) ?
            $this->commonFunctions->getStringFromArray($arrDistrict) : "";
        $sCond = $sDistrictList ? "AND district IN ($sDistrictList)" : "AND team_id = $teamId";

        return $sCond;
    }

    final public function storeDBWiseOptions($dbName, $condition = "")
    {
        // Clear previous day data from table
        $this->clearOfflineDropdownTable($dbName);

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT db_name, client_id, project_id, team_id FROM {$this->cloudTable} WHERE" .
            " dstatus = 0 AND db_name = ? $condition AND team_id NOT IN (SELECT DISTINCT team_id" .
            " FROM $dbName.{$this->offlineDropdownTable} WHERE dstatus = 0)";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, array($dbName));

        if ($iNoRows > 0) {
            while ($row = $this->dbConn->GetData($rsRes)) {
                $dbName = $row['db_name'];
                $clientId = $row['client_id'];
                $projectId = $row['project_id'];
                $teamId = $row['team_id'];

                // store dropdown options
                if (!isset($this->arrDBWiseTeamsDone[$dbName][$teamId])) {
                    $this->storeTeamDropdownOptions($dbName, $clientId, $projectId, $teamId);
                    $this->arrDBWiseTeamsDone[$dbName][$teamId] = true;
                }
            }
        }
    }
}
