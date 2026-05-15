<?php

// generate order by condition
function getOrderByCond($default = "id", $orderBy = "", $order = "")
{
    if ($orderBy) {
        return "ORDER BY $orderBy $order";
    }

    if ($default) {
        return "ORDER BY $default DESC";
    }
}

//get pagination limit for query
function getPaginationLimit($dbConn, $data, $query, $arrParams = [])
{
    $sAction = null;
    $total = 0;
    $dbConn->ExecuteSelectQuery($query, $sAction, $total, $arrParams);

    $limit = getFormData($data, "limit");
    $page = getFormData($data, "page");

    if (!$limit) {
        $limit = 10;
    }

    $last = ceil($total / $limit);

    if ($last < 1) {
        $last = 1;
    }

    if ($page < 1) {
        $page = 1;
    } elseif ($page > $last) {
        $page = $last;
    }

    $limit = "LIMIT " . ($page - 1) * $limit . ',' . $limit;
    return ["limit" => $limit, "total" => $total];
}

// get filter query string
function getFilterResult($data, $filters, $dbConn = null)
{
    $strFilter = "";

    if (isNonEmptyArray($filters)) {
        $strFilter = "AND ";
        foreach ($filters as $key => $filter) {
            if (is_array($filter) && (($filter[1] !== 4 && isset($data[$key]) && !matchValue($data[$key], "")) || $filter[1] === 4)) {
                if ($filter[1] === 0) {
                    if (isset($filter[2]) && $filter[2]) {
                        $str = "";
                        // if dropdown and select All, don't include search
                        if (!isset($filter[3]) || !$filter[3] || (isset($filter[3]) && $filter[3] && isNonEmptyArray($data[$key]) && $data[$key][0] !== $GLOBALS['APP_CONSTANTS']["ALL_VALUE"])) {
                            $str = getStringFromArray($data[$key]);
                        }
                    } else {
                        $str = $data[$key];
                    }

                    if ($str) {
                        $strFilter .= "{$filter[0]} IN ({$str}) AND ";
                    }
                } elseif ($filter[1] === 1) {
                    $strFilter .= "{$filter[0]} LIKE '%{$data[$key]}%' AND ";
                } elseif ($filter[1] === 2) {
                    $dateFrom = currentDate(getValidDate($data[$key]));
                    $dateTo = currentDate(getValidDate($data[$filter[2]]));
                    $strFilter .= "{$filter[0]} BETWEEN '$dateFrom' AND '$dateTo' AND ";
                } elseif ($filter[1] === 3) {
                    // multiple values
                    if ($filter[6]) {
                        $str = getStringFromArray($data[$key]);
                    } else {
                        $str = "'" . $data[$key] . "'";
                    }

                    if ($str) {
                        // Don't use dstatus = 0
                        $arrRecs = getRowsColumn($dbConn, $filter[2], $filter[3], "{$filter[4]} IN ({$str}) $filter[5]");
                        $sRecs = getStringFromArray($arrRecs);
                        $strFilter .= "{$filter[0]} IN ({$sRecs}) AND ";
                    }
                } elseif ($filter[1] === 4) {
                    if ($filter[3]) {
                        $dateFrom = getValidDate($data[$key]);
                        $dateTo = getValidDate($data[$filter[2]]);
                    } else {
                        $dateFrom = $data[$key];
                        $dateTo = $data[$filter[2]];
                    }
                    if ($dateFrom && $dateTo) {
                        $dateFrom = currentDate($dateFrom);
                        $dateTo = currentDate($dateTo);
                        $strFilter .= "{$filter[0]} BETWEEN '$dateFrom' AND '$dateTo' AND ";
                    } elseif ($dateFrom && !$dateTo) {
                        $dateFrom = currentDate($dateFrom);
                        $strFilter .= "{$filter[0]} >= '$dateFrom' AND ";
                    } elseif (!$dateFrom && $dateTo) {
                        $dateTo = currentDate($dateTo);
                        $strFilter .= "{$filter[0]} <= '$dateTo' AND ";
                    }
                } else {
                    // if dropdown and select All, don't include search
                    if ($data[$key] !== $GLOBALS['APP_CONSTANTS']["ALL_VALUE"]) {
                        $strFilter .= "{$filter[0]} = '{$data[$key]}' AND ";
                    }
                }
            }
        }

        $andPos = strrpos($strFilter, " AND");
        if ($andPos >= 0) {
            $strFilter = substr($strFilter, 0, $andPos);
        }
    }
    return $strFilter;
}

