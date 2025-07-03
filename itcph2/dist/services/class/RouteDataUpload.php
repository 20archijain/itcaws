<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

// phpcs:ignore
class RouteDataUpload
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
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $rsAction = null;
        $iRows = 0;

        $sQuery = "SHOW COLUMNS FROM $routeDetailsTable";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $columns = [];
            while ($row = $this->_dbConn->GetData($rsAction)) {
                if ($row['Field'] !== "rec_id") {
                    $columns[] = $row['Field'];
                }
            }

            // Indexes you want to keep

            $selectedIndexes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 20, 21, 22, 28];

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
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];

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
            "binary" => 5,
            "varbinary" => 5,
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
                    // Loop through each selected column
                    foreach ($arrExcelDataColumnHeader as $excelColumnHeader) {
                        $columnData = $arrExcelData[$excelColumnHeader][$index];

                        $arrSubData[] = $columnData;

                        // insert only if atleast once column data is present in a row
                        if ($columnData) {
                            $isAnyDataFound = true;
                        }
                    }

                    if ($isAnyDataFound) {
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

        $dataTypeSelect = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$routeDetailsTable' AND COLUMN_NAME IN ($dTypeColumns) GROUP BY COLUMN_NAME ORDER BY FIELD(COLUMN_NAME, $dTypeColumns)";
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

        $exHead = "";

        foreach ($columns as $key => $colVal) {
            if ($colVal == 'outlet_type') {
                $exHead = $key;
                break;
            }
        }

        if ($exHead) {
            $exheader = explode('-', $exHead);
            $exhead = implode("-", array_splice($exheader, 2));
            foreach ($arrExcelData[$exhead] as $key => $exData) {
                if ($key > 0 && $key < count($arrExcelData[$exhead]) - 1) {
                    $errorColumns = $arrExcelData[$exhead][$key + 1];
                    $errorRow = $key + 1;

                    foreach ($arrExcelDataColumnHeader as $excelColumnHeader) {
                        if (!empty($arrExcelData[$excelColumnHeader][$key])) {
                            if ($exData) {
                                if (!($exData == 'ROC' || $exData == 'OTHERS')) {
                                    $errorMessage = $GLOBALS["INCORRECT_OUTLET_TYPES"] . strtolower($errorColumns . " at row " . $errorRow . " so data can not be inserted.");
                                    $arrMessage = responseMessage(array($errorMessage));
                                    echo json_encode($arrMessage);
                                    exit();
                                }
                            } else {
                                $errorMessage = $GLOBALS["EMPTY_OUTLET_COLUMN"] . strtolower($errorColumns . " at row " . $errorRow . " so data can not be inserted.");
                                $arrMessage = responseMessage(array($errorMessage));
                                echo json_encode($arrMessage);
                                exit();
                            }
                        }
                    }
                }
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
            $arrStatus[] = addRecord($this->_dbConn, $routeDetailsTable, $sSelectedColumns, $sValues, $arrEachRow);
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
