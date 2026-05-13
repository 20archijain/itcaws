<?php

// phpcs:ignore
class DBConnection
{
    private $_db;
    private $_logFileName = "debug_pdo_error_log";

    // phpcs:ignore
    public function __construct($dbname, $username, $password, $servername = DB_HOSTNAME)
    {
        try {
            $this->_db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errorMessage = responseMessage(["Connection Error: " . $e->getMessage()]);
            echo json_encode($errorMessage);
            return;
        }
    }

    // phpcs:ignore
    final public function ExecuteSelectQuery($sql_statement, &$result_resource, &$returned_rows_count, $arrParams = [])
    {
        try {
            $result_resource = $this->_db->prepare($sql_statement);
            $result_resource->execute($arrParams);
            $returned_rows_count = $result_resource->rowCount();
            return 1;
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            debug_log("ExecuteSelectQuery\r\n$sql_statement\r\n" . json_encode($arrParams) . "\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(), $this->_logFileName);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function ExecuteQuery($sql_statement, &$result_resource, &$returned_rows_count, $arrParams = [])
    {
        try {
            $result_resource = $this->_db->prepare($sql_statement);
            if (isNonEmptyArray($arrParams)) {
                $i = 1;
                foreach ($arrParams as $key => $param) {
                    $result_resource->bindParam($i, $$key);
                    $$key = $param;
                    $i++;
                }
            }
            $result_resource->execute();
            $returned_rows_count = $result_resource->rowCount();
            return 1;
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            debug_log("ExecuteQuery\r\n$sql_statement\r\n" . json_encode($arrParams) . "\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(), $this->_logFileName);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function GetData($result_resource, $type = PDO::FETCH_ASSOC)
    {
        try {
            $rowCount = $result_resource->rowCount();
            if ($rowCount > 0) {
                return $result_resource->fetch($type);
            }
            return 0;
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            debug_log("GetData\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(), $this->_logFileName);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function GetLastInsertId(&$last_insert_id)
    {
        try {
            $last_insert_id = $this->_db->lastInsertId();
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function BeginTransaction()
    {
        try {
            $this->_db->beginTransaction();
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function CommitTransaction()
    {
        try {
            $this->_db->commit();
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function RollbackTransaction()
    {
        try {
            $this->_db->rollBack();
        } catch (PDOException $e) {
            $error_message = responseMessage([$e->getMessage()]);
            echo json_encode($error_message);
            return;
        }
    }

    // phpcs:ignore
    final public function Close()
    {
        $this->_db = null;
    }
}

$dbConn = new DBConnection($DB_DBNAME, $DB_USERNAME, $DB_PASSWORD);
