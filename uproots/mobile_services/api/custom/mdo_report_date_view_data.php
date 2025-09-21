<?php

$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
$currentDate = $commonFunctions->currentDate();

class GetMDOSummary extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_mdo_report_date_view_data";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/custom");
    }

    private function validateData()
    {
        return isset($this->requestGetData["json_id"]) && $this->requestGetData["json_id"];
    }

    private function getMdo()
    {
        global $TBL_ATTENDANCE;

        $jsondata = file_get_contents("php://input");
        $jsondata = json_decode($jsondata, true);
        $dbName = $this->arrUserDetails["db_name"];
        $teamId = $this->arrUserDetails["team_id"];
        // Calculate date 30 days ago
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $sQuery = "SELECT capture_date, other_details FROM (SELECT capture_date, other_details,ROW_NUMBER() OVER (PARTITION BY capture_date ORDER BY capture_datetime ASC) as rn FROM $dbName.$TBL_ATTENDANCE WHERE dstatus = 0" .
            " AND team_id = '$teamId' AND capture_date >= '$thirtyDaysAgo') ranked WHERE rn = 1 ORDER BY capture_date DESC";
        $sAction = null;
        $iRows = 0;
        $dateViewItemList = [];
        $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        if ($iRows > 0) {
            while ($row = $this->dbConn->GetData($sAction)) {
                // Decode other_details JSON
                $otherDetails = json_decode($row['other_details'], true);
                $workingWith = isset($otherDetails['workingWith']) ? $otherDetails['workingWith'] : '';
                $selectRoute = isset($otherDetails['selectRouteYouAreGoingOn']) ? $otherDetails['selectRouteYouAreGoingOn'] : [];

                // Extract fields
                $wdCode = isset($selectRoute[0]) ? $selectRoute[0] : 'Unknown';
                $dsName = isset($selectRoute[1]) ? $selectRoute[1] : 'Unknown';
                $routeName = isset($selectRoute[2]) ? $selectRoute[2] : 'Unknown';

                // Derive DS Type from DS Name (e.g., "RK AGARWAL - VAN DS" -> "Van Ds")
                $dsType = 'Unknown';
                if (strpos($dsName, ' - ') !== false) {
                    $dsType = trim(explode(' - ', $dsName)[1]);
                }

                // Initialize item for dateViewItemList
                $item = [
                    'fields' => [
                        [
                            'label' => 'Date',
                            'value' => $row['capture_date']
                        ],
                        [
                            'label' => 'WD Code',
                            'value' => $wdCode
                        ],
                        [
                            'label' => 'Type of Market Work',
                            'value' => $workingWith
                        ]
                    ]
                ];

                // Add Info array if workingWith is "Market work with DS"
                if ($workingWith === 'Market work with DS') {
                    $item['Info'] = [
                        [
                            'label' => 'DS Name',
                            'value' => explode(' - ', $dsName)[0] // Get name before hyphen
                        ],
                        [
                            'label' => 'DS Type',
                            'value' => $dsType
                        ],
                        [
                            'label' => 'Route Name',
                            'value' => $routeName
                        ]
                    ];
                }

                $dateViewItemList[] = $item;
            }

            $responseArr = [["dateViewItemList" => $dateViewItemList]];
            $response = $this->response->sendResponse(["message" => "", "response" => $responseArr], 1);
            $this->logOutput($response, $this->sExtraLogData);
        } else {
            $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST05"]]);
            $this->logOutput($response, $this->sExtraLogData);
        }
    }


    final public function getMdoData()
    {
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);
        // Directly call our method for testing purpose
        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->getMdo();
            } else {
                $response = $this->response->sendResponse(["message" => $this->arrCustomMessages["CUST01"]]);
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$stock = new GetMDOSummary($dbConn, $tableUtil, $commonFunctions);
$stock->getMdoData();
$dbConn->Close();
