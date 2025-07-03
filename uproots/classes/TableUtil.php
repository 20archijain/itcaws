<?php

class TableUtil
{
    private $dbConn;
    private $commonFunctions;
    private $response;
    private $log = false;
    private $logFilename = null;

    public function __construct($dbConn, $commonFunctions, $response = null, $log = true, $logFilename = "log_TableUtil")
    {
        $this->dbConn = $dbConn;
        $this->commonFunctions = $commonFunctions;
        $this->response = $response;
        $this->log = $log;
        $this->logFilename = $logFilename;
    }

    // return no of rows if record exists
    final public function isRecordExist(
        $table,
        $existCol,
        $existCond = "",
        $arrParams = array(),
        $logQuery = false
    ) {
        if (!($table || $existCol)) {
            return -1;
        }

        $rsAction = null;
        $iActionRows = 0;
        $cond = "";
        if ($existCond) {
            $cond = "WHERE $existCond";
        }

        $sQuery = "SELECT $existCol FROM $table $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\nisRecordExist Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, $arrParams);

        return $iActionRows;
    }

    // Update record(s)
    final public function updateRecord(
        $table,
        $values,
        $condition = "",
        $arrParams = array(),
        $logQuery = false
    ) {
        if (!($table || $values)) {
            return -1;
        }

        $sAction = null;
        $iNum_rows = 0;
        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $sQuery = "UPDATE $table SET $values $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\nupdateRecord Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        return $iNum_rows;
    }

