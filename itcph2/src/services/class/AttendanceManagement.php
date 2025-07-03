<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class AttendanceManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_tables = [];
    private $_arrAccessInfo = [];
    private $_totalTeams = 0;
    private $session;

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
        $this->_tables = $GLOBALS['TABLES'];
    }

    private function getAttendanceCondition($branchColumn = "c.branch_id", $circle = "c.circle", $section = "c.section", $wdCode = "c.wd_code", $teamTypeColumn = "c.is_type")
    {
        $condition = "";
        $branch = getFormData($this->_data, "branch");
        $type = getFormData($this->_data, "teamType");
        $team = getFormData($this->_data, "dsName");
        $circle = getFormData($this->_data, "circle");
        $section = getFormData($this->_data, "section");
        $wdCode = getFormData($this->_data, "wdCode");
        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        $where = "";
        // user has some specific permission
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }
        $branchCond = "dstatus = 0";
        if ($branch) {
            $branch = getFormData($branch);
            $matchAll = checkIfAllSelected($branch);
            if (!$matchAll) {
                if (isNonEmptyArray($branch)) {
                    $branch = implode(",", $branch);
                    $where .= " AND $branchColumn IN ($branch)";
                    $branchCond .= " AND branch_id IN ($branch)";
                } else {
                    $where .= " AND $branchColumn = $branch";
                    $branchCond .= " AND branch_id = $branch";
                }
            }
        }
        if ($circle) {
            $matchAll = checkIfAllSelected($circle);
            if (!$matchAll) {
                if (isNonEmptyArray($circle)) {
                    $circle = "'" . implode("','", $circle) . "'";
                    $where .= " AND circle IN ($circle)";
                    $branchCond .= " AND circle IN ($circle)";
                } else {
                    $where .= " AND circle = '$circle'";
                    $branchCond .= " AND circle = '$circle'";
                }
            }
        }
        if ($section) {
            $matchAll = checkIfAllSelected($section);
            if (!$matchAll) {
                if (isNonEmptyArray($section)) {
                    $section = "'" . implode("','", $section) . "'";
                    $where .= " AND section IN ($section)";
                    $branchCond .= " AND section IN ($section)";
                } else {
                    $where .= " AND section = '$section'";
                    $branchCond .= " AND section = '$circle'";
                }
            }
        }
        if ($wdCode) {
            $matchAll = checkIfAllSelected($wdCode);
            if (!$matchAll) {
                if (isNonEmptyArray($wdCode)) {
                    $wdCode = "'" . implode("','", $wdCode) . "'";
                    $where .= " AND wd_code IN ($wdCode)";
                    $branchCond .= " AND wd_code IN ($wdCode)";
                } else {
                    $where .= " AND wd_code = '$wdCode'";
                    $branchCond .= " AND wd_code = '$wdCode'";
                }
            }
        }
        if (isset($type) && $type != "" && $type >= 0) {
            $matchAll = checkIfAllSelected($type);
            if (!$matchAll) {
                if (isNonEmptyArray($type)) {
                    $type = "'" . implode("','", $type) . "'";
                    $where .= " AND is_type IN ($type)";
                    $branchCond .= " AND is_type IN ($type)";
                } else {
                    $where .= " AND is_type = '$type'";
                    $branchCond .= " AND is_type = '$type'";
                }
            }
        }
        if ($team) {
            $team = getFormData($team);
            $matchAll = checkIfAllSelected($team);
            if (!$matchAll) {
                if (isNonEmptyArray($team)) {
                    $team = implode(",", $team);
                    $where .= " AND a.team_id IN ($team)";
                } else {
                    $where .= " AND a.team_id = $team";
                }
            }
        }
        // get Team list of selected Client, and project
        $sTeam = getTeamsOptions($this->_dbConn, "", "", 0, true, "$branchCond AND s_id = '99'", true);
        if (isset($sTeam) && !isEmptyString($sTeam)) {
            $this->_totalTeams = count(explode(",", $sTeam));
            $where .= " AND a.team_id IN ($sTeam)";
        }
        return $where;
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
                "teamType" => getTeamType($this->_dbConn, $branch),
                "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99'  $branchCond"),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
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
                "teamType" => getTeamType($this->_dbConn, $where),
                "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99' $branchCond  $circleCond"),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond"),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
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
                "teamType" => getTeamType($this->_dbConn, $where),
                "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond"),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond"),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
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
                "teamType" => getTeamType($this->_dbConn, $where),
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond"),
            );
        } else {
            $arrResult = array(
                "teamType" => "",
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamList()
    {
        $dsType = $this->_data['teamType'];
        $wdCode = $this->_data['wdCode'];
        $branch = $this->_data['branch'];
        $circle = $this->_data['circle'];
        $section = $this->_data['section'];
        $wdCodeCond = "";
        $circleCond = "";
        $branchCond = "";
        $sectionCond = "";
        $dsTypeCond = "";
        if ($dsType >= 0 || $wdCode || $branch || $circle || $section) {
            if (isset($dsType) && $dsType != "" && $dsType >= 0) {
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
                "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL  AND s_id = '99' $branchCond $circleCond $sectionCond  $wdCodeCond $dsTypeCond"),
            );
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function getAttendanceTimeList()
    {
        $arrOptions = array(
            array("label" => "Morning", "value" => "0"),
            array("label" => "Day End", "value" => "1"),
        );

        return $arrOptions;
    }

    final public function getAttendanceTrackerData()
    {
        $arrResult = $this->getTeamsAndTimeOptions();
        $arrResult["showAsUserCard"] = true;
        $arrResult["yearList"] = getYearList();
        $arrResult["monthList"] = getMonthList();

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function viewAttendance()
    {
        global $ARR_TEAM_TYPES;
        $arrData = array(
            "tracker" => array(),
            "locator" => array(),
        );

        $date = getFormData($this->_data, "date");
        $date = $date ? currentDate(getValidDate($date)) : currentDate();

        $attendanceTime = getFormData($this->_data, "attendanceTime");
        if ($attendanceTime == '0') {
            $pinColor = "/green-flag.png";
        } elseif ($attendanceTime == '1') {
            $pinColor = "/red-flag.png";
        }

        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $imagesTable = getImageTable();
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $no_img_path = $GLOBALS["LOGO_URL"];

        $where = $this->getAttendanceCondition();
        $where .= " AND a.capture_date = ?";
        $where .= " AND a.call_type = ?";
        $arrParams = array($date, $attendanceTime);

        // $types = array(0 => "VAN DS", 1 => "Hybrid", 2 => "Town SWD", 5 => "NPSR");
        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.att_id, a.uni_id, a.mob_img_id, a.capture_datetime, a.lt, a.lg, c.team_name,c.is_type,c.branch_id,c.circle,c.section,c.wd_code FROM $attendanceTable AS a, $projectTeamTable AS c" .
            " WHERE a.team_id = c.team_id AND a.dstatus = 0 AND c.dstatus = 0 $where GROUP BY a.team_id ORDER BY c.team_name";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows, $arrParams);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $uniId = $row["uni_id"];
                $mobImgId = $row["mob_img_id"];
                $branchId = $row["branch_id"];
                $dsType = $row["is_type"];
                $ARR_TEAM_TYPES[$dsType];

                // Get Image
                $arrImage = getRowColumns($this->_dbConn, $imagesTable, "file_domain, file_path, file_name", "dstatus = 0 AND uni_id = '$uniId' AND mob_img_id = '$mobImgId'");

                $branchName = getRowColumn($this->_dbConn, $branchTable, "main_branch", "dstatus = 0 AND branch_id = '$branchId' ");

                $time = currentDateTime($row["capture_datetime"], "h:i:s A");
                $description = "Team Name: " . $row["team_name"] . " Time: " . $time . " Lt: " . $row["lt"] . " Lg: " . $row["lg"];
                if (isset($arrImage)) {
                    $imgPath = $arrImage[0] . constant("PRODS_ANY_FOLDER") . $arrImage[1];
                    $imgName = $arrImage[2];
                    $thumbImg = $imgPath . "thumb_" . $imgName;
                    $smallImage = true;
                } else {
                    $imgPath = $no_img_path . "/";
                    $imgName = "no-image.jpg";
                    $thumbImg = $imgPath . $imgName;
                    $smallImage = false;
                }
                $trackerDescription = "<div class='attendance-marker'><p class='attendance-label'>" . $row["team_name"] . "<br>Time - " . $time .
                    "</p> <p class='attendance-label'>" . "Branch - " . $branchName . "<br>" . "Circle - " . $row["circle"] . "<br>" . "Section - " . $row["section"] . "<br>" . "WDCode - " . $row["wd_code"] . "<br>" . "DS Type - " . $ARR_TEAM_TYPES[$dsType] . "</p></div>";

                // attandance tracker
                $arrData["tracker"][] = formatListingImage($imgPath, $imgName, $smallImage, false, $description, $row["att_id"], $row["team_name"], $time);

                // attandance locator
                $arrData["locator"][] = array(
                    "latitude" => (float) $row["lt"],
                    "longitude" => (float) $row["lg"],
                    "markerUrl" => $GLOBALS['MARKER_URL'] . $pinColor,
                    "markerTitle" => $row["team_name"],
                    "windowTitle" => $trackerDescription,
                );
            }
        }

        $arrData["total"] = $iRows;
        return $arrData;
    }

    final public function viewAttendanceTracker()
    {
        $arrResult = $this->viewAttendance();

        $arrResponse = array("totalPresent" => $arrResult["total"], "totalTeams" => $this->_totalTeams, "images" => $arrResult["tracker"]);
        if ($arrResult["total"] > 0) {
            $arrMessage = responseMessage(array(), 1, $arrResponse, true);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']), 0, $arrResponse);
        }
        echo json_encode($arrMessage);
    }

    final public function getDownloadData()
    {
        $arrInfo = array();
        $arrBody = array();

        $month = getFormData($this->_data, "month");
        $month = $month ? $month : date("m");
        $year = getFormData($this->_data, "year");
        $attendanceTime = getFormData($this->_data, "attendanceTime");

        $attendanceTable = $this->_tables["ATTENDANCE_TABLE"];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];

        $where = $this->getAttendanceCondition("b.branch_id", "b.is_type");
        $where2 = "AND a.capture_date LIKE ?";
        $where2 .= " AND a.call_type = ?";
        $arrParams = array("$year-$month-%");
        if ($attendanceTime != '') {
            $arrParams[] = $attendanceTime;
        } else {
            $arrParams[] = "0";
        }

        // create header
        $days = date("t", strtotime("$year-$month-01"));
        $header = array();
        $header = range(1, $days);
        array_unshift($header, "District");
        array_unshift($header, "Team ID");
        array_unshift($header, "Team Name");
        array_unshift($header, "WD Code");
        array_unshift($header, "Section");
        array_unshift($header, "Circle");
        array_unshift($header, "Branch");
        $header[] = "Total Present";
        $header[] = "Total Absent";

        $rsAction = null;
        $iRows = 0;
        $sQuery = "SELECT DISTINCT a.capture_date, b.team_id, b.team_name, b.circle, b.section, b.wd_code, b.district, c.branch_name,c.main_branch FROM $attendanceTable AS a, $projectTeamTable as b, $branchTable AS c WHERE a.team_id = b.team_id" .
            " AND a.dstatus = 0 AND b.dstatus = 0 AND c.dstatus = 0 AND b.branch_id = c.branch_id $where $where2 ORDER BY b.team_name, b.team_id, a.capture_date DESC";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iRows, $arrParams);

        if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($rsAction)) {
                $sData_0 = $row['capture_date'];
                $sData_1 = $row['team_name'];
                $sData_3 = $row['team_id'];
                $circle = $row['circle'];
                $section = $row['section'];

                if (!isset($arrInfo[$sData_3])) {
                    $arrInfo[$sData_3] = array();
                }

                $arrInfo[$sData_3][] = array(
                    "dayPresent" => (int) date("d", strtotime($sData_0)),
                    "branchName" => $row['main_branch'],
                    "teamName" => $sData_1,
                    "wdCode" => $row['wd_code'],
                    "district" => $row['district'],
                    "circle" => $row['circle'],
                    "section" => $row['section'],
                );
            }
        }

        if (isNonEmptyArray($arrInfo)) {
            $row = 0;
            foreach ($arrInfo as $iTeamId => $arrAttendance) {
                // count total present and absent of a team
                $totalPresent = 0;
                $totalAbsent = 0;
                $arrBody[$row][] = $arrAttendance[0]["branchName"];
                $arrBody[$row][] = $arrAttendance[0]["circle"];
                $arrBody[$row][] = $arrAttendance[0]["section"];
                $arrBody[$row][] = $arrAttendance[0]["wdCode"];
                $arrBody[$row][] = $arrAttendance[0]["teamName"];
                $arrBody[$row][] = $iTeamId;
                $arrBody[$row][] = $arrAttendance[0]["district"];

                // insert each day attendance
                for ($day = 1; $day <= $days; $day++) {
                    // check if day is present
                    $isDayFound = array_search($day, array_column($arrAttendance, "dayPresent"));

                    if ($isDayFound !== false) {
                        $arrBody[$row][] = "P";
                        $totalPresent++;
                    } else {
                        $arrBody[$row][] = "A";
                        $totalAbsent++;
                    }
                }

                // insert total present and absent
                $arrBody[$row][] = $totalPresent;
                $arrBody[$row][] = $totalAbsent;
                $row++;
            }
        }

        // insert people who were absent in this month i.e not present atleast once in a month
        $where = $this->getAttendanceCondition("a.branch_id", "a.is_type");
        $rsAction2 = null;
        $iRows2 = 0;
        $sQuery2 = "SELECT a.team_id, a.team_name, a.wd_code, a.district,a.circle, a.section, c.branch_name, c.main_branch FROM $projectTeamTable as a, $branchTable AS c WHERE a.branch_id = c.branch_id AND a.dstatus = 0 AND c.dstatus = 0" .
            " AND a.team_id NOT IN (SELECT DISTINCT a.team_id FROM $attendanceTable AS a WHERE a.dstatus = 0 $where2) $where ORDER BY a.team_name";
        $this->_dbConn->ExecuteSelectQuery($sQuery2, $rsAction2, $iRows2, $arrParams);

        if ($iRows2 > 0) {
            while ($row2 = $this->_dbConn->GetData($rsAction2)) {
                $sData_2 = $row2['team_id'];
                $sData_3 = $row2['wd_code'];
                $sData_4 = $row2['team_name'];
                $sData_5 = $row2['district'];
                $sData_6 = $row2['main_branch'];
                $sData_7 = $row2['circle'];
                $sData_8 = $row2['section'];

                $arrAtt = array($sData_6, $sData_7, $sData_8, $sData_3, $sData_4, $sData_2, $sData_5);
                // insert each day attendance
                for ($day = 1; $day <= $days; $day++) {
                    $arrAtt[] = "A";
                }
                // insert total present and absent
                $arrAtt[] = 0;
                $arrAtt[] = $days;

                $arrBody[] = $arrAtt;
            }
        }

        $arrResult = formatDownloadData("Attendance_report", array($header), $arrBody);
        $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
        echo json_encode($arrMessage);
    }

    final public function getAttendanceLocatorData()
    {
        $arrResult = $this->getTeamsAndTimeOptions();

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewAttendanceLocator()
    {
        $arrResult = $this->viewAttendance();

        if ($arrResult["total"] > 0) {
            $arrMessage = responseMessage(array(), 1, array("total" => $arrResult["total"], "markers" => $arrResult["locator"]), true);
        } else {
            $arrMessage = responseMessage(array($GLOBALS['NO_RECORD_FOUND']));
        }
        echo json_encode($arrMessage);
    }

    final public function getTeamsAndTimeOptions()
    {
        $authDetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $user_id = $this->_iUserId;
        $groupId = getRowColumn($this->_dbConn, $authDetailsTable, "group_id", "user_id = $user_id");
        // $groupId = 3;
        if ($groupId == 1 || $groupId == 2) {
            $branchList = getBranchList($this->_dbConn, false, "", "", 1, false, true, "mainBranch");
            $branchFilter = true;
        } elseif ($groupId == 4) {
            $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
            $branchFilter = true;
        } else {
            $branchList = getBranchList($this->_dbConn, false, "", "", 0, false, true, "mainBranch");
            $branchFilter = false;
        }
        $where = "";
        $teamList = $this->_arrAccessInfo["user_teams"];
        if ($teamList) {
            $where .= " AND team_id IN $teamList";
        }
        return array(
            "attendanceTimeList" => $this->getAttendanceTimeList(),
            "branchFilter" => $branchFilter,
            // Don't use dstatus = 0
            "branchList" => $branchList,
            "teamList" => getTeamsOptions($this->_dbConn, "", "", 0, true, "s_id = '99' $where"),
            "teamType" => getTeamType($this->_dbConn),
            "circleList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "team_id IS NOT NULL AND s_id = '99' $where"),
            "sectionList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "team_id IS NOT NULL AND s_id = '99' $where"),
            "wdCodeList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "team_id IS NOT NULL  AND s_id = '99' $where"),
        );
    }

    final public function getBranchTeamTypeList($wdCode = "wd_code")
    {
        $wdCode = $this->_data['wdCode'];
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
        } else {
            $wdCodeCond = "";
        }

        $branchIds = getRowsColumn($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "branch_id", " dstatus = '0' $wdCodeCond ");
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
            "teamType" => getTeamType($this->_dbConn, $where),
            "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", "team_id IS NOT NULL  $wdCodeCond"),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function deleteAttendance()
    {
        deleteListingRecord($this->_dbConn, $this->_tables["ATTENDANCE_TABLE"], "att_id", $this->_iUserId, "", $this->_data, "id");
    }
}
