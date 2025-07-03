<?php

// NOVICEMARCOM
// 9555606606 - Miss call no

// Use this value to validate
$I_am_req_always = "I @m req @lway$ tokeniZer";

// Used to include DBConnection
require_once "../../includes/index.php";
require_once $CLASSES_PATH . "/Otp.php";

$currentDate = $commonFunctions->currentDate();
$currentDateTime = $commonFunctions->currentDateTime();

// DB params
$dbName = $NOVICEMARCOM_DB;
$missCallTable = "tblcloudring_live_licious";
$missCallNumber = "9555606606";

// OTP settings
$smsProvider = 1;
$smsSenderId = "APPOTP";
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
$logFileName = "log_novicemarcom_licious_coupon";
$logFolderName = "/otp_via_misscall";
$sResponse = "";
$sExtraLogData = "DB: $dbName MissCallTable: $missCallTable" .
    " Mobile: $sMobile smsProvider: $smsProvider smsSenderId: $smsSenderId smsTemplateId: $smsTemplateId";

// Create OTP object
$otp = new Otp($dbConn, $tableUtil, $commonFunctions, $logFileName, $logFolderName);

if ($sMobile) {
    // Check if miss call found or not
    $isMissCallFound = $tableUtil->isRecordExist(
        "$dbName.$missCallTable",
        "rec_id",
        "dstatus = 0 AND rec_who = ?",
        array($sMobile)
    );

    $isCouponRequired = false;
    $isCouponFound = false;

    // Generate Coupon
    $couponCode = "";

    // Not found
    if ($isMissCallFound === 0) {
        // Get available coupon code
        $couponCode = $tableUtil->getRowColumn(
            "$dbName.tblcloudring_licious_Coupons",
            "couponcode",
            "dstatus = 0 AND is_sent = 0"
        );

        $isCouponRequired = true;
        $isCouponFound = $couponCode ? true : false;

        $message = "Welcome to Licious! Use code: $couponCode to get 100%* cashback up to Rs.500 on your" .
            " first order. Download the app https://bit.ly/3fWHaX2 *T&C apply";
    } else {
        $message = "Hello again! Seems you have already availed this offer. It's valid for a single use only." .
            " Interested in today's deals? Buy fresh meat now https://bit.ly/37wHAjn";
    }

    // If coupon is required, it should be available
    if (!$isCouponRequired || ($isCouponRequired && $isCouponFound)) {
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
            $sExtraLogData .= "\r\nCoupon Code: $couponCode Message: $message";

            // Update mobile and date for the availed coupon
            if ($isCouponRequired) {
                $tableUtil->updateRecord(
                    // $dbName,
                    "$dbName.tblcloudring_licious_Coupons",
                    "is_sent = 1, mobile = ?, rcd = '$currentDate', rdt = '$currentDateTime'",
                    "dstatus = 0 AND couponcode = ?",
                    array($sMobile, $couponCode)
                );
            }

            $sIN_Query_Org = "INSERT INTO $dbName.$missCallTable (process, token, rec_who, rec_circle, rec_op" .
                ", rec_misscallno, api_output, rcd, rdt) VALUES " .
                "('0', '$couponCode', '$sMobile', '$sCircle', '$sOperator', '$missCallNumber', '$sOutput', '$currentDate', '$currentDateTime')";
            $sIN_Query = "INSERT INTO $dbName.$missCallTable (process, token, rec_who, rec_circle, rec_op, rec_misscallno, api_output" .
                ", rcd, rdt) VALUES ('0', '$couponCode', ?, ?, ?, ?, ?, '$currentDate', '$currentDateTime')";
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
        // OTP not sent. Coupon not found
        $sResponse = $arrOTPMessages["OTP20"];
    }
} else {
    // Invalid data
    $sResponse = $arrOTPMessages["OTP00"];
}

// Log response
$otp->logOutput($sResponse, $sExtraLogData);
$dbConn->Close();
