<?php

require_once $include_path . "defined_index.php";

// check if record exists
function isRecordExist($dbConn, $table, $existCol, $existCond = "", $arrParams = [], $cloud = false)
{
    global $DB_DBNAME_CLOUD;
    $rsAction = null;
    $iActionRows = 0;

    if ($existCond) {
        $existCond = "WHERE $existCond";
    }

    if ($cloud) {
        $sQuery = "SELECT $existCol FROM $DB_DBNAME_CLOUD.$table $existCond";
    } else {
        $sQuery = "SELECT $existCol FROM $table $existCond";
    }
    $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, $arrParams);

    if ($iActionRows > 0) {
        return 1;
    }
    return 0;
}

// update record
function updateRecord($dbConn, $tblName, $values, $condition = "", $arrParams = [], $cloud = false)
{
    global $DB_DBNAME_CLOUD;
    $sAction = null;
    $iNum_rows = 0;
    if (!empty($values)) {
        if ($condition) {
            $condition = "WHERE $condition";
        }

        if ($cloud) {
            $sQuery = "UPDATE $DB_DBNAME_CLOUD.$tblName SET $values $condition";
        } else {
            $sQuery = "UPDATE $tblName SET $values $condition";
        }
        $dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        if ($iNum_rows > 0) {
            return 1;
        }
        return 0;
    }
    return -1;
}

// temporary delete record
function deleteRecord($dbConn, $tblName, $column, $iUserId = 0, $condition = "", $arrParams = [], $cloud = false, $containMultipleIds = false, $paramKey = "")
{
    global $DB_DBNAME_CLOUD;
    $sAction = null;
    $iNum_rows = 0;
    if ($cloud) {
        $tblName = $DB_DBNAME_CLOUD . "." . $tblName;
    }
    if (!isEmptyString($column)) {
        if ($containMultipleIds) {
            $sQuery = "UPDATE $tblName SET dstatus = 1, modif_id = $iUserId WHERE dstatus = 0 AND $column IN ($arrParams[$paramKey])";
            $arrParams = [];
        } else {
            $sQuery = "UPDATE $tblName SET dstatus = 1, modif_id = $iUserId WHERE dstatus = 0 AND $column = ?";
        }

        if (!matchValue($condition, "")) {
            $sQuery .= $condition;
        }
        $dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        if ($iNum_rows > 0) {
            return 1;
        }
        return 0;
    }
    return -1;
}

// temporary delete record from response table
function deleteListingRecord($dbConn, $tblName, $column, $iUserId = 0, $condition = "", $data = "", $paramKey = "", $cloud = false, $printMsg = true)
{
    $arrParams = [];
    if (!isEmptyString($paramKey)) {
        $arrParams[$paramKey] = getStringFromArray($data[$paramKey]);
    }

    $iStatus = deleteRecord($dbConn, $tblName, $column, $iUserId, $condition, $arrParams, $cloud, true, $paramKey);

    if ($printMsg) {
        if (matchValue($iStatus, 1, true)) {
            $arrMessage = responseMessage([$GLOBALS['DATA_DELETED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_DELETED']]);
        }

        echo json_encode($arrMessage);
    } else {
        return $iStatus;
    }
}

// restore deleted record
function restoreRecord($dbConn, $tblName, $column, $iUserId = 0, $condition = "", $arrParams = [], $cloud = false, $containMultipleIds = false, $paramKey = "")
{
    global $DB_DBNAME_CLOUD;
    $sAction = null;
    $iNum_rows = 0;
    if ($cloud) {
        $tblName = $DB_DBNAME_CLOUD . "." . $tblName;
    }
    if (!isEmptyString($column)) {
        if ($containMultipleIds) {
            $sQuery = "UPDATE $tblName SET dstatus = 0, modif_id = $iUserId WHERE dstatus = 1 AND $column IN ($arrParams[$paramKey])";
            $arrParams = [];
        } else {
            $sQuery = "UPDATE $tblName SET dstatus = 0, modif_id = $iUserId WHERE dstatus = 1 AND $column = ?";
        }

        if (!matchValue($condition, "")) {
            $sQuery .= $condition;
        }
        $dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        if ($iNum_rows > 0) {
            return 1;
        }
        return 0;
    }
    return -1;
}

