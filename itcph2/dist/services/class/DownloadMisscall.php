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
            "dataBaseList" => $this->getDatabaseName(),
            "viewHeader" => array(
                // "Id",
                "OTP",
                "Mobile Number",
                "Is Verified",
                "Processed On",
                "RCD",
                "RDT",

            ),
            "viewBody" => array(
                // "id",
                "token",
                "rec_who",
                "showVerified",
                "processed_on",
                "rcd",
                "rdt"
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition()
    {
        $where = "";
        $where .= getFilterResult(
            isset($this->_data["searchbar"]) ? $this->_data["searchbar"] : $this->_data,
            array(
                "dateFrom" => array("rcd", 2, "dateTo"),
            ),
            $this->_dbConn
        );
        // if (
        //     isset(
        //         $this->_data['searchbar']['dateRange']['from']['year'],
        //         $this->_data['searchbar']['dateRange']['from']['month'],
        //         $this->_data['searchbar']['dateRange']['from']['day'],
        //         $this->_data['searchbar']['dateRange']['to']['year'],
        //         $this->_data['searchbar']['dateRange']['to']['month'],
        //         $this->_data['searchbar']['dateRange']['to']['day']
        //     )
        // ) {
        //     $fromArr = $this->_data['searchbar']['dateRange']['from'];
        //     $toArr   = $this->_data['searchbar']['dateRange']['to'];
        //     $from = sprintf('%04d-%02d-%02d', $fromArr['year'], $fromArr['month'], $fromArr['day']);
        //     $to   = sprintf('%04d-%02d-%02d', $toArr['year'], $toArr['month'], $toArr['day']);
        //     $where .= "AND  rcd BETWEEN '$from' AND '$to'";
        // }
        // $phoneNumber = getFormData($this->_data['searchbar'], "phoneNumber");
        $phoneNumber = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "phoneNumber");
        if (isset($phoneNumber) && !empty($phoneNumber)) {
            $where .= " AND rec_who = $phoneNumber";
        }
        return $where;
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

        $rsAction = null;
        $iRows = 0;

        $sQuery = "SELECT rec_id, token, rec_who, process, processed_on, rcd, rdt FROM $database.$project $where";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        // Create header
        $header = [];
        $header[] = [
            'ID',
            'OTP',
            'Phone',
            'Processed',
            'Processed On',
            'rcd',
            'rdt'
        ];

        $arrDataHolder = [];

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $process = $row['process'];
                if ($process == 1) {
                    $showVerified = "Verified";
                } else {
                    $showVerified = "Not Verified";
                }

                $arrDataHolder[] = [
                    cleanCSVValue($row['rec_id']),
                    cleanCSVValue($row['token']),
                    cleanCSVValue($row['rec_who']),
                    cleanCSVValue($showVerified),
                    cleanCSVValue($row['processed_on']),
                    cleanCSVValue($row['rcd']),
                    cleanCSVValue($row['rdt'])
                ];
            }
        }

        if (!empty($arrDataHolder)) {
            $arrResult = formatDownloadData("Miss_Call_Report", $header, $arrDataHolder);
            $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']), 0);
        }

        echo json_encode($arrMessage);
    }

    final public function viewData()
    {
        $where = $this->getCondition();
        $phoneCond = "";
        $database = getFormData($this->_data['searchbar'], 'database');
        $project = getFormData($this->_data['searchbar'], 'project');
        $arrData = array();
        $sOrderCond = getOrderByCond("rdt", $this->_data["sort"]);
        $sQuery = "SELECT rec_id, token, rec_who, process, processed_on, rcd, rdt FROM $database.$project  WHERE dstatus = 0 $where $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];
        // print_r($sQuery);
        // die;
        $rsAction = null;
        $iActionRows = 0;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $process = $row['process'];
                if ($process == 1) {
                    $showVerified = "Verified";
                } else {
                    $showVerified = "Not Verified";
                }
                $arrData[] = array(
                    //    "id" => $row['rec_id'],
                    "token" => $row["token"],
                    "rec_who" => $row["rec_who"],
                    "showVerified" => $showVerified,
                    "processed_on" => $row["processed_on"],
                    "rcd" => $row["rcd"],
                    "rdt" => $row["rdt"],
                );
            }
        }
        $arrData[] = array(
            "total" => $limit["total"]
        );

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrData), true);
        echo json_encode($arrMessage);
    }
}
