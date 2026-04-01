<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;


// phpcs:ignore
class SWDRetailerAssignTarget
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }


    final public function downloadExcelHeaders()
    {

        $products = array();
        $arrData = array(
            "Rec ID",
            "Month",
            "Year"
        );

        $products = getRowsColumn($this->_dbConn, "tblbranch_pickupstock_products", "product_name", "branch_id IN ('42','43')", array(), true);

        // Create header row
        if (isNonEmptyArray($products)) {
            $headerRow = array_merge($arrData, $products);
        } else {
            $headerRow = $arrData;
        }

        // Create data row with default values
        $currentMonth = date('m'); // Current month in mm format
        $currentYear = date('Y'); // Current year in yyyy format
        $dataRow = array("1", $currentMonth, $currentYear);

        // Add "1" for each product column
        if (isNonEmptyArray($products)) {
            $productDefaults = array_fill(0, count($products), "1");
            $dataRow = array_merge($dataRow, $productDefaults);
        }

        // Combine header and data row
        $arr_merged = array($headerRow, $dataRow);
        if (!empty($arr_merged)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->fromArray($arr_merged);

            // Auto-size columns
            foreach (range('A', $sheet->getHighestDataColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $headerRow = '1';
            $styleHeader = $sheet->getStyle('A' . $headerRow . ':' . $sheet->getHighestDataColumn() . $headerRow);
            $styleHeader->getFont()->setBold(false);
            $styleHeader->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFA500');
            $allStyle = [
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ),
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow())->applyFromArray($allStyle);

            // Save the spreadsheet
            $fileName = "SWD_RETAILER_TARGET_FORMAT" . ".xlsx";
            $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
            $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
            $fileDetails = array(
                "filePath" => $downloadFileLocation,
                "fileName" => $fileName,
            );
            $writer = new Xlsx($spreadsheet);
            try {
                $writer->save($filename);
                $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
            } catch (PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
                echo "Error saving spreadsheet: " . $e->getMessage();
            }
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }


    public function uploadData()
    {
        if (!isset($_FILES['file0']) || empty($_FILES['file0']['tmp_name'])) {
            echo json_encode(responseMessage([$GLOBALS['NO_FILE_UPLOADED'] ?? 'No file uploaded'], 2));
            return;
        }

        $file = $_FILES['file0'];
        $fileName = $file['name'];
        $fileType = $file['type'];

        // Allowed Excel MIME types
        $allowedMimes = [
            'application/vnd.ms-excel', // .xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/octet-stream'
        ];

        // Allowed extensions
        $allowedExtensions = ['xls', 'xlsx'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate MIME type and extension
        if (!in_array($extension, $allowedExtensions) || !in_array($fileType, $allowedMimes)) {
            echo json_encode(responseMessage(['Invalid file format. Only .xls or .xlsx files are allowed'], 2));
            return;
        }

        $filePath = $_FILES['file0']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);

            if (count($data) <= 1) {
                echo json_encode(responseMessage([$GLOBALS['EXCEL_NO_DATA'] ?? 'Excel has no data'], 2));
                return;
            }

            // Validate header row
            $expectedHeaders = [
                'A' => 'Rec ID',
                'B' => 'Month',
                'C' => 'Year',
            ];

            $headerRow = $data[1];
            foreach ($expectedHeaders as $col => $expectedHeader) {
                if (!isset($headerRow[$col]) || trim($headerRow[$col]) !== $expectedHeader) {
                    echo json_encode(responseMessage(["Header mismatch in column $col. Expected: '$expectedHeader', Found: '" . ($headerRow[$col] ?? 'empty') . "'"], 2));
                    return;
                }
            }

            // Get product mapping from database: product_name => summary_column_name
            $productMapping = array();
            $productColumns = getRowsColumns(
                $this->_dbConn,
                "tblbranch_pickupstock_products",
                "product_name, summary_column_name",
                "branch_id IN ('42','43') AND dstatus = 0"
            );

            if (isNonEmptyArray($productColumns)) {
                foreach ($productColumns as $productRow) {
                    $productName = trim($productRow[0]);
                    $summaryColumnName = trim($productRow[1]);
                    $productMapping[$productName] = $summaryColumnName;
                }
            }

            // Map Excel columns to database columns
            // Header row: A=Rec ID, B=Month, C=Year, D onwards = Product names
            $excelColumnMap = array(); // Maps Excel column letter to database column name

            // Process product columns from header (starting from column D)
            // Iterate through all columns in header row
            $skipColumns = ['A', 'B', 'C']; // Skip Rec ID, Month, Year
            foreach ($headerRow as $columnIndex => $headerValue) {
                // Skip first 3 columns (Rec ID, Month, Year)
                if (in_array($columnIndex, $skipColumns)) {
                    continue;
                }

                $productName = trim($headerValue);

                // Stop if we reach an empty column
                if (empty($productName)) {
                    break;
                }

                if (isset($productMapping[$productName])) {
                    $excelColumnMap[$columnIndex] = $productMapping[$productName];
                } else {
                    echo json_encode(responseMessage(["Product '$productName' in column $columnIndex not found in database"], 2));
                    return;
                }
            }

            if (empty($excelColumnMap)) {
                echo json_encode(responseMessage(["No valid product columns found in Excel"], 2));
                return;
            }

            // Start transaction - all rows must be inserted or none
            $this->_dbConn->BeginTransaction();

            $insertCount = 0;

            try {
                // Process data rows (starting from row 2, row 1 is header)
                for ($i = 2; $i <= count($data); $i++) {
                    $row = $data[$i];

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Extract basic fields
                    $rec_id = trim($row['A'] ?? '');
                    $month = trim($row['B'] ?? '');
                    $year = trim($row['C'] ?? '');

                    $rowErrors = [];

                    // Validate required fields
                    if ($rec_id === '') {
                        $rowErrors[] = 'Rec ID is required';
                    } elseif (!ctype_digit($rec_id)) {
                        $rowErrors[] = 'Rec ID must be numeric';
                    }

                    if ($month === '') {
                        $rowErrors[] = 'Month is required';
                    } elseif (!preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
                        $rowErrors[] = 'Month must be between 01 and 12';
                    }

                    if ($year === '') {
                        $rowErrors[] = 'Year is required';
                    } elseif (!preg_match('/^\d{4}$/', $year)) {
                        $rowErrors[] = 'Year must be 4 digits';
                    }

                    // If validation errors → rollback and return error
                    if (!empty($rowErrors)) {
                        $rowInfo = "Row $i";
                        if (!empty($rec_id)) {
                            $rowInfo .= " (Rec ID: $rec_id)";
                        }

                        $errorMessages = [];
                        foreach ($rowErrors as $err) {
                            $errorMessages[] = "$rowInfo - $err";
                        }

                        $this->_dbConn->RollbackTransaction();
                        echo json_encode(responseMessage($errorMessages, 2));
                        return;
                    }

                    // Prepare data for insertion
                    // Start with basic columns: prod_id, rec_id, year, month
                    // Note: prod_id needs to be determined - using rec_id as prod_id for now, adjust if needed
                    // $prod_id = $rec_id; // Adjust this based on your business logic

                    $columns = "rec_id, year, month";
                    $values = " ?, ?, ?";
                    $arrParams = [$rec_id, $year, $month];

                    // Add product columns dynamically
                    foreach ($excelColumnMap as $excelCol => $dbColumn) {
                        $productValue = trim($row[$excelCol] ?? '0');
                        $productName = $headerRow[$excelCol] ?? 'Unknown';

                        // Skip validation if value is empty (will default to 0)
                        if (!empty($productValue)) {
                            // Validate that product value is numeric
                            if (!is_numeric($productValue)) {
                                $rowInfo = "Row $i";
                                if (!empty($rec_id)) {
                                    $rowInfo .= " (Rec ID: $rec_id)";
                                }

                                $this->_dbConn->RollbackTransaction();
                                echo json_encode(responseMessage(["$rowInfo - Product '$productName' value '$productValue' must be numeric"], 2));
                                return;
                            }

                            // Check if value has more than 2 decimal places
                            if (strpos($productValue, '.') !== false) {
                                $decimalParts = explode('.', $productValue);
                                if (isset($decimalParts[1]) && strlen($decimalParts[1]) > 2) {
                                    $rowInfo = "Row $i";
                                    if (!empty($rec_id)) {
                                        $rowInfo .= " (Rec ID: $rec_id)";
                                    }

                                    $this->_dbConn->RollbackTransaction();
                                    echo json_encode(responseMessage(["$rowInfo - Product '$productName' value '$productValue' cannot have more than 2 decimal places"], 2));
                                    return;
                                }
                            }
                        }

                        // Convert to float and round to 2 decimal places
                        $productValue = is_numeric($productValue) ? round((float)$productValue, 2) : 0;

                        $columns .= ", $dbColumn";
                        $values .= ", ?";
                        $arrParams[] = $productValue;
                    }

                    // Insert record
                    $res = addRecord($this->_dbConn, "tblswd_retailer_target", $columns, $values, $arrParams);

                    // Check if insert was successful
                    if ($res == 2) {
                        $insertCount++;
                    } else {
                        // Insert failed - rollback and return error
                        $rowInfo = "Row $i";
                        if (!empty($rec_id)) {
                            $rowInfo .= " (Rec ID: $rec_id)";
                        }

                        $errorMsg = "$rowInfo - Failed to insert data";
                        if ($res == 1) {
                            $errorMsg = "$rowInfo - Record already exists";
                        } elseif ($res == 0) {
                            $errorMsg = "$rowInfo - Database insert failed";
                        }

                        $this->_dbConn->RollbackTransaction();
                        echo json_encode(responseMessage([$errorMsg], 2));
                        return;
                    }
                }

                // Check if any rows were inserted
                if ($insertCount > 0) {
                    // All rows processed successfully - commit transaction
                    $this->_dbConn->CommitTransaction();
                    echo json_encode(responseMessage([$GLOBALS['DATA_UPDATED_SUCCESSFULLY'] ?? 'Data uploaded successfully'], 1));
                } else {
                    // No rows inserted - rollback transaction
                    $this->_dbConn->RollbackTransaction();
                    echo json_encode(responseMessage([$GLOBALS['DATA_NOT_UPDATED_SUCCESSFULLY'] ?? 'No data was inserted'], 2));
                }
            } catch (Exception $e) {
                // Rollback on any exception
                $this->_dbConn->RollbackTransaction();
                echo json_encode(responseMessage(['Error processing Excel data: ' . $e->getMessage()], 2));
                return;
            }
        } catch (Exception $e) {
            echo json_encode(responseMessage(['Error reading Excel: ' . $e->getMessage()], 2));
        }
    }
}
