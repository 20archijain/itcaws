<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class EditProductPrice
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_arrAccessInfo = [];

    public function __construct($dbConn, $data, $arrAccessInfo)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
    }

    final public function getData()
    {
        $arrResult = [
            // Don't use dstatus = 0
            "branchList" => getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch"),
            "wdList" => getOptions($this->_dbConn, "tblproject_team", "wd_code", "wd_code", "dstatus = 0"),
            "showTransactionDownloadBtn" => true,
            "showSummaryDownloadBtn" => true,
        ];
        $arrResult["editProductHeading"] = "Edit Product Price";
        $arrResult["branchLabel"] = "Branch";
        $arrResult["wdLabel"] = "WD Code";
        $arrResult["teamTypeLabel"] = "Team Type";

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $branch = getFormData($this->_data, "branch");
        if ($branch) {
            $arrResult = [
                "teamsTypeList" => getTeamType($this->_dbConn, $branch),
            ];
        } else {
            $arrResult = [
                "teamsTypeList" => "",
            ];
        }

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getProductAndPrice()
    {

        $where = "";
        $where2 = "";
        $branch = getFormData($this->_data, "branchId");
        $wdCode = getFormData($this->_data, "wdCode");
        $teamType = getFormData($this->_data, "teamType");
        $arrProductDetails = [];
        if ($branch) {
            $where .= " AND branch_id = $branch";
        }
        if ($teamType) {
            $where .= " AND team_type = $teamType";
        }
        if ($wdCode) {
            $where2 .= " AND wd_code = '$wdCode'";
        }
        if ($wdCode) {
            $sAction = null;
            $iRows = 0;
            $sQuery = "SELECT DISTINCT product_name, net_rate, rec_id, wd_code FROM tblwd_product_net_rate_update WHERE dstatus = 0 $where2";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            if ($iRows > 0) {
                while ($row = $this->_dbConn->GetData($sAction)) {
                    $productName = $row['product_name'];
                    $productPrice = $row['net_rate'];
                    $productId = $row['rec_id'];
                    $wdCode = $row['wd_code'];
                    $arrProductDetails[] = [
                        "productName" => $productName,
                        "sellingPrice" => $productPrice,
                        "productId" => $productId,
                        "wdCode" => $wdCode,
                        "branchId" => "",
                    ];
                }
            } else {
                $branch_id = getRowColumn($this->_dbConn, "tblproject_team", "branch_id", " dstatus = 0 $where2");
                $sProductAction = null;
                $iProductRows = 0;
                $sProductQuery = "SELECT rec_id, product_name, net_rate, branch_id FROM tblbranch_pickupstock_products WHERE dstatus = 0 AND branch_id = $branch_id ORDER BY sort_order";
                $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

                if ($iProductRows > 0) {
                    while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                        $productName = $rowProduct['product_name'];
                        $productPrice = $rowProduct['net_rate'];
                        $productId = $rowProduct['rec_id'];
                        $branchId = $rowProduct['branch_id'];
                        $wd_code = getRowColumn($this->_dbConn, "tblproject_team", "wd_code", " dstatus = 0 AND branch_id = $branchId");
                        $arrProductDetails[] = [
                            "productName" => $productName,
                            "sellingPrice" => $productPrice,
                            "productId" => $productId,
                            "wdCode" => $wd_code,
                            "branchId" => "",
                        ];
                    }
                }
            }
        } else {
            $sProductAction = null;
            $iProductRows = 0;
            $sProductQuery = "SELECT rec_id, product_name, net_rate, branch_id FROM tblbranch_pickupstock_products WHERE dstatus = 0 $where ORDER BY sort_order";
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $productName = $rowProduct['product_name'];
                    $productPrice = $rowProduct['net_rate'];
                    $productId = $rowProduct['rec_id'];
                    $branchId = $rowProduct['branch_id'];
                    $arrProductDetails[] = [
                        "productName" => $productName,
                        "sellingPrice" => $productPrice,
                        "productId" => $productId,
                        "branchId" => $branchId,
                        "wdCode" => "",
                    ];
                }
            }
        }

        $arrResponse = [
            "productsList" => $arrProductDetails,
        ];

        $arrMessage = responseMessage([], 1, $arrResponse, true);
        echo json_encode($arrMessage);
    }

    final public function updateSellingPrice()
    {
        $branch = getFormData($this->_data, "branchId");
        $wdCode = getFormData($this->_data, "wdCode");
        $sellingPrice = getFormData($this->_data, "sellingPrice");
        $currentDate = currentDate();
        $currentDateTime = currentDateTime();

        $productDetails = [];

        foreach ($sellingPrice as $key => $price) {
            $parts = explode('-', $key);
            if (count($parts) >= 2) {
                $productName = $parts[0];
                $productCode = $parts[1];

                $productDetails[] = [
                    'name' => $productName,
                    'id' => $productCode,
                    'price' => $price
                ];
            }
        }

        foreach ($productDetails as $product) {
            $productName = $product['name'];
            $productPrice = $product['price'];
            $id = $product['id'];

            if ($wdCode) {
                $iWdStatus = isRecordExist($this->_dbConn, "tblwd_product_net_rate_update", "rec_id", "wd_code = '$wdCode' AND product_name = '$productName'");

                if ($iWdStatus === 1) {
                    $sAction = null;
                    $iRows = 0;
                    $query = "SELECT net_rate FROM tblwd_product_net_rate_update WHERE rec_id = $id";
                    $this->_dbConn->ExecuteSelectQuery($query, $sAction, $iRows);

                    if ($iRows > 0) {
                        while ($row = $this->_dbConn->GetData($sAction)) {
                            $currentPrice = $row['net_rate'];

                            if ($currentPrice != $productPrice) {
                                // Update the record
                                $status = updateRecord($this->_dbConn, "tblwd_product_net_rate_update", "net_rate = $productPrice, rcd = '$currentDate', rdt = '$currentDateTime'", "rec_id = $id");

                                // Add old and new price in notification
                                $cols = "wd_code, product_name, old_net_rate, new_net_rate, rcd, rdt";
                                $val = "?, ?, ?, ?, ?, ?";
                                $arrParam = [$wdCode, $productName, $currentPrice, $productPrice, $currentDate, $currentDateTime];
                                addRecord($this->_dbConn, "tbl_notification", $cols, $val, $arrParam);
                                $colsh = "wd_code, product_name, net_rate, rcd, rdt";
                                $valh = "?, ?, ?, ?, ?";
                                $arrParam = [$wdCode, $productName, $productPrice, $currentDate, $currentDateTime];
                                addRecord($this->_dbConn, "tbl_price_history", $colsh, $valh, $arrParam);
                            }
                        }
                    }
                } else {
                    $cols = "wd_code, product_name, net_rate, rcd, rdt";
                    $val = "?, ?, ?, ?, ?";
                    $arrParam = [$wdCode, $productName, $productPrice, $currentDate, $currentDateTime];
                    $status = addRecord($this->_dbConn, "tblwd_product_net_rate_update", $cols, $val, $arrParam);
                }
            } else {
                $sAction1 = null;
                $iRows1 = 0;
                $query = "SELECT net_rate FROM tblbranch_pickupstock_products WHERE rec_id = $id";
                $this->_dbConn->ExecuteSelectQuery($query, $sAction1, $iRows1);

                if ($iRows1 > 0) {
                    $row = $this->_dbConn->GetData($sAction1);
                    $currentSellingPrice = $row['net_rate'];

                    if ($currentSellingPrice != $productPrice) {
                        $status = updateRecord($this->_dbConn, "tblbranch_pickupstock_products", "net_rate = $productPrice, rcd = '$currentDate', rdt = '$currentDateTime'", "rec_id = $id");
                        $wd_code = getRowColumn($this->_dbConn, "tblproject_team", "wd_code", " dstatus = 0 AND branch_id = $branch");

                        $cols = "branch_id, wd_code, product_name, old_net_rate, new_net_rate, is_branch_update, rcd, rdt";
                        $val = "?, ?, ?, ?, ?, ?, ?, ?";
                        $arrParam = [$branch, $wd_code, $productName, $currentSellingPrice, $productPrice, '1', $currentDate, $currentDateTime];
                        addRecord($this->_dbConn, "tbl_notification", $cols, $val, $arrParam);
                        $colsh = "wd_code, product_name, net_rate, rcd, rdt";
                        $valh = "?, ?, ?, ?, ?";
                        $arrParam = [$wdCode, $productName, $productPrice, $currentDate, $currentDateTime];
                        addRecord($this->_dbConn, "tbl_price_history", $colsh, $valh, $arrParam);
                    }
                }
            }
        }
        $arrMessage = responseMessage([$GLOBALS['UPDATE_SUCCESS']], 1, $status ?? null);
        echo json_encode($arrMessage);
    }
}
