<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class AssignTarget
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }

    final public function getData()
    {
        if ($this->_iUserId == 1) {
            $tempBranchCond = " AND a.branch_id = 40";
            $tempTeamTableBranchCond = " AND b.branch_id = 40";
        } else {
            $tempBranchCond = "";
            $tempTeamTableBranchCond = "";
        }
        $firstDay = date('Y-m-01', strtotime('first day of previous month'));
        $lastDay = date('Y-m-t', strtotime('last day of previous month'));
        $previousMonthCond = " AND activity_date BETWEEN '$firstDay' AND '$lastDay'";

        $prevMonth = date("m", strtotime("first day of previous month"));
        $prevYear  = date("Y", strtotime("first day of previous month"));

        $currentFirstDay = date('Y-m-01');
        $currentLastDay = date('Y-m-t');
        $currentMonthCond = " AND activity_date BETWEEN '$currentFirstDay' AND '$currentLastDay'";

        $currentMonth = date("m");
        $currentYear  = date("Y");
        $currentDate  = date("d");

        $nextMonth = date("m", strtotime("first day of next month"));
        $nextYear  = date("Y", strtotime("first day of next month"));

        $teamList = $this->_arrAccessInfo["user_teams"];

        // echo "<pre>";
        // print_r($teamList);die;
        $where = "";
        $where1 = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
            $where1 .= " AND team_id IN $teamList";
        }

        if ($currentDate > 21) {
            $filledYear = $nextYear;
            $filledMonth = $nextMonth;
        } else {
            $filledYear = $currentYear;
            $filledMonth = $currentMonth;
        }

        $arrStockProductsList = [];
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_products_month_wise as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND a.is_focusbrand != 0 AND a.month = '$filledMonth' AND a.year = '$filledYear' $tempBranchCond $where ORDER BY a.is_focusbrand limit 3";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        $arrProductColumns = [];
        $arrColumns = [];
        $arrProducts = [];
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $arrProductColumns[] = "SUM({$row["summary_column_name"]}) AS {$row["summary_column_name"]}";
                $arrColumns[] = "{$row["summary_column_name"]}";
                $arrProducts[] = $row["product_name"];

                $arrStockProductsList[] = [
                    "label" => $row["product_name"],
                    "value" => $row["summary_column_name"],
                    "brand" => $row["category_name"],
                ];
            }
        }

        // $skuColumn = implode(", ", array_map(function ($c) {
        //     return "sum($c) as $c";
        // }, $arrColumns));

        $sActionPrevious = null;
        $iRowsPrevious = 0;
        $sQueryPrevious = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_products_month_wise as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND month = '$prevMonth' AND year = '$prevYear' $tempBranchCond $where ORDER BY a.is_focusbrand limit 3";
        // echo $sQueryPrevious;die;
        $this->_dbConn->ExecuteSelectQuery($sQueryPrevious, $sActionPrevious, $iRowsPrevious);

        $arrProductColumnsPrevious = [];
        $arrColumnsPrevious = [];
        $arrProductsPrevious = [];
        if ($iRowsPrevious > 0) {
            while ($rowPrevious = $this->_dbConn->GetData($sActionPrevious)) {
                $arrProductColumnsPrevious[] = "SUM({$rowPrevious["summary_column_name"]}) AS {$rowPrevious["summary_column_name"]}";
                $arrColumnsPrevious[] = "{$rowPrevious["summary_column_name"]}";
                $arrProductsPrevious[] = $rowPrevious["product_name"];
            }
        }

        $skuColumnPrevious = implode(", ", array_map(function ($c) {
            return "sum($c) as $c";
        }, $arrColumnsPrevious));

        // echo $skuColumnPrevious;die;

        $sActionCurrent = null;
        $iRowsCurrent = 0;
        $sQueryCurrent = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_products_month_wise as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND month = '$currentMonth' AND year = '$currentYear' $tempBranchCond $where ORDER BY a.is_focusbrand limit 3";
        // echo $sQueryCurrent;die;
        $this->_dbConn->ExecuteSelectQuery($sQueryCurrent, $sActionCurrent, $iRowsCurrent);

        $arrProductColumnsCurrent = [];
        $arrColumnsCurrent = [];
        $arrProductsCurrent = [];
        if ($iRowsCurrent > 0) {
            while ($rowCurrent = $this->_dbConn->GetData($sActionCurrent)) {
                $arrProductColumnsCurrent[] = "SUM({$rowCurrent["summary_column_name"]}) AS {$rowCurrent["summary_column_name"]}";
                $arrColumnsCurrent[] = "{$rowCurrent["summary_column_name"]}";
                $arrProductsCurrent[] = $rowCurrent["product_name"];
            }
        }

        $skuColumnCurrent = implode(", ", array_map(function ($c) {
            return "sum($c) as $c";
        }, $arrColumnsCurrent));

        $sActionNext = null;
        $iRowsNext = 0;
        $sQueryNext = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_products_month_wise as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND month = '$nextMonth' AND year = '$nextYear' $tempBranchCond $where ORDER BY a.is_focusbrand limit 3";
        // echo $sQueryNext;die;
        $this->_dbConn->ExecuteSelectQuery($sQueryNext, $sActionNext, $iRowsNext);

        $arrProductColumnsNext = [];
        $arrColumnsNext = [];
        $arrProductsNext = [];
        if ($iRowsNext > 0) {
            while ($rowNext = $this->_dbConn->GetData($sActionNext)) {
                $arrProductColumnsNext[] = "SUM({$rowNext["summary_column_name"]}) AS {$rowNext["summary_column_name"]}";
                $arrColumnsNext[] = "{$rowNext["summary_column_name"]}";
                $arrProductsNext[] = $rowNext["product_name"];
            }
        }

        $skuColumnNext = implode(", ", array_map(function ($c) {
            return "sum($c) as $c";
        }, $arrColumnsNext));

        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 $tempBranchCond $where ORDER BY a.category_name, a.product_name";
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

        $arrProductColumnsAllProduct = [];
        $arrColumnsAllProduct = [];
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $arrProductColumnsAllProduct[] = "SUM({$row3["summary_column_name"]}) AS {$row3["summary_column_name"]}";
                $arrColumnsAllProduct[] = "{$row3["summary_column_name"]}";
            }
        }

        $skuColumnAllProduct = implode(" + ", $arrColumnsAllProduct);
        $arrTeamList = [];

        $sAction2 = null;
        $iRows2 = 0;
        $sQuery2 = "SELECT DISTINCT b.team_id, b.team_name, b.wd_code FROM tblproject_team as b WHERE b.dstatus = 0 AND b.is_type = 5 $tempTeamTableBranchCond $where";
        // echo $sQuery2;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
        if ($iRows2 > 0) {
            while ($row2 = $this->_dbConn->GetData($sAction2)) {
                $team_id = $row2["team_id"];

                if (isset($skuColumnPrevious) && $skuColumnPrevious) {
                    $preMonthTarget =  $this->getResult("tblassign_target", "$skuColumnPrevious", " AND team_id = $team_id AND year = '$prevYear' AND month = '$prevMonth'");
                    $previousMonthAchieve =  $this->getResult("tblvands_summary", "$skuColumnPrevious", " AND team_id = $team_id $previousMonthCond");
                }

                if (isset($skuColumnCurrent) && $skuColumnCurrent) {
                    $currentMonthTarget =  $this->getResult("tblassign_target", "$skuColumnCurrent", " AND team_id = $team_id AND year = '$currentYear' AND month = '$currentMonth'");

                    $currentMonthAchieve =  $this->getResult("tblvands_summary", "$skuColumnCurrent", " AND team_id = $team_id $currentMonthCond");
                }

                if (isset($skuColumnAllProduct) && $skuColumnAllProduct) {
                    $overallPreviousMonthArrAchieve =  $this->getResult("tblvands_summary", "sum($skuColumnAllProduct)", " AND team_id = $team_id $previousMonthCond");

                    $overallCurrentMonthArrAchieve =  $this->getResult("tblvands_summary", "sum($skuColumnAllProduct)", " AND team_id = $team_id $currentMonthCond");
                }

                if (isset($preMonthTarget[0]) && $preMonthTarget[0]) {
                    $productOnePreMonthTarget = $preMonthTarget[0];
                } else {
                    $productOnePreMonthTarget = 0;
                }

                if (isset($preMonthTarget[1]) && $preMonthTarget[1]) {
                    $productTwoPreMonthTarget = $preMonthTarget[1];
                } else {
                    $productTwoPreMonthTarget = 0;
                }

                if (isset($preMonthTarget[2]) && $preMonthTarget[2]) {
                    $overAllPreMonthTarget = $preMonthTarget[2];
                } else {
                    $overAllPreMonthTarget = 0;
                }

                if (isset($currentMonthTarget[0]) && $currentMonthTarget[0]) {
                    $productOneCurrentMonthTarget = $currentMonthTarget[0];
                } else {
                    $productOneCurrentMonthTarget = 0;
                }

                if (isset($currentMonthTarget[1]) && $currentMonthTarget[1]) {
                    $productTwoCurrentMonthTarget = $currentMonthTarget[1];
                } else {
                    $productTwoCurrentMonthTarget = 0;
                }

                if (isset($currentMonthTarget[2]) && $currentMonthTarget[2]) {
                    $overallCurrentMonthTarget = $currentMonthTarget[2];
                } else {
                    $overallCurrentMonthTarget = 0;
                }

                //Achieve

                if (isset($previousMonthAchieve[0]) && $previousMonthAchieve[0]) {
                    $productOnepreviousMonthAchieve = $previousMonthAchieve[0];
                } else {
                    $productOnepreviousMonthAchieve = 0;
                }

                if (isset($previousMonthAchieve[1]) && $previousMonthAchieve[1]) {
                    $productTwopreviousMonthAchieve = $previousMonthAchieve[1];
                } else {
                    $productTwopreviousMonthAchieve = 0;
                }

                if (isset($currentMonthAchieve[0]) && $currentMonthAchieve[0]) {
                    $productOnecurrentMonthAchieve = $currentMonthAchieve[0];
                } else {
                    $productOnecurrentMonthAchieve = 0;
                }

                if (isset($currentMonthAchieve[1]) && $currentMonthAchieve[1]) {
                    $productTwocurrentMonthAchieve = $currentMonthAchieve[1];
                } else {
                    $productTwocurrentMonthAchieve = 0;
                }

                if (isset($overallPreviousMonthArrAchieve[0]) && $overallPreviousMonthArrAchieve[0]) {
                    $overallPreviousMonthAchieve = $overallPreviousMonthArrAchieve[0];
                } else {
                    $overallPreviousMonthAchieve = 0;
                }

                if (isset($overallCurrentMonthArrAchieve[0]) && $overallCurrentMonthArrAchieve[0]) {
                    $overallCurrentMonthAchieve = $overallCurrentMonthArrAchieve[0];
                } else {
                    $overallCurrentMonthAchieve = 0;
                }

                // $year = currentDate("", 'Y');
                // $month = currentDate("", 'm');

                // $existTeam = getRowColumn($this->_dbConn, "tblassign_target", "prod_id", "  dstatus = 0 AND year = $year AND month = $month AND team_id = $team_id");

                // $existTeamTableCond = 0;
                // if ($existTeam > 0) {
                //     $existTeamTableCond = 1;
                // }

                if ($currentDate > 21 && isset($skuColumnNext) && $skuColumnNext) {
                    $NextMonthTarget =  $this->getResult("tblassign_target", "$skuColumnNext", " AND team_id = $team_id AND year = '$nextYear' AND month = '$nextMonth'");
                }

                if (isset($NextMonthTarget[0]) && $NextMonthTarget[0]) {
                    $productOneNextMonthTarget = $NextMonthTarget[0];
                } else {
                    $productOneNextMonthTarget = 0;
                }

                if (isset($NextMonthTarget[1]) && $NextMonthTarget[1]) {
                    $productTwoNextMonthTarget = $NextMonthTarget[1];
                } else {
                    $productTwoNextMonthTarget = 0;
                }

                if (isset($NextMonthTarget[2]) && $NextMonthTarget[2]) {
                    $overAllNextMonthTarget = $NextMonthTarget[2];
                } else {
                    $overAllNextMonthTarget = 0;
                }

                $arrTeamList[] = [
                    "label" => $row2["team_name"],
                    "value" => $row2["team_id"],
                    "wd_code" => $row2["wd_code"],
                    "productOnePreMonthTarget" => round((float) $productOnePreMonthTarget, 2),
                    "productOnepreviousMonthAchieve" => round((float) $productOnepreviousMonthAchieve, 2),
                    "productTwoPreMonthTarget" => round((float) $productTwoPreMonthTarget, 2),
                    "productTwopreviousMonthAchieve" => round((float) $productTwopreviousMonthAchieve, 2),
                    "productOneCurrentMonthTarget" => round((float) $productOneCurrentMonthTarget, 2),
                    "productOnecurrentMonthAchieve" => round((float) $productOnecurrentMonthAchieve, 2),
                    "productTwoCurrentMonthTarget" => round((float) $productTwoCurrentMonthTarget, 2),
                    "productTwocurrentMonthAchieve" => round((float) $productTwocurrentMonthAchieve, 2),
                    "overAllPreMonthTarget" => round((float) $overAllPreMonthTarget, 2),
                    "overallPreviousMonthAchieve" => round((float) $overallPreviousMonthAchieve, 2),
                    "overallCurrentMonthTarget" => round((float) $overallCurrentMonthTarget, 2),
                    "overallCurrentMonthAchieve" => round((float) $overallCurrentMonthAchieve, 2),
                    "productOneNextMonthTarget" => round((float) $productOneNextMonthTarget, 2),
                    "productTwoNextMonthTarget" => round((float) $productTwoNextMonthTarget, 2),
                    "overAllNextMonthTarget" => round((float) $overAllNextMonthTarget, 2),
                    "existTeamTableCond" => (int) 0
                ];
            }
        }

        $arrResult = [
            "stockProductsList" => $arrTeamList,
            "teamsList" => $arrStockProductsList,
            "previousMonthProduct1" => $arrProductsPrevious[0] ?? "Not Confirmed",
            "previousMonthProduct2" => $arrProductsPrevious[1] ?? "Not Confirmed",
            "product1" => $arrProductsCurrent[0] ?? "Not Confirmed",
            "product2" => $arrProductsCurrent[1] ?? "Not Confirmed",
            "nextMonthProduct1" => $arrProductsNext[0] ?? "Not Confirmed",
            "nextMonthProduct2" => $arrProductsNext[1] ?? "Not Confirmed",
        ];
        $arrMessage = responseMessage([], 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    final public function getResult($table, $products, $where)
    {
        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT $products from $table WHERE dstatus = 0 $where ";
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);
        $result = "";
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $result = array_values($row3);  // push full row (associative array) into result
            }
        }
        return $result;
    }

    final public function addData()
    {
        $arrQty = getFormData($this->_data, "qty");
        $monthCheck = $this->_data['monthCheck'];
        if ($monthCheck == 2) {
            $year  = date("Y", strtotime("first day of next month"));
            $month = date("m", strtotime("first day of next month"));
        } else {
            $year = currentDate("", "Y");
            $month = currentDate("", "m");
        }
        $arrTeamwiseQty = [];

        $currentDate = currentDate();
        $currentDateTime = currentDateTime();

        // foreach ($arrQty as $arrTeamProductwiseQty) {
        //     foreach ($arrTeamProductwiseQty as $sTeamProduct => $qty) {
        //         $arrTeamProduct = explode("-", $sTeamProduct);
        //         $teamId = $arrTeamProduct[1];
        //         $productSummaryColumn = $arrTeamProduct[2];

        //         if (!isset($arrTeamwiseQty[$teamId])) {
        //             $arrTeamwiseQty[$teamId] = array();
        //         }
        //         if (is_numeric($qty)) {
        //             $arrTeamwiseQty[$teamId][$productSummaryColumn] = $qty;
        //         } elseif ($qty == "") {
        //             $arrTeamwiseQty[$teamId][$productSummaryColumn] = 0;
        //         }
        //     }
        // }

        foreach ($arrQty as $arrTeamProductwiseQty) {
            foreach ($arrTeamProductwiseQty as $sTeamProduct => $qty) {
                $arrTeamProduct = explode("-", $sTeamProduct);
                $productSummaryColumn = $arrTeamProduct[1]; // e.g., total_sale_product37
                $teamId = $arrTeamProduct[2];               // e.g., 19077

                if (!isset($arrTeamwiseQty[$teamId])) {
                    $arrTeamwiseQty[$teamId] = [];
                }

                if (is_numeric($qty)) {
                    $arrTeamwiseQty[$teamId][$productSummaryColumn] = $qty;
                }
                // elseif ($qty === "") {
                //     $arrTeamwiseQty[$teamId][$productSummaryColumn] = 0;
                // }
            }
        }

        $arrKeys = [];
        $arrValues = [];

        // Loop through all indexes (1, 2, 3, 4)
        foreach ($arrTeamwiseQty as $index => $items) {
            if (is_array($items)) {
                $arrKeys = array_keys($items);
                $arrValues = array_values($items);
            }

            $existTeamProdId = getRowColumn($this->_dbConn, "tblassign_target", "prod_id", " dstatus = 0 AND year = $year AND month = $month AND team_id = $index");

            if ($existTeamProdId == 0) {
                if (isset($arrKeys) && isset($arrValues) && $arrKeys && $arrValues) {
                    $cols = "team_id, year, month, rcd, rdt, ";
                    $vals = "?, ?, ?, ?, ?, ";
                    $arrParams = [$index, $year, $month, $currentDate, $currentDateTime];
                    $cols .= implode(', ', $arrKeys);
                    $vals .= implode(', ', array_fill(0, count($arrKeys), '?'));
                    $arrParams = array_merge($arrParams, $arrValues);
                    $iNum_rows = addRecord($this->_dbConn, "tblassign_target", $cols, $vals, $arrParams);
                }
            } else {
                if (isset($arrKeys) && isset($arrValues) && $arrKeys && $arrValues) {
                    $cols = "rcd = ?, rdt = ?, ";
                    $arrParams = [$currentDate, $currentDateTime];

                    $cols .= implode(", ", array_map(function ($v) {
                        return "$v = ?";
                    }, $arrKeys));
                    // $cols .= implode(' = ?, ', $arrKeys);
                    $arrParams = array_merge($arrParams, $arrValues);
                    $iUpdate = updateRecord($this->_dbConn, "tblassign_target", $cols, "prod_id = '$existTeamProdId'", $arrParams);
                }
            }
        }

        if (isset($iNum_rows) && $iNum_rows == 2 || isset($iUpdate) && $iUpdate == 1) {
            $arrMessage = responseMessage([$GLOBALS['TARGET_ASSIGNED']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['TARGET_NOT_ASSIGNED']]);
        }
        echo json_encode($arrMessage);
    }

    // final public function addData()
    // {
    //     $arrQty = getFormData($this->_data, "qty");
    //     $month = getFormData($this->_data, "month");

    //     $arrTeamwiseQty = array();
    //     $year = currentDate("", "Y");
    //     $currentDate = currentDate();
    //     $currentDateTime = currentDateTime();

    //     foreach ($arrQty as $arrTeamProductwiseQty) {
    //         foreach ($arrTeamProductwiseQty as $sTeamProduct => $qty) {
    //             $arrTeamProduct = explode("-", $sTeamProduct);
    //             $teamId = $arrTeamProduct[1];
    //             $productSummaryColumn = $arrTeamProduct[2];

    //             if (!isset($arrTeamwiseQty[$teamId])) {
    //                 $arrTeamwiseQty[$teamId] = array();
    //             }
    //             if (is_numeric($qty)) {
    //                 $arrTeamwiseQty[$teamId][$productSummaryColumn] = $qty;
    //             } elseif ($qty == "") {
    //                 $arrTeamwiseQty[$teamId][$productSummaryColumn] = 0;
    //             }
    //         }
    //     }

    //     $arrKeys = array();
    //     $arrValues = array();

    //     // Loop through all indexes (1, 2, 3, 4)
    //     foreach ($arrTeamwiseQty as $index => $items) {
    //         if (is_array($items)) {
    //             $arrKeys = array_keys($items);
    //             $arrValues = array_values($items);
    //         }

    //         if (isset($arrKeys) && isset($arrValues) && $arrKeys && $arrValues) {
    //             $cols = "team_id, year, month, rcd, rdt, ";
    //             $vals = "?, ?, ?, ?, ?, ";
    //             $arrParams = array($index, $year, $month, $currentDate, $currentDateTime);
    //             $cols .= implode(', ', $arrKeys);
    //             $vals .= implode(', ', array_fill(0, count($arrKeys), '?'));
    //             $arrParams = array_merge($arrParams, $arrValues);
    //             $iNum_rows = addRecord($this->_dbConn, "tblassign_target", $cols, $vals, $arrParams);
    //         }
    //     }

    //     if (isset($iNum_rows) && $iNum_rows == 2) {
    //         $arrMessage = responseMessage(array($GLOBALS['TARGET_ASSIGNED']), 1);
    //     } else {
    //         $arrMessage = responseMessage(array($GLOBALS['TARGET_NOT_ASSIGNED']));
    //     }
    //     echo json_encode($arrMessage);
    // }
}