    // temporary delete record(s)
    final public function deleteRecord(
        $table,
        $column,
        $condition = "",
        $arrParams = array(),
        $iUserId = 0,
        $printMsg = false,
        $logQuery = false
    ) {
        if (!($table || $column)) {
            return -1;
        }

        $sAction = null;
        $iNum_rows = 0;
        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        if ($cond) {
            $cond .= " AND $column IN (?)";
        } else {
            $cond = "WHERE $column IN (?)";
        }

        if (!$iUserId || !is_numeric($iUserId)) {
            $iUserId = 0;
        }

        // Add ? if last param is array i.e deleting multiple records
        $last = $arrParams && $this->commonFunctions->isNonEmptyArray($arrParams) ? array_slice($arrParams, -1, 1) : null;
        $lastKey = $last ? array_keys($last)[0] : null;
        if ($last && is_array($last[$lastKey])) {
            $str = implode(",", array_fill(0, count($last[$lastKey]), "?"));
            // add ? with number of values coming
            $cond = substr($cond, 0, strlen($cond) - 2) . $str . ")";
            array_pop($arrParams);
            foreach ($last[$lastKey] as $value) {
                $arrParams[] = $value;
            }
        }

        $sQuery = "UPDATE $table SET dstatus = 1, modif_id = $iUserId $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ndeleteRecord Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        if ($printMsg) {
            if ($iNum_rows > 0) {
                $this->response->sendResponse(array("message" => $GLOBALS['DATA_DELETED_SUCCESSFULL']), 1);
            } else {
                $this->response->sendResponse(array("message" => $GLOBALS['DATA_NOT_DELETED']));
            }
        } else {
            return $iNum_rows;
        }
    }

    // permanent delete record(s)
    final public function permanentDeleteRecord(
        $table,
        $condition = "",
        $arrParams = array(),
        $logQuery = false
    ) {
        if (!$table) {
            return -1;
        }

        $sAction = null;
        $iNum_rows = 0;
        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $sQuery = "DELETE FROM $table $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\npermanentDeleteRecord Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        return $iNum_rows;
    }

    // Add single record
    final public function addRecord(
        $table,
        $cols,
        $vals,
        $arrParams = array(),
        $checkExist = 0,
        $existCol = "id",
        $existCond = "",
        $arrExistParams = array(),
        $logQuery = false
    ) {
        if (!($table || $cols || $vals)) {
            return -1;
        }

        $iExist = 0;
        // Check if record exists
        if ($checkExist == 1) {
            $iExist = $this->isRecordExist($table, $existCol, $existCond, $arrExistParams, $logQuery);
        }

        // Not Exist, so add
        if ($iExist == 0) {
            $sAction = null;
            $iNoRows = 0;

            $sQuery = "INSERT INTO $table ($cols) VALUES ($vals)";
            if ($this->log && $logQuery) {
                $this->commonFunctions->debugLog(
                    "\r\naddRecord Query: $sQuery\r\nParams: " . json_encode($arrParams),
                    $this->logFilename
                );
            }
            $this->dbConn->ExecuteQuery($sQuery, $sAction, $iNoRows, $arrParams);

            return $iNoRows;
        } else {
            // Exist
            return -2;
        }
    }

    // Get dropdown Options list for portal
    final public function getOptions(
        $table,
        $label,
        $value = "",
        $condition = "",
        $arrParams = array(),
        $all = 0,
        $distinct = true,
        $addOrderByIfNotAdded = true,
        $getNoRecordOptionIfNoResultFound = false,
        $allLabel = "All",
        $labelKey = "label",
        $valueKey = "value",
        $logQuery = false
    ) {
        if (!($table || $label)) {
            return -1;
        }

        $arrData = array();

        // Only "All" option
        if ($all == 2) {
            $arrData[] = array(
                $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
                $labelKey => $allLabel,
            );
        } else {
            // check if multi column
            $iIsMultiLabelColumn = false;
            $arrColumns = explode(", ", $label);
            if (count($arrColumns) > 1) {
                $iIsMultiLabelColumn = true;
            }

            $distinctCond = "";
            if ($distinct) {
                $distinctCond = "DISTINCT";
            }

            if ($value) {
                $columns = $label . ", " . $value;
            } else {
                $columns = $label;
            }

            $arrCond = array();
            // Add IS NOT NULL if single label and doesn't contains alias name
            if (!$iIsMultiLabelColumn && count(explode(" AS ", $label)) == 1) {
                $arrCond[] = "$label IS NOT NULL";
            }

            if ($condition) {
                $arrCond[] = $condition;
            }

            $cond = "";
            if ($this->commonFunctions->isNonEmptyArray($arrCond)) {
                $cond = "WHERE " . implode(" AND ", $arrCond);
            }

            // Add ORDER BY condition
            $sOrderByCond = "";
            if ($addOrderByIfNotAdded) {
                if ($cond) {
                    $arrOrderByPresent = explode("ORDER BY", $cond);
                    $isOrderByPresent = count($arrOrderByPresent) > 1 ? true : false;
                    if (!$isOrderByPresent) {
                        $arrLabel = explode(" AS ", $label);
                        $sOrderByLabel = count($arrLabel) == 1 ? $label : $arrLabel[1];
                        $sOrderByCond = "ORDER BY $sOrderByLabel";
                    }
                } else {
                    $arrLabel = explode(" AS ", $label);
                    $sOrderByLabel = count($arrLabel) == 1 ? $label : $arrLabel[1];
                    $sOrderByCond = "ORDER BY $sOrderByLabel";
                }
            }

            $rsRes = null;
            $iNoRows = 0;
            $sQuery = "SELECT $distinctCond $columns FROM $table $cond $sOrderByCond";
            if ($this->log && $logQuery) {
                $this->commonFunctions->debugLog(
                    "\r\ngetOptions Query: $sQuery\r\nParams: " . json_encode($arrParams),
                    $this->logFilename
                );
            }
            $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

            if ($iNoRows > 0) {
                // include all option
                if ($all == 1) {
                    $arrData[] = array(
                        $valueKey => $GLOBALS['APP_CONSTANTS']['ALL_VALUE'],
                        $labelKey => $allLabel,
                    );
                }

                while ($row = $this->dbConn->GetData($rsRes)) {
                    $sLabel = "";
                    if ($iIsMultiLabelColumn) {
                        $i = 1;
                        foreach ($arrColumns as $column) {
                            $arrSubColumns = explode(" AS ", trim($column));
                            $sLabel .= $row[count($arrSubColumns) == 1 ? trim($column) : trim($arrSubColumns[1])];
                            if ($i !== count($arrColumns)) {
                                $sLabel .= ", ";
                            }
                            $i++;
                        }
                    } else {
                        $arrSubColumns = explode(" AS ", $label);
                        $sLabel = $row[count($arrSubColumns) == 1 ? trim($label) : trim($arrSubColumns[1])];
                    }

                    $arrSubColumns = $value ? explode(" AS ", $value) : array();
                    $arrData[] = array(
                        $valueKey => $value ?
                            $row[count($arrSubColumns) == 1 ? trim($value) : trim($arrSubColumns[1])] : $sLabel,
                        $labelKey => $sLabel,
                    );
                }
            } elseif ($getNoRecordOptionIfNoResultFound) {
                $arrData[] = array(
                    $valueKey => "",
                    $labelKey => "No Record",
                );
            }
        }

        return $arrData;
    }

    // Get dropdown Options list for mobile app
    final public function getOptionsForApp(
        $table,
        $label,
        $value = "",
        $condition = "",
        $arrParams = array(),
        $distinct = true,
        $addOrderByIfNotAdded = true,
        $removeSpecialChar = true,
        $addPleaseSelectOption = true,
        $pleaseSelectLabel = "Please select",
        $labelKey = "label",
        $valueKey = "value",
        $logQuery = false
    ) {
        if (!($table || $label)) {
            return -1;
        }

        $arrData = array();

        // check if multi column
        $iIsMultiLabelColumn = false;
        $arrColumns = explode(", ", $label);
        if (count($arrColumns) > 1) {
            $iIsMultiLabelColumn = true;
        }

        $distinctCond = "";
        if ($distinct) {
            $distinctCond = "DISTINCT";
        }

        if ($value) {
            $columns = $label . ", " . $value;
        } else {
            $columns = $label;
        }

        $arrCond = array();
        // Add IS NOT NULL if single label and doesn't contains alias name
        if (!$iIsMultiLabelColumn && count(explode(" AS ", $label)) == 1) {
            $arrCond[] = "$label IS NOT NULL";
        }

        if ($condition) {
            $arrCond[] = $condition;
        }

        $cond = "";
        if ($this->commonFunctions->isNonEmptyArray($arrCond)) {
            $cond = "WHERE " . implode(" AND ", $arrCond);
        }

        // Add ORDER BY condition
        $sOrderByCond = "";
        if ($addOrderByIfNotAdded) {
            if ($cond) {
                $arrOrderByPresent = explode("ORDER BY", $cond);
                $isOrderByPresent = count($arrOrderByPresent) > 1 ? true : false;
                if (!$isOrderByPresent) {
                    $arrLabel = explode(" AS ", $label);
                    $sOrderByLabel = count($arrLabel) == 1 ? $label : $arrLabel[1];
                    $sOrderByCond = "ORDER BY $sOrderByLabel";
                }
            } else {
                $arrLabel = explode(" AS ", $label);
                $sOrderByLabel = count($arrLabel) == 1 ? $label : $arrLabel[1];
                $sOrderByCond = "ORDER BY $sOrderByLabel";
            }
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT $distinctCond $columns FROM $table $cond $sOrderByCond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ngetOptionsForApp Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            while ($row = $this->dbConn->GetData($rsRes)) {
                $sLabel = "";
                if ($iIsMultiLabelColumn) {
                    $i = 1;
                    foreach ($arrColumns as $column) {
                        $arrSubColumns = explode(" AS ", trim($column));
                        $sLabel .= $row[count($arrSubColumns) == 1 ? trim($column) : trim($arrSubColumns[1])];
                        if ($i !== count($arrColumns)) {
                            $sLabel .= ", ";
                        }
                        $i++;
                    }
                } else {
                    $arrSubColumns = explode(" AS ", $label);
                    $sLabel = $row[count($arrSubColumns) == 1 ? trim($label) : trim($arrSubColumns[1])];
                }

                $arrSubColumns = $value ? explode(" AS ", $value) : array();

                // Remove special characters
                $sLabel = htmlentities($removeSpecialChar ? $this->commonFunctions->removeSpecialCharFromString($sLabel) : $sLabel);
                if ($value) {
                    $sValue = $row[count($arrSubColumns) == 1 ? trim($value) : trim($arrSubColumns[1])];
                    $sValue = htmlentities($removeSpecialChar ? $this->commonFunctions->removeSpecialCharFromString($sValue) : $sValue);
                } else {
                    $sValue = $sLabel;
                }

                $arrData[] = array(
                    $valueKey => $sValue,
                    $labelKey => $sLabel,
                );
            }
        }

        // include blank/Please select option
        if ($addPleaseSelectOption) {
            array_unshift($arrData, array(
                $labelKey => $pleaseSelectLabel,
                $valueKey => "",
            ));
        }

        return $arrData;
    }

    // Get selected row and column value from table
    final public function getRowColumn(
        $table,
        $column,
        $condition = "",
        $arrParams = array(),
        $logQuery = false
    ) {
        if (!($table || $column)) {
            return -1;
        }

        $data = null;
        $sAction = null;
        $iNum_rows = 0;
        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $sQuery = "SELECT $column FROM $table $cond LIMIT 1";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ngetRowColumn Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iNum_rows, $arrParams);

        if ($iNum_rows > 0) {
            $columns = explode(" AS ", trim($column));

            $row = $this->dbConn->GetData($sAction);
            $data = count($columns) == 1 ? $row[trim($column)] : $row[trim($columns[1])];
        }

        return $data;
    }

    // Get selected row and columns value from table
    final public function getRowColumns(
        $table,
        $columns,
        $condition = "",
        $arrParams = array(),
        $useFirstColumnAsIndex = false,
        $keytype = 0,
        $logQuery = false
    ) {
        if (!($table || $columns)) {
            return -1;
        }

        $arrData = array();

        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT $columns FROM $table $cond LIMIT 1";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ngetRowColumns Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $row = $this->dbConn->GetData($rsRes);
            $i = 0;
            $columns = explode(", ", trim($columns));

            // Use first column as index, and 2nd column as value if only 2 columns, else
            // all columns as array of value except first
            if ($useFirstColumnAsIndex) {
                $firstColumn = trim($columns[0]);
                $arrFirstColumns = explode(" AS ", $firstColumn);
                $firstColumnCount = count($arrFirstColumns);
                $sFirstColumn = $firstColumnCount === 1 ? $firstColumn : trim($arrFirstColumns[1]);

                $arrFirstColumnOnwardsValue = array();
                foreach ($columns as $index => $column) {
                    if ($index > 0) {
                        $column = trim($column);
                        $arrColumns = explode(" AS ", $column);
                        $columnCount = count($arrColumns);
                        $sColumn = $columnCount === 1 ? $column : trim($arrColumns[1]);

                        // alphabetic index
                        if ($keytype == 1) {
                            $arrFirstColumnOnwardsValue["data" . $i] = $row[$sColumn];
                        } elseif ($keytype == 2) {
                            // column name as index
                            $arrFirstColumnOnwardsValue[$sColumn] = $row[$sColumn];
                        } else {
                            // numeric index
                            $arrFirstColumnOnwardsValue[] = $row[$sColumn];
                        }
                        $i++;
                    }
                }

                $firstColumnKey = $keytype == 1 ? "data0" : ($keytype == 2 ? $sColumn : 0);
                $arrData[$row[$sFirstColumn]] = count($arrFirstColumnOnwardsValue) == 1 ?
                    $arrFirstColumnOnwardsValue[$firstColumnKey] : $arrFirstColumnOnwardsValue;
            } else {
                foreach ($columns as $column) {
                    $column = trim($column);
                    $arrColumns = explode(" AS ", $column);
                    $columnCount = count($arrColumns);
                    $sColumn = $columnCount === 1 ? $column : trim($arrColumns[1]);

                    // alphabetic index
                    if ($keytype == 1) {
                        $arrData["data" . $i] = $row[$sColumn];
                    } elseif ($keytype == 2) {
                        // column name as index
                        $arrData[$sColumn] = $row[$sColumn];
                    } else {
                        // numeric index
                        $arrData[] = $row[$sColumn];
                    }
                    $i++;
                }
            }
        }

        return $arrData;
    }

    // Get selected rows and column value from table
    final public function getRowsColumn(
        $table,
        $column,
        $condition = "",
        $arrParams = array(),
        $distinct = false,
        $keytype = 0,
        $logQuery = false
    ) {
        if (!($table || $column)) {
            return -1;
        }

        $arrData = array();

        $sColumn = $column;
        if ($distinct) {
            $sColumn = "DISTINCT $column";
        }

        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT $sColumn FROM $table $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ngetRowsColumn Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $i = 0;
            $arrColumns = explode(" AS ", trim($column));
            $column = count($arrColumns) == 1 ? trim($column) : trim($arrColumns[1]);
            while ($row = $this->dbConn->GetData($rsRes)) {
                // alphabetic index
                if ($keytype == 1) {
                    $arrData["data" . $i] = $row[$column];
                } elseif ($keytype == 2) {
                    // column name as index
                    $arrData[$column] = $row[$column];
                } else {
                    // numeric index
                    $arrData[] = $row[$column];
                }
                $i++;
            }
        }

        return $arrData;
    }

    // Get selected rows and columns value from table
    final public function getRowsColumns(
        $table,
        $columns,
        $condition = "",
        $arrParams = array(),
        $distinct = false,
        $useFirstColumnAsIndex = false,
        $keytype = 0,
        $logQuery = false
    ) {
        if (!($table || $columns)) {
            return -1;
        }

        $arrData = array();

        $sColumns = $columns;
        if ($distinct) {
            $sColumns = "DISTINCT $columns";
        }

        $cond = "";
        if ($condition) {
            $cond = "WHERE $condition";
        }

        $rsRes = null;
        $iNoRows = 0;
        $sQuery = "SELECT $sColumns FROM $table $cond";
        if ($this->log && $logQuery) {
            $this->commonFunctions->debugLog(
                "\r\ngetRowsColumns Query: $sQuery\r\nParams: " . json_encode($arrParams),
                $this->logFilename
            );
        }
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $arrParams);

        if ($iNoRows > 0) {
            $arrColumns = explode(", ", trim($columns));

            while ($row = $this->dbConn->GetData($rsRes)) {
                $array = array();
                $i = 0;

                // Use first column as index, and
                // 2nd column as value if only 2 columns, or
                // all columns as array of value except first column
                if ($useFirstColumnAsIndex) {
                    $indexColumn = trim($arrColumns[0]);
                    $arrIndexColumns = explode(" AS ", $indexColumn);
                    $indexColumnCount = count($arrIndexColumns);
                    $sIndexColumn = $indexColumnCount === 1 ? $indexColumn : trim($arrIndexColumns[1]);

                    $arrFirstColOnwardsValue = array();
                    foreach ($arrColumns as $index => $column) {
                        if ($index > 0) {
                            $valueColumn = trim($column);
                            $arrValueColumns = explode(" AS ", $valueColumn);
                            $valueColumnCount = count($arrValueColumns);
                            $sValueColumn = $valueColumnCount === 1 ? $valueColumn : trim($arrValueColumns[1]);

                            // alphabetic index
                            if ($keytype == 1) {
                                $arrFirstColOnwardsValue["data" . $i] = $row[$sValueColumn];
                            } elseif ($keytype == 2) {
                                // column name as index
                                $arrFirstColOnwardsValue[$sValueColumn] = $row[$sValueColumn];
                            } else {
                                // numeric index
                                $arrFirstColOnwardsValue[] = $row[$sValueColumn];
                            }
                            $i++;
                        }
                    }

                    $firstColumnKey = $keytype == 1 ? "data0" : ($keytype == 2 ? $sValueColumn : 0);
                    $arrData[$row[$sIndexColumn]] = count($arrColumns) == 2 ?
                        $arrFirstColOnwardsValue[$firstColumnKey] : $arrFirstColOnwardsValue;
                } else {
                    foreach ($arrColumns as $column) {
                        $column = trim($column);
                        $arrSubColumns = explode(" AS ", $column);
                        $columnCount = count($arrSubColumns);

                        $sColumn = $columnCount === 1 ? $column : trim($arrSubColumns[1]);
                        // alphabetic index
                        if ($keytype == 1) {
                            $array["data" . $i] = $row[$sColumn];
                        } elseif ($keytype == 2) {
                            // column name as index
                            $array[$sColumn] = $row[$sColumn];
                        } else {
                            // numeric index
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

    // unlock portal users whose password is locked due to max attempts
    final public function unlockUsers($table, $sUserIds, $arrParams = array())
    {
        if (!$this->commonFunctions->isEmptyString($sUserIds)) {
            return $this->updateRecord(
                $table,
                "login_attempts = 0",
                "dstatus = 0 AND user_id IN ($sUserIds)",
                $arrParams
            );
        }

        return -1;
    }

    // get pagination limit for query
    final public function getPaginationLimit($arrData, $query, $arrParams = array())
    {
        $sAction = null;
        $total = 0;
        $this->dbConn->ExecuteSelectQuery($query, $sAction, $total, $arrParams);

        $limit = $this->commonFunctions->getFormData($arrData, "limit");
        $page = $this->commonFunctions->getFormData($arrData, "page");

        if (!$limit || !is_numeric($limit)) {
            $limit = 10;
        }
        if (!is_numeric($page)) {
            $page = 1;
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
        return array("limit" => $limit, "total" => $total);
    }

    // get filter query string
    final public function getFilterResult($data, $filters)
    {
        // global $tableUtil, $commonFunctions;
        $strFilter = "";
        $arrParams = array();

        if ($this->commonFunctions->isNonEmptyArray($filters)) {
            $strFilter = "AND ";
            foreach ($filters as $formKey => $filter) {
                if (is_array($filter) && (($filter[1] !== 4 && isset($data[$formKey]) && !$this->commonFunctions->matchValue($data[$formKey], "")) || $filter[1] === 4)) {
                    // Use IN operator
                    if ($filter[1] === 0) {
                        // make array if not to ease query params
                        $arrValue = is_array($data[$formKey]) ? $data[$formKey] : array($data[$formKey]);

                        // if selected "all", don't include in search
                        if (isset($arrValue[0]) && $arrValue[0] !== $GLOBALS['APP_CONSTANTS']["ALL_VALUE"]) {
                            list($inStr, $arrInParams) = $this->commonFunctions->getSafeStringFromArrayForInClause($arrValue);
                            $strFilter .= "{$filter[0]} IN ($inStr) AND ";
                            $arrParams = array_merge($arrParams, $arrInParams);
                        }
                    } elseif ($filter[1] === 1) {
                        // Use LIKE operator
                        $strFilter .= "{$filter[0]} LIKE ? AND ";
                        $arrParams[] = "%" . $data[$formKey] . "%";
                    } elseif ($filter[1] === 2) {
                        // Use BETWEEN operator for 2 dates
                        $dateFrom = $this->commonFunctions->currentDate("Y-m-d", $this->commonFunctions->getValidDate($data[$formKey]));
                        $dateTo = $this->commonFunctions->currentDate("Y-m-d", $this->commonFunctions->getValidDate($data[$filter[2]]));
                        $strFilter .= "{$filter[0]} BETWEEN ? AND ? AND ";
                        $arrParams[] = $dateFrom;
                        $arrParams[] = $dateTo;
                    } elseif ($filter[1] === 4) {
                        // Use BETWEEN or >= or <= operator for 2 dates
                        if (isset($filter[3]) && $filter[3]) {
                            $dateFrom = $this->commonFunctions->getValidDate($data[$formKey]);
                            $dateTo = $this->commonFunctions->getValidDate($data[$filter[2]]);
                        } else {
                            $dateFrom = $data[$formKey];
                            $dateTo = $data[$filter[2]];
                        }

                        if ($dateFrom && $dateTo) {
                            $dateFrom = $this->commonFunctions->currentDate("Y-m-d", $dateFrom);
                            $dateTo = $this->commonFunctions->currentDate("Y-m-d", $dateTo);
                            $strFilter .= "{$filter[0]} BETWEEN ? AND ? AND ";
                            $arrParams[] = $dateFrom;
                            $arrParams[] = $dateTo;
                        } elseif ($dateFrom && !$dateTo) {
                            $dateFrom = $this->commonFunctions->currentDate("Y-m-d", $dateFrom);
                            $strFilter .= "{$filter[0]} >= ? AND ";
                            $arrParams[] = $dateFrom;
                        } elseif (!$dateFrom && $dateTo) {
                            $dateTo = $this->commonFunctions->currentDate("Y-m-d", $dateTo);
                            $strFilter .= "{$filter[0]} <= ? AND ";
                            $arrParams[] = $dateTo;
                        }
                    } else {
                        // Use = operator

                        // if selected "all", don't include in search
                        if ($data[$formKey] !== $GLOBALS['APP_CONSTANTS']["ALL_VALUE"]) {
                            $strFilter .= "{$filter[0]} = ? AND ";
                            $arrParams[] = $data[$formKey];
                        }
                    }
                }
            }

            // Remove last AND
            $andPos = strrpos($strFilter, " AND");
            if ($andPos >= 0) {
                $strFilter = substr($strFilter, 0, $andPos);
            }
        }

        return array($strFilter, $arrParams);
    }
}
