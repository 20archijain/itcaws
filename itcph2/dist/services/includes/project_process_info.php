<?php

$COMMON_PROCESS_SETTINGS = array(
    "ALLOWED_PID" => array(1),
    "PROCESS_TABLE" => "tblsurvey_response_new",
    "RESPONSE_TABLE" => "tblsurvey_response_details",
    "IMG_TABLE" => "tblsurvey_response_file_new",

    // ITC Client, Van DS Project
    "NO_OF_QUESTIONS" => array(3, 2, 2, 2, 5, 2, 2, 2, 1),
    "PROCESS_BASED_ON_SKIP_LOGIC" => array(
        1 => array(
            "QUES_ID" => 1,
            // needs to be in lowercase
            "attendance" => array(1, 8),
            "outlet survey" => array(1, 2),
            "outlet order" => array(1, 2),
            "add outlet" => array(1, 5),
            "day end selfie" => array(1, 9)
        ),
        2 => array(
            "QUES_ID" => 2,
            // needs to be in lowercase
            "yes" => array(2, 3),
            "no" => array(2, 4)
        ),
        5 => array(
            "QUES_ID" => 5,
            // needs to be in lowercase
            "yes" => array(5, 6),
            "no" => array(5, 7)
        ),
    ),
    "PROCESS_ATTENDANCE" => true,
    "ATTENDANCE_FORM" => array(1, 1),           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
    "ATTENDANCE_MOBIMGID_FORM" => array(8, 2),  // Attendance unique mob id (8 = Page Id, 2 = Ques ID)
    "ATTENDANCE_DATA" => array(
        array("label" => "route", "valueIndex" => array(1, 2)),
        array("label" => "beatAdherenceReason", "valueIndex" => array(1, 3)),
        array("label" => "pickupDetails", "valueIndex" => array(8, 1)),
    ),

    "PROCESS_DAYEND" => true,
    "DAYEND_FORM" => array(1, 1),               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
    "DAYEND_MOBIMGID_FORM" => array(9, 1),      // Dayend unique mob id (9 = Page Id, 1 = Ques ID)
    "DAYEND_DATA" => array(
        array("label" => "route", "valueIndex" => array(1, 2)),
    ),

    "PROCESS_OTHER" => true,                    // JSON contains other items other than Attendance and Day end selfie
);