// retore deleted record from response table
function restoreListingRecord($dbConn, $tblName, $column, $iUserId = 0, $condition = "", $data = "", $paramKey = "", $cloud = false, $printMsg = true)
{
    $arrParams = [];
    if (!isEmptyString($paramKey)) {
        $arrParams[$paramKey] = getStringFromArray($data[$paramKey]);
    }

    $iStatus = restoreRecord($dbConn, $tblName, $column, $iUserId, $condition, $arrParams, $cloud, true, $paramKey);

    if ($printMsg) {
        if (matchValue($iStatus, 1, true)) {
            $arrMessage = responseMessage([$GLOBALS['DATA_RESTORED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_RESTORED']]);
        }

        echo json_encode($arrMessage);
    } else {
        return $iStatus;
    }
}

//Add new record in table
function addRecord($dbConn, $table, $cols, $vals, $arrParams = [], $cloud = false, $checkExist = 0, $existCol = "id", $existCond = "", $arrExistParams = [])
{

    global $DB_DBNAME_CLOUD;

    $iExist = 0;
    //Check if record exists
    if ($checkExist == 1) {
        $iExist = isRecordExist($dbConn, $table, $existCol, $existCond, $arrExistParams, $cloud);
    }

    //Not Exist
    if ($iExist === 0) {
        $sAction = null;
        $iNoRows = 0;
        if ($cloud) {
            $sQuery = "INSERT INTO $DB_DBNAME_CLOUD.$table ($cols) VALUES ($vals)";
        } else {
            $sQuery = "INSERT INTO $table ($cols) VALUES ($vals)";
        }
        $dbConn->ExecuteQuery($sQuery, $sAction, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            return 2;
        }
        return 0;
    } else {
        //Exist
        return 1;
    }
}

//Options list for select box
function getOptions($dbConn, $table, $label, $value = "", $where = "", $arrParams = [], $all = 0, $allLabel = "All", $isNoRecord = false, $labelKey = "label", $valueKey = "value")
{
    $arrData = [];

    //Only All option
    if ($all == 2) {
        $arrData[] = [
            $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
            $labelKey => $allLabel,
        ];
    } else {
        // check if multi column
        $iIsMultiColumn = false;
        $arrColumns = explode(",", $label);
        if (count($arrColumns) > 1) {
            $iIsMultiColumn = true;
        }

        if ($value === "") {
            $column = $label;
        } else {
            $column = $label . ", " . $value;
        }

        if (!$iIsMultiColumn) {
            $sCond = "$label IS NOT NULL";
            $where = $where ? "$sCond AND $where" : $sCond;
        }

        $sOrderByCond = "";
        if ($where) {
            $where = "WHERE $where";
            $arrOrderByPresent = explode("ORDER BY", $where);
            $isOrderByPresent = count($arrOrderByPresent) > 1 ? true : false;
            if (!$isOrderByPresent) {
                $sOrderByCond = "ORDER BY $label";
            }
        } else {
            $sOrderByCond = "ORDER BY $label";
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT DISTINCT $column FROM $table $where $sOrderByCond";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            //include all option
            if ($all == 1) {
                $arrData[] = [
                    $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
                    $labelKey => $allLabel,
                ];
            }

            while ($row = $dbConn->GetData($rsRes)) {
                $sLabel = "";
                if ($iIsMultiColumn) {
                    $i = 1;
                    foreach ($arrColumns as $column) {
                        $sLabel .= $row[trim($column)];
                        if ($i !== count($arrColumns)) {
                            $sLabel .= ", ";
                        }
                        $i++;
                    }
                } else {
                    $columns = explode(".", $label);
                    $sLabel = $row[count($columns) === 1 ? $label : $columns[1]];
                }

                $columns = explode(".", $value);
                $arrData[] = [
                    $valueKey => $value === "" ? $sLabel : $row[count($columns) === 1 ? $value : $columns[1]],
                    $labelKey => $sLabel,
                ];
            }
        } elseif ($isNoRecord) {
            $arrData[] = [
                $valueKey => "",
                $labelKey => "No Record",
            ];
        }
    }
    return $arrData;
}

