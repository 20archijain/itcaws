<?php

// Used to upload image as well as any other media file

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";
require_once $CLASSES_PATH . "/AppLogin.php";
require_once $CLASSES_PATH . "/AwsRequest.php";

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
            $ARR_IMAGE_FORMATS, $IMPACT_DB, $NOVICEMARCOM_DB, $WONDER_DB, $ZX_DB;

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

                    // Create watermark text
                    if ($dbName == $ZX_DB) {
                        if ($projectId == 34) {
                            $watermarkText = "Timestamp:$sM_datetime\r\nLt:$sLat, Lg:$sLog";
                        } elseif ($projectId == 82) {
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["LEFT"];
                            $sCurrentDateTime = $this->commonFunctions->currentDateTime("d/m/y h:i A", $sM_datetime);
                            $watermarkText = "Lat $sLat\r\nLong $sLog\r\n$sCurrentDateTime";
                        } elseif ($projectId == 96 || $projectId == 99 || $projectId == 102 || $projectId == 104 || $projectId == 106) {
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["LEFT"];
                            $watermarkText = "Lt:$sLat, Lg:$sLog, Timestamp:$sM_datetime";
                        } elseif ($projectId == 108 || $projectId == 109 || $projectId == 111) {
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["BOTTOM"];
                            $watermarkText = "Lt:$sLat, Lg:$sLog, Timestamp:$sM_datetime";
                        }
                    } elseif ($dbName == $WONDER_DB) {
                        if ($clientId != 27 || ($clientId == 27 && ($sPageId == 5 && ($sQuesId == 4 || $sQuesId == 5)))) {
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["BOTTOM"];
                            $sCurrentDateTime = $this->commonFunctions->currentDateTime("d/m/y h:i A", $sM_datetime);
                            $watermarkText = "Lat $sLat\r\nLong $sLog\r\n$sCurrentDateTime";
                        }
                    } elseif ($dbName == $NOVICEMARCOM_DB) {
                        if ($projectId == 27 || $projectId == 28) {
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["BOTTOM"];
                            $watermarkText = "Lt:$sLat, Lg:$sLog, Timestamp:$sM_datetime";
                        }
                    }

                    // Add default watermark if not set above
                    if (!$watermarkText) {
                        $watermarkText = "Lt:$sLat, Lg:$sLog, Timestamp:$sM_datetime";
                    }

                    // Add/Replace additional text in watermark
                    if ($dbName == $ZX_DB && $projectId == 34) {
                        $createAndfillRectangle = true;

                        // Get and add village name in watermark
                        $surResponse = $this->tableUtil->getRowColumn(
                            "$dbName.$TBL_SURVEY_RES_NEW",
                            "sur_response",
                            "dstatus = 0 AND uni_id = ?",
                            array($iUnique_Id)
                        );
                        if (isset($surResponse) && $surResponse) {
                            $arrSurResponse = json_decode($surResponse, true);

                            $pageId1Index = array_search(1, array_column($arrSurResponse, "pageId"));
                            if ($pageId1Index !== false) {
                                $arrQuesList = $arrSurResponse[$pageId1Index]["quesList"];
                                $ques2Index = array_search(2, array_column($arrQuesList, "quesId"));
                                $ques3Index = array_search(3, array_column($arrQuesList, "quesId"));

                                $villageAns = $otherVillageAns = "";
                                // Dropdown
                                if ($ques2Index !== false) {
                                    $villageAns = $arrQuesList[$ques2Index]["ansMultiChoice"];
                                }
                                // Textbox
                                if ($ques3Index !== false) {
                                    $otherVillageAns = $arrQuesList[$ques3Index]["singleAns"];
                                }

                                $villageName = "";
                                if (isset($otherVillageAns) && $otherVillageAns) {
                                    $villageName = $otherVillageAns;
                                } elseif (isset($villageAns) && $villageAns) {
                                    $recId = is_array($villageAns) ? (isset($villageAns[2]) ? $villageAns[2] : "") : json_decode($villageAns, true)[2];
                                    if ($recId && is_numeric($recId)) {
                                        $villageName = $this->tableUtil->getRowColumn(
                                            "$dbName.$TBL_ROUTE_DETAILS",
                                            "village",
                                            "dstatus = 0 AND rec_id = ?",
                                            array($recId)
                                        );
                                    }
                                }

                                // Add village Name in watermark
                                if ($villageName) {
                                    $watermarkText .= "\r\nVillage:$villageName";
                                }
                            }
                        }
                    } elseif ($dbName == $NOVICEMARCOM_DB && $projectId == 27) {
                        $arrTextcolor = array(255, 255, 255);   // white text color
                        $createAndfillRectangle = true;
                        $arrRectangleBgColor = array(0, 0, 0, 70);   // black bg color
                        $createRedRectangularBorder = false;

                        // Get and add state, city and village name in watermark
                        $surResponse = $this->tableUtil->getRowColumn(
                            "$dbName.$TBL_SURVEY_RES_NEW",
                            "sur_response",
                            "dstatus = 0 AND uni_id = ?",
                            array($iUnique_Id)
                        );

                        if (isset($surResponse) && $surResponse) {
                            $arrSurResponse = json_decode($surResponse, true);

                            $pageId1Index = array_search(1, array_column($arrSurResponse, "pageId"));
                            if ($pageId1Index !== false) {
                                $arrQuesList = $arrSurResponse[$pageId1Index]["quesList"];
                                $ques2Index = array_search(2, array_column($arrQuesList, "quesId"));
                                $ques3Index = array_search(3, array_column($arrQuesList, "quesId"));

                                $villageAns = $otherVillageAns = "";
                                // Dropdown
                                if ($ques2Index !== false) {
                                    $villageAns = $arrQuesList[$ques2Index]["ansMultiChoice"];
                                }
                                // Textbox
                                if ($ques3Index !== false) {
                                    $otherVillageAns = $arrQuesList[$ques3Index]["singleAns"];
                                }

                                $state = "";
                                $city = "";
                                if (isset($villageAns, $villageAns[0]) && $villageAns[0]) {
                                    $state = $villageAns[0];
                                    $city = $villageAns[1];
                                }
                                $villageName = isset($otherVillageAns) && $otherVillageAns ? $otherVillageAns : "";

                                if ($state && $city && $villageName) {
                                    $watermarkText = "State: $state City: $city\r\nLocation: $villageName\r\n" . $watermarkText;
                                }
                            }
                        }
                    } elseif (
                        $dbName == $WONDER_DB ||
                        ($dbName == $ZX_DB &&
                            ($projectId == 82 || $projectId == 96 || $projectId == 99 || $projectId == 102 ||
                                $projectId == 104 || $projectId == 106 || $projectId == 108 || $projectId == 109 ||
                                $projectId == 111)) ||
                        ($dbName == $NOVICEMARCOM_DB && $projectId == 28)
                    ) {
                        $arrTextcolor = array(255, 255, 255);   // white text color
                        $createAndfillRectangle = true;
                        $arrRectangleBgColor = array(0, 0, 0, 70);   // black bg color
                        $createRedRectangularBorder = false;

                        $surResponse = null;
                        if ($dbName == $WONDER_DB) {
                            $surResponse = $this->tableUtil->getRowColumns(
                                "$dbName.tblsurvey_response_details_patanjali2",
                                "lt, lg",
                                "dstatus = 0 AND uni_id = ?",
                                array($iUnique_Id)
                            );
                        }

                        // Get village name from google maps based on lt and lg
                        $surResponse = $surResponse ? $surResponse : $this->tableUtil->getRowColumns(
                            "$dbName.$TBL_SURVEY_RES_NEW",
                            "lt, lg",
                            "dstatus = 0 AND uni_id = ?",
                            array($iUnique_Id)
                        );

                        // Take image coordinates if record not found
                        if ($sLat && !$surResponse) {
                            $surResponse = array($sLat, $sLog);
                        }
                        if (isset($surResponse) && $surResponse) {
                            $lt = $surResponse[0];
                            $lg = $surResponse[1];
                            $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($lt) .
                                ',' . trim($lg) . '&key=AIzaSyCFHMKlZIoArRRYYiu6XiaqqVtz0qB5mCM';
                            $result = $this->commonFunctions->getApiResponseUsingCurl($url);

                            $result = $result ? json_decode($result, true) : array();
                            if ($result && $result['status'] == 'OK' && $result['results'][0]) {
                                // Get address from json data
                                $address = str_replace("'", "", $result['results']['0']['formatted_address']);
                                $locality = str_replace("'", "", $result['results']['0']['address_components']['0']['long_name']) .
                                    "," . str_replace("'", "", $result['results']['0']['address_components']['1']['long_name']) .
                                    "," . str_replace("'", "", $result['results']['0']['address_components']['2']['long_name']);

                                $watermarkText = $locality . "\r\n" . $address . "\r\n" . $watermarkText;
                            }
                        }
                    }

                    // Don't add any watermark
                    if (
                        $dbName == $WONDER_DB &&
                        ($clientId == 27 && ($sPageId == 5 && ($sQuesId == 2 || $sQuesId == 3)))
                    ) {
                        $watermarkText = "";
                    } elseif ($dbName == $ZX_DB && $projectId == 48) {
                        $watermarkText = "";
                    } elseif (
                        $dbName == $NOVICEMARCOM_DB &&
                        ($projectId == 14 || $projectId == 18)
                    ) {
                        $watermarkText = "";
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
                    $bucket = new AwsRequest($this->commonFunctions);
                    $bucket->connectDigitalOceanSpaces();
                    list($isImageMoved, $sImageNewDomain) = $bucket->uploadS3Bucket(
                        "DigitalOcean",
                        $dbName,
                        $sFileFullPath,
                        $sFileName,
                        $sFileAnotherServerPath
                    );
                    if ($isImageMoved) {
                        $sDomain = $sImageNewDomain;
                    }
                } elseif (constant("MOVE_IMAGES_ON_BACKBLAZE")) {
                    // Move uploaded image to backblaze server
                    $bucket = new AwsRequest($this->commonFunctions);
                    $bucket->connectBackblazeBuckets();
                    list($isImageMoved, $sImageNewDomain) = $bucket->uploadS3Bucket(
                        "Backblaze",
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
                    $dbName === $IMPACT_DB || $dbName === $NOVICEMARCOM_DB ||
                    $dbName === $WONDER_DB || $dbName === $ZX_DB
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
                        "iUnique_Id" => $iUnique_Id, "sImg_ID" => $sImg_ID, "sToken" => $this->sToken,
                        "sPageId" => $sPageId, "sQuesId" => $sQuesId, "sSID" => $sSID, "sM_date" => $sM_date,
                        "sM_datetime" => $sM_datetime, "psLT" => $sLT, "psLG" => $sLG, "sLT" => $sLT,
                        "sLG" => $sLG, "sDbFilePath" => $sDbFilePath,
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
                        "iUnique_Id" => $iUnique_Id, "sImg_ID" => $sImg_ID, "sToken" => $this->sToken,
                        "sPageId" => $sPageId, "sQuesId" => $sQuesId, "sSID" => $sSID, "sM_date" => $sM_date,
                        "sM_datetime" => $sM_datetime, "sLT" => $sLT, "sLG" => $sLG,
                        "sDbFilePath" => $sDbFilePath, "sFileName" => $sFileName
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
