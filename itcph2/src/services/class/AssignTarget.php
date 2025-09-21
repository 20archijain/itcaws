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
        $monthCheck = $this->_data['monthCheck'];
        $firstDay = date('Y-m-01', strtotime('first day of previous month'));
        $lastDay = date('Y-m-t', strtotime('last day of previous month'));

        $prevMonth = date("m", strtotime("first day of previous month"));
        $prevYear  = date("Y", strtotime("first day of previous month"));

        $previousMonthCond = " AND activity_date BETWEEN '$firstDay' AND '$lastDay'";


        $currentFirstDay = date('Y-m-01');
        $currentLastDay = date('Y-m-t');

        $currentMonth = date("m");
        $currentYear  = date("Y");

        $currentMonthCond = " AND activity_date BETWEEN '$currentFirstDay' AND '$currentLastDay'";

        $nextMonth = date("m", strtotime("first day of next month"));
        $nextYear  = date("Y", strtotime("first day of next month"));

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        $where1 = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
            $where1 .= " AND team_id IN $teamList";
        }
        $arrStockProductsList = array();
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products_assign_target as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND a.is_focusbrand != 0 AND a.branch_id != 40 $where ORDER BY a.category_name, a.product_name, a.is_focusbrand limit 3";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        $arrProductColumns = array();
        $arrColumns = array();
        $arrProducts = array();
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $arrProductColumns[] = "SUM({$row["summary_column_name"]}) AS {$row["summary_column_name"]}";
                $arrColumns[] = "{$row["summary_column_name"]}";
                $arrProducts[] = $row["product_name"];

                $arrStockProductsList[] = array(
                    "label" => $row["product_name"],
                    "value" => $row["summary_column_name"],
                    "brand" => $row["category_name"],
                );
            }
        }

        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products_assign_target as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0" .
            " AND a.team_type = 5 AND a.is_focusbrand != 2 AND a.branch_id != 40 $where ORDER BY a.category_name, a.product_name";
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

        $arrProductColumnsAllProduct = array();
        $arrColumnsAllProduct = array();
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $arrProductColumnsAllProduct[] = "SUM({$row3["summary_column_name"]}) AS {$row3["summary_column_name"]}";
                $arrColumnsAllProduct[] = "{$row3["summary_column_name"]}";
            }
        }

        $skuColumn = implode(", ", array_map(function ($c) {
            return "sum($c) as $c";
        }, $arrColumns));

        $skuColumnAllProduct = implode(" + ", $arrColumnsAllProduct);
        $arrTeamList = array();

        $sAction2 = null;
        $iRows2 = 0;
        $sQuery2 = "SELECT DISTINCT b.team_id, b.team_name, b.wd_code FROM tblproject_team as b WHERE b.dstatus = 0 AND b.is_type = 5 AND b.branch_id != 40 $where";
        // echo $sQuery2;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
        if ($iRows2 > 0) {
            while ($row2 = $this->_dbConn->GetData($sAction2)) {
                $team_id = $row2["team_id"];
                if ($monthCheck == 2) {
                    $NextMonthTarget =  $this->getResult("tblassign_target", "$skuColumn", " AND team_id = $team_id AND year = '$nextYear' AND month = '$nextMonth'");


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

                    $existTeam = getRowColumn($this->_dbConn, "tblassign_target", "prod_id", "  dstatus = 0 AND year = $nextYear AND month = $nextMonth AND team_id = $team_id");

                    $existTeamTableCond = 0;
                    if ($existTeam > 0) {
                        $existTeamTableCond = 1;
                    }

                    $arrTeamList[] = array(
                        "label" => $row2["team_name"],
                        "value" => $row2["team_id"],
                        "wd_code" => $row2["wd_code"],
                        "productOneNextMonthTarget" => round((float) $productOneNextMonthTarget, 2),
                        "productTwoNextMonthTarget" => round((float) $productTwoNextMonthTarget, 2),
                        "overAllNextMonthTarget" => round((float) $overAllNextMonthTarget, 2),
                        "existTeamTableCond" => (int) $existTeamTableCond
                    );
                } else {
                    $preMonthTarget =  $this->getResult("tblassign_target", "$skuColumn", " AND team_id = $team_id AND year = '$prevYear' AND month = '$prevMonth'");

                    $currentMonthTarget =  $this->getResult("tblassign_target", "$skuColumn", " AND team_id = $team_id AND year = '$currentYear' AND month = '$currentMonth'");

                    $previousMonthAchieve =  $this->getResult("tblvands_summary", "$skuColumn", " AND team_id = $team_id $previousMonthCond");

                    $currentMonthAchieve =  $this->getResult("tblvands_summary", "$skuColumn", " AND team_id = $team_id $currentMonthCond");

                    $overallPreviousMonthArrAchieve =  $this->getResult("tblvands_summary", "sum($skuColumnAllProduct)", " AND team_id = $team_id $previousMonthCond");

                    $overallCurrentMonthArrAchieve =  $this->getResult("tblvands_summary", "sum($skuColumnAllProduct)", " AND team_id = $team_id $currentMonthCond");

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

                    $year = currentDate("", 'Y');
                    $month = currentDate("", 'm');

                    $existTeam = getRowColumn($this->_dbConn, "tblassign_target", "prod_id", "  dstatus = 0 AND year = $year AND month = $month AND team_id = $team_id");

                    $existTeamTableCond = 0;
                    if ($existTeam > 0) {
                        $existTeamTableCond = 1;
                    }

                    $arrTeamList[] = array(
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
                        "existTeamTableCond" => (int) $existTeamTableCond
                    );
                }
            }
        }

        if ($monthCheck == 2) {
            $tableColumnCondition = false;
        } else {
            $tableColumnCondition = true;
        }

        $arrResult = array(
            "stockProductsList" => $arrTeamList,
            "teamsList" => $arrStockProductsList,
            "product1" => $arrProducts[0],
            "product2" => $arrProducts[1],
            "tableColumnCondition" => $tableColumnCondition,
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);

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
        $arrTeamwiseQty = array();

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

        $arrKeys = array();
        $arrValues = array();

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
                    $arrParams = array($index, $year, $month, $currentDate, $currentDateTime);
                    $cols .= implode(', ', $arrKeys);
                    $vals .= implode(', ', array_fill(0, count($arrKeys), '?'));
                    $arrParams = array_merge($arrParams, $arrValues);
                    $iNum_rows = addRecord($this->_dbConn, "tblassign_target", $cols, $vals, $arrParams);
                }
            }
        }

        if (isset($iNum_rows) && $iNum_rows == 2) {
            $arrMessage = responseMessage(array($GLOBALS['TARGET_ASSIGNED']), 1);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['TARGET_NOT_ASSIGNED']));
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
