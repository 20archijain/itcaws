<?php

// Used in ITC Phase 2 setup to get the price alert notification in WD App


// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

// phpcs:ignore
class GetNotification extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_get_notification";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"] ? true : false;
    }

    private function getNotification()
    {
        global $ITCPH2_DB, $TBL_PROJECT_TEAM, $TBL_NOTIFICATION;

        $currentDate = $this->commonFunctions->currentDate();

        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];
        // $jsonId = $this->requestGetData["json_id"];

        $branchId = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "branch_id",
                "team_id = $teamId"
            ) : null;


        $wdCode = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "wd_code",
                "team_id = $teamId"
            ) : null;
        $jsonId = ($dbName == $ITCPH2_DB) ?
            $this->tableUtil->getRowColumn(
                "$dbName.$TBL_PROJECT_TEAM",
                "s_id",
                "team_id = $teamId"
            ) : null;
        // Branch found
        if ($branchId) {
            if ($dbName == $ITCPH2_DB) {
                $allNotifications = array();
                if ($jsonId == 100) {
                    $allNotifications = $this->tableUtil->getRowsColumns(
                        "$dbName.$TBL_NOTIFICATION",
                        "rec_id, rcd, product_name, old_net_rate, new_net_rate",
                        "dstatus = 0 AND wd_code = '$wdCode' AND is_branch_update = '0' ORDER BY rcd DESC"
                    );
                    // Get notifications for the current date
                    $currentDateNotifications = $this->tableUtil->getRowsColumns(
                        "$dbName.$TBL_NOTIFICATION",
                        "product_name, old_net_rate, new_net_rate, rcd",
                        "dstatus = 0 AND wd_code = '$wdCode' AND is_branch_update = '0' AND rcd = '$currentDate'"
                    );
                } elseif ($jsonId == 101) {
                    $allNotifications = $this->tableUtil->getRowsColumns(
                        "$dbName.$TBL_NOTIFICATION",
                        "rec_id, rcd, product_name, old_net_rate, new_net_rate",
                        "dstatus = 0 AND branch_id = $branchId AND is_branch_update = '1' ORDER BY rcd DESC"
                    );
                    // Get notifications for the current date
                    $currentDateNotifications = $this->tableUtil->getRowsColumns(
                        "$dbName.$TBL_NOTIFICATION",
                        "product_name, old_net_rate, new_net_rate, rcd",
                        "dstatus = 0 AND branch_id = $branchId AND is_branch_update = '1' AND rcd = '$currentDate'"
                    );
                }

                // Group notifications by date
                $dateGroupedNotifications = [];
                foreach ($allNotifications as $notification) {
                    $date = $notification[1];
                    $dateGroupedNotifications[$date][] = $notification;
                }

                $arrInappNotifications = [];
                foreach ($dateGroupedNotifications as $date => $notifications) {
                    $productRows = '';

                    // Build rows for all products on this date
                    foreach ($notifications as $notification) {
                        $productName = $notification[2];
                        $oldRate = $notification[3];
                        $newRate = $notification[4];
                        $productRows .= "<tr><td>{$productName}</td><td>{$oldRate}</td><td>{$newRate}</td></tr>";
                    }

                    // Create a single notification entry for each date with all products listed
                    $arrInappNotifications[] = [
                        "notificationId" => $notifications[0][0],  // Use the first rec_id for this date
                        "heading" => "Price Change Alert",
                        "subheading" => date("d-m-Y", strtotime($date)),
                        "isunread" => true,
                        // phpcs:ignore
                        "content" => "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Notification Data</title><style>body{font-family:Arial,sans-serif;background-color: #f8f9fa;display: flex;justify-content: center;align-items: center;min-height: 8vh;margin: 0;}.notification{border:1px solid #ddd;padding:20px;margin:20px 0;border-radius:8px;background-color: #ffffff;box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);max-width: 600px;width: 100%;}.product-table{width:100%;border-collapse:separate;border-spacing: 0;margin-top:10px;border-radius: 8px;overflow: hidden;}.product-table th,.product-table td{padding:10px;text-align:left;}.product-table th{background-color:#189c80;color: #ffffff;font-weight: bold;}.product-table td {background-color: #f8f9fa;border-top: 1px solid #ddd;}.product-table tr:last-child td {border-bottom: none;}</style></head><body><div class='notification'><table class='product-table'><tr><th>Item</th><th>Old Base Rate</th><th>New Base Rate</th></tr>{$productRows}</table></div></body></html>"
                    ];
                }

                $arrPopupNotification = null; // Initialize as null to exclude it from the response if no data is found

                if (!empty($currentDateNotifications)) {
                    // Build current date popup notification
                    $currProductRows = '';
                    foreach ($currentDateNotifications as $currNotification) {
                        $currProductRows .= "<tr><td>{$currNotification[0]}</td><td>{$currNotification[1]}</td><td>{$currNotification[2]}</td></tr>";
                    }

                    $arrPopupNotification = [
                        "heading" => "Price Change Alert",
                        "subheading" => date("d-m-Y", strtotime($currentDateNotifications[0][3])) ?? '',
                        // phpcs:ignore
                        "content" => "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Notification Data</title><style>body{font-family:Arial,sans-serif;background-color: #f8f9fa;display: flex;justify-content: center;align-items: center;min-height: 8vh;margin: 0;}.notification{border:1px solid #ddd;padding:20px;margin:20px 0;border-radius:8px;background-color: #ffffff;box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);max-width: 600px;width: 100%;}.product-table{width:100%;border-collapse:separate;border-spacing: 0;margin-top:10px;border-radius: 8px;overflow: hidden;}.product-table th,.product-table td{padding:10px;text-align:left;}.product-table th{background-color:#189c80;color: #ffffff;font-weight: bold;}.product-table td {background-color: #f8f9fa;border-top: 1px solid #ddd;}.product-table tr:last-child td {border-bottom: none;}</style></head><body><div class='notification'><table class='product-table'><tr><th>Item</th><th>Old Base Rate</th><th>New Base Rate</th></tr>{$currProductRows}</table></div></body></html>"
                    ];
                }

                if ($arrInappNotifications || $arrPopupNotification) {
                    if (!is_null($arrPopupNotification) || $arrPopupNotification) {
                        $arrResponse = array(
                            "inapp_notifications" => $arrInappNotifications,
                            "popup_notification" => $arrPopupNotification,
                        );
                    } else {
                        $arrResponse = array(
                            "inapp_notifications" => $arrInappNotifications
                        );
                    }

                    $response = $this->response->sendResponse(array("message" => "", "response" => $arrResponse), 1);
                    $this->logOutput($response, $this->sExtraLogData);
                } else {
                    // No data found
                    $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST05"]));
                    $this->logOutput($response, $this->sExtraLogData);
                }
            } else {
                // API is not applicable
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST04"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        } else {
            // Branch not found
            $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST03"]));
            $this->logOutput($response, $this->sExtraLogData);
        }
    }

    final public function getAlert()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData($dbName)) {
                $this->getNotification();
            } else {
                // JSON ID is missing
                $response = $this->response->sendResponse(array("message" => $this->arrCustomMessages["CUST01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new GetNotification($dbConn, $tableUtil, $commonFunctions);
$stock->getAlert();
$dbConn->Close();
