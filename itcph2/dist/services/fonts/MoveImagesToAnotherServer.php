<?php

// phpcs:ignore
class MoveImagesToAnotherServer
{
    private $dbConn;
    private $tableUtil;
    private $defaultImgTable = null;
    private $apiEndPoint = null;

    public function __construct($dbConn, $tableUtil)
    {
        $this->dbConn = $dbConn;
        $this->tableUtil = $tableUtil;
        $this->defaultImgTable = $GLOBALS["TBL_SURVEY_RES_FILE_NEW"];
    }

    private function moveImage($filePath, $imageSource, $imageThumbSource)
    {
        if (file_exists($imageSource)) {
            $ch = curl_init($this->apiEndPoint);
            $data = array(
                "path" => $filePath,
                "file" => new CURLFile($imageSource),
                "thumb" => file_exists($imageThumbSource) ? new CURLFile($imageThumbSource) : null
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);

            $arrOutput = $output ? json_decode($output, true) : null;
            if ($arrOutput && $arrOutput["status"] == 200) {
                if (file_exists($imageSource)) {
                    unlink($imageSource);
                }
                if (file_exists($imageThumbSource)) {
                    unlink($imageThumbSource);
                }
                return 1;
            }

            return 0;
        }
        return 2;
    }

    final public function moveImagesOfProject($dbName, $cond, $fileDomain, $apiEndPoint, $imgTable = null)
    {
        $this->apiEndPoint = $apiEndPoint;
        if (!$imgTable) {
            $imgTable = $this->defaultImgTable;
        }

        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT resp_id, file_path, file_name FROM $dbName.$imgTable" .
            " WHERE move_image_to_digitalocean = 0 $cond LIMIT 50";
        $this->dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows);

        if ($iActionRows > 0) {
            while ($row = $this->dbConn->GetData($rsAction)) {
                $respId = $row["resp_id"];
                $filePath = $row["file_path"];
                $fileName = $row["file_name"];

                $imageSource = $GLOBALS["PRODS_ANY_PATH"] . $filePath . $fileName;
                $imageThumbSource = $GLOBALS["PRODS_ANY_PATH"] . $filePath . "thumb_$fileName";
                $isMovedStatus = $this->moveImage($filePath, $imageSource, $imageThumbSource);

                // Update Status
                if ($isMovedStatus > 0) {
                    $this->tableUtil->updateRecord(
                        "$dbName.$imgTable",
                        $isMovedStatus == 1 ?
                            "move_image_to_digitalocean = $isMovedStatus," .
                            " file_domain = '$fileDomain'" :
                            "move_image_to_digitalocean = $isMovedStatus",
                        "resp_id = $respId"
                    );
                }
            }
        }
    }
}
