<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once "../class/AppSummary.php";
require_once $CLASSES_PATH . "/AppLogin.php";

// Format
// $staticSummary = [
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100",
//                     "textColor" => "#ff0000"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Login summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => "fa fa-bath"
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => "fa fa-snowflake-o"
//                 )
//             ],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => null
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ],
//                     [
//                         "Product 4",
//                         "101",
//                         "1",
//                         "101"
//                     ]
//                 ]
//             )
//         )
//     ),
//     array(
//         "summaryTitle" => "Attendance summary",
//         "summaryData" => array(
//             "labelList" => [
//                 array(
//                     "label" => "Total People",
//                     "value" => "100"
//                 ),
//                 array(
//                     "label" => "Today Present",
//                     "value" => "90"
//                 )
//             ],
//             "cardList" => [
//                 array(
//                     "label" => "Present Today",
//                     "value" => "Yes",
//                     "icon" => ""
//                 ),
//                 array(
//                     "label" => "Present Yesterday",
//                     "value" => "Yes",
//                     "icon" => null
//                 )
//             ],
//             "tableData" => array(
//                 "header" => [
//                     "Product Name",
//                     "Qyt",
//                     "Rate",
//                     "Amount"
//                 ],
//                 "body" => [
//                     [
//                         "Product 1",
//                         "100",
//                         "50",
//                         "1000"
//                     ],
//                     [
//                         "Product 2",
//                         "10",
//                         "5",
//                         "100"
//                     ],
//                     [
//                         "Product 3",
//                         "1001",
//                         "51",
//                         "1100"
//                     ]
//                 ]
//             )
//         )
//     )
// ];

// phpcs:ignore
class GetHomeScreenData extends AppSummary
{
    private $appType = 2;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, "log_home_screen_data");
    }

    private function outputSummary($arrSummary = array(), $arrCustomResp = array(), $arrTopSummary = array())
    {
        // No Summary to display
        if (!$this->commonFunctions->isNonEmptyArray($arrSummary)) {
            $arrSummary = $this->getDefaultSummary($this->appType);
        }

        $arrSummaryList = array(
            // Display $arrTopSummary summary first and then $arrSummary
            "summaryList" => array_merge($arrTopSummary, $arrSummary),
        );

        // Add app and json min version
        $arrCustomResp = array_merge($arrCustomResp, $this->getAppAndJsonMinVersion());

        $response = $this->response->sendResponse(
            array("message" => "", "response" => $arrSummaryList),
            1,
            $arrCustomResp
        );
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function getHomeScreenData()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $clientId = $this->arrUserDetails["client_id"];
            $projectId = $this->arrUserDetails["project_id"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: $clientId Project ID: $projectId Team ID: $teamId";

            // Check whether to show summary
            if ($this->isSummaryVisible($dbName, $clientId, $projectId)) {
                list($arrSummary, $arrCustomResp, $arrTopSummary) = $this->getSummary($this->appType);
                $this->outputSummary($arrSummary, $arrCustomResp, $arrTopSummary);
            } else {
                $this->outputSummary();
            }
        }
    }
}

$homeScreenData = new GetHomeScreenData($dbConn, $tableUtil, $commonFunctions);
$homeScreenData->getHomeScreenData();
$dbConn->Close();
