<?php

// Used to fetch notification details in class-based setup

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetNotifications extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_ds_app_notifications";
    private $sExtraLogData;
    protected $requestGetData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function fetchNotifications()
    {
        $dbName = $this->arrUserDetails["db_name"];
        $dbTeam = $this->arrUserDetails["team_id"];

        $notificationDetails = null;
        $notificationRows = 0;
        $notificationQuery = "SELECT notification_id, team_id, notification_type, notification_title, notification_text, notification_datetime
                             FROM {$dbName}.tblapp_notification WHERE dstatus = 0 AND team_id = $dbTeam ORDER BY notification_datetime DESC";
        $this->dbConn->ExecuteSelectQuery($notificationQuery, $notificationDetails, $notificationRows);

        $finalNotifications = [];
        while ($row = $this->dbConn->GetData($notificationDetails)) {
            $notificationDateTime = $row['notification_datetime'];
            $timestampInMillis = strtotime($notificationDateTime) * 1000;
            // Assign color based on notification type (you can customize this)
            $color = "#000000"; // default black
            switch ((int)$row['notification_type']) {
                case 0:
                    $color = "#0000FF";
                    break;
                case 1:
                    $color = "#FF0000";
                    break;
            }
            $finalNotifications[] = [
                'notificationId' => (string)$row['notification_id'],
                'notificationType' => (string)$row['notification_type'],
                'teamId' => (string)$row['team_id'],
                'notificationTitle' => $row['notification_title'],
                'notificationText' => $row['notification_text'],
                'notificationDateTime' => $row['notification_datetime'],
                'dateTimeMili' => $timestampInMillis,
                'color' => $color
            ];
        }

        $arrResponse = [
            "notificationDetails" => $finalNotifications
        ];

        $response = $this->response->sendResponse(["message" => "", "response" => $arrResponse], 1);
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getNotificationsMain()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            $this->fetchNotifications();
        } else {
            $this->setLogFileName("log_get_ds_app_notifications_unauthorised_access");
            $response = $this->response->sendResponse(["message" => $this->arrAuthMessages["AUTH04"] ?? "Unauthorized access"], 0);
            $this->logOutput($response, "");
        }
    }
}
$notifications = new GetNotifications($dbConn, $tableUtil, $commonFunctions);
$notifications->getNotificationsMain();
$dbConn->Close();
