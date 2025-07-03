<?php

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

require_once "../includes/index.php";

$arrImagesRequired = array(
    "Handwash" => array(
        "noOfImages" => 4,
        // 1=>0 means 1 is question id, and another 1 is multiplier
        "timeMultiplier" => array(1 => 0, 2 => 1, 3 => 2, 5 => 3),
    ),
    "Clean Water + Nutrition" => array(
        "noOfImages" => 5,
        "timeMultiplier" => array(1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4),
    ),
    "Sanitation" => array(
        "noOfImages" => 4,
        "timeMultiplier" => array(1 => 0, 2 => 1, 3 => 2, 4 => 3),
    ),
);

$currentDate = $commonFunctions->currentDate();
$projectId = 18;

$rsRes = null;
$iNoRows = 0;
$sQuery = "SELECT resp_id, cid, uni_id, sur_response, capture_datetime FROM $NOVICEMARCOM_DB.$TBL_SURVEY_RES_NEW" .
    " WHERE dstatus = 0 AND pid = $projectId AND capture_date = '2022-09-27'" .
    " AND rec_images_datetime_updated = 0 LIMIT 50";
$dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows);

if ($iNoRows > 0) {
    while ($row = $dbConn->GetData($rsRes)) {
        $respId = $row["resp_id"];
        $sClient_CID = $row["cid"];
        $sClient_PID = $projectId;
        $uniId = $row["uni_id"];
        $surResponse = $row["sur_response"];
        $captureDatetime = $row["capture_datetime"];

        $arrResponse = json_decode($surResponse, true);

        $firstFormIndex = array_search(1, array_column($arrResponse, 'pageId'));

        if ($firstFormIndex !== false) {
            $quesList = $arrResponse[$firstFormIndex]["quesList"];
            $secondQuesIndex = array_search(2, array_column($quesList, 'quesId'));

            if ($secondQuesIndex !== false) {
                $ans = $quesList[$secondQuesIndex]["singleAns"];

                // Get all images in sequence
                $iImgNoRows = 0;
                $rsImgRes = null;
                $sImgQuery = "SELECT resp_id, ques_id, p_lt, p_lg, file_path, file_name, img_datetime_updated" .
                    " FROM $NOVICEMARCOM_DB.$TBL_SURVEY_RES_FILE_NEW WHERE dstatus = 0 AND uni_id = ? ORDER BY ques_id";
                $dbConn->ExecuteSelectQuery($sImgQuery, $rsImgRes, $iImgNoRows, array($uniId));

                if ($iImgNoRows > 0) {
                    $sClient_Dbname = $NOVICEMARCOM_DB;
                    $iImagesUpdated = 0;
                    $firstImageGapInSec = rand(20, 30);    // take duration between 20sec and 30sec for first image
                    while ($rowImg = $dbConn->GetData($rsImgRes)) {
                        $imgRespId = $rowImg["resp_id"];
                        $quesId = $rowImg["ques_id"];
                        $sLat = $rowImg["p_lt"];
                        $sLog = $rowImg["p_lg"];
                        $filePath = $rowImg["file_path"];
                        $fileName = $rowImg["file_name"];
                        $isImgDatetimeUploaded = $rowImg["img_datetime_updated"];

                        // Image already processed
                        if ($isImgDatetimeUploaded == 1) {
                            $iImagesUpdated++;
                        } else {
                            // First image
                            if ($quesId == 1) {
                                $timeToAdd = $firstImageGapInSec;
                            } else {
                                if ($ans == "Clean Water + Nutrition") {
                                    // take duration between 165 sec and 180 sec for other than first image
                                    $otherImageGapInSec = rand(165, 180);
                                } else {
                                    // take duration between 235 sec and 250 sec for other than first image
                                    $otherImageGapInSec = rand(235, 250);
                                }
                                $timeToAdd = ($otherImageGapInSec *
                                    (isset($arrImagesRequired[$ans]["timeMultiplier"][$quesId]) ?
                                        $arrImagesRequired[$ans]["timeMultiplier"][$quesId] : 0)) +
                                    $firstImageGapInSec;
                            }

                            $imgDatetime = date("Y-m-d H:i:s", strtotime("$captureDatetime +$timeToAdd sec"));

                            $watermarkText = "Timestamp:$imgDatetime\r\nLt:$sLat, Lg:$sLog";
                            $iUnique_Id = $uniId;
                            $sWatermarkPosition = $ARR_WATERMARK_POSITION["TOP"];
                            $sTarget_SpecFolder = $PRODS_ANY_PATH . $filePath . $fileName;
                            $sThumb_Target_SpecFolder = $PRODS_ANY_PATH . $filePath . "thumb_" . $fileName;

                            if (file_exists($sTarget_SpecFolder)) {
                                // Update new timestamp on image and thumbnail
                                $commonFunctions->addWatermark(
                                    $sTarget_SpecFolder,
                                    $watermarkText,
                                    $sWatermarkPosition
                                );
                                $commonFunctions->addWatermark(
                                    $sThumb_Target_SpecFolder,
                                    $watermarkText,
                                    $sWatermarkPosition
                                );

                                // Update new timestamp in image table
                                $tableUtil->updateRecord(
                                    "$sClient_Dbname.$TBL_SURVEY_RES_FILE_NEW",
                                    "capture_datetime = ?, img_datetime_updated = 1",
                                    "resp_id = $imgRespId",
                                    array($imgDatetime)
                                );
                                $iImagesUpdated++;
                            }
                        }
                    }

                    $iRequiredImagesToUpdate = isset($arrImagesRequired[$ans]["noOfImages"]) ?
                        $arrImagesRequired[$ans]["noOfImages"] : 0;

                    // all images updated, update flag
                    if ($iImagesUpdated == $iRequiredImagesToUpdate) {
                        $tableUtil->updateRecord(
                            "$sClient_Dbname.$TBL_SURVEY_RES_NEW",
                            "rec_images_datetime_updated = 1",
                            "resp_id = $respId"
                        );
                    }
                }
            }
        }
    }
}