//Options list for with Null Options
function getOptionsWithNull($dbConn, $table, $label, $value = "", $where = "", $arrParams = [], $null = 0, $all = 0, $allLabel = "NULL", $isNoRecord = false, $labelKey = "label", $valueKey = "value")
{
    $arrData = [];

    // Include only Null option
    if ($all == 2) {
        $arrData[] = [
            $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
            $labelKey => $allLabel,
        ];
    } else {
        $iIsMultiColumn = false;
        $arrColumns = explode(",", $label);
        if (count($arrColumns) > 1) {
            $iIsMultiColumn = true;
        }

        if ($value === "") {
            $column = $label;
        } else {
            $column = "$label, $value";
        }

        if (!$iIsMultiColumn) {
            $sCond = "$label IS NOT NULL AND $label != ''";
            $where = $where ? "$sCond AND $where" : $sCond;
        }

        $sOrderByCond = "ORDER BY $label";
        if ($where) {
            $where = "WHERE $where";
            if (stripos($where, "ORDER BY") !== false) {
                $sOrderByCond = "";
            }
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT DISTINCT $column FROM $table $where $sOrderByCond";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            if ($all == 1) {
                $arrData[] = [
                    $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
                    $labelKey => $allLabel,
                ];
            }

            while ($row = $dbConn->GetData($rsRes)) {
                $sLabel = "";
                if ($iIsMultiColumn) {
                    $i = 1;
                    foreach ($arrColumns as $column) {
                        $sLabel .= $row[trim($column)];
                        if ($i !== count($arrColumns)) {
                            $sLabel .= ", ";
                        }
                        $i++;
                    }
                } else {
                    $columns = explode(".", $label);
                    $sLabel = $row[count($columns) === 1 ? $label : $columns[1]];
                }

                $columns = explode(".", $value);
                $sValue = $value === "" ? $sLabel : $row[count($columns) === 1 ? $value : $columns[1]];

                // Ensure both label and value are not NULL or empty
                if (!empty($sLabel) && !empty($sValue)) {
                    $arrData[] = [
                        $valueKey => $sValue,
                        $labelKey => $sLabel,
                    ];
                }
            }
        } elseif ($isNoRecord) {
            $arrData[] = [
                $valueKey => "",
                $labelKey => "No Record",
            ];
        }
    }
    // Include NULL option if $null == 1
    if ($null == 1) {
        $arrData[] = [
            $valueKey => "",
            $labelKey => "NULL",
        ];
    }

    return $arrData;
}

//Get selected row and column from table
function getRowColumn($dbConn, $table, $column, $where = "", $arrParams = [])
{
    $arrData = null;
    $rsRes = null;
    $iNoRows = 0;

    if (!isEmptyString($table) && !isEmptyString($column)) {
        if ($where) {
            $where = "WHERE $where";
        }

        $sQuery = "SELECT $column FROM $table $where LIMIT 1";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $columns = explode(" AS ", $column);

            $row = $dbConn->GetData($rsRes);
            $arrData = count($columns) === 1 ? $row[trim($column)] : $row[$columns[1]];
        }
    }
    return $arrData;
}

//Get selected rows and column from table
function getRowsColumn($dbConn, $table, $column, $where = "", $arrParams = [], $distinct = false, $keytype = 0)
{
    $arrData = [];
    $rsRes = null;
    $iNoRows = 0;

    if (!isEmptyString($table) && !isEmptyString($column)) {
        $columnCond = $column;
        if ($distinct) {
            $columnCond = "DISTINCT $column";
        }
        if ($where) {
            $where = "WHERE $where";
        }

        $sQuery = "SELECT $columnCond FROM $table $where";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $i = 0;
            $columns = explode(" AS ", $column);
            while ($row = $dbConn->GetData($rsRes)) {
                if ($keytype == 1) {
                    $arrData["data" . $i] = count($columns) === 1 ? $row[trim($column)] : $row[$columns[1]];
                } else {
                    $arrData[] = count($columns) === 1 ? $row[trim($column)] : $row[$columns[1]];
                }
                $i++;
            }
        }
    }
    return $arrData;
}

//Get selected row and columns from table
function getRowColumns($dbConn, $table, $columns, $where = "", $arrParams = [], $keytype = 0)
{
    $arrData = null;
    $rsRes = null;
    $iNoRows = 0;

    if (!isEmptyString($table) && !isEmptyString($columns)) {
        if ($where) {
            $where = "WHERE $where";
        }

        $sQuery = "SELECT $columns FROM $table $where LIMIT 1";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $row = $dbConn->GetData($rsRes);
            $array = [];
            $i = 0;

            $columns = explode(",", trim($columns));
            foreach ($columns as $column) {
                $column = trim($column);
                $arrColumns = explode(" AS ", $column);

                //alphabetic index
                if ($keytype == 1) {
                    $array["data" . $i] = count($arrColumns) === 1 ? $row[$column] : $row[$arrColumns[1]];
                } else {
                    //numeric index
                    $array[] = count($arrColumns) === 1 ? $row[$column] : $row[$arrColumns[1]];
                }
                $i++;
            }
            $arrData = $array;
        }
    }
    return $arrData;
}

