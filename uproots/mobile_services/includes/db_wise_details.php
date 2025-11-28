<?php

$currentDate = $commonFunctions->currentDate();

// Used for Agreement/Declaration upload
$arrDBDeclarationDetails = array(
    "missCallTable" => "tblcloudring_declaration",
    "missCallMessage" => "Welcome to ITC family. Please share your code {OTP} with our promoter.-APPILARY",
    "smsProvider" => 6,
    "smsTemplateId" => "1707168909346357447",
    "smsSenderId" => "APILRY",
    "otpLength" => 4,
    "otpValidateConfig" => array(
        "allowBackDateDuplicateCall" => true,
        "allowDuplicateCallIfDifferentDates" => true
    ),
);

// Used for Login via OTP
$arrDBLoginViaOtpDetails = array(
    "missCallTable" => "tblcloudring_live_login",
    "missCallMessage" => "Dear User, Please use OTP {OTP} to login to your RADAR mobile app - APPILARY",
    "smsSenderId" => "APILRY",
    "smsProvider" => 6,
    "smsTemplateId" => "1707172905765154465",
    "regenerateOtpIfExistToday" => true,
    "preventDummyNumberToSendOTP" => true,
    "otpValidateConfig" => array(
        "allowDuplicateCallWithDifferentOtp" => true,
    ),
    "btlmo74_generic" => array(
        14 => array(
            22 => array(
                "missCallTable" => "tblcloudring_live_login",
                "otpLength" => 4,
                "otpMode" => $OTP_MODE["WHATSAPP_ONLY"],
                "regenerateOtpIfExistToday" => true,
                "otpValidateConfig" => array(
                    "allowDuplicateCallWithDifferentOtp" => true,
                ),
            ),
        ),
    ),
);

$arrDBProjectDetails = array(
    "itcawsportal_itcph2" => array(
        "path" => $SITE_PATH . "/itcph2" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                ),
                "missCallTable" => "tblcloudring_live",
                "missCallMessage" => "Dear Customer, OTP for Outlet addition in RADAR app is {OTP}. Please share it with your salesman. - APPILARY",
                "smsSenderId" => "APILRY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707172923172729093",
                "regenerateOtpIfExistToday" => true,
                "otpValidateConfig" => array(
                    "allowDuplicateCallWithDifferentOtp" => true,
                    "noMissCallFoundErrorMsgKey" => "OTP11",
                ),
            ),
        ),
    )
);
