<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;


// phpcs:ignore
class UploadFSOTracker
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

        $products = getRowsColumn($this->_dbConn, "tblbranch_pickupstock_products", "product_name", "branch_id IN ('42','43')");

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
                'D' => 'FSO Name',
                'E' => 'FSO Id',
                'F' => 'WD Code',
                'G' => 'Parameters',
                'H' => 'Target Mar\'26 (Lacs)',
                'I' => 'MTD Ach',
                'J' => 'Ach %',
            ];


            $headerRow = $data[1];
            // print_r($data);die;
            foreach ($expectedHeaders as $col => $expectedHeader) {
                // print_r($col);die;
                if (!isset($headerRow[$col]) || trim($headerRow[$col]) !== $expectedHeader) {
                    echo json_encode(responseMessage(["Header mismatch in column $col. Expected: '$expectedHeader', Found: '" . ($headerRow[$col] ?? 'empty') . "'"], 2));
                    return;
                }
            }

            // Start transaction - all rows must be inserted or none
            $this->_dbConn->BeginTransaction();

            $insertCount = 0;

            try {
                // Process data rows (starting from row 2, row 1 is header)
                for ($i = 4; $i <= count($data); $i++) {
                    $row = $data[$i];

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Extract basic fields
                    $fso_name = trim($row['D'] ?? '');
                    $fso_id = trim($row['E'] ?? '');
                    $wdCode = trim($row['F'] ?? '');
                    $parameter = trim($row['G'] ?? '');
                    $target = trim($row['H'] ?? '');
                    $mtd = trim($row['I'] ?? '');
                    $ach = trim($row['J'] ?? '');
                    // $rcd = date('Y-m-d');
                    $rcd = currentDate();
                    $rowErrors = [];

                    // Validate required fields
                    $allowedParameters = ['Business', 'Market Coverage', 'UOB ( 150 Outlets / WD)', 'Gate Working Days'];

                    // Validate parameter field - must exactly match one of the allowed values
                    if (!in_array($parameter, $allowedParameters, true)) {
                        $rowErrors[] = 'Parameter must be exactly one of: "Business", "Market Coverage", or "UOB ( 150 Outlets / WD)"';
                    }

                    // If validation errors → rollback and return error
                    if (!empty($rowErrors)) {
                        $rowInfo = "Row $i";
                        $errorMessages = [];
                        foreach ($rowErrors as $err) {
                            $errorMessages[] = "$rowInfo - $err";
                        }
                        $this->_dbConn->RollbackTransaction();
                        echo json_encode(responseMessage($errorMessages, 2));
                        return;
                    }

                    //If record already exist in table
                    $iStatus = isRecordExist($this->_dbConn, "tbl_fso_tracker", "fso_id, rcd", "fso_id = ? AND rcd < ?", array($fso_id, $rcd));
                    if ($iStatus == 1) {
                        //update d_status = 1 in table
                        $arrUpdateParams = array(1);
                        $rcdOld = getRowColumn($this->_dbConn, "tbl_fso_tracker", "rcd", "fso_id = '$fso_id'");
                        updateRecord($this->_dbConn, "tbl_fso_tracker", "dstatus = ?", "fso_id = '$fso_id' AND rcd = '$rcdOld'", $arrUpdateParams);
                    }
                    $columns = "fso_id, fso_name, wd_code, parameters, target, mtd_ach, ach_per, rcd";
                    $values = " ?, ?, ?, ?, ?, ?, ?, ?";
                    $arrParams = [$fso_id, $fso_name, $wdCode, $parameter, $target, $mtd, $ach, $rcd];
                    // Insert record
                    $res = addRecord($this->_dbConn, "tbl_fso_tracker", $columns, $values, $arrParams);

                    // Check if insert was successful
                    if ($res == 2) {
                        $insertCount++;
                    } else {
                        // Insert failed - rollback and return error
                        $rowInfo = "Row $i";
                        if (!empty($fso_id)) {
                            $rowInfo .= " (Rec ID: $fso_id)";
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
