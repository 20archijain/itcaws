<?php

// Impact
// 9015545044 - Miss call no

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/Otp.php";

$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();

// DB params
$dbName = $IMPACT_DB;
$missCallTable = "tblcloudring_live_cadbury";
$missCallNumber = "9015545044";

// OTP settings
$smsProvider = 4;
$smsSenderId = "APPLRY";
$smsTemplateId = "";

// Miss call params
// $_GET = {"caller":"9718296659","circle":"Delhi","operator":"ID","datetime":"2020-08-21 19:20:45","prefix":"91"}
$prefix = isset($_GET["prefix"]) ? $_GET["prefix"] : "";
$sMobile = isset($_GET["caller"]) ? $_GET["caller"] : "";
$sCircle = $_GET["circle"];
$sOperator = $_GET["operator"];
$sMisscallDatetime = $_GET["datetime"];
$missCallNumber = isset($_GET["misscallno"]) ? $_GET["misscallno"] : $missCallNumber;

// Log params
$logFileName = "log_Impact_CadburyD2D";
$logFolderName = "/otp_via_misscall";
$sResponse = "";
$sExtraLogData = "DB: $dbName MissCallTable: $missCallTable" .
    " Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";

// Create OTP object
$otp = new Otp($dbConn, $tableUtil, $commonFunctions, $logFileName, $logFolderName);

if ($sMobile) {
    // Generate OTP
    $getUniqueCode = $otp->generateUniqueNumericCode(4);

    $message = "Thank you for your interest in Cadbury Bournvita. Your code for purchase is $getUniqueCode\n- APPILARY";

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
            ", rec_misscallno, api_output, rcd, rdt) VALUES ('0', '$getUniqueCode', ?, ?, ?, ?, ?, '$currentDate', '$currentDateTime')";
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
    // Invalid data
    $sResponse = $arrOTPMessages["OTP00"];
}

// Log response
$otp->logOutput($sResponse, $sExtraLogData);
$dbConn->Close();