// get listing images
function getListingImages($dbConn, $uniId, $sCond = "", $arrLabels = [], $useIndexAsLabelKey = false, $staticLabel = "", $labelKey = "", $imgTable = "", $useFileDomain = true)
{
    global $CUST_FOLDER_PATH;
    $arrImages = [];
    if (!$imgTable) {
        $imgTable = getImageTable();
    }

    $sAction = null;
    $iRows = 0;
    $sQuery = "SELECT resp_id, mob_img_id, file_domain, file_path, file_name, file_caption FROM $imgTable WHERE dstatus = 0 AND uni_id = '$uniId' $sCond ORDER BY mob_img_id";
    $dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

    if ($iRows > 0) {
        $i = 1;
        while ($row = $dbConn->GetData($sAction)) {
            $imagePath = $useFileDomain ? $row["file_domain"] . constant("PRODS_ANY_FOLDER") : $CUST_FOLDER_PATH;
            $arrImages[] = formatListingImage(
                $imagePath . $row["file_path"],
                $row["file_name"],
                true,
                true,
                $useIndexAsLabelKey ? $arrLabels[$i] : ($staticLabel ? $staticLabel : ($labelKey ? $arrLabels[$labelKey] : (isset($arrLabels[$row["mob_img_id"]]) ? $arrLabels[$row["mob_img_id"]] : ""))),
                $row["resp_id"]
            );
            $i++;
        }
    }

    return $arrImages;
}

// get formatted images list
function formatListingImage($path, $imageName, $smallImage = false, $mediumImage = false, $description = "", $imgId = "", $thumbnailTitle = "", $thumbnailContent = "", $thumbnailAltText = "")
{
    return [
        "big" => $path . $imageName,
        "small" => $smallImage ? $path . "thumb_" . $imageName : $path . $imageName,
        "medium" => $mediumImage ? $path . "thumb_" . $imageName : $path . $imageName,
        "description" => $description,
        "id" => $imgId,
        "downloadFileName" => $imageName,
        "thumbnailTitle" => $thumbnailTitle,
        "thumbnailContent" => $thumbnailContent,
        "thumbnailAltText" => $thumbnailAltText,
    ];
}

