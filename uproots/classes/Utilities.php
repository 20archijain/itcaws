<?php

// phpcs:ignore
class Utilities
{
    protected $dbConn;
    protected $tableUtil;
    protected $commonFunctions;
    protected $currentDate;
    protected $currentDateTime;
    protected $logFileName;
    protected $logFolderName;
    protected $sToken;
    protected $response;
    protected $logResponse = array();
    protected $requestPostData;
    protected $requestGetData;
    protected $requestFiles;
    protected $arrAuthMessages;
    protected $arrOTPMessages;
    protected $arrBreakMessages;
    protected $arrCustomMessages;
    protected $arrDBDeclarationDetails;
    protected $arrDBLoginViaOtpDetails;
    protected $arrDBProjectDetails;
    protected $arrSummaryLabels;

    public function __construct(
        $dbConn,
        $tableUtil,
        $commonFunctions,
        $logFileName = "log",
        $logFolderName = "",
        $log = false
    ) {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->currentDate = $this->commonFunctions->currentDate();
        $this->currentDateTime = $this->commonFunctions->currentDateTime();
        $this->logFileName = $logFileName;
        $this->logFolderName = $logFolderName;
        $this->sToken = $this->getToken();
        $this->response = new Response();

        $this->logResponse = array(
            "log" => $log,
            "fileName" => $this->logFileName,
            "folderName" => $this->logFolderName,
        );

        $this->getAndSetGlobalVariables();
        $this->getRequestFilesAndData();
    }

    private function getAndSetGlobalVariables()
    {
        $this->arrAuthMessages = isset($GLOBALS["arrAuthMessages"]) ? $GLOBALS["arrAuthMessages"] : array();
        $this->arrOTPMessages = isset($GLOBALS["arrOTPMessages"]) ? $GLOBALS["arrOTPMessages"] : array();
        $this->arrBreakMessages = isset($GLOBALS["arrBreakMessages"]) ? $GLOBALS["arrBreakMessages"] : array();
        $this->arrCustomMessages = isset($GLOBALS["arrCustomMessages"]) ? $GLOBALS["arrCustomMessages"] : array();
        $this->arrDBDeclarationDetails = isset($GLOBALS["arrDBDeclarationDetails"]) ? $GLOBALS["arrDBDeclarationDetails"] : array();
        $this->arrDBLoginViaOtpDetails = isset($GLOBALS["arrDBLoginViaOtpDetails"]) ? $GLOBALS["arrDBLoginViaOtpDetails"] : array();
        $this->arrDBProjectDetails = isset($GLOBALS["arrDBProjectDetails"]) ? $GLOBALS["arrDBProjectDetails"] : array();
        $this->arrSummaryLabels = isset($GLOBALS["arrSummaryLabels"]) ? $GLOBALS["arrSummaryLabels"] : array();
    }

    private function getRequestFilesAndData()
    {
        $this->requestFiles = isset($_FILES) && $_FILES ? $_FILES : array();

        if ($this->requestFiles && $this->commonFunctions->isNonEmptyArray($this->requestFiles)) {
            $this->requestPostData = $_POST;
        } else {
            $requestData = file_get_contents("php://input");
            if (isset($requestData) && $requestData) {
                if (is_array($requestData)) {
                    $this->requestPostData = $requestData;
                } else {
                    $this->requestPostData = json_decode($requestData, true);
                }
            } elseif (isset($_POST) && $this->commonFunctions->isNonEmptyArray($_POST)) {
                $this->requestPostData = $_POST;
            }

            if (isset($_GET) && $this->commonFunctions->isNonEmptyArray($_GET)) {
                $this->requestGetData = $_GET;
            }
        }
    }

    protected function setLogFileName($logFileName)
    {
        $this->logFileName = $logFileName;
    }

    protected function getToken()
    {
        $sToken = "";
        if (
            isset($_SERVER["PHP_AUTH_PW"]) && $_SERVER["PHP_AUTH_PW"] &&
            $_SERVER["PHP_AUTH_USER"] && $_SERVER["PHP_AUTH_PW"] === $_SERVER["PHP_AUTH_USER"]
        ) {
            $sToken = $_SERVER["PHP_AUTH_PW"];
        }

        if (
            !$sToken && isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]) && $_SERVER["REDIRECT_HTTP_AUTHORIZATION"]
            && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)
        ) {
            list($name, $password) = explode(':', base64_decode($matches[1]));
            $sToken = strip_tags($password);
        }

        return $sToken;
    }

    protected function getLoginUserDetails($sToken)
    {
        $login = new AppLogin($this->dbConn, $this->tableUtil, $this->commonFunctions);
        $arrLoginUser = $login->getUserFromToken($sToken);

        // Token not found
        if ($arrLoginUser === false) {
            // Unauthorized access
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH04"]));
            $this->logOutput($response);
            return array();
        }

        // User found
        if ($this->commonFunctions->isNonEmptyArray($arrLoginUser)) {
            return $arrLoginUser;
        } else {
            // Unauthorized phone
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["AUTH06"]));
            $this->logOutput($response);
            return array();
        }
    }

    final public function log($stringData, $logRequestPostPayload = true, $logRequestGetPayload = true, $logRequestHeaders = true, $logRequestInfo = true)
    {
        $this->commonFunctions->debugLog(
            "\r\nSERVER LOG DATE TIME: " . $this->currentDateTime .
                " Token: " . $this->getToken() .
                ($logRequestPostPayload ? "\r\nREQUEST POST PAYLOAD: " . json_encode($this->requestPostData) : "") .
                ($logRequestGetPayload ? "\r\nREQUEST GET PAYLOAD: " . json_encode($this->requestGetData) : "") .
                ($logRequestHeaders ? "\r\nREQUEST HEADERS: " . json_encode(getallheaders()) : "") .
                ($logRequestInfo ? "\r\nREQUEST INFO: " . json_encode($_SERVER) : "") .
                "\r\n" . $stringData,
            $this->logFileName,
            $this->logFolderName
        );
    }

    final public function logOutput($sResponse, $sExtraLogData = "")
    {
        $logData = "RESPONSE: $sResponse";
        if ($sExtraLogData) {
            $logData .= "\r\nEXTRA: $sExtraLogData";
        }
        $this->log($logData);
    }
}
