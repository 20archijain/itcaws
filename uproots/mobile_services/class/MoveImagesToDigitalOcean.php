<?php

// phpcs:ignore
class MoveImagesToDigitalOcean
{
    private $dbConn;
    private $tableUtil;
    private $commonFunctions;
    private $respTable = null;
    private $imgMoveLimit = 40;
    private $imgMoved = 0;
    private $defaultImgTable = null;
    private $bucket = null;

    public function __construct($dbConn, $tableUtil, $commonFunctions)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->commonFunctions = $commonFunctions;
        $this->respTable = $GLOBALS["TBL_SURVEY_RES_NEW"];
        $this->defaultImgTable = $GLOBALS["TBL_SURVEY_RES_FILE_NEW"];
        $this->bucket = new AwsRequest($this->commonFunctions);
        $this->bucket->connectDigitalOceanSpaces();
    }

    private function getImageTable($dbName, $clientId, $projectId)
    {
        $arrDbWiseConfig = $GLOBALS["arrDBProjectDetails"];

        if (
            $arrDbWiseConfig && isset($arrDbWiseConfig[$dbName][$clientId][$projectId]["imgTable"]) &&
            $arrDbWiseConfig[$dbName][$clientId][$projectId]["imgTable"]
        ) {
            return $arrDbWiseConfig[$dbName][$clientId][$projectId]["imgTable"];
        }

        return $this->defaultImgTable;
    }

    private function moveProjectImages($dbName, $isNewSetup, $imgTable, $projectId, $sDateCondition)
    {
        $projectCond = "AND pid = $projectId";
        if ($isNewSetup) {
            $projectCond = "AND project_id = $projectId";
        }

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT resp_id, file_domain, file_path, file_name FROM $dbName.$imgTable" .
            " WHERE dstatus = 0 $projectCond AND move_image_to_digitalocean = 0 $sDateCondition" .
            " LIMIT {$this->imgMoveLimit}";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($rowImage = $this->dbConn->GetData($rsAction)) {
                $img_id = $rowImage["resp_id"];
                $img_domain = $rowImage["file_domain"];
                $img_path = $rowImage["file_path"];
                $img_name = $rowImage["file_name"];

                $sImage_Folder_Path = $GLOBALS["PRODS_ANY_PATH"] . $img_path;
                $sImagePath = $sImage_Folder_Path . $img_name;

                // If domain is already digital ocean
                if (strpos($img_domain, "digitaloceanspaces") !== false) {
                    $this->tableUtil->updateRecord(
                        "$dbName.$imgTable",
                        "move_image_to_digitalocean = 1, modif_id = 1",
                        "resp_id = $img_id"
                    );
                } elseif (file_exists($sImagePath)) {
                    // If file exists
                    list($isImageMoved, $sImageNewDomain) = $this->bucket->uploadS3Bucket(
                        "DigitalOcean",
                        $dbName,
                        substr($sImage_Folder_Path, 0, -1), // remove / from end
                        $img_name,
                        substr($GLOBALS["PRODS_ANY_FOLDER"] . $img_path, 1, -1) // remove / from start and end
                    );

                    if ($isImageMoved) {
                        $this->tableUtil->updateRecord(
                            "$dbName.$imgTable",
                            "file_domain = ?, move_image_to_digitalocean = 1, modif_id = 1",
                            "resp_id = $img_id",
                            array($sImageNewDomain)
                        );
                    }
                } else {
                    // File not exists
                    $this->tableUtil->updateRecord(
                        "$dbName.$imgTable",
                        "move_image_to_digitalocean = 2, modif_id = 1",
                        "resp_id = $img_id"
                    );
                }

                $this->imgMoved++;
                if ($this->imgMoved == $this->imgMoveLimit) {
                    break;
                }
            }
        }
    }

    final public function moveImagesOfDB($dbName, $isNewSetup = false, $date = "", $condition = "")
    {
        $currentHr = date("G");
        // Run code between 8PM to 8AM IST
        if ($currentHr >= 20 || $currentHr < 8) {
            $this->imgMoved = 0;
            $sDate = $date ? $date : $this->commonFunctions->currentDate();
            $sPreviousDayDate = date("Y-m-d", strtotime("$sDate -1 day"));
            $sDateCondition = "AND capture_date BETWEEN '$sPreviousDayDate' AND '$sDate'";

            $columns = "cid, pid";
            if ($isNewSetup) {
                $columns = "client_id, project_id";
            }

            // Get distinct projects today
            $rsAction = null;
            $iActionRows = 0;
            $sQuery = "SELECT DISTINCT $columns FROM $dbName.{$this->respTable}" .
                " WHERE dstatus = 0 $sDateCondition $condition";
            $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

            if ($iActionRows > 0) {
                while ($row = $this->dbConn->GetData($rsAction)) {
                    $clientId = $isNewSetup ? $row["client_id"] : $row["cid"];
                    $projectId = $isNewSetup ? $row["project_id"] : $row["pid"];

                    // Get Image table for this project
                    $imgTable = $this->getImageTable($dbName, $clientId, $projectId);

                    $this->moveProjectImages($dbName, $isNewSetup, $imgTable, $projectId, $sDateCondition);

                    if ($this->imgMoved == $this->imgMoveLimit) {
                        break;
                    }
                }
            }
        }
    }
}
