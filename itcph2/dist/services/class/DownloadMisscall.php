<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// phpcs:ignore
class DownloadMisscall
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];
    private $_iUserId = null;

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
    }


    final public function getData()
    {
        $arrResult = array(
            "dataBaseList" => $this->getDatabaseName()
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition()
    {
        if (isset($this->_data['dateRange']['from']['year'], $this->_data['dateRange']['from']['month'], $this->_data['dateRange']['from']['day'], $this->_data['dateRange']['to']['year'], $this->_data['dateRange']['to']['month'], $this->_data['dateRange']['to']['day'])) {
            $fromArr = $this->_data['dateRange']['from'];
            $toArr   = $this->_data['dateRange']['to'];
            $from = sprintf('%04d-%02d-%02d', $fromArr['year'], $fromArr['month'], $fromArr['day']);
            $to   = sprintf('%04d-%02d-%02d', $toArr['year'], $toArr['month'], $toArr['day']);
            $where = "WHERE rcd BETWEEN '$from' AND '$to'";
            return $where;
        }
    }


    final public function getDatabaseName()
    {
        $user_id = $this->_iUserId;
        $rsAction = null;
        $iRows = 0;
        $databaseName =  "%itcawsportal%";

        $sQuery = "Show DATABASES like '$databaseName'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            $arrData = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $dataBases = array_values($row)[0];  // Get the first column value dynamically
                $arrData[] = array(
                    "label" => $dataBases,
                    "value" => $dataBases
                );
            }
        }
        return $arrData;
    }

    final public function getProjectList()
    {
        $user_id = $this->_iUserId;
        $database = getFormData($this->_data, 'database');
        $rsAction = null;
        $iRows = 0;
        $tableName =  "tblcloudring_live%";

        $sQuery = "SHOW TABLES IN $database LIKE '$tableName'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            $arrData = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $tables = array_values($row)[0];  // Get the first column value dynamically

                $arrData[] = array(
                    "label" => $tables,
                    "value" => $tables
                );
            }
            $arrResult = array(
                "projectList" => $arrData
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getDownloadMissCallReport()
    {

        $database = getFormData($this->_data, 'database');
        $project = getFormData($this->_data, 'project');
        $where = $this->getCondition();
        $arrExcelData = array();
        $rsAction = null;
        $iRows = 0;

        $sQuery = "SELECT rec_id, token, rec_who, process, processed_on, rcd, rdt FROM $database.$project  $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            $arrExcelData = array(array(
                'ID',
                'OTP',
                'Phone',
                'Processed',
                'Processed On',
                'rcd',
                'rdt'
            ));
            $excelTitle = "Miss_Call_Report_";

            $showVerified = "";
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $process = $row['process'];
                if ($process == 1) {
                    $showVerified = "Verified";
                } else {
                    $showVerified = "Not Verified";
                }
                $arrExcelData[] = array(
                    $row['rec_id'],
                    $row['token'],
                    $row['rec_who'],
                    $showVerified,
                    $row['processed_on'],
                    $row['rcd'],
                    $row['rdt'],
                );
            }
        }


        if (!empty($arrExcelData)) {
            $currentDateTime = currentDateTime();
            $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
            $fileName = "$excelTitle$currentDateTime.xlsx";
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->fromArray($arrExcelData);
            $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
            $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
            $fileDetails = array(
                "filePath" => $downloadFileLocation,
                "fileName" => $fileName,
            );
            $writer = new Xlsx($spreadsheet);
            $writer->save($filename);
            $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }
        echo json_encode($arrMessage);
    }
}