//Get selected rows and columns from table
function getRowsColumns($dbConn, $table, $columns, $where = "", $arrParams = [], $distinct = false, $keytype = 0)
{
    $arrData = [];

    if (!isEmptyString($table) && !isEmptyString($columns)) {
        $columnCond = $columns;
        if ($distinct) {
            $columnCond = "DISTINCT $columns";
        }

        if ($where) {
            $where = "WHERE $where";
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT $columnCond FROM $table $where";
        $dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $columns = explode(",", trim($columns));

            while ($row = $dbConn->GetData($rsRes)) {
                $array = [];
                $i = 0;

                foreach ($columns as $column) {
                    $arrColumns = explode(" AS ", trim($column));
                    $columnCount = count($arrColumns);
                    $arrColumns = $columnCount === 1 ? explode(".", trim($column)) : $arrColumns;

                    $sColumn = $columnCount === 1 ? trim($column) : trim($arrColumns[1]);
                    if ($keytype == 1) {
                        $array["data" . $i] = $row[$sColumn];
                    } elseif ($keytype == 2) {
                        // column name as index
                        $array[$sColumn] = $row[$sColumn];
                    } else {
                        $array[] = $row[$sColumn];
                    }
                    $i++;
                }
                $arrData[] = $array;
            }
        }
    }
    return $arrData;
}

//get allowed modules of a user
function getAllowedModules($dbConn, $sModules)
{
    $arrModules = [];

    $modulesTable = $GLOBALS["TABLES"]["MODULES_TABLE"];
    $sMenuAction = null;
    $iMenuRows = 0;
    $sMenuQuery = "SELECT module_id, module_name, module_code, parent_module_code, module_component, module_actioncode, module_url_link, module_icon, module_position, show_breadcrumb FROM $modulesTable" .
        " WHERE dstatus = 0 AND module_id IN ($sModules) AND module_position IN ('navbar', 'leftside', 'leftside_hidden', 'actionbar') ORDER BY module_position DESC, module_sort";
    $dbConn->ExecuteSelectQuery($sMenuQuery, $sMenuAction, $iMenuRows);

    if ($iMenuRows > 0) {
        while ($module = $dbConn->GetData($sMenuAction)) {
            if ($module['module_position'] === 'navbar') {
                $arrModules[$module['module_code']] = getModuleSchema($module);
            } elseif ($module['module_position'] === 'leftside' || $module['module_position'] === 'leftside_hidden') {
                $arrModules[$module['parent_module_code']]['submodules'][$module['module_code']] = getModuleSchema($module, $module['module_position'] === 'leftside_hidden');
            } else {
                //no submodules
                if (
                    substr($module['parent_module_code'], -1, 2) === '1' &&
                    isset($arrModules[$module['parent_module_code']]) &&
                    !isNonEmptyArray($arrModules[$module['parent_module_code']]["submodules"])
                ) {
                    $arrModules[$module['parent_module_code']]['actions'][] = $module['module_actioncode'];
                } else {
                    //have submodules
                    $module_code = preg_replace('/\d+/', '', $module['parent_module_code']) . "01";
                    $arrModules[$module_code]['submodules'][$module['parent_module_code']]['actions'][] = $module['module_actioncode'];
                }
            }
        }
    }
    return $arrModules;
}

//get login info including allowed branches, wd codes
function getAccessInfo($dbConn, $iUserId)
{
    $arrInfo = [];

    $userAuthdetailsTable = $GLOBALS["TABLES"]["USER_AUTHDETAILS_TABLE"];
    $sAction = null;
    $iRows = 0;
    $sQuery = "SELECT access_type FROM $userAuthdetailsTable WHERE user_id = ? AND dstatus = 0 LIMIT 1";
    $arrParams = [$iUserId];
    $dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows, $arrParams);

    if ($iRows === 1) {
        $arrData = $dbConn->GetData($sAction);
        $access_type = $arrData["access_type"];

        //In case of user has some specific clients, projects, branches, wd code level restriction
        $user_clients = $user_projects = $user_teams = $clients = $projects = $teams = "";

        // If not admin, get access details
        if ($access_type > 0) {
            list($clients, $projects, $teams) = getUserAccessList($dbConn, $iUserId, $access_type);

            if ($clients != "") {
                $user_clients = "(" . $clients . ")";
            }
            if ($projects != "") {
                $user_projects = "(" . $projects . ")";
            }
            if ($teams != "") {
                $user_teams = "(" . $teams . ")";
            }
        }

        $arrInfo = [
            "access_type" => $arrData["access_type"],
            "user_clients" => $user_clients,
            "user_projects" => $user_projects,
            "user_teams" => $user_teams,
            "clients" => $clients ? explode(",", $clients) : [],
            "projects" => $projects ? explode(",", $projects) : [],
            "teams" => $teams ? explode(",", $teams) : [],
        ];
    }
    return $arrInfo;
}

