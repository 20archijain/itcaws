<?php

// Track PDF Access for Survey Reports

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class TrackPDFAccess extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_track_pdf_access";
    private $sExtraLogData;
    protected $requestGetData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["sid"]) && $this->requestGetData["sid"] && isset($this->requestGetData["url"]) && $this->requestGetData["url"];
    }

    private function trackAccess()
    {
        $summaryId = (int)$this->requestGetData["sid"];
        $pdfUrl = $this->requestGetData["url"];

        if (empty($summaryId) || empty($pdfUrl)) {
            header("HTTP/1.1 400 Bad Request");
            exit("Invalid parameters");
        }

        // Get summary details using summary table name
        global $TBL_VANDS_SUMMARY, $ITCPH2_DB;
        $summaryTable = "$ITCPH2_DB." . ($TBL_VANDS_SUMMARY ?? 'tblvands_summary');
        $summaryQuery = "SELECT team_id FROM $summaryTable WHERE summary_id = $summaryId AND dstatus = 0";
        $summaryAction = null;
        $summaryRows = 0;

        try {
            $this->dbConn->ExecuteSelectQuery($summaryQuery, $summaryAction, $summaryRows);
        } catch (Exception $e) {
            error_log("Summary query failed: " . $e->getMessage() . " | Query: " . $summaryQuery);
            header("HTTP/1.1 500 Internal Server Error");
            exit("Database query failed for summary lookup. Check logs for details.");
        }

        if ($summaryRows === 0) {
            header("HTTP/1.1 404 Not Found");
            exit("Summary not found");
        }

        $summaryData = $this->dbConn->GetData($summaryAction);
        $teamId = $summaryData['team_id'] ?? 0;
        if (empty($teamId)) {
            header("HTTP/1.1 404 Not Found");
            exit("Invalid team ID in summary");
        }

        // Log the access using tableUtil
        $accessDatetime = date("Y-m-d H:i:s");
        $accessDate = date("Y-m-d");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $logTable = "$ITCPH2_DB.tblpdf_access_log";
        $cols = "summary_id, team_id, pdf_url, access_datetime, ip_address, user_agent, rcd, rdt";
        $vals = "?, ?, ?, ?, ?, ?, ?, ?";
        $params = array(
            $summaryId,
            $teamId,
            $pdfUrl,
            $accessDatetime,
            $ipAddress,
            $userAgent,
            $accessDate,
            $accessDatetime
        );

        try {
            $status = $this->tableUtil->addRecord($logTable, $cols, $vals, $params);
            if ($status !== 1) {
                error_log("Failed to log PDF access: Insert status = $status");
            }
        } catch (Exception $e) {
            error_log("PDF tracking insert failed: " . $e->getMessage());
        }

        // Redirect to actual PDF
        header("Location: " . $pdfUrl);
        exit();
    }

    final public function processAccess()
    {
        // No auth needed for tracking, so skip login and directly process
        $this->setLogFileName($this->localLogFileName);

        if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
            $this->requestGetData = $_GET;
        }

        if ($this->validateData()) {
            $this->trackAccess();
        } else {
            header("HTTP/1.1 400 Bad Request");
            exit("Invalid parameters");
        }
    }
}

$tracker = new TrackPDFAccess($dbConn, $tableUtil, $commonFunctions);
$tracker->processAccess();
$dbConn->Close();
