<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class SuperUserManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];
    private $_appConstants = [];
    private $_loginTypeList = [
        ["label" => "Admin Level", "value" => "1"],
        ["label" => "Client Level", "value" => "2"],
        ["label" => "Project Level", "value" => "3"],
        ["label" => "Branch Level", "value" => "4"],
        ["label" => "WD Code Level", "value" => "5"],
        ["label" => "Circle Level", "value" => "6"],
        ["label" => "Section Level", "value" => "7"],
        ["label" => "Team Level", "value" => "8"],
        ["label" => "Team Type Level", "value" => "9"],
    ];

    public function __construct($dbConn, $data, $iUserId)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_iUserId = $iUserId;
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
        $this->_tables = $GLOBALS['TABLES'];
        $this->_appConstants = $GLOBALS['APP_CONSTANTS'];
    }

    // User Functions Start
    private function checkUserValidation($isEdit, $fullName, $email, $userName, $password, $confirmPassword, $group, $landing, $accessType, $client, $project, $branch)
    {
        $obj = new Validation();
        $obj->addValidation('fullname', $fullName, 'alnum_s', 1, 1, $this->_validationLength['NAME_MAXLENGTH'], 'Full Name');
        $obj->addValidation('email', $email, 'email', 0, $this->_validationLength['EMAIL_MINLENGTH'], $this->_validationLength['EMAIL_MAXLENGTH'], 'Email');
        $obj->addValidation('username', $userName, 'username', 1, $this->_validationLength['USERNAME_MINLENGTH'], $this->_validationLength['USERNAME_MAXLENGTH'], 'Username');
        if (!$isEdit || ($isEdit && ($password || $confirmPassword))) {
            $obj->addValidation('password', $password, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Password');
            $obj->addValidation('cpassword', $confirmPassword, 'pwd', 1, $this->_validationLength['PASSWORD_MINLENGTH'], $this->_validationLength['PASSWORD_MAXLENGTH'], 'Confirm Password');
        }
        $obj->addValidation('group', $group, 'pnz_num', 1, 1, null, 'Group Name', $this->_validationLength['MINVALUE']);
        $obj->addValidation('landing', $landing, 'pnz_num', 1, 1, null, 'Landing Page', $this->_validationLength['MINVALUE']);
        $obj->addValidation('accessType', $accessType, 'pnz_num', 1, 1, null, 'Access Type', $this->_validationLength['MINVALUE']);

        // validate client
        if ($accessType == 2) {
            $this->validateInputArray($obj, $client, "client", "Client");
        } elseif ($accessType == 3) {
            // validate project
            $this->validateInputArray($obj, $project, "project", "Project");
        } elseif ($accessType == 4) {
            // validate branch
            $this->validateInputArray($obj, $branch, "branch", "Branch");
        }

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    private function validateInputArray($obj, $arrValue, $type, $message, $minValue = 1)
    {
        if (isNonEmptyArray($arrValue)) {
            // check if All selected
            $matchAll = checkIfAllSelected($arrValue);

            if (!$matchAll) {
                foreach ($arrValue as $value) {
                    if ($value != $this->_appConstants['ALL_VALUE']) {
                        $obj->addValidation($type, $value, 'pnz_num', 1, 1, null, $message, $minValue);
                    }
                }
            }
        } else {
            $obj->addValidation($type, $arrValue, 'pnz_num', 1, 1, null, $message, $minValue);
        }
    }

    final public function addUser()
    {
        $confirmPassword = getFormData($this->_data, "confirmPassword");
        $fullName = getFormData($this->_data, "fullname");
        $email = getFormData($this->_data, "email");
        $group = getFormData($this->_data, "group");
        $landing = getFormData($this->_data, "landing");
        $password = getFormData($this->_data, "password");
        $userName = getFormData($this->_data, "username");
        $accessType = getFormData($this->_data, 'type');
        $client = getFormData($this->_data, "client");
        $project = getFormData($this->_data, "project");
        $branch = getFormData($this->_data, 'branch');
        $wdCode = getFormData($this->_data, 'wdCode');
        $circle = getFormData($this->_data, 'circle');
        $section = getFormData($this->_data, 'section');
        $team = getFormData($this->_data, 'team');
        $teamType = getFormData($this->_data, 'teamType');

        $isValidated = $this->checkUserValidation(false, $fullName, $email, $userName, $password, $confirmPassword, $group, $landing, $accessType, $client, $project, $branch);

        //inputs validated
        if ($isValidated) {
            //password criteria not match
            if (constant("STRONG_PASSWORD_CHECK_FUNC") && !strongPwdCheck($password)) {
                $arrMessage = responseMessage([$GLOBALS['WEAK_PASSWORD']]);
            } elseif (!matchValue($password, $confirmPassword, true)) {
                //new and confirm password not match
                $arrMessage = responseMessage([$GLOBALS['PASSWORD_MISMATCH']]);
            } else {
                //check if user exists
                $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
                // Don't use dstatus = 0
                $iStatus = isRecordExist($this->_dbConn, $userAuthdetailsTable, "user_id", "auth_name = ?", [$userName]);

                //user not exist
                if ($iStatus === 0) {
                    $cDT = currentDateTime();
                    $cD = currentDate();

                    $sauth_salt = pseudoRandomKey(32);
                    $usr_pwd = securePassword($password, $sauth_salt);

                    // get landing page
                    $landingPage = getRowColumns($this->_dbConn, $this->_tables["MODULES_TABLE"], "module_code, parent_module_code", "dstatus = 0 AND module_id = ?", [$landing]);

                    $cols = "auth_name, group_id, landing_modc, landing_pmodc, access_type, usr_fullname, usr_email, auth_pwd, last_pwd_update, creator_id, rcd, rdt";
                    $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                    $arrParams = [$userName, $group, $landingPage[0], $landingPage[1], $accessType - 1, $fullName, $email, $usr_pwd, $cDT, $this->_iUserId, $cD, $cDT];

                    $iStatus = addRecord($this->_dbConn, $userAuthdetailsTable, $cols, $vals, $arrParams);

                    // user added
                    if ($iStatus == 2) {
                        $user_id = null;
                        $this->_dbConn->GetLastInsertId($user_id);

                        $this->createAccess($user_id, $cD, $cDT, $accessType, $client, $project, $branch, $wdCode, $circle, $section, $team, $teamType);

                        $arrMessage = responseMessage([$GLOBALS['USER_ADDED']], 1);
                    } else {
                        $arrMessage = responseMessage([$GLOBALS['USER_NOT_ADDED']]);
                    }
                } else {
                    $arrMessage = responseMessage([$GLOBALS['USER_EXISTS']]);
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    private function createAccess($user_id, $cD, $cDT, $accessType, $arrClient, $arrProject, $arrBranch, $arrWdCode, $arrCircle, $arrSection, $arrTeam, $arrTeamType)
    {
        $accessType = (int) $accessType;
        $arrStatus = [];

        // Admin Login
        if ($accessType === 1) {
            $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, null, null, null, 0, 999);
        } elseif ($accessType === 2) {
            // Client Login
            foreach ($arrClient as $iClient) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, $iClient, 0, 0, null, null, null, 0, 999);
            }
        } elseif ($accessType === 3) {
            // Project Login
            foreach ($arrProject as $iProject) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, $iProject, 0, null, null, null, 0, 999);
            }
        } elseif ($accessType === 4) {
            // Branch Login
            foreach ($arrBranch as $iBranch) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, $iBranch, null, null, null, 0, 999);
            }
        } elseif ($accessType === 5) {
            // WD Code Login
            foreach ($arrWdCode as $sWdCode) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, $sWdCode, null, null, 0, 999);
            }
        } elseif ($accessType === 6) {
            // Circle Login
            foreach ($arrCircle as $sCircle) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, null, $sCircle, null, 0, 999);
            }
        } elseif ($accessType === 7) {
            // Section Login
            foreach ($arrSection as $sSection) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, null, null, $sSection, 0, 999);
            }
        } elseif ($accessType === 8) {
            // Team Login
            foreach ($arrTeam as $sTeam) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, null, null, null, $sTeam, 999);
            }
        } elseif ($accessType === 9) {
            // Team Type Login
            foreach ($arrTeamType as $sTeamType) {
                $arrStatus[] = $this->modifyAccess($user_id, $cD, $cDT, 0, 0, 0, null, null, null, 0, $sTeamType);
            }
        }

        return $arrStatus;
    }

    private function modifyAccess($user_id, $cD, $cDT, $iClient, $iProject, $iBranch, $sWdCode, $sCircle, $sSection, $sTeam, $sTeamType)
    {
        $cols = "user_id, client_id, project_id, branch_id, wd_code, circle, section, team_id, team_type, creator_id, rcd, rdt";
        $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
        $arrParams = [$user_id, $iClient, $iProject, $iBranch, $sWdCode, $sCircle, $sSection, $sTeam, $sTeamType, $this->_iUserId, $cD, $cDT];

        $iStatus = addRecord($this->_dbConn, $this->_tables["USER_ACCESS_TABLE"], $cols, $vals, $arrParams, false);

        return $iStatus;
    }

    final public function getTeamType()
    {
        $arrTypes = [];
        global $ARR_TEAM_TYPES;
        foreach ($ARR_TEAM_TYPES as $id => $teamType) {
            $arrTypes[] = [
                "label" => $teamType,
                "value" => $id,
            ];
        }

        return $arrTypes;
    }

    final public function getUserData($fromListing)
    {
        $arrResult = [
            "loginTypeList" => $this->_loginTypeList,
            "clientList" => getClients($this->_dbConn, 0, "dstatus = 0"),
            "projectList" => getProjectOptions($this->_dbConn, "", 0, true, "dstatus = 0"),
            "branchList" => getBranchList($this->_dbConn, true, "dstatus = 0"),
            "wdCodeList" => getOptions($this->_dbConn, $this->_tables["PROJECT_TEAM_TABLE"], "wd_code", "", "dstatus = 0"),
            "circleList" => getOptions($this->_dbConn, $this->_tables["PROJECT_TEAM_TABLE"], "circle", "", "dstatus = 0 AND circle is not null AND circle != ''"),
            "sectionList" => getOptions($this->_dbConn, $this->_tables["PROJECT_TEAM_TABLE"], "section", "", "dstatus = 0 AND section is not null AND section != ''"),
            "teamList" => getOptions($this->_dbConn, $this->_tables["PROJECT_TEAM_TABLE"], "team_name", "team_id", "dstatus = 0 AND team_name is not null AND team_name != ''"),
            "teamTypeList" => $this->getTeamType(),
            "groupList" => getOptions($this->_dbConn, $this->_tables["GROUPS_TABLE"], "group_name", "group_id", $fromListing ? "" : "dstatus = 0"),
            "landingPageList" => getLandingPageList($this->_dbConn, $fromListing),
            "sortOptions" => [
                ["label" => "Full Name", "value" => "a.usr_fullname"],
                ["label" => "Group Name", "value" => "b.group_name"],
                ["label" => "Username", "value" => "a.auth_name"],
                ["label" => "User Created - ASC", "value" => "a.rdt"],
            ],
            "viewHeader" => [
                "app.user.user.view.userId", "app.user.user.add.fullname", "auth.login.form.username", "app.user.user.add.userType", "app.user.user.add.landingPage", "app.user.group.form.groupName"
            ],
            "viewBody" => ["id", "name", "username", "userType", "landingPage", "group"],
            "unlockCondition" => ["isAccountLocked", false],
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewUsers()
    {
        $arrResult = [];

        // order by condition
        $sOrder_cond = getOrderByCond("a.rdt", $this->_data["sort"]);
        // filter by search query
        $where = getFilterResult($this->_data['searchbar'], ["name" => ["a.usr_fullname", 1], "username" => ["a.auth_name", 1], "group" => ["a.group_id", -1]]);

        $userType = getFormData($this->_data['searchbar'], "userType");
        if ($userType) {
            $where .= " AND a.access_type = " . ($userType - 1);
        }

        $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
        $groupsTable = $this->_tables["GROUPS_TABLE"];
        $userAccessTable = $this->_tables["USER_ACCESS_TABLE"];

        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.user_id, a.auth_name, a.usr_fullname, a.group_id, a.landing_modc, a.landing_pmodc, a.access_type, a.login_attempts, b.group_name FROM $userAuthdetailsTable AS a, $groupsTable AS b WHERE a.dstatus = 0 AND a.group_id = b.group_id $where $sOrder_cond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $userId = $arrData["user_id"];
                $userTypeIndex = array_search($arrData['access_type'] + 1, array_column($this->_loginTypeList, "value"));
                $userType = $this->_loginTypeList[$userTypeIndex]["label"];

                // all clients, projects, branches, wd codes
                $arrAccess = [];
                if (matchValue($arrData['access_type'], 0)) {
                    $arrAccess = [
                        "client" => [],
                        "project" => [],
                        "branch" => [],
                        "wdCode" => [],
                        "circle" => [],
                        "section" => [],
                        "team" => [],
                        "teamType" => [],
                    ];
                } else {
                    // specific clients
                    if (matchValue($arrData['access_type'], 1)) {
                        $arrAccess = [
                            "client" => getRowsColumn($this->_dbConn, $userAccessTable, "client_id", "dstatus = 0 AND user_id = $userId AND client_id > 0"),
                            "project" => [],
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => [],
                            "section" => [],
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 2)) {
                        // specific projects
                        $arrAccess = [
                            "client" => [],
                            "project" => getRowsColumn($this->_dbConn, $userAccessTable, "project_id", "dstatus = 0 AND user_id = $userId AND project_id > 0"),
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => [],
                            "section" => [],
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 3)) {
                        // specific branches
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => getRowsColumn($this->_dbConn, $userAccessTable, "branch_id", "dstatus = 0 AND user_id = $userId AND branch_id > 0"),
                            "wdCode" => [],
                            "circle" => [],
                            "section" => [],
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 4)) {
                        // specific wd codes
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => [],
                            "wdCode" => getRowsColumn($this->_dbConn, $userAccessTable, "wd_code", "dstatus = 0 AND user_id = $userId AND wd_code IS NOT NULL"),
                            "circle" => [],
                            "section" => [],
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 5)) {
                        // specific circles
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => getRowsColumn($this->_dbConn, $userAccessTable, "circle", "dstatus = 0 AND user_id = $userId AND circle IS NOT NULL"),
                            "section" => [],
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 6)) {
                        // specific circles
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => [],
                            "section" => getRowsColumn($this->_dbConn, $userAccessTable, "section", "dstatus = 0 AND user_id = $userId AND section IS NOT NULL"),
                            "team" => [],
                            "teamType" => [],
                        ];
                    } elseif (matchValue($arrData['access_type'], 7)) {
                        // specific team
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => [],
                            "section" => [],
                            "team" => getRowsColumn($this->_dbConn, $userAccessTable, "team_id", "dstatus = 0 AND user_id = $userId AND team_id > 0"),
                            "teamType" => [],
                        ];
                    } else {
                        // specific Team Type
                        $arrAccess = [
                            "client" => [],
                            "project" => [],
                            "branch" => [],
                            "wdCode" => [],
                            "circle" => [],
                            "section" => [],
                            "team" => [],
                            "teamType" => getRowsColumn($this->_dbConn, $userAccessTable, "team_type", "dstatus = 0 AND user_id = $userId"),

                        ];
                    }
                }

                // Don't use dstatus = 0
                $landingPage = getRowColumns($this->_dbConn, $this->_tables["MODULES_TABLE"], "module_id, module_name", "module_code = ? AND parent_module_code = ?", [$arrData['landing_modc'], $arrData['landing_pmodc']]);

                $arrResult[] = [
                    "id" => $userId,
                    "name" => $arrData['usr_fullname'],
                    "username" => $arrData['auth_name'],
                    "type" => ($arrData['access_type'] + 1) . '',
                    "userType" => $userType,
                    "landingPageId" => $landingPage[0],
                    "landingPage" => $landingPage[1],
                    "groupId" => $arrData['group_id'],
                    "group" => $arrData['group_name'],
                    "client" => isset($arrAccess['client']) ? $arrAccess['client'] : "",
                    "project" => isset($arrAccess['project']) ? $arrAccess['project'] : "",
                    "branch" => isset($arrAccess['branch']) ? $arrAccess['branch'] : "",
                    "wdCode" => isset($arrAccess['wdCode']) ? $arrAccess['wdCode'] : "",
                    "circle" => isset($arrAccess['circle']) ? $arrAccess['circle'] : "",
                    "section" => isset($arrAccess['section']) ? $arrAccess['section'] : "",
                    "team" => isset($arrAccess['team']) ? $arrAccess['team'] : "",
                    "teamType" => isset($arrAccess['teamType']) ? $arrAccess['teamType'] : "",
                    "isAccountLocked" => $arrData['login_attempts'] >= constant('MAX_LOGIN_ATTEMPTS') ? true : false,
                ];
            }
        }
        $arrResult[] = ["total" => $limit["total"]];

        $arrMessage = responseMessage([], 1, ["data0" => $arrResult], true);
        echo json_encode($arrMessage);
    }

    final public function editUser()
    {
        $userId = getFormData($this->_data, "id");
        $fullname = getFormData($this->_data, "name");
        $landingPageId = getFormData($this->_data, "landingPageId");
        $groupId = getFormData($this->_data, "groupId");
        $username = getFormData($this->_data, "username");
        $password = getFormData($this->_data, "password");
        $confirmPassword = getFormData($this->_data, "confirmPassword");
        $accessType = getFormData($this->_data, "type");
        $client = getFormData($this->_data, "client");
        $project = getFormData($this->_data, "project");
        $branch = getFormData($this->_data, "branch");
        $wdCode = getFormData($this->_data, "wdCode");
        $circle = getFormData($this->_data, "circle");
        $section = getFormData($this->_data, "section");
        $team = getFormData($this->_data, "team");
        $teamType = getFormData($this->_data, "teamType");

        $isValidated = $this->checkUserValidation(true, $fullname, "", $username, $password, $confirmPassword, $groupId, $landingPageId, $accessType, $client, $project, $branch);

        //inputs validated
        if ($isValidated) {
            $userAuthdetailsTable = $this->_tables["USER_AUTHDETAILS_TABLE"];
            $groupsTable = $this->_tables["GROUPS_TABLE"];
            $userAccessTable = $this->_tables["USER_ACCESS_TABLE"];

            //check if another user exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $userAuthdetailsTable, "user_id", "user_id != ? AND auth_name = ?", [$userId, $username]);

            //user not exist
            if ($iStatus === 0) {
                $isError = false;
                // If password filled by user
                if ($password || $confirmPassword) {
                    //password criteria not match
                    if (constant("STRONG_PASSWORD_CHECK_FUNC") && !strongPwdCheck($password)) {
                        $isError = true;
                        $arrMessage = responseMessage([$GLOBALS['WEAK_PASSWORD']]);
                    } elseif (!matchValue($password, $confirmPassword, true)) {
                        //new and confirm password not match
                        $isError = true;
                        $arrMessage = responseMessage([$GLOBALS['PASSWORD_MISMATCH']]);
                    }
                }

                // No error in password
                if (!$isError) {
                    $cD = currentDate();
                    $cDT = currentDateTime();

                    // get landing page
                    $landingPage = getRowColumns($this->_dbConn, $this->_tables["MODULES_TABLE"], "module_code, parent_module_code", "dstatus = 0 AND module_id = ?", [$landingPageId]);

                    if (isset($landingPage, $landingPage[0]) && $landingPage) {
                        // Check if Group exist
                        $iStatus = isRecordExist($this->_dbConn, $groupsTable, "group_id", "dstatus = 0 AND group_id = ?", [$groupId]);

                        if ($iStatus === 1) {
                            $cols = "auth_name = ?, usr_fullname = ?, group_id = ?, landing_modc = ?, landing_pmodc = ?, access_type = ?, modif_id = ?";
                            $arrParams = [$username, $fullname, $groupId, $landingPage[0], $landingPage[1], $accessType - 1, $this->_iUserId];

                            if ($password) {
                                $sauth_salt = pseudoRandomKey(32);
                                $usr_pwd = securePassword($password, $sauth_salt);

                                $cols .= ", auth_pwd = ?, temp_pwd = '', temp_flag = 0, last_pwd_update = ?, login_attempts = 0";
                                $arrParams[] = $usr_pwd;
                                $arrParams[] = $cDT;
                            }
                            $arrParams[] = $userId;

                            $this->_dbConn->BeginTransaction();

                            // Delete old records
                            deleteRecord($this->_dbConn, $userAccessTable, "user_id", $this->_iUserId, "", [$userId]);
                            // create new access
                            $arrStatus = $this->createAccess($userId, $cD, $cDT, $accessType, $client, $project, $branch, $wdCode, $circle, $section, $team, $teamType);

                            $iStatus = updateRecord($this->_dbConn, $userAuthdetailsTable, $cols, "dstatus = 0 AND user_id = ?", $arrParams);

                            // user modified
                            if ($iStatus == 1 || (isNonEmptyArray($arrStatus) && !in_array(0, $arrStatus))) {
                                $this->_dbConn->CommitTransaction();
                                $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
                            } else {
                                $this->_dbConn->RollbackTransaction();
                                $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']]);
                            }
                        } else {
                            $arrMessage = responseMessage([$GLOBALS['GROUP_NO_LONGER_EXISTS']]);
                        }
                    } else {
                        $arrMessage = responseMessage([$GLOBALS['LANDING_PAGE_NO_LONGER_EXISTS']]);
                    }
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['USER_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    public function unlockUsers()
    {
        $ids = getFormData($this->_data, "ids");
        $sIds = isNonEmptyArray($ids) ? implode(",", $ids) : $ids;

        $iStatus = unlockUsers($this->_dbConn, $sIds);

        if ($iStatus > 0) {
            $arrMessage = responseMessage([$GLOBALS['USER_UNLOCK_SUCCESSFUL']], 1);
        } else {
            $arrMessage = responseMessage([$GLOBALS['USER_UNLOCK_FAILED']]);
        }

        echo json_encode($arrMessage);
    }
    // User Functions End

    // Group Functions Start
    private function checkGroupValidation($name)
    {
        $obj = new Validation();
        $obj->addValidation('name', $name, 'alnum_s', 1, 1, $this->_validationLength['NAME_MAXLENGTH'], 'Group Name');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function getGroupData()
    {
        $isEdit = getFormData($this->_data, "isEdit");
        $arrResult = [
            "modulesList" => $this->getModulesList(),
            "groupData" => $isEdit ? $this->getGroupEditData() : null,
            "sortOptions" => [
                ["label" => "Group Name", "value" => "group_name"],
                ["label" => "Group Created - ASC", "value" => "rdt"],
            ],
        ];
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    private function getModulesList()
    {
        $arrModules = [];
        $sAction = null;
        $iRows = 0;
        $modulesTable = $this->_tables["MODULES_TABLE"];
        $sQuery = "SELECT module_id, module_name, parent_module_code FROM $modulesTable WHERE dstatus = 0 AND module_id > 2 ORDER BY module_code, parent_module_code";
        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                if ($arrData['parent_module_code'] === '0') {
                    $arrModules[] = [];
                }

                $len = count($arrModules);

                $arrModules[$len - 1][] = [
                    "label" => $arrData['module_name'],
                    "value" => $arrData['module_id'],
                ];
            }
        }

        // sort the modules list by no of modules in ascending order
        usort($arrModules, [$this, "sortByCount"]);
        return $arrModules;
    }

    private function sortByCount($previous, $next)
    {
        if (count($previous) === count($next)) {
            return 0;
        } elseif (count($previous) > count($next)) {
            return 1;
        }
        return -1;
    }

    final public function addGroup()
    {
        $name = getFormData($this->_data, "name");
        $items = "1,2," . implode(",", getFormData($this->_data, "items"));

        $isValidated = $this->checkGroupValidation($name);

        //inputs validated
        if ($isValidated) {
            $groupsTable = $this->_tables["GROUPS_TABLE"];

            //check if group exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $groupsTable, "group_id", "group_name = ?", [$name]);

            //group not exist
            if ($iStatus === 0) {
                $cDT = currentDateTime();
                $cD = currentDate();

                $cols = "group_name, role_permission, creator_id, rcd, rdt";
                $vals = "?, ?, ?, ?, ?";
                $arrParams = [$name, $items, $this->_iUserId, $cD, $cDT];

                $iStatus = addRecord($this->_dbConn, $groupsTable, $cols, $vals, $arrParams);

                // group added
                if ($iStatus == 2) {
                    $arrMessage = responseMessage([$GLOBALS['GROUP_ADDED']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['GROUP_NOT_ADDED']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['GROUP_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function viewGroups()
    {
        $arrResult = [];

        // order by condition
        $sOrder_cond = getOrderByCond("rdt", $this->_data["sort"]);
        // filter by search query
        $where = getFilterResult($this->_data['searchbar'], ["name" => ["group_name", 1]]);

        $groupsTable = $this->_tables["GROUPS_TABLE"];
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT group_id, group_name, role_permission FROM $groupsTable WHERE dstatus = 0 $where $sOrder_cond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $modules = getRowsColumn($this->_dbConn, $this->_tables["MODULES_TABLE"], "module_name", "dstatus = 0 AND module_id in ({$arrData['role_permission']})");
                $sModules = getStringFromArray($modules, true, " | ");
                $arrResult[] = [
                    "name" => $arrData['group_name'],
                    "id" => $arrData['group_id'],
                    "modules" => $sModules,
                ];
            }
        }
        $arrResult[] = ["total" => $limit["total"]];

        $arrMessage = responseMessage([], 1, ["data0" => $arrResult], true);
        echo json_encode($arrMessage);
    }

    private function getGroupEditData()
    {

        $id = getFormData($this->_data, "id");
        $group = getRowColumns($this->_dbConn, $this->_tables["GROUPS_TABLE"], "group_name, role_permission", "dstatus = 0 AND group_id = '$id'");
        if ($group && count($group)) {
            $arrResult = [
                "name" => $group[0],
                "items" => $group[1],
            ];
            return $arrResult;
        }
        return null;
    }

    final public function editGroup()
    {
        $groupId = getFormData($this->_data, "id");
        $name = getFormData($this->_data, "name");
        $items = getFormData($this->_data, "items");
        if (isNonEmptyArray($items) && !in_array(2, $items)) {
            array_unshift($items, "2");
        }
        if (isNonEmptyArray($items) && !in_array(1, $items)) {
            array_unshift($items, "1");
        }
        sort($items);
        $items = implode(",", $items);

        $isValidated = $this->checkGroupValidation($name);

        //inputs validated
        if ($isValidated && $items) {
            $groupsTable = $this->_tables["GROUPS_TABLE"];

            //check if group exists
            $iStatus = isRecordExist($this->_dbConn, $groupsTable, "group_id", "dstatus = 0 AND group_id = ?", [$groupId]);

            // Edit Group not exists
            if ($iStatus === 0) {
                $arrMessage = responseMessage([$GLOBALS['GROUP_NOT_EXISTS']]);
            } else {
                //check if same group exists
                // Don't use dstatus = 0
                $iStatus = isRecordExist($this->_dbConn, $groupsTable, "group_id", "group_id != ? AND group_name = ?", [$groupId, $name]);

                //group not exist
                if ($iStatus === 0) {
                    $cols = "group_name = ?, role_permission = ?, modif_id = ?";
                    $arrParams = [$name, $items, $this->_iUserId, $groupId];

                    $iStatus = updateRecord($this->_dbConn, $groupsTable, $cols, "dstatus = 0 AND group_id = ?", $arrParams);

                    // group modified
                    if ($iStatus == 1) {
                        $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
                    } else {
                        $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']]);
                    }
                } else {
                    $arrMessage = responseMessage([$GLOBALS['GROUP_EXISTS']]);
                }
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
    // Group Function End

    // Module Functions Start
    private function checkModuleValidation($id, $icon, $modc, $moduleActionCode, $moduleComponent, $modulePos, $name, $pmodc, $sort, $url, $breadcrumb)
    {
        $obj = new Validation();

        $obj->addValidation('id', $id, 'pnz_num', 0, null, null, 'Module Name', $this->_validationLength['MINVALUE']);
        $obj->addValidation('name', $name, 'alnum_s', 1, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_NAME_MAXLENGTH'], 'Module Name');
        $obj->addValidation('modc', $modc, 'modc', 1, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_CODE_MAXLENGTH'], 'Module Code');
        $obj->addValidation('pmodc', $pmodc, 'modc', 1, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_CODE_MAXLENGTH'], 'Parent Module Code');
        $obj->addValidation('moduleComponent', $moduleComponent, 'alpha', 0, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_COMPONENT_MAXLENGTH'], 'Module Component');
        $obj->addValidation('moduleActionCode', $moduleActionCode, 'alpha', 1, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_CODE_MAXLENGTH'], 'Module Action Code');
        $obj->addValidation('modulePos', $modulePos, 'alpha', 1, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_POSITION_MAXLENGTH'], 'Module Position');
        $obj->addValidation('icon', $icon, 'alpha_h', 0, $this->_validationLength['MINLENGTH'], $this->_validationLength['MODULE_ICON_MAXLENGTH'], 'Module Icon');
        $obj->addValidation('url', $url, 'mod_url', 0, $this->_validationLength['MINLENGTH'], $this->_validationLength['MAXLENGTH'], 'Module URL');
        $obj->addValidation('sort', $sort, 'pnz_num', 1, null, null, 'Module Sort', $this->_validationLength['MINVALUE']);
        $obj->addValidation('breadcrumb', $breadcrumb, 'pz_num', 1, null, null, 'Show Breadcrumb', $this->_validationLength['MINVALUE'] - 1, $this->_validationLength['MINVALUE']);

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function getModuleData()
    {
        $arrResult = [
            "moduleActionCodeList" => [
                ["label" => "ADD", "value" => "ADD"],
                ["label" => "DELETE", "value" => "DEL"],
                ["label" => "EDIT", "value" => "EDIT"],
                ["label" => "VIEW", "value" => "VIEW"],
                ["label" => "MAP", "value" => "MAP"],
            ],
            "modulePositionList" => [
                ["label" => "Actionbar", "value" => "actionbar"],
                ["label" => "Leftside", "value" => "leftside"],
                ["label" => "Navbar", "value" => "navbar"],
                ["label" => "Topbar", "value" => "topbar"],
            ],
            "breadcrumbList" => [
                ["label" => "Yes", "value" => 1],
                ["label" => "No", "value" => 0],
            ],
            "sortOptions" => [
                ["label" => "Module Name", "value" => "module_name"],
                ["label" => "Module Code", "value" => "module_code"],
                ["label" => "Module Position", "value" => "module_position"],
                ["label" => "Module Actioncode", "value" => "module_actioncode"],
                ["label" => "Module Added - ASC", "value" => "rdt"],
            ],
            "viewHeader" => [
                "app.user.module.add.moduleId", "app.user.module.add.moduleName",
                "app.user.module.add.moduleCode", "app.user.module.add.parentModuleCode", "app.user.module.add.moduleActionCode", "app.user.module.add.moduleComponent",
                "app.user.module.add.moduleIcon", "app.user.module.add.moduleUrl", "app.user.module.add.moduleSort"
            ],
            "viewBody" => [
                "id", "name", "modc", "pmodc", "moduleActionCode", "moduleComponent", "icon", "url", "sort"
            ],
        ];
        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function addModule()
    {
        $icon = getFormData($this->_data, "icon");
        $id = getFormData($this->_data, "id");
        $modc = getFormData($this->_data, "modc");
        $moduleActionCode = getFormData($this->_data, "moduleActionCode");
        $moduleComponent = getFormData($this->_data, "moduleComponent");
        $modulePos = getFormData($this->_data, "modulePos");
        $name = getFormData($this->_data, "name");
        $pmodc = getFormData($this->_data, "pmodc");
        $sort = getFormData($this->_data, "sort");
        $url = getFormData($this->_data, "url");
        $breadcrumb = getFormData($this->_data, "breadcrumb");

        $isValidated = $this->checkModuleValidation($id, $icon, $modc, $moduleActionCode, $moduleComponent, $modulePos, $name, $pmodc, $sort, $url, $breadcrumb);

        //inputs validated
        if ($isValidated) {
            $modulesTable = $this->_tables["MODULES_TABLE"];

            //check if module exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $modulesTable, "module_id", "(module_name = ? OR module_code = ?)", [$name, $modc]);

            //module not exist
            if ($iStatus === 0) {
                $cDT = currentDateTime();
                $cD = currentDate();
                $id = $id ? $id : null;

                $cols = "module_id, module_name, module_code, parent_module_code, module_component, module_actioncode, module_url_link, module_icon, module_position, module_sort, show_breadcrumb, creator_id, rcd, rdt";
                $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                $arrParams = [$id, $name, $modc, $pmodc, $moduleComponent, $moduleActionCode, $url, $icon, $modulePos, $sort, $breadcrumb, $this->_iUserId, $cD, $cDT];

                $iStatus = addRecord($this->_dbConn, $modulesTable, $cols, $vals, $arrParams);

                // user added
                if ($iStatus == 2) {
                    $arrMessage = responseMessage([$GLOBALS['MODULE_ADDED']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['MODULE_NOT_ADDED']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['MODULE_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function viewModules()
    {
        $modulesTable = $this->_tables["MODULES_TABLE"];
        $arrResult = [];

        // order by condition
        $sOrder_cond = getOrderByCond("rdt", $this->_data["sort"]);
        // filter by search query
        $where = getFilterResult($this->_data['searchbar'], ["name" => ["module_name", 1], "moduleCode" => ["module_code", 1], "moduleUrl" => ["module_url_link", 1]]);

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT module_id, module_name, module_code, parent_module_code, module_component, module_actioncode, module_url_link, module_icon, module_position, module_sort, show_breadcrumb FROM $modulesTable WHERE dstatus = 0 $where $sOrder_cond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $arrResult[] = [
                    "id" => $arrData['module_id'],
                    "name" => $arrData['module_name'],
                    "modc" => $arrData['module_code'],
                    "pmodc" => $arrData['parent_module_code'],
                    "moduleComponent" => $arrData['module_component'],
                    "moduleActionCode" => $arrData['module_actioncode'],
                    "modulePos" => $arrData['module_position'],
                    "icon" => $arrData['module_icon'],
                    "url" => $arrData['module_url_link'],
                    "sort" => $arrData['module_sort'],
                    "breadcrumb" => (int) $arrData['show_breadcrumb'],
                ];
            }
        }
        $arrResult[] = ["total" => $limit["total"]];

        $arrMessage = responseMessage([], 1, ["data0" => $arrResult], true);
        echo json_encode($arrMessage);
    }

    final public function editModule()
    {
        $moduleId = getFormData($this->_data, "id");
        $name = getFormData($this->_data, "name");
        $modc = getFormData($this->_data, "modc");
        $pmodc = getFormData($this->_data, "pmodc");
        $moduleComponent = getFormData($this->_data, "moduleComponent");
        $moduleActionCode = getFormData($this->_data, "moduleActionCode");
        $modulePos = getFormData($this->_data, "modulePos");
        $icon = getFormData($this->_data, "icon");
        $url = getFormData($this->_data, "url");
        $sort = getFormData($this->_data, "sort");
        $breadcrumb = getFormData($this->_data, "breadcrumb");

        $isValidated = $this->checkModuleValidation($moduleId, $icon, $modc, $moduleActionCode, $moduleComponent, $modulePos, $name, $pmodc, $sort, $url, $breadcrumb);

        //inputs validated
        if ($isValidated) {
            $modulesTable = $this->_tables["MODULES_TABLE"];

            //check if module exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $modulesTable, "module_id", "module_id != ? AND (module_name = ? OR module_code = ?)", [$moduleId, $name, $modc]);

            //module not exist
            if ($iStatus === 0) {
                $cols = "module_name = ?, module_code = ?, parent_module_code = ?, module_component = ?, module_actioncode = ?, module_url_link = ?, module_icon = ?, module_position = ?, module_sort = ?, show_breadcrumb = ?, modif_id = ?";
                $arrParams = [$name, $modc, $pmodc, $moduleComponent, $moduleActionCode, $url, $icon, $modulePos, $sort, $breadcrumb, $this->_iUserId, $moduleId];

                $iStatus = updateRecord($this->_dbConn, $modulesTable, $cols, "dstatus = 0 AND module_id = ?", $arrParams);

                // module modified
                if ($iStatus == 1) {
                    $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['MODULE_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
    // Module Functions End
}
