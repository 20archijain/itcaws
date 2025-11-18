<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

// phpcs:ignore
class MdoRouteUpload
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];

    public function __construct($dbConn, $data, $arrAccessInfo)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
    }

    final public function getHeaderData()
    {
        $rsAction = null;
        $iRows = 0;

        $sQuery = "SHOW COLUMNS FROM tblroute_details_breeze";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $columns = [];
            while ($row = $this->_dbConn->GetData($rsAction)) {
                if ($row['Field'] !== "rec_id") {
                    $columns[] = $row['Field'];
                }
            }

            // Indexes you want to keep

            $selectedIndexes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];

            $filteredColumns = array_intersect_key($columns, array_flip($selectedIndexes));

            $filteredColumns = array_values($filteredColumns);

            if (isNonEmptyArray($filteredColumns) && isset($_FILES["file"])) {
                if ($_FILES["file"]['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES["file"]['tmp_name'];
                    $fileName = $_FILES["file"]['name'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if ($fileExt === 'xlsx' || $fileExt === 'xls') {
                        $spreadsheet = IOFactory::load($fileTmpPath);
                        $sheet = $spreadsheet->getActiveSheet();

                        $excelData = [];
                        $highestRow = $sheet->getHighestRow();
                        $highestColumn = $sheet->getHighestColumn();

                        for ($row = 1; $row <= $highestRow; $row++) {
                            $rowData = [];
                            for ($col = 'A'; $col <= $highestColumn; $col++) {
                                $rowData[] = $sheet->getCell($col . $row)->getValue();
                            }
                            $excelData[] = $rowData;
                        }

                        $headers = $excelData[0];

                        $groupedData = [];

                        for ($row = 0; $row < count($excelData); $row++) {
                            $rowData = $excelData[$row];

                            for ($col = 0; $col < count($headers); $col++) {
                                $header = $headers[$col];
                                $cellValue = isset($rowData[$col]) ? $rowData[$col] : '';

                                if (!isset($groupedData[$header])) {
                                    $groupedData[$header] = [];
                                }

                                $groupedData[$header][] = $cellValue;
                            }
                        }

                        $arrResult = array(
                            "excelHeader" => $headers,
                            "excelData" => $groupedData,
                            "tableColumns" => $filteredColumns,
                        );

                        $arrMessage = responseMessage(array(), 1, $arrResult, true);
                    } else {
                        $arrMessage = responseMessage(array($GLOBALS["INVALID_EXCEL_FILE"]));
                    }
                }
            } else {
                if (!isNonEmptyArray($columns)) {
                    $arrMessage = responseMessage(array($GLOBALS["NO_COLUMN_FOUND"]));
                } else {
                    $arrMessage = responseMessage(array($GLOBALS["NO_FILE_SELECTED"]));
                }
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS["NO_COLUMN_FOUND"]));
        }

        echo json_encode($arrMessage);
    }

    final public function uploadData()
    {
        $arrData = [];
        $arrValues = [];

        $dTypeColumns = [];
        $arrSelectedColumns = [];
        $arrExcelDataColumnHeader = [];

        $arrExcelData = $this->_data['excelData'];

        $columns = $this->_data['columns'];

        $arrColumnTypesMapping = array(
            1 => array("int", "bigint", "double", "decimal", "float", "tinyint", "smallint", "mediumint", "numeric", "real", "serial", "boolean"),
            2 => array("datetime", "timestamp"),
            3 => array("date"),
            4 => array("time"),
            5 => array("char", "varchar", "text", "nchar", "nvarchar", "binary", "varbinary", "blob", "clob", "json", "enum"),

            "bigint" => 1,
            "int" => 1,
            "double" => 1,
            "decimal" => 1,
            "float" => 1,
            "tinyint" => 1,
            "smallint" => 1,
            "mediumint" => 1,
            "numeric" => 1,
            "real" => 1,
            "serial" => 1,
            "boolean" => 1,

            "datetime" => 2,
            "timestamp" => 2,

            "date" => 3,

            "time" => 4,

            "char" => 5,
            "varchar" => 5,
            "text" => 5,
            "nchar" => 5,
            "nvarchar" => 5,
            "float" => 1,
            "float" => 1,
            "blob" => 5,
            "clob" => 5,
            "json" => 5,
            "enum" => 5,

        );

        // Get columns in which data has to be inserted
        if (isset($this->_data['columns']) && is_array($this->_data['columns'])) {
            foreach ($this->_data['columns'] as $key => $tableColumnName) {
                if ($tableColumnName) {
                    // insert data in this column
                    $dTypeColumns[] = "'$tableColumnName'";
                    $arrSelectedColumns[] = $tableColumnName;
                    $arrValues[] = "?";

                    // Take data from this excel column
                    $expData = explode("-", $key);
                    $arrExcelDataColumnHeader[] = $expData[2];
                }
            }

            // Loop through each row
            foreach ($arrExcelData[$arrExcelDataColumnHeader[0]] as $index => $data) {
                $arrSubData = array();
                $isAnyDataFound = false;
                if ($index > 0) {

                    // -----------------------------------------
                    // TEAM EXISTENCE CHECK + INSERT IF NOT EXISTS
                    // -----------------------------------------

                    // Get column indexes dynamically
                    $teamIdPos   = array_search('team_id', $arrSelectedColumns);
                    $branchIdPos = array_search('branch_id', $arrSelectedColumns);
                    $teamNamePos = array_search('ds_name', $arrSelectedColumns);
                    $teamTypePos = array_search('team_type', $arrSelectedColumns);
                    $wdCodePos   = array_search('wd_code', $arrSelectedColumns);

                    // Extract values from Excel row
                    $teamId   = trim($arrExcelData[$arrExcelDataColumnHeader[$teamIdPos]][$index]);
                    $branchId = trim($arrExcelData[$arrExcelDataColumnHeader[$branchIdPos]][$index]);
                    $teamName = trim($arrExcelData[$arrExcelDataColumnHeader[$teamNamePos]][$index]);
                    $teamType = trim($arrExcelData[$arrExcelDataColumnHeader[$teamTypePos]][$index]);
                    $wdCode   = trim($arrExcelData[$arrExcelDataColumnHeader[$wdCodePos]][$index]);

                    $isExistingTeam = isRecordExist($this->_dbConn, "tblbreeze_team", "team_id", "team_id = '$teamId'");
                    if ($isExistingTeam !== 1) {
                        $cols = "team_id, project_id, team_name, is_type, s_id, branch_id, wd_code";
                        $vals = "?, 1, ?, ?, 99, ?, ?";
                        $arrEachRow = [$teamId, $teamName, $teamType, $branchId, $wdCode];
                        addRecord($this->_dbConn, "tblbreeze_team", $cols, $vals, $arrEachRow);
                    }

                    $requiredColumns = [
                        'team_id',
                        'branch_id',
                        'team_type',
                        'ds_name'
                    ];

                    // Validate BEFORE building row
                    foreach ($requiredColumns as $reqCol) {

                        // Get index of the required column in selected columns
                        $colPos = array_search($reqCol, $arrSelectedColumns);

                        if ($colPos !== false) {
                            $excelColumn = $arrExcelDataColumnHeader[$colPos];
                            $value = trim($arrExcelData[$excelColumn][$index]);

                            if ($value === "") {
                                $errorMessage = "$reqCol cannot be empty (Row: $index). Please check the Excel.";
                                $arrMessage = responseMessage([$errorMessage]);
                                echo json_encode($arrMessage);
                                exit();
                            }
                        }
                    }

                    // Loop through each selected column
                    foreach ($arrExcelDataColumnHeader as $colIndex => $excelColumnHeader) {
                        $columnData = trim($arrExcelData[$excelColumnHeader][$index]);

                        // ✅ Clean lt/lg columns (force numeric or NULL)
                        if (in_array($arrSelectedColumns[$colIndex], ['lt', 'lg'])) {
                            if ($columnData === '' || !is_numeric($columnData)) {
                                $columnData = 0; // store NULL if not valid
                            } else {
                                $columnData = (float)$columnData; // cast to float
                            }
                        }

                        $arrSubData[] = $columnData;

                        // insert only if atleast once column data is present in a row
                        if ($columnData) {
                            $isAnyDataFound = true;
                        }
                    }

                    if ($isAnyDataFound) {
                        // Find column index of shop_uniq_code
                        $shopCodePos = array_search('shop_uniq_code', $arrSelectedColumns);

                        if ($shopCodePos !== false) {

                            $shopCodeExcelColumn = $arrExcelDataColumnHeader[$shopCodePos];
                            $shopCodeValue = trim($arrExcelData[$shopCodeExcelColumn][$index]);

                            if ($shopCodeValue !== "") {

                                // Check if already exists
                                $isExistingShopCode = isRecordExist(
                                    $this->_dbConn,
                                    "tblroute_details_breeze",
                                    "shop_uniq_code",
                                    "shop_uniq_code = '$shopCodeValue'"
                                );

                                // Skip this row if exists
                                if ($isExistingShopCode == 1) {
                                    continue; // ← SKIP INSERTING THIS RECORD
                                }
                            }
                        }
                        $arrData[] = $arrSubData;
                    }
                }
            }
        }

        $dTypeColumns = implode(",", $dTypeColumns);
        $sSelectedColumns = implode(",", $arrSelectedColumns);
        $sValues = implode(",", $arrValues);

        $rsDataType = null;
        $iRowsDataType = 0;

        $arrDataType = [];
        $columnDataTypes = [];
        $dbName = $GLOBALS["DB_DBNAME"];

        $dataTypeSelect = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = 'tblroute_details_breeze' AND COLUMN_NAME IN ($dTypeColumns) GROUP BY COLUMN_NAME ORDER BY FIELD(COLUMN_NAME, $dTypeColumns)";
        $this->_dbConn->ExecuteSelectQuery($dataTypeSelect, $rsDataType, $iRowsDataType);

        if ($iRowsDataType > 0) {
            while ($rowDataType = $this->_dbConn->GetData($rsDataType)) {
                $columnDataTypes[] = $arrColumnTypesMapping[$rowDataType['DATA_TYPE']];
            }
        }

        foreach ($arrSubData as $rowIndex => $row) {
            if (is_numeric($row) && $columnDataTypes[$rowIndex] == 5) {
                $arrDataType[$rowIndex] = 5;
            } elseif (is_numeric($row) && $columnDataTypes[$rowIndex] != 5) {
                $arrDataType[$rowIndex] = 1;
            } elseif (DateTime::createFromFormat('Y-m-d H:i:s', $row) !== false) {
                $arrDataType[$rowIndex] = 2;
            } elseif (DateTime::createFromFormat('Y-m-d', $row) !== false) {
                $arrDataType[$rowIndex] = 3;
            } elseif (DateTime::createFromFormat('H:i:s A', $row) !== false) {
                $arrDataType[$rowIndex] = 4;
            } else {
                $arrDataType[$rowIndex] = 5;
            }
        }

        $mismatchedColumns = array_diff_assoc($arrDataType, $columnDataTypes);

        if (!empty($mismatchedColumns)) {
            $errorColumns = [];
            foreach ($mismatchedColumns as $index => $mismatch) {
                $errorColumns[] = $arrExcelDataColumnHeader[$index];
            }

            $errorMessage = $GLOBALS["DATA_TYPE_NOT_MATCH"] . strtolower(implode(', ', $errorColumns));
            $arrMessage = responseMessage(array($errorMessage));
            echo json_encode($arrMessage);
            exit();
        }

        $this->_dbConn->BeginTransaction();
        $arrStatus = array();

        foreach ($arrData as $arrEachRow) {
            $arrStatus[] = addRecord($this->_dbConn, "tblroute_details_breeze", $sSelectedColumns, $sValues, $arrEachRow);
        }

        if (count($arrStatus) == 0 || in_array(0, $arrStatus)) {
            $this->_dbConn->RollbackTransaction();
            $arrMessage = responseMessage(array($GLOBALS["DATA_NOT_UPLOADED"]));
        } else {
            $this->_dbConn->CommitTransaction();
            $arrMessage = responseMessage(array($GLOBALS["DATA_UPLOADED"]), 1);
        }

        echo json_encode($arrMessage);
    }
}
