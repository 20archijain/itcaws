<?php

// NOVICEMARCOM
// 9971277577 - Miss call no
die;

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/Otp.php";

$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();
$currentHr = date("H");
$currentMin = date("i");

// DB params
$dbName = $NOVICEMARCOM_DB;
$missCallTable = "tblcloudring_live_kumbh_mela";
$missCallNumber = "9971277577";

// OTP settings
$smsProvider = 4;
$smsSenderId = "OTPtxt";
$smsTemplateId = "";

// Miss call params
// $_GET = {"caller":"9718296659","circle":"Delhi","operator":"ID","datetime":"2020-08-21 19:20:45",
// "prefix":"91","misscallno":"9958709700"}
$prefix = isset($_GET["prefix"]) ? $_GET["prefix"] : "";
$sMobile = isset($_GET["caller"]) ? $_GET["caller"] : "";
$sCircle = $_GET["circle"];
$sOperator = $_GET["operator"];
$sMisscallDatetime = $_GET["datetime"];
$missCallNumber = isset($_GET["misscallno"]) ? $_GET["misscallno"] : $missCallNumber;

// Log params
$logFileName = "log_novicemarcom_kumb_mela";
$logFolderName = "/otp_via_misscall";
$sResponse = "";
$sExtraLogData = "DB: $dbName MissCallTable: $missCallTable" .
    " Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";

// Create OTP object
$otp = new Otp($dbConn, $tableUtil, $commonFunctions, $logFileName, $logFolderName);

if ($sMobile) {
    // send SMS from 1PM to 9:15PM
    if ($currentHr >= 13 && $currentHr <= 21 && ($currentHr != 21 || ($currentHr == 21 && $currentMin <= 15))) {
        // Check if miss call found or not
        $iMissCallsFound = $tableUtil->isRecordExist(
            "$dbName.$missCallTable",
            "rec_id",
            "dstatus = 0 AND rec_who = ? AND rcd = '$currentDate'",
            array($sMobile)
        );

        // send max 2 SMS
        if ($iMissCallsFound < 2) {
            // Generate OTP
            $getUniqueCode = $otp->generateUniqueNumericCode(6);

            $message = "Thank you for Participating at Chings Stall Kumbh Mela. Your sample Code is $getUniqueCode";

            // Send OTP
            list($isOtpSent, $sOutput) = $otp->sendOTP(
                $sMobile,
                $message,
                $smsProvider,
                $smsSenderId,
                $smsTemplateId
            );

            $sExtraLogData .= "\r\nMISS CALL API OUTPUT: $sOutput";

            // OTP sent
            if ($isOtpSent) {
                $sExtraLogData .= "\r\nOTP: $getUniqueCode Message: $message";

                $sIN_Query_Org = "INSERT INTO $dbName.$missCallTable (process, token, rec_who, rec_circle, rec_op" .
                    ", rec_misscallno, api_output, rcd, rdt) VALUES " .
                    "('0', '$getUniqueCode', '$sMobile', '$sCircle', '$sOperator', '$missCallNumber', '$sOutput', '$currentDate', '$currentDateTime')";
                $sIN_Query = "INSERT INTO $dbName.$missCallTable (process, token, rec_who, rec_circle, rec_op" .
                    ", rec_misscallno, api_output, rcd, rdt) VALUES ('0', '$getUniqueCode', ?, ?, ?, ?, ?" .
                    ", '$currentDate', '$currentDateTime')";
                $arrParams = array(
                    "sMobile" => $sMobile, "sCircle" => $sCircle, "sOperator" => $sOperator,
                    "missCallNumber" => $missCallNumber, "output" => $sOutput
                );
                $sExtraLogData .= "\r\nQUERY: $sIN_Query_Org";
                $dbConn->ExecuteQuery($sIN_Query, $iNum_Action, $iNum_rows, $arrParams);

                if ($iNum_rows > 0) {
                    // OTP sent
                    $sResponse = $arrOTPMessages["OTP08"];
                } else {
                    // OTP sent but failed to store in DB
                    $sResponse = $arrOTPMessages["OTP07"];
                }
            } else {
                // OTP not sent. Server down
                $sResponse = $arrOTPMessages["OTP05"];
            }
        } else {
            // OTP Limit reached
            $sResponse = $arrOTPMessages["OTP19"];
        }
    } else {
        // Time over. OTP not sent
        $sResponse = $arrOTPMessages["OTP15"];
    }
} else {
    // Invalid data
    $sResponse = $arrOTPMessages["OTP00"];
}

// Log response
$otp->logOutput($sResponse, $sExtraLogData);
$dbConn->Close();
