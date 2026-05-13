<?php

$COMMON_PROCESS_SETTINGS = [
    "ALLOWED_PID" => [1],
    "PROCESS_TABLE" => "tblsurvey_response_new",
    "RESPONSE_TABLE" => "tblsurvey_response_details",
    "IMG_TABLE" => "tblsurvey_response_file_new",

    // ITC Client, Van DS Project
    "NO_OF_QUESTIONS" => [3, 2, 2, 2, 5, 2, 2, 2, 1],
    "PROCESS_BASED_ON_SKIP_LOGIC" => [
        1 => [
            "QUES_ID" => 1,
            // needs to be in lowercase
            "attendance" => [1, 8],
            "outlet survey" => [1, 2],
            "outlet order" => [1, 2],
            "add outlet" => [1, 5],
            "day end selfie" => [1, 9]
        ],
        2 => [
            "QUES_ID" => 2,
            // needs to be in lowercase
            "yes" => [2, 3],
            "no" => [2, 4]
        ],
        5 => [
            "QUES_ID" => 5,
            // needs to be in lowercase
            "yes" => [5, 6],
            "no" => [5, 7]
        ],
    ],
    "PROCESS_ATTENDANCE" => true,
    "ATTENDANCE_FORM" => [1, 1],           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
    "ATTENDANCE_MOBIMGID_FORM" => [8, 2],  // Attendance unique mob id (8 = Page Id, 2 = Ques ID)
    "ATTENDANCE_DATA" => [
        ["label" => "route", "valueIndex" => [1, 2]],
        ["label" => "beatAdherenceReason", "valueIndex" => [1, 3]],
        ["label" => "pickupDetails", "valueIndex" => [8, 1]],
    ],

    "PROCESS_DAYEND" => true,
    "DAYEND_FORM" => [1, 1],               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
    "DAYEND_MOBIMGID_FORM" => [9, 1],      // Dayend unique mob id (9 = Page Id, 1 = Ques ID)
    "DAYEND_DATA" => [
        ["label" => "route", "valueIndex" => [1, 2]],
    ],

    "PROCESS_OTHER" => true,                    // JSON contains other items other than Attendance and Day end selfie
];

$PROJECT_SPECIFIC_SETTINGS = [
    // ITC Client
    1 => [
        // Van DS PH2 Project
        1 => [
            // vandify
            99 => [
                "PROCESS_TABLE" => "tblsurvey_response_new",
                "RESPONSE_TABLE" => "tblsurvey_response_details",
                "IMG_TABLE" => "tblsurvey_response_file_new",
                "NO_OF_QUESTIONS" => [3, 2, 2, 2, 5, 2, 2, 2, 1],
                "PROCESS_BASED_ON_SKIP_LOGIC" => [
                    1 => [
                        "QUES_ID" => 1,
                        // needs to be in lowercase
                        "attendance" => [1, 8],
                        "morning survey" => [1, 8],
                        "outlet survey" => [1, 2],
                        "outlet order" => [1, 2],
                        "add outlet" => [1, 5],
                        "day end selfie" => [1, 9]
                    ],
                    2 => [
                        "QUES_ID" => 2,
                        // needs to be in lowercase
                        "yes" => [2, 3],
                        "no" => [2, 4]
                    ],
                    5 => [
                        "QUES_ID" => 5,
                        // needs to be in lowercase
                        "yes" => [5, 6],
                        "no" => [5, 7]
                    ],
                ],
                "PROCESS_ATTENDANCE" => true,
                "ATTENDANCE_FORM" => [1, 1],           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
                "ATTENDANCE_MOBIMGID_FORM" => [8, 2],  // Attendance unique mob id (8 = Page Id, 2 = Ques ID)
                "ATTENDANCE_DATA" => [
                    ["label" => "route", "valueIndex" => [1, 2]],
                    ["label" => "beatAdherenceReason", "valueIndex" => [1, 3]],
                    ["label" => "pickupDetails", "valueIndex" => [8, 1]],
                ],

                "PROCESS_DAYEND" => true,
                "DAYEND_FORM" => [1, 1],               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
                "DAYEND_MOBIMGID_FORM" => [9, 1],      // Dayend unique mob id (9 = Page Id, 1 = Ques ID)
                "DAYEND_DATA" => [
                    ["label" => "route", "valueIndex" => [1, 2]],
                ],

                "PROCESS_OTHER" => true,
            ],
            // WD Price Update App
            100 => [
                "NO_OF_QUESTIONS" => [1],
                "PROCESS_OTHER" => true,
            ],
            // MDO
            10 => [
                "PROCESS_TABLE" => "tblsurvey_response_new",
                "RESPONSE_TABLE" => "tblsurvey_response_details_mdo",
                "IMG_TABLE" => "tblsurvey_response_file_new",
                "NO_OF_QUESTIONS" => [4, 1, 9, 9, 9, 9, 9, 9, 9, 2, 4, 4],
                "PROCESS_BASED_ON_SKIP_LOGIC" => [
                    1 => [
                        "QUES_ID" => 1,
                        // needs to be in lowercase
                        "attendance" => [1, 0],
                        "day end selfie" => [1, 11],
                        "infradetails" => [1, 12],
                    ],
                ],
                "PROCESS_ATTENDANCE" => true,
                "ATTENDANCE_FORM" => [1, 1],           // Attendance radio answer (1 = Page Id, 1 = Ques ID)
                "ATTENDANCE_DATA" => [
                    ["label" => "workingWith", "valueIndex" => [1, 2]],
                    ["label" => "selectRouteYouAreGoingOn", "valueIndex" => [1, 3]]
                ],

                "PROCESS_DAYEND" => true,
                "DAYEND_FORM" => [1, 1],               // Dayend radio answer (1 = Page Id, 1 = Ques ID)
                "DAYEND_DATA" => [
                    ["label" => "outlet", "valueIndex" => [11, 1]],
                    ["label" => "SalesVolume", "valueIndex" => [11, 2]],
                    ["label" => "SalesValue", "valueIndex" => [11, 3]],
                ],

                "PROCESS_OTHER" => true,
            ],
        ],
    ],
];

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
