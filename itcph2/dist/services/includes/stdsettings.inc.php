<?php

require_once $include_path . "defined_index.php";

define('MAX_LOGIN_ATTEMPTS_FUNC', true); //Enable maximum login attempts functionality
define('MAX_LOGIN_ATTEMPTS', 5); //maximum 5 login attempts

define('PASSWORD_CHANGE_ALERT_FUNC', false); //Enable change password warning functionality
define('PASSWORD_CHANGE_MAX_DAYS', 35); //maximum days allowed to login w/o password change
define('PASSWORD_CHANGE_WARNING_DAYS', 25); //minimum days required to show password change warning

define('ENABLE_CAPTCHA_FOR_LOGIN', false); //Enable/Disable captcha check for login
define('TEMP_PASSWORD_ALERT_FUNC', true); //Show/Hide temporary password login warning
define('ENABLE_TWO_WAY_AUTH', false); // Enable OTP verification if true
define('OTP_LIMIT_PER_USER_PER_DAY', 10); // Max OTP allowed per day per user
define("OUR_DLT_ENTITY_ID", "1201159202619010869");

define('ALLOW_MULTIPLE_LOGIN_PER_USERID_FUNC', true); //Enable multiple login by same id functionality

define('HASH_CYCLE_LIMIT', 300); //used for password match

//used for strong password match (include atleast 1 uppercase, 1 lowercase, 1 number, 1 special character)
define('STRONG_PASSWORD_CHECK_FUNC', false);

define("MAX_SIZE_IN_BYTES", 10485760); // max image size to upload (in Bytes) (10 MB, 1MB = 1024KB)
define("UPLOAD_THUMBNAIL_SIZE_IN_PX", 80); // thumbnail image size (width and height) to upload (in px)

define("SUCCESS", 200); //Success response code
define("WARNING", 300); //Warning response code
define("FAILED", 400); //Failed response code

define('MAIL_FROM', "appilary@btlmonitor.com");
define('MAIL_REGARDS', "Appilary Support Team");

define("DEFAULT_TIMEZONE", "Asia/Calcutta");
date_default_timezone_set(DEFAULT_TIMEZONE);

// used for session
if (isset($_SERVER["SERVER_NAME"])) {
    if ($_SERVER["SERVER_NAME"] === "localhost") {
        define('DOMAIN_NAME', $_SERVER["SERVER_NAME"]);
    } else {
        $domainName = str_replace("www.", "", $_SERVER["SERVER_NAME"]);
        define('DOMAIN_NAME', ".$domainName");
    }
} else {
    define('DOMAIN_NAME', 'localhost'); // fallback for CLI
}

// Dummy Image
define("DUMMY_LOGO_NAME", 'dummy_pic.jpg');

// Customer Folder
define('CUSTOMER_FOLDER', "cust_itcph2_angular_581654hy5");
define('CUSTOMER_LOGO', "dummy_pic.jpg");
define('CUSTOMER_NAME', "ITCPH2");             // used while creating teams
define('PRODS_ANY_FOLDER', "/prods/any");
define('CLIENT_LOGO_FOLDER', "clientlogo");
define('CLIENTS_FOLDER', "clients");
define('CLIENTS_UPLOAD_MEDIA_FOLDER', "m_files");
define('CLIENTS_UPLOAD_IMAGE_FOLDER', "m_img");
define('CLIENTS_JSON_FOLDER', "p_xml");
define('CLIENTS_RES_FOLDER', "res/android/drawable");
define("GOOGLE_MAP_API_KEY", "AIzaSyCQl11SJbdomqoqquBRoOzWpVbjcQ6Sroo");

// DB DETAILS
// define("DB_HOSTNAME", "3.109.90.46");
define("DB_HOSTNAME", "localhost");
$DB_DBNAME = 'itcawsportal_itcph2';

$isLocalServer = isset($_SERVER["SERVER_NAME"]) && $_SERVER["SERVER_NAME"] == "localhost" ? true : false;