// format data in a required format to be used for graphs
function formatStatisticsData($arrConfig, $array, $xAxisValueKey, $yAxisValueKeys = [], $yAxisLabelKeys = [], $isXAxisLabelKeyDate = true)
{
    $arrData = [];

    // single line chart
    if ($arrConfig["type"] === 0) {
        if (isNonEmptyArray($array)) {
            $arrData[] = [
                "name" => $yAxisLabelKeys && $yAxisLabelKeys[0] ? $yAxisLabelKeys[0] : "",
            ];
            $len = count($arrData);
            $arrData[$len - 1]["series"] = [];

            foreach ($array as $data) {
                $arrData[$len - 1]["series"][] = [
                    "name" => $isXAxisLabelKeyDate ? currentDate($data[$xAxisValueKey], "d-m-Y") : $data[$xAxisValueKey],
                    "value" => $data[$yAxisValueKeys[0]],
                ];
            }
        }
    } elseif ($arrConfig["type"] === 5 || $arrConfig["type"] === 6) {
        // stack area or area chart
        if (isNonEmptyArray($array)) {
            foreach ($array as $key => $data) {
                $arrData[] = [
                    "name" => $key,
                ];

                $len = count($arrData);
                $arrData[$len - 1]["series"] = [];

                if (isNonEmptyArray($data)) {
                    foreach ($data as $arr) {
                        $arrData[$len - 1]["series"][] = [
                            "name" => $arr[$xAxisValueKey],
                            "value" => $arr[$yAxisValueKeys[0]],
                        ];
                    }
                }
            }

            // check if any value is null, if so replace it with 0
            if (isNonEmptyArray($arrData)) {
                foreach ($arrData as $pIndex => $series) {
                    foreach ($series["series"] as $index => $data) {
                        if (!$data["value"]) {
                            $arrData[$pIndex]["series"][$index]["value"] = 0;
                        }
                    }
                }
            }
        }
    } else {
        if (isNonEmptyArray($array)) {
            foreach ($array as $sKey => $data) {
                $arrData[] = [
                    "name" => $isXAxisLabelKeyDate ? currentDate($data[$xAxisValueKey], "d-m-Y") : ($xAxisValueKey ? $data[$xAxisValueKey] : $sKey),
                ];

                $len = count($arrData);
                $arrData[$len - 1]["series"] = [];

                if (isNonEmptyArray($yAxisValueKeys)) {
                    foreach ($yAxisValueKeys as $key => $label) {
                        $arrData[$len - 1]["series"][] = [
                            "name" => $yAxisLabelKeys[$key],
                            "value" => $data[$label],
                        ];
                    }
                }
            }

            // check if any value is null, if so replace it with 0
            if (isNonEmptyArray($arrData)) {
                foreach ($arrData as $pIndex => $series) {
                    foreach ($series["series"] as $index => $data) {
                        if (!$data["value"]) {
                            $arrData[$pIndex]["series"][$index]["value"] = 0;
                        }
                    }
                }
            }
        }
    }

    $arrConfig["data"] = $arrData;
    return $arrConfig;
}

// format data in a required format to be used for download
function formatDownloadData($fileName = "file", $header = [], $body = [], $includeDatetimeInFileName = true)
{
    return [
        "fileName" => $fileName . ($includeDatetimeInFileName ? "_" . str_replace([" ", "-", ":"], "_", currentDateTime()) : ""),
        "header" => $header,
        "body" => $body,
    ];
}

// format data in a required format to be used for listing
function formatReportingData($arrayData)
{
    $arrFormatedData = [];
    $arrayData0 = isset($arrayData[0]) ? $arrayData[0] : [];
    $arrayData1 = isset($arrayData[1]) ? $arrayData[1] : [];
    $arrayData2 = isset($arrayData[2]) ? $arrayData[2] : [];

    if ($arrayData0) {
        $arrFormatedData["column1"] = array_reduce(array_map("transformReportingData", $arrayData0, range(0, count($arrayData0) - 1)), "getTransformedReportingData", []);
    }
    if ($arrayData1) {
        $arrFormatedData["column2"] = array_reduce(array_map("transformReportingData", $arrayData1, range(0, count($arrayData1) - 1)), "getTransformedReportingData", []);
    }
    if ($arrayData2) {
        $arrFormatedData["column3"] = array_reduce(array_map("transformReportingData", $arrayData2, range(0, count($arrayData2) - 1)), "getTransformedReportingData", []);
    }
    return $arrFormatedData;
}

function transformReportingData($value, $index)
{
    return ["data$index" => $value];
}

function getTransformedReportingData($carry, $value)
{
    foreach ($value as $key => $val) {
        $carry[$key] = $val;
    }
    return $carry;
}

// get team name of team id
function getTeamName($dbConn, $team_id, $cond = "")
{
    // Don't use dstatus = 0
    return getRowColumn($dbConn, $GLOBALS['TABLES']["PROJECT_TEAM_TABLE"], "team_name", "team_id = $team_id $cond");
}

