<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BreezeResponseUpload
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];

    public function __construct($dbConn, $data, $arrAccessInfo)
    {
        $this->_dbConn = $dbConn;
        $this->_data   = $data;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
    }

    private function normalizeDate($val)
    {
        if (is_numeric($val)) {
            return ExcelDate::excelToDateTimeObject($val)->format("Y-m-d");
        }
        $formats = ["d-m-Y", "Y-m-d", "d/m/Y", "d.m.Y"];
        foreach ($formats as $f) {
            $d = DateTime::createFromFormat($f, $val);
            if ($d) {
                return $d->format("Y-m-d");
            }
        }
        return $val;
    }

    private function normalizeTime($val)
    {
        if (is_numeric($val)) {
            return gmdate("H:i:s", ExcelDate::excelToTimestamp($val));
        }
        return $val;
    }

    final public function validateAndUploadData()
    {
        // Expected headers EXACTLY SAME as Excel and DB column names
        $expectedHeaders = [
            'capture_date',
            'branch_id',
            'branch_name',
            'circle',
            'section',
            'qualified',
            'present',
            'wd_code',
            'ds_id',
            'type',
            'ds_name',
            'start_time',
            'end_time',
            'total_time_spent',
            'total_km_travelled',
            'planned_outlets',
            'outlet_re_visit',
            'new_outlet_visited',
            'total_sale'
        ];

        // Mandatory fields
        $mandatoryFields = [
            'capture_date',
            'branch_id',
            'branch_name',
            'circle',
            'section',
            'wd_code',
            'ds_id',
            'type',
            'ds_name'
        ];

        // ---------------- FILE CHECK ----------------

        if (!isNonEmptyArray($_FILES["file"])) {
            echo json_encode(responseMessage([$GLOBALS["NO_COLUMN_FOUND"]]));
            return;
        }

        if ($_FILES["file"]['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(responseMessage([$GLOBALS["INVALID_EXCEL_FILE"]]));
            return;
        }

        $fileTmpPath = $_FILES["file"]['tmp_name'];
        $ext = strtolower(pathinfo($_FILES["file"]['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            echo json_encode(responseMessage([$GLOBALS["INVALID_EXCEL_FILE"]]));
            return;
        }

        $spreadsheet = IOFactory::load($fileTmpPath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // ---------------- READ HEADERS ----------------
        $headers = [];
        foreach (range('A', $highestCol) as $col) {
            $headers[] = trim($sheet->getCell($col . '1')->getValue());
        }

        // Validate header format
        if ($headers !== $expectedHeaders) {
            echo json_encode(responseMessage(["Header mismatch. Please use the correct Excel format."]));
            return;
        }

        // Columns to insert (same as Excel)
        $columns = $expectedHeaders;

        $data = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            // skip empty rows
            if ($sheet->getCell("A$r")->getValue() === null) {
                continue;
            }

            $row = [];
            $i = 0;

            foreach (range('A', $highestCol) as $col) {
                $header = $headers[$i];
                $val = $sheet->getCell($col . $r)->getValue();

                // Normalize date
                if ($header === 'capture_date' && $val !== "") {
                    $val = $this->normalizeDate($val);
                }

                // Normalize time
                if (in_array($header, ['start_time', 'end_time', 'total_time_spent']) && $val !== "") {
                    $val = $this->normalizeTime($val);
                }

                $row[$header] = $val;
                $i++;
            }

            foreach ($mandatoryFields as $field) {
                if (!isset($row[$field]) || trim($row[$field]) === "") {
                    echo json_encode(responseMessage([
                        "Mandatory field missing at ROW {$r}: {$field}"
                    ]));
                    return;
                }
            }

            $data[] = $row;
        }

        if (empty($data)) {
            echo json_encode(responseMessage(["No valid data found in Excel file."]));
            return;
        }

        $table = "tblbreeze_response_data";
        $conn  = $this->_dbConn;

        $columnList = implode(",", $columns);
        $placeholder = implode(",", array_fill(0, count($columns), "?"));
        $sql = "INSERT INTO {$table} ($columnList) VALUES ($placeholder)";

        $inserted = 0;
        $chunkSize = 1000;

        try {
            $conn->beginTransaction();

            foreach (array_chunk($data, $chunkSize) as $batch) {
                foreach ($batch as $row) {
                    $params = [];

                    // Create values in correct order
                    foreach ($columns as $colName) {
                        $params[] = $row[$colName] ?? "";
                    }

                    $iA = $iR = 0;
                    $conn->ExecuteQuery($sql, $iA, $iR, $params);
                    $inserted++;
                }
            }

            $conn->CommitTransaction();

            echo json_encode(responseMessage([
                "Data uploaded successfully. Total records: {$inserted}"
            ], 1));
        } catch (Exception $e) {
            $conn->RollbackTransaction();
            echo json_encode(responseMessage([
                "Upload failed: " . $e->getMessage()
            ]));
        }
    }

    final public function getDownloadData()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

        $arrExcelData[] = [
            'capture_date',
            'branch_id',
            'branch_name',
            'circle',
            'section',
            'qualified',
            'present',
            'wd_code',
            'ds_id',
            'type',
            'ds_name',
            'start_time',
            'end_time',
            'total_time_spent',
            'total_km_travelled',
            'planned_outlets',
            'outlet_re_visit',
            'new_outlet_visited',
            'total_sale'
        ];

        $fileName = "BREEZE_DATA_FORMAT.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrExcelData);

        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'font' => ['bold' => true],
        ];

        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }

        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = [
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        ];

        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        echo json_encode(responseMessage([$GLOBALS['FILE_DOWNLOADING']], 1, $fileDetails));
    }
}