// LOCAL DB
if (isset($isLocalServer) && $isLocalServer && constant("DB_HOSTNAME") == "localhost") {
    $DB_USERNAME = 'root';
    $DB_PASSWORD = '';
} else {
    // PROD DB

    $DB_USERNAME = 'itcawsportal_itccamp';
    $DB_PASSWORD = 'AppiLaryC406#';
}

// LOCAL FILES
if (isset($isLocalServer) && $isLocalServer) {
    // For file include/upload
    $SITE_PATH = 'C:/xampp/htdocs/work/itcawsportal';
    $LOG_PATH = $SITE_PATH . '/itcph2/dist/services/logs_data';

    // for file display/download
    $SITE_URL = 'http://localhost:80/work/itcawsportal/uproots';
} else {
    // PROD FILES

    // For file include/upload
    $SITE_PATH = '/home/itcawsportal/public_html';
    $LOG_PATH = $SITE_PATH . '/itcph2/services/logs_data';

    // for file display/download
    $SITE_URL = 'https://upimg2.radardashboard.com';
}

$UPROOTS_PATH = $SITE_PATH . '/uproots';
$CLASSES_PATH = $UPROOTS_PATH . '/classes';
$LOGO_PATH = $UPROOTS_PATH . '/logo';
$CAPTCHA_FONTS_PATH = $UPROOTS_PATH . '/fonts';
$CAPTCHA_IMG_PATH = $UPROOTS_PATH . '/captcha';
$MOBILE_SERVICES_PATH = $UPROOTS_PATH . '/mobile_services';
$WATERMARK_FONTS_PATH = $MOBILE_SERVICES_PATH . '/fonts';
$CUST_FOLDER_PATH = $UPROOTS_PATH . constant("PRODS_ANY_FOLDER");
$SAVE_SPREADSHEET_PATH = $UPROOTS_PATH . '/upload_xls';
$SAVE_PDF_PATH = $UPROOTS_PATH . '/pdf';

$CAPTCHA_IMG_URL = $SITE_URL . '/captcha';
$UPLOAD_URL = $SITE_URL . constant("PRODS_ANY_FOLDER");
$LOGO_URL = $SITE_URL . '/logo';
$MARKER_URL = $SITE_URL . '/markers';
$SAVE_SPREADSHEET_URL = $SITE_URL . '/upload_xls';
$SAVE_PDF_URL = $SITE_URL . '/pdf';

// LIBRARY PATH
$LIB_PATH = $UPROOTS_PATH . "/php_libs";
// Used to send mail
$PHP_MAILER_EXCEPTION_PATH = $LIB_PATH . "/PHPMailer/src/Exception.php";
$PHP_MAILER_MAIN_PATH = $LIB_PATH . "/PHPMailer/src/PHPMailer.php";
$PHP_MAILER_SMTP_PATH = $LIB_PATH . "/PHPMailer/src/SMTP.php";
// Used to generate Excel files
$PHP_SPREADSHEET_PATH = $LIB_PATH . "/PhpSpreadsheet/vendor/autoload.php";
// Used to generate PPT files
$PHP_PRESENTATION_PATH = $LIB_PATH . "/PHPPresentation/src/PhpPresentation/Autoloader.php";
$PHP_OFFICE_PATH = $LIB_PATH . "/PhpOffice/Common/src/Common/Autoloader.php";
$PHP_FPDF_PATH = $LIB_PATH . "/fpdf186/fpdf.php";

// Cloud DB
$DB_DBNAME_CLOUD = 'itcawsportal_mobiappilaryauth';

// Watermark Position
$ARR_WATERMARK_POSITION = array(
    "TOP" => "top",
    "BOTTOM" => "bottom",
    "LEFT" => "left",
    "RIGHT" => "right",
    "CENTER_HORIZONTAL" => "centerHorizontal",
);


// Team Types
$ARR_TEAM_TYPES = array(
    0 => "Van DS",
    1 => "Niche",
    2 => "Town SWD",
    3 => "Hybrid",
    4 => "SCP",
    5 => "NPSR",
    6 => "RMD",
    7 => "MDO",
    10 => "FSO",
);
