<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class ProcessLeaderBoard
{
    private $dbConn = null;
    private $tables = [];
    private $commonSettings = [];
    private $arrBranchwiseFocusProducts = [];
    private $arrBranchwiseProducts = [];
    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
        $this->tables = $GLOBALS['TABLES'];
        $this->commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
    }

    // Process leaderboard records for the current date.

    final public function processLeaderBoardRecords()
    {
        $projectTeamTable = $this->tables["PROJECT_TEAM_TABLE"];
        $summaryTable = $this->tables["VANDS_SUMMARY_TABLE"];
        $constantsTable = $this->tables["CONSTANTS_TABLE"];

        $minTotalShops = (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
        $minQualifiedAttendanceTimeInMin = (int) getRowColumn($this->dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
        $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;
        $startOfMonth = date("Y-m-01");
        $currentDt = date("Y-m-d");
        $totalDaysSoFar = (strtotime($currentDt) - strtotime($startOfMonth)) / (60 * 60 * 24) + 1; // Days from start of the month to today
        $iRows2 = 0;
        $sAction2 = null;
        $sQuery2 = "SELECT team_id, branch_id FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99";
        $this->dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
        if ($iRows2 > 0) {
            while ($arData1 = $this->dbConn->GetData($sAction2)) {
                $currentDateTime = currentDateTime();
                $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);
                $teamId = $arData1["team_id"];
                $branch_id = $arData1["branch_id"];
                $NoOfQualifiedDays = 0;
                $qualifiedDays = 0;
                $TotalUniqueShops = 0;
                $UniqueShopsForFirstFocusProduct = 0;
                $UniqueShopsForSecondFocusProduct = 0;
                $para1percentatge = 0; // Reset to 0 for each team
                //total shops linked to that ds
                $TotalShops = getRowColumn($this->dbConn, "tblroute_details", "COUNT(DISTINCT rec_id) AS total", "dstatus = 0 AND team_id = $teamId");
                $iRows = 0;
                $sAction = null;
                // Loop through each day
                for ($i = 0; $i < $totalDaysSoFar; $i++) {
                    $checkDate = date("Y-m-d", strtotime("$startOfMonth +$i days"));
                    $sQuery = "SELECT start_datetime, end_datetime, total_sales_deliveries, total_other_shops FROM $summaryTable WHERE dstatus = 0 AND team_id = $teamId AND activity_date = '$checkDate'";
                    $this->dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
                    if ($iRows > 0) {
                        while ($arData = $this->dbConn->GetData($sAction)) {
                            //total shops done
                            $totalShopsDone = $arData["total_sales_deliveries"] + $arData["total_other_shops"];
                            $timeSpentInSec = getTimeDifferenceInString($arData["start_datetime"], $arData["end_datetime"], true);
                            //qualified Attendance
                            $isQualifiedAttendance = $totalShopsDone >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec ? true : false;
                            // Calculate scores of first parameter
                            if ($isQualifiedAttendance) {
                                $qualifiedDays++;
                            }
                            $NoOfQualifiedDays = $qualifiedDays;
                            $para1percentatge = isset($para1percentatge) ? min(($NoOfQualifiedDays / max($totalDaysSoFar, 1)) * 100, 100) : 0;
                        }
                    }
                }

                $this->getBranchWiseProducts();
                // Get branch wise product columns
                $arrProductBought = $this->getBranchWiseProducts($branch_id);
                $columnsCondition = '';
                if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                    $columns = [];
                    foreach ($arrProductBought as $product) {
                        if (!empty($product[1])) { // Ensure the column name exists
                            $columns[] = $product[1];
                        }
                    }
                    // Create a condition to check if the sum of these columns is greater than 0
                    if (!empty($columns)) {
                        $columnsCondition = '(' . implode(' + ', $columns) . ') > 0';
                    }
                }
                // Check if we have a valid condition
                if (!empty($columnsCondition)) {
                    // Total Unique Shops where the sum of product sales is greater than 0
                    $TotalUniqueShops = getRowColumn(
                        $this->dbConn,
                        "tblsurvey_response_details",
                        "COUNT(DISTINCT ques_3) AS total",
                        "dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN '$startOfMonth' AND '$currentDt' AND $columnsCondition"
                    );
                }
                if ($TotalShops > 0) {
                    $para2percentatge = min(($TotalUniqueShops / max($TotalShops, 1)) * 100, 100);
                };

                $this->getBranchWiseFocusProducts(null, null);
                // Get branch wise product columns
                $arrProductBought = $this->getBranchWiseFocusProducts($branch_id);
                // Initialize variables to store the first and second product column names
                $firstProductColumn = null;
                $secondProductColumn = null;
                if ($arrProductBought && isNonEmptyArray($arrProductBought)) {
                    // Check if there are at least two products
                    if (isset($arrProductBought[0])) {
                        $firstProductColumn = $arrProductBought[0][1]; // First product column name
                    }
                    if (isset($arrProductBought[1])) {
                        $secondProductColumn = $arrProductBought[1][1]; // Second product column name
                    }
                }

                if ($firstProductColumn) {
                    $UniqueShopsForFirstFocusProduct = getRowColumn(
                        $this->dbConn,
                        "tblsurvey_response_details",
                        "COUNT(DISTINCT ques_3) AS total",
                        "dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN '$startOfMonth' AND '$currentDt' AND $firstProductColumn > 0"
                    );
                }

                if ($secondProductColumn) {
                    $UniqueShopsForSecondFocusProduct = getRowColumn(
                        $this->dbConn,
                        "tblsurvey_response_details",
                        "COUNT(DISTINCT ques_3) AS total",
                        "dstatus = 0 AND team_id = $teamId AND capture_date BETWEEN '$startOfMonth' AND '$currentDt' AND $secondProductColumn > 0"
                    );
                }

                if ($TotalShops > 0) {
                    // Parameter 3
                    $para3percentatge = min(($UniqueShopsForFirstFocusProduct / max($TotalShops, 1)) * 100, 100);
                    // Parameter 4
                    $para4percentatge = min(($UniqueShopsForSecondFocusProduct / max($TotalShops, 1)) * 100, 100);
                };

                $totalScore = round($para1percentatge * 0.5 + $para2percentatge * 0.25 + $para3percentatge * 0.125 + $para4percentatge * 0.125, 2);
                $arrBody = [
                    $branch_id,
                    $teamId,
                    $NoOfQualifiedDays,
                    $totalDaysSoFar,
                    $para1percentatge ?? 0,  // Use 0 if para1percentatge is null
                    $TotalShops,
                    $TotalUniqueShops,
                    $para2percentatge ?? 0,
                    $UniqueShopsForFirstFocusProduct,
                    $para3percentatge ?? 0,
                    $UniqueShopsForSecondFocusProduct,
                    $para4percentatge ?? 0,
                    $totalScore ?? 0,
                    $currentDt,
                    $currentDateTime,
                ];

                // Check if an entry for this team_id and month already exists
                $existingRecordId = isRecordExist($this->dbConn, "tbl_leaderboard", "lb_id", "team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = DATE_FORMAT('$currentDt', '%Y-%m')");
                // record exists, update
                if ($existingRecordId) {
                    $rsRes = null;
                    $iNoRows = 0;
                    $update_query = "UPDATE tbl_leaderboard SET qualifiedDays = ?, ttldays = ?, para1_score = ?, ttloutlets = ?, uob = ?, para2_score = ?, fb1uob = ?, para3_score = ?, fb2uob = ?, para4_score = ?, total_score = ?, capture_date = ?, capture_datetime = ?" .
                        " WHERE team_id = $teamId AND DATE_FORMAT(capture_date, '%Y-%m') = DATE_FORMAT('$currentDt', '%Y-%m')";
                    $this->dbConn->ExecuteQuery($update_query, $rsRes, $iNoRows, array_slice($arrBody, 2));
                } else {
                    // record does not exist, add a new record
                    $columns = "branch_id, team_id, qualifiedDays, ttldays, para1_score, ttloutlets, uob, para2_score, fb1uob, para3_score, fb2uob, para4_score, total_score, capture_date, capture_datetime";
                    $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

                    addRecord($this->dbConn, 'tbl_leaderboard', $columns, $vals, $arrBody);
                }
            }
        }
    }

    final private function getBranchWiseFocusProducts($branchId = null, $teamType = null)
    {
        $branchProductTable = $this->tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        if ($branchId) {
            if ($teamType !== null && $teamType !== "") {
                return $this->arrBranchwiseFocusProducts[$branchId][$teamType] ?? [];
            } else {
                return $this->arrBranchwiseFocusProducts[$branchId] ?? [];
            }
        } else {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = getRowsColumns(
                    $branchProductTable,
                    "branch_id, product_name, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' AND is_focusbrand = 1 ORDER BY product_name",
                    [],
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $productName = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->arrBranchwiseFocusProducts[$branchId][$teamType])) {
                        $this->arrBranchwiseFocusProducts[$branchId][$teamType] = [];
                    }
                    $this->arrBranchwiseFocusProducts[$branchId][$teamType][] = [$productName, $summaryColumnName];
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name FROM $branchProductTable WHERE dstatus = 0 AND is_focusbrand = 1 ORDER BY product_name";
                $this->dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];
                        if (!isset($this->arrBranchwiseFocusProducts[$branchId])) {
                            $this->arrBranchwiseFocusProducts[$branchId] = [];
                        }
                        $this->arrBranchwiseFocusProducts[$branchId][] = [$rowProduct["product_name"], $rowProduct["summary_column_name"]];
                    }
                }
            }
        }
    }

    final private function getBranchWiseProducts($branchId = null, $productsList = true, $teamType = null)
    {
        $branchProductTable = $this->tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        if ($branchId) {
            if ($teamType !== null && $teamType !== "") {
                if ($productsList) {
                    return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
                } else {
                    return $this->arrBranchwiseProducts[$branchId][$teamType] ?? [];
                }
            } else {
                if ($productsList) {
                    return $this->arrBranchwiseProducts[$branchId] ?? [];
                } else {
                    return $this->arrBranchwiseProducts[$branchId] ?? [];
                }
            }
        } else {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = getRowsColumns(
                    $branchProductTable,
                    "branch_id, product_name, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' ORDER BY product_name",
                    [],
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $productName = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->arrBranchwiseProducts[$branchId][$teamType])) {
                        $this->arrBranchwiseProducts[$branchId][$teamType] = [];
                    }
                    $this->arrBranchwiseProducts[$branchId][$teamType][] = [$productName, $summaryColumnName];
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT DISTINCT branch_id, product_name, summary_column_name FROM $branchProductTable WHERE dstatus = 0 ORDER BY product_name";
                $this->dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];

                        if (!isset($this->arrBranchwiseProducts[$branchId])) {
                            $this->arrBranchwiseProducts[$branchId] = [];
                        }
                        $this->arrBranchwiseProducts[$branchId][] = [$rowProduct["product_name"], $rowProduct["summary_column_name"]];
                    }
                }
            }
        }
    }
}

$processResponse = new ProcessLeaderBoard($dbConn);
$processResponse->processLeaderBoardRecords();
