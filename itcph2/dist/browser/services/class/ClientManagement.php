<?php

require_once $include_path . "defined_index.php";

// phpcs:ignore
class ClientManagement
{
    private $_dbConn = null;
    private $_data = null;
    private $_files = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];
    private $_tables = [];
    private $_valErrors = [];
    private $_validationLength = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $files = "", $iUserId = null)
    {
        $this->_data = $data;
        $this->_dbConn = $dbConn;
        $this->_files = $files;
        $this->_iUserId = $iUserId;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_validationLength = $GLOBALS['VALIDATOR_LENGTH'];
    }

    private function checkClientValidation($clientname, $desc)
    {
        $obj = new Validation();
        $obj->addValidation('name', $clientname, 'alnum_s', 1, 1, $this->_validationLength['CLIENT_NAME_MAXLENGTH'], 'Client Name');
        $obj->addValidation('desc', $desc, 'alnum_s', 0, 1, $this->_validationLength['DESC_MAXLENGTH'], 'Client Description');

        $isValidated = $obj->validateForm();
        $this->_valErrors = $obj->getErrors();

        return $isValidated;
    }

    final public function addClient()
    {
        $clientname = getFormData($this->_data['name']);
        $desc = getFormData($this->_data['desc']);

        $isValidated = $this->checkClientValidation($clientname, $desc);

        //inputs validated
        if ($isValidated) {
            //check if client exists
            $clientsTable = $this->_tables["CLIENTS_TABLE"];
            // Don't use dstatus = 0
            $status = isRecordExist($this->_dbConn, $clientsTable, "client_id", "client_name = ?", array($clientname));

            //client not exist so add
            if ($status === 0) {
                $imageUpload = 0; //image not uploaded
                $errors = 0; //no error message for upload image

                //Generate folder / image name
                $clientName = str_replace(" ", "_", ucwords(strtolower($clientname)));
                $unique_id = uniqueHashValue($this->_iUserId);
                $unique_id2 = uniqueSmallValue($clientName, $this->_iUserId);

                //Image selected
                if (!isEmptyString($this->_files) && isNonEmptyArray($this->_files)) {
                    $imageUpload = 1; //image uploading

                    $image_name = $clientName . "_" . $unique_id;

                    //copy with new name in New Path
                    $destination = $GLOBALS["CUST_FOLDER_PATH"] . "/" . constant("CUSTOMER_FOLDER") . "/" . constant("CLIENT_LOGO_FOLDER") . "/";

                    $result = uploadFile($this->_files, $destination, $image_name, true);
                    $errors = $result["errors"];
                }

                $cDT = currentDateTime();
                $cD = currentDate();
                $cols = "client_name, client_desc, creator_id, rcd, rdt";
                $vals = "?, ?, ?, ?, ?";
                $arrParams = array($clientname, $desc, $this->_iUserId, $cD, $cDT);

                //no errors
                if ($errors === 0) {
                    $cols .= ", image_name";
                    $vals .= ", ?";

                    //image selected
                    if ($imageUpload === 1) {
                        $arrParams[] = $result["filename"];
                    } else {
                        $arrParams[] = "";
                    }

                    // client folder name
                    $sClient_Folder_Name = $unique_id2;
                    $sClient_Folder = $GLOBALS["CUST_FOLDER_PATH"] . "/" . constant("CUSTOMER_FOLDER") . "/" . constant("CLIENTS_FOLDER") . "/" . $sClient_Folder_Name;

                    $cols .= ", client_dir_path";
                    $vals .= ", ?";
                    $arrParams[] = $sClient_Folder_Name;

                    $iStatus = addRecord($this->_dbConn, $clientsTable, $cols, $vals, $arrParams);

                    // client added
                    if ($iStatus == 2) {
                        // create client folder
                        mkdir($sClient_Folder, 0777, true);
                        mkdir($sClient_Folder . "/" . constant("CLIENTS_UPLOAD_MEDIA_FOLDER"));
                        mkdir($sClient_Folder . "/" . constant("CLIENTS_UPLOAD_IMAGE_FOLDER"));
                        mkdir($sClient_Folder . "/" . constant("CLIENTS_JSON_FOLDER"));
                        mkdir($sClient_Folder . "/" . constant("CLIENTS_RES_FOLDER"), 0777, true);
                        $arrMessage = responseMessage(array($GLOBALS['CLIENT_ADDED']), 1);
                    } else {
                        $arrMessage = responseMessage(array($GLOBALS['CLIENT_NOT_ADDED']));
                    }
                } else {
                    $arrMessage = responseMessage($result["messages"]);
                }
            } else {
                $arrMessage = responseMessage(array($GLOBALS['CLIENT_EXISTS']));
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }

    final public function getData()
    {
        $arrResult = array(
            "sortOptions" => array(
                array("label" => "Client Name", "value" => "client_name"),
                array("label" => "Description", "value" => "client_desc"),
            ),
            "viewHeader" => array("app.client.view.id", "app.client.add.name", "app.client.add.desc"),
            "viewBody" => array("id", "name", "desc"),
        );
        $arrMessage = responseMessage(array(), 1, $arrResult, true);
        echo json_encode($arrMessage);
    }

    final public function viewClients()
    {
        $clientsTable = $this->_tables["CLIENTS_TABLE"];
        $arrResult = array();

        // order by condition
        $sOrderCond = getOrderByCond("rdt", $this->_data["sort"]);
        // filter by search query
        $where = getFilterResult($this->_data['searchbar'], array("name" => array("client_name", 1)));

        $clientList = $this->_arrAccessInfo["user_clients"];
        if ($clientList) {
            $where .= " AND client_id IN $clientList";
        }

        $sAction = null;
        $iRows = 0;
        $sQuery = "SELECT client_id, client_name, client_dir_path, client_desc, image_name FROM $clientsTable WHERE dstatus = 0 $where $sOrderCond";
        $limit = getPaginationLimit($this->_dbConn, $this->_data, $sQuery);
        $sQuery .= " " . $limit["limit"];

        $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

        if ($iRows > 0) {
            $logo_path = $GLOBALS["UPLOAD_URL"] . "/" . constant("CUSTOMER_FOLDER") . "/" . constant('CLIENT_LOGO_FOLDER') . "/";

            while ($arrData = $this->_dbConn->GetData($sAction)) {
                $arrResult[] = array(
                    "name" => $arrData['client_name'],
                    "images" => array(
                        formatListingImage($logo_path, $arrData['image_name'], true),
                    ),
                    "desc" => $arrData['client_desc'],
                    "id" => $arrData['client_id'],
                );
            }
        }
        $arrResult[] = array("total" => $limit["total"]);

        $arrMessage = responseMessage(array(), 1, array("data0" => $arrResult), true);
        echo json_encode($arrMessage);
    }

    final public function editClient()
    {
        $clientId = getFormData($this->_data, "id");
        $clientName = getFormData($this->_data, "name");
        $clientDesc = getFormData($this->_data, "desc");

        $isValidated = $this->checkClientValidation($clientName, $clientDesc);

        //inputs validated
        if ($isValidated) {
            $clientsTable = $this->_tables["CLIENTS_TABLE"];

            //check if client exists
            // Don't use dstatus = 0
            $iStatus = isRecordExist($this->_dbConn, $clientsTable, "client_id", "client_id != ? AND client_name = ?", array($clientId, $clientName));

            // Client not exist
            if ($iStatus === 0) {
                $cols = "client_name = ?, client_desc = ?, modif_id = ?";
                $arrParams = array($clientName, $clientDesc, $this->_iUserId, $clientId);

                $iStatus = updateRecord($this->_dbConn, $clientsTable, $cols, "dstatus = 0 AND client_id = ?", $arrParams);

                // client modified
                if ($iStatus == 1) {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_EDITED_SUCCESSFULL']), 1);
                } else {
                    $arrMessage = responseMessage(array($GLOBALS['DATA_NOT_EDITED']));
                }
            } else {
                $arrMessage = responseMessage(array($GLOBALS['CLIENT_EXISTS']));
            }
        } else {
            $arrMessage = responseMessage($this->_valErrors);
        }

        echo json_encode($arrMessage);
    }
}