//generate string from array seperated by provided seperator
function getStringFromArray($array, $withoutQuotes = false, $seperator = ", ", $key = "")
{
    $str = "";
    if (isNonEmptyArray($array)) {
        $i = 1;
        foreach ($array as $val) {
            $value = isEmptyString($key) ? $val : $val[$key];

            if (matchValue($i, count($array))) {
                if ($withoutQuotes) {
                    $str .= $value;
                } else {
                    $str .= "'" . $value . "'";
                }
            } else {
                if ($withoutQuotes) {
                    $str .= $value . $seperator;
                } else {
                    $str .= "'" . $value . "'" . $seperator;
                }
            }
            $i++;
        }
    } elseif (!isEmptyString($array)) {
        // if non-empty string
        $str = $array;
    }
    return $str;
}

// get clients list
function getClients($dbConn, $all = 0, $condition = "", $returnOnlyIdsAsString = false)
{
    $clientWhere = $condition;
    // get allowed client list if filter is set
    $allowedClients = $GLOBALS["arrAccessInfo"]["user_clients"];
    if ($allowedClients) {
        $where = "client_id IN $allowedClients";
        $clientWhere = $clientWhere ? "$clientWhere AND $where" : $where;
    }

    if ($returnOnlyIdsAsString) {
        $arrClientOptions = getRowsColumn($dbConn, $GLOBALS["TABLES"]["CLIENTS_TABLE"], "client_id", $clientWhere);
        $clientOptions = isNonEmptyArray($arrClientOptions) ? getStringFromArray($arrClientOptions, true) : "";
    } else {
        $clientOptions = getOptions($dbConn, $GLOBALS["TABLES"]["CLIENTS_TABLE"], "client_name", "client_id", $clientWhere, [], $all);
    }

    return $clientOptions;
}

// get project list
// if $all = 1, include All as option in list
// if $all = 2, return only All as option in list
// else don't include All as option
function getProjects($dbConn, $client = "", $all = 0, $returnOnlyIdsAsString = false, $cond = "")
{
    // get allowed clients and projects list
    $sAllowedClients = $GLOBALS["arrAccessInfo"]["user_clients"];
    $sAllowedProjects = $GLOBALS["arrAccessInfo"]["user_projects"];

    $projectWhere = $cond;
    if ($sAllowedClients) {
        $where = "client_id IN $sAllowedClients";
        $projectWhere = $projectWhere ? "$projectWhere AND $where" : $where;
    }
    if ($sAllowedProjects) {
        // since user has some project level access, don't include All as option
        $all = 0;
        $where = "project_id IN $sAllowedProjects";
        $projectWhere = $projectWhere ? "$projectWhere AND $where" : $where;
    }

    $client = getFormData($client);
    $matchAll = checkIfAllSelected($client);

    // filter projects since user has selected multiple/single client expect All
    if (!$matchAll && $client) {
        if (isNonEmptyArray($client)) {
            $client = implode(",", $client);
            $where = "client_id IN ($client)";
        } else {
            $where = "client_id = $client";
        }
        $projectWhere = $projectWhere ? "$projectWhere AND $where" : $where;
    }

    $arrResult = [];

    if ($returnOnlyIdsAsString) {
        $arrResult = getRowsColumn($dbConn, $GLOBALS['TABLES']['PROJECTS_TABLE'], "project_id", $projectWhere);
    } else {
        $arrResult = getOptions($dbConn, $GLOBALS['TABLES']['PROJECTS_TABLE'], "project_name", "project_id", $projectWhere, [], $all);
    }

    if ($returnOnlyIdsAsString) {
        return isNonEmptyArray($arrResult) ? getStringFromArray($arrResult, true) : "";
    } else {
        return $arrResult;
    }
}

