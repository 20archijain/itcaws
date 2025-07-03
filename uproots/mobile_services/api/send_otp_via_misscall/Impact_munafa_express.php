<?php

// Impact
// 9971277577 - Miss call no

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/Otp.php";

$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();
$minMissCallDurationInSeconds = 6 * 60 * 60; // 6hr

// DB params
$dbName = $IMPACT_DB;
$missCallTable = "tblcloudring_live_munafa_express";
$missCallNumber = "9971277577";

// OTP settings
$smsProvider = 4;
$smsSenderId = "APPLRY";
$smsTemplateId = "";

$firstMisscallMessage = "आपका कोड है {OTP}. आपने गोपनीयता नीति को पढ़ और समझ लिया है." .
    " https://www.unilevernotices.com/privacy-notices/india-english.html
-APPILARY";
$SecondMisscallMessage = "प्रिय ग्राहक, आप पहले से ही हमारे यहाँ रेजिस्टर्ड हैं." .
    " कृपया 36 घंटे बाद दुबारा कोशिश करें - APPILARY";

// Miss call params
// $_GET = {"caller":"9718296659","circle":"Delhi","operator":"ID","datetime":"2020-08-21 19:20:45","prefix":"91"}
$prefix = isset($_GET["prefix"]) ? $_GET["prefix"] : "";
$sMobile = isset($_GET["caller"]) ? $_GET["caller"] : "";
$sCircle = $_GET["circle"];
$sOperator = $_GET["operator"];
$sMisscallDatetime = $_GET["datetime"];
$missCallNumber = isset($_GET["misscallno"]) ? $_GET["misscallno"] : $missCallNumber;

// Log params
$logFileName = "log_Impact_munafa_express";
$logFolderName = "/otp_via_misscall";
$sResponse = "";
$sExtraLogData = "DB: $dbName MissCallTable: $missCallTable" .
    " Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";

// Create OTP object
$otp = new Otp($dbConn, $tableUtil, $commonFunctions, $logFileName, $logFolderName);

if ($sMobile) {
    // Send 1st OTP if no miss call found, or difference between last miss call of type 1 and
    // this miss call should be atleast $minMissCallDurationInSeconds seconds, else send 2nd OTP
    $rsRes0 = null;
    $iNoRows0 = 0;
    $sQuery0 = "SELECT rdt FROM $dbName.$missCallTable WHERE call_type = 1 AND rec_who = ? AND dstatus = 0" .
        " ORDER BY rdt DESC LIMIT 1";
    $dbConn->ExecuteSelectQuery($sQuery0, $rsRes0, $iNoRows0, array($sMobile));

    // No miss call found or this miss is made after $minMissCallDurationInSeconds seconds from last miss call
    // of type 1, so send first OTP and insert record with first miss call
    $isFirstOTP = false;
    if ($iNoRows0 > 0) {
        $row0 = $dbConn->GetData($rsRes0);
        $lastMissCallDatetimeOfFirstCall = $row0["rdt"];

        // get time difference
        $noOfSecondsPast = $commonFunctions->getTimeDifference($lastMissCallDatetimeOfFirstCall, $currentDateTime, true);

        // If min time is reached, send 1st OTP
        if ($noOfSecondsPast >= $minMissCallDurationInSeconds) {
            $isFirstOTP = true;
        }
    } else {
        // No miss call
        $isFirstOTP = true;
    }

    // 1st OTP
    if ($isFirstOTP) {
        // Generate OTP
        $getUniqueCode = $otp->generateUniqueNumericCode(5);
        $message = str_replace("{OTP}", $getUniqueCode, $firstMisscallMessage);
        $callType = 1;
    } else {
        // 2nd OTP

        // Generate OTP
        $getUniqueCode = "";
        $message = $SecondMisscallMessage;
        $callType = 2;
    }

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
        $sExtraLogData .= "\r\nCall Type: $callType OTP: $getUniqueCode Message: $message";

        $sIN_Query_Org = "INSERT INTO $dbName.$missCallTable (call_type, process, token, rec_who, rec_circle, rec_op, rec_misscallno" .
            ", api_output, rcd, rdt) VALUES ('$callType', '0', '$getUniqueCode', '$sMobile', '$sCircle', '$sOperator', '$missCallNumber', '$sOutput', '$currentDate', '$currentDateTime')";
        $sIN_Query = "INSERT INTO $dbName.$missCallTable (call_type, process, token, rec_who, rec_circle, rec_op" .
            ", rec_misscallno, api_output, rcd, rdt) VALUES (?, '0', '$getUniqueCode', ?, ?, ?, ?, ?, '$currentDate'" .
            ", '$currentDateTime')";
        $arrParams = array(
            "callType" => $callType, "sMobile" => $sMobile, "sCircle" => $sCircle, "sOperator" => $sOperator,
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
    // Invalid data
    $sResponse = $arrOTPMessages["OTP00"];
}

// Log response
$otp->logOutput($sResponse, $sExtraLogData);
$dbConn->Close();
