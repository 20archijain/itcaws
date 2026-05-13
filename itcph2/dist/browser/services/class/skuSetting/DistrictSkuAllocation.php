<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class DistrictSkuAllocation
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
    }

    //SEARCH CONDITION
    private function getCondition()
    {
        // filter query
        $searchCond = getFilterResult(
            $this->_data["searchbar"] ?? $this->_data,
            [
                "branch" => ["a.branch_id", 0, true, true],
                "dsType" => ["a.team_type", 1],
            ],
            $this->_dbConn
        );

        return $searchCond;
    }

    // MASTER DATA
    final public function getViewSKUData()
    {
        $submittedData = $this->getSubmittedData();
        $selectedData = $this->getSelectedRecord();
        if (isset($submittedData[0]) && $submittedData[0]) {
            $arrResult = [
                "productList" => [],
                "statusFlag" => true,
                "submittedList" => $submittedData[1],
                "isDspmList" => [],
                "isFocusList" => [],
                "selectedDataList" => [],
            ];
        } else {
            $arrResult = [
                "productList" => $this->getBranchProduct(),
                "statusFlag" => false,
                "submittedList" => [],
                "isDspmList" => $selectedData[0],
                "isFocusList" => $selectedData[1],
                "selectedDataList" => $selectedData[2],
            ];
        }
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSubmittedData()
    {
        $arrData = [];
        $region = $this->_data['region'];
        $teamType = $this->_data['teamType'];
        $year  = date('Y', strtotime('+1 month'));
        $month = date('m', strtotime('+1 month'));

        $foundRecord = false;
        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select category_name, product_name, dspm_focus, is_focusbrand from tblbranch_pickupstock_products_allocation where dstatus = 0 AND month = '$month' AND year = '$year' AND branch_id = '$region' AND team_type = '$teamType' AND filled_by_district = 1";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            $foundRecord = true;
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $branchData = getRowColumns($this->_dbConn, "tblbranch", "main_branch, branch_name", " branch_id = '$region'");
                $arrData[] = [
                    "branch" => $branchData[0],
                    "region" => $branchData[1],
                    "category_name" => $row['category_name'],
                    "product_name" => $row['product_name'],
                    "dspm_focus" => $row['dspm_focus'] == 1 ? "Yes" : "",
                    "is_focusbrand" => $row['is_focusbrand'] == 1 ? "Yes" : "",
                ];
            }
        }

        return [$foundRecord, $arrData];
    }

    final public function getSelectedRecord()
    {
        $region = $this->_data['region'];
        $teamType = $this->_data['teamType'];
        $year  = date('Y', strtotime('+1 month'));
        $month = date('m', strtotime('+1 month'));

        $arrDspm = [];
        $arrIsFocus = [];
        $arrData = [];
        $rsAction = null;
        $iActionRows = 0;
        $query = "select category_name, product_name, dspm_focus, is_focusbrand, summary_column_name from tblbranch_pickupstock_products_allocation where dstatus = 0 AND month = '$month' AND year = '$year' AND branch_id = '$region' AND team_type = '$teamType' AND filled_by_branch = 1";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                if (isset($row['dspm_focus']) && $row['dspm_focus']) {
                    $arrDspm[$row['summary_column_name']] = true;
                }
                if (isset($row['is_focusbrand']) && $row['is_focusbrand']) {
                    $arrIsFocus[$row['summary_column_name']] = true;
                }
                $arrData[] = [
                    "category" => $row['category_name'],
                    "name" => $row['product_name'],
                    "id" => $row['summary_column_name'],
                ];
            }
        }

        return [$arrDspm, $arrIsFocus, $arrData];
    }

    final public function getDefaultData()
    {
        $currentDate = currentDate("", "d");
        if ($currentDate > 21) {
            $skuDefaultAllocation = true;
        } else {
            $skuDefaultAllocation = true;
        }
        $arrResult = [
            "mainBranchList" => $this->getMainBranchList(),
            "skuDefaultAllocation" => $skuDefaultAllocation,
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getBranchProduct()
    {
        $arrData = [];
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT distinct category_name, product_name, summary_column_name from tblbranch_pickupstock_products" .
            " Where dstatus = 0 order by category_name";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $arrData[] = [
                    "category" => $row['category_name'],
                    "label" => $row['product_name'],
                    "value" => $row['summary_column_name'],
                ];
            }
        }

        return $arrData;
    }

    //CSV Report
    final public function downloadMasterData()
    {
        $dwnCond = $this->getCondition();
        $branchPickupTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rcd");

        // Create header
        $header = [];
        $header[] = [
            "District",
            "Branch",
            "Region",
            "DS Type",
            "Focus Brand",
            "SKU Category",
            "SKU Name",
            "Base Rate (M)"
        ];

        $arrDataHolder = [];
        $sAction = null;
        $iRows = 0;
        $types = [0 => "VAN DS", 1 => "Niche", 2 => "Town SWD", 3 => "Hybrid", 4 => "SCP", 5 => "NPSR"];
        $focusType = [0 => "No", 1 => "Yes"];

        $sQuery = "SELECT a.branch_id, a.team_type, a.is_focusbrand, a.category_name, a.product_name, a.net_rate, a.rcd, b.district, b.branch_name, b.main_branch FROM $branchPickupTable AS a, $branchTable AS b WHERE a.dstatus = 0 AND a.branch_id = b.branch_id $dwnCond $sOrderCond";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $focusBrand = $arrData["is_focusbrand"];
                $dsType = $arrData["team_type"];
                $district = $arrData["district"];
                $mainBranch = $arrData["main_branch"];
                $branchName = $arrData["branch_name"];
                $categoryName = $arrData["category_name"];
                $prodName = $arrData["product_name"];
                $prodRate = $arrData["net_rate"];

                $arrDataHolder[] = [
                    cleanCSVValue($district),
                    cleanCSVValue($mainBranch),
                    cleanCSVValue($branchName),
                    cleanCSVValue($types[$dsType]),
                    cleanCSVValue($focusType[$focusBrand]),
                    cleanCSVValue($categoryName),
                    cleanCSVValue($prodName),
                    cleanCSVValue($prodRate)
                ];
            }
        }

        $arrResult = formatDownloadData("Active_SKU", [$header], $arrDataHolder);
        $arrMessage = responseMessage([$GLOBALS['DWN_CSV_SUCCESS']], 1, $arrResult);
        echo json_encode($arrMessage);
    }

    final public function editData()
    {
        $focusBrand = getFormData($this->_data, "focusBrand");
        $recId = getFormData($this->_data, "recId");

        if ($focusBrand == "Yes") {
            $isFocus = "1";
        } else {
            $isFocus = "0";
        }
        $cols = "is_focusbrand = ?";
        $arrParams = [$isFocus, $recId];

        $iStatus = updateRecord($this->_dbConn, "tblbranch_pickupstock_products", $cols, " rec_id = ?", $arrParams);

        if ($iStatus === 1) {
            $arrMessage = responseMessage([$GLOBALS['DATA_UPDATED_SUCCESSFULL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['DATA_NOT_UPDATED']]);
        }
        echo json_encode($arrMessage);
    }

    final public function getMainBranchList($cond = "")
    {
        $arrData = [];
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all",
        // );

        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct a.main_branch from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where order by a.main_branch";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = [
                    "label" => $row['main_branch'],
                    "value" => $row['main_branch'],
                ];
            }
        }

        return $arrData;
    }

    final public function getRegionList($cond = "")
    {
        $arrData = [];
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        // echo $where;die;
        $rsAction = null;
        $iActionRows = 0;
        $query = "select distinct a.branch_name, a.branch_id from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND a.dstatus = 0 AND b.dstatus = 0 $where order by a.main_branch";
        // echo $query;die;
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $arrData[] = [
                    "label" => $row['branch_name'],
                    "value" => $row['branch_id'],
                ];
            }
        }

        return $arrData;
    }

    final public function getDsTypeList($cond = "")
    {
        $arrData = [];
        // $arrData[] = array(
        //     "label" => "All",
        //     "value" => "all"
        // );
        $teamList = $this->_arrAccessInfo["user_teams"];
        $where = "";
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        if ($cond) {
            $where .= $cond;
        }

        $rsAction = null;
        $iActionRows = 0;
        $query = "select Distinct b.is_type from tblbranch as a, tblproject_team as b where a.branch_id = b.branch_id AND b.is_type != 4 AND a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = '99' $where order by b.is_type";
        $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $teamType = "";
                if ($row['is_type'] == 0) {
                    $teamType = "Van DS";
                } elseif ($row['is_type'] == 1) {
                    $teamType = "Niche";
                } elseif ($row['is_type'] == 2) {
                    $teamType = "Town SWD";
                } elseif ($row['is_type'] == 3) {
                    $teamType = "Hybrid";
                } elseif ($row['is_type'] == 5) {
                    $teamType = "NPSR";
                }
                $arrData[] = [
                    "label" => $teamType,
                    "value" => (string)$row['is_type']
                ];
            }
        }

        return $arrData;
    }

    final public function getRegion()
    {
        $mainBranch = $this->_data['mainBranch'];
        $mainBranchCond = "";
        if (!empty($mainBranch)) {
            if (!is_array($mainBranch)) {
                $mainBranch = [$mainBranch];
            }
            if (in_array('all', $mainBranch)) {
                $mainBranchCond = ""; // No condition for 'all'
            } else {
                $mainBranch = "'" . implode("','", $mainBranch) . "'";
                $mainBranchCond = " AND a.main_branch IN ($mainBranch)";
            }

            $arrResult = [
                "regionList" => $this->getRegionList($mainBranchCond),
            ];
        } else {
            $arrResult = [
                "regionList" => "",
            ];
        }
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $region = $this->_data['region'];
        $regionCond = "";
        if ($region) {
            if ($region) {
                if (!is_array($region)) {
                    $region = [$region];
                }
                if (in_array('all', $region)) {
                    $regionCond = ""; // No condition for 'all'
                } else {
                    $region = "'" . implode("','", $region) . "'";
                    $regionCond = " AND b.branch_id IN ($region)";
                }
            }
            $arrResult = [
                "teamTypeList" => $this->getDsTypeList($regionCond),
            ];
        } else {
            $arrResult = [
                "teamTypeList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function submitData()
    {
        $selectedProducts = getFormData($this->_data, "selectedProducts");
        $formData = getFormData($this->_data, "formData");
        $region = $formData['region'];
        $teamType = $formData['teamType'];
        $year  = date('Y', strtotime('+1 month'));
        $month = date('m', strtotime('+1 month'));
        $rcd = currentDate();
        $rdt = currentDateTime();
        $user = $this->_iUserId;

        $this->_dbConn->BeginTransaction();
        foreach ($selectedProducts as $record) {
            $categoryName = $record['category'];
            $product_name = $record['name'];
            $summary_column_name = $record['id'];
            $dspm_focus = isset($record['dspmBrand']) && $record['dspmBrand'] ? 1 : 0;
            $is_focusbrand = isset($record['isFocusBrand']) && $record['isFocusBrand'] ? 1 : 0;
            $rec_id = getRowColumn($this->_dbConn, "tblbranch_pickupstock_products_allocation", "rec_id", " year = '$year' AND month = '$month' AND summary_column_name = '$summary_column_name' AND category_name = '$categoryName' AND product_name = '$product_name'");

            if ($rec_id > 0) {
                updateRecord($this->_dbConn, "tblbranch_pickupstock_products_allocation", "dspm_focus = ?, is_focusbrand = ?, filled_by_district = ?, user_id = ?", "rec_id = $rec_id", [$dspm_focus, $is_focusbrand, 1, $user]);

                $arrStatus[] = 2;
            } else {
                $cols = "month, year, branch_id, team_type, dspm_focus, is_focusbrand, category_name, product_name, summary_column_name, filled_by_district, rcd, rdt, user_id";
                $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                $arrParams = [$month, $year, $region, $teamType, $dspm_focus, $is_focusbrand, $categoryName, $product_name, $summary_column_name, 1, $rcd, $rdt, $user];

                $iStatus = addRecord($this->_dbConn, "tblbranch_pickupstock_products_allocation", $cols, $vals, $arrParams);

                $arrStatus[] = $iStatus;
            }
        }

        if (in_array(0, $arrStatus)) {
            $this->_dbConn->RollbackTransaction();
            $arrMessage = responseMessage([$GLOBALS['PRODUCT_NOT_ADDED']]);
        } else {
            // All success, commit
            $this->_dbConn->CommitTransaction();
            $arrMessage = responseMessage([$GLOBALS['PRODUCT_ADDED']], 1);
        }

        echo json_encode($arrMessage);
    }
}
