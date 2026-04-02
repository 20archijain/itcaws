<?php

require_once $include_path . "defined_index.php";
require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// phpcs:ignore
class MdoTargetReport
{
  private $_dbConn = null;
  private $_data = null;
  private $_tables = [];
  private $_projectId = 1;
  private $_arrAccessInfo = [];
  private $arrBranchwiseProducts = [];


  public function __construct($dbConn, $data, $arrAccessInfo)
  {
    $this->_data = $data;
    $this->_dbConn = $dbConn;
    $this->_tables = $GLOBALS['TABLES'];
    $this->_arrAccessInfo = $arrAccessInfo;
  }


  final public function getConditionFilter($andCondition = true)
  {
    $condition = "";

    $district = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "district");
    if ($district) {
      $matchAll = checkIfAllSelected($district);
      if (!$matchAll) {
        if (isNonEmptyArray($district)) {
          $districts = "'" . implode("','", $district) . "'";
          $condition .= " AND s.district IN ($districts)";
        } else {
          $condition .= " AND s.district = '$district'";
        }
      }
    }

    $branch = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "branch");
    if ($branch) {
      $matchAll = checkIfAllSelected($branch);
      if (!$matchAll) {
        if (isNonEmptyArray($branch)) {
          $branchIds = "'" . implode("','", $branch) . "'";
          $condition .= " AND s.main_branch IN ($branchIds)";
        } else {
          $condition .= " AND s.main_branch = '$branch'";
        }
      }
    }

    $circle = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "circle");
    if ($circle) {
      $matchAll = checkIfAllSelected($circle);
      if (!$matchAll) {
        if (isNonEmptyArray($circle)) {
          $circleIds = "'" . implode("','", $circle) . "'";
          $condition .= " AND s.circle IN ($circleIds)";
        } else {
          $condition .= " AND s.circle = '$circle'";
        }
      }
    }

    $section = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "section");
    if ($section) {
      $matchAll = checkIfAllSelected($section);
      if (!$matchAll) {
        if (isNonEmptyArray($section)) {
          $sectionIds = "'" . implode("','", $section) . "'";
          $condition .= " AND s.section IN ($sectionIds)";
        } else {
          $condition .= " AND s.section = '$section'";
        }
      }
    }

    $wdCode = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdCode");
    if ($wdCode) {
      $matchAll = checkIfAllSelected($wdCode);
      if (!$matchAll) {
        if (isNonEmptyArray($wdCode)) {
          $wdCodeIds = "'" . implode("','", $wdCode) . "'";
          $condition .= " AND s.wd_code IN ($wdCodeIds)";
        } else {
          $condition .= " AND s.wd_code = '$wdCode'";
        }
      }
    }

    $wdMarket = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdMarket");
    if ($wdMarket) {
      if (!is_array($wdMarket)) {
        $wdMarket = array($wdMarket);
      }
      if (!in_array('all', $wdMarket)) {
        $wdMarket = "'" . implode("','", $wdMarket) . "'";
        $condition .= " AND s.wd_market IN ($wdMarket)";
      }
    }

    $wdPopGroup = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "wdPopGroup");
    if ($wdPopGroup) {
      if (!is_array($wdPopGroup)) {
        $wdPopGroup = array($wdPopGroup);
      }
      if (!in_array('all', $wdPopGroup)) {
        $wdPopGroup = "'" . implode("','", $wdPopGroup) . "'";
        $condition .= " AND s.wd_pop_group IN ($wdPopGroup)";
      }
    }

    $teamType = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsType");
    if (isset($teamType) && $teamType !== "" && isNonEmptyArray($teamType) && $teamType >= 0) {
      $matchAll = checkIfAllSelected($teamType);
      if (!$matchAll) {
        if (isNonEmptyArray($teamType)) {
          $teamTypes = "'" . implode("','", $teamType) . "'";
          $condition .= " AND s.is_type IN ($teamTypes)";
        } else {
          $condition .= " AND s.is_type = $teamType";
        }
      }
    }

    $dsName = getFormData(isset($this->_data['searchbar']) ? $this->_data['searchbar'] : $this->_data, "dsName");
    if ($dsName) {
      $matchAll = checkIfAllSelected($dsName);
      if (!$matchAll) {
        if (isNonEmptyArray($dsName)) {
          $dsNames = "'" . implode("','", $dsName) . "'";
          $condition .= " AND s.team_id IN ($dsNames)";
        } else {
          $condition .= " AND s.team_id = $dsName";
        }
      }
    }


    $where = $condition ? $condition : "";

    $teamList = $this->_arrAccessInfo["user_teams"];
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }

    return $where;
  }

  final public function getData()
  {
    $arrResult = array(
      "districtList"   => $this->getDistrictList(),
      "branchList"     => $this->getBranchList(),
      "circleList"     => $this->getCircleList(),
      "sectionList"    => $this->getSectionList(),
      "wdCodeList"     => $this->getWdCodeList(),
      "teamType"       => $this->getDsTypeList(),
      "teamList"       => $this->getTeamsList(),
      "wdMarketList"   => $this->getWdMarketList(),
      "wdPopGroupList" => $this->getWdPopGroupList(),
      "monthList"      => $this->monthLabelAndValue(),
    );

    $arrMessage = responseMessage(array(), 1, $arrResult, true);
    echo json_encode($arrMessage);
  }

  private function monthLabelAndValue($count = 12)
  {
    $months = [
      "January","February","March","April","May","June",
      "July","August","September","October","November","December"
    ];

    $arrData      = [];
    $currentMonth = date('n');
    $currentYear  = date('Y');
    $nextMonth    = $currentMonth + 1;
    $nextYear     = $currentYear;

    if ($nextMonth > 12) {
      $nextMonth = 1;
      $nextYear++;
    }

    for ($i = 0; $i < $count; $i++) {
      $targetMonth = $nextMonth - $i;
      $targetYear  = $nextYear;

      while ($targetMonth <= 0) {
        $targetMonth += 12;
        $targetYear--;
      }

      $monthName  = $months[$targetMonth - 1];
      $shortLabel = date('M', mktime(0, 0, 0, $targetMonth, 10)) . ' ' . substr($targetYear, 2);

      $arrData[] = [
        "label" => $shortLabel,
        "value" => $monthName . ' ' . $targetYear
      ];
    }

    return $arrData;
  }

  final public function getProducts()
  {
    $arrResult = array(
      "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"], $this->_data["type"]),
    );

    $arrMessage = responseMessage(array(), 1, $arrResult, true);
    echo json_encode($arrMessage);
  }

  final public function getBranchTeamTypeList()
  {
    if ($this->_data["branch"]) {
      $arrResult = array(
        "teamType"    => getTeamType($this->_dbConn, $this->_data["branch"]),
        "productList" => getBranchWiseProducts($this->_dbConn, $this->_data["branch"]),
      );
    } else {
      $arrResult = array(
        "teamType"    => "",
        "productList" => "",
      );
    }

    $arrMessage = responseMessage(array(), 1, $arrResult, true);
    echo json_encode($arrMessage);
  }

  final public function getDownloadData()
  {
    $currentDateTime = currentDateTime();
    $currentDateTime = preg_replace("/\s+|[:]+/", "_", $currentDateTime);

    $arrMonth = $this->_data['month'];

    $whereFilter = $this->getConditionFilter();

    // ---------------- HEADER ----------------
    $header = [[
      "Month",
      "District",
      "Branch",
      "Circle",
      "Section",
      "WD Code",
      "WD Pop Group",
      "WD Market",
      "MDO Id",
      "MDO Name",
      "MDO Type",
      "Is Team Active",
      "Parameter Type",
      "Target",
      "Achievement",
      "Ach%",
      "Max Points"
    ]];

    // ---------------- PARAMETER CONFIG ----------------
    $parameters = [
      "Min 6 Days With Van DS/month"                  => ["target" => 6,    "col" => "van_ds_days",          "max" => 6],
      "Min 10 Days With RMD + SCP DS/month"           => ["target" => 10,   "col" => "rmd_scp_days",         "max" => 10],
      "Min 2 Days With GT TL/month"                   => ["target" => 2,    "col" => "gt_tl_days",           "max" => 2],
      "Min 2 Days With AE/month"                      => ["target" => 2,    "col" => "ae_days",              "max" => 2],
      "Avg Time in Market Daily(Min 18 Days / month)" => ["target" => 18,   "col" => "working_days",         "max" => 18],
      "RMD/ SCP DS Monthly Payout"                    => ["target" => 1200, "col" => "incentive_rmd_scp",    "max" => 1200],
      "Van DS Monthly Payout"                         => ["target" => 1200, "col" => "incentive_van_ds",     "max" => 1200],
      "Basis Sales Of All Infra Mapped (Criteria 1)"  => ["target" => 1500, "col" => "incentive_criteria_1", "max" => 1500],
      "Basis Sales Of All Infra Mapped (Criteria 2)"  => ["target" => 4000, "col" => "incentive_criteria_2", "max" => 4000],
    ];

    $arrDataHolder = [];

    // ---------------- LOOP MONTHS ----------------
    foreach ($arrMonth as $month) {

      $date         = DateTime::createFromFormat('F Y', $month);
      $numericMonth = $date->format('m');
      $numericYear  = $date->format('Y');
      $monthKey     = $numericYear . '-' . $numericMonth;

      // Single query per month — all columns already in mdo_dspm_summary
      $sAction = null;
      $iRows   = 0;
      $sQuery  = "SELECT
                    s.team_id,
                    s.team_name,
                    s.is_type,
                    s.district,
                    s.main_branch,
                    s.branch_name,
                    s.circle,
                    s.section,
                    s.wd_code,
                    s.wd_market,
                    s.wd_pop_group,
                    s.van_ds_days,
                    s.rmd_scp_days,
                    s.gt_tl_days,
                    s.ae_days,
                    s.working_days,
                    s.incentive_rmd_scp,
                    s.incentive_van_ds,
                    s.incentive_criteria_1,
                    s.incentive_criteria_2,
                    s.dstatus
                  FROM mdo_dspm_summary AS s
                  WHERE s.month = '$monthKey'
                  AND s.year = $numericYear
                  $whereFilter
                  GROUP BY s.team_id, s.month, s.year";

      $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

      if ($iRows <= 0) continue;

      while ($row = $this->_dbConn->GetData($sAction)) {

        // Derive active/inactive label from dstatus
        $activeTeam = "";
        if ($row['dstatus'] == 0) {
          $activeTeam = "Active";
        } elseif ($row['dstatus'] == 1) {
          $activeTeam = "Inactive";
        }

        // MDO Type label
        $mdoTypeLabel = "";
        if ($row['is_type'] == 7) {
          $mdoTypeLabel = "MDO A";
        } elseif ($row['is_type'] == 10) {
          $mdoTypeLabel = "MDO B";
        } else {
          $mdoTypeLabel = $row['is_type'];
        }

        // ---------------- BUILD ROWS PER PARAMETER ----------------
        foreach ($parameters as $paramLabel => $paramConfig) {

          $target      = $paramConfig['target'];
          $maxPoints   = $paramConfig['max'];
          $achCol      = $paramConfig['col'];
          $achievement = !empty($row[$achCol]) ? $row[$achCol] : 0;
          $achPct      = ($target > 0) ? round(($achievement / $target) * 100, 2) . "%" : "0%";

          $arrDataHolder[] = [
            $month,
            $row['district'],
            $row['branch_name'],
            $row['circle'],
            $row['section'],
            $row['wd_code'],
            $row['wd_pop_group'],
            $row['wd_market'],
            $row['team_id'],
            $row['team_name'],
            $mdoTypeLabel,
            $activeTeam,
            $paramLabel,
            $target,
            $achievement,
            $achPct,
            $maxPoints
          ];
        }
      }
    }

    // ---------------- WRITE CSV ----------------
    $fileName = "MDO_DSPM_Report_$currentDateTime.csv";

    if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
      mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
    }

    $filePath             = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
    $downloadFileLocation = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

    $fp = fopen($filePath, 'w');
    if ($fp === false) {
      echo json_encode(responseMessage(["Failed to create CSV file"], 0));
      return;
    }

    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

    foreach ($header as $headerRow) {
      fputs($fp, implode(",", array_map('cleanCSVValue', $headerRow)) . "\n");
    }

    foreach ($arrDataHolder as $dataRow) {
      fputs($fp, implode(",", array_map('cleanCSVValue', $dataRow)) . "\n");
    }

    fclose($fp);

    echo json_encode(responseMessage(
      [$GLOBALS['FILE_DOWNLOADING']],
      1,
      ["filePath" => $downloadFileLocation, "fileName" => $fileName]
    ));
  }

  final public function getBranchListWithoutAll($cond = "")
  {
    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.main_branch FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.main_branch";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    $arrData = [];
    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = $row['main_branch'];
      }
    }
    return $arrData;
  }

  final public function getDistrictList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.district FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.district";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['district'], "value" => $row['district']];
      }
    }
    return $arrData;
  }

  final public function getBranchList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.main_branch, s.branch_name FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.branch_name";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = [
          "label"      => $row['branch_name'],
          "value"      => $row['main_branch'],
          "mainBranch" => $row['main_branch']
        ];
      }
    }
    return $arrData;
  }

  final public function getCircleList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.circle IS NOT NULL AND s.circle != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.circle FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.circle";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['circle'], "value" => $row['circle']];
      }
    }
    return $arrData;
  }

  final public function getSectionList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.section IS NOT NULL AND s.section != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.section FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.section";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['section'], "value" => $row['section']];
      }
    }
    return $arrData;
  }

  final public function getWdCodeList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.wd_code IS NOT NULL AND s.wd_code != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.wd_code, s.wd_market FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.wd_code";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = [
          "label" => $row['wd_code'] . ' - ' . $row['wd_market'],
          "value" => $row['wd_code']
        ];
      }
    }
    return $arrData;
  }

  final public function getWdMarketList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.wd_market IS NOT NULL AND s.wd_market != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.wd_market FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.wd_market";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['wd_market'], "value" => $row['wd_market']];
      }
    }
    return $arrData;
  }

  final public function getWdPopGroupList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.wd_pop_group IS NOT NULL AND s.wd_pop_group != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.wd_pop_group FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.wd_pop_group";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['wd_pop_group'], "value" => $row['wd_pop_group']];
      }
    }
    return $arrData;
  }

  final public function getDsTypeList($cond = "")
  {
    $arrData   = [];
    $arrData[] = ["label" => "All", "value" => "all"];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.is_type IN (7, 10)";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.is_type FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.is_type";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $teamType = "";
        if ($row['is_type'] == 7) {
          $teamType = "MDO A";
        } elseif ($row['is_type'] == 10) {
          $teamType = "MDO B";
        }
        $arrData[] = ["label" => $teamType, "value" => (string)$row['is_type']];
      }
    }
    return $arrData;
  }

  final public function getTeamsList($cond = "")
  {
    $arrData = [];

    $teamList = $this->_arrAccessInfo["user_teams"];
    $where    = "s.dstatus = 0 AND s.team_name IS NOT NULL AND s.team_name != ''";
    if ($teamList) {
      $where .= " AND s.team_id IN $teamList";
    }
    if ($cond) {
      $where .= $cond;
    }

    $rsAction    = null;
    $iActionRows = 0;
    $query = "SELECT DISTINCT s.team_name, s.team_id FROM mdo_dspm_summary AS s WHERE $where ORDER BY s.team_name";
    $this->_dbConn->ExecuteSelectQuery($query, $rsAction, $iActionRows);

    if ($iActionRows > 0) {
      while ($row = $this->_dbConn->GetData($rsAction)) {
        $arrData[] = ["label" => $row['team_name'], "value" => $row['team_id']];
      }
    }
    return $arrData;
  }


  final public function getBranch()
  {
    $district     = $this->_data['district'];
    $districtCond = "";
    if (!empty($district)) {
      if (!is_array($district)) $district = array($district);
      if (!in_array('all', $district)) {
        $district     = "'" . implode("','", $district) . "'";
        $districtCond = " AND s.district IN ($district)";
      }
      $arrResult = [
        "branchList"     => $this->getBranchList($districtCond),
        "circleList"     => $this->getCircleList($districtCond),
        "sectionList"    => $this->getSectionList($districtCond),
        "wdCodeList"     => $this->getWdCodeList($districtCond),
        "teamType"       => $this->getDsTypeList($districtCond),
        "teamList"       => $this->getTeamsList($districtCond),
        "wdMarketList"   => $this->getWdMarketList($districtCond),
        "wdPopGroupList" => $this->getWdPopGroupList($districtCond),
      ];
    } else {
      $arrResult = [
        "branchList" => "", "circleList" => "", "sectionList" => "",
        "wdCodeList" => "", "teamType" => "", "teamList" => "",
        "wdMarketList" => "", "wdPopGroupList" => "",
      ];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getCircle()
  {
    $branch     = $this->_data['branch'];
    $branchCond = "";
    if ($branch) {
      if (!is_array($branch)) $branch = array($branch);
      if (!in_array('all', $branch)) {
        $branch     = "'" . implode("','", $branch) . "'";
        $branchCond = " AND s.main_branch IN ($branch)";
      }
      $arrResult = [
        "circleList"     => $this->getCircleList($branchCond),
        "sectionList"    => $this->getSectionList($branchCond),
        "wdCodeList"     => $this->getWdCodeList($branchCond),
        "teamType"       => $this->getDsTypeList($branchCond),
        "teamList"       => $this->getTeamsList($branchCond),
        "wdMarketList"   => $this->getWdMarketList($branchCond),
        "wdPopGroupList" => $this->getWdPopGroupList($branchCond),
      ];
    } else {
      $arrResult = [
        "circleList" => "", "sectionList" => "", "wdCodeList" => "",
        "teamType" => "", "teamList" => "", "wdMarketList" => "", "wdPopGroupList" => "",
      ];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getSection()
  {
    $circle     = $this->_data['circle'];
    $circleCond = "";
    if ($circle) {
      if (!is_array($circle)) $circle = array($circle);
      if (!in_array('all', $circle)) {
        $circle     = "'" . implode("','", $circle) . "'";
        $circleCond = " AND s.circle IN ($circle)";
      }
      $arrResult = [
        "sectionList"    => $this->getSectionList($circleCond),
        "wdCodeList"     => $this->getWdCodeList($circleCond),
        "teamType"       => $this->getDsTypeList($circleCond),
        "teamList"       => $this->getTeamsList($circleCond),
        "wdMarketList"   => $this->getWdMarketList($circleCond),
        "wdPopGroupList" => $this->getWdPopGroupList($circleCond),
      ];
    } else {
      $arrResult = [
        "sectionList" => "", "wdCodeList" => "", "teamType" => "",
        "teamList" => "", "wdMarketList" => "", "wdPopGroupList" => "",
      ];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getWDCode()
  {
    $section     = $this->_data['section'];
    $sectionCond = "";
    if ($section) {
      if (!is_array($section)) $section = array($section);
      if (!in_array('all', $section)) {
        $section     = "'" . implode("','", $section) . "'";
        $sectionCond = " AND s.section IN ($section)";
      }
      $arrResult = [
        "wdCodeList"     => $this->getWdCodeList($sectionCond),
        "teamType"       => $this->getDsTypeList($sectionCond),
        "teamList"       => $this->getTeamsList($sectionCond),
        "wdMarketList"   => $this->getWdMarketList($sectionCond),
        "wdPopGroupList" => $this->getWdPopGroupList($sectionCond),
      ];
    } else {
      $arrResult = [
        "wdCodeList" => "", "teamType" => "", "teamList" => "",
        "wdMarketList" => "", "wdPopGroupList" => "",
      ];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getTeamType()
  {
    $wdCode     = $this->_data['wdCode'];
    $wdCodeCond = "";
    if ($wdCode) {
      if (!is_array($wdCode)) $wdCode = array($wdCode);
      if (!in_array('all', $wdCode)) {
        $wdCode     = "'" . implode("','", $wdCode) . "'";
        $wdCodeCond = " AND s.wd_code IN ($wdCode)";
      }
      $arrResult = [
        "teamType" => $this->getDsTypeList($wdCodeCond),
        "teamList" => $this->getTeamsList($wdCodeCond),
      ];
    } else {
      $arrResult = ["teamType" => "", "teamList" => ""];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getTeamList()
  {
    $dsType   = $this->_data['dsType'];
    $typeCond = "";
    if (isset($dsType) && $dsType !== "" && $dsType >= 0) {
      if (!is_array($dsType)) $dsType = array($dsType);
      if (!in_array('all', $dsType)) {
        $dsType   = "'" . implode("','", $dsType) . "'";
        $typeCond = " AND s.is_type IN ($dsType)";
      }
      $arrResult = ["teamList" => $this->getTeamsList($typeCond)];
    } else {
      $arrResult = ["teamList" => ""];
    }
    echo json_encode(responseMessage(array(), 1, $arrResult, true));
  }

  final public function getResult($table, $products, $where)
  {
    $sAction3 = null;
    $iRows3   = 0;
    $sQuery3  = "SELECT $products from $table WHERE dstatus = 0 $where ";
    $this->_dbConn->ExecuteSelectQuery($sQuery3, $sAction3, $iRows3);
    $result = "";
    if ($iRows3 > 0) {
      while ($row3 = $this->_dbConn->GetData($sAction3)) {
        $result = array_values($row3);
      }
    }
    return $result;
  }
}
