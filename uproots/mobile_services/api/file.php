<?php

// Used to upload image as well as any other media file

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
// require_once $CLASSES_PATH . "/AwsRequest.php";

// phpcs:ignore
class UploadFile extends Utilities
{
    private $arrUserDetails;
    private $localLogFileName = "log_file";
    private $sExtraLogData;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        parent::__construct($dbConn, $tableUtil, $commonFunctions, $this->localLogFileName, "/upload");
    }

    private function validateData()
    {
        return $this->commonFunctions->isNonEmptyArray($this->requestPostData) &&
            $this->commonFunctions->isNonEmptyArray($this->requestFiles) &&
            isset($this->requestFiles["file"]["name"]) && $this->requestFiles["file"]["name"];
    }

    private function upload()
    {
        global $PRODS_ANY_PATH, $PRODS_ANY_FOLDER, $CLIENTS_FOLDER, $FILES_FOLDER, $IMG_FOLDER,
            $TBL_SURVEY_RES_FILE_NEW, $TBL_SURVEY_RES_NEW, $TBL_ROUTE_DETAILS, $ARR_WATERMARK_POSITION,
            $ARR_IMAGE_FORMATS, $IMPACT_DB, $ITCPH2_DB;

        $dbName = $this->arrUserDetails["db_name"];
        $clientId = $this->arrUserDetails["client_id"];
        $projectId = $this->arrUserDetails["project_id"];
        $teamId = $this->arrUserDetails["team_id"];
        $jsonId = $this->arrUserDetails["s_id"];
        $customerFolder = $this->arrUserDetails['client_res'];
        $clientFolder = $this->arrUserDetails['proj_res_folder'];
        $sDomain = $this->arrUserDetails['c_subdomain'];
        $currentDate = $this->commonFunctions->currentDate();
        $currentDateTime = $this->commonFunctions->currentDateTime();

        $jsonfiles = $this->requestFiles["file"];

        // surveyUniqId is same ID that was received in data.php (uniqueId)
        $iUnique_Id = $teamId . htmlentities($this->requestPostData["surveyUniqId"]);
        $sM_datetime = isset($this->requestPostData["dt"]) ? htmlentities(date("Y-m-d H:i:s", ceil($this->requestPostData["dt"] / 1000))) : $currentDateTime;
        $sM_date = explode(" ", $sM_datetime)[0];
        $sLT = isset($this->requestPostData["lt"]) ? htmlentities($this->requestPostData["lt"]) : 0;
        $sLG = isset($this->requestPostData["lg"]) ? htmlentities($this->requestPostData["lg"]) : 0;
        $sSID = isset($this->requestPostData["surveyId"]) ? htmlentities($this->requestPostData["surveyId"]) : 0;   // JSON ID
        $sPageId = isset($this->requestPostData["pageId"]) ? htmlentities($this->requestPostData["pageId"]) : 0;  // Form/Page ID
        $sQuesId = isset($this->requestPostData["quesId"]) ? htmlentities($this->requestPostData["quesId"]) : 0;  // Question ID
        // Image unique ID that was received in data.php (fileUniqueId) in above question ID
        $sImg_ID = isset($this->requestPostData["uniqId"]) ? htmlentities($this->requestPostData["uniqId"]) : (time() * 10);

        // update JSON ID
        if (!$sSID) {
            $sSID = $jsonId;
        }

        // Get file table
        $fileTable = isset($this->arrDBProjectDetails[$dbName][$clientId][$projectId]["imgTable"]) &&
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["imgTable"] ?
            $this->arrDBProjectDetails[$dbName][$clientId][$projectId]["imgTable"] :
            $TBL_SURVEY_RES_FILE_NEW;

        // Check if file is already uploaded or not, if not then only upload
        $isRecordExist = $this->tableUtil->isRecordExist(
            "$dbName.$fileTable",
            "resp_id",
            "dstatus = 0 AND uni_id = ? AND mob_img_id = ?",
            array($iUnique_Id, $sImg_ID)
        );

        // Not exist, so upload
        if ($isRecordExist === 0) {
            // get file extension
            $sFileType = "." . $this->commonFunctions->getExtension($jsonfiles["name"]);

            // Set file name
            $sFileName = $sImg_ID . "_" . time() . $sFileType;

            // Set upload folder as per media type
            if (in_array($sFileType, $ARR_IMAGE_FORMATS)) {
                $uploadFolderNameAsPerFileType = $IMG_FOLDER . "/" . $currentDate;
            } else {
                $uploadFolderNameAsPerFileType = $FILES_FOLDER . "/" . $currentDate;
            }

            // Set file path
            $sFilePath = "/" . $customerFolder . $CLIENTS_FOLDER . "/" . $clientFolder . $uploadFolderNameAsPerFileType;
            $sFileFullPath = $PRODS_ANY_PATH . $sFilePath;
            $sFileAnotherServerPath = substr($PRODS_ANY_FOLDER, 1) . $sFilePath;
            $sDbFilePath = $sFilePath . "/";

            // create upload folder if not created
            if (!file_exists($sFileFullPath)) {
                @mkdir($sFileFullPath, 0777, true);
            }

            // Full path with file name
            $sFullPathWithFileName = $sFileFullPath . "/" . $sFileName;

            // Thumbnail image
            $sFullPathWithThumbFileName = $sFileFullPath . "/thumb_" . $sFileName;
            $iThumbWidth = 80;
            $iThumbHeight = 80;

            // Copy file
            // To upload big file, Open php.ini and set "post_max_size=1G" and "upload_max_filesize=1G", 1G means 1GB, 40M means 40MB
            if (!move_uploaded_file($jsonfiles["tmp_name"], $sFullPathWithFileName)) {
                // Media not uploaded
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["FILE01"], "response" => $sImg_ID));
            } else {
                // Add watermark on image and create thumbnail of image
                if (in_array($sFileType, $ARR_IMAGE_FORMATS)) {
                    $sLat = $sLT;
                    $sLog = $sLG;

                    // Default watemark settings
                    $sWatermarkPosition = $ARR_WATERMARK_POSITION["TOP"];
                    $watermarkText = "";
                    $arrTextcolor = array();
                    $createAndfillRectangle = false;
                    $arrRectangleBgColor = array();
                    $createRedRectangularBorder = true;

                    // Add default watermark if not set above
                    if (!$watermarkText) {
                        $watermarkText = "Lt:$sLat, Lg:$sLog, Timestamp:$sM_datetime";
                    }


                    // Add watermark if $watermarkText is set
                    if ($watermarkText) {
                        // Add watermark
                        $this->commonFunctions->addWatermark(
                            $sFullPathWithFileName,
                            $watermarkText,
                            $sWatermarkPosition,
                            $arrTextcolor,
                            $createAndfillRectangle,
                            $arrRectangleBgColor,
                            $createRedRectangularBorder
                        );
                    }

                    // Create Thumbnail
                    $this->commonFunctions->createThumbnail(
                        $sFullPathWithFileName,
                        $sFullPathWithThumbFileName,
                        $iThumbWidth,
                        $iThumbHeight,
                        $sFileType
                    );
                }

                // Move uploaded image to digitalocean server
                $moveImagesToDigitalOcean = (isset(constant("MOVE_IMAGES_ON_DIGITALOCEAN")["ALL_DB"]) && constant("MOVE_IMAGES_ON_DIGITALOCEAN")["ALL_DB"]) ||
                    (isset(constant("MOVE_IMAGES_ON_DIGITALOCEAN")[$dbName]) && constant("MOVE_IMAGES_ON_DIGITALOCEAN")[$dbName]);
                if ($moveImagesToDigitalOcean) {
                    $bucket = new AwsRequest();
                    list($isImageMoved, $sImageNewDomain) = $bucket->uploadS3Bucket(
                        $dbName,
                        $sFileFullPath,
                        $sFileName,
                        $sFileAnotherServerPath
                    );
                    if ($isImageMoved) {
                        $sDomain = $sImageNewDomain;
                    }
                }

                $sInsert_Action = null;
                $sInsert_Result = 0;
                // Impact, Novicemarcom, Wonder, ZX
                if (
                    $dbName === $IMPACT_DB
                ) {
                    $Query_Insert_Org = "INSERT INTO $dbName.$fileTable (cid, pid, team_id, uni_id" .
                        ", mob_img_id, lic_auth_code, page_id, ques_id, s_id, capture_date, capture_datetime" .
                        ", upload_date, upload_datetime, p_lt, p_lg, lt, lg, file_domain, file_path, file_name" .
                        ", rcd, rdt) VALUES ('$clientId', '$projectId', '$teamId', '$iUnique_Id'" .
                        ", '$sImg_ID', '{$this->sToken}', '$sPageId', '$sQuesId', '$sSID', '$sM_date', '$sM_datetime'" .
                        ", '$currentDate', '$currentDateTime', '$sLT', '$sLG', '$sLT', '$sLG', '$sDomain'" .
                        ", '$sDbFilePath', '$sFileName', '$currentDate', '$currentDateTime')";

                    $Query_Insert = "INSERT INTO $dbName.$fileTable (cid, pid, team_id, uni_id" .
                        ", mob_img_id, lic_auth_code, page_id, ques_id, s_id, capture_date, capture_datetime" .
                        ", upload_date, upload_datetime, p_lt, p_lg, lt, lg, file_domain, file_path, file_name" .
                        ", rcd, rdt) VALUES ('$clientId', '$projectId', '$teamId', ?, ?, ?, ?, ?, ?" .
                        ", ?, ?, '$currentDate', '$currentDateTime', ?, ?, ?, ?, '$sDomain', ?, ?" .
                        ", '$currentDate', '$currentDateTime')";

                    $arrParams = array(
                        "iUnique_Id" => $iUnique_Id,
                        "sImg_ID" => $sImg_ID,
                        "sToken" => $this->sToken,
                        "sPageId" => $sPageId,
                        "sQuesId" => $sQuesId,
                        "sSID" => $sSID,
                        "sM_date" => $sM_date,
                        "sM_datetime" => $sM_datetime,
                        "psLT" => $sLT,
                        "psLG" => $sLG,
                        "sLT" => $sLT,
                        "sLG" => $sLG,
                        "sDbFilePath" => $sDbFilePath,
                        "sFileName" => $sFileName
                    );
                } else {
                    $Query_Insert_Org = "INSERT INTO $dbName.$fileTable (client_id, project_id, team_id" .
                        ", uni_id, mob_img_id, lic_auth_code, page_id, ques_id, s_id, capture_date" .
                        ", capture_datetime, lt, lg, file_domain, file_path, file_name, rcd, rdt)" .
                        " VALUES ('$clientId', '$projectId', '$teamId', '$iUnique_Id', '$sImg_ID'" .
                        ", '{$this->sToken}', '$sPageId', '$sQuesId', '$sSID', '$sM_date', '$sM_datetime', '$sLT'" .
                        ", '$sLG', '$sDomain', '$sDbFilePath', '$sFileName', '$currentDate'" .
                        ", '$currentDateTime')";

                    $Query_Insert = "INSERT INTO $dbName.$fileTable (client_id, project_id, team_id" .
                        ", uni_id, mob_img_id, lic_auth_code, page_id, ques_id, s_id, capture_date" .
                        ", capture_datetime, lt, lg, file_domain, file_path, file_name, rcd, rdt)" .
                        " VALUES ('$clientId', '$projectId', '$teamId', ?, ?, ?, ?, ?, ?, ?, ?, ?" .
                        ", ?, '$sDomain', ?, ?, '$currentDate', '$currentDateTime')";

                    $arrParams = array(
                        "iUnique_Id" => $iUnique_Id,
                        "sImg_ID" => $sImg_ID,
                        "sToken" => $this->sToken,
                        "sPageId" => $sPageId,
                        "sQuesId" => $sQuesId,
                        "sSID" => $sSID,
                        "sM_date" => $sM_date,
                        "sM_datetime" => $sM_datetime,
                        "sLT" => $sLT,
                        "sLG" => $sLG,
                        "sDbFilePath" => $sDbFilePath,
                        "sFileName" => $sFileName
                    );
                }

                $this->dbConn->ExecuteQuery($Query_Insert, $sInsert_Action, $sInsert_Result, $arrParams);

                if ($sInsert_Result > 0) {
                    // Media uploaded successfully
                    $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["FILE02"], "response" => $sImg_ID), 1);
                } else {
                    // Media upload failed
                    $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["FILE03"], "response" => $sImg_ID));
                }

                $this->sExtraLogData .= "\r\nQuery: " . $Query_Insert_Org;
            }
        } else {
            // Already exists, so send success response

            // Media uploaded successfully
            $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["FILE02"], "response" => $sImg_ID), 1);
        }
        $this->logOutput($response, $this->sExtraLogData);
    }

    final public function uploadFile()
    {
        // Get requesting user details
        $this->arrUserDetails = $this->getLoginUserDetails($this->sToken);

        if ($this->commonFunctions->isNonEmptyArray($this->arrUserDetails)) {
            $dbName = $this->arrUserDetails["db_name"];
            $teamId = $this->arrUserDetails["team_id"];

            // Set logfile name as per DB as single log file may be huge
            $this->setLogFileName($this->localLogFileName . "_$dbName");

            $this->sExtraLogData = "DB: $dbName Client ID: {$this->arrUserDetails["client_id"]} Project ID: {$this->arrUserDetails["project_id"]} Team ID: $teamId";

            if ($this->validateData()) {
                $this->upload();
            } else {
                // Data cannot be empty
                $response = $this->response->sendResponse(array("message" => $this->arrAuthMessages["DATA01"]));
                $this->logOutput($response, $this->sExtraLogData);
            }
        }
    }
}

$upload = new UploadFile($dbConn, $tableUtil, $commonFunctions);
$upload->uploadFile();
$dbConn->Close();
