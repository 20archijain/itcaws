<?php

/**
 * Breeze API: Breeze response data
 *
 * Endpoint (preferred):  POST .../custom/send-data
 * Legacy path:           POST .../custom/fetch_breeze_data.php
 *
 * Auth: Basic (token in username) or Bearer.
 * Body: JSON object(s) whose keys are tblbreeze_response_data column names.
 */

$I_am_req_always = "I @m req @lway$ tokeniZer";
date_default_timezone_set("Asia/Calcutta");

$includesDir ='../../includes';
require_once $includesDir . '/index.php';
require_once $CLASSES_PATH . "/AppLogin.php";

if (empty($commonFunctions) || empty($tableUtil)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'Server config: dependencies not loaded']);
    exit;
}


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, projectId');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'isError' => true,
        'msg' => 'Method not allowed. Use POST.',
        'data' => null,
        'error' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

// phpcs:ignore
class FetchBreezeData extends Utilities
{
    private const API_VALID_TOKEN = 'dTNeWF9Kx7ZQm3WJ2R8dHcY0P4VnL6TSeB5GTrGT';
    private const RESP_TABLE = 'tblbreeze_response_data';
    private const DB_NAME = 'itcawsportal_itcph2';

    private static $TABLE_COLUMNS = [
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
        'total_sale',
    ];

    private $localLogFileName = "log_fetch_breeze_data";
    private $unauthLogFileName = "log_fetch_breeze_data_unauthorised";

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/infield");
    }

    /**
     * Resolve API token from Authorization (Basic username / Bearer) or body.
     */
    private function getApiToken(): ?string
    {
        $h = getallheaders();
        $auth = $h['Authorization'] ?? $h['authorization'] ?? '';
        if (preg_match('/Basic\s+(\S+)/i', $auth, $m)) {
            $v = trim($m[1]);
            $d = base64_decode($v, true);
            return ($d !== false && strpos($d, ':') !== false)
                ? (trim(explode(':', $d, 2)[0]) ?: null)
                : ($v ?: null);
        }
        if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        $post = $this->requestPostData;
        return isset($post['token']) && $post['token'] !== '' ? trim($post['token']) : null;
    }

    private function breezeLog(string $message, bool $useUnauthFile = false): void
    {
        $file = $useUnauthFile ? $this->unauthLogFileName : $this->localLogFileName;
        $this->commonFunctions->debugLog(
            "[{$this->currentDateTime}] " . $message,
            $file,
            $this->logFolderName
        );
    }

    private function sendApiResponse(int $code, bool $isError, string $msg, $data, ?string $errorKey = null): void
    {
        http_response_code($code);
        echo json_encode([
            'status' => $isError ? 'error' : 'success',
            'isError' => $isError,
            'msg' => $msg,
            'data' => $data,
            'error' => $errorKey
        ]);
    }

    /**
     * Parse request body into array of rows (single object → one row, array of objects → multiple rows).
     * Uses $this->requestPostData set by parent from php://input.
     */
    private function parseRequestBody(): array
    {
        $decoded = $this->requestPostData;
        if (!is_array($decoded)) {
            return [];
        }
        if (isset($decoded[0]) && is_array($decoded[0])) {
            return $decoded;
        }
        return [$decoded];
    }

    /**
     * Validate token and insert rows. Entry point.
     */
    final public function processRequest(): void
    {
        $token = $this->getApiToken();
        $this->breezeLog("Request received. token_present=" . (!empty($token) ? "yes" : "no"));

        if (empty($token)) {
            $this->breezeLog("Token: (empty) | MISSING_TOKEN", true);
            $this->breezeLog("MISSING_TOKEN");
            $this->sendApiResponse(401, true, 'Unauthorized. Token is required.', null, 'MISSING_TOKEN');
            return;
        }

        if ($token !== self::API_VALID_TOKEN) {
            $this->breezeLog("Token: $token | INVALID_TOKEN", true);
            $this->breezeLog("INVALID_TOKEN");
            $this->sendApiResponse(403, true, 'Forbidden. Invalid token.', null, 'INVALID_TOKEN');
            return;
        }

        $rowsToInsert = $this->parseRequestBody();

        if (empty($rowsToInsert)) {
            $this->breezeLog("MISSING_RESPONSE_DATA | Bad or empty JSON body.");
            $this->sendApiResponse(
                400,
                true,
                'Bad request. Send JSON with keys matching table columns (single object or array of objects).',
                null,
                'MISSING_RESPONSE_DATA'
            );
            return;
        }

        $this->breezeLog("Body parsed: " . count($rowsToInsert) . " row(s) to insert.");

        $fullTableName = self::DB_NAME . '.' . self::RESP_TABLE;
        $insertedCount = 0;
        $errors = [];

        foreach ($rowsToInsert as $idx => $row) {
            if (!is_array($row)) {
                $errors[] = "Row $idx: not an object.";
                continue;
            }
            $filtered = [];
            foreach (self::$TABLE_COLUMNS as $col) {
                $filtered[$col] = array_key_exists($col, $row) ? $row[$col] : '';
            }
            $cols = implode(', ', array_keys($filtered));
            $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
            $arrParams = array_values($filtered);
            $iStatus = $this->tableUtil->addRecord($fullTableName, $cols, $placeholders, $arrParams);
            if ($iStatus > 0) {
                $insertedCount++;
            } else {
                $errors[] = "Row $idx: insert failed (status=$iStatus).";
            }
        }

        if ($insertedCount > 0) {
            $summary = "INSERT_SUCCESS | inserted=$insertedCount total=" . count($rowsToInsert);
            if (!empty($errors)) {
                $summary .= " | errors=" . implode('; ', $errors);
            }
            $this->breezeLog($summary);
            $this->sendApiResponse(200, false, 'Data received successfully', [
                'saved' => true,
                'inserted' => $insertedCount,
                'total' => count($rowsToInsert),
                'errors' => empty($errors) ? null : $errors,
            ]);
        } else {
            $this->breezeLog("INSERT_FAILED | total=" . count($rowsToInsert) . " | " . implode('; ', $errors));
            $this->sendApiResponse(500, true, 'Failed to save response.', ['errors' => $errors], 'INSERT_FAILED');
        }
    }
}

$breezeApi = new FetchBreezeData($dbConn, $tableUtil, $commonFunctions);
$breezeApi->processRequest();
$dbConn->Close();