$PROJECT_SPECIFIC_SETTINGS = array(
    // ITC Client
    1 => array(
        // Van DS PH2 Project
        1 => array(
            // vandify
            99 => array(
                "PROCESS_TABLE" => "tblsurvey_response_new",
                "RESPONSE_TABLE" => "tblsurvey_response_details",
                "IMG_TABLE" => "tblsurvey_response_file_new",
                "NO_OF_QUESTIONS" => array(3, 2, 2, 2, 5, 2, 2, 2, 1),
                "PROCESS_BASED_ON_SKIP_LOGIC" => array(
                    1 => array(
                        "QUES_ID" => 1,
                        // needs to be in lowercase
                        "attendance" => array(1, 8),
                        "morning survey" => array(1, 8),
                        "outlet survey" => array(1, 2),
                        "outlet order" => array(1, 2),
                        "add outlet" => array(1, 5),
                        "day end selfie" => array(1, 9)
                    ),
                    2 => array(
                        "QUES_ID" => 2,
                        // needs to be in lowercase
                        "yes" => array(2, 3),
                        "no" => array(2, 4)
                    ),
                    5 => array(
                        "QUES_ID" => 5,
                        // needs to be in lowercase
                        "yes" => array(5, 6),
                        "no" => array(5, 7)
                    ),
                ),
                "PROCESS_ATTENDANCE" => true,
                "ATTENDANCE_FORM" => array(1, 1),           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
                "ATTENDANCE_MOBIMGID_FORM" => array(8, 2),  // Attendance unique mob id (8 = Page Id, 2 = Ques ID)
                "ATTENDANCE_DATA" => array(
                    array("label" => "route", "valueIndex" => array(1, 2)),
                    array("label" => "beatAdherenceReason", "valueIndex" => array(1, 3)),
                    array("label" => "pickupDetails", "valueIndex" => array(8, 1)),
                ),

                "PROCESS_DAYEND" => true,
                "DAYEND_FORM" => array(1, 1),               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
                "DAYEND_MOBIMGID_FORM" => array(9, 1),      // Dayend unique mob id (9 = Page Id, 1 = Ques ID)
                "DAYEND_DATA" => array(
                    array("label" => "route", "valueIndex" => array(1, 2)),
                ),

                "PROCESS_OTHER" => true,
            ),
            // WD Price Update App
            100 => array(
                "NO_OF_QUESTIONS" => array(1),
                "PROCESS_OTHER" => true,
            ),
            // MDO
            10 => array(
                "PROCESS_TABLE" => "tblsurvey_response_new",
                "RESPONSE_TABLE" => "tblsurvey_response_details_mdo",
                "IMG_TABLE" => "tblsurvey_response_file_new",
                "NO_OF_QUESTIONS" => array(4, 1, 9, 9, 9, 9, 9, 9, 9, 2, 4),
                "PROCESS_BASED_ON_SKIP_LOGIC" => array(
                    1 => array(
                        "QUES_ID" => 1,
                        // needs to be in lowercase
                        "attendance" => array(1, 0),
                        "day end selfie" => array(1, 11),
                        "infra details" => array(1, 11),
                    ),
                ),
                "PROCESS_ATTENDANCE" => true,
                "ATTENDANCE_FORM" => array(1, 1),           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
                "ATTENDANCE_DATA" => array(
                    array("label" => "workingWith", "valueIndex" => array(1, 2)),
                    array("label" => "selectRouteYouAreGoingOn", "valueIndex" => array(1, 3))
                ),

                "PROCESS_DAYEND" => true,
                "DAYEND_FORM" => array(1, 1),               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
                "DAYEND_DATA" => array(
                    array("label" => "outlet", "valueIndex" => array(11, 1)),
                    array("label" => "SalesVolume", "valueIndex" => array(11, 2)),
                    array("label" => "SalesValue", "valueIndex" => array(11, 3)),
                ),

                "PROCESS_OTHER" => true,
            ),
        ),
    ),
);

function getRespTable($clientId = null, $projectId = null, $jsonId = null)
{
    global $COMMON_PROCESS_SETTINGS, $PROJECT_SPECIFIC_SETTINGS;

    // Add null checks and use isset() for safer array access
    if (
        !is_null($clientId) && !is_null($projectId) && !is_null($jsonId) &&
        isset($PROJECT_SPECIFIC_SETTINGS[$clientId][$projectId][$jsonId]["RESPONSE_TABLE"])
    ) {
        return $PROJECT_SPECIFIC_SETTINGS[$clientId][$projectId][$jsonId]["RESPONSE_TABLE"];
    }
    return isset($COMMON_PROCESS_SETTINGS["RESPONSE_TABLE"]) ?
        $COMMON_PROCESS_SETTINGS["RESPONSE_TABLE"] :
        "tblsurvey_response_details";
}

function getImageTable($clientId = null, $projectId = null, $jsonId = null)
{
    global $COMMON_PROCESS_SETTINGS, $PROJECT_SPECIFIC_SETTINGS;

    // Add null checks and use isset() for safer array access
    if (
        !is_null($clientId) && !is_null($projectId) && !is_null($jsonId) &&
        isset($PROJECT_SPECIFIC_SETTINGS[$clientId][$projectId][$jsonId]["IMG_TABLE"])
    ) {
        return $PROJECT_SPECIFIC_SETTINGS[$clientId][$projectId][$jsonId]["IMG_TABLE"];
    }
    return isset($COMMON_PROCESS_SETTINGS["IMG_TABLE"]) ?
        $COMMON_PROCESS_SETTINGS["IMG_TABLE"] :
        "tblsurvey_response_file_new";
}
