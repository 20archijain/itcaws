<?php

// phpcs:ignore
class DBConnection
{
    private $db;
    private $commonFunctions;
    private $response;
    private $logFileName = "debug_pdo_error_log";

    // phpcs:ignore
    public function __construct(
        $dbname,
        $username,
        $password,
        $commonFunctions,
        $sendWebApiResponse = false,
        $servername = DB_HOSTNAME
    ) {
        $this->commonFunctions = $commonFunctions;
        $this->response = new Response($sendWebApiResponse);
        try {
            $this->db = new PDO(
                "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (__construct)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "Connection Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function ExecuteSelectQuery($sql_statement, &$result_resource, &$returned_rows_count, $arrParams = array())
    {
        try {
            $result_resource = $this->db->prepare($sql_statement);
            $result_resource->execute($arrParams);
            $returned_rows_count = $result_resource->rowCount();
            return 1;
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (ExecuteSelectQuery)\r\n$sql_statement\r\n" . json_encode($arrParams) .
                    "\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString() . "\r\nToken: " .
                    (isset($GLOBALS["sToken"]) ? $GLOBALS["sToken"] : ""),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "E.S.Q Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function ExecuteQuery($sql_statement, &$result_resource, &$returned_rows_count, $arrParams = array())
    {
        try {
            $result_resource = $this->db->prepare($sql_statement);
            if ($this->commonFunctions->isNonEmptyArray($arrParams)) {
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
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (ExecuteQuery)\r\n$sql_statement\r\n" . json_encode($arrParams) .
                    "\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "E.Q Error"));
            die;
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
            return $rowCount;
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (GetData)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "G.D Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function GetLastInsertId(&$last_insert_id)
    {
        try {
            $last_insert_id = $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (GetLastInsertId)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "G.L.I.D Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function BeginTransaction()
    {
        try {
            $this->db->beginTransaction();
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (BeginTransaction)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "B.T Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function CommitTransaction()
    {
        try {
            $this->db->commit();
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (CommitTransaction)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "C.T Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function RollbackTransaction()
    {
        try {
            $this->db->rollBack();
        } catch (PDOException $e) {
            $this->commonFunctions->debugLog(
                "\r\nConnection Error (RollbackTransaction)\r\n" . $e->getMessage() . "\r\n" . $e->getTraceAsString(),
                $this->logFileName
            );
            $this->response->sendResponse(array("message" => "R.T Error"));
            die;
        }
    }

    // phpcs:ignore
    final public function Close()
    {
        if (is_resource($this->db)) {
            $this->db = null;
        }
    }
}