// get project list
// if $all = 1, include All as option in list
// if $all = 2, return only All as option in list
// else don't include All as option
function getTeams($dbConn, $project = "", $client = "", $all = 0, $returnOnlyIdsAsString = false, $cond = "")
{
    // get allowed teams list
    $sAllowedTeams = $GLOBALS["arrAccessInfo"]["user_teams"];

    $teamWhere = $cond;
    if ($sAllowedTeams) {
        $where = "team_id IN $sAllowedTeams";
        $teamWhere = $teamWhere ? "$teamWhere AND $where" : $where;
    }

    $project = getFormData($project);
    $matchAll = checkIfAllSelected($project);

    // filter teams since user has selected multiple/single project expect All
    if (!$matchAll && $project) {
        if (isNonEmptyArray($project)) {
            $project = implode(",", $project);
            $where = "project_id IN ($project)";
        } else {
            $where = "project_id = $project";
        }
        $teamWhere = $teamWhere ? "$teamWhere AND $where" : $where;
    }

    $arrResult = [];

    // get projects list of given client without All as option
    $sProjects = getProjects($dbConn, $client, 0, true);
    if ($sProjects) {
        $where = "project_id IN ($sProjects)";
        $teamWhere = $teamWhere ? "$teamWhere AND $where" : $where;
    }

    if ($returnOnlyIdsAsString) {
        $arrResult = getRowsColumn($dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_id", $teamWhere);
    } else {
        $arrResult = getOptions($dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", $teamWhere, [], $all);
    }

    if ($returnOnlyIdsAsString) {
        return isNonEmptyArray($arrResult) ? getStringFromArray($arrResult, true) : "";
    } else {
        return $arrResult;
    }
}

function getProjectOptions($dbConn, $client = "", $all = 0, $returnResponse = false, $cond = "", $returnOnlyIdsAsString = false, $key = "projectList")
{
    $arrMessage = [];
    $hidePopup = true;
    $status = 1;

    $projectList = getProjects($dbConn, $client, $all, $returnOnlyIdsAsString, $cond);
    $arrResult = [
        $key => $projectList,
    ];

    if ($returnResponse) {
        return $projectList;
    } else {
        $arrMessage = responseMessage($arrMessage, $status, $arrResult, $hidePopup);
        echo json_encode($arrMessage);
    }
}

function getTeamsOptions($dbConn, $project = "", $client = "", $all = 0, $returnResponse = false, $cond = "", $returnOnlyIdsAsString = false, $key = "teamList")
{
    $arrMessage = [];
    $hidePopup = true;
    $status = 1;

    $teamsList = getTeams($dbConn, $project, $client, $all, $returnOnlyIdsAsString, $cond);
    $arrResult = [
        $key => $teamsList,
    ];

    if ($returnResponse) {
        return $teamsList;
    }
    $arrMessage = responseMessage($arrMessage, $status, $arrResult, $hidePopup);
    echo json_encode($arrMessage);
}

function getTeamType($dbConn, $branchId = "", $wdCode = "")
{
    global $ARR_TEAM_TYPES;

    $where = "";
    if ($branchId) {
        $matchAll = checkIfAllSelected($branchId);
        if (!$matchAll) {
            if (!is_array($branchId)) {
                $branchId = [$branchId];
            }
            if (isNonEmptyArray($branchId)) {
                $branchIds = implode(",", $branchId);
                $where .= " AND team_type IN (SELECT is_type FROM tblproject_team WHERE dstatus = 0 AND is_type != 4 AND branch_id IN ($branchIds))";
            } else {
                $where .= " AND team_type IN (SELECT is_type FROM tblproject_team WHERE dstatus = 0 AND is_type != 4 AND branch_id = $branchId)";
            }
        }
    }

    if ($wdCode) {
        $matchAll = checkIfAllSelected($wdCode);
        if (!$matchAll) {
            if (!is_array($wdCode)) {
                $wdCode = [$wdCode];
            }
            if (isNonEmptyArray($wdCode)) {
                $wdCodes = implode(",", $wdCode);
                $where .= " AND team_type IN (SELECT is_type FROM tblproject_team WHERE dstatus = 0 AND is_type != 4 AND wd_code IN ('$wdCodes')) ";
            } else {
                $where .= " AND team_type IN (SELECT is_type FROM tblproject_team WHERE dstatus = 0 AND is_type != 4 AND wd_code = '$wdCode')";
            }
        }
    }
    $sAction = null;
    $iRows = 0;
    $arrResult = [];
    // $types = array(0 => "VAN DS", 1 => "Niche", 5 => "NPSR");
    $sQuery = "SELECT DISTINCT team_type FROM tblteams_types WHERE dstatus = 0 $where";
    $dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

    if ($iRows > 0) {
        while ($row = $dbConn->GetData($sAction)) {
            $teamType = $row['team_type'];
            if (isset($ARR_TEAM_TYPES[$teamType])) {
                $arrResult[] = [
                    "label" => $ARR_TEAM_TYPES[$teamType],
                    "value" => (string) $teamType
                ];
            }
        }
    }
    return $arrResult;
}

function getBranchWiseProducts($dbConn, $branchId = "", $teamType = "")
{
    $branchProductsTable = $GLOBALS['TABLES']["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
    $where = "";
    if ($branchId) {
        $matchAll = checkIfAllSelected($branchId);
        if (!$matchAll) {
            if (!is_array($branchId)) {
                $branchId = [$branchId];
            }
            if (isNonEmptyArray($branchId)) {
                $branchIds = implode(",", $branchId);
                $where .= " AND branch_id IN ($branchIds)";
            } else {
                $where .= " AND branch_id = $branchId";
            }
        }
    }

    // Adding the teamType WHERE condition if provided
    if (isset($teamType)  && $teamType) {
        $where .= " AND team_type = $teamType";
    }

    $sProductAction = null;
    $iProductRows = 0;
    $arrResult = [];
    $sProductQuery = "SELECT DISTINCT product_name FROM $branchProductsTable WHERE dstatus = 0 $where ORDER BY product_name";
    $dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

    if ($iProductRows > 0) {
        while ($rowProduct = $dbConn->GetData($sProductAction)) {
            $products = $rowProduct["product_name"];
            $arrResult[] = [
                "label" => $products,
                "value" => $products
            ];
        }
    }
    return $arrResult;
}

// Get Grid data as string
function getGridDataAsString($arrData, $arrLabels, $noOfColumns = 1, $noOfRows = 1, $zeroIfNotSet = false)
{
    $str = "";
    if (isNonEmptyArray($arrData)) {
        $arrFormattedData = [];
        foreach ($arrData as $data) {
            $arrFormattedData[$data["rowNo"] . "-" . $data["colNo"]] = $data["ans"];
        }
        for ($row = 1; $row <= $noOfRows; $row++) {
            for ($column = 1; $column <= $noOfColumns; $column++) {
                $str .= $arrLabels[$row . "-" . $column] . ": " . (isset($arrFormattedData[$row . "-" . $column]) ? $arrFormattedData[$row . "-" . $column] : ($zeroIfNotSet ? 0 : "")) . ", ";
            }
        }

        $str = substr($str, 0, strlen($str) - 2);
    }

    return $str;
}

// Get Grid data as array
function getGridDataAsArray($arrData, $noOfColumns = 1, $noOfRows = 1, $zeroIfNotSet = false)
{
    $arrValues = [];
    if (isNonEmptyArray($arrData)) {
        $arrFormattedData = [];
        foreach ($arrData as $data) {
            $arrFormattedData[$data["colNo"] . "-" . $data["rowNo"]] = $data["ans"];
        }
        for ($column = 1; $column <= $noOfColumns; $column++) {
            $arrValues[$column - 1] = [];
            for ($row = 1; $row <= $noOfRows; $row++) {
                $arrValues[$column - 1][] = isset($arrFormattedData[$column . "-" . $row]) && $arrFormattedData[$column . "-" . $row] ? $arrFormattedData[$column . "-" . $row] : ($zeroIfNotSet ? 0 : '');
            }
        }
    }

    return $arrValues;
}

// Get Grid data as array
function getGridDataForOrderAsArray($arrData, $noOfColumns = 1, $noOfRows = 1, $zeroIfNotSet = false)
{
    $arrValues = [];
    if (isNonEmptyArray($arrData)) {
        $arrFormattedData = [];
        foreach ($arrData as $data) {
            $arrFormattedData[$data["colNo"] . "-" . $data["rowNo"]] = $data["ans2"];
        }
        for ($column = 1; $column <= $noOfColumns; $column++) {
            $arrValues[$column - 1] = [];
            for ($row = 1; $row <= $noOfRows; $row++) {
                $arrValues[$column - 1][] = isset($arrFormattedData[$column . "-" . $row]) && $arrFormattedData[$column . "-" . $row] ? $arrFormattedData[$column . "-" . $row] : ($zeroIfNotSet ? 0 : '');
            }
        }
    }

    return $arrValues;
}

function getBranchList($dbConn, $allBranch = false, $branchCond = "", $project = "", $all = 0, $returnOnlyIdsAsString = false, $groupBy = false, $groupByKey = "")
{
    // get allowed projects and teams list
    $sAllowedProjects = $GLOBALS["arrAccessInfo"]["user_projects"];
    $sAllowedTeams = $GLOBALS["arrAccessInfo"]["user_teams"];

    $where = "";
    if ($sAllowedProjects) {
        $where = "project_id IN $sAllowedProjects";
    }
    if ($project) {
        $where = $where ? "$where AND project_id = $project" : "project_id = $project";
    }
    if ($sAllowedTeams) {
        $where = $where ? "$where AND team_id IN $sAllowedTeams" : "team_id IN $sAllowedTeams";
    }

    $sBranchCond = $branchCond;
    if (!$allBranch) {
        // Don't use dstatus = 0
        $arrBranchIds = getRowsColumn($dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", $where, [], true);
        $sBranchIds = $arrBranchIds && isNonEmptyArray($arrBranchIds) ? getStringFromArray($arrBranchIds, true) : "";
        $sCond = $sBranchIds ? "branch_id IN ($sBranchIds)" : "";
        $sBranchCond = $sBranchCond && $sCond ? "$sBranchCond AND $sCond" : ($sCond ? $sCond : $sBranchCond);
    }

    if ($returnOnlyIdsAsString) {
        return getRowsColumn($dbConn, $GLOBALS['TABLES']["BRANCH_TABLE"], "branch_id", $sBranchCond);
    } else {
        if ($groupBy) {
            $arrAllOption = [];
            if ($all == 1) {
                $arrAllOption[] = [
                    "label" => "All",
                    "value" => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
                ];
            }

            $arrOptions = getRowsColumns($dbConn, $GLOBALS['TABLES']["BRANCH_TABLE"], "branch_name AS label, branch_id AS value, main_branch AS $groupByKey", $sBranchCond, [], false, 2);
            return array_merge($arrAllOption, $arrOptions);
        } else {
            return getOptions($dbConn, $GLOBALS['TABLES']["BRANCH_TABLE"], "branch_name", "branch_id", $sBranchCond, [], $all);
        }
    }
}

function getStringFromEncodedArray($string, $separator = ", ", $otherLabel = "Other:")
{
    if ($string) {
        $arrString = json_decode($string, true);
        $arrString = is_array($arrString) ? $arrString : [$string];

        $arrOptions = [];
        if (isNonEmptyArray($arrString)) {
            foreach ($arrString as $str) {
                $arrStr = explode($otherLabel, $str);
                if ($arrStr && count($arrStr) > 1) {
                    $arrOptions[] = $arrStr[1];
                } else {
                    $arrOptions[] = $arrStr[0];
                }
            }
        }
        return implode($separator, $arrOptions);
    }

    return "";
}
