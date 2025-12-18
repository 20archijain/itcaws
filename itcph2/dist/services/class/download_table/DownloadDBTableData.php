<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

class DownloadTable
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

    // Helper method to safely escape values
    private function escapeValue($value)
    {
        // Try different methods to escape the value
        if (method_exists($this->_dbConn, 'escapeString')) {
            return $this->_dbConn->escapeString($value);
        } elseif (method_exists($this->_dbConn, 'escape')) {
            return $this->_dbConn->escape($value);
        } elseif (method_exists($this->_dbConn, 'real_escape_string')) {
            return $this->_dbConn->real_escape_string($value);
        } elseif (method_exists($this->_dbConn, 'getConnection')) {
            return mysqli_real_escape_string($this->_dbConn->getConnection(), $value);
        } else {
            // Fallback: basic escaping (not recommended for production)
            return addslashes($value);
        }
    }

    final public function getDownloadSqlTable()
    {
        $projectIdCond = null;
        $arrMessage = null; // Initialize the message variable
        ob_start(); // Start output buffering

        $database = getFormData($this->_data, 'database');
        $table = getFormData($this->_data, 'table');
        $projectId = getFormData($this->_data, 'projectId');
        $type = getFormData($this->_data, 'type'); // Get the option value
        $conditions = getFormData($this->_data, 'conditions'); // Get custom conditions

        // Handle project ID condition
        if ($projectId && $table == 'tblroute_details') {
            $projectIdCond .= " WHERE pid = '$projectId'";
        } elseif ($projectId && $table !== 'tblroute_details') {
            $projectIdCond .= " WHERE project_id = '$projectId'";
        }

        // Build WHERE clause with custom conditions
        $whereClause = $this->buildWhereClause($projectIdCond, $conditions);

        // Set the file name for the download
        $fileName = $table  . ".sql";

        // Define the path and URL to save the file
        $filePath = $GLOBALS["UPROOTS_PATH"] . "/database" . "/$fileName";
        $downloadFileLocation = $GLOBALS["SITE_URL"] . "/database" . "/$fileName";
        $fileDetails = array(
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        );

        // Open the file for writing
        $output = fopen($filePath, 'w');

        if (!$output) {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
            echo json_encode($arrMessage);
            return;
        }

        // Write phpMyAdmin-style header
        fwrite($output, "-- phpMyAdmin SQL Dump\n");
        fwrite($output, "-- version 5.0.0\n");
        fwrite($output, "-- https://www.phpmyadmin.net/\n");
        fwrite($output, "--\n");
        fwrite($output, "-- Host: localhost\n");
        fwrite($output, "-- Generation Time: " . date('M d, Y') . " at " . date('h:i A') . "\n");
        fwrite($output, "-- Server version: 8.0.0\n");
        fwrite($output, "-- PHP Version: 7.4.0\n");
        fwrite($output, "\n");
        fwrite($output, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($output, "START TRANSACTION;\n");
        fwrite($output, "SET time_zone = \"+00:00\";\n");
        fwrite($output, "\n");
        fwrite($output, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        fwrite($output, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        fwrite($output, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        fwrite($output, "/*!40101 SET NAMES utf8mb4 */;\n");
        fwrite($output, "\n");
        fwrite($output, "--\n");
        fwrite($output, "-- Database: `$database`\n");
        fwrite($output, "--\n");
        fwrite($output, "\n");

        $hasStructure = false;
        $hasData = false;

        // If the option is 'structure', fetch and write the CREATE TABLE statement
        if ($type == 'structure' || $type == '') {
            $sQuery = "SHOW CREATE TABLE `$database`.`$table`";
            $rsAction = null;
            $iRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                $row = $this->_dbConn->GetData($rsAction);
                fwrite($output, "-- --------------------------------------------------------\n");
                fwrite($output, "\n");
                fwrite($output, "--\n");
                fwrite($output, "-- Table structure for table `$table`\n");
                fwrite($output, "--\n");
                fwrite($output, "\n");
                // fwrite($output, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($output, "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
                fwrite($output, "/*!40101 SET character_set_client = utf8 */;\n");
                fwrite($output, $row['Create Table'] . ";\n");
                fwrite($output, "/*!40101 SET character_set_client = @saved_cs_client */;\n");
                fwrite($output, "\n");
                $hasStructure = true;
            }
        }

        // If the type is 'data' or empty, write INSERT statements
        if ($type == 'data' || $type == '') {
            // Query to fetch rows based on conditions
            $sQuery = "SELECT * FROM `$database`.`$table` $whereClause";
            $rsAction = null;
            $iRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                fwrite($output, "--\n");
                fwrite($output, "-- Dumping data for table `$table`\n");
                fwrite($output, "--\n");
                fwrite($output, "\n");

                // Get the first row to determine column structure
                $firstRow = $this->_dbConn->GetData($rsAction);
                if ($firstRow) {
                    $columns = array_keys($firstRow);
                    $columnList = "`" . implode("`, `", $columns) . "`";

                    // Start the INSERT statement
                    fwrite($output, "INSERT INTO `$table` ($columnList) VALUES\n");

                    $rowCount = 0;
                    $batchSize = 100; // Number of rows per INSERT statement
                    $allRows = array($firstRow); // Include the first row we already fetched

                    // Fetch all remaining rows
                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        $allRows[] = $row;
                    }

                    $totalRows = count($allRows);

                    // Process rows in batches
                    for ($i = 0; $i < $totalRows; $i++) {
                        $row = $allRows[$i];

                        // If this is the start of a new batch (and not the first batch), start a new INSERT
                        if ($i > 0 && $i % $batchSize == 0) {
                            fwrite($output, ";\n\nINSERT INTO `$table` ($columnList) VALUES\n");
                            $rowCount = 0;
                        }

                        // Escape and quote values
                        $escapedValues = [];
                        foreach (array_values($row) as $value) {
                            if ($value === null) {
                                $escapedValues[] = 'NULL';
                            } else {
                                // Use the helper method for escaping
                                $escapedValue = $this->escapeValue($value);
                                $escapedValues[] = "'" . $escapedValue . "'";
                            }
                        }

                        // Add comma if not the first row in current batch
                        $prefix = ($rowCount > 0) ? ",\n" : "";

                        // Write the values with proper formatting
                        fwrite($output, $prefix . "(" . implode(", ", $escapedValues) . ")");
                        $rowCount++;
                    }

                    // Close the final INSERT statement
                    fwrite($output, ";\n");
                    $hasData = true;
                } else {
                    fwrite($output, "-- No records found matching the criteria\n");
                }
            } else {
                fwrite($output, "-- No records found matching the criteria\n");
            }
        }

        // Write phpMyAdmin-style footer
        fwrite($output, "\n");
        fwrite($output, "COMMIT;\n");
        fwrite($output, "\n");
        fwrite($output, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
        fwrite($output, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
        fwrite($output, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

        fclose($output);

        // Set appropriate message based on what was exported
        if ($hasStructure || $hasData) {
            $arrMessage = responseMessage(array($GLOBALS['FILE_DOWNLOADING']), 1, $fileDetails);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }

        echo json_encode($arrMessage);
    }

    private function buildWhereClause($baseCondition, $conditions)
    {
        $whereClause = $baseCondition;

        if (empty($conditions) || !is_array($conditions)) {
            // Add date range condition if exists
            if ($whereClause == '') {
                $whereClause .= $this->getCondition("WHERE");
            } elseif (isset($whereClause)) {
                $whereClause .= $this->getCondition("AND");
            }
            return $whereClause;
        }

        foreach ($conditions as $condition) {
            if (empty($condition['column']) || empty($condition['operator'])) {
                continue;
            }

            $column = $this->escapeValue($condition['column']);
            $operator = $condition['operator'];
            $value = $condition['value'] ?? '';
            $logicalOperator = $condition['logicalOperator'] ?? 'AND';

            // Determine if we need WHERE or AND/OR
            if (empty($whereClause)) {
                $connective = "WHERE";
            } else {
                $connective = " " . strtoupper($logicalOperator) . " ";
            }

            // Build condition based on operator
            switch ($operator) {
                case '=':
                case '!=':
                case '<':
                case '>':
                case '<=':
                case '>=':
                    $escapedValue = $this->escapeValue($value);
                    $whereClause .= "$connective `$column` $operator '$escapedValue'";
                    break;

                case 'LIKE':
                case 'NOT LIKE':
                    $escapedValue = $this->escapeValue($value);
                    $whereClause .= "$connective `$column` $operator '%$escapedValue%'";
                    break;

                case 'IN':
                case 'NOT IN':
                    if (is_array($value)) {
                        $values = array_map(function ($v) {
                            return "'" . $this->escapeValue(trim($v)) . "'";
                        }, $value);
                        $valueList = implode(',', $values);
                    } else {
                        // If it's a string, split by comma and trim each value
                        $valueArray = array_map('trim', explode(',', $value));
                        $values = array_map(function ($v) {
                            return "'" . $this->escapeValue($v) . "'";
                        }, $valueArray);
                        $valueList = implode(',', $values);
                    }
                    $whereClause .= "$connective `$column` $operator ($valueList)";
                    break;

                case 'IS NULL':
                case 'IS NOT NULL':
                    $whereClause .= "$connective `$column` $operator";
                    break;

                case 'BETWEEN':
                    if (isset($condition['value2'])) {
                        $value1 = $this->escapeValue($value);
                        $value2 = $this->escapeValue($condition['value2']);
                        $whereClause .= "$connective `$column` BETWEEN '$value1' AND '$value2'";
                    }
                    break;
            }
        }

        // Add date range condition if exists
        $dateCondition = $this->getCondition($whereClause ? "AND" : "WHERE");
        if ($dateCondition) {
            $whereClause .= $dateCondition;
        }

        return $whereClause;
    }

    final public function previewQuery()
    {
        $database = getFormData($this->_data, 'database');
        $table = getFormData($this->_data, 'table');
        $projectId = getFormData($this->_data, 'projectId');
        $conditions = getFormData($this->_data, 'conditions');

        if (!$database || !$table) {
            $arrMessage = responseMessage(array('Database and table are required'), 0);
            echo json_encode($arrMessage);
            return;
        }

        $projectIdCond = null;

        // Handle project ID condition
        if ($projectId && $table == 'tblroute_details') {
            $projectIdCond .= " WHERE pid = '$projectId'";
        } elseif ($projectId && $table !== 'tblroute_details') {
            $projectIdCond .= " WHERE project_id = '$projectId'";
        }

        // Build WHERE clause with custom conditions
        $whereClause = $this->buildWhereClause($projectIdCond, $conditions);

        // Build the preview query
        $previewQuery = "SELECT * FROM `$database`.`$table` $whereClause";

        // Get row count for the query
        $countQuery = "SELECT COUNT(*) as total FROM `$database`.`$table` $whereClause";
        $rsAction = null;
        $iRows = 0;
        $this->_dbConn->ExecuteSelectQuery($countQuery, $rsAction, $iRows);

        $totalRows = 0;
        if ($iRows > 0) {
            $row = $this->_dbConn->GetData($rsAction);
            $totalRows = $row['total'];
        }

        $previewData = array(
            'query' => $previewQuery,
            'estimatedRows' => $totalRows,
            'database' => $database,
            'table' => $table
        );

        $arrMessage = responseMessage(array('Query preview generated successfully'), 1, $previewData);
        echo json_encode($arrMessage);
    }

    final public function formatQueryForDisplay($query)
    {
        // Add line breaks and indentation for better readability
        $formattedQuery = str_replace('SELECT', "SELECT\n  ", $query);
        $formattedQuery = str_replace('FROM', "\nFROM\n  ", $formattedQuery);
        $formattedQuery = str_replace('WHERE', "\nWHERE\n  ", $formattedQuery);
        $formattedQuery = str_replace(' AND ', "\n  AND ", $formattedQuery);
        $formattedQuery = str_replace(' OR ', "\n  OR ", $formattedQuery);

        return $formattedQuery;
    }

    final public function getData()
    {
        $arrResult = array(
            "dataBaseList" => $this->getDatabaseName(),
            "projectList" => getOptions($this->_dbConn, "tblprojects", "project_name", "project_id"),
            "typeList" => array(
                array("label" => "Download Structure Only", "value" => "structure"),
                array("label" => "Download Data Only", "value" => "data"),
                array("label" => "Download Structure And Data", "value" => ""),
            ),
            "operatorList" => array(
                array("label" => "Equals", "value" => "="),
                array("label" => "Not Equals", "value" => "!="),
                array("label" => "Greater Than", "value" => ">"),
                array("label" => "Less Than", "value" => "<"),
                array("label" => "Greater Than or Equal", "value" => ">="),
                array("label" => "Less Than or Equal", "value" => "<="),
                array("label" => "LIKE", "value" => "LIKE"),
                array("label" => "NOT LIKE", "value" => "NOT LIKE"),
                array("label" => "IN", "value" => "IN"),
                array("label" => "NOT IN", "value" => "NOT IN"),
                array("label" => "BETWEEN", "value" => "BETWEEN"),
                array("label" => "IS NULL", "value" => "IS NULL"),
                array("label" => "IS NOT NULL", "value" => "IS NOT NULL"),
            ),
            "logicalOperatorList" => array(
                array("label" => "AND", "value" => "AND"),
                array("label" => "OR", "value" => "OR"),
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getCondition($con = "AND")
    {
        $where = "";
        if (!empty($this->_data['searchbar']['dateRange']['from']) && !empty($this->_data['searchbar']['dateRange']['to'])) {
            $from = date('Y-m-d', strtotime($this->_data['searchbar']['dateRange']['from']));
            $to = date('Y-m-d', strtotime($this->_data['searchbar']['dateRange']['to']));
            $where = " $con rcd BETWEEN '$from' AND '$to'";
        }
        return $where;
    }

    // Get table columns for building conditions
    final public function getTableColumns()
    {
        $database = getFormData($this->_data, 'database');
        $table = getFormData($this->_data, 'table');

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SHOW COLUMNS FROM `$database`.`$table`";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

        if ($iRows > 0) {
            $arrData = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = array(
                    "label" => $row['Field'],
                    "value" => $row['Field'],
                    "type" => $row['Type']
                );
            }
            $arrResult = array("columnList" => $arrData);
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    // database names
    final public function getDatabaseName()
    {
        $rsAction = null;
        $iRows = 0;

        $sQuery = "Show DATABASES like '%itcawsportal_itcph2%'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            $arrData = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $dataBases = $row['Database (%itcawsportal_itcph2%)'];
                $arrData[] = array(
                    "label" => $dataBases,
                    "value" => $dataBases
                );
            }
        }
        return $arrData;
    }

    // Method for retrieving the table list for a specific database
    final public function getTableList()
    {
        $database = getFormData($this->_data, 'database');
        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT table_name FROM information_schema.tables WHERE table_schema = '$database' AND table_name LIKE '%tbl%'";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);
        if ($iRows > 0) {
            $arrData = array();
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $tables = $row['table_name'];
                $arrData[] = array(
                    "label" => $tables,
                    "value" => $tables
                );
            }
            $arrResult = array("tableList" => $arrData);
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getProjectList()
    {
        $database = getFormData($this->_data, 'database');
        $arrResult = array(
            "projectList" => getOptions($this->_dbConn, $database . "." . "tblprojects", "project_name", "project_id")
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }
}
