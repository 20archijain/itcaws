<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";

$currentDateTime = $commonFunctions->currentDateTime();

$logFileName = "debug_get_offline_dropdown_options";
$unauthorisedAccessLogFileName = "debug_get_offline_dropdown_options_unauthorised_access";
$logFolderName = "/offline_dropdown";

$response = new Response();
$logResponse = array(
    "log" => true,
    "fileName" => $logFileName,
    "folderName" => $logFolderName,
);

$sToken = "";
if ($_SERVER["PHP_AUTH_PW"] && $_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"] === $_SERVER["PHP_AUTH_USER"]) {
    $sToken = $_SERVER["PHP_AUTH_PW"];
}

$commonFunctions->debugLog(
    "\r\nSERVER LOG DATE TIME: $currentDateTime Token: $sToken",
    $logFileName,
    $logFolderName
);

// token not set
if (!$sToken) {
    // Unauthorized access
    $commonFunctions->debugLog(
        "SERVER LOG DATE TIME: $currentDateTime Token: $sToken\r\n" . $arrAuthMessages["AUTH04"],
        $unauthorisedAccessLogFileName,
        $logFolderName
    );
    $commonFunctions->debugLog(
        $arrAuthMessages["AUTH04"],
        $logFileName,
        $logFolderName
    );
    $response->sendResponse(array("message" => $arrAuthMessages["AUTH04"]));
} else {
    $sQuery_Org = "SELECT db_name, client_id, project_id, team_id FROM $TBL_CLOUD_AUTH_PIN" .
        " WHERE token = '$sToken' AND dstatus = 0 LIMIT 1";

    $sQuery = "SELECT db_name, client_id, project_id, team_id FROM $TBL_CLOUD_AUTH_PIN" .
        " WHERE token = ? AND dstatus = 0 LIMIT 1";
    $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, array($sToken));

    // User found
    if ($iActionRows === 1) {
        $row = $dbConn->GetData($rsAction);

        $db_name = $row['db_name'];
        $clientId = $row['client_id'];
        $projectId = $row['project_id'];
        $teamId = $row['team_id'];
        $arrBranchListWithNewApp = array(
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17,
            18, 19, 20, 21, 23, 24, 25, 26, 27, 28
        );

        // get branch of team
        $branchId = 0;
        if ($db_name === "itccam5_itc" || $db_name === "itccam5_delhi" || $db_name === "itccam5_jaipur") {
            $branchId = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "branch_id",
                "team_id = $teamId"
            );
        }

        $arrResponse = array();

        // Wonder
        if ($db_name === "itccam5_wonder") {
            // get project list
            $arrProjectList = $tableUtil->getOptionsForApp(
                "$db_name.tblprojects",
                "projname",
                "pid",
                "dstatus = 0 AND cid != 27"
            );
            $arrResponse = array(
                array(
                    "key" => "projectList",
                    "dropDownItemList" => $arrProjectList,
                ),
            );
        } elseif ($db_name === "itccam5_zx" && ($projectId == 15 || $projectId == 18 || $projectId == 22)) {
            // Find district of team he is linked to
            $sDistrict = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "district",
                "team_id = $teamId"
            );
            $arrDistrict = $sDistrict ? explode(",", $sDistrict) : array();
            $sDistrictList = $commonFunctions->isNonEmptyArray($arrDistrict) ?
                $commonFunctions->getStringFromArray($arrDistrict) : "";
            $sCond = $sDistrictList ? "AND district IN ($sDistrictList)" : "AND team_id = $teamId";

            $arrShopList = array(
                array(
                    "label" => "Please select",
                    "value" => "",
                    "options" => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                            "options" => array(
                                array(
                                    "label" => "Please select",
                                    "value" => "",
                                    "options" => array(
                                        array(
                                            "label" => "Please select",
                                            "value" => "",
                                            "options" => array(
                                                array(
                                                    "label" => "Please select",
                                                    "value" => "",
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

            $sDropdownCond = isset($arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"]) ?
                $arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"] : "";

            $rsDistrictRes = null;
            $iDistrictNoRows = 0;
            $sDistrictQuery = "SELECT DISTINCT district FROM $db_name.tblroute_details" .
                " WHERE dstatus = 0 AND pid = $projectId $sCond $sDropdownCond ORDER BY district";
            $dbConn->ExecuteSelectQuery($sDistrictQuery, $rsDistrictRes, $iDistrictNoRows);

            if ($iDistrictNoRows > 0) {
                $i = 1;
                while ($rowDistrict = $dbConn->GetData($rsDistrictRes)) {
                    $sDistrict = $rowDistrict["district"];

                    $arrShopList[$i] = array(
                        "label" => htmlentities($sDistrict),
                        "value" => htmlentities($sDistrict),
                        "options" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    );

                    // Subdistrict
                    $rsSubDistrictRes = null;
                    $iSubDistrictNoRows = 0;
                    $sSubDistrictQuery = "SELECT DISTINCT subdistrict FROM $db_name.tblroute_details" .
                        " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict' $sCond" .
                        " $sDropdownCond ORDER BY subdistrict";
                    $dbConn->ExecuteSelectQuery($sSubDistrictQuery, $rsSubDistrictRes, $iSubDistrictNoRows);

                    if ($iSubDistrictNoRows > 0) {
                        $j = 1;
                        while ($rowSubDistrict = $dbConn->GetData($rsSubDistrictRes)) {
                            $sSubDistrict = $rowSubDistrict["subdistrict"];

                            $arrShopList[$i]["options"][$j] = array(
                                "label" => htmlentities($sSubDistrict),
                                "value" => htmlentities($sSubDistrict),
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            );

                            // Distributor
                            $rsDistributorRes = null;
                            $iDistributorNoRows = 0;
                            $sDistributorQuery = "SELECT DISTINCT distributor_name FROM" .
                                " $db_name.tblroute_details WHERE dstatus = 0 AND pid = $projectId" .
                                " AND district = '$sDistrict' AND subdistrict = '$sSubDistrict' $sCond" .
                                " $sDropdownCond ORDER BY distributor_name";
                            $dbConn->ExecuteSelectQuery($sDistributorQuery, $rsDistributorRes, $iDistributorNoRows);

                            if ($iDistributorNoRows > 0) {
                                $k = 1;
                                while ($rowDistributor = $dbConn->GetData($rsDistributorRes)) {
                                    $sDistributor = $rowDistributor["distributor_name"];

                                    $arrShopList[$i]["options"][$j]["options"][$k] = array(
                                        "label" => htmlentities($sDistributor),
                                        "value" => htmlentities($sDistributor),
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    );

                                    // Village
                                    $rsVillageRes = null;
                                    $iVillageNoRows = 0;
                                    $sVillageQuery = "SELECT DISTINCT village FROM $db_name.tblroute_details" .
                                        " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict'" .
                                        " AND subdistrict = '$sSubDistrict' AND" .
                                        " distributor_name = '$sDistributor' $sCond $sDropdownCond ORDER BY village";
                                    $dbConn->ExecuteSelectQuery($sVillageQuery, $rsVillageRes, $iVillageNoRows);

                                    if ($iVillageNoRows) {
                                        $l = 1;
                                        while ($rowVillage = $dbConn->GetData($rsVillageRes)) {
                                            $sVillage = $rowVillage["village"];

                                            $arrShopList[$i]["options"][$j]["options"][$k]["options"][$l] = array(
                                                "label" => htmlentities($sVillage),
                                                "value" => htmlentities($sVillage),
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            );

                                            // outlet name
                                            $rsOutletRes = null;
                                            $iOutletNoRows = 0;
                                            $sOutletQuery = "SELECT rec_id, outlet_name FROM" .
                                                " $db_name.tblroute_details WHERE dstatus = 0 AND pid = $projectId" .
                                                " AND district = '$sDistrict' AND subdistrict = '$sSubDistrict'" .
                                                " AND distributor_name = '$sDistributor' AND village = '$sVillage'" .
                                                " $sCond $sDropdownCond ORDER BY outlet_name";
                                            $dbConn->ExecuteSelectQuery($sOutletQuery, $rsOutletRes, $iOutletNoRows);

                                            if ($iOutletNoRows > 0) {
                                                while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                                                    $iRecId = (int) $rowOutlet["rec_id"];
                                                    $sOutletName = htmlentities($rowOutlet["outlet_name"]);

                                                    $arrShopList[$i]["options"][$j]["options"][$k]["options"][$l]["options"][] =
                                                        array(
                                                            "label" => $sOutletName,
                                                            "value" => $iRecId,
                                                        );
                                                }
                                            }

                                            $l++;
                                        }
                                    }

                                    $k++;
                                }
                            }

                            $j++;
                        }
                    }

                    $i++;
                }
            }

            $arrResponse = array(
                array(
                    "key" => "shopList",
                    "dropDownItemList" => $arrShopList,
                ),
            );
        } elseif (
            $db_name === "itccam5_zx" && ($projectId == 20 || $projectId == 21 ||
                $projectId == 24 || $projectId == 35 || $projectId == 36 || $projectId == 42)
        ) {
            // Find district of team he is linked to
            $sDistrict = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "district",
                "team_id = $teamId"
            );
            $arrDistrict = $sDistrict ? explode(",", $sDistrict) : array();
            $sDistrictList = $commonFunctions->isNonEmptyArray($arrDistrict) ?
                $commonFunctions->getStringFromArray($arrDistrict) : "";
            $sCond = $sDistrictList ? "AND district IN ($sDistrictList)" : "AND team_id = $teamId";

            $arrShopList = array(
                array(
                    "label" => "Please select",
                    "value" => "",
                    "options" => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                            "options" => array(
                                array(
                                    "label" => "Please select",
                                    "value" => "",
                                    "options" => array(
                                        array(
                                            "label" => "Please select",
                                            "value" => "",
                                            "options" => array(
                                                array(
                                                    "label" => "Please select",
                                                    "value" => "",
                                                    "options" => array(
                                                        array(
                                                            "label" => "Please select",
                                                            "value" => "",
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

            $sDropdownCond = isset($arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"]) ?
                $arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"] : "";

            $rsDistributorRes = null;
            $iDistributorNoRows = 0;
            $sDistributorQuery = "SELECT DISTINCT distributor_name FROM $db_name.tblroute_details" .
                " WHERE dstatus = 0 AND pid = $projectId $sCond $sDropdownCond ORDER BY distributor_name";
            $dbConn->ExecuteSelectQuery($sDistributorQuery, $rsDistributorRes, $iDistributorNoRows);

            if ($iDistributorNoRows) {
                $h = 1;
                while ($rowDistributor = $dbConn->GetData($rsDistributorRes)) {
                    $sDistributor = $rowDistributor["distributor_name"];

                    $arrShopList[$h] = array(
                        "label" => htmlentities($sDistributor),
                        "value" => htmlentities($sDistributor),
                        "options" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
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

                    $rsDistrictRes = null;
                    $iDistrictNoRows = 0;
                    $sDistrictQuery = "SELECT DISTINCT district FROM $db_name.tblroute_details WHERE dstatus = 0" .
                        " AND pid = $projectId AND distributor_name = '$sDistributor' $sCond $sDropdownCond" .
                        " ORDER BY district";
                    $dbConn->ExecuteSelectQuery($sDistrictQuery, $rsDistrictRes, $iDistrictNoRows);

                    if ($iDistrictNoRows > 0) {
                        $i = 1;
                        while ($rowDistrict = $dbConn->GetData($rsDistrictRes)) {
                            $sDistrict = $rowDistrict["district"];

                            $arrShopList[$h]["options"][$i] = array(
                                "label" => htmlentities($sDistrict),
                                "value" => htmlentities($sDistrict),
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            );

                            // village
                            $rsVillageRes = null;
                            $iVillageNoRows = 0;
                            $sVillageQuery = "SELECT DISTINCT village FROM $db_name.tblroute_details WHERE" .
                                " dstatus = 0 AND pid = $projectId AND distributor_name = '$sDistributor'" .
                                " AND district = '$sDistrict' $sCond $sDropdownCond ORDER BY village";
                            $dbConn->ExecuteSelectQuery($sVillageQuery, $rsVillageRes, $iVillageNoRows);

                            if ($iVillageNoRows > 0) {
                                $j = 1;
                                while ($rowVillage = $dbConn->GetData($rsVillageRes)) {
                                    $sVillage = $rowVillage["village"];

                                    $arrShopList[$h]["options"][$i]["options"][$j] = array(
                                        "label" => htmlentities($sVillage),
                                        "value" => htmlentities($sVillage),
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    );

                                    // Shop Name
                                    $rsOutletNameRes = null;
                                    $iOutletNameNoRows = 0;
                                    $sOutletNameQuery = "SELECT DISTINCT outlet_name FROM $db_name.tblroute_details" .
                                        " WHERE dstatus = 0 AND pid = $projectId AND" .
                                        " distributor_name = '$sDistributor' AND district = '$sDistrict'" .
                                        " AND village = '$sVillage' $sCond $sDropdownCond ORDER BY outlet_name";
                                    $dbConn->ExecuteSelectQuery(
                                        $sOutletNameQuery,
                                        $rsOutletNameRes,
                                        $iOutletNameNoRows
                                    );

                                    if ($iOutletNameNoRows > 0) {
                                        $k = 1;
                                        while ($rowOutletName = $dbConn->GetData($rsOutletNameRes)) {
                                            $sOutletName = $rowOutletName["outlet_name"];

                                            $arrShopList[$h]["options"][$i]["options"][$j]["options"][$k] = array(
                                                "label" => htmlentities($sOutletName),
                                                "value" => htmlentities($sOutletName),
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            );

                                            // Shop Owner Name
                                            $rsOwnerNameRes = null;
                                            $iOwnerNameNoRows = 0;
                                            $sOwnerNameQuery = "SELECT DISTINCT shop_owner_name FROM" .
                                                " $db_name.tblroute_details WHERE dstatus = 0 AND pid = $projectId" .
                                                " AND distributor_name = '$sDistributor' AND district = '$sDistrict'" .
                                                " AND village = '$sVillage' AND outlet_name= '$sOutletName'" .
                                                " $sCond $sDropdownCond ORDER BY shop_owner_name";
                                            $dbConn->ExecuteSelectQuery(
                                                $sOwnerNameQuery,
                                                $rsOwnerNameRes,
                                                $iOwnerNameNoRows
                                            );

                                            if ($iOwnerNameNoRows) {
                                                $l = 1;
                                                while ($rowOwnerName = $dbConn->GetData($rsOwnerNameRes)) {
                                                    $sOwnerName = $rowOwnerName["shop_owner_name"];

                                                    $arrShopList[$h]["options"][$i]["options"][$j]["options"][$k]["options"][$l] =
                                                        array(
                                                            "label" => htmlentities($sOwnerName),
                                                            "value" => htmlentities($sOwnerName),
                                                            "options" => array(
                                                                array(
                                                                    "label" => "Please select",
                                                                    "value" => "",
                                                                ),
                                                            ),
                                                        );

                                                    // Owner Mobile
                                                    $rsOutletRes = null;
                                                    $iOutletNoRows = 0;
                                                    $sOutletQuery = "SELECT rec_id, shop_owner_phone FROM" .
                                                        " $db_name.tblroute_details WHERE dstatus = 0" .
                                                        " AND pid = $projectId AND distributor_name" .
                                                        " = '$sDistributor' AND district = '$sDistrict'" .
                                                        " AND village = '$sVillage' AND outlet_name" .
                                                        " = '$sOutletName' AND shop_owner_name = '$sOwnerName'" .
                                                        " $sCond $sDropdownCond ORDER BY shop_owner_phone";
                                                    $dbConn->ExecuteSelectQuery(
                                                        $sOutletQuery,
                                                        $rsOutletRes,
                                                        $iOutletNoRows
                                                    );

                                                    if ($iOutletNoRows > 0) {
                                                        while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                                                            $iRecId = (int) $rowOutlet["rec_id"];
                                                            $sPhone = htmlentities($rowOutlet["shop_owner_phone"]);

                                                            $arrShopList[$h]["options"][$i]["options"][$j]["options"][$k]["options"][$l]["options"][] =
                                                                array(
                                                                    "label" => $sPhone,
                                                                    "value" => $iRecId,
                                                                );
                                                        }
                                                    }

                                                    $l++;
                                                }
                                            }

                                            $k++;
                                        }
                                    }

                                    $j++;
                                }
                            }

                            $i++;
                        }
                    }

                    $h++;
                }
            }

            $arrResponse = array(
                array(
                    "key" => "shopList",
                    "dropDownItemList" => $arrShopList,
                ),
            );
        } elseif (
            ($db_name === "itccam5_zx" &&
                ($projectId == 31 || $projectId == 38 || $projectId == 44 ||
                    $projectId == 45 || $projectId == 58 || $projectId == 61 ||
                    $projectId == 65 || $projectId == 66 || $projectId == 69 ||
                    $projectId == 70 || $projectId == 72 || $projectId == 75)) ||
            ($db_name === "itccam5_novicemarcom" &&
                ($projectId == 14 || $projectId == 16 || $projectId == 17 ||
                    $projectId == 19 || $projectId == 20 || $projectId == 24 || $projectId == 25 || $projectId == 26))
        ) {
            // Find district of team he is linked to
            $sDistrict = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "district",
                "team_id = $teamId"
            );
            $arrDistrict = $sDistrict ? explode(",", $sDistrict) : array();
            $sDistrictList = $commonFunctions->isNonEmptyArray($arrDistrict) ?
                $commonFunctions->getStringFromArray($arrDistrict) : "";
            $sCond = $sDistrictList ? "AND district IN ($sDistrictList)" : "AND team_id = $teamId";

            $respKeyName = ($db_name === "itccam5_zx" && $projectId == 38) ? "routeList" : "districtList";
            // $optionsKeyName = ($db_name === "itccam5_zx" && $projectId == 38) ? "outletOptions" : "options";
            $optionsKeyName = "options";

            $arrList = array(
                array(
                    "label" => "Please select",
                    "value" => "",
                    $optionsKeyName => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                            "options" => array(
                                array(
                                    "label" => "Please select",
                                    "value" => "",
                                    "options" => array(
                                        array(
                                            "label" => "Please select",
                                            "value" => "",
                                            "options" => array(
                                                array(
                                                    "label" => "Please select",
                                                    "value" => "",
                                                    "options" => array(
                                                        array(
                                                            "label" => "Please select",
                                                            "value" => "",
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

            $sDropdownCond = isset($arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"]) ?
                $arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"] : "";

            $rsDistrictRes = null;
            $iDistrictNoRows = 0;
            $sDistrictQuery = "SELECT DISTINCT district FROM $db_name.tblroute_details" .
                " WHERE dstatus = 0 AND pid = $projectId $sCond $sDropdownCond ORDER BY district";
            $dbConn->ExecuteSelectQuery($sDistrictQuery, $rsDistrictRes, $iDistrictNoRows);

            if ($iDistrictNoRows > 0) {
                $i = 1;
                while ($rowDistrict = $dbConn->GetData($rsDistrictRes)) {
                    $sDistrict = $rowDistrict["district"];

                    $arrList[$i] = array(
                        "label" => htmlentities($sDistrict),
                        "value" => htmlentities($sDistrict),
                        $optionsKeyName => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
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

                    $columns = "subdistrict";
                    if ($db_name === "itccam5_zx" && $projectId == 38) {
                        $columns .= ", village, shop_owner_phone, lt, lg";
                    }

                    $rsCityRes = null;
                    $iCityNoRows = 0;
                    $sCityQuery = "SELECT $columns FROM $db_name.tblroute_details WHERE dstatus = 0" .
                        " AND pid = $projectId AND district = '$sDistrict' $sCond $sDropdownCond" .
                        " GROUP BY subdistrict ORDER BY subdistrict";
                    $dbConn->ExecuteSelectQuery($sCityQuery, $rsCityRes, $iCityNoRows);

                    if ($iCityNoRows > 0) {
                        $j = 1;
                        while ($rowCity = $dbConn->GetData($rsCityRes)) {
                            $sCity = $rowCity["subdistrict"];

                            $arrList[$i][$optionsKeyName][$j] = array(
                                "label" => htmlentities($sCity),
                                "value" => htmlentities($sCity),
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            );

                            if ($db_name === "itccam5_zx" && $projectId == 38) {
                                $arrList[$i][$optionsKeyName][$j]["otherDetails"] = array(
                                    "htmlText" => "<div><strong>Market Name: </strong>{$rowCity["village"]}</div>",
                                    "contactNo" => isset($rowCity["shop_owner_phone"]) && $rowCity["shop_owner_phone"] ?
                                        $rowCity["shop_owner_phone"] : "",
                                    "showMapIcon" => isset($rowCity["lt"]) && $rowCity["lt"] ? true : false,
                                    "lt" => number_format(floatval($rowCity["lt"]), 8),
                                    "lg" => number_format(floatval($rowCity["lg"]), 8),
                                );
                            } else {
                                $rsLocalityRes = null;
                                $iLocalityNoRows = 0;
                                $sLocalityQuery = "SELECT DISTINCT village FROM $db_name.tblroute_details" .
                                    " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict'" .
                                    " AND subdistrict = '$sCity' $sCond $sDropdownCond ORDER BY village";
                                $dbConn->ExecuteSelectQuery($sLocalityQuery, $rsLocalityRes, $iLocalityNoRows);

                                if ($iLocalityNoRows > 0) {
                                    $k = 1;
                                    while ($rowLocality = $dbConn->GetData($rsLocalityRes)) {
                                        $sLocality = $rowLocality["village"];

                                        $arrList[$i][$optionsKeyName][$j]["options"][$k] = array(
                                            "label" => htmlentities($sLocality),
                                            "value" => htmlentities($sLocality),
                                            "options" => array(
                                                array(
                                                    "label" => "Please select",
                                                    "value" => "",
                                                    "options" => array(
                                                        array(
                                                            "label" => "Please select",
                                                            "value" => "",
                                                            "options" => array(
                                                                array(
                                                                    "label" => "Please select",
                                                                    "value" => "",
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        );

                                        $orderByCond = "ORDER BY outlet_name";
                                        if ($db_name === "itccam5_novicemarcom" && $projectId == 19) {
                                            $orderByCond = "";
                                        } elseif ($db_name === "itccam5_zx" && $projectId == 58) {
                                            $orderByCond = "";
                                        }

                                        $rsRetailerRes = null;
                                        $iRetailerNoRows = 0;
                                        $sRetailerQuery = "SELECT DISTINCT outlet_name FROM" .
                                            " $db_name.tblroute_details WHERE dstatus = 0 AND pid = $projectId" .
                                            " AND district = '$sDistrict' AND subdistrict = '$sCity'" .
                                            " AND village = '$sLocality' $sCond $sDropdownCond $orderByCond";
                                        $dbConn->ExecuteSelectQuery($sRetailerQuery, $rsRetailerRes, $iRetailerNoRows);

                                        if ($iRetailerNoRows > 0) {
                                            $l = 1;
                                            while ($rowRetailer = $dbConn->GetData($rsRetailerRes)) {
                                                $sRetailer = $rowRetailer["outlet_name"];

                                                $arrList[$i][$optionsKeyName][$j]["options"][$k]["options"][$l] = array(
                                                    "label" => htmlentities($sRetailer),
                                                    "value" => htmlentities($sRetailer),
                                                    "options" => array(
                                                        array(
                                                            "label" => "Please select",
                                                            "value" => "",
                                                            "options" => array(
                                                                array(
                                                                    "label" => "Please select",
                                                                    "value" => "",
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                );

                                                $columns = $db_name === "itccam5_novicemarcom" && $projectId == 14 ?
                                                    "rec_id, address" : "address";

                                                $rsAddressRes = null;
                                                $iAddressNoRows = 0;
                                                $sAddressQuery = "SELECT DISTINCT $columns FROM" .
                                                    " $db_name.tblroute_details WHERE dstatus = 0" .
                                                    " AND pid = $projectId AND district = '$sDistrict'" .
                                                    " AND subdistrict = '$sCity' AND village = '$sLocality'" .
                                                    " AND outlet_name = ?" .
                                                    " $sCond $sDropdownCond ORDER BY address";
                                                $dbConn->ExecuteSelectQuery(
                                                    $sAddressQuery,
                                                    $rsAddressRes,
                                                    $iAddressNoRows,
                                                    array($sRetailer)
                                                );

                                                if ($iAddressNoRows > 0) {
                                                    $m = 1;
                                                    while ($rowAddress = $dbConn->GetData($rsAddressRes)) {
                                                        $sAddress = $rowAddress["address"];
                                                        $iRecId = isset($rowAddress["rec_id"]) ?
                                                            (int) $rowAddress["rec_id"] : "";

                                                        $arrList[$i][$optionsKeyName][$j]["options"][$k]["options"][$l]["options"][$m] =
                                                            array(
                                                                "label" => htmlentities($sAddress),
                                                                "value" => $db_name === "itccam5_novicemarcom" &&
                                                                    $projectId == 14 ? $iRecId :
                                                                    htmlentities($sAddress),
                                                                "options" => array(
                                                                    array(
                                                                        "label" => "Please select",
                                                                        "value" => "",
                                                                    ),
                                                                ),
                                                            );

                                                        if (
                                                            !($db_name === "itccam5_novicemarcom" && $projectId == 14)
                                                        ) {
                                                            $rsGiftRes = null;
                                                            $iGiftNoRows = 0;
                                                            $sGiftQuery = "SELECT rec_id, shop_owner_name" .
                                                                " FROM $db_name.tblroute_details WHERE dstatus = 0" .
                                                                " AND pid = $projectId AND district = '$sDistrict'" .
                                                                " AND subdistrict = '$sCity'" .
                                                                " AND village = '$sLocality'" .
                                                                " AND outlet_name = '$sRetailer'" .
                                                                " AND address = '$sAddress'" .
                                                                " $sCond $sDropdownCond ORDER BY shop_owner_name";
                                                            $dbConn->ExecuteSelectQuery(
                                                                $sGiftQuery,
                                                                $rsGiftRes,
                                                                $iGiftNoRows
                                                            );

                                                            if ($iGiftNoRows > 0) {
                                                                while ($rowGift = $dbConn->GetData($rsGiftRes)) {
                                                                    $iRecId = (int) $rowGift["rec_id"];
                                                                    $sGift = htmlentities($rowGift["shop_owner_name"]);

                                                                    $arrList[$i][$optionsKeyName][$j]["options"][$k]["options"][$l]["options"][$m]["options"][] =
                                                                        array(
                                                                            "label" => $sGift,
                                                                            "value" => $iRecId,
                                                                        );
                                                                }
                                                            }
                                                        }

                                                        $m++;
                                                    }
                                                }

                                                $l++;
                                            }
                                        }

                                        $k++;
                                    }
                                }
                            }

                            $j++;
                        }
                    }

                    $i++;
                }
            }

            $arrResponse = array(
                array(
                    "key" => $respKeyName,
                    "dropDownItemList" => $arrList,
                ),
            );
        } elseif (
            $db_name === "itccam5_impact" || $db_name === "itccam5_zx" ||
            ($db_name === "itccam5_itcnew" && ($projectId == 15 || $projectId == 28 ||
                $projectId == 38 || $projectId == 71))
        ) {
            // Find district of team he is linked to
            $sDistrict = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "district",
                "team_id = $teamId"
            );
            $arrDistrict = $sDistrict ? explode(",", $sDistrict) : array();
            $sDistrictList = $commonFunctions->isNonEmptyArray($arrDistrict) ?
                $commonFunctions->getStringFromArray($arrDistrict) : "";
            $sCond = $sDistrictList ? "AND district IN ($sDistrictList)" : "AND team_id = $teamId";

            $arrDistrictList = array(
                array(
                    "label" => "Please select",
                    "value" => "",
                    "options" => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                            "options" => array(
                                array(
                                    "label" => "Please select",
                                    "value" => "",
                                    "options" => array(
                                        array(
                                            "label" => "Please select",
                                            "value" => "",
                                            "options" => array(
                                                array(
                                                    "label" => "Please select",
                                                    "value" => "",
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

            $sDropdownCond = isset($arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"]) ?
                $arrDBProjectDetails[$db_name][$clientId][$projectId]["dropdownCond"] : "";

            // District
            $rsDistrictRes = null;
            $iDistrictNoRows = 0;
            $sDistrictQuery = "SELECT DISTINCT district FROM $db_name.tblroute_details WHERE dstatus = 0" .
                " AND pid = $projectId $sCond $sDropdownCond ORDER BY district";
            $dbConn->ExecuteSelectQuery($sDistrictQuery, $rsDistrictRes, $iDistrictNoRows);

            if ($iDistrictNoRows > 0) {
                $i = 1;
                while ($rowDistrict = $dbConn->GetData($rsDistrictRes)) {
                    $sDistrict = $rowDistrict["district"];

                    $arrDistrictList[$i] = array(
                        "label" => $sDistrict,
                        "value" => $sDistrict,
                        "options" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    );

                    // Subdistrict
                    $rsSubDistrictRes = null;
                    $iSubDistrictNoRows = 0;
                    $sSubDistrictQuery = "SELECT DISTINCT subdistrict FROM $db_name.tblroute_details" .
                        " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict'" .
                        " $sCond $sDropdownCond ORDER BY subdistrict";
                    $dbConn->ExecuteSelectQuery($sSubDistrictQuery, $rsSubDistrictRes, $iSubDistrictNoRows);

                    if ($iSubDistrictNoRows > 0) {
                        $j = 1;
                        while ($rowSubDistrict = $dbConn->GetData($rsSubDistrictRes)) {
                            $sSubDistrict = $rowSubDistrict["subdistrict"];

                            $arrDistrictList[$i]["options"][$j] = array(
                                "label" => htmlentities($sSubDistrict),
                                "value" => htmlentities($sSubDistrict),
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            );

                            $columns = $db_name === "itccam5_impact" &&
                                ($projectId == 32 || $projectId == 44 || $projectId == 70 || $projectId == 76) ?
                                "village" : "rec_id, village";

                            // Village
                            $rsVillageRes = null;
                            $iVillageNoRows = 0;
                            $sVillageQuery = "SELECT DISTINCT $columns FROM $db_name.tblroute_details" .
                                " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict'" .
                                " AND subdistrict = '$sSubDistrict' $sCond $sDropdownCond ORDER BY village";
                            $dbConn->ExecuteSelectQuery($sVillageQuery, $rsVillageRes, $iVillageNoRows);

                            if ($iVillageNoRows > 0) {
                                $k = 1;
                                while ($rowVillage = $dbConn->GetData($rsVillageRes)) {
                                    $iRecId = isset($rowVillage["rec_id"]) ? (int) $rowVillage["rec_id"] : "";
                                    $sVillage = $rowVillage["village"];

                                    $arrDistrictList[$i]["options"][$j]["options"][$k] = array(
                                        "label" => htmlentities($sVillage),
                                        "value" => $db_name === "itccam5_impact" && ($projectId == 32 ||
                                            $projectId == 44 || $projectId == 70 || $projectId == 76) ?
                                            htmlentities($sVillage) : $iRecId,
                                        "options" => array(
                                            array(
                                                "label" => "Please select",
                                                "value" => "",
                                                "options" => array(
                                                    array(
                                                        "label" => "Please select",
                                                        "value" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    );

                                    if (
                                        $db_name === "itccam5_impact" &&
                                        ($projectId == 32 || $projectId == 44 || $projectId == 50 ||
                                            $projectId == 18 || $projectId == 70 || $projectId == 76 ||
                                            $projectId == 77)
                                    ) {
                                        // Site
                                        $rsSiteRes = null;
                                        $iSiteNoRows = 0;
                                        $sSiteQuery = "SELECT DISTINCT site FROM $db_name.tblroute_details" .
                                            " WHERE dstatus = 0 AND pid = $projectId AND district = '$sDistrict'" .
                                            " AND subdistrict = '$sSubDistrict' AND village = '$sVillage'" .
                                            " $sCond $sDropdownCond ORDER BY site";
                                        $dbConn->ExecuteSelectQuery($sSiteQuery, $rsSiteRes, $iSiteNoRows);

                                        if ($iSiteNoRows > 0) {
                                            $l = 1;
                                            while ($rowSite = $dbConn->GetData($rsSiteRes)) {
                                                $sSite = $rowSite["site"];

                                                $arrDistrictList[$i]["options"][$j]["options"][$k]["options"][$l] =
                                                    array(
                                                        "label" => htmlentities($sSite),
                                                        "value" => htmlentities($sSite),
                                                        "options" => array(
                                                            array(
                                                                "label" => "Please select",
                                                                "value" => "",
                                                            ),
                                                        ),
                                                    );

                                                // Address
                                                $rsAddressRes = null;
                                                $iAddressNoRows = 0;
                                                $sAddressQuery = "SELECT rec_id, address FROM" .
                                                    " $db_name.tblroute_details WHERE dstatus = 0" .
                                                    " AND pid = $projectId AND district = '$sDistrict'" .
                                                    " AND subdistrict = '$sSubDistrict' AND village = '$sVillage'" .
                                                    " AND site = '$sSite' $sCond $sDropdownCond ORDER BY address";
                                                $dbConn->ExecuteSelectQuery(
                                                    $sAddressQuery,
                                                    $rsAddressRes,
                                                    $iAddressNoRows
                                                );

                                                if ($iAddressNoRows > 0) {
                                                    while ($rowAddress = $dbConn->GetData($rsAddressRes)) {
                                                        $iAddressRecId = (int) $rowAddress["rec_id"];
                                                        $sAddress = $rowAddress["address"];

                                                        $arrDistrictList[$i]["options"][$j]["options"][$k]["options"][$l]["options"][] =
                                                            array(
                                                                "label" => htmlentities($sAddress),
                                                                "value" => $iAddressRecId,
                                                            );
                                                    }
                                                }

                                                $l++;
                                            }
                                        }
                                    } elseif ($db_name === "itccam5_zx" && $projectId == 46) {
                                        // issues
                                        $arrDistrictList[$i]["options"][$j]["options"][$k]["checkboxOptions"] = array();

                                        $rsIssueRes = null;
                                        $iIssueNoRows = 0;
                                        $sIssueQuery = "SELECT issue_id, capture_date, issue_desc FROM" .
                                            " $db_name.tblsnpl_issues WHERE dstatus = 0 AND team_id = $teamId" .
                                            " AND shop_id = $iRecId AND is_issue_resolved = 0";
                                        $dbConn->ExecuteSelectQuery($sIssueQuery, $rsIssueRes, $iIssueNoRows);

                                        $l = 0;
                                        if ($iIssueNoRows > 0) {
                                            while ($rowIssue = $dbConn->GetData($rsIssueRes)) {
                                                $issueId = $rowIssue["issue_id"];
                                                $captureDate = $rowIssue["capture_date"];
                                                $issueDesc = $rowIssue["issue_desc"];

                                                $arrDistrictList[$i]["options"][$j]["options"][$k]["checkboxOptions"][$l] =
                                                    array(
                                                        "label" => htmlentities($issueDesc . " on " .
                                                            $commonFunctions->currentDate("d-m-Y", $captureDate)),
                                                        "value" => (int) htmlentities($issueId),
                                                    );

                                                $l++;
                                            }
                                            $arrDistrictList[$i]["options"][$j]["options"][$k]["checkboxOptions"][] =
                                                array(
                                                    "label" => "No issues resolved",
                                                    "value" => 0,
                                                );
                                        } else {
                                            $arrDistrictList[$i]["options"][$j]["options"][$k]["checkboxOptions"][$l] =
                                                array(
                                                    "label" => "No issues reported",
                                                    "value" => 0,
                                                );
                                        }
                                    }

                                    $k++;
                                }
                            }

                            $j++;
                        }
                    }

                    $i++;
                }
            }

            $arrResponse = array(
                array(
                    "key" => "districtList",
                    "dropDownItemList" => $arrDistrictList,
                ),
            );
        } elseif ($db_name === "itccam5_itcnew") {
            // ITC NEW

            $iCity = $tableUtil->getRowColumn(
                "$db_name.$TBL_PROJECT_TEAM",
                "city_id",
                "team_id = $teamId"
            );

            // get store list
            $arrStoreList = $tableUtil->getOptionsForApp(
                "$db_name.tbloutlet_master AS a, $db_name.tbloutlet_project AS b",
                "CONCAT(a.storeName,' - ',a.storeLocation) AS label",
                "rec_id",
                "b.dstatus = 0 AND a.outlet_id = b.outlet_id AND b.project_id = $projectId" .
                    " AND (team_id = $teamId OR (city_id = $iCity AND team_id = 0))"
            );

            $arrResponse = array(
                array(
                    "key" => "storeList",
                    "dropDownItemList" => $arrStoreList,
                ),
            );
        } elseif ($db_name === "itccam5_snpl") {
            // get route list
            $arrRouteList = array(
                array(
                    "label" => "Please select", "value" => "",
                    "outletOptions" => array(
                        array("label" => "Please select", "value" => "")
                    ),
                    "marketOptions" => array(
                        array("label" => "Please select", "value" => "")
                    ),
                ),
            );

            // Route
            $rsRouteRes = null;
            $iRouteNoRows = 0;
            $sRouteQuery = "SELECT DISTINCT route_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                " AND team_id = $teamId AND outlet_type = 'ROC' ORDER BY route_name";
            $dbConn->ExecuteSelectQuery($sRouteQuery, $rsRouteRes, $iRouteNoRows);

            if ($iRouteNoRows > 0) {
                $i = 1;
                while ($rowRoute = $dbConn->GetData($rsRouteRes)) {
                    $sRoute = $rowRoute["route_name"];

                    $arrRouteList[$i] = array(
                        "label" => htmlentities($sRoute),
                        "value" => htmlentities($sRoute),
                        "outletOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                            ),
                        ),
                        "marketOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                            ),
                        ),
                    );

                    // outlet
                    $rsOutletRes = null;
                    $iOutletNoRows = 0;
                    $sOutletQuery = "SELECT rec_id, market_name, outlet_name, outlet_mobile, lt, lg" .
                        " FROM $db_name.tblroute_details WHERE dstatus = 0 AND team_id = $teamId" .
                        " AND route_name = '$sRoute' AND outlet_type = 'ROC' ORDER BY outlet_name";
                    $dbConn->ExecuteSelectQuery($sOutletQuery, $rsOutletRes, $iOutletNoRows);

                    if ($iOutletNoRows > 0) {
                        while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                            $sOutletId = $rowOutlet["rec_id"];
                            $sOutletName = $rowOutlet["outlet_name"];

                            $arrRouteList[$i]["outletOptions"][] = array(
                                "label" => htmlentities($sOutletName),
                                "value" => $sOutletId,
                                "otherDetails" => array(
                                    "htmlText" => $rowOutlet["market_name"],
                                    "contactNo" => isset($rowOutlet["outlet_mobile"]) && $rowOutlet["outlet_mobile"] ?
                                        $rowOutlet["outlet_mobile"] : "",
                                    "showMapIcon" => true,
                                    "lt" => floatval($rowOutlet["lt"]),
                                    "lg" => floatval($rowOutlet["lg"]),
                                ),
                            );
                        }
                    }

                    // market
                    $rsMarketRes = null;
                    $iMarketNoRows = 0;
                    $sMarketQuery = "SELECT DISTINCT market_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                        " AND team_id = $teamId AND route_name = '$sRoute' AND outlet_type = 'ROC'" .
                        " ORDER BY market_name";
                    $dbConn->ExecuteSelectQuery($sMarketQuery, $rsMarketRes, $iMarketNoRows);

                    if ($iMarketNoRows > 0) {
                        while ($rowMarket = $dbConn->GetData($rsMarketRes)) {
                            $sMarketName = $rowMarket["market_name"];

                            $arrRouteList[$i]["marketOptions"][] = array(
                                "label" => htmlentities($sMarketName),
                                "value" => htmlentities($sMarketName),
                            );
                        }
                    }

                    $i++;
                }
            }
            // get outlet list
            $arrOutletList = $tableUtil->getOptionsForApp(
                "$db_name.tblroute_details",
                "outlet_name",
                "rec_id",
                "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'",
                array(),
                true,
                true,
                false
            );
            // get market list
            $arrMarketList = $tableUtil->getOptionsForApp(
                "$db_name.tblroute_details",
                "market_name",
                "",
                "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'",
                array(),
                true,
                true,
                false
            );

            $arrResponse = array(
                array(
                    "key" => "routeList",
                    "dropDownItemList" => $arrRouteList,
                ),
                array(
                    "key" => "outletList",
                    "dropDownItemList" => $arrOutletList,
                ),
                array(
                    "key" => "marketList",
                    "dropDownItemList" => $arrMarketList,
                ),
            );
        } elseif (
            ($db_name === "itccam5_itc" && in_array($branchId, $arrBranchListWithNewApp)) ||
            $db_name === "itccam5_delhi" || $db_name === "itccam5_jaipur" || $db_name === "itccam5_south"
        ) {
            // get route list
            $arrRouteList = array(
                array(
                    "label" => "Please select", "value" => "",
                    "outletOptions" => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                        )
                    ),
                    "marketOptions" => array(
                        array(
                            "label" => "Please select",
                            "value" => "",
                            "options" => array(
                                array(
                                    "label" => "Please select",
                                    "value" => ""
                                ),
                            ),
                        )
                    ),
                ),
            );

            $rocOutletCond = "AND outlet_type = 'ROC'";
            $otherOutletCond = "AND outlet_type = 'Other'";
            if ($db_name === "itccam5_south") {
                $rocOutletCond = "";
                $otherOutletCond = "";
                // no need of outletOptions
                unset($arrRouteList[0]["outletOptions"]);
            }

            // Route
            $rsRouteRes = null;
            $iRouteNoRows = 0;
            $sRouteQuery = "SELECT DISTINCT route_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                " AND team_id = $teamId $rocOutletCond ORDER BY route_name";
            $dbConn->ExecuteSelectQuery($sRouteQuery, $rsRouteRes, $iRouteNoRows);

            if ($iRouteNoRows > 0) {
                $i = 1;
                while ($rowRoute = $dbConn->GetData($rsRouteRes)) {
                    $sRoute = $rowRoute["route_name"];

                    $arrRouteList[$i] = array(
                        "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sRoute)),
                        "value" => htmlentities($commonFunctions->removeSpecialCharFromString($sRoute)),
                        "outletOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                            ),
                        ),
                        "marketOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => ""
                                    ),
                                    array(
                                        "label" => "Other",
                                        "value" => "Other"
                                    ),
                                ),
                            ),
                        ),
                    );

                    if ($db_name === "itccam5_south") {
                        unset($arrRouteList[$i]["outletOptions"]);
                        array_splice($arrRouteList[$i]["marketOptions"][0]["options"], 1, 1);
                    } else {
                        // outlet
                        $rsOutletRes = null;
                        $iOutletNoRows = 0;
                        $sOutletQuery = "SELECT rec_id, market_name, outlet_name, outlet_mobile, lt, lg FROM" .
                            " $db_name.tblroute_details WHERE dstatus = 0 AND team_id = $teamId" .
                            " AND route_name = '$sRoute' $rocOutletCond ORDER BY outlet_name";
                        $dbConn->ExecuteSelectQuery($sOutletQuery, $rsOutletRes, $iOutletNoRows);

                        if ($iOutletNoRows > 0) {
                            while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                                $sOutletId = $rowOutlet["rec_id"];
                                $sOutletName = $rowOutlet["outlet_name"];

                                $arrRouteList[$i]["outletOptions"][] = array(
                                    "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sOutletName)),
                                    "value" => $sOutletId,
                                    "otherDetails" => array(
                                        "htmlText" => "<div><strong>Market Name: </strong>" .
                                            "{$rowOutlet["market_name"]}</div>",
                                        "contactNo" => isset($rowOutlet["outlet_mobile"]) &&
                                            $rowOutlet["outlet_mobile"] ? $rowOutlet["outlet_mobile"] : "",
                                        "showMapIcon" => true,
                                        "lt" => floatval($rowOutlet["lt"]),
                                        "lg" => floatval($rowOutlet["lg"]),
                                    ),
                                );
                            }
                        }
                    }

                    // market
                    $rsMarketRes = null;
                    $iMarketNoRows = 0;
                    $sMarketQuery = "SELECT DISTINCT market_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                        " AND team_id = $teamId AND route_name = '$sRoute' $rocOutletCond" .
                        " ORDER BY market_name";
                    $dbConn->ExecuteSelectQuery($sMarketQuery, $rsMarketRes, $iMarketNoRows);

                    if ($iMarketNoRows > 0) {
                        $j = 1;
                        while ($rowMarket = $dbConn->GetData($rsMarketRes)) {
                            $sMarketName = $rowMarket["market_name"];

                            $arrRouteList[$i]["marketOptions"][$j] = array(
                                "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sMarketName)),
                                "value" => htmlentities($commonFunctions->removeSpecialCharFromString($sMarketName)),
                                "options" => array(
                                    array(
                                        "label" => "Please select",
                                        "value" => "",
                                    ),
                                    array(
                                        "label" => "Other",
                                        "value" => "Other"
                                    ),
                                ),
                            );

                            // Remove "Other" option
                            if ($db_name === "itccam5_south") {
                                array_splice($arrRouteList[$i]["marketOptions"][$j]["options"], 1, 1);
                            }

                            // other outlet
                            $rsOutletRes = null;
                            $iOutletNoRows = 0;
                            $sOutletQuery = "SELECT rec_id, outlet_name, outlet_mobile, lt, lg FROM" .
                                " $db_name.tblroute_details WHERE dstatus = 0 AND team_id = $teamId" .
                                " AND route_name = '$sRoute' AND market_name = '$sMarketName'" .
                                " $otherOutletCond ORDER BY outlet_name";
                            $dbConn->ExecuteSelectQuery($sOutletQuery, $rsOutletRes, $iOutletNoRows);

                            if ($iOutletNoRows > 0) {
                                while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                                    $sOutletId = $rowOutlet["rec_id"];
                                    $sOutletName = $rowOutlet["outlet_name"];

                                    $arrRouteList[$i]["marketOptions"][$j]["options"][] = array(
                                        "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sOutletName)),
                                        "value" => $sOutletId,
                                        "otherDetails" => array(
                                            "contactNo" => isset($rowOutlet["outlet_mobile"]) &&
                                                $rowOutlet["outlet_mobile"] ? $rowOutlet["outlet_mobile"] : "",
                                            "showMapIcon" => true,
                                            "lt" => floatval($rowOutlet["lt"]),
                                            "lg" => floatval($rowOutlet["lg"]),
                                        ),
                                    );
                                }
                            }
                            $j++;
                        }
                    }

                    $i++;
                }
            }

            $arrResponse = array(
                array(
                    "key" => "routeList",
                    "dropDownItemList" => $arrRouteList,
                ),
            );

            // send pickup avg stock for each product
            if ($db_name === "itccam5_delhi") {
                $branchId = $branchId ? $branchId : 1;

                // get product list
                $rsProductRes = null;
                $iProductNoRows = 0;
                $sProductQuery = "SELECT DISTINCT product_name, summary_column_name" .
                    " FROM $db_name.tblbranch_pickupstock_products WHERE dstatus = 0 AND branch_id = $branchId";
                $dbConn->ExecuteSelectQuery($sProductQuery, $rsProductRes, $iProductNoRows);

                $arrProductwiseSale = array();
                $arrProductwiseSummaryColumn = array();
                $arrProductwiseAvgSummaryColumn = array();
                if ($iProductNoRows > 0) {
                    while ($rowProduct = $dbConn->GetData($rsProductRes)) {
                        $arrProductwiseSale[$rowProduct["product_name"]] = 0;
                        $arrProductwiseSummaryColumn[$rowProduct["product_name"]] = $rowProduct["summary_column_name"];
                        $arrProductwiseAvgSummaryColumn[] = "AVG(" . $rowProduct["summary_column_name"] . ") AS " .
                            $rowProduct["summary_column_name"];
                    }
                }

                // get avg stock for each product for last 4 weeks
                $currentDate = $commonFunctions->currentDate();
                $past4WeekDate = date("Y-m-d", strtotime("-4 week"));
                $sProductwiseAvgSummaryColumn = implode(",", $arrProductwiseAvgSummaryColumn);
                $rsStockRes = null;
                $iStockNoRows = 0;
                $sStockQuery = "SELECT $sProductwiseAvgSummaryColumn FROM $db_name.tblstock_summary" .
                    " WHERE dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN" .
                    " '$past4WeekDate' AND '$currentDate' AND stock_type = 0";
                $dbConn->ExecuteSelectQuery($sStockQuery, $rsStockRes, $iStockNoRows);

                if ($iStockNoRows > 0) {
                    $rowStock = $dbConn->GetData($rsStockRes);
                    foreach ($arrProductwiseSummaryColumn as $productName => $productSummaryColumn) {
                        $iAvgStock = isset($rowStock[$productSummaryColumn]) ?
                            round($rowStock[$productSummaryColumn]) : 0;
                        $arrProductwiseSale[$productName] = $iAvgStock;
                    }
                }

                $arrResponse[] = array(
                    "key" => "salesList",
                    "productwiseSales" => $arrProductwiseSale,
                );
            }
        } elseif ($db_name === "itccam5_itc" && !in_array($branchId, $arrBranchListWithNewApp)) {
            // get route list
            $arrRouteList = array(
                array(
                    "label" => "Please select", "value" => "",
                    "outletOptions" => array(
                        array("label" => "Please select", "value" => "")
                    ),
                    "marketOptions" => array(
                        array("label" => "Please select", "value" => "")
                    ),
                ),
            );

            // Route
            $rsRouteRes = null;
            $iRouteNoRows = 0;
            $sRouteQuery = "SELECT DISTINCT route_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                " AND team_id = $teamId AND outlet_type = 'ROC' ORDER BY route_name";
            $dbConn->ExecuteSelectQuery($sRouteQuery, $rsRouteRes, $iRouteNoRows);

            if ($iRouteNoRows > 0) {
                $i = 1;
                while ($rowRoute = $dbConn->GetData($rsRouteRes)) {
                    $sRoute = $rowRoute["route_name"];

                    $arrRouteList[$i] = array(
                        "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sRoute)),
                        "value" => htmlentities($commonFunctions->removeSpecialCharFromString($sRoute)),
                        "outletOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                            ),
                        ),
                        "marketOptions" => array(
                            array(
                                "label" => "Please select",
                                "value" => "",
                            ),
                        ),
                    );

                    // outlet
                    $rsOutletRes = null;
                    $iOutletNoRows = 0;
                    $sOutletQuery = "SELECT rec_id, market_name, outlet_name, outlet_mobile, lt, lg FROM" .
                        " $db_name.tblroute_details WHERE dstatus = 0 AND team_id = $teamId" .
                        " AND route_name = '$sRoute' AND outlet_type = 'ROC' ORDER BY outlet_name";
                    $dbConn->ExecuteSelectQuery($sOutletQuery, $rsOutletRes, $iOutletNoRows);

                    if ($iOutletNoRows > 0) {
                        while ($rowOutlet = $dbConn->GetData($rsOutletRes)) {
                            $sOutletId = $rowOutlet["rec_id"];
                            $sOutletName = $rowOutlet["outlet_name"];

                            $arrRouteList[$i]["outletOptions"][] = array(
                                "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sOutletName)),
                                "value" => $sOutletId,
                                "otherDetails" => array(
                                    "htmlText" => $rowOutlet["market_name"],
                                    "contactNo" => isset($rowOutlet["outlet_mobile"]) && $rowOutlet["outlet_mobile"] ?
                                        $rowOutlet["outlet_mobile"] : "",
                                    "showMapIcon" => true,
                                    "lt" => floatval($rowOutlet["lt"]),
                                    "lg" => floatval($rowOutlet["lg"]),
                                ),
                            );
                        }
                    }

                    // market
                    $rsMarketRes = null;
                    $iMarketNoRows = 0;
                    $sMarketQuery = "SELECT DISTINCT market_name FROM $db_name.tblroute_details WHERE dstatus = 0" .
                        " AND team_id = $teamId AND route_name = '$sRoute' AND outlet_type = 'ROC'" .
                        " ORDER BY market_name";
                    $dbConn->ExecuteSelectQuery($sMarketQuery, $rsMarketRes, $iMarketNoRows);

                    if ($iMarketNoRows > 0) {
                        while ($rowMarket = $dbConn->GetData($rsMarketRes)) {
                            $sMarketName = $rowMarket["market_name"];

                            $arrRouteList[$i]["marketOptions"][] = array(
                                "label" => htmlentities($commonFunctions->removeSpecialCharFromString($sMarketName)),
                                "value" => htmlentities($commonFunctions->removeSpecialCharFromString($sMarketName)),
                            );
                        }
                    }

                    $i++;
                }
            }
            // get outlet list
            $arrOutletList = $tableUtil->getOptionsForApp(
                "$db_name.tblroute_details",
                "outlet_name",
                "rec_id",
                "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'"
            );
            // get market list
            $arrMarketList = $tableUtil->getOptionsForApp(
                "$db_name.tblroute_details",
                "market_name",
                "",
                "dstatus = 0 AND team_id = $teamId AND outlet_type = 'ROC'"
            );

            $arrResponse = array(
                array(
                    "key" => "routeList",
                    "dropDownItemList" => $arrRouteList,
                ),
                array(
                    "key" => "outletList",
                    "dropDownItemList" => $arrOutletList,
                ),
                array(
                    "key" => "marketList",
                    "dropDownItemList" => $arrMarketList,
                ),
            );
        }

        $response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
    } else {
        // Unauthorized phone
        $commonFunctions->debugLog(
            $arrAuthMessages["AUTH06"],
            $logFileName,
            $logFolderName
        );
        $commonFunctions->debugLog(
            "SERVER LOG DATE TIME: $currentDateTime Token: $sToken\r\n$sQuery_Org\r\n" . $arrAuthMessages["AUTH06"],
            $unauthorisedAccessLogFileName,
            $logFolderName
        );
        $response->sendResponse(array("message" => $arrAuthMessages["AUTH06"]));
    }
}

$dbConn->Close();
