<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class SystemOfflineManagement
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
        $arrResult = [
            "projectList" => getProjectOptions($this->_dbConn, "", 0, true, "dstatus = 0"),
        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function getTeam()
    {
        $project = $this->_data['project'];
        $arrResult = [
            "teamList" => getOptions($this->_dbConn, $GLOBALS['TABLES']['PROJECT_TEAM_TABLE'], "team_name", "team_id", " project_id = '$project' AND team_name is not null", []),

        ];

        $arrMessage = responseMessage([], 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function deleteTeam()
    {
        $teamCond = "";
        $team = getFormData($this->_data, "team");
        if (isset($team)) {
            $teamCond = "'" . implode("','", $team) . "'";
            $rsAction = null;
            $iRows = 0;
            $sql = "DELETE FROM tbloffline_dropdown_options WHERE team_id IN ($teamCond)";
            $this->_dbConn->ExecuteSelectQuery($sql, $rsAction, $iRows);

            if ($iRows > 0) {
                $arrMessage = responseMessage([$GLOBALS['TEAM_DELETED_SUCCESSFULLY']], 1);
            } else {
                $arrMessage = responseMessage([$GLOBALS['TEAM_NOT_FOUND_IN_OFFLINE']]);
            }
        }
        echo json_encode($arrMessage);
    }
}
