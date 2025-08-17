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
        $sQuery = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0 AND a.team_type = 5 AND a.is_focusbrand = 1 $where ORDER BY a.category_name, a.product_name";
        // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        $arrProductColumns = array();
        $arrColumns = array();
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $arrProductColumns[] = "SUM({$row["summary_column_name"]}) AS {$row["summary_column_name"]}";
                $arrColumns[] = "{$row["summary_column_name"]}";

                $arrStockProductsList[] = array(
                    "label" => $row["product_name"],
                    "value" => $row["summary_column_name"],
                    "brand" => $row["category_name"],
                );
            }
        }

        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT DISTINCT a.summary_column_name, a.category_name, a.product_name FROM tblbranch_pickupstock_products as a, tblproject_team as b WHERE a.branch_id = b.branch_id AND a.dstatus = 0 AND a.team_type = 5 $where ORDER BY a.category_name, a.product_name";
        // echo $sQuery3;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);

        $arrProductColumnsAllProduct = array();
        $arrColumnsAllProduct = array();
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $arrProductColumnsAllProduct[] = "SUM({$row3["summary_column_name"]}) AS {$row3["summary_column_name"]}";
                $arrColumnsAllProduct[] = "{$row3["summary_column_name"]}";
            }
        }

        $year = currentDate("", 'Y');
        $month = currentDate("", 'm');

        $firstDay = date('Y-m-01', strtotime('first day of previous month'));
        $lastDay = date('Y-m-t', strtotime('last day of previous month'));

        $previousMonthCond = " AND activity_date BETWEEN '$firstDay' AND '$lastDay'";

        $existTeam = getRowsColumn($this->_dbConn, "tblassign_target", "team_id", "dstatus = 0 AND year = $year AND month = $month $where1");

        $teamAlreadyExistCond = "";
        if ($existTeam && is_array($existTeam)) {
            $team = "'" . implode("', '", $existTeam) . "'";
            $teamAlreadyExistCond .= "AND b.team_id NOT IN ($team)";
        }

        $skuColumn = implode(" + ", $arrColumns);
        $skuColumnAllProduct = implode(" + ", $arrColumnsAllProduct);
        $arrTeamList = array();

        $sAction2 = null;
        $iRows2 = 0;
        $sQuery2 = "SELECT DISTINCT b.team_id, b.team_name, b.wd_code FROM tblproject_team as b WHERE b.dstatus = 0 AND b.is_type = 5 $where $teamAlreadyExistCond";
        // echo $sQuery2;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $sAction2, $iRows2);
        if ($iRows2 > 0) {
            while ($row2 = $this->_dbConn->GetData($sAction2)) {
                $team_id = $row2["team_id"];
                $monthTarget = getRowColumn($this->_dbConn, "tblassign_target", "SUM($skuColumn)", " team_id = $team_id");
                $monthAchieve = $this->getResult("tblvands_summary", "SUM($skuColumnAllProduct)", " AND team_id = $team_id $previousMonthCond");

                if ($monthTarget) {
                    $previousMonthTarget = $monthTarget;
                } else {
                    $previousMonthTarget = 0;
                }
                if ($monthAchieve) {
                    $previousmonthAchieve = $monthAchieve;
                } else {
                    $previousmonthAchieve = 0;
                }
                $arrTeamList[] = array(
                    "label" => $row2["team_name"],
                    "value" => $row2["team_id"],
                    "wd_code" => $row2["wd_code"],
                    "previousMonthTarget" => round($previousMonthTarget, 2),
                    "previousMonthAchieve" => round($previousmonthAchieve, 2),
                );
            }
        }

        $arrResult = array(
            "stockProductsList" => $arrTeamList,
            "teamsList" => $arrStockProductsList,
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);

        echo json_encode($arrMessage);
    }


    final public function getResult($table, $products, $where)
    {
        $sAction3 = null;
        $iRows3 = 0;
        $sQuery3 = "SELECT $products as total from $table WHERE dstatus = 0 $where ";
        // echo $sQuery3;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);
        if ($iRows3 > 0) {
            while ($row3 = $this->_dbConn->GetData($sAction3)) {
                $total = $row3['total'];
            }
        }

        return $total;
    }

    final public function addData()
    {
        $arrQty = getFormData($this->_data, "qty");
        $arrTeamwiseQty = array();
        $year = currentDate("", "Y");
        $month = currentDate("", "m");
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
