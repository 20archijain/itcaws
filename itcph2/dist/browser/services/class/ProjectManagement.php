<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ProjectManagement
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

    private function checkProjectValidation(
        $isEdit,
        $projectName,
        $landingPage,
        $client = null
    ) {
        $obj = new Validation();
        $obj->addValidation('name', $projectName, 'alnum_s', 1, 1, $this->_validationLength['PROJECT_NAME_MAXLENGTH'], 'Project Name');
        $obj->addValidation('landing', $landingPage, 'pnz_num', 1, 1, null, 'Landing Page', $this->_validationLength['MINVALUE']);

        if (!$isEdit) {
            $obj->addValidation('client', $client, 'pnz_num', 1, 1, null, 'Client Name', $this->_validationLength['MINVALUE']);
        }

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function getAddProjectData()
    {
        $modulesTable = $this->_tables["MODULES_TABLE"];

        $arrResult = [
            "clientList" => getClients($this->_dbConn, 0, "dstatus = 0"),
            "landingPageList" => getOptions($this->_dbConn, $modulesTable, "module_name", "module_id", "dstatus = 0"),
        ];
        $arrMessage = responseMessage([], 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    final public function addProject()
    {
        $client = getFormData($this->_data, "client");
        $projectName = getFormData($this->_data, "projectName");
        $landingPage = getFormData($this->_data, "landingPage");

        $isValidated = $this->checkProjectValidation(
            false,
            $projectName,
            $landingPage,
            $client
        );

        //inputs validated
        if ($isValidated) {
            $projectsTable = $this->_tables["PROJECTS_TABLE"];
            $modulesTable = $this->_tables["MODULES_TABLE"];

            //check if project exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $projectsTable, "project_id", "client_id = ? AND project_name = ?", [$client, $projectName]);

            // Not exist
            if ($iStatus == 0) {
                $cDT = currentDateTime();
                $cD = currentDate();

                $arrLandingPage = getRowColumns($this->_dbConn, $modulesTable, "module_code, parent_module_code", "dstatus = 0 AND module_id = ?", [$landingPage]);

                $cols = "client_id, project_name, dsh_modc, dsh_pmodc, creator_id, rcd, rdt";
                $vals = "?, ?, ?, ?, ?, ?, ?";
                $arrParams = [$client, $projectName, $arrLandingPage[0], $arrLandingPage[1], $this->_iUserId, $cD, $cDT];

                // Add project
                $iStatus = addRecord($this->_dbConn, $projectsTable, $cols, $vals, $arrParams);

                // project added
                if ($iStatus == 2) {
                    $arrMessage = responseMessage([$GLOBALS['PROJECT_ADDED']], 1);
                } else {
                    $arrMessage = responseMessage([$GLOBALS['PROJECT_NOT_ADDED']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['PROJECT_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function getViewProjectData()
    {
        $modulesTable = $this->_tables["MODULES_TABLE"];

        $arrResult = [
            // Don't use dstatus = 0
            "clientList" => getClients($this->_dbConn, 1),
            // Don't use dstatus = 0
            "landingPageList" => getOptions($this->_dbConn, $modulesTable, "module_name", "module_id"),
            "sortOptions" => [
                ["label" => "Client Name", "value" => "b.client_name"],
                ["label" => "Project Name", "value" => "a.project_name"],
                ["label" => "Project Added - ASC", "value" => "a.rdt"],
                ["label" => "Project Added - DESC", "value" => "a.rdt DESC"],
            ],
            "viewHeader" => [
                "app.project.view.id", "app.project.add.name", "app.client.add.name", "app.user.user.add.landingPage"
            ],
            "viewBody" => [
                "id", "projectName", "clientName", "landingPage"
            ],
        ];
        $arrMessage = responseMessage([], 1, $arrResult, true);

        echo json_encode($arrMessage);
    }

    final public function viewProjects()
    {
        $clientsTable = $this->_tables["CLIENTS_TABLE"];
        $projectsTable = $this->_tables["PROJECTS_TABLE"];
        $modulesTable = $this->_tables["MODULES_TABLE"];
        $arrResult = [];

        $clientList = $this->_arrAccessInfo["user_clients"];
        $projectList = $this->_arrAccessInfo["user_projects"];

        // order by condition
        $sOrderCond = getOrderByCond("a.rdt", $this->_data["sort"]);
        // filter by search query
        $where = getFilterResult($this->_data['searchbar'], ["projectName" => ["a.project_name", 1], "client" => ["a.client_id", -1]]);

        // user has some specific permission
        if ($clientList) {
            $where .= " AND a.client_id IN $clientList";
        }
        if ($projectList) {
            $where .= " AND a.project_id IN $projectList";
        }

        // Don't use b.dstatus = 0
        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT a.client_id, a.project_id, a.project_name, a.dsh_modc, a.dsh_pmodc, b.client_name FROM $projectsTable AS a, $clientsTable AS b WHERE a.dstatus = 0 AND a.client_id = b.client_id $where $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $projectId = $arrData["project_id"];
                $modc = $arrData["dsh_modc"];
                $pmodc = $arrData["dsh_pmodc"];

                // Get landing page
                // Don't use dstatus = 0
                $arrLandingPage = getRowColumns($this->_dbConn, $modulesTable, "module_id, module_name", "module_code = '$modc' AND parent_module_code = '$pmodc'");
                $arrLandingPage = $arrLandingPage ? $arrLandingPage : ["", ""];

                $arrResult[] = [
                    "projectName" => $arrData['project_name'],
                    "clientId" => $arrData['client_id'],
                    "clientName" => $arrData['client_name'],
                    "id" => $projectId,
                    "landingPage" => $arrLandingPage[1],
                    "landingPageId" => $arrLandingPage[0],
                ];
            }
        }
        $arrResult[] = ["total" => $limit["total"]];

        $arrMessage = responseMessage([], 1, ["data0" => $arrResult], true);
        echo json_encode($arrMessage);
    }

    final public function editProject()
    {
        $clientId = getFormData($this->_data, "clientId");
        $projectName = getFormData($this->_data, "projectName");
        $projectId = getFormData($this->_data, "id");
        $landingPage = getFormData($this->_data, "landingPageId");

        $isValidated = $this->checkProjectValidation(true, $projectName, $landingPage);

        //inputs validated
        if ($isValidated) {
            $projectsTable = $this->_tables["PROJECTS_TABLE"];
            $modulesTable = $this->_tables["MODULES_TABLE"];

            //check if project exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $projectsTable, "project_id", "project_id != ? AND client_id = ? AND project_name = ?", [$projectId, $clientId, $projectName]);

            // Project not exist, edit
            if ($iStatus === 0) {
                $arrLandingPage = getRowColumns($this->_dbConn, $modulesTable, "module_code, parent_module_code", "dstatus = 0 AND module_id = ?", [$landingPage]);

                if (isset($arrLandingPage, $arrLandingPage[0]) && $arrLandingPage[0]) {
                    $cols = "project_name = ?, dsh_modc = ?, dsh_pmodc = ?, modif_id = ?";
                    $arrParams = [$projectName, $arrLandingPage[0], $arrLandingPage[1], $this->_iUserId, $projectId];

                    $iStatus = updateRecord($this->_dbConn, $projectsTable, $cols, "dstatus = 0 AND project_id = ?", $arrParams);

                    // project modified
                    if ($iStatus === 1) {
                        $arrMessage = responseMessage([$GLOBALS['DATA_EDITED_SUCCESSFULL']], 1);
                    } else {
                        $arrMessage = responseMessage([$GLOBALS['DATA_NOT_EDITED']]);
                    }
                } else {
                    $arrMessage = responseMessage([$GLOBALS['LANDING_PAGE_NO_LONGER_EXISTS']]);
                }
            } else {
                $arrMessage = responseMessage([$GLOBALS['PROJECT_EXISTS']]);
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
