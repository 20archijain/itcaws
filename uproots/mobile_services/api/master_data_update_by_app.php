<?php

// Used in ITC cig (itc2)

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// phpcs:ignore
class UpdateMasterData extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_master_data_update_by_app";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/master_data_update_by_app");
    }

    private function validateData()
    {
        return $this->commonFunctions->isNonEmptyArray($this->requestPostData) &&
            isset($this->requestPostData["shopId"]) && $this->requestPostData["shopId"];
    }

    private function updateData()
    {
        $dbName = $this->arrUserDetails["db_name"];

        $shopName = isset($this->requestPostData["shopName"]) ? htmlentities($this->requestPostData["shopName"]) : "";
        $mobileNo = isset($this->requestPostData["mobileNo"]) ? htmlentities($this->requestPostData["mobileNo"]) : "";
        $shopId = isset($this->requestPostData["shopId"]) ? htmlentities($this->requestPostData["shopId"]) : "";
        $lt = isset($this->requestPostData["lt"]) && $this->requestPostData["lt"] !== "" ? (float) $this->requestPostData["lt"] : 0;
        $lg = isset($this->requestPostData["lg"]) && $this->requestPostData["lg"] !== "" ? (float) $this->requestPostData["lg"] : 0;


        $iStatus = $this->tableUtil->isRecordExist("$dbName.tblroute_details", "rec_id", "dstatus = 0 AND outlet_mobile = $mobileNo AND rec_id != $shopId");
        if ($iStatus) {
            // Data is invalid
            $response = $this->response->sendResponse(array("message" => $this->arrOTPMessages["OTP22"]));
        } else {
            // Valid shop
            if (is_numeric($shopId)) {
                // Update route table with new shop name and mobile number
                $updateResult = $this->tableUtil->updateRecord(
                    "$dbName.tblroute_details",
                    "outlet_name = ?, outlet_mobile = ?, lt = ?, lg = ?, modifiedbyapp = 1",
                    "rec_id = $shopId",
                    array($shopName, $mobileNo, $lt, $lg)
                );

                // Data updated successfully
                if ($updateResult > 0) {
                    $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA05"]), 1);
                } else {
                    // Data not updated
                    $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA06"]));
                }
            } else {
                // Data is invalid
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA04"]));
            }
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function updateMasterData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->updateData();
            } else {
                // Data cannot be empty
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$data = new UpdateMasterData($dbConn, $tableUtil, $commonFunctions);
$data->updateMasterData();
$dbConn->Close();
