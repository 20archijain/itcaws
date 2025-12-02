<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

            $selectedIndexes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 20, 21, 22, 23, 24, 25, 29, 30];

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
                    // Loop through each selected column
                    foreach ($arrExcelDataColumnHeader as $colIndex => $excelColumnHeader) {
                        $columnData = trim($arrExcelData[$excelColumnHeader][$index]);

                        //  Clean lt/lg columns (force numeric or NULL)
                        if (in_array($arrSelectedColumns[$colIndex], ['lt', 'lg'])) {
                            if ($columnData === '' || !is_numeric($columnData)) {
                                $columnData = 0; // store NULL if not valid
                            } else {
                                $columnData = (float)$columnData; // cast to float
                            }
                        }

                        if (in_array($arrSelectedColumns[$colIndex], ['ds_mobile', 'outlet_mobile'])) {

                            // ---- EMPTY CHECK ----
                            if ($columnData === '' || $columnData === null || trim($columnData) === '') {
                                $errorMessage = ["Mobile number is required"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }

                            $columnData = trim($columnData);

                            // Remove +91, spaces, hyphens, etc.
                            $columnData = preg_replace('/[^0-9]/', '', $columnData);

                            if ($columnData === '' || $columnData == 0) {
                                $errorMessage = ["Mobile number is required"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }

                            // ---- LENGTH CHECK ----
                            if (strlen($columnData) !== 10) {
                                $errorMessage = ["Mobile must be exactly 10 digits: $columnData"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }

                            // ---- START DIGIT CHECK ----
                            if (!preg_match('/^[6-9]/', $columnData)) {
                                $errorMessage = ["Mobile must start with 6, 7, 8, or 9 : $columnData"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }

                            // ---- REPEATED DIGITS CHECK ----
                            if (preg_match('/^(.)\1{9}$/', $columnData)) {
                                $errorMessage = ["Invalid mobile number pattern : $columnData"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }

                            // ---- DUPLICATE CHECK ONLY IN SAME FIELD ----
                            if ($arrSelectedColumns[$colIndex] === 'ds_mobile') {
                                // Check ds_mobile only
                                $isExist = isRecordExist( $this->_dbConn, $routeDetailsTable, "ds_mobile", "ds_mobile = '$columnData' AND dstatus = 0");
                            }

                            if ($arrSelectedColumns[$colIndex] === 'outlet_mobile') {
                                // Check outlet_mobile only
                                $isExist = isRecordExist($this->_dbConn, $routeDetailsTable,"outlet_mobile","outlet_mobile = '$columnData' AND dstatus = 0");
                            }

                            if ($isExist == 1) {
                                $errorMessage = ["Mobile Number Already Exist : $columnData"];
                                echo json_encode(responseMessage([$errorMessage]));
                                exit();
                            }
                        }

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

        $exHeadBeat = "";
        foreach ($columns as $key => $colVal) {
            if ($colVal == 'outlet_type') {
                $exHead = $key;
                break;
            }
            if ($colVal == 'beat_day') {
                $exHeadBeat = $key;
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


    final public function getDownloadData()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        // create header
        $arrExcelData = [];
        $arrExcelData[] = [
            "Team id",
            "BRANCH CODE",
            "CFP SECTION CODE",
            "WD CODE",
            "NPSR Name",
            "NPSR Number",
            "Dhanush id",
            "Address",
            "Outlet Name",
            "Beat Name"
        ];

        $fileName = "ROUTE_DATA_FORMAT.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrExcelData);

        // Apply yellow background to header row
        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF00', // Yellow color
                ],
            ],
            'font' => [
                'bold' => true, 
            ],
        ];

        // Get the last column letter (J in this case - 10 columns)
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

        // Optional: Auto-size columns
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);

        echo json_encode($arrMessage);
    }
}
