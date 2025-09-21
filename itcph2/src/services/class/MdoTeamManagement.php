<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class MdoTeamManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];
    private $_arrSeperator = array(
        1 => " ",
        2 => "-",
        3 => "_",
    );

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }

    private function checkAddTeamValidation(
        $project,
        $branch,
        $password,
        $json,
        $wdCode,
        $addMethodType,
        $teams,
        $dsName,
        $startIndex,
        $endIndex,
        $separator
    ) {
        $obj = new Validation();

        $obj->addValidation('project', $project, 'pnz_num', 1, 1, null, 'Project Name', $this->_validationLength['MINVALUE']);
        $obj->addValidation("branch", $branch, 'pz_num', 1, 1, null, 'Branch');
        $obj->addValidation('json', $json, 'json', 1, 1, $this->_validationLength['JSON_MAXLENGTH'], 'JSON');
        // $obj->addValidation('wdCode', $wdCode, 'alnum_s_u_h', 1, 1, $this->_validationLength['WD_CODE_MAXLENGTH'], 'WD Code');
        $obj->addValidation('password', $password, 'pwd', 0, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Password');
        $obj->addValidation('addMethodType', $addMethodType, 'pnz_num', 1, 1, 1, 'Add method', 1, 2);

        // Using Names
        if ($addMethodType == 1) {
            if (isNonEmptyArray($teams)) {
                foreach ($teams as $index => $team) {
                    $obj->addValidation("dsName$index", $team["dsName"], 'alnum_s_u_h', 1, 1, $this->_validationLength['TEAM_NAME_MAXLENGTH'], 'DS Name');
                    if (isset($team["dsPhone"])) {
                        $obj->addValidation("dsPhone$index", $team["dsPhone"], 'mobile', 1, $this->_validationLength['MOBILE_MINLENGTH'], $this->_validationLength['MOBILE_MAXLENGTH'], 'DS Phone');
                    }
                    // $obj->addValidation("dsPhone$index", $team["dsPhone"], 'mobile', 1, 1, $this->_validationLength['MOBILE_MINLENGTH'], 'DS Phone');
                }
            } else {
                $obj->addValidation('dsName', "", 'alnum_s_u_h', 1, 1, $this->_validationLength['TEAM_NAME_MAXLENGTH'], 'DS Name');
                $obj->addValidation('dsPhone', "", 'mobile', 1, $this->_validationLength['MOBILE_MINLENGTH'], $this->_validationLength['MOBILE_MAXLENGTH'], 'DS Phone');
            }
        } else {
            // Using Index
            $obj->addValidation('dsName', $dsName, 'alnum_s_u_h', 1, 1, $this->_validationLength['TEAM_NAME_MAXLENGTH'], 'DS Name');
            $obj->addValidation('startIndex', $startIndex, 'pnz_num', 1, 1, null, 'Start Index', $this->_validationLength['MINVALUE']);
            $obj->addValidation('endIndex', $endIndex, 'pnz_num', 1, 1, null, 'End Index', $this->_validationLength['MINVALUE']);
            $obj->addValidation('separator', $separator, 'pnz_num', 1, 1, 1, 'Separator', $this->_validationLength['MINVALUE']);
        }

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function checkEditTeamValidation($teamName, $phone)
    {
        $obj = new Validation();

        $obj->addValidation('teamName', $teamName, 'alnum_s_u_h', 1, 1, $this->_validationLength['TEAM_NAME_MAXLENGTH'], 'Team Name');
        $obj->addValidation("phone", $phone, 'mobile', 1, $this->_validationLength['MOBILE_MINLENGTH'], $this->_validationLength['MOBILE_MAXLENGTH'], 'Phone Number');
        // $obj->addValidation('password', $password, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Password');
        // $obj->addValidation('json', $json, 'json', 1, 1, $this->_validationLength['JSON_MAXLENGTH'], 'JSON');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function checkTeamAndPhoneIfExists($project, $arrTeams)
    {
        $arrExists = array(
            "hasError" => false,
            "errors" => array(),
        );

        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];

        foreach ($arrTeams as $team) {
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $projectTeamTable, "team_id", "project_id = ? AND ds_number = ?", array($project, $team["dsPhone"]));
            // Don't use dstatus = 0
            $iStatusCloud = isRecordExist($this->_dbConn, $cloudAuthPinTable, "rec_id", "mobile = ?", array($team["dsPhone"]), true);

            if ($iStatus === 1 || $iStatusCloud === 1) {
                $arrExists["hasError"] = true;
                $arrExists["errors"][] = $iStatus === 1 ? "Number'{$team['dsPhone']}' already exists" : "Number'{$team['dsPhone']}' already exists";
            }
        }

        return $arrExists;
    }

    private function getToken($teamId)
    {
        $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];
        $token = sha1(rand() . time() . $teamId);

        // Don't use dstatus = 0
        $isExist = isRecordExist($this->_dbConn, $cloudAuthPinTable, "rec_id", "token = ?", array($token), true);

        // if exist, generate new token
        if ($isExist === 1) {
            $this->getToken($teamId);
        } else {
            return $token;
        }
    }

    final public function getAddTeamData()
    {
        $arrResult = array(
            "projectList" => getProjectOptions($this->_dbConn, "", 0, true, "dstatus = 0"),
            "branchList" => getBranchList($this->_dbConn, true, "dstatus = 0"),
            "circleList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "dstatus = 0", array(), 1),
            "sectionList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "dstatus = 0", array(), 1),
            "wdList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", "dstatus = 0 AND s_id = '99'", array(), 1),
            "aeNameList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "ae_name", "ae_name", "dstatus = 0", array(), 1),
            "dsTypeList" => getTeamType($this->_dbConn),
            "teamList" => getTeamsOptions($this->_dbConn, "", "", 0, true, "s_id = '99'"),
            "separatorList" => array(
                array("label" => "Space", "value" => "1"),
                array("label" => "Hyphen (-)", "value" => "2"),
                array("label" => "Underscore (_)", "value" => "3"),
            ),
            "jsonIdList" => array(
                array("label" => "MDO App", "value" => "10"),
            ),
            "accessList" => array(
                array("label" => "WD Code", "value" => "1"),
                array("label" => "Team", "value" => "2"),
            )
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
            $branch = "'" . implode("','", $branch) . "'";
            $branchCond .= "branch_id IN ($branch)";

            $arrResult = array(
                // Don't use dstatus = 0
                "circleList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "circle", "circle", "$branchCond", array(), 1),
                "sectionList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", "$branchCond", array(), 1),

            );
        } else {
            $arrResult = array(
                "circleList" => "",
                "sectionList" => "",
            );
        }
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getSection($circle = "circle")
    {
        $circle = $this->_data['circle'];
        $branch = $this->_data['branch'];
        $circleCond = "";
        if (isset($circle)) {
            if (!is_array($circle)) {
                $circle = array($circle);
            }
            if (!is_array($branch)) {
                $branch = array($branch);
            }
            $circle = "'" . implode("','", $circle) . "'";
            $circleCond .= " AND circle IN ($circle)";

            $arrResult = array(
                "sectionList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "section", "section", " dstatus = '0'  $circleCond", array(), 1, "NULL", true),
            );
        } else {
            $arrResult = array(
                "sectionList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getWdCode($section = "section")
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if (isset($section)) {
            if (!is_array($section)) {
                $section = array($section);
            }
            $section = "'" . implode("','", $section) . "'";
            $sectionCond .= " AND section IN ($section)";

            $arrResult = array(
                "wdList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "wd_code", "wd_code", " dstatus = '0' AND s_id = '99' $sectionCond", array(), 1),
            );
        } else {
            $arrResult = array(
                "wdList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeamType($wdCode = "wdCode")
    {
        $wdCode = $this->_data['wdCode'];
        if (isset($wdCode)) {
            if (!is_array($wdCode)) {
                $wdCode = array($wdCode);
            }
            $wdCode = implode("','", $wdCode);
            $arrResult = array(
                "dsTypeList" => getTeamType($this->_dbConn, "", $wdCode),
            );
        } else {
            $arrResult = array(
                "dsTypeList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeams($dsType = "dsType", $wdCode = "wdCode")
    {
        $dsType = $this->_data['dsType'];
        $wdCode = $this->_data['wdCode'];
        $where = "";
        if ($wdCode) {
            if (!is_array($wdCode)) {
                $wdCode = array($wdCode);
            }
            $wdCode = "'" . implode("','", $wdCode) . "'";
            $where .= " AND wd_code IN ($wdCode)";
        }
        if (isset($dsType) || isset($wdCode)) {
            if (!is_array($dsType)) {
                $dsType = array($dsType);
            }
            $dsType = "'" . implode("','", $dsType) . "'";
            $where .= " AND is_type IN ($dsType)";
            if ($dsType == 4 || $dsType == 6) {
                $arrResult = array(
                    "teamList" => getOptions($this->_dbConn, "", "", 0, true, $where)
                );
            } else {
                $arrResult = array(
                    "teamList" => getTeamsOptions($this->_dbConn, "", "", 0, true, "s_id = '99' $where")
                );
            }
        } else {
            $arrResult = array(
                "teamList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getAeName($section = "section")
    {
        $section = $this->_data['section'];
        $sectionCond = "";
        if ($section) {
            if (!is_array($section)) {
                $section = array($section);
            }

            $section = "'" . implode("','", $section) . "'";
            $sectionCond .= "AND section IN ($section)";

            $arrResult = array(
                "aeNameList" => getOptionsWithNull($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "ae_name", "ae_name", "dstatus = 0  $sectionCond", array(), 1),
            );
        } else {
            $arrResult = array(
                "aeNameList" => "",
            );
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getJsonName()
    {
        $jsonId = $this->_data['jsonId'];
        if ($jsonId) {
            switch ($jsonId) {
                case 10:
                    $arrResult = array(
                        "jsonName" => "mdoapp_2025.json",
                    );
                    break;
            }
        }

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function addTeam()
    {
        $addMethodType = getFormData($this->_data, "addMethodType");
        $branch = getFormData($this->_data, "branch");
        $accessType = getFormData($this->_data, "accessType");
        $circle = getFormData($this->_data, "circle");
        $section = getFormData($this->_data, "section");
        $wdCode = getFormData($this->_data, "wdCode");
        $dsType = getFormData($this->_data, "dsType");
        $team = getFormData($this->_data, "team");
        $aeName = getFormData($this->_data, "aeName");
        $username = getFormData($this->_data, "username");
        $dsName = getFormData($this->_data, "dsName");
        $json = getFormData($this->_data, "json");
        $jsonId = getFormData($this->_data, "jsonId");
        $endIndex = getFormData($this->_data, "endIndex");
        $password = getFormData($this->_data, "password");
        $project = getFormData($this->_data, "project");
        $separator = getFormData($this->_data, "separator");
        $startIndex = getFormData($this->_data, "startIndex");
        $teams = isset($this->_data["teams"]) ? $this->_data["teams"] : array();
        $isValidated = $this->checkAddTeamValidation(
            $project,
            $branch,
            $password,
            $json,
            $wdCode,
            $addMethodType,
            $teams,
            $dsName,
            $startIndex,
            $endIndex,
            $separator
        );

        // Prefix username
        // $userNamePrefix = constant("CUSTOMER_NAME") . $project . ".";
        $userNamePrefix = "";
        if ($accessType == 1) {
            if ($wdCode) {
                $wdCodes = "'" . implode("','", $wdCode) . "'";
                $arrTeams = getRowsColumns($this->_dbConn, "tblproject_team", "team_id, is_type", "dstatus = 0 AND wd_code IN ($wdCodes) AND s_id = '99'");
                $arrWdId = getRowsColumn($this->_dbConn, "tblmapping_wd", "rec_id", "dstatus = 0 AND wd_code IN ($wdCodes)");
            }
        } else {
            if ($team) {
                $arrTeams = $team;
            }
        }

        //inputs validated
        if ($isValidated) {
            // By Names
            if ($addMethodType == 1) {
                foreach ($teams as $index => $team) {
                    // set username if not set
                    if (!isset($team["username"]) || !$team["username"]) {
                        $teams[$index]["username"] = strtolower($userNamePrefix . str_replace(array(" ", ".", "_", "-"), "", $team["dsName"]));
                    } else {
                        $teams[$index]["username"] = strtolower($userNamePrefix . $team["username"]);
                    }
                }
            } else {
                // by index
                $teams = array();
                for ($i = $startIndex; $i <= $endIndex; $i++) {
                    $sTeamName = $dsName . $this->_arrSeperator[$separator] . $i;
                    $teams[] = array(
                        "teamName" => $sTeamName,
                        "username" => strtolower($userNamePrefix . str_replace(array(" ", ".", "_", "-"), "", $sTeamName)),
                    );
                }
            }

            $arrExists = $this->checkTeamAndPhoneIfExists($project, $teams);

            // Error, some team already exists
            if ($arrExists["hasError"]) {
                $arrMessage = responseMessage($arrExists["errors"]);
            } else {
                $clientsTable = $this->_tables["CLIENTS_TABLE"];
                $projectsTable = $this->_tables["PROJECTS_TABLE"];
                $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
                $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];

                // Begin Transaction
                $this->_dbConn->BeginTransaction();
                $arrStatus = array();
                $aeNumber = "";
                $amName = "";
                $amNumber = "";

                $client = getRowColumn($this->_dbConn, $projectsTable, "client_id", "dstatus = 0 AND project_id = ?", array($project));
                if ($aeName) {
                    $aeNumber = getRowColumn($this->_dbConn, $projectTeamTable, "ae_number", "dstatus = 0 AND ae_name = ?", array($aeName));
                    $amName = getRowColumn($this->_dbConn, $projectTeamTable, "am_name", "dstatus = 0 AND ae_name = ?", array($aeName));
                    $amNumber = getRowColumn($this->_dbConn, $projectTeamTable, "am_number", "dstatus = 0 AND ae_name = ?", array($aeName));
                }
                // $password = strtolower(constant("CUSTOMER_NAME")) . "." . ($password ? $password : $client . $project);
                $password = $password ? $password : $client . $project;
                $cDT = currentDateTime();
                $cD = currentDate();
                $customerFolder = constant("CUSTOMER_FOLDER");

                // Don't use dstatus = 0
                $arrClient = getRowColumns($this->_dbConn, $clientsTable, "client_name, client_dir_path", "client_id = ?", array($client));

                foreach ($teams as $team) {
                    // Add Team
                    $cols = "project_id, s_id, is_type, team_name, branch_id, circle, section, ds_number, ae_name, ae_number, am_name, am_number, creator_id, rcd, rdt";
                    $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                    $arrParams = array($project, $jsonId, 7, $team["dsName"], $branch, $circle, $section, $team["dsPhone"], $aeName, $aeNumber, $amName, $amNumber, $this->_iUserId, $cD, $cDT);

                    $iStatus = addRecord($this->_dbConn, $projectTeamTable, $cols, $vals, $arrParams);
                    $arrStatus[] = $iStatus;

                    // Team added
                    if ($iStatus === 2) {
                        $teamId = 0;
                        $this->_dbConn->GetLastInsertId($teamId);
                        $token = $this->getToken($teamId);

                        foreach ($arrTeams as $accesteam) {
                            $accessCol = "mdo_id, teams, is_type, rcd, rdt";
                            $accessVal = "?, ?, ?, ?, ?";
                            $arraccessParams = array($teamId, $accesteam[0], $accesteam[1], $cD, $cDT);
                            addRecord($this->_dbConn, "tblmdo_access", $accessCol, $accessVal, $arraccessParams);
                        }

                        foreach ($arrWdId as $wdId) {
                            $wdCol = "mdo_id, wd_id, rcd, rdt";
                            $wdVal = "?, ?, ?, ?";
                            $arrWdParams = array($teamId, $wdId, $cD, $cDT);
                            addRecord($this->_dbConn, "tblmdo_wd_mapping", $wdCol, $wdVal, $arrWdParams);
                        }

                        // Add username in cloud table
                        $colsCloud = "s_id, client_res, proj_res_folder, username, mobile, password, client_name, client_id, project_id, team_id, team_name, db_user, db_pwd, db_name, c_subdomain, c_init_xml, token, creator_id, rcd, rdt";
                        $valsCloud = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                        $arrCloudParams = array(
                            $jsonId,
                            $customerFolder,
                            $arrClient[1],
                            $team["username"],
                            $team["dsPhone"],
                            $password,
                            $arrClient[0],
                            $client,
                            $project,
                            $teamId,
                            $team["dsName"],
                            $GLOBALS["DB_USERNAME"],
                            $GLOBALS["DB_PASSWORD"],
                            $GLOBALS["DB_DBNAME"],
                            $GLOBALS["SITE_URL"],
                            $json,
                            $token,
                            $this->_iUserId,
                            $cD,
                            $cDT
                        );

                        $iStatus = addRecord($this->_dbConn, $cloudAuthPinTable, $colsCloud, $valsCloud, $arrCloudParams, true);
                        $arrStatus[] = $iStatus;
                    }
                }

                // Some error, rollback
                if (in_array(0, $arrStatus)) {
                    $this->_dbConn->RollbackTransaction();
                    $arrMessage = responseMessage(array($GLOBALS['TEAM_NOT_ADDED']));
                } else {
                    // All success, commit
                    $this->_dbConn->CommitTransaction();
                    $arrMessage = responseMessage(array($GLOBALS['TEAM_ADDED']), 1);
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function getViewTeamData()
    {
        $arrResult = array(
            // Don't use dstatus = 0
            "branchList" => getBranchList($this->_dbConn),
            "sortOptions" => array(
                array("label" => "Team Name", "value" => "a.team_name"),
                array("label" => "Branch Name", "value" => "b.branch_name"),
                array("label" => "Username", "value" => "c.username"),
                array("label" => "Date Created - ASC", "value" => "a.rdt"),
            ),
            "viewHeader" => array(
                "app.team.view.teamId",
                "app.team.add.name",
                "app.team.add.branch",
                "app.team.view.wdCode",
                "auth.login.form.mobile",
                // "auth.login.form.password",
                "app.team.add.json"
            ),
            "viewBody" => array(
                "id",
                "teamName",
                "branchName",
                "wdCode",
                "mobile",
                // "password",
                "json"
            ),
        );

        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewTeams()
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $cloudDBName = $GLOBALS["DB_DBNAME_CLOUD"];

        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rdt", $this->_data["sort"]);

        // filter by search query
        $where = getFilterResult(
            $this->_data['searchbar'],
            array(
                "branch" => array("a.branch_id", -1),
                "json" => array("c.c_init_xml", 1),
                // "password" => array("c.password", 1),
                "dsName" => array("a.team_name", 1),
                "wdCode" => array("a.wd_code", 1),
                "phone" => array("c.mobile", 1),
            )
        );

        // user has some specific permission
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND a.team_id IN $teamList";
        }

        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.project_id, a.team_id, a.team_name, a.wd_code, b.branch_name,a.ds_number, c.rec_id, c.username, c.password, c.mobile, c.c_init_xml FROM $projectTeamTable AS a, $branchTable AS b" .
            ", $cloudDBName.$cloudAuthPinTable AS c WHERE a.dstatus = 0 AND c.dstatus = 0 AND a.branch_id = b.branch_id AND a.team_id = c.team_id AND c.db_name = '{$GLOBALS['DB_DBNAME']}' $where $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $teamId = $arrData["team_id"];

                $arrResult[] = array(
                    "id" => $teamId,
                    "teamName" => $arrData["team_name"],
                    "projectId" => $arrData["project_id"],
                    "recId" => $arrData["rec_id"],
                    "mobile" => $arrData["mobile"],
                    "password" => $arrData["password"],
                    "json" => $arrData["c_init_xml"],
                    "branchName" => $arrData["branch_name"],
                    "wdCode" => $arrData["wd_code"],
                );
            }
        }

        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function exportTeams()
    {
        $projectsTable = $this->_tables['PROJECTS_TABLE'];
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];
        $branchTable = $this->_tables["BRANCH_TABLE"];
        $cloudDBName = $GLOBALS["DB_DBNAME_CLOUD"];

        // order by condition
        $sOrderCond = getOrderByCond("b.team_name");

        // filter by search query
        $where = getFilterResult(
            $this->_data,
            array(
                "branch" => array("b.branch_id", 1),
                "json" => array("d.c_init_xml", 1),
                // "password" => array("d.password", 1),
                "wdCode" => array("b.wd_code", 1),
                "dsName" => array("b.team_name", 1),
                "phone" => array("c.mobile", 1),
            )
        );

        $projectList = $this->_arrAccessInfo["user_projects"];
        $teamList = $this->_arrAccessInfo["user_teams"];

        // user has some specific permission
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }
        if ($teamList) {
            $where .= " AND b.team_id IN $teamList";
        }

        // Don't use a.dstatus = 0 AND c.dstatus = 0
        $arrBody = array();
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.project_name, b.team_name, b.wd_code, c.branch_name, b.circle, b.section, d.username, d.password, d.mobile FROM $projectsTable AS a, $projectTeamTable AS b, $branchTable AS c, $cloudDBName.$cloudAuthPinTable AS d" .
            " WHERE b.dstatus = 0 AND d.dstatus = 0 AND a.project_id = b.project_id AND b.branch_id = c.branch_id AND b.team_id = d.team_id AND d.db_name = '{$GLOBALS['DB_DBNAME']}' $where $sOrderCond";

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $arrBody[] = array(
                    $arrData["project_name"],
                    $arrData["team_name"],
                    $arrData["branch_name"],
                    $arrData["circle"],
                    $arrData["section"],
                    $arrData["wd_code"],
                    $arrData["mobile"],
                    // $arrData["password"],
                );
            }
        }

        $header = array("Project Name", "Team Name", "Branch", "Circle", "Section", "WD Code", "Phone Number");

        $arrResult = formatDownloadData("Team_details", array($header), $arrBody);
        $arrMessage = responseMessage(array($GLOBALS['DWN_CSV_SUCCESS']), 1, $arrResult);
        echo json_encode($arrMessage);
    }

    final public function editTeam()
    {
        $teamId = getFormData($this->_data, "id");
        $projectId = getFormData($this->_data, "projectId");
        $recId = getFormData($this->_data, "recId");
        $teamName = getFormData($this->_data, "teamName");
        $phone = getFormData($this->_data, "phone");
        // $password = getFormData($this->_data, "password");
        // $json = getFormData($this->_data, "json");

        $isValidated = $this->checkEditTeamValidation($teamName, $phone);

        //inputs validated
        if ($isValidated) {
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $cloudAuthPinTable = $this->_tables["CLOUD_AUTHPIN_TABLE"];

            //check if team or username exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $projectTeamTable, "team_id", "team_id != ? AND project_id = ? AND ds_number = ?", array($teamId, $projectId, $phone));
            // Don't use dstatus = 0
            $iStatusCloud = isRecordExist($this->_dbConn, $cloudAuthPinTable, "rec_id", "rec_id != ? AND mobile = ?", array($recId, $phone), true);

            // Team not exist, edit
            if ($iStatus === 0 && $iStatusCloud === 0) {
                $cols = "team_name = ?, ds_number = ?, modif_id = ?";
                $arrParams = array($teamName, $phone, $this->_iUserId, $teamId);

                $iStatus = updateRecord($this->_dbConn, $projectTeamTable, $cols, "dstatus = 0 AND team_id = ?", $arrParams);

                $colsCloud = "team_name = ?, mobile = ?, modif_id = ?";
                $arrParamsCloud = array($teamName, $phone, $this->_iUserId, $recId);

                $iStatusCloud = updateRecord($this->_dbConn, $cloudAuthPinTable, $colsCloud, "dstatus = 0 AND rec_id = ?", $arrParamsCloud, true);

                // team modified
                if ($iStatus === 1 || $iStatusCloud === 1) {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_EDITED_SUCCESSFULL']), 1);
                } else {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']));
                }
            } else {
                if ($iStatus === 1) {
                    $arrMessage = responseMessage(array($GLOBALS['TEAM_EXISTS']));
                } else {
                    $arrMessage = responseMessage(array($GLOBALS['USERNAME_EXISTS']));
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
