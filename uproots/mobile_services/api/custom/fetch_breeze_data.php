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
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

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

    /** Max records per request; reject if exceeded to protect DB */
    private const MAX_RECORDS_PER_REQUEST = 500;
    /** Max 1 call per minute per token */
    private const RATE_LIMIT_PER_MINUTE = 1;
    /** Max 50 calls per hour per token */
    private const RATE_LIMIT_PER_HOUR = 50;
    /** Max length per field value to prevent oversized payloads */
    private const MAX_FIELD_LENGTH = 500;

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
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/breeze");
    }

    /**
     * Resolve API token from Authorization (Basic username / Bearer) or body.
     * Uses multiple fallbacks so auth works when the server does not pass Authorization to PHP.
     */
    private function getApiToken(): ?string
    {
        $auth = '';
        $h = getallheaders();
        $auth = $h['Authorization'] ?? $h['authorization'] ?? '';
        // Server may pass header via env set by .htaccess (REDIRECT_HTTP_AUTHORIZATION)
        if ($auth === '' && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if ($auth === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if ($auth !== '' && preg_match('/Basic\s+(\S+)/i', $auth, $m)) {
            $v = trim($m[1]);
            $d = base64_decode($v, true);
            return ($d !== false && strpos($d, ':') !== false)
                ? (trim(explode(':', $d, 2)[0]) ?: null)
                : ($v ?: null);
        }
        if ($auth !== '' && preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        // On some servers Basic auth populates PHP_AUTH_USER (username = token)
        if (!empty($_SERVER['PHP_AUTH_USER'])) {
            return trim($_SERVER['PHP_AUTH_USER']);
        }
        // Custom header fallback when Authorization is stripped by server/proxy
        $xToken = $h['X-API-Token'] ?? $h['x-api-token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if ($xToken !== '') {
            return trim($xToken);
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
     * Rate limit: 1 request per minute, 50 per hour per token (cost-weighted).
     * Returns null if OK, or error message if limited.
     * @param string $identifier Token or fallback to IP
     * @param int $cost Cost of request (default 1)
     */
    private function checkRateLimit(string $identifier, int $cost = 1): ?string
    {
        if (empty($identifier) || !is_string($identifier)) {
            $identifier = 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        $cost = max(1, $cost);
        global $LOG_PATH;
        $dir = $LOG_PATH . '/rate_limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $key = hash('sha256', $identifier);
        $file = $dir . '/breeze_' . $key . '.json';
        $now = time();
        $data = [
            'minute_ts' => $now,
            'minute_count' => 0,
            'hour_ts' => $now,
            'hour_count' => 0,
        ];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    $data = $dec;
                }
            }
        }
        if (($now - (int)($data['minute_ts'] ?? 0)) >= 60) {
            $data['minute_ts'] = $now;
            $data['minute_count'] = 0;
        }
        if (($now - (int)($data['hour_ts'] ?? 0)) >= 3600) {
            $data['hour_ts'] = $now;
            $data['hour_count'] = 0;
        }
        $minuteCount = (int)($data['minute_count'] ?? 0);
        $hourCount = (int)($data['hour_count'] ?? 0);
        if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE) {
            return 'Rate limit exceeded. Maximum ' . self::RATE_LIMIT_PER_MINUTE . ' request(s) per minute.';
        }
        if (($hourCount + $cost) > self::RATE_LIMIT_PER_HOUR) {
            return 'Rate limit exceeded. Maximum ' . self::RATE_LIMIT_PER_HOUR . ' request(s) per hour.';
        }
        $data['minute_count'] = $minuteCount + 1;
        $data['hour_count'] = $hourCount + $cost;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return null;
    }

    /**
     * Sanitize a single value for DB: scalar only, max length. Prevents harmful payloads.
     */
    private function sanitizeFieldValue($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $s = (string)$value;
        if (strlen($s) > self::MAX_FIELD_LENGTH) {
            return substr($s, 0, self::MAX_FIELD_LENGTH);
        }
        return $s;
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

        $rateLimitError = $this->checkRateLimit($token, 1);
        if ($rateLimitError !== null) {
            $this->breezeLog("RATE_LIMIT | " . $rateLimitError);
            $this->sendApiResponse(429, true, $rateLimitError, null, 'RATE_LIMIT_EXCEEDED');
            return;
        }

        $rowsToInsert = $this->parseRequestBody();

        if (empty($rowsToInsert)) {
            $this->breezeLog("MISSING_RESPONSE_DATA | Bad or empty JSON body.");
            $this->sendApiResponse(
                400,
                true,
                'No Data in Body.',
                null,
                'MISSING_RESPONSE_DATA'
            );
            return;
        }

        if (count($rowsToInsert) > self::MAX_RECORDS_PER_REQUEST) {
            $this->breezeLog("MAX_RECORDS_EXCEEDED | received=" . count($rowsToInsert) . " max=" . self::MAX_RECORDS_PER_REQUEST);
            $this->sendApiResponse(
                400,
                true,
                'Maximum ' . self::MAX_RECORDS_PER_REQUEST . ' records per request. Received ' . count($rowsToInsert) . '.',
                null,
                'MAX_RECORDS_EXCEEDED'
            );
            return;
        }

        $this->breezeLog("Body parsed: " . count($rowsToInsert) . " row(s) to insert.");

        $fullTableName = self::DB_NAME . '.' . self::RESP_TABLE;
        $insertedCount = 0;
        $errors = [];
        $hasError = false;

        $this->dbConn->BeginTransaction();

        try {
            foreach ($rowsToInsert as $idx => $row) {
                if (!is_array($row)) {
                    $errors[] = "Row $idx: not an object.";
                    $hasError = true;
                    break;
                }
                $filtered = [];
                foreach (self::$TABLE_COLUMNS as $col) {
                    $raw = array_key_exists($col, $row) ? $row[$col] : '';
                    $filtered[$col] = $this->sanitizeFieldValue($raw);
                }
                $cols = implode(', ', array_keys($filtered));
                $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
                $arrParams = array_values($filtered);
                $iStatus = $this->tableUtil->addRecord($fullTableName, $cols, $placeholders, $arrParams);
                if ($iStatus > 0) {
                    $insertedCount++;
                } else {
                    $errors[] = "Row $idx: insert failed (status=$iStatus).";
                    $hasError = true;
                    break;
                }
            }

            if ($hasError || $insertedCount !== count($rowsToInsert)) {
                $this->dbConn->RollbackTransaction();
                $errMsg = $hasError ? implode('; ', $errors) : 'Not all rows inserted.';
                $this->breezeLog("INSERT_FAILED (rollback) | total=" . count($rowsToInsert) . " | " . $errMsg);
                $this->sendApiResponse(500, true, 'No data saved. ' . ($errors[0] ?? 'Transaction rolled back due to error.'), ['errors' => $errors], 'INSERT_FAILED');
                return;
            }

            $this->dbConn->CommitTransaction();
            $this->breezeLog("INSERT_SUCCESS | inserted=$insertedCount total=" . count($rowsToInsert));
            $this->sendApiResponse(200, false, 'Data received successfully', [
                'saved' => true,
                'inserted' => $insertedCount,
                'total' => count($rowsToInsert),
            ]);
        } catch (Exception $e) {
            $this->dbConn->RollbackTransaction();
            $this->breezeLog("INSERT_FAILED (exception) | " . $e->getMessage());
            $this->sendApiResponse(500, true, 'No data saved. An error occurred.', ['errors' => [$e->getMessage()]], 'INSERT_FAILED');
        }
    }
}

$breezeApi = new FetchBreezeData($dbConn, $tableUtil, $commonFunctions);
$breezeApi->processRequest();
$dbConn->Close();
