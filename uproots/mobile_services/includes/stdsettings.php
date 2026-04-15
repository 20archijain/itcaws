<?php

if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set('serialize_precision', 10);
}

// Json Response Constants
define("SUCCESS", 200); //Success response code
define("WARNING", 300); //Warning response code
define("FAILED", 400); //Failed response code
define("DEFAULT_TIMEZONE", "Asia/Calcutta");
define("MOVE_IMAGES_ON_BACKBLAZE", false);
define("OUR_DLT_ENTITY_ID", "1201159202619010869");

date_default_timezone_set(DEFAULT_TIMEZONE);

// DB Constants
// define("DB_HOSTNAME", "3.109.90.46");
define("DB_HOSTNAME", "localhost");
define("DB_NAME", "itcawsportal_mobiappilaryauth");

// LOCAL DB
if (isset($isLocalServer) && $isLocalServer && constant("DB_HOSTNAME") == "localhost") {
    $DB_USERNAME = "root";
    $DB_PASSWORD = "";

    // BTLMO74
    define("BTLMO74_DB_HOSTNAME", "localhost");
    $BTLMO74_DB_USERNAME = "root";
    $BTLMO74_DB_PASSWORD = "";
} else {
    // PROD DB

    $DB_USERNAME = "itcawsportal_itccamp";
    $DB_PASSWORD = "AppiLaryC406#";

    // BTLMO74
    define("BTLMO74_DB_HOSTNAME", "ded3735.inmotionhosting.com");
    $BTLMO74_DB_USERNAME = "btlmo74_btlmonitor";
    $BTLMO74_DB_PASSWORD = "AppiLaryC406#";
}

// Folder Names
$CLIENTS_FOLDER = "/clients";
$JSON_FOLDER = "/p_xml";
$FILES_FOLDER = "/m_files"; // Used to store all files except images
$IMG_FOLDER = "/m_img"; // Used to store image files
$RES_FOLDER = "/res";
$DRAWABLE_FOLDER = "/drawable";
$FONTS_FOLDER = "/fonts";
$LOGS_FOLDER = "/logs_data";
$UPROOTS_FOLDER = "/uproots";
$MOBILE_SERVICES_FOLDER = "/mobile_services";
$PHP_LIBS_FOLDER = "/php_libs";
$PRODS_ANY_FOLDER = "/prods/any";

// URLS
$OUTABOX_SERVER_UPIMG_URL = "https://accostmedia.com/uproots";
$OUTABOX_SERVER_API_URL = "$OUTABOX_SERVER_UPIMG_URL/mobile_services/api/move_images.php";

// Paths
$UPROOTS_PATH = $SITE_PATH . $UPROOTS_FOLDER;
$MOBILE_SERVICES_PATH = $UPROOTS_PATH . $MOBILE_SERVICES_FOLDER;
$FONTS_PATH = $MOBILE_SERVICES_PATH . $FONTS_FOLDER;
$LOG_PATH = $MOBILE_SERVICES_PATH . $LOGS_FOLDER;
$PHP_LIBS_PATH = $UPROOTS_PATH . $PHP_LIBS_FOLDER;
$PRODS_ANY_PATH = $UPROOTS_PATH . $PRODS_ANY_FOLDER;
$SMS_ALERT_LIB_PATH = $PHP_LIBS_PATH . "/SMSAlert/vendor/autoload.php";
$AWS_SDK_LIB_PATH = $PHP_LIBS_PATH . "/aws-sdk-php/vendor/autoload.php";

