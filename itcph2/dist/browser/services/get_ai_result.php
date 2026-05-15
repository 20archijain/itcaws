<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class GetAIResult
{
    private $dbConn = null;
    private $tables = [];
    private $logFilename;

    public function __construct($dbConn, $logFilename = "")
    {
        $this->dbConn = $dbConn;
        $this->tables = $GLOBALS['TABLES'];
        $this->logFilename = $logFilename ? $logFilename : "log_GetAIResult";
    }

    final public function getResult($date = "", $cond = "", $limit = 25)
    {
        $date = $date ? $date : currentDate();
        $dateCond = "AND a.capture_date = '$date'";

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.pro_id, a.capture_date, a.capture_datetime, b.file_domain, b.file_path, b.file_name FROM tblsurvey_response_details AS a, tblsurvey_response_file_new AS b WHERE a.dstatus = 0" .
            " AND a.uni_id = b.uni_id AND a.ques_8 = b.mob_img_id AND a.is_ai_result_processed = 0 AND a.team_id IN (SELECT team_id FROM tblproject_team WHERE branch_id = 13 AND dstatus = 0) $dateCond $cond ORDER BY a.pro_id DESC LIMIT $limit";
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                $proId = $row["pro_id"];

                $arrData = [
                    // "https://itccampaign.com/13.jpg"
                    "url" => $row["file_domain"] . "/prods/any" . $row["file_path"] . $row["file_name"],
                    "event_id" => 77,
                    "seq" => "default"
                ];
                $sAiResult = $this->callApi($row["pro_id"], "http://147.189.195.124:5000/api/yolo", $arrData);

                $arrGetProcessedResult = $this->getProcessedResult($sAiResult);

                updateRecord(
                    $this->dbConn,
                    "tblsurvey_response_details",
                    "is_ai_result_processed = 1, ai_result_org = ?, ai_result = ?",
                    "pro_id = $proId",
                    [$sAiResult, $arrGetProcessedResult ? json_encode($arrGetProcessedResult) : null]
                );
            }
        }
    }

    private function getProcessedResult($sAiResult)
    {
        $arrMaterial = [
            "888" => "Gold Flake Superstar",
            "999" => "Wills Flake Special Filter",
        ];

        $arrResult = null;
        if ($sAiResult) {
            $arrAiResult = json_decode($sAiResult, true);

            if (
                $arrAiResult && isset($arrAiResult["packet_Counts"]) &&
                isNonEmptyArray($arrAiResult["packet_Counts"])
            ) {
                $arrResult = [];
                foreach ($arrAiResult["packet_Counts"] as $matId => $matAiDetectedQty) {
                    $arrResult[] = [
                        "matId" => $matId,
                        "matName" => $arrMaterial[$matId],
                        "ai_detected_qty" => $matAiDetectedQty,
                    ];
                }
            }
        }

        return $arrResult;
    }

    private function callApi($resp_id, $url, $arrData)
    {
        $currentDateTime = date("Y-m-d H:i:s:v");

        // Initialize a cURL session
        $ch = curl_init();
        $data = json_encode($arrData);

        $username = "bat-portal";
        $password = "access@granted";
        $arrHeaders = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$username:$password")
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1200);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        debug_log(
            "\r\nServer Datetime: $currentDateTime" .
                " Resp ID: $resp_id\r\nUrl: $url\r\nParams:$data",
            $this->logFilename
        );

        // Set the request URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //get response
        $output = curl_exec($ch);

        // get information of result and Check if any error occurred
        $arrInfo = curl_getinfo($ch);

        debug_log("\r\nResp ID: $resp_id\r\nOutput: $output\r\nRequest Info: " .
            ($arrInfo && is_array($arrInfo) ? json_encode($arrInfo) : $arrInfo) .
            "\r\nError: " . curl_error($ch) . "\r\nError No: " . curl_errno($ch), $this->logFilename);
        // close cURL resource, and free up system resources
        curl_close($ch);

        // all well
        if ($arrInfo && $arrInfo["http_code"] == 200) {
            return $output;
        }
        return null;
    }
}

$getAIResult = new GetAIResult($dbConn);
$getAIResult->getResult();
