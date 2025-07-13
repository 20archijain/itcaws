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
    "itccam5_itc" => array(
        "path" => $SITE_PATH . "/itc" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                ),
                "missCallTable" => "tblcloudring_live_login",
                "missCallMessage" => "Dear Retailer {OTP} is your verification code for outlet addition in Radar app." .
                    " Please share it with the salesman. Reference id {MOBILE}\n-Appilary",
                "smsSenderId" => "APILRY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707169141301689512",
                "regenerateOtpIfExistToday" => true,
                "otpValidateConfig" => array(
                    "allowDuplicateCallWithDifferentOtp" => true,
                ),
            ),
        ),
    ),
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
            ),
        ),
    ),
    "itccam5_south" => array(
        "path" => $SITE_PATH . "/south" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                ),
            ),
        ),
    ),
    "itccam5_jaipur" => array(
        "path" => $SITE_PATH . "/jaipur" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                ),
                "missCallTable" => "tblcloudring_live",
                "missCallMessage" => "Dear Retailer {OTP} is your verification code for outlet addition in Radar app." .
                    " Please share it with the salesman. Reference id {MOBILE}\n-Appilary",
                "smsProvider" => 1,
                "regenerateOtpIfExistToday" => true,
            ),
        ),
    ),
    "itccam5_delhi" => array(
        "path" => $SITE_PATH . "/delhi" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "attendanceCond" => "AND DAYOFWEEK(capture_date) != 1", // Don't count sunday
                    "logoutCond" => "AND DAYOFWEEK(capture_date) != 1", // Don't count sunday
                    "attendanceShowNoDaysInAMonth" => true,
                    "attendanceExcludeWeekDay" => "Sunday",
                    "attendanceMtdLabel" => "MARKET_WORKING_DAYS",
                    "showOtherSummary" => true,
                ),
                "missCallTable" => "tblcloudring_live",
                "missCallMessage" => "Dear Retailer, please share code {OTP} with our representative to complete your outlet verification. - APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707174107422585138",
                "smsSenderId" => "APILRY",
                "otpLength" => 4
            ),
        ),
    ),
    "itccam5_snpl" => array(
        "path" => $SITE_PATH . "/snpl" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                ),
            ),
        ),
    ),
    "itccam5_itcnew" => array(
        "path" => $SITE_PATH . "/itcnew" . ($isLocalServer ? "/src" : "") . "/services/includes/project_process_info.php",
        "requireJsonIdForRespTable" => true,
        "1" => array(
            "1" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "7" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "12" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "13" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "124" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
            "149" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
        ),
        "2" => array(
            "2" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "10" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "22" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
        ),
        "3" => array(
            "3" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "8" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "14" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "21" => array(
                "missCallTable" => "tblcloudring_aashirvad_spices_d2d",
                "missCallMessage" => "Welcome to ITC family. Please share your code {OTP} with our promoter.-APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707168909346357447",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "118" => array(
                "missCallTable" => "tblcloudring_sunfeast_marielite_d2d",
                "missCallMessage" => "Welcome to ITC family. Please share your code {OTP} with our promoter.-APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707168909346357447",
                "smsSenderId" => "APILRY",
                "otpLength" => 4,
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true
                ),
            ),
            "123" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
            "140" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
            "147" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
            "157" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
        ),
        "4" => array(
            "4" => array(
                "missCallTable" => "tblcloudring_live_svasti_milk_in_shop_kol",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "9" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "16" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "17" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "18" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
        ),
        "5" => array(
            "5" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "99" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "141" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
        ),
        "6" => array(
            "6" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "31" => array(
                "missCallTable" => "tblcloudring_aashirvad_durgbhilai",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.",
                "smsProvider" => 4,
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "90" => array(
                "missCallTable" => "tblcloudring_aashirvad_spices_d2d",
                "missCallMessage" => "Welcome to ITC family. Please share your code {OTP} with our promoter.-APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707168909346357447",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
            "111" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "115" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "116" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "126" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "127" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "showDayendSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "showLeaveWeekOffSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "129" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "showDayendSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "showLeaveWeekOffSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "139" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "showDayendSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "142" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
        ),
        "7" => array(
            "11" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "20" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "59" => array(
                "missCallTable" => "tblcloudring_aashirvad_durgbhilai",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.- APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1207161579175449261",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
            "66" => array(
                "missCallTable" => "tblcloudring_yipeed2d_NW",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.- APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1207161579175449261",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
            "67" => array(
                "missCallTable" => "tblcloudring_yipeed2d_S",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.- APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1207161579175449261",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
            "125" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
        ),
        "8" => array(
            "15" => array(
                "imgTable" => "tblimages_manoranjan_ka_pitara",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "28" => array(
                "imgTable" => "tblimages_manoranjan_ka_pitara_phase2",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "38" => array(
                "imgTable" => "tblimages_manoranjan_ka_pitara_phase3",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "71" => array(
                "imgTable" => "tblimages_manoranjan_ka_pitara_phase4",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
        ),
        "9" => array(
            "104" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
        ),
        "10" => array(
            "26" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "35" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "51" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "52" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "61" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "64" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "68" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "87" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "133" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "137" => array(
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
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "144" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "145" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
            "152" => array(
                "imgTable" => "tblimages_scp_yippee_pb_sep2021",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                ),
            ),
        ),
        "13" => array(
            "118" => array(
                "missCallTable" => "tblcloudring_sunfeast_marielite_d2d",
                "missCallMessage" => "Thank you for buying Sunfeast Marie." .
                    " Please share the code {OTP} with our promoter. - APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707171013585102182",
                "smsSenderId" => "APILRY",
                "otpLength" => 6,
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true
                ),
            ),
            "121" => array(
                "missCallTable" => "tblcloudring_sunfeast_swd_2024",
                "missCallMessage" => "Thank you for buying Sunfeast Marie." .
                    " Please share the code {OTP} with our promoter. - APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707171013585102182",
                "smsSenderId" => "APILRY",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    // "showOtherSummary" => true
                ),
            ),
            "128" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "showDayendSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "showLeaveWeekOffSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020",
                ),
            ),
            "148" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "isSeparateAttendanceTable" => true,
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_gnb_dec2020"
                ),
            ),
        ),
    ),
    "itccam5_wonder" => array(
        "0" => array(
            "0" => array(
                "summary" => array(
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_patanjali2",
                ),
            ),
        ),
    ),
    "itccam5_impact" => array(
        "2" => array(
            "32" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_nestle_restaurant",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
                "dropdownCond" => "AND Done = 0 AND activity_date = '$currentDate'",
                "missCallTable" => "tblcloudring_live_nestle_rest",
                // "missCallMessage" => "Thank you for Participation. Your S Code is {OTP} \n- Appilary",
                "missCallMessage" => "Thank you for showing interest in Nestle products. Please share Passcode {OTP}" .
                    " with our representative to confirm your interest in future correspondence from Nestle." .
                    " With this, you permit us to send a SMS/Call/visit, despite being registered with DND/NCPR." .
                    " You are aware that you can reach out to Nestle in case you wish to opt out.\n-Appilary",
                "smsProvider" => 1,
            ),
        ),
        "4" => array(
            "5" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_bike_activity_jul2020",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
            "6" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_van_activity_sep2020",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "5" => array(
            "7" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_perfetti_retail_seeding",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "7" => array(
            "10" => array(
                "imgTable" => "tblsurvey_response_file_new_jk_cement_campaign",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_jk_cement_campaign",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "8" => array(
            "11" => array(
                "imgTable" => "tblsurvey_response_file_new_indi_chai",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_indi_chai",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
            "12" => array(
                "imgTable" => "tblsurvey_response_file_new_tata_tea_wd",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_tata_tea_wd",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
            "13" => array(
                "imgTable" => "tblsurvey_response_file_new_tata_tea_wd",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_tata_tea_wd",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "9" => array(
            "15" => array(
                "imgTable" => "tblsurvey_response_file_new_vc_cooler_installation",
                "dropdownCond" => "AND Done = 0",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_vc_cooler_installation",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
            "25" => array(
                "dropdownCond" => "AND Done = 0",
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_vc_cooler_phase_2",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "10" => array(
            "18" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_demo",
                "missCallMessage" => "Dear Retailer {OTP} is your verification code for outlet addition in Radar app." .
                    " Please share it with the salesman. Reference id {MOBILE}\n-Appilary",
                "smsSenderId" => "APILRY",
                "smsProvider" => 5,
            ),
            "88" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_demo",
                "missCallMessage" => "Dear Retailer {OTP} is your verification code for outlet addition in Radar app." .
                    " Please share it with the salesman. Reference id {MOBILE}\n-Appilary",
                "smsSenderId" => "APILRY",
                "smsProvider" => 5,
            ),
        ),
        "11" => array(
            "21" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_britania_wall",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
            "74" => array(
                "summary" => array(
                    "showAttendanceSummary" => false,
                    "attendanceTable" => "",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => false,
                    "respTable" => ""
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_BritanniaNutri",
                "missCallMessage" => "Thank you for participating in Britannia Marie Gold Campaign." .
                    " Your Britannia Marie Gold code is {OTP}. - APPILARY",
                "smsSenderId" => "APPLRY",
            ),
        ),
        "13" => array(
            "28" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_pidilite_d2d",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_pidilitewd40",
                "doExtraTaskPostValidOtp" => true,
                "otpValidateConfig" => array(
                    "skipMobileNoCheck" => true, // means mobile number is not coming in API so validate using OTP only
                    "noMissCallFoundErrorMsgKey" => "OTP11",
                    "canOTPExpire" => true,
                    "maxOtpValidTimeInSec" => 3 * 60 * 60, // 3hr
                ),
            ),
            "30" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_pidilite_phase2",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_pidilitewd40",
                "doExtraTaskPostValidOtp" => true,
                "otpValidateConfig" => array(
                    "skipMobileNoCheck" => true, // means mobile number is not coming in API so validate using OTP only
                    "noMissCallFoundErrorMsgKey" => "OTP11",
                    "canOTPExpire" => true,
                    "maxOtpValidTimeInSec" => 3 * 60 * 60, // 3hr
                ),
            ),
        ),
        "20" => array(
            "53" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_Maxo_Retail_activation",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "attendanceShowLoginTime" => true,
                    "attendanceShowLogoutTime" => true,
                    "logoutCond" => "AND ques_1 = 'DayEnd Report'",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_Maxo_Retail_activation"
                ),
            ),
        ),
        "25" => array(
            "63" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_attendance_survey",
                    "attendanceCond" => "AND ques_1 = 'Attendance'"
                ),
            ),
        ),
        "26" => array(
            "65" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_bournvita_d2d",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_bournvita_d2d"
                ),
                "dropdownCond" => "AND Done = 0",
            ),
            "80" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_bournvita_phase2",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_bournvita_phase2",
                ),
            ),
        ),
        "28" => array(
            "68" => array(
                "summary" => array(
                    "showAttendanceSummary" => false,
                    "attendanceTable" => "tblsurvey_response_details_malgudi_survey",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => false,
                    "respTable" => "tblsurvey_response_details_malgudi_survey"
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_continental_wetsample",
                "missCallMessage" => "Thank you for Participation. Your S Code is {OTP}- Appilary",
                "smsProvider" => 5,
                "smsSenderId" => "OTPtxt",
            ),
        ),
    ),
    "itccam5_novicemarcom" => array(
        "10" => array(
            "12" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_retail_seeding",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "11" => array(
            "14" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_gpay_activation",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "13" => array(
            "16" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_itc_sampling",
                "missCallMessage" => "Thank you for Participation. Your S Code is {OTP} \n- Appilary",
                "smsProvider" => 1,
            ),
        ),
        "14" => array(
            "17" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_drfixit",
                "missCallMessage" => "Kindly share this Demo Code No. {OTP} with DR. FIXIT representative at the" .
                    " Dealer.Retailer counter to register with Dr. Fixit, Pidilite Ind. Ltd.\n-Appilary",
                "smsSenderId" => "APPLRY",
                "doExtraTaskPostValidOtp" => true,
            ),
        ),
        "16" => array(
            "19" => array(
                "dropdownCond" => "AND Done = 0"
            ),
        ),
        "18" => array(
            "22" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_bournvita",
                "missCallMessage" => "Kindly share this Demo Code No. {OTP} with DR. FIXIT representative at the" .
                    " Dealer.Retailer counter to register with Dr. Fixit, Pidilite Ind. Ltd.\n-Appilary",
                "smsSenderId" => "APPLRY",
            ),
        ),
        "19" => array(
            "23" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_bournvita_d2d",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_bournvita_d2d"
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_bournvita",
                "missCallMessage" => "Your sales code is {OTP} for the purchase of pack of bornvita at" .
                    " Rs 25/- each.\n- APPILARY",
                "smsSenderId" => "CNFIRM",
            ),
        ),
        "21" => array(
            "26" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_exide_yodha",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_exide_yodha"
                ),
            ),
        ),
    ),
    "itccam5_zx" => array(
        "5" => array(
            "58" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "6" => array(
            "8" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_daburmadison_FEM",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showSalesSummary" => true,
                    "salesSummaryTable" => "tblsurvey_response_details_daburmadison_FEM",
                    "salesSummaryConfig" => array(
                        // configure each radio option i.e setting
                        array(
                            "salesSummaryTitle" => "Today's Summary",
                            "salesSummaryCond" => "AND ques_1 = 'Sales Details' AND ques_9 = 'Yes'",
                            // can have multiple sales questions
                            "salesSummaryQues" => array(
                                array(
                                    "quesNo" => "ques_10",
                                    "rows" => 8,
                                    "columns" => 1,
                                    "salesLabels" => array(
                                        "Fem Gold- 25gm",
                                        "Fem Gold- 50gm",
                                        "Fem Rose- 25gm",
                                        "Fem Rose- 50gm",
                                        "Fem Sandal- 25gm",
                                        "Fem Sandal- 50gm",
                                        "Fem Turmeric- 25gm",
                                        "Fem Turmeric- 50gm"
                                    ),
                                ),
                            ),
                        )
                    ),
                ),
            ),
        ),
        "7" => array(
            "9" => array(
                "imgTable" => "tblsurvey_response_file_new_mother_dairy_wall_painting",
            ),
        ),
        "8" => array(
            "10" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "9" => array(
            "27" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_itc_retailbranding",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.",
                "smsProvider" => 4,
            ),
            "29" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_rmdapp",
                "missCallMessage" => "ITC Parivar me aapka swagat hai. Apka unique code {OTP} hai.",
                "smsProvider" => 4,
            ),
        ),
        "11" => array(
            "18" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_Pidilite_V2",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showSalesSummary" => true,
                    "salesSummaryTable" => "tblsurvey_response_details_Pidilite_V2",
                    "salesSummaryConfig" => array(
                        // configure each radio option i.e setting
                        array(
                            "salesSummaryTitle" => "Today's First Visit Summary",
                            "salesSummaryCond" => "AND ques_1 = 'First Visit' AND ques_9 = 'Yes'",
                            // can have multiple sales questions
                            "salesSummaryQues" => array(
                                array(
                                    "quesNo" => "ques_10",
                                    "salesLabels" => array("Qty Sold")
                                ),
                            ),
                            // Count no of records today and send
                            "totalRecordsSummary" => array(
                                "count" => true,
                                "label" => "Outlets Covered"
                            ),
                        ),
                        array(
                            "salesSummaryTitle" => "Today's Second Visit Summary",
                            "salesSummaryCond" => "AND ques_1 = 'Second Visit' AND ques_3 = 'Yes'",
                            // can have multiple sales questions
                            "salesSummaryQues" => array(
                                array(
                                    "quesNo" => "ques_4",
                                    "salesLabels" => array("Qty Sold")
                                ),
                            ),
                            // Count no of records today and send
                            "totalRecordsSummary" => array(
                                "count" => true,
                                "label" => "Outlets Covered"
                            ),
                        ),
                        array(
                            "salesSummaryTitle" => "Today's Third Visit Summary",
                            "salesSummaryCond" => "AND ques_1 = 'Third Visit' AND ques_3 = 'Yes'",
                            // can have multiple sales questions
                            "salesSummaryQues" => array(
                                array(
                                    "quesNo" => "ques_4",
                                    "salesLabels" => array("Qty Sold")
                                ),
                            ),
                            // Count no of records today and send
                            "totalRecordsSummary" => array(
                                "count" => true,
                                "label" => "Outlets Covered"
                            ),
                        ),
                    ),
                ),
            ),
        ),
        "13" => array(
            "24" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "18" => array(
            "31" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_gift_delivery",
                "missCallMessage" => "Dear Retailer {OTP} is your verification code for outlet addition in Radar app." .
                    " Please share it with the salesman. Reference id {MOBILE}\n-Appilary",
                "smsSenderId" => "APILRY",
                "smsProvider" => 5,
            ),
        ),
        "19" => array(
            "32" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_Promotion_dabur",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                ),
            ),
        ),
        "24" => array(
            "37" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_itc_sampling",
                "missCallMessage" => "Thanks for showing interest in Sample. Please share" .
                    " your unique code {OTP} with our representative - APPILARY",
                "smsProvider" => 5,
                "smsSenderId" => "APILRY",
                "smsTemplateId" => "1707168913559106595",
                "otpLength" => 4,
            ),
            "59" => array(
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_dsaudit",
                "missCallMessage" => "Dear Retailer, {OTP} is your verification code for outlet survey conducted in" .
                    " RADAR app. Pl. share it with the researcher for completion of survey. - APPILARY",
                "smsSenderId" => "APPLRY",
                "preventDummyNumberToSendOTP" => true,
            ),
            "72" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "25" => array(
            "39" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_phase2__Snpl_Calls",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "otherSummaryCond" => "AND ques_1 IN ('First Call', 'Second Call')",
                    "respTable" => "tblsurvey_response_details_phase2__Snpl_Calls",
                ),
            ),
            "46" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_snpl_tm_calls_phase2",
                    "attendanceCond" => "AND ques_1 = 'Attendance'",
                    "showOtherSummary" => true,
                    "otherSummaryCond" => "AND ques_1 IN ('First Call', 'Second Call')",
                    "respTable" => "tblsurvey_response_details_snpl_tm_calls_phase2",
                ),
            ),
        ),
        "27" => array(
            "61" => array(
                "dropdownCond" => "AND Done = 0",
            ),
            "96" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "30" => array(
            "110" => array(
                "summary" => array(
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_merchantdisingph3",
                ),
                "missCallTable" => "tblcloudring_live_itc_merchandising_delhi",
                "missCallMessage" => "Welcome to ITC family. Please share your code {OTP} with our promoter.-APPILARY",
                "smsProvider" => 6,
                "smsTemplateId" => "1707168909346357447",
                "smsSenderId" => "APILRY",
            ),
        ),
        "36" => array(
            "67" => array(
                "summary" => array(
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_veetd2d_reporting"
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_veetd2d",
                "missCallMessage" => "Thank you for choosing Veet." .
                    " Please share your sample code {OTP} with our representative. - APPILARY",
                "smsSenderId" => "APPLRY",
                "otpEndTime" => "6:30PM",
            ),
        ),
        "38" => array(
            "74" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_private_hawker",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_private_hawker",
                ),
            ),
            "76" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_private_hawker",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_private_hawker",
                ),
            ),
            "77" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_private_hawker",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_private_hawker",
                ),
            ),
            "78" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_private_hawker",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_private_hawker",
                ),
            ),
            "79" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_private_hawker",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_private_hawker",
                ),
            ),
        ),
        "40" => array(
            "83" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "41" => array(
            "84" => array(
                "summary" => array(
                    "showAttendanceSummary" => true,
                    "attendanceTable" => "tblsurvey_response_details_nivea_h2h",
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_nivea_h2h",
                ),
                "dropdownCond" => "AND Done = 0",
                "missCallTable" => "tblcloudring_live_nivea_h2h",
                "missCallMessage" => "Kindly share this Demo Code No. {OTP} with Nivea representative" .
                    " to register with Nivea - APPILARY",
                "smsProvider" => 5,
                "smsSenderId" => "APPLRY",
                "otpEndTime" => "6:30PM",
            ),
        ),
        "42" => array(
            "86" => array(
                "dropdownCond" => "AND Done = 0",
            ),
        ),
        "45" => array(
            "93" => array(
                "dropdownCond" => "AND Done = 0",
            ),
            "100" => array(
                "summary" => array(
                    "showOtherSummary" => true,
                    "respTable" => "tblresponse_retail_merchandising_reccee",
                ),
            ),
            "103" => array(
                "summary" => array(
                    "showOtherSummary" => true,
                    "respTable" => "tblsurvey_response_details_interspace_ooh",
                ),
            ),
        ),
    ),
);