//get allowed clients, projects, teams list if access flag is set
function getUserAccessList($dbConn, $iUserId, $access_type)
{
    $userAccessTable = $GLOBALS["TABLES"]["USER_ACCESS_TABLE"];
    $projectsTable = $GLOBALS["TABLES"]["PROJECTS_TABLE"];
    $projectTeamTable = $GLOBALS["TABLES"]["PROJECT_TEAM_TABLE"];
    $rsAction = null;
    $iActionRows = 0;

    //Client Level
    if ($access_type == 1) {
        $cond = "AND a.client_id = c.client_id";
    } elseif ($access_type == 2) {
        //project Level
        $cond = "AND b.project_id = c.project_id";
    } elseif ($access_type == 3) {
        //Branch Level
        $cond = "AND b.branch_id = c.branch_id";
    } elseif ($access_type == 4) {
        //WD Code Level
        $cond = "AND b.wd_code = c.wd_code";
    } elseif ($access_type == 5) {
        //WD Code Level
        $cond = "AND b.circle = c.circle";
    } elseif ($access_type == 6) {
        //WD Code Level
        $cond = "AND b.section = c.section";
    } elseif ($access_type == 7) {
        //Team Level
        $cond = "AND b.team_id = c.team_id";
    } elseif ($access_type == 8) {
        //Team Level
        $cond = "AND b.is_type = c.team_type";
    }

    // Don't use a.dstatus = 0 AND b.dstatus = 0
    $sQuery = "SELECT DISTINCT a.client_id, a.project_id, b.team_id FROM $projectsTable AS a, $projectTeamTable AS b, $userAccessTable AS c WHERE c.user_id = $iUserId AND c.dstatus = 0 AND a.project_id = b.project_id $cond";
    $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
        $arrClient = $arrProject = $arrTeam = [];
        while ($row = $dbConn->GetData($rsAction)) {
            $iClientId = isset($row['client_id']) ? $row['client_id'] : "";
            $iProjectId = isset($row['project_id']) ? $row['project_id'] : "";
            $iTeamId = isset($row['team_id']) ? $row['team_id'] : "";

            if (!in_array($iClientId, $arrClient)) {
                $arrClient[] = $iClientId;
            }
            if (!in_array($iProjectId, $arrProject)) {
                $arrProject[] = $iProjectId;
            }
            if (!in_array($iTeamId, $arrTeam)) {
                $arrTeam[] = $iTeamId;
            }
        }

        return [implode(",", $arrClient), implode(",", $arrProject), implode(",", $arrTeam)];
    } else {
        return ["", "", ""];
    }
}

// get new id if already exists
function generateUniqueIdForTable($dbConn, $currentIdValue, $table, $selectColumn, $checkColumn, $seperator = "_", $condition = "")
{
    $arrParts = explode($seperator, $currentIdValue);
    if (count($arrParts) == 2) {
        $index = 1;
        $sNew_Id = $currentIdValue . $seperator . $index;
    } else {
        $index = (int) $arrParts[2];
        $arrParts[2] = $index + 1;
        $sNew_Id = implode($seperator, $arrParts);
    }

    // Don't use dstatus = 0
    $iExits = isRecordExist($dbConn, $table, $selectColumn, "$checkColumn = '$currentIdValue' $condition");

    if ($iExits == 1) {
        $currentIdValue = generateUniqueIdForTable($dbConn, $sNew_Id, $table, $selectColumn, $checkColumn, $seperator);
    }
    return $currentIdValue;
}

function checkIfAllSelected($value)
{
    $matchAll = false;

    // check if All selected
    if (isNonEmptyArray($value)) {
        $isAllFound = array_search($GLOBALS['APP_CONSTANTS']['ALL_VALUE'], $value);
        // All selected
        if ($isAllFound !== false) {
            $matchAll = true;
        }
    } else {
        $matchAll = matchValue($value, $GLOBALS['APP_CONSTANTS']['ALL_VALUE']);
    }

    return $matchAll;
}

function returnIfAllSelected($value)
{
    $matchAll = checkIfAllSelected($value);

    // return only ALL
    if ($matchAll) {
        return [$GLOBALS['APP_CONSTANTS']['ALL_VALUE']];
    }

    return $value;
}

// unlock users
function unlockUsers($dbConn, $sUserIds)
{
    $tblName = $GLOBALS["TABLES"]["USER_AUTHDETAILS_TABLE"];
    $sAction = null;
    $iNum_rows = 0;

    if (!isEmptyString($sUserIds)) {
        $sQuery = "UPDATE $tblName SET login_attempts = 0 WHERE dstatus = 0 AND user_id IN ($sUserIds)";
        $dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows);
        return $iNum_rows;
    }
    return -1;
}
