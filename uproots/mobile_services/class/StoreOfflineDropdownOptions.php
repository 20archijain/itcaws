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

        if ($dbName === $GLOBALS["WONDER_DB"]) {
            $arrConfig = array(
                array(
                    "jsonKey" => "projectList",
                    "tableName" => "$dbName." . $GLOBALS["TBL_PROJECTS"],
                    "condition" => "dstatus = 0 AND cid != 27",
                    "tableConfig" => array(
                        "labelColumn" => "projname",
                        "valueColumn" => "pid",
                        "addSubLevelOptions" => false,
                        "orderByColumn" => "projname",
                    ),
                ),
            );
        } elseif ($dbName === $GLOBALS["ZX_DB"]) {
            // Find district of team where he is linked to
            $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

            $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";

            if ($projectId == 15 || $projectId == 18 || $projectId == 22) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "shopList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "distributor_name",
                                    "orderByColumn" => "distributor_name",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "village",
                                        "orderByColumn" => "village",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "outlet_name",
                                            "valueColumn" => "rec_id",
                                            "orderByColumn" => "outlet_name",
                                            "addSubLevelOptions" => false,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif (
                $projectId == 20 || $projectId == 21 || $projectId == 24 || $projectId == 35 ||
                $projectId == 36 || $projectId == 42
            ) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "shopList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "distributor_name",
                            "orderByColumn" => "distributor_name",
                            "sublevelConfig" => array(
                                "labelColumn" => "district",
                                "orderByColumn" => "district",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "outlet_name",
                                        "orderByColumn" => "outlet_name",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "shop_owner_name",
                                            "orderByColumn" => "shop_owner_name",
                                            "sublevelConfig" => array(
                                                "labelColumn" => "shop_owner_phone",
                                                "valueColumn" => "rec_id",
                                                "orderByColumn" => "shop_owner_phone",
                                                "addSubLevelOptions" => false,
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 112) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "valueColumn" => "rec_id",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                );
            } elseif (
                $projectId == 19 || $projectId == 31 || $projectId == 44 || $projectId == 45 || $projectId == 58 ||
                $projectId == 61 || $projectId == 65 || $projectId == 66 || $projectId == 69 || $projectId == 70 ||
                $projectId == 72 || $projectId == 75 || $projectId == 80 || $projectId == 81 || $projectId == 83 ||
                $projectId == 85 || $projectId == 86 || $projectId == 87 || $projectId == 88 || $projectId == 89 ||
                $projectId == 90 || $projectId == 102 || $projectId == 104 || $projectId == 105 || $projectId == 106
            ) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => $projectId == 80 ?
                            "dstatus = 0 AND pid = $projectId $sDropdownCond" :
                            "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "outlet_name",
                                        "orderByColumn" => $projectId == 58 ? "" : "outlet_name",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "address",
                                            "valueColumn" => $projectId == 106 ? "rec_id" : "address",
                                            "orderByColumn" => "address",
                                            "addSubLevelOptions" => $projectId == 106 ? false : true,
                                            "sublevelConfig" => array(
                                                "labelColumn" => $projectId == 83 ? "site" : "shop_owner_name",
                                                "valueColumn" => "rec_id",
                                                "orderByColumn" => $projectId == 83 ? "site" : "shop_owner_name",
                                                "addSubLevelOptions" => false,
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif (
                $projectId == 109
            ) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "outlet_name",
                                        "orderByColumn" => "outlet_name",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "address",
                                            "orderByColumn" => "address",
                                            "sublevelConfig" => array(
                                                "labelColumn" => "shop_owner_name",
                                                "orderByColumn" => "shop_owner_name",
                                                "addSubLevelOptions" => false,
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif (
                $projectId == 93 || $projectId == 99 || $projectId == 101 || $projectId == 108
            ) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "outlet_name",
                                        "valueColumn" => "rec_id",
                                        "orderByColumn" => "outlet_name",
                                        "encodeLabel" => ($projectId == 93 || $projectId == 101) ?
                                            false : true,
                                        "addSubLevelOptions" => false,
                                        "addStaticOptions" => $projectId == 101 ? null : array(
                                            array(
                                                "label" => "Other",
                                                "value" => "Other",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 100) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "recceList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "outlet_name",
                                        "orderByColumn" => "outlet_name",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "shop_owner_name",
                                            "orderByColumn" => "shop_owner_name",
                                            "otherDetails" => array(
                                                "htmlTextColumns" => array(
                                                    array(
                                                        "label" => "Address: ",
                                                        "column" => "shop_owner_name",
                                                        "boldLabel" => true
                                                    ),
                                                ),
                                            ),
                                            "sublevelConfig" => array(
                                                "labelColumn" => "shop_owner_phone",
                                                "orderByColumn" => "shop_owner_phone",
                                                "otherDetails" => array(
                                                    "htmlTextColumns" => array(
                                                        array(
                                                            "label" => "Shop Number: ",
                                                            "column" => "shop_owner_phone",
                                                            "boldLabel" => true
                                                        ),
                                                    ),
                                                ),
                                                "sublevelConfig" => array(
                                                    "labelColumn" => "AE_name",
                                                    "orderByColumn" => "AE_name",
                                                    "otherDetails" => array(
                                                        "htmlTextColumns" => array(
                                                            array(
                                                                "label" => "TSM Name: ",
                                                                "column" => "AE_name",
                                                                "boldLabel" => true
                                                            ),
                                                        ),
                                                    ),
                                                    "sublevelConfig" => array(
                                                        "labelColumn" => "AE_phone",
                                                        "orderByColumn" => "AE_phone",
                                                        "otherDetails" => array(
                                                            "htmlTextColumns" => array(
                                                                array(
                                                                    "label" => "TSM Number: ",
                                                                    "column" => "AE_phone",
                                                                    "boldLabel" => true
                                                                ),
                                                            ),
                                                        ),
                                                        "valueColumn" => "rec_id",
                                                        "addSubLevelOptions" => false
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        "jsonKey" => "installtionList",
                        "condition" => "dstatus = 0 AND status = 1 $sDropdownCond",
                        "tableName" => "$dbName." . "v_airtel_dropdown",
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "city",
                                "orderByColumn" => "city",
                                "sublevelConfig" => array(
                                    "labelColumn" => "locality",
                                    "orderByColumn" => "locality",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "shop_name",
                                        "orderByColumn" => "shop_name",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "shop_address",
                                            "orderByColumn" => "shop_address",
                                            "otherDetails" => array(
                                                "htmlTextColumns" => array(
                                                    array(
                                                        "label" => "Address: ",
                                                        "column" => "shop_address",
                                                        "boldLabel" => true
                                                    ),
                                                ),
                                            ),
                                            "sublevelConfig" => array(
                                                "labelColumn" => "retailer_number",
                                                "orderByColumn" => "retailer_number",
                                                "otherDetails" => array(
                                                    "htmlTextColumns" => array(
                                                        array(
                                                            "label" => "Shop Number: ",
                                                            "column" => "retailer_number",
                                                            "boldLabel" => true
                                                        ),
                                                    ),
                                                ),
                                                "sublevelConfig" => array(
                                                    "labelColumn" => "tsm_name",
                                                    "orderByColumn" => "tsm_name",
                                                    "otherDetails" => array(
                                                        "htmlTextColumns" => array(
                                                            array(
                                                                "label" => "TSM Name: ",
                                                                "column" => "tsm_name",
                                                                "boldLabel" => true
                                                            ),
                                                        ),
                                                    ),
                                                    "sublevelConfig" => array(
                                                        "labelColumn" => "tsm_number",
                                                        "valueColumn" => "shop_id",
                                                        "orderByColumn" => "tsm_number",
                                                        "otherDetails" => array(
                                                            "htmlTextColumns" => array(
                                                                array(
                                                                    "label" => "TSM Number: ",
                                                                    "column" => "tsm_number",
                                                                    "boldLabel" => true
                                                                ),
                                                            ),
                                                        ),
                                                        "optionOptionsKey" => array("elementOptions"),
                                                        "sublevelConfig" => array(
                                                            "elementOptions" => array(
                                                                "labelColumn" => "element",
                                                                "orderByColumn" => "element",
                                                                "valueColumn" => "element",
                                                                "addSubLevelOptions" => false,
                                                            ),
                                                        ),
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
            } elseif ($projectId == 96 || $projectId == 97) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "site",
                                        "orderByColumn" => "site",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "address",
                                            "valueColumn" => "rec_id",
                                            "addSubLevelOptions" => false,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 110) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND Done = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "village",
                                "orderByColumn" => "village",
                                "sublevelConfig" => array(
                                    "labelColumn" => "site",
                                    "valueColumn" => "rec_id",
                                    "addSubLevelOptions" => false,
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 113 || $projectId == 115 || $projectId == 118) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND Done = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "site",
                                        "valueColumn" => "rec_id",
                                        "addSubLevelOptions" => false,
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 116) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND Done = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "subdistrict",
                            "orderByColumn" => "subdistrict",
                            "sublevelConfig" => array(
                                "labelColumn" => "village",
                                "orderByColumn" => "village",
                                "sublevelConfig" => array(
                                    "labelColumn" => "outlet_name",
                                    "orderByColumn" => "outlet_name",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "distributor_code",
                                        "valueColumn" => "rec_id",
                                        "addSubLevelOptions" => false,
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 95) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "village",
                                "orderByColumn" => "village",
                                "sublevelConfig" => array(
                                    "labelColumn" => "subdistrict",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "subdistrict",
                                    "addSubLevelOptions" => false,
                                ),
                            ),
                        ),
                    ),
                );
            } else {
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "village",
                                    "addSubLevelOptions" => false,
                                ),
                            ),
                        ),
                    ),
                );
            }
        } elseif ($dbName === $GLOBALS["NOVICEMARCOM_DB"]) {
            // Find district of team where he is linked to
            $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

            $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";

            $addLevel6 = ($projectId == 14 || $projectId == 28) ? false : true;
            $arrConfig = array(
                array(
                    "jsonKey" => "districtList",
                    "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "district",
                        "orderByColumn" => "district",
                        "sublevelConfig" => array(
                            "labelColumn" => "subdistrict",
                            "orderByColumn" => "subdistrict",
                            "sublevelConfig" => array(
                                "labelColumn" => "village",
                                "orderByColumn" => "village",
                                "sublevelConfig" => array(
                                    "labelColumn" => "outlet_name",
                                    "orderByColumn" => $projectId == 19 ? "" : "outlet_name",
                                    "sublevelConfig" => array(
                                        "labelColumn" => "address",
                                        "valueColumn" => $addLevel6 ? "" : "rec_id",
                                        "orderByColumn" => "address",
                                        "addSubLevelOptions" => $addLevel6 ? true : false,
                                        "sublevelConfig" => $addLevel6 ? array(
                                            "labelColumn" => "shop_owner_name",
                                            "valueColumn" => "rec_id",
                                            "orderByColumn" => "shop_owner_name",
                                            "addSubLevelOptions" => false,
                                        ) : null,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            );
        } elseif ($dbName === $GLOBALS["IMPACT_DB"]) {
            if ($projectId == 56) {
                // Find district of team where he is linked to
                $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

                $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                    $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district1",
                            "orderByColumn" => "district1",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "orderByColumn" => "village",
                                    "addSubLevelOptions" => false,
                                    "otherDetails" => array(
                                        "outletIdColumn" => "site",
                                        "addressColumn" => "address",
                                        "contactNoColumn" => "Primary_phone",
                                        "landmarkColumn" => "Cuisine2",
                                        "ltColumn" => "lt",
                                        "lgColumn" => "lg"
                                    ),
                                ),
                            ),
                        ),
                    ),
                );
            } else {
                // Find district of team where he is linked to
                $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

                $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                    $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";

                $includeSiteList = $projectId == 18 || $projectId == 32 || $projectId == 44 || $projectId == 50 ||
                    $projectId == 70 || $projectId == 76 || $projectId == 77 || $projectId == 88 ? true : false;

                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "valueColumn" => $includeSiteList ? "" : "rec_id",
                                    "orderByColumn" => "village",
                                    "addSubLevelOptions" => $includeSiteList,
                                    "sublevelConfig" => $includeSiteList ? array(
                                        "labelColumn" => "site",
                                        "orderByColumn" => "site",
                                        "sublevelConfig" => array(
                                            "labelColumn" => "address",
                                            "valueColumn" => "rec_id",
                                            "orderByColumn" => "address",
                                            "addSubLevelOptions" => false,
                                        ),
                                    ) : null,
                                ),
                            ),
                        ),
                    ),
                );
            }
        } elseif ($dbName === $GLOBALS["ITCNEW_DB"]) {
            if (
                $projectId == 15 || $projectId == 28 || $projectId == 38 || $projectId == 71 ||
                $projectId == 80 || $projectId == 100
            ) {
                // Find district of team where he is linked to
                $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

                $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                    $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "orderByColumn" => "subdistrict",
                                "sublevelConfig" => array(
                                    "labelColumn" => "village",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "village",
                                    "addSubLevelOptions" => false,
                                ),
                            ),
                        ),
                    ),
                );
            } elseif ($projectId == 112 || $projectId == 121) {
                // Find district of team where he is linked to
                $sCond = $this->getTeamDistrictCondition($dbName, $teamId);

                $sDropdownCond = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"]) ?
                    $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["dropdownCond"] : "";
                $arrConfig = array(
                    array(
                        "jsonKey" => "districtList",
                        "condition" => "dstatus = 0 AND pid = $projectId $sCond $sDropdownCond",
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                        "tableConfig" => array(
                            "labelColumn" => "district",
                            "orderByColumn" => "district",
                            "sublevelConfig" => array(
                                "labelColumn" => "subdistrict",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "subdistrict",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                );
            } else {
                list($iCity, $jsonId) = $this->tableUtil->getRowColumns(
                    "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
                    "city_id, s_id",
                    "team_id = $teamId"
                );
                $iCity = $iCity ? $iCity : 0;

                $commonCondition = "a.dstatus = 0 AND b.dstatus = 0 AND a.outlet_id = b.outlet_id" .
                    " AND b.project_id = $projectId";

                // Supervisor
                if ($jsonId == 3) {
                    // Check if $iCity contains multiple IDs
                    if (strpos($iCity, ',') !== false) {
                        // Handle multiple IDs
                        $cityIds = array_map('trim', explode(',', $iCity));
                        // Sanitize: ensure all are integers
                        $cityIds = array_filter($cityIds, 'is_numeric');
                        $idList = implode(',', $cityIds);

                        $supCondition = "AND city_id IN ($idList)";
                    } else {
                        // Single ID
                        $iCity = is_numeric($iCity) ? (int)$iCity : 0;
                        $supCondition = "AND city_id = $iCity";
                    }
                } else {
                    // Promoter
                    $supCondition = "AND team_id = $teamId";

                    // Check if any record found of team, if not get city records
                    $resAction = null;
                    $iNumRows = 0;
                    $sQuery = "SELECT a.outlet_id FROM $dbName.tbloutlet_master AS a, $dbName.tbloutlet_project AS b" .
                        " WHERE $commonCondition $supCondition LIMIT 1";
                    $this->dbConn->ExecuteSelectQuery($sQuery, $resAction, $iNumRows);

                    if ($iNumRows == 0) {
                        $supCondition = "AND city_id = $iCity";
                    }
                }

                $arrConfig = array(
                    array(
                        "jsonKey" => "storeList",
                        "condition" => "$commonCondition $supCondition",
                        "tableName" => "$dbName.tbloutlet_master AS a, $dbName.tbloutlet_project AS b",
                        "tableConfig" => array(
                            "labelColumn" => "CONCAT(a.storeName,' - ',a.storeLocation) AS label",
                            "valueColumn" => "rec_id",
                            "orderByColumn" => "label",
                            "useLabelAsNotNullClause" => false,
                            "distinctOptions" => false,
                            "addSubLevelOptions" => false,
                        ),
                    ),
                );
            }
        } elseif ($dbName === $GLOBALS["SNPL_DB"]) {
            $arrConfig = array(
                array(
                    "jsonKey" => "routeList",
                    "condition" => "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "route_name",
                        "orderByColumn" => "route_name",
                        "optionOptionsKey" => array("outletOptions", "marketOptions"),
                        "sublevelConfig" => array(
                            "outletOptions" => array(
                                "labelColumn" => "outlet_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "outlet_name",
                                "addSubLevelOptions" => false,
                                "otherDetails" => array(
                                    "htmlTextColumns" => array(
                                        array("column" => "market_name"),
                                    ),
                                    "contactNoColumn" => "outlet_mobile",
                                    "showMapIcon" => true,
                                ),
                            ),
                            "marketOptions" => array(
                                "labelColumn" => "market_name",
                                "orderByColumn" => "market_name",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                ),
                array(
                    "jsonKey" => "outletList",
                    "condition" => "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "outlet_name",
                        "valueColumn" => "rec_id",
                        "orderByColumn" => "outlet_name",
                        "addSubLevelOptions" => false,
                    ),
                ),
                array(
                    "jsonKey" => "marketList",
                    "condition" => "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "market_name",
                        "orderByColumn" => "market_name",
                        "addSubLevelOptions" => false,
                    ),
                ),
            );
        } elseif ($dbName === $GLOBALS["ITC_DB"]) {
            $todayDate = date('Y-m-d'); // Today's date
            $twoMonthsAgoDate = date('Y-m-d', strtotime('-2 months')); // Date two months ago
            $currentMonthStartDate = date('Y-m-01'); // Start of the current month

            $arrIds = $this->tableUtil->getRowsColumn(
                "$dbName.tblsurvey_response_details_pilot",
                "SUBSTRING(ques_2, 3, LENGTH(ques_2)-4)",
                "dstatus = 0 AND ques_0 = 'Sales Detail'" .
                    " AND capture_date BETWEEN '$twoMonthsAgoDate' AND '$todayDate' AND team_id = $teamId",
                array(),
                true
            );

            $arrLtLg = $this->tableUtil->getRowColumns("$dbName.tblproject_team", "base_lt,base_lt", "team_id = $teamId");

            $arrConfig = array(
                array(
                    "jsonKey" => "routeList",
                    "condition" => "dstatus = 0 AND team_id = $teamId",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "route_name",
                        "orderByColumn" => "route_name",
                        // "levelCondition" => "AND outlet_type = 'ROC'",
                        "optionOptionsKey" => array("outletOptions", "marketOptions"),
                        "sublevelConfig" => array(
                            "outletOptions" => array(
                                "labelColumn" => "outlet_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "outlet_name",
                                "levelCondition" => "AND outlet_type = 'ROC'",
                                "addSubLevelOptions" => false,
                                "otherDetails" => array(
                                    "htmlTextColumns" => array(
                                        array(
                                            "label" => "Market Name:",
                                            "column" => "market_name",
                                            "boldLabel" => true
                                        ),
                                    ),
                                    "contactNoColumn" => "outlet_mobile",
                                    "showMapIcon" => true,
                                ),
                            ),
                            "marketOptions" => array(
                                "labelColumn" => "market_name",
                                "orderByColumn" => "market_name",
                                // "levelCondition" => "AND outlet_type = 'ROC'",
                                "sublevelConfig" => array(
                                    "labelColumn" => "outlet_name",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "outlet_name",
                                    "levelCondition" => "AND outlet_type = 'Other'",
                                    "addStaticOptions" => array(
                                        array(
                                            "label" => "Other",
                                            "value" => "Other",
                                        ),
                                    ),
                                    "addSubLevelOptions" => false,
                                    "otherDetails" => array(
                                        "contactNoColumn" => "outlet_mobile",
                                        "showMapIcon" => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                    "baseLt" => $arrLtLg[0],
                    "baseLg" => $arrLtLg[1],
                ),
            );
        } elseif ($dbName === $GLOBALS["DELHI_DB"]) {
            $todayDate = date('Y-m-d'); // Today's date
            $twoMonthsAgoDate = date('Y-m-d', strtotime('-2 months')); // Date two months ago
            $currentMonthStartDate = date('Y-m-01'); // Start of the current month

            $arrIds = $this->tableUtil->getRowsColumn(
                "$dbName.tblsurvey_response_details",
                "SUBSTRING(ques_2, 3, LENGTH(ques_2)-4)",
                "dstatus = 0 AND ques_0 = 'ROC Delivery'" .
                    " AND capture_date BETWEEN '$currentMonthStartDate' AND '$todayDate' AND team_id = $teamId",
                array(),
                true
            );

            $arrConfig = array(
                array(
                    "jsonKey" => "routeList",
                    "condition" => "dstatus = 0 AND team_id = $teamId",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "route_name",
                        "orderByColumn" => "route_name",
                        // "levelCondition" => "AND outlet_type = 'ROC'",
                        "optionOptionsKey" => array("outletOptions", "marketOptions"),
                        "sublevelConfig" => array(
                            "outletOptions" => array(
                                "labelColumn" => "outlet_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "outlet_name",
                                "levelCondition" => "AND outlet_type = 'ROC'",
                                "addSubLevelOptions" => false,
                                "otherDetails" => array(
                                    "htmlTextColumns" => array(
                                        array(
                                            "label" => "Market Name:",
                                            "column" => "market_name",
                                            "boldLabel" => true
                                        ),
                                    ),
                                    "contactNoColumn" => "outlet_mobile",
                                    "showMapIcon" => true,
                                ),
                            ),
                            "marketOptions" => array(
                                "labelColumn" => "market_name",
                                "orderByColumn" => "market_name",
                                // "levelCondition" => "AND outlet_type = 'ROC'",
                                "sublevelConfig" => array(
                                    "labelColumn" => "outlet_name",
                                    "valueColumn" => "rec_id",
                                    "orderByColumn" => "outlet_name",
                                    "levelCondition" => "AND outlet_type = 'Other'",
                                    "addStaticOptions" => array(
                                        array(
                                            "label" => "Other",
                                            "value" => "Other",
                                        ),
                                    ),
                                    "addSubLevelOptions" => false,
                                    "otherDetails" => array(
                                        "contactNoColumn" => "outlet_mobile",
                                        "showMapIcon" => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    "jsonKey" => "districtList",
                    "condition" => "dstatus = 0 AND team_id = $teamId AND (kyc_verified IS NULL OR kyc_verified = 0)",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "route_name",
                        "orderByColumn" => "route_name",
                        "sublevelConfig" => array(
                            "labelColumn" => "market_name",
                            "orderByColumn" => "market_name",
                            "sublevelConfig" => array(
                                "labelColumn" => "outlet_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "outlet_name",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                ),
                array(
                    "jsonKey" => "gtTlKycList",
                    "condition" => "dstatus = 0 AND team_id = $teamId AND done = 0",
                    "tableName" => "$dbName." . "v_gt_tl_kyc_dropdown",
                    "tableConfig" => array(
                        "labelColumn" => "csr_name",
                        "orderByColumn" => "csr_name",
                        "sublevelConfig" => array(
                            "labelColumn" => "route_name",
                            "orderByColumn" => "route_name",
                            "sublevelConfig" => array(
                                "labelColumn" => "shop_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "shop_name",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                ),
                array(
                    "jsonKey" => "gtTlVisitList",
                    "condition" => "dstatus = 0 AND team_id = $teamId",
                    "tableName" => "$dbName." . "v_gt_tl_visit_dropdown",
                    "tableConfig" => array(
                        "labelColumn" => "csr_name",
                        "orderByColumn" => "csr_name",
                        "sublevelConfig" => array(
                            "labelColumn" => "route_name",
                            "orderByColumn" => "route_name",
                            "sublevelConfig" => array(
                                "labelColumn" => "shop_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "shop_name",
                                "addSubLevelOptions" => false,
                            ),
                        ),
                    ),
                ),
                array(
                    "jsonKey" => "gtTlAddList",
                    "condition" => "dstatus = 0 AND team_id = $teamId",
                    "tableName" => "$dbName." . "v_gt_tl_kyc_dropdown",
                    "tableConfig" => array(
                        "labelColumn" => "csr_name",
                        "orderByColumn" => "csr_name",
                        "sublevelConfig" => array(
                            "labelColumn" => "route_name",
                            "valueColumn" => "rec_id",
                            "orderByColumn" => "route_name",
                            "addSubLevelOptions" => false,
                        ),
                    ),
                ),
            );
        } elseif ($dbName === $GLOBALS["SOUTH_DB"]) {
            $arrConfig = array(
                array(
                    "jsonKey" => "routeList",
                    "condition" => "dstatus = 0 AND team_id = $teamId",
                    "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
                    "tableConfig" => array(
                        "labelColumn" => "route_name",
                        "orderByColumn" => "route_name",
                        "optionOptionsKey" => "marketOptions",
                        "sublevelConfig" => array(
                            "labelColumn" => "market_name",
                            "orderByColumn" => "market_name",
                            "sublevelConfig" => array(
                                "labelColumn" => "outlet_name",
                                "valueColumn" => "rec_id",
                                "orderByColumn" => "outlet_name",
                                "addSubLevelOptions" => false,
                                "otherDetails" => array(
                                    "contactNoColumn" => "outlet_mobile",
                                    "showMapIcon" => true,
                                ),
                            ),
                        ),
                    ),
                ),
            );
        } elseif ($dbName === $GLOBALS["ITCPH2_DB"]) {
            $todayDate = date('Y-m-d'); // Today's date
            $twoMonthsAgoDate = date('Y-m-d', strtotime('-1 months')); // Date one months ago
            $arrIds = $this->tableUtil->getRowsColumn(
                "$dbName.tblsurvey_response_details",
                "ques_3",
                "dstatus = 0 AND ques_0 = 'Outlet Order'" .
                    " AND capture_date BETWEEN '$twoMonthsAgoDate' AND '$todayDate' AND team_id = $teamId",
                array(),
                true
            );
            $jsonId = $this->tableUtil->getRowColumn(
                "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
                "s_id",
                "team_id = $teamId"
            );
            if ($jsonId == 10) {
                $arrConfig = array(
                    array(
                        "jsonKey" => "mdoDataList",
                        "condition" => "dstatus = 0 AND team_id = $teamId",
                        "tableName" => "$dbName." . "tblmdo_offline_data",
                        "tableConfig" => array(
                            "labelColumn" => "wd_code",
                            "orderByColumn" => "wd_code",
                            "sublevelConfig" => array(
                                "labelColumn" => "ds_name",
                                "orderByColumn" => "ds_name",
                                "valueColumn" => "type",
                                "sublevelConfig" => array(
                                    "labelColumn" => "route_name",
                                    "orderByColumn" => "route_name",
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
                                                "listKpiFirst" => array(
                                                    array(
                                                        "label" => "Total Survey Qty",
                                                        "value" => "total_survey_qty",
                                                    ),
                                                    array(
                                                        "label" => "Avg Survey Qty",
                                                        "value" => "avg_survey_qty",
                                                    ),
                                                    array(
                                                        "label" => "Avg CFT",
                                                        "value" => "avg_cft",
                                                    ),
                                                    array(
                                                        "label" => "ULC",
                                                        "value" => "ulc",
                                                    ),
                                                    array(
                                                        "label" => "Total Visit",
                                                        "value" => "total_visits",
                                                    ),
                                                ),
                                                "listKpiSecond" => array(
                                                    array(
                                                        "label" => "Last DS Visit",
                                                        "value" => "last_ds_visit",
                                                    ),
                                                    array(
                                                        "label" => "Last Order",
                                                        "value" => "last_order",
                                                    ),
                                                    array(
                                                        "label" => "Highest Survey Product",
                                                        "value" => "highest_survey_product",
                                                    ),
                                                    array(
                                                        "label" => "Lowest Survey Product",
                                                        "value" => "focus1_last_purchase",
                                                    ),
                                                    array(
                                                        "label" => "Focus 1 Last Purchase",
                                                        "value" => "focus1_last_purchase",
                                                    ),
                                                    array(
                                                        "label" => "Focus 2 Last Purchase",
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
                        "tableName" => "$dbName." . $GLOBALS["TBL_ROUTE_DETAILS"],
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

        // add issues for each record
        if (
            $dbName === $GLOBALS["ZX_DB"] && ($projectId == 46 || $projectId == 95) &&
            $arrOptions && $this->commonFunctions->isNonEmptyArray($arrOptions)
        ) {
            $arrOptions = $this->getZxTMCallsIssues($dbName, $projectId, $teamId, $arrOptions);
        } elseif ($dbName === $GLOBALS["DELHI_DB"]) {
            // send pickup avg stock for each product
            $arrProductwiseSale = $this->getDelhiStock($dbName, $teamId);

            $arrOptions[] = array(
                "key" => "salesList",
                "productwiseSales" => $arrProductwiseSale,
            );
            foreach ($arrOptions as &$arrRoute) {
                if (isset($arrRoute["dropDownItemList"]) && $arrRoute["dropDownItemList"]) {
                    foreach ($arrRoute["dropDownItemList"] as &$routeItem) {
                        if (isset($routeItem["outletOptions"]) && $routeItem["outletOptions"]) {
                            foreach ($routeItem["outletOptions"] as &$arrOutlet) {
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
        } elseif ($dbName === $GLOBALS["ITC_DB"]) {
            $arrOptions[0]["allowed_distance_in_mtr"] = 100;
            foreach ($arrOptions as &$arrRoute) {
                foreach ($arrRoute["dropDownItemList"] as &$routeItem) {
                    foreach ($routeItem["outletOptions"] as &$arrOutlet) {
                        if (!empty($arrOutlet["value"]) && !in_array($arrOutlet["value"], $arrIds)) {
                            $arrOutlet["otherDetails"]["backGroundColour"] = "#FF0000";
                        } else {
                            $arrOutlet["otherDetails"]["backGroundColour"] = "";
                        }
                    }
                }
            }
        } elseif ($dbName === $GLOBALS["ITCPH2_DB"]) {
            $branchId = $this->tableUtil->getRowColumn(
                "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
                "branch_id",
                "team_id = $teamId"
            );
            $allowedBranchIds = [1, 2, 3, 4, 5, 6, 30];
            if (in_array($branchId, $allowedBranchIds)) {
                $arrOptions[0]["allowed_distance_in_mtr"] = 300;
            }
            // Add today's date in 'Y-m-d' format
            $arrOptions[0]["today_date"] = date("Y-m-d");
            foreach ($arrOptions as &$arrRoute) {
                foreach ($arrRoute["dropDownItemList"] as &$routeItem) {
                    if (isset($routeItem["outletOptions"]) && $routeItem["outletOptions"]) {
                        foreach ($routeItem["outletOptions"] as &$arrOutlet) {
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
        return $arrOptions && $this->commonFunctions->isNonEmptyArray($arrOptions) ? $arrOptions : null;
    }

    private function getZxTMCallsIssues($dbName, $projectId, $teamId, $arrOptions)
    {
        // Beat/Route
        foreach ($arrOptions[0]["dropDownItemList"] as $districtIndex => $arrDistrict) {
            // Dss Name
            foreach ($arrDistrict["options"] as $subdistrictIndex => $arrSubdistrict) {
                // if ($projectId == 95) {
                //     $arrOptions[0]["dropDownItemList"][$districtIndex]["options"][$subdistrictIndex]["checkboxOptions"] =
                //         $this->getIssuesList($dbName, $teamId, $subdistrictIndex, $arrSubdistrict);
                // } else {
                // Shop Name
                foreach ($arrSubdistrict["options"] as $villageIndex => $arrVillage) {
                    $arrOptions[0]["dropDownItemList"][$districtIndex]["options"][$subdistrictIndex]["options"][$villageIndex]["checkboxOptions"] =
                        $this->getIssuesList($dbName, $teamId, $villageIndex, $arrVillage);
                }
                // }
            }
        }

        return $arrOptions;
    }

    private function getIssuesList($dbName, $teamId, $index, $arrData)
    {
        $arrIssues = array();
        if ($index > 0) {
            $iRecId = $arrData["value"];

            $rsIssueRes = null;
            $iIssueNoRows = 0;
            $sIssueQuery = "SELECT issue_id, capture_date, issue_desc FROM" .
                " $dbName.tblsnpl_issues WHERE dstatus = 0 AND team_id = $teamId" .
                " AND shop_id = $iRecId AND is_issue_resolved = 0";
            $this->dbConn->ExecuteSelectQuery($sIssueQuery, $rsIssueRes, $iIssueNoRows);

            if ($iIssueNoRows > 0) {
                while ($rowIssue = $this->dbConn->GetData($rsIssueRes)) {
                    $issueId = $rowIssue["issue_id"];
                    $captureDate = $rowIssue["capture_date"];
                    $issueDesc = $rowIssue["issue_desc"];

                    $arrIssues[] =
                        array(
                            "label" => htmlentities($issueDesc . " on " .
                                $this->commonFunctions->currentDate("d-m-Y", $captureDate)),
                            "value" => (int) htmlentities($issueId),
                        );
                }

                $arrIssues[] =
                    array(
                        "label" => "No issues resolved",
                        "value" => 0,
                    );
            } else {
                $arrIssues[] =
                    array(
                        "label" => "No issues reported",
                        "value" => 0,
                    );
            }
        }

        return $arrIssues;
    }

    private function getDelhiStock($dbName, $teamId)
    {
        $branchId = $this->tableUtil->getRowColumn(
            "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
            "branch_id",
            "team_id = $teamId"
        );
        $branchId = $branchId ? $branchId : 1;

        // get product list
        $rsProductRes = null;
        $iProductNoRows = 0;
        $sProductQuery = "SELECT DISTINCT product_name, summary_column_name" .
            " FROM $dbName.tblbranch_pickupstock_products WHERE dstatus = 0 AND branch_id = $branchId";
        $this->dbConn->ExecuteSelectQuery($sProductQuery, $rsProductRes, $iProductNoRows);

        $arrProductwiseSale = array();
        $arrProductwiseSummaryColumn = array();
        $arrProductwiseAvgSummaryColumn = array();
        if ($iProductNoRows > 0) {
            while ($rowProduct = $this->dbConn->GetData($rsProductRes)) {
                $arrProductwiseSale[$rowProduct["product_name"]] = 0;
                $arrProductwiseSummaryColumn[$rowProduct["product_name"]] = $rowProduct["summary_column_name"];
                $arrProductwiseAvgSummaryColumn[] = "AVG(" . $rowProduct["summary_column_name"] . ") AS " .
                    $rowProduct["summary_column_name"];
            }
        }

        // get avg stock for each product for last 4 weeks
        $currentDate = $this->commonFunctions->currentDate();
        $past4WeekDate = date("Y-m-d", strtotime("-4 week"));
        $sProductwiseAvgSummaryColumn = implode(",", $arrProductwiseAvgSummaryColumn);
        $rsStockRes = null;
        $iStockNoRows = 0;
        $sStockQuery = "SELECT $sProductwiseAvgSummaryColumn FROM $dbName.tblstock_summary" .
            " WHERE dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN" .
            " '$past4WeekDate' AND '$currentDate' AND stock_type = 0";
        $this->dbConn->ExecuteSelectQuery($sStockQuery, $rsStockRes, $iStockNoRows);

        if ($iStockNoRows > 0) {
            $rowStock = $this->dbConn->GetData($rsStockRes);
            foreach ($arrProductwiseSummaryColumn as $productName => $productSummaryColumn) {
                $iAvgStock = isset($rowStock[$productSummaryColumn]) ?
                    round($rowStock[$productSummaryColumn]) : 0;
                $arrProductwiseSale[$productName] = $iAvgStock;
            }
        }

        return $arrProductwiseSale;
    }

    private function getTeamDistrictCondition($dbName, $teamId)
    {
        // Find district of team where he is linked to
        $sDistrict = $this->tableUtil->getRowColumn(
            "$dbName.{$GLOBALS["TBL_PROJECT_TEAM"]}",
            "district",
            "team_id = $teamId"
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
            " dstatus = 0 AND db_name = '$dbName' $condition AND team_id NOT IN (SELECT DISTINCT team_id" .
            " FROM $dbName.{$this->offlineDropdownTable} WHERE dstatus = 0)";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows);

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
