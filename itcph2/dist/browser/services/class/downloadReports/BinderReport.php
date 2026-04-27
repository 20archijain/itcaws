<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class BinderReport
{
    private $_dbConn = null;
    private $_data = null;
    private $_tables = [];
    private $_projectId = 1;
    private $_arrAccessInfo = [];
    private $_iUserId = null;

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
    }

    private function getCondition()
    {
        $arrSearchParams = array(
            "dateFrom" => array("capture_date", 4, "dateTo", true),
        );

        // filter query
        $where = getFilterResult(
            $this->_data,
            $arrSearchParams,
            $this->_dbConn
        );

        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        return $where;
    }

    // final public function getData()
    // {
    //     $arrResult = array(
    //         // Don't use dstatus = 0
    //         "branchList" => getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch"),
    //     );

    //     $arrMessage = responseMessage(array(), 1, $arrResult, true);
    //     echo json_encode($arrMessage);
    // }

    final public function getData()
    {
        $where = "";
        $where2 = "";
        $userBranch = "";
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
            $where2 .= "team_id IN $teamList";
            $branchId = getRowColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", "$where2");
        }
        $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $user_id = $this->_iUserId;
        $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        if ($groupId == 1 || $groupId == 2) {
            $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
            $branchFilter = true;
        } else {
            $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
            $branchFilter = true;
        }
        $arrResult = array(
            // Don't use dstatus = 0
            "branchList" => $branchList,
            // "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id = '99' $where"),
            // "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99' $where"),
            // "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL  AND s_id = '99' $where"),
            // "teamType" => getTeamType($this->_dbConn),
            // "teamList" => getTeamsOptions($this->_dbConn, "", "", 0, true, "s_id='99' $where"),
            // "showTransactionDownloadBtn" => true,
            // "showSummaryDownloadBtn" => true,
            "branchFilter" => $branchFilter,
            "userBranch" => $userBranch,
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }


    final public function getCircle($branch = "branch_id")
    {
        $branch = $this->_data['branch'];
        $branchCond = "";
        if ($branch) {
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            if (in_array('all', $branch)) {
                $branchCond = ""; // No condition for 'all'
            } else {
                $branch = "'" . implode("','", $branch) . "'";
                $branchCond = " AND branch_id IN ($branch)";
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $branch),
                "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "circleList" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection($circle = "circle")
    {
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $circleCond = "";
        $branchCond = "";
        if ($circle || $branch) {
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }
            $branchIds = getRowsColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", " dstatus = '0' AND s_id = '99' $branchCond $circleCond ");
            $where = "";
            if ($branchIds) {
                $matchAll = checkIfAllSelected($branchIds);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchIds)) {
                        $branchIds = implode(",", $branchIds);
                        $where = "$branchIds";
                    } else {
                        $where = "$branchIds";
                    }
                }
            }
            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $where),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99' $branchCond  $circleCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "sectionList" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getWDCode($section = "section")
    {
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        if ($section || $branch || $section) {
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }
            $branchIds = getRowsColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", " dstatus = '0' AND s_id = '99' $branchCond $circleCond $sectionCond ");
            $where = "";
            if ($branchIds) {
                $matchAll = checkIfAllSelected($branchIds);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchIds)) {
                        $branchIds = implode(",", $branchIds);
                        $where = "$branchIds";
                    } else {
                        $where = "$branchIds";
                    }
                }
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $where),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "wdCodeList" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType()
    {
        $wdCode = $this->_data['wdCode'];
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $wdCodeCond = "";
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        if ($wdCode || $branch || $circle || $section) {
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND wd_code IN ($wdCode)";
                }
            }
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }

            $branchIds = getRowsColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", " dstatus = '0' AND s_id = '99' $branchCond $circleCond $sectionCond $wdCodeCond ");
            $where = "";
            if ($branchIds) {
                $matchAll = checkIfAllSelected($branchIds);
                if (!$matchAll) {
                    if (isNonEmptyArray($branchIds)) {
                        $branchIds = implode(",", $branchIds);
                        $where = "$branchIds";
                    } else {
                        $where = "$branchIds";
                    }
                }
            }
            $arrResult = array(
                // Don't use dstatus = 0
                "dsType" => getTeamType($this->_dbConn, $branch, $wdCode, $where),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond"),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamList()
    {
        $dsType = $this->_data['dsType'];
        $wdCode = $this->_data['wdCode'];
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $wdCodeCond = "";
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        $dsTypeCond = "";
        $where = "";
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
        }
        if (isset($dsType) && $dsType != "" && $dsType >= 0 || $wdCode || $branch || $circle || $section) {
            if ($dsType) {
                if (!is_array($dsType)) {
                    $dsType = array($dsType);
                }
                if (in_array('all', $dsType)) {
                    $dsTypeCond = ""; // No condition for 'all'
                } else {
                    $dsType = "'" . implode("','", $dsType) . "'";
                    $dsTypeCond = " AND is_type IN ($dsType)";
                }
            }
            if ($wdCode) {
                if (!is_array($wdCode)) {
                    $wdCode = array($wdCode);
                }
                if (in_array('all', $wdCode)) {
                    $wdCodeCond = ""; // No condition for 'all'
                } else {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND wd_code IN ($wdCode)";
                }
            }
            if ($section) {
                if (!is_array($section)) {
                    $section = array($section);
                }
                if (in_array('all', $section)) {
                    $sectionCond = ""; // No condition for 'all'
                } else {
                    $section = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($section)";
                }
            }
            if ($circle) {
                if (!is_array($circle)) {
                    $circle = array($circle);
                }
                if (in_array('all', $circle)) {
                    $circleCond = ""; // No condition for 'all'
                } else {
                    $circle = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circle)";
                }
            }
            if ($branch) {
                if (!is_array($branch)) {
                    $branch = array($branch);
                }
                if (in_array('all', $branch)) {
                    $branchCond = ""; // No condition for 'all'
                } else {
                    $branch = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branch)";
                }
            }

            $arrResult = array(
                // Don't use dstatus = 0
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL  AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond $dsTypeCond $where"),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getBranchTeamTypeList()
    {
        if ($this->_data["branch"]) {
            $arrResult = array(
                "dsType" => getTeamType($this->_dbConn, $this->_data["branch"]),
                "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"]),
            );
        } else {
            $arrResult = array(
                "dsType" => "",
                "productList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    private function getBranches()
    {
        return getBranchList($this->_dbConn, false, "", "", 0, true);
    }

    private function getWeekNumber($date)
    {
        $day = (int)date('j', strtotime($date)); // Get day of the month

        if ($day >= 1 && $day <= 7) {
            return "Week 1";
        } elseif ($day >= 8 && $day <= 14) {
            return "Week 2";
        } elseif ($day >= 15 && $day <= 21) {
            return "Week 3";
        } else {
            return "Week 4";
        }
    }

    public function getDownloadData()
    {
        $currentDateTime = currentDateTime();
        $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);


        // Filter query
        $where = $this->getCondition();
        $branch = getFormData($this->_data, "branch");
        $circle = getFormData($this->_data, "circle");
        $section = getFormData($this->_data, "section");
        $wdCode = getFormData($this->_data, "wdCode");
        $dsType = getFormData($this->_data, "dsType");
        $dsName = getFormData($this->_data, "dsName");

        $respTable = getRespTable(1, $this->_projectId);
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $branchProductsTable = $this->_tables["BRANCH_PICKUPSTOCK_PRODUCTS_TABLE"];
        $routeTable = $this->_tables["ROUTE_DETAILS_TABLE"];
        $Cond = "";
        $teamTypeCond = "";
        if ($dsType) {
            $teamTypeCond .= " AND team_type = $dsType";
            $Cond .= " AND b.is_type = $dsType";
        }

        if ($branch) {
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branchs = "'" . implode("','", $branch) . "'";
                    $branchCond = " AND branch_id IN ($branchs)";
                    $Cond .= " AND b.branch_id IN ($branchs)";
                } else {
                    $branchCond = " AND branch_id = $branch";
                    $Cond .= " AND b.branch_id = $branch";
                }
            } else {
                $branch = $this->getBranches();
            }
        }


        $circleCond = "";
        if ($circle) {
            $matchAll = checkIfAllSelected($circle);
            if (!$matchAll) {
                if (isNonEmptyArray($circle)) {
                    $circles = "'" . implode("','", $circle) . "'";
                    $circleCond = " AND circle IN ($circles)";
                    $Cond .= " AND b.circle IN ($circles)";
                } else {
                    $circleCond = " AND circle = $circle";
                    $Cond .= " AND b.circle = $circle";
                }
            }
        }

        $sectionCond = "";
        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $sections = "'" . implode("','", $section) . "'";
                    $sectionCond = " AND section IN ($sections)";
                    $Cond .= " AND b.section IN ($sections)";
                } else {
                    $sectionCond = " AND section = $section";
                    $Cond .= " AND b.section = $section";
                }
            }
        }

        $wdCodeCond = "";
        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCodes = "'" . implode("','", $wdCode) . "'";
                    $wdCodeCond = " AND wd_code IN ($wdCodes)";
                    $Cond .= " AND b.wd_code IN ($wdCodes)";
                } else {
                    $wdCodeCond = " AND wd_code = $wdCode";
                    $Cond .= " AND b.wd_code = $wdCode";
                }
            }
        }

        $dsNameCond = "";
        if ($dsName) {
            $matchAll = checkIfAllSelected($dsName);
            if (!$matchAll) {
                if (isNonEmptyArray($dsName)) {
                    $dsNames = "'" . implode("','", $dsName) . "'";
                    $dsNameCond = " AND team_id IN ($dsNames)";
                    $Cond .= " AND b.team_id IN ($dsNames)";
                } else {
                    $dsNameCond = " AND team_id = $dsName";
                    $Cond .= " AND b.team_id = $dsName";
                }
            }
        }

        $allCond = "";
        if ($Cond) {
            $allCond .= " AND a.team_id IN (SELECT team_id FROM $projectTeamTable WHERE dstatus = 0  $Cond)";
        }
        // print_r($allCond);
        // $productCond = "";
        // if ($product) {
        //     if (isNonEmptyArray($product)) {
        //         $products = "'" .  implode("','", $product)  . "'";
        //         $productCond = " AND product_name IN ($products)";
        //     } else {
        //         $productCond = " AND product_name = '$product'";
        //     }
        // }

        // $branchCond = "";
        // if ($branch) {
        //     $matchAll = checkIfAllSelected($branch);
        //     if (!$matchAll) {
        //         if (isNonEmptyArray($branch)) {
        //             $branchs = implode(",", $branch);
        //             $branchCond = " AND branch_id IN ($branchs)";
        //             $Cond .= " AND b.branch_id IN ($branchs)";
        //         } else {
        //             $branchCond = " AND branch_id = $branch";
        //             $Cond .= " AND b.branch_id = $branch";
        //         }
        //     }
        // }

        foreach ($branch as $branchId) {
            $sProductQuery = "SELECT DISTINCT product_name, summary_column_name, category_name FROM $branchProductsTable WHERE dstatus = 0 AND branch_id = $branchId $teamTypeCond
            ORDER BY product_name";
            $sProductAction = null;
            $iProductRows = 0;
            $this->_dbConn->ExecuteSelectQuery($sProductQuery, $sProductAction, $iProductRows);

            if ($iProductRows > 0) {
                $summaryColName = [];
                $productNames = [];
                $category_name = [];

                while ($rowProduct = $this->_dbConn->GetData($sProductAction)) {
                    $summaryColName[] = $rowProduct["summary_column_name"];
                    $productNames[] = $rowProduct["product_name"];
                    $category_name[$rowProduct["summary_column_name"]] = $rowProduct["category_name"];
                }
                $sProductSaleColumns = implode(",", $summaryColName);

                $isType = [0 => "Van DS", 1 => "Niches"];
                $rsAction = null;
                $iRows = 0;

                // Fetch all records (no grouping)
                $sQuery = "SELECT a.capture_datetime, a.capture_date, a.ques_0, a.ques_1, a.ques_3, b.team_id, b.team_name, b.is_type, b.circle, b.section, b.wd_code, c.district, c.branch_name, c.main_branch, $sProductSaleColumns FROM $respTable AS a, $projectTeamTable AS b, $branchTable AS c" .
                    " Where a.team_id = b.team_id AND b.branch_id = c.branch_id AND a.dstatus = 0 AND ques_0 IN ('Outlet Order', 'Add Outlet') $where $allCond AND b.branch_id = $branchId ORDER BY a.capture_date DESC, capture_datetime DESC";

                $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows);

                if ($iRows > 0) {
                    $aggregatedData = []; // Array to store aggregated results
                    while ($row = $this->_dbConn->GetData($rsAction)) {
                        $district = $row['district'];
                        $mainBranchName = $row['main_branch'];
                        $branchName = $row['branch_name'];
                        $teamId = $row['team_id'];
                        $teamName = $row['team_name'];
                        $circle = $row['circle'];
                        $outletId = $row['ques_3'];
                        $section = $row['section'];
                        $dsType = $isType[$row['is_type']];
                        $wdCode = $row['wd_code'];
                        $date = $row['capture_date'];
                        $week = $this->getWeekNumber($date); // Get week number
                        $route = htmlspecialchars_decode(json_decode($row["ques_1"], true)[0]);
                        $outletData = getRowColumns(
                            $this->_dbConn,
                            "$routeTable",
                            "outlet_name, shop_uniq_code, shop_type, outlet_mobile",
                            "dstatus = 0 AND rec_id = '$outletId'
                        AND team_id = $teamId "
                        );

                        $outletName  = isset($outletData[0]) ? htmlentities($outletData[0]) : "";
                        $shopUniqueCode = $outletData[1] ?? "";
                        $outletType = $outletData[2] ?? "";
                        $mobileNo = $outletData[3] ?? "";

                        foreach ($summaryColName as $colName) {
                            // Get sales quantity for each product variant per transaction
                            $salesQty = $row[$colName] ?? 0;

                            if ($salesQty > 0) {
                                // Unique key for grouping (team_id + shop_id + product_id + date)
                                $key = $teamId . "_" . $shopUniqueCode . "_" . $colName . "_" . $date;
                                if (!isset($aggregatedData[$key])) {
                                    $aggregatedData[$key] = [
                                        'District' => $district,
                                        'Branch' => $mainBranchName,
                                        'Region' => $branchName,
                                        'Circle' => $circle,
                                        'Section' => $section,
                                        'WD Code' => $wdCode,
                                        'DS Type' => $dsType,
                                        'DS ID' => $teamId,
                                        'DS Name' => $teamName,
                                        'Date' => $date,
                                        'Week' => $week,
                                        'Beat/Route' => $route,
                                        'Outlet Name' => $outletName,
                                        'Owner Moblie Number' => $mobileNo,
                                        'Outlet ID' => $shopUniqueCode,
                                        'Outlet Type' => $outletType,
                                        'Category' => $category_name[$colName] ?? '',
                                        'Variant' => $productNames[array_search($colName, $summaryColName)],
                                        'Sales Qty (M)' => 0, // Initialize sales quantity
                                    ];
                                }
                                // Aggregate sales quantity
                                $aggregatedData[$key]['Sales Qty (M)'] += $salesQty;
                            }
                        }
                    }
                }
                // Define headers before adding data
                $arrExcelData = [];
                $arrExcelData[] = [
                    'District',
                    'Branch',
                    'Region',
                    'Circle',
                    'Section',
                    'WD Code',
                    'DS Type',
                    'DS ID',
                    'DS Name',
                    'Date',
                    'Week',
                    'Beat/Route',
                    'Outlet Name',
                    'Owner Moblie Number',
                    'Outlet ID',
                    'Outlet Type',
                    'Category',
                    'Variant',
                    'Sales Qty (M)'
                ];

                // Convert associative array to indexed array and add data
                $arrExcelData = array_merge($arrExcelData, array_values($aggregatedData));
            }
        }

        $fileName = "BINDER_REPORT_$currentDateTime.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($arrExcelData);

        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";
        $fileDetails = [
            "filePath" => $downloadFileLocation,
            "fileName" => $fileName,
        ];
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        $arrMessage = responseMessage([$GLOBALS['FILE_DOWNLOADING']], 1, $fileDetails);

        echo json_encode($arrMessage);
    }
}