// TABLES LIST
$TBL_CLOUD_AUTH_PIN = "tblcloud_auth_pin_angular";
$TBL_APP_LOGIN_LOG = "tblapp_login_log";
$TBL_SURVEY_RES_NEW = "tblsurvey_response_new";
$TBL_SURVEY_RESPONSE = "tblsurvey_response_details";
$TBL_SURVEY_RES_FILE_NEW = "tblsurvey_response_file_new";
$TBL_CLOUDRING_LIVE = "tblcloudring_live";
$TBL_CLOUDRING_PROMOTER_VERIFICATION = "tblcloudring_promoter_verification";
$TBL_PROJECTS = "tblprojects";
$TBL_PROJECT_TEAM = "tblproject_team";
$TBL_ATTENDANCE = "tblattendance";
$TBL_BREAK = "tblbreak";
$TBL_ROUTE_DETAILS = "tblroute_details";
$TBL_MOBILE_SUMMARY = "tblmobile_summary";
$TBL_VANDS_SUMMARY = "tblvands_summary";
$TBL_CONSTANTS = "tblconstants";
$TBL_OFFLINE_DROPDOWN_OPTIONS = "tbloffline_dropdown_options";
$TBL_MOBILE_CALENDAR_DATA = "tblmobile_calendar_data";
$TBL_MOBILE_CALENDAR_SUMMARY = "tblmobile_calendar_summary";
$TBL_MOBILE_CALENDAR_SUMMARY_KEYDETAILS = "tblmobile_calendar_summary_keydetails";
$TBL_BRANCH_PRODUCTS = "tblbranch_products";
$TBL_STOCK_SUMMARY = "tblstock_summary";
$TBL_BRANCH_PICKUPSTOCK_PRODUCTS = "tblbranch_pickupstock_products";
$TBL_WEEKLY_SALES_SUMMARY = "tblweekly_sales_summary";
$TBL_TEAM_LOCATION = "tblteam_location";
$TBL_DAILY_MOBILE_SUMMARY = "tbldaily_mobile_summary";
$TBL_HAWKER_MOBILE_SUMMARY = "tblhawker_mobile_summary";
$TBL_NOTIFICATION = "tbl_notification";
$TBL_ORDER_DETAILS = "tblsurvey_response_details_orders";
$TBL_DELIVERY_DETAILS = "tblsurvey_response_details_delivery";

// DB Names
$IMPACT_DB = "itccam5_impact";
$NOVICEMARCOM_DB = "itccam5_novicemarcom";
$WONDER_DB = "itccam5_wonder";
$ZX_DB = "itccam5_zx";
$DELHI_DB = "itccam5_delhi";
$ITC_DB = "itccam5_itc";
$ITCNEW_DB = "itccam5_itcnew";
$ITCPH2_DB = "itcawsportal_itcph2";
$JAIPUR_DB = "itccam5_jaipur";
$SNPL_DB = "itccam5_snpl";
$SOUTH_DB = "itccam5_south";
$BTLMO74_DB_NAME = "btlmo74_mobiappilaryauth";
$GENERIC_DB = "btlmo74_generic";
$IMPACT3_DB = "btlmo74_impact3";
$OCP_DB = "btlmo74_ocp";
$V5_DB = "btlmo74_v5";
$ECOMMERCE_DB = "btlmo74_ecommerce";

$ARR_BTLMO74_DBS = array(
    $GENERIC_DB,
    $IMPACT3_DB,
    $OCP_DB,
    $V5_DB,
    $ECOMMERCE_DB,
);

define("MOVE_IMAGES_ON_DIGITALOCEAN", array(
    "ALL_DB" => true,       // set this to true to enable digitalocean for all DB on inmotionhosting
    $IMPACT_DB => false,
    $NOVICEMARCOM_DB => false,
    $WONDER_DB => false,
    $ZX_DB => false,
    $DELHI_DB => false,
    $ITC_DB => true,
    $ITCNEW_DB => false,
    $ITCPH2_DB => false,
    $JAIPUR_DB => false,
    $SNPL_DB => false,
    $SOUTH_DB => false,
));

// Dummy mobile number list
$arrDefaultDummyNumberList = array(
    "1111111111", "2222222222", "3333333333", "4444444444", "5555555555", "6666666666",
    "7777777777", "8888888888", "9999999999"
);

// Dummy otp list
$arrDefaultDummyOtpList = array(
    "9999", "99999"
);

// Watermark Position
$ARR_WATERMARK_POSITION = array(
    "TOP" => "top",
    "BOTTOM" => "bottom",
    "LEFT" => "left",
    "RIGHT" => "right",
    "CENTER_HORIZONTAL" => "centerHorizontal",
);

// Image formats
$ARR_IMAGE_FORMATS = array(".jpg", ".jpeg", ".png", ".gif");

// OTP Mode
$OTP_MODE = array(
    "SMS_ONLY" => 1,
    "WHATSAPP_ONLY" => 2,
    "SMS_AND_WHATSAPP_BOTH" => 3,
);
