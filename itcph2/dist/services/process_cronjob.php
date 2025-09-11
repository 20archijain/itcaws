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
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class ProcessResponse
{
    private $_dbConn = null;
    private $_tables = [];
    private $_commonSettings = [];
    private $_projectSpecificSettings = [];
    private $_jsonWiseAndbranchWiseProductsColumns = [];
    private $_maxDistanceBwCoordinates = 80 * 1000; // 80km

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_commonSettings = $GLOBALS['COMMON_PROCESS_SETTINGS'];
        $this->_projectSpecificSettings = $GLOBALS['PROJECT_SPECIFIC_SETTINGS'];
    }

    final public function processRecords()
    {
        // List of projects to process
        $arrPid = $this->_commonSettings["ALLOWED_PID"];
        $sPids = implode(",", $arrPid);
        $processTable = $this->_commonSettings["PROCESS_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $currentDate = currentDate();
        $cond = "AND rcd = '$currentDate'";

        // Get branch wise products
        $this->getBranchWiseProducts();

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT resp_id, uni_id, client_id, project_id, team_id, s_id, sur_response, distance_travelled_in_km, call_time, capture_date, capture_datetime, lt, lg, rcd, rdt FROM $processTable WHERE project_id IN ($sPids) AND processed = '0' AND dstatus = 0 $cond ORDER BY resp_id LIMIT 200";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            // Store team branch id to avoid query to fetch branch id of same team
            $arrTeamBranch = array();
            while ($row = $this->_dbConn->GetData($sAction)) {
                $respId = $row["resp_id"];
                $uniId = $row["uni_id"];
                $clientId = $row["client_id"];
                $projectId = $row["project_id"];
                $teamId = $row["team_id"];
                $userType = getRowColumn($this->_dbConn, $projectTeamTable, "is_type", "team_id = $teamId");
                $jsonId = $row["s_id"];
                $resString = $row["sur_response"];
                $distanceTravelledInKm = $row["distance_travelled_in_km"];
                $callTime = $row["call_time"];
                $captureDate = $row["capture_date"];
                $captureDatetime = $row["capture_datetime"];
                $lt = $row["lt"];
                $lg = $row["lg"];
                $rcd = $row["rcd"];
                $rdt = $row["rdt"];

                // TO BE REMOVED
                // $jsonId = $jsonId == 20 ? 10 : $jsonId;

                // get team branch
                if (isset($arrTeamBranch[$teamId])) {
                    $branchId = $arrTeamBranch[$teamId];
                } else {
                    $branchId = getRowColumn($this->_dbConn, $projectTeamTable, "branch_id", "team_id = $teamId");
                    $branchId = $branchId ? $branchId : 1;
                    $arrTeamBranch[$teamId] = $branchId;
                }

                $arrResponse = json_decode($resString, true);
                usort($arrResponse, array($this, "sortPages"));

                $arrData = array();

                $clientSettings = isset($this->_projectSpecificSettings[$clientId]) ? $this->_projectSpecificSettings[$clientId] : null;
                $projectSettings = $clientSettings && isset($clientSettings[$projectId], $clientSettings[$projectId][$jsonId]) ? $clientSettings[$projectId][$jsonId] : $this->_commonSettings;

                // Get process settings based on skip logic
                $processBasedOnSkipLogic = $projectSettings ? (isset($projectSettings["PROCESS_BASED_ON_SKIP_LOGIC"]) ? $projectSettings["PROCESS_BASED_ON_SKIP_LOGIC"] : array()) : array();

                // Get number of questions in each page/form
                $arrNoOfQuestions = $projectSettings["NO_OF_QUESTIONS"];

                // Get all answers in an array
                foreach ($arrResponse as $page) {
                    $pageId = $page["pageId"];

                    $quesList = isset($page["quesList"]) ? $page["quesList"] : array();  // Questions/Controls in that page
                    if (isNonEmptyArray($quesList)) {
                        $arrData[$pageId] = array();
                        // No of questions in page (This is required since some questions might be optional)
                        $iNoOfQuestions = $arrNoOfQuestions[$pageId - 1];

                        for ($i = 1; $i <= $iNoOfQuestions; $i++) {
                            $quesIndex = array_search($i, array_column($quesList, 'quesId'));

                            // Question found
                            if ($quesIndex !== false) {
                                $singleAns = isset($quesList[$quesIndex]["singleAns"]) ? $quesList[$quesIndex]["singleAns"] : null;
                                $ansMultiChoice = isset($quesList[$quesIndex]["ansMultiChoice"]) ? $quesList[$quesIndex]["ansMultiChoice"] : null;  // Dropdown
                                $fileUniqueId = isset($quesList[$quesIndex]["fileUniqueId"]) ? $quesList[$quesIndex]["fileUniqueId"] : null;    // Image
                                $ansGrid = isset($quesList[$quesIndex]["ansGrid"]) ? $quesList[$quesIndex]["ansGrid"] : null;   // Grid
                                $ansMatched = isset($quesList[$quesIndex]["ansMatched"]) ? array("ansMatched" => $quesList[$quesIndex]["ansMatched"], "quesId" => $quesList[$quesIndex]["quesId"]) : null; // Quiz
                                $netAmount = isset($quesList[$quesIndex]["net_amount"]) ? $quesList[$quesIndex]["net_amount"] : null;
                                $discount = isset($quesList[$quesIndex]["discount"]) ? $quesList[$quesIndex]["discount"] : null;
                                // Separate ansGrid and financial data
                                $netAmountDiscount = isset($netAmount) || isset($discount)
                                    ? array("netAmount" => $netAmount, "discount" => $discount)
                                    : null;
                                $arrData[$pageId][$i] = isset($ansMatched) ?
                                    $ansMatched : (isset($singleAns) ? $singleAns : (isset($ansMultiChoice) ? $ansMultiChoice : (isset($fileUniqueId) ? $fileUniqueId : (isset($netAmountDiscount) ? ['ansGrid' => $ansGrid, 'netAmountDiscount' => $netAmountDiscount] : $ansGrid))));
                            } else {
                                $arrData[$pageId][$i] = "";
                            }
                        }
                    }
                }

                if (isNonEmptyArray($arrData)) {
                    $lastRecId = 0;
                    $responseTable = $projectSettings["RESPONSE_TABLE"];
                    $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
                    $routeDetailsTable = $this->_tables["ROUTE_DETAILS_TABLE"];
                    $stockSummaryTable = $this->_tables["STOCK_SUMMARY_TABLE"];

                    // get settings
                    // Attendance
                    $processAttendance = isset($projectSettings["PROCESS_ATTENDANCE"]) ? $projectSettings["PROCESS_ATTENDANCE"] : false;
                    $attendanceForm = $processAttendance ? $projectSettings["ATTENDANCE_FORM"] : array();
                    $attendanceMobImgIdForm = $processAttendance ? $projectSettings["ATTENDANCE_MOBIMGID_FORM"] : array();

                    // Dayend
                    $processDayend = isset($projectSettings["PROCESS_DAYEND"]) ? $projectSettings["PROCESS_DAYEND"] : false;
                    $dayendForm = $processDayend ? $projectSettings["DAYEND_FORM"] : array();
                    $dayendMobImgIdForm = $dayendForm ? $projectSettings["DAYEND_MOBIMGID_FORM"] : array();

                    // Other than Attendance and Dayend
                    $processOther = isset($projectSettings["PROCESS_OTHER"]) ? $projectSettings["PROCESS_OTHER"] : false;
                    $arrStoreOptype23Separately = isset($projectSettings["STORE_OPTYPE_23_OPTIONS_SEPARATELY"]) ?
                        $projectSettings["STORE_OPTYPE_23_OPTIONS_SEPARATELY"] : array();

                    // Attendance
                    $attendanceValue = isNonEmptyArray($attendanceForm) ? $arrData[$attendanceForm[0]][$attendanceForm[1]] : null;
                    $dayendValue = isNonEmptyArray($dayendForm) ? $arrData[$dayendForm[0]][$dayendForm[1]] : null;
                    $isAttendanceRecord = false;
                    $isDayendRecord = false;
                    $isOtherRecord = false;
                    $isUnAdherence = false;
                    $reasonForNoBeatAdherence = "";
                    $route = "";

                    if ($processAttendance && is_string($attendanceValue) && $attendanceValue && (strtolower($attendanceValue) === "attendance" || strtolower(substr($attendanceValue, 0, 14)) === "morning survey")) {
                        $isAttendanceRecord = true;
                        $cols = "resp_id, client_id, project_id, team_id, s_id, uni_id, mob_img_id, capture_date, capture_datetime, lt, lg, rcd, rdt";
                        $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrParams = array($respId, $clientId, $projectId, $teamId, $jsonId, $uniId, isset($arrData[$attendanceMobImgIdForm[0]][$attendanceMobImgIdForm[1]]) ? $arrData[$attendanceMobImgIdForm[0]][$attendanceMobImgIdForm[1]] : "", $captureDate, $captureDatetime, $lt, $lg, $rcd, $rdt);

                        // Check if JSON contains other data that should be stored in attendance table
                        $attendanceData = isset($projectSettings["ATTENDANCE_DATA"]) ? $projectSettings["ATTENDANCE_DATA"] : array();
                        if (isNonEmptyArray($attendanceData)) {
                            $arrAttendanceData = array();
                            foreach ($attendanceData as $data) {
                                $arrAttendanceData[$data["label"]] = isset($arrData[$data["valueIndex"][0]][$data["valueIndex"][1]]) ? $arrData[$data["valueIndex"][0]][$data["valueIndex"][1]] : "";
                            }

                            // Check if beatAdherenceReason is NOT available
                            if (!isset($arrAttendanceData["beatAdherenceReason"]) || empty($arrAttendanceData["beatAdherenceReason"])) {
                                $isUnAdherence = false;
                                $reasonForNoBeatAdherence = "";
                            } else {
                                $isUnAdherence = true;
                                $reasonForNoBeatAdherence = $arrAttendanceData["beatAdherenceReason"];
                            }
                            $route = isset($arrAttendanceData['route']) ? $arrAttendanceData['route'] : [];
                            $route = isset($route[0]) ? $route[0] : null;
                            $cols .= ", other_details";
                            $vals .= ", ?";
                            $arrParams[] = json_encode($arrAttendanceData);
                        }

                        $activityType = $attendanceValue;
                        $iStatus = addRecord($this->_dbConn, $attendanceTable, $cols, $vals, $arrParams);

                        // Update process status
                        if ($iStatus === 2) {
                            $this->_dbConn->GetLastInsertId($lastRecId);
                            $this->updateProcessStatus($processTable, $respId, $jsonId);
                        }
                    } elseif ($processDayend && is_string($dayendValue) && $dayendValue && strtolower(substr($dayendValue, 0, 7)) === "day end") {
                        // Dayend
                        $isDayendRecord = true;
                        $cols = "resp_id, client_id, project_id, team_id, s_id, uni_id, mob_img_id, call_type, capture_date, capture_datetime, lt, lg, rcd, rdt";
                        $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrParams = array($respId, $clientId, $projectId, $teamId, $jsonId, $uniId, isset($arrData[$dayendMobImgIdForm[0]][$dayendMobImgIdForm[1]]) ? $arrData[$dayendMobImgIdForm[0]][$dayendMobImgIdForm[1]] : "", '1', $captureDate, $captureDatetime, $lt, $lg, $rcd, $rdt);

                        // Check if JSON contains other data that can should be stored in table
                        $dayendData = isset($projectSettings["DAYEND_DATA"]) ? $projectSettings["DAYEND_DATA"] : array();
                        if (isNonEmptyArray($dayendData)) {
                            $arrDayendData = array();
                            foreach ($dayendData as $data) {
                                $arrDayendData[$data["label"]] = isset($arrData[$data["valueIndex"][0]][$data["valueIndex"][1]]) ? $arrData[$data["valueIndex"][0]][$data["valueIndex"][1]] : null;
                            }

                            $cols .= ", other_details";
                            $vals .= ", ?";
                            $arrParams[] = json_encode($arrDayendData);
                        }

                        $activityType = $dayendValue;
                        $iStatus = addRecord($this->_dbConn, $attendanceTable, $cols, $vals, $arrParams);

                        // Update process status
                        if ($iStatus === 2) {
                            $this->_dbConn->GetLastInsertId($lastRecId);
                            $this->updateProcessStatus($processTable, $respId, $jsonId);
                        }
                    } elseif ($processOther) {
                        // Other

                        $isOtherRecord = true;
                        $cols = "resp_id, uni_id, client_id, project_id, team_id, s_id, call_time, capture_date, capture_datetime, lt, lg, rcd, rdt";
                        $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrParams = array($respId, $uniId, $clientId, $projectId, $teamId, $jsonId, $callTime, $captureDate, $captureDatetime, $lt, $lg, $rcd, $rdt);

                        // Find page Ids to process based on skip logic if any
                        $arrPageIds = array();
                        if (isNonEmptyArray($processBasedOnSkipLogic)) {
                            foreach ($processBasedOnSkipLogic as $pageId => $quesInfo) {
                                $iQuesId = $quesInfo["QUES_ID"];
                                $selectedValue = isset($arrData[$pageId]) && isset($arrData[$pageId][$iQuesId]) ? strtolower($arrData[$pageId][$iQuesId]) : null;

                                // Check if selectedValue is not null, not empty, and the key exists in quesInfo
                                if (isset($selectedValue) && $selectedValue !== "" && isset($quesInfo[$selectedValue]) && (count($arrPageIds) == 0 || in_array($pageId, $arrPageIds))) {
                                    $arrProcessPageIds = $quesInfo[$selectedValue];
                                    if (isNonEmptyArray($arrProcessPageIds)) {
                                        foreach ($arrProcessPageIds as $iPageId) {
                                            if (!in_array($iPageId, $arrPageIds)) {
                                                $arrPageIds[] = $iPageId;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // create query params
                        $i = 0;
                        // keep some values in last
                        $arrLast = array();
                        foreach ($arrData as $pageId => $quesList) {
                            if (!isNonEmptyArray($arrPageIds) || in_array($pageId, $arrPageIds)) {
                                foreach ($quesList as $quesId => $answer) {
                                    if (
                                        $arrStoreOptype23Separately && isNonEmptyArray($arrStoreOptype23Separately) &&
                                        isset($arrStoreOptype23Separately[$pageId]) &&
                                        in_array($quesId, $arrStoreOptype23Separately[$pageId])
                                    ) {
                                        foreach ($answer as $ans) {
                                            $cols .= ", ques_$i";
                                            $vals .= ", ?";
                                            $arrParams[] = is_array($ans) ? json_encode($ans) : $ans;
                                            $i++;
                                        }
                                    } else {
                                        // Move OTP ques value in last
                                        if ($jsonId == 99 && $pageId == 5 && $quesId == 3) {
                                            $arrLast[] = $answer;
                                        } else {
                                            $cols .= ", ques_$i";
                                            $vals .= ", ?";
                                            $arrParams[] = is_array($answer) ? json_encode($answer) : (isset($answer) ? $answer : "");
                                            $i++;
                                        }
                                    }
                                }
                            }
                        }

                        // Add last values in query
                        if (isNonEmptyArray($arrLast)) {
                            foreach ($arrLast as $answer) {
                                $cols .= ", ques_$i";
                                $vals .= ", ?";
                                $arrParams[] = is_array($answer) ? json_encode($answer) : (isset($answer) ? $answer : "");
                                $i++;
                            }
                        }

                        $productsBought = "";
                        $activityType = $arrParams[13];

                        // Van DS
                        if ($projectId == 1) {
                            if ($jsonId == 99) {
                                // Other Outlet
                                if ($arrParams[13] == "Add Outlet") {
                                    $otherCols = "resp_id, team_id, route_name, outlet_name, outlet_mobile, wd_code, shop_type, capture_date, capture_datetime, lt, lg, sort_order";
                                    $otherVals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                                    $route = json_decode($arrParams[14], true)[0];
                                    $daySortOrder = ["Mon" => 1, "Tue" => 2, "Wed" => 3, "Thu" => 4, "Fri" => 5, "Sat" => 6, "Sun" => 7];
                                    $sort_order = $daySortOrder[substr($route, 0, 3)] ?? 0;
                                    if ($jsonId == 99) {
                                        $shopName = $arrParams[16];
                                        $ownerMobileNumber = $arrParams[17];
                                        $shopType = $arrParams[18];
                                        $sellin = $arrParams[19];
                                        if ($sellin == "Yes") {
                                            $productsBought = $arrParams[20];
                                        }
                                        $shopFrontPicture = $arrParams[21] ?? null;

                                        // add if valid shop
                                        if ($jsonId == 99) {
                                            $otp = $arrParams[22] ?? null;
                                            //list($callStatus) = getCallStatus($this->_dbConn, "tblcloudring_live_login", $ownerMobileNumber, $otp);
                                            $callStatus = 5;
                                        } else {
                                            $otp = "";
                                            $callStatus = 5;
                                        }

                                        // add shop only if valid and not exists
                                        if ($callStatus == 5) {
                                            $arrWdcode = getRowColumns($this->_dbConn, $routeDetailsTable, "wd_code", "dstatus = 0 AND route_name = ? AND team_id = ?", array($route, $teamId));
                                            $arrWdcode = $arrWdcode ? $arrWdcode : array("");
                                            $arrOtherParams = array($respId, $teamId, $route, $shopName, $ownerMobileNumber, $arrWdcode[0], $shopType, $captureDate, $captureDatetime, $lt, $lg, $sort_order);
                                            $existingShopId = getRowColumn(
                                                $this->_dbConn,
                                                $routeDetailsTable,
                                                "rec_id",
                                                "team_id = ? AND route_name = ? AND outlet_name = ? AND outlet_mobile = ? AND wd_code = ? AND shop_type = ?",
                                                array($teamId, $route, $shopName, $ownerMobileNumber, $arrWdcode[0], $shopType)
                                            );

                                            if (!(isset($existingShopId) && $existingShopId)) {
                                                addRecord($this->_dbConn, $routeDetailsTable, $otherCols, $otherVals, $arrOtherParams);
                                            }

                                            $recIdOfAddedShop = getRowColumn(
                                                $this->_dbConn,
                                                $routeDetailsTable,
                                                "rec_id",
                                                "team_id = ? AND route_name = ? AND outlet_name = ? AND outlet_mobile = ? AND wd_code = ? AND shop_type = ?",
                                                array($teamId, $route, $shopName, $ownerMobileNumber, $arrWdcode[0], $shopType)
                                            );
                                        } else {
                                            // delete record since not a valid shop
                                            deleteRecord($this->_dbConn, $processTable, "resp_id", 1, "", array($respId));
                                            continue;
                                        }

                                        // take 17 values till Beat/Route question and recreate params, cols, vals
                                        $arrParams = array_slice($arrParams, 0, 16);
                                        $arrParams[] = $recIdOfAddedShop;
                                        $arrParams[] = $sellin;
                                        $arrParams[] = $productsBought;
                                        $arrParams[] = $shopFrontPicture;
                                        $arrParams[] = $otp;
                                        // take first 13 static columns
                                        $arrCols = array_slice(explode(",", $cols), 0, 13);
                                        $arrVals = array_slice(explode(",", $vals), 0, 13);
                                        for ($i = 0; $i < (count($arrParams) - 13); $i++) {
                                            $arrCols[] = "ques_$i";
                                            $arrVals[] = "?";
                                        }
                                        $cols = implode(",", $arrCols);
                                        $vals = implode(",", $arrVals);
                                    }
                                } else {
                                    $this->_dbConn->GetLastInsertId($lastRecId);
                                    // Outlet Orders
                                    $sellin = $arrParams[17];
                                    if ($sellin == "Yes") {
                                        $productsBought = $arrParams[18];
                                    }
                                    // Update route coordinates if not updated
                                    $shopId = $arrParams[16];
                                    if ($lt && $lt > 0 && $shopId && is_numeric($shopId)) {
                                        // Don't use dstatus = 0
                                        $shopLt = getRowColumn(
                                            $this->_dbConn,
                                            $routeDetailsTable,
                                            "lt",
                                            "rec_id = $shopId"
                                        );

                                        if (!$shopLt || $shopLt <= 0) {
                                            // Don't use dstatus = 0
                                            updateRecord(
                                                $this->_dbConn,
                                                $routeDetailsTable,
                                                "lt = ?, lg = ?",
                                                "rec_id = $shopId",
                                                array($lt, $lg)
                                            );
                                        }
                                    }
                                }

                                // Add each product bought Qty in separate column
                                if ($productsBought) {
                                    $arrProductsBought = is_string($productsBought) ? json_decode($productsBought, true) : $productsBought;

                                    // Get branch products
                                    $arrProductSummaryColumns = $this->getBranchWiseProducts($branchId, $jsonId, $userType);
                                    // Get sales
                                    $arrSales = getGridDataAsArray($arrProductsBought["ansGrid"], 2, count($arrProductSummaryColumns));

                                    $cols .= ", update_sale";
                                    $vals .= ", 1";

                                    // Add sale for each product if sale > 0
                                    if (isNonEmptyArray($arrProductSummaryColumns)) {
                                        foreach ($arrProductSummaryColumns as $productIndex => $productSummaryColumn) {
                                            $iSale = isset($arrSales[1][$productIndex]) && floatval($arrSales[1][$productIndex]) ? floatval($arrSales[1][$productIndex]) : 0;

                                            if ($iSale > 0) {
                                                $cols .= ", $productSummaryColumn";
                                                $vals .= ", $iSale";
                                            }
                                        }
                                    }
                                    // Check if netAmountDiscount exists in $arrProductsBought and extract values
                                    if (isset($arrProductsBought['netAmountDiscount'])) {
                                        $netAmount = isset($arrProductsBought['netAmountDiscount']['netAmount'])
                                            ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['netAmount'])
                                            : 0;

                                        // Check if discount exists and ensure it defaults to 0 if empty or an empty string
                                        $discount = isset($arrProductsBought['netAmountDiscount']['discount'])
                                            ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['discount'])
                                            : 0;

                                        $netAmount = ($netAmount === '' || $netAmount === null) ? 0 : $netAmount;
                                        $discount = ($discount === '' || $discount === null) ? 0 : $discount;

                                        // Append cleaned values to $cols and $vals
                                        $cols .= ", netAmount, discount";
                                        $vals .= ", '$netAmount', '$discount'";
                                    }
                                }
                            } elseif ($jsonId == 100) {
                                $productsBought = $arrParams[13];
                                $arrBranchIsTypeWdcode = getRowColumns($this->_dbConn, $projectTeamTable, "branch_id, is_type, wd_code", "dstatus = 0 AND team_id = ?", array($teamId));

                                // Add each product bought Qty in separate rows
                                if ($productsBought) {
                                    $arrProductsBought = is_string($productsBought) ? json_decode($productsBought, true) : $productsBought;
                                    // Get branch products
                                    $arrProductSummaryColumns = $this->getBranchWiseProducts($branchId, 99);
                                    // Get sales
                                    $arrSales = getGridDataAsArray($arrProductsBought, 2, count($arrProductSummaryColumns));

                                    // Insert each product's sale as a separate row
                                    if (isNonEmptyArray($arrProductSummaryColumns)) {
                                        foreach ($arrProductSummaryColumns as $productIndex => $productSummaryColumn) {
                                            $iSale = isset($arrSales[1][$productIndex]) && floatval($arrSales[1][$productIndex]) ? floatval($arrSales[1][$productIndex]) : 0;

                                            if ($iSale > 0 && isset($arrProductsBought[$productIndex])) {
                                                // Extract the category and product name for the current product
                                                $category = $arrProductsBought[$productIndex]['Category'];
                                                $productName = $arrProductsBought[$productIndex]['productname'];
                                                $sortOrder = $arrProductsBought[$productIndex]['rowNo'];
                                                $responseTable = "tblwd_product_net_rate_update";
                                                $iWdrecId = getRowColumn($this->_dbConn, $responseTable, "rec_id", "wd_code = '$arrBranchIsTypeWdcode[2]' AND product_name = '$productName'");
                                                if (!empty($iWdrecId)) {
                                                    // Update the record
                                                    $iStatus = updateRecord($this->_dbConn, "tblwd_product_net_rate_update", "net_rate = $iSale, rcd = '$captureDate', rdt = '$captureDatetime'", "rec_id = $iWdrecId");
                                                } else {
                                                    // Prepare column names and values for each product
                                                    $cols = "branch_id, wd_code, json_id, team_type, is_focusbrand, category_name, product_name, summary_column_name, net_rate, sort_order";
                                                    $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

                                                    // Prepare the array for each product’s parameters
                                                    $arrParams = array(
                                                        $arrBranchIsTypeWdcode[0],  // branch_id
                                                        $arrBranchIsTypeWdcode[2],  // wd_code
                                                        $jsonId,                    // json_id
                                                        $arrBranchIsTypeWdcode[1],  // team_type
                                                        "1",                        // is_focusbrand (if needed, fill accordingly)
                                                        $category,                  // category_name (actual value from $arrProductsBought)
                                                        $productName,               // product_name (actual value from $arrProductsBought)
                                                        $productSummaryColumn,      // summary_column_name
                                                        $iSale,                     // Set the sale value to the net_rate column
                                                        $sortOrder                        // sort_order (provide actual value)
                                                    );

                                                    // Insert data into the table for each product inside the loop
                                                    $iStatus = addRecord($this->_dbConn, $responseTable, $cols, $vals, $arrParams);
                                                }
                                                if ($iStatus === 2) {
                                                    $this->updateProcessStatus($processTable, $respId, $jsonId);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($jsonId != 100) {
                            $iStatus = addRecord($this->_dbConn, $responseTable, $cols, $vals, $arrParams);

                            // Update process status
                            if ($iStatus === 2) {
                                $this->_dbConn->GetLastInsertId($lastRecId);
                                $this->updateProcessStatus($processTable, $respId, $jsonId);

                                // add each Products Bought stock in separate column if not exists in stock summary table
                                if ($projectId == 1) {
                                    if ($arrParams[13] == "Outlet Survey") {
                                        updateRecord($this->_dbConn, "tblsurvey_response_details", "ques_0 = 'Outlet Order'", "pro_id = $lastRecId");
                                    }

                                    // Add each product bought Qty in separate column
                                    if ($productsBought && $jsonId = 99) {
                                        $stockColumns = "team_id, capture_date, stock_type, rec_id, rcd, rdt";
                                        $stockValues = "?, ?, ?, ?, ?, ?";
                                        $arrStockParams = array($teamId, $captureDate, 2, $lastRecId, $rcd, $rdt);

                                        // Get branch products
                                        $arrProductSummaryColumns = $this->getBranchWiseProducts($branchId, $jsonId);
                                        $arrProductsBought = is_string($productsBought) ? json_decode($productsBought, true) : $productsBought;
                                        // Get sales
                                        $arrSales = getGridDataAsArray($arrProductsBought["ansGrid"], 2, count($arrProductSummaryColumns));

                                        if (isNonEmptyArray($arrProductSummaryColumns)) {
                                            foreach ($arrProductSummaryColumns as $productIndex => $productSummaryColumn) {
                                                $stockColumns .= ", $productSummaryColumn";
                                                $stockValues .= ", ?";
                                                $arrStockParams[] = isset($arrSales[1][$productIndex]) && floatval($arrSales[1][$productIndex]) ? floatval($arrSales[1][$productIndex]) : 0;
                                            }
                                        }

                                        addRecord($this->_dbConn, $stockSummaryTable, $stockColumns, $stockValues, $arrStockParams, false, 1, "sp_id", "team_id = $teamId AND capture_date = '$captureDate' AND stock_type = 2 AND rec_id = $lastRecId");
                                    }
                                }
                            }
                        }
                    }

                    if ($jsonId != 100) {
                        $this->updateSummary($lastRecId, $jsonId, $teamId, $captureDate, $captureDatetime, $lt, $lg, $activityType, $arrParams, $isOtherRecord, $branchId, $isAttendanceRecord, $isDayendRecord, $distanceTravelledInKm, $isUnAdherence, $reasonForNoBeatAdherence, $route, $userType);
                    }
                }
            }
        }
    }

    private function sortPages($a, $b)
    {
        return $a["pageId"] - $b["pageId"];
    }

    private function updateProcessStatus($processTable, $respId, $jsonId)
    {
        updateRecord($this->_dbConn, $processTable, "processed = '1', s_id = '$jsonId'", "dstatus = 0 AND resp_id = $respId");
    }

    private function updateSummary($lastRecId, $jsonId, $teamId, $captureDate, $captureDatetime, $lt, $lg, $activityType, $arrData, $isOtherRecord, $branchId, $isAttendanceRecord, $isDayendRecord, $distanceTravelledInKm, $isUnAdherence, $reasonForNoBeatAdherence, $route, $userType)
    {
        $currentDate = currentDate();
        $currentDatetime = currentDateTime();
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];

        $arrProductSummaryColumns = $this->getBranchWiseProducts($branchId, $jsonId, $userType);


        $arrSummary = getRowColumns($this->_dbConn, $summaryTable, "summary_id, attendance_datetime, dayend_datetime, resp_startdatetime, last_rec_lt, last_rec_lg", "dstatus = 0 AND team_id = $teamId AND activity_date = ?", array($captureDate));

        // Summary exist, update
        if (isset($arrSummary, $arrSummary[0]) && $arrSummary[0]) {
            $summaryId = $arrSummary[0];
            $attendanceDatetime = $arrSummary[1];
            $dayendDatetime = $arrSummary[2];
            $respStartDatetime = $arrSummary[3];
            $lastRecLt = $arrSummary[4];
            $lastRecLg = $arrSummary[5];

            // Update only if first attendance record or first dayend record or other record
            if (($isAttendanceRecord && !$attendanceDatetime) || ($isDayendRecord && !$dayendDatetime) || $isOtherRecord) {
                $values = "team_id = ?";
                $arrParams = array($teamId);
                $condition = "dstatus = 0 AND summary_id = $summaryId";

                // Outlet Order OR Add Outlet
                if ($isOtherRecord && ($activityType === 'Outlet Order' || $activityType === 'Outlet Survey' || $activityType === 'Add Outlet')) {
                    if ($activityType === 'Outlet Order' || $activityType === 'Outlet Survey ') {
                        $values .= ", total_sales_deliveries = (total_sales_deliveries + 1)";
                    } else {
                        $values .= ", total_other_shops = (total_other_shops + 1)";
                    }

                    if ($arrData[17] === 'Yes') {
                        $values .= ", total_sellin_shops = (total_sellin_shops + 1)";
                    }

                    if ((($activityType === 'Outlet Order' || $activityType === 'Outlet Survey') && $arrData[17] === 'Yes') || ($activityType === 'Add Outlet' && $arrData[17] === 'Yes')) {
                        $arrProductsBought = is_string($arrData[18]) ? json_decode($arrData[18], true) : $arrData[18];
                        $arrSale = getGridDataAsArray($arrProductsBought["ansGrid"], 2, count($arrProductSummaryColumns));

                        // update sales values in correct columns
                        $arrSalesValues = array();
                        if (isNonEmptyArray($arrProductSummaryColumns)) {
                            foreach ($arrProductSummaryColumns as $productIndex => $productSummaryColumn) {
                                $values .= ", $productSummaryColumn = ($productSummaryColumn + ?)";
                                $arrSalesValues[] = isset($arrSale[1][$productIndex]) && floatval($arrSale[1][$productIndex]) ? floatval($arrSale[1][$productIndex]) : 0;
                            }
                        }

                        // Check if netAmountDiscount exists in $arrProductsBought and extract values
                        if (isset($arrProductsBought['netAmountDiscount'])) {
                            $netAmount = isset($arrProductsBought['netAmountDiscount']['netAmount'])
                                ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['netAmount'])
                                : 0;

                            // Check if discount exists and ensure it defaults to 0 if empty or an empty string
                            $discount = isset($arrProductsBought['netAmountDiscount']['discount'])
                                ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['discount'])
                                : 0;

                            $netAmount = ($netAmount === '' || $netAmount === null) ? 0 : $netAmount; // Final fallback
                            $discount = ($discount === '' || $discount === null) ? 0 : $discount; // Final fallback

                            $values .= ", netAmount = (netAmount + {$netAmount}), discount = (discount + {$discount})";
                        }

                        $arrParams = array_merge($arrParams, $arrSalesValues);
                    }
                }

                // update distance travelled related values only if not other record, or if other record, then it should upload before dayend
                if (!$isOtherRecord || ($isOtherRecord && !$dayendDatetime)) {
                    // get distance travelled from last record to this record
                    if ($distanceTravelledInKm > 0 || $lt) {
                        $useKmValueFromTable = $distanceTravelledInKm > 0 ? true : false;
                        $distanceInM = $useKmValueFromTable ?
                            0 : ($lastRecLt ? calculateDistanceBwCoordinates($lastRecLt, $lastRecLg, $lt, $lg) : 0);

                        // update last record id, lt, lg
                        if ($useKmValueFromTable || ($distanceInM <= $this->_maxDistanceBwCoordinates)) {
                            $values .= ", last_rec_id = ?, last_rec_lt = ?, last_rec_lg = ?";
                            $arrParams[] = $lastRecId;
                            $arrParams[] = $lt;
                            $arrParams[] = $lg;
                        } elseif (!$useKmValueFromTable && $distanceInM > $this->_maxDistanceBwCoordinates) {
                            // Ignore if distance > threshold
                            $distanceInM = 0;
                        }

                        if ($useKmValueFromTable) {
                            $distanceInM = round($distanceTravelledInKm * 1000, 2);
                            $values .= ", total_meter_travelled = $distanceInM";
                        } else {
                            $values .= ", total_meter_travelled = (total_meter_travelled + $distanceInM)";
                        }
                    }

                    // update attendance datetime
                    if ($isAttendanceRecord) {
                        $values .= ", attendance_datetime = '$captureDatetime'";
                    } elseif ($isDayendRecord) {
                        // update dayend datetime
                        $values .= ", dayend_datetime = '$captureDatetime'";
                    } elseif ($isOtherRecord) {
                        // update resp start, end datetime
                        $values .= $respStartDatetime ? ", resp_enddatetime = '$captureDatetime'" : ", resp_startdatetime = '$captureDatetime', resp_enddatetime = '$captureDatetime'";
                    }

                    $values .= ", end_datetime = ?";
                    $arrParams[] = $captureDatetime;
                }

                updateRecord($this->_dbConn, $summaryTable, $values, $condition, $arrParams);
            }
        } else {
            // Summary not exist, create
            $columns = "team_id, activity_date, start_datetime, end_datetime, rcd, rdt";
            $values = "?, ?, ?, ?, ?, ?";
            $arrParams = array($teamId, $captureDate, $captureDatetime, $captureDatetime, $currentDate, $currentDatetime);

            // Update attendance datetime
            if ($isAttendanceRecord) {
                $columns .= ", attendance_datetime, route";
                $values .= ", ?, ?";
                $arrParams[] = $captureDatetime;
                $arrParams[] = $route;
            } elseif ($isDayendRecord) {
                // Update dayend datetime
                $columns .= ", dayend_datetime";
                $values .= ", ?";
                $arrParams[] = $captureDatetime;
            } elseif ($isOtherRecord) {
                // update resp start, end datetime if other record
                $columns .= ", resp_startdatetime, resp_enddatetime";
                $values .= ", ?, ?";
                $arrParams[] = $captureDatetime;
                $arrParams[] = $captureDatetime;
            }

            // Update beatAdherence
            if ($isUnAdherence) {
                $isBeatAdherenceValue = 'No';
                $columns .= ", is_beat_adherence, beat_adherence_reason";
                $values .= ", ?, ?";
                $arrParams[] = $isBeatAdherenceValue;
                $arrParams[] = $reasonForNoBeatAdherence;
            }

            // Add last coordinates
            if ($lt != 0) {
                $columns .= ", last_rec_id, last_rec_lt, last_rec_lg";
                $values .= ", ?, ?, ?";
                $arrParams[] = $lastRecId;
                $arrParams[] = $lt;
                $arrParams[] = $lg;
            }

            // Outlet Orders OR Add Outlet
            if ($activityType === 'Outlet Order' || $activityType === 'Outlet Survey' || $activityType === 'Add Outlet') {
                $columns .= ", total_sales_deliveries, total_sellin_shops, total_other_shops";
                $values .= ", ?, ?, ?";

                // $arrSale = array();
                if ((($activityType === 'Outlet Order' || $activityType === 'Outlet Survey') && $arrData[17] === 'Yes') || ($activityType === 'Add Outlet' && $arrData[17] === 'Yes')) {
                    $arrProductsBought = is_string($arrData[18]) ? json_decode($arrData[18], true) : $arrData[18];
                    $arrSale = getGridDataAsArray($arrProductsBought["ansGrid"], 2, count($arrProductSummaryColumns));
                }

                // insert sales values in correct columns
                $arrSalesValues = array();
                if (isNonEmptyArray($arrProductSummaryColumns) && isNonEmptyArray($arrSale)) {
                    foreach ($arrProductSummaryColumns as $productIndex => $productSummaryColumn) {
                        $columns .= ", $productSummaryColumn";
                        $values .= ", ?";
                        $arrSalesValues[] = isset($arrSale[1][$productIndex]) && floatval($arrSale[1][$productIndex]) ? floatval($arrSale[1][$productIndex]) : 0;
                    }
                }

                if (isset($arrProductsBought['netAmountDiscount'])) {
                    $netAmount = isset($arrProductsBought['netAmountDiscount']['netAmount'])
                        ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['netAmount'])
                        : 0;

                    // Check if discount exists and ensure it defaults to 0 if empty or an empty string
                    $discount = isset($arrProductsBought['netAmountDiscount']['discount'])
                        ? str_replace(['₹', ',', ' '], '', $arrProductsBought['netAmountDiscount']['discount'])
                        : 0;

                    $netAmount = ($netAmount === '' || $netAmount === null) ? 0 : $netAmount;
                    $discount = ($discount === '' || $discount === null) ? 0 : $discount;

                    // Append cleaned values to $cols and $vals
                    $columns .= ", netAmount, discount";
                    $values .= ", '$netAmount', '$discount'";
                }

                $arrParams = array_merge(
                    $arrParams,
                    array(
                        ($activityType === 'Outlet Survey' || $activityType === 'Outlet Order') ? 1 : 0,
                        $arrData[17] === 'Yes' ? 1 : 0,
                        $activityType === 'Add Outlet' ? 1 : 0,
                    ),
                    $arrSalesValues
                );
            }

            addRecord($this->_dbConn, $summaryTable, $columns, $values, $arrParams);
        }
    }

    // private function getBranchWiseProducts($branchId = null, $jsonId = null)
    // {
    //     $branchProductTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];

    //     if ($branchId) {
    //         return $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId];
    //     } else {
    //         // ORDER BY is important
    //         $arrProductSummaryColumns = getRowsColumns($this->_dbConn, $branchProductTable, "branch_id, json_id, summary_column_name", "dstatus = 0 ORDER BY json_id, sort_order");

    //         foreach ($arrProductSummaryColumns as $arrBranchColumns) {
    //             $branchId = $arrBranchColumns[0];
    //             $jsonId = $arrBranchColumns[1];
    //             $summaryColumnName = $arrBranchColumns[2];

    //             if (!isset($this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId])) {
    //                 $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId] = array();
    //             }
    //             $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][] = $summaryColumnName;
    //         }
    //     }
    // }

    private function getBranchWiseProducts($branchId = null, $jsonId = null, $teamType = null)
    {
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        if ($branchId) {
            $teamTypeKey = ($teamType !== null && $teamType !== "") ? $teamType : 'default';

            // Avoid undefined index warning
            $value = $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$teamTypeKey] ?? [];

            return (array)$value;
        } else {
            if ($teamType !== null && $teamType !== "") {
                $arrProductSummaryColumns = getRowsColumns(
                    $this->_dbConn,
                    $branchProductsTable,
                    "branch_id, json_id, summary_column_name",
                    "dstatus = 0 AND team_type = '$teamType' AND branch_id = '$branchId' ORDER BY json_id, sort_order",
                    array(),
                    true
                );

                foreach ($arrProductSummaryColumns as $arrBranchColumns) {
                    $branchId = $arrBranchColumns[0];
                    $jsonId = $arrBranchColumns[1];
                    $summaryColumnName = $arrBranchColumns[2];

                    if (!isset($this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$teamType])) {
                        $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$teamType] = array();
                    }
                    $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$teamType][] = $summaryColumnName;
                }
            } else {
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT branch_id, json_id, summary_column_name, team_type FROM $branchProductsTable  WHERE dstatus = 0 ORDER BY json_id, sort_order";
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $branchId = $rowProduct["branch_id"];
                        $jsonId = $rowProduct["json_id"];
                        $team_type = $rowProduct["team_type"];
                        if (!isset($this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$team_type])) {
                            $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$team_type] = [];
                        }
                        $this->_jsonWiseAndbranchWiseProductsColumns[$jsonId][$branchId][$team_type][] = $rowProduct["summary_column_name"];
                    }
                }
            }
        }
    }
}

$processResponse = new ProcessResponse($dbConn);
$processResponse->processRecords();
