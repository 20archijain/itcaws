<?php

// Used to update FCM token for a team in class-based setup

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class UpdateFCMToken extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_update_fcm_token";
    private $sExtraLogData;
    protected $requestGetData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function updateToken()
    {

        $rawInput = file_get_contents("php://input");
        $postData = json_decode($rawInput, true);
        $fcmToken = isset($postData['fcm_token']) ? trim($postData['fcm_token']) : '';
        $appVersion = isset($postData['appVersion']) ? trim($postData['appVersion']) : '';
        $versionCode = isset($postData['versionCode']) ? trim($postData['versionCode']) : '';
        $appType = isset($postData['appType']) ? trim($postData['appType']) : '';
        $deviceInfo = isset($postData['deviceInfo']) ? trim($postData['deviceInfo']) : '';
        $lastLogout = isset($postData['lastLogout']) ? trim($postData['lastLogout']) : '';
        $suspesiousScore = isset($postData['suspesiousScore']) ? trim($postData['suspesiousScore']) : '';

        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];

        // Step 1: Check existing token
        $sCheckTokenQuery = "SELECT fcm_token FROM {$dbName}.tblproject_team WHERE team_id = ? AND dstatus = 0 LIMIT 1";
        $sAction = null;
        $iRows = 0;
        $this->dbConn->ExecuteSelectQuery($sCheckTokenQuery, $sAction, $iRows, array($teamId));

        if ($iRows === 1) {
            $row = $this->dbConn->GetData($sAction);
            $existingToken = $row['fcm_token'];

            if ($existingToken === $fcmToken) {
                $response = $this->response->sendResponse(["message" => "FCM token already up to date"], 1);
                $this->logOutput($response, $this->sExtraLogData);
                return;
            }
        }

        // Step 2: Update data only if different
        $vals = "fcm_token = ?, app_version = ?, version_code = ?, app_type = ?, device_info = ?, last_logout = ?, suspesious_score = ?";
        $arrParams = array($fcmToken, $appVersion, $versionCode, $appType, $deviceInfo, $lastLogout, $suspesiousScore);
        $iStatus = $this->tableUtil->updateRecord("{$dbName}.tblproject_team", $vals, "team_id = $teamId AND dstatus = 0", $arrParams);

        if ($iStatus) {
            $response = $this->response->sendResponse(["message" => "FCM token updated successfully"], 1);
            $this->logOutput($response, $this->sExtraLogData);
        } else {
            $response = $this->response->sendResponse(["message" => "Failed to update FCM token"], 0);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    final public function updateFCMTokenMain()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateToken();
            } else {
                $response = $this->response->sendResponse(["message" => "Invalid fcm_token provided or method not allowed"], 0);
                $this->logOutput($response, $this->sExtraLogData);
            }
        } else {
            $response = $this->response->sendResponse(["message" => $this->arrAuthMessages["AUTH04"] ?? "Unauthorized access"], 0);
            // Log to unauthorised if needed
            $this->setLogFileName("log_update_fcm_token_unauthorised_access");
            $this->logOutput($response, "");
        }
    }
}
$fcm = new UpdateFCMToken($dbConn, $tableUtil, $commonFunctions);
$fcm->updateFCMTokenMain();
$dbConn->Close();
