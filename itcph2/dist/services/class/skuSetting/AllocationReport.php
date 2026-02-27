<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class AllocationReport
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

    final public function getSelectedRecord()
    {
        $region = $this->_data['region'];
        $teamType = $this->_data['teamType'];
        $year = currentDate("", 'Y');
        $month = currentDate("", 'm');

        $arrDspm = array();
        $arrIsFocus = array();
        $arrData = array();
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
                $arrData[] = array(
                    "category" => $row['category_name'],
                    "name" => $row['product_name'],
                    "id" => $row['summary_column_name'],
                );
            }
        }

        return array($arrDspm, $arrIsFocus, $arrData);
    }

    final public function getDefaultData()
    {
        $arrResult = array(
            "tableData" => $this->getTable(),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTable()
    {
        // echo "<pre>";
        // print_r($getTable);die;
        $arrDistrictWiseData = array();
        $sAction = null;
        $iRows = 0;
        $year  = date('Y', strtotime('+1 month'));
        $month = date('m', strtotime('+1 month'));

        $sQuery = "SELECT district, main_branch, branch_id from tblbranch" .
            " Where dstatus = 0 order by main_branch";
            // echo $sQuery;die;
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);
        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $district = $row['district'];
                $main_branch = $row['main_branch'];
                $branch_id = $row['branch_id'];

                $dataStatus = getRowColumns($this->_dbConn, "tblbranch_pickupstock_products_allocation", "filled_by_branch, filled_by_district, filled_by_ho", " month = '$month' AND year = '$year' AND branch_id = '$branch_id'");

                $branchStatus = isset($dataStatus[0]) && $dataStatus[0] ? 1 : 0;
                $districtStatus = isset($dataStatus[1]) && $dataStatus[1] ? 1 : 0;
                $hoStatus = isset($dataStatus[2]) && $dataStatus[2] ? 1 : 0;

                if ($hoStatus == 1 && $districtStatus == 1 && $branchStatus == 1) {
                    $showStatus = "HO Level";
                } elseif($hoStatus == 0 && $districtStatus == 1 && $branchStatus == 1){
                    $showStatus = "District Level";
                } elseif($hoStatus == 0 && $districtStatus == 0 && $branchStatus == 1){
                    $showStatus = "Branch Level";
                }else{
                    $showStatus = "Allocation Not Set yet";
                }


                $dataSubmitted = getRowColumn($this->_dbConn, "tblbranch_pickupstock_products_allocation", "rec_id", " month = '$month' AND year = '$year' AND branch_id = '$branch_id' AND filled_by_ho = 1");

                if (isset($dataSubmitted) && $dataSubmitted) {
                    $setValue = 1;
                } else {
                    $setValue = 0;
                }

                $dataSubmittedDspm = getRowColumn($this->_dbConn, "tblbranch_pickupstock_products_allocation", "rec_id", "month = '$month' AND year = '$year' AND branch_id = '$branch_id' AND filled_by_ho = 1 AND dspm_focus = 1");

                if (isset($dataSubmittedDspm) && $dataSubmittedDspm) {
                    $setValueDspm = 1;
                } else {
                    $setValueDspm = 0;
                }

                $dataSubmittedFocus = getRowColumn($this->_dbConn, "tblbranch_pickupstock_products_allocation", "rec_id", "month = '$month' AND year = '$year' AND branch_id = '$branch_id' AND filled_by_ho = 1 AND is_focusbrand = 1");

                if (isset($dataSubmittedFocus) && $dataSubmittedFocus) {
                    $setValueFocus = 1;
                } else {
                    $setValueFocus = 0;
                }


                $dataFromAssignTarget = getRowColumn($this->_dbConn, "tblassign_target as a, tblproject_team as b", "a.prod_id", "a.team_id = b.team_id AND a.month = '$month' AND a.year = '$year' AND b.branch_id = '$branch_id' ");

                if (isset($dataFromAssignTarget) && $dataFromAssignTarget) {
                    $setValueTarget = 1;
                } else {
                    $setValueTarget = 0;
                }

                $arrDistrictWiseData[$district][$main_branch][$branch_id] = array(
                    $setValue,
                    $setValueDspm,
                    $setValueFocus,
                    $setValueTarget,
                    $showStatus
                );
            }
        }

        $arrSet = array();
        foreach ($arrDistrictWiseData as $district => $arrDistrict) {
            foreach ($arrDistrict as $branch => $arrBranch) {
                $countOfRegion =  count($arrBranch);
                $sumOverAll = 0;
                $sumDspm = 0;
                $sumFocus = 0;
                $sumTarget = 0;
                foreach ($arrBranch as $regionID => $arrRegion) {
                    $sumOverAll += $arrRegion[0]; //Overall
                    $sumDspm += $arrRegion[1]; //DSPM
                    $sumFocus += $arrRegion[2]; //Focus
                    $sumTarget += $arrRegion[3]; //Target
                }
                if ($sumOverAll == $countOfRegion) {
                    $rOver = "Yes";
                } else {
                    $rOver = "No";
                }
                if ($sumDspm == $countOfRegion) {
                    $rDspm = "Yes";
                } else {
                    $rDspm = "No";
                }
                if ($sumFocus == $countOfRegion) {
                    $rFocus = "Yes";
                } else {
                    $rFocus = "No";
                }
                if ($sumTarget == $countOfRegion) {
                    $rTarget = "Yes";
                } else {
                    $rTarget = "No";
                }

                $percentage = round(($sumOverAll / $countOfRegion) * 100, 2);
                $arrSet[] = array($district, $branch, $rOver, $rFocus, $rDspm, $rTarget, $percentage, $arrRegion[4]);
            }
        }

        return $arrSet;
    }
}
