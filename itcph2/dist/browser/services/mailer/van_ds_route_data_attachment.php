<?php

ini_set('memory_limit', '-1');  // Unlimited memory
ini_set('max_execution_time', '1200');  // 20 minutes

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'includes/reporting_functions.php';
include_once $CLASSES_PATH . '/CommonFunctions.php';
include_once $CLASSES_PATH . '/Response.php';
include_once $CLASSES_PATH . '/DBConnection.php';
require_once $CLASSES_PATH . '/TableUtil.php';

// phpcs:ignore
class VanDsMailer
{
    private $_dbConn = null;
    private $tableUtil;
    private $commonFunctions;
    private $_tables = [];

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->_dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->_tables = $GLOBALS['TABLES'];
    }

    final public function sendMasterData()
    {
        $currentDate = $this->commonFunctions->currentDate();
        $fileName = "VanDS_RouteData_" . $currentDate . ".xlsx";
        $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        $toEmails = ["kunalrajput8218@gmail.com"]; // Specify recipients for the group
        $ccEmails = ["shiva@appilary.com"]; // CC recipients

        $respTable = getRespTable(1, 1);
        $arrTeamType = [0 => "VAN DS", 2 => "Town SWD", 5 => "NPSR"];
        $arrHeader = [
            "Rec Id",
            "DS Id",
            "DS Name",
            "DS Type",
            "Branch",
            "Region",
            "Section",
            "WD Code",
            "Week Day",
            "Route Name",
            "Outlet Name",
            "Outlet Mobile",
            "DS Mobile",
            "Shop Type",
            "Shop Unique Code",
            "Lt",
            "Lg",
            "Shop Last Visited"
        ];

        // Define groups of branch IDs
        $group1 = [1, 2, 3, 4, 5, 6, 21, 30];
        $group2 = [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, ];
        $group3 = [23, 24, 25, 26, 27, 28, 31];

        $groups = [
            'North' => $group1,
            'East' => $group2,
            'West' => $group3
        ];

        // Send emails for each group
        foreach ($groups as $groupName => $branchIds) {
            $arrData = [];  // Initialize data array for each group

            // Loop through each branch in the group and collect data
            $branchIdsString = implode(",", $branchIds);

            // Collect the data for all branches in this group
            $iRows = 0;
            $rsAction = null;

            $partialQuery = "FROM $routeDetailsTable AS a, $projectTeamTable AS b, $branchTable AS c WHERE a.team_id = b.team_id AND b.branch_id = c.branch_id AND b.branch_id IN ($branchIdsString) AND a.dstatus = 0 AND b.dstatus = 0";

            $sQuery = "SELECT a.rec_id, a.section_code, a.wd_code, a.wd_town, a.state, a.district, a.sub_district_goi, a.route_name, a.market_name, a.goi_market_id, a.outlet_name, a.outlet_mobile, a.goi_pop_group, a.ds_sify_id, a.ds_mobile, a.outlet_type, a.shop_type, a.shop_uniq_code" .
                ", a.lt, a.lg, a.team_id, b.team_name, b.is_type, c.branch_name, c.main_branch $partialQuery ORDER BY a.capture_datetime DESC";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($rsAction)) {
                    $route_name = $row["route_name"];
                    $shopId = $row["rec_id"];
                    $arrParts = explode('_', $route_name);
                    $dayName = isset($arrParts[0]) ? $arrParts[0] : "";

                    $outletType = $row["outlet_type"];
                    $cond = $outletType == 'ROC' ? "AND ques_2 = '[\"$shopId\"]'" : "AND ques_3 = '$shopId'";

                    $lastVisitedAndCountofVisited = $this->tableUtil->getRowColumn(
                        $respTable,
                        "MAX(capture_date) AS date",
                        "dstatus = 0 $cond"
                    );

                    $rowData = [
                        $row["rec_id"],
                        $row["team_id"],
                        $row["team_name"],
                        $arrTeamType[$row["is_type"]],
                        $row["main_branch"],
                        $row["branch_name"],
                        $row["section_code"],
                        $row["wd_code"],
                        $dayName,
                        $route_name,
                        $row["outlet_name"],
                        $row["outlet_mobile"],
                        $row["ds_mobile"],
                        $row["shop_type"],
                        $row["shop_uniq_code"],
                        $row["lt"],
                        $row["lg"],
                        isset($lastVisitedAndCountofVisited) ? $lastVisitedAndCountofVisited : "",
                    ];

                    $arrData[] = $rowData;
                }
            }

            // Send email for this group if there's data
            if (!empty($arrData)) {
                $subject = "$groupName - Route report";
                $this->commonFunctions->sendMailWithAttachment($fileName, $subject, $toEmails, $ccEmails, $arrHeader, $arrData);
            }
        }
    }
}

$commonFunctions = new CommonFunctions();
$dbConn = new DBConnection($DB_DBNAME, $DB_USERNAME, $DB_PASSWORD, $commonFunctions, true);
$tableUtil = new TableUtil($dbConn, $commonFunctions);
$vanDsMailer = new VanDsMailer($dbConn, $tableUtil, $commonFunctions);
$vanDsMailer->sendMasterData();
