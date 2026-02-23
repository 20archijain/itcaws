<?php

require_once $include_path . "defined_index.php";

require $PHP_MAILER_EXCEPTION_PATH;
require $PHP_MAILER_MAIN_PATH;
require $PHP_MAILER_SMTP_PATH;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $PHP_SPREADSHEET_PATH;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//generate response msg to send
function responseMessage($message = array(), $status = 0, $data = "", $hidePopup = false)
{
    $statusArr = array(
        0 => constant('FAILED'),
        1 => constant('SUCCESS'),
        2 => constant('WARNING'),
    );

    $arrMsg = array(
        "status" => $statusArr[$status],
        "message" => $message,
        "data" => $data,
        "hidePopup" => $hidePopup,
    );
    return $arrMsg;
}

//get module info array
function getModuleSchema($module, $isHiddenModule = false)
{
    return array(
        "breadcrumbs" => $module['show_breadcrumb'] == 1 ? true : false,
        "id" => $module['module_id'],
        "name" => $module['module_name'],
        "hidden" => $isHiddenModule,
        "icon" => $module['module_icon'],
        "modc" => $module['module_code'],
        "pmodc" => $module['parent_module_code'],
        "fold" => $module['module_url_link'],
        "componentName" => $module['module_component'],
        "submodules" => array(),
        "actions" => array(),
    );
}

//upload file precheck
function fileUploadPrecheck($file, $allowedExtension = array("jpg", "jpeg", "png", "gif"))
{
    $extension = strtolower(getExtension($file['name'])); //get the extension of the file in a lower case format

    //valid file
    if (in_array($extension, $allowedExtension)) {
        $size = filesize($file['tmp_name']);

        //valid size
        if ($size <= constant("MAX_SIZE_IN_BYTES")) {
            return array("status" => 0, "message" => array(), "extension" => $extension);
        }
        return array("status" => 1, "message" => array("Max size of image to upload is " . (constant("MAX_SIZE_IN_BYTES") / 1000000) . " MB"));
    }
    return array("status" => 1, "message" => array("Invalid file"));
}

//upload file
function uploadFile($file, $destination, $image_name = "", $genThumbnail = false, $allowedExtension = array("jpg", "jpeg", "png", "gif"))
{
    $arrResult = array();

    //check file validity
    $imageResult = fileUploadPrecheck($file, $allowedExtension);

    //valid file
    if ($imageResult["status"] === 0) {
        try {
            $upload = new UploadAndThumbnail($destination);
            if ($genThumbnail) {
                $upload->genThumbnail(); //Allow to generate Thumbnail
            }
            $upload->upload($file, $image_name); //Upload file with new name and rename file if already exists
            $result = $upload->getMessages(); //Get messages

            $arrResult["errors"] = $result["status"];
            $arrResult["org_filename"] = $upload->getOrigFileName();
            $arrResult["filename"] = $upload->getFileName();
            $arrResult["messages"] = $result["messages"];
        } catch (Exception $error) {
            if (is_array($error->getMessage())) {
                $result = $error->getMessage();
            } else {
                $result = array($error->getMessage());
            }
            $arrResult["errors"] = 1;
            $arrResult["messages"] = $result;
        }

        return $arrResult;
    }

    return array("errors" => 1, "messages" => $imageResult["message"]);
}

//get file extension
function getExtension($str)
{
    $i = strrpos($str, ".");
    if (!$i) {
        return "";
    }
    $l = strlen($str) - $i;
    $ext = substr($str, $i + 1, $l);
    return $ext;
}

// get hashed random value
function uniqueHashValue($sSalt)
{
    $s_Time = md5(microtime());
    $iFinal = $s_Time . $sSalt;
    $unique = hash('sha256', $iFinal);
    return $unique;
}

// Generate a unique ID based on the current time in microseconds.
function uniqueSmallValue($Prefix, $sSalt)
{
    $Pre = $Prefix . "_" . $sSalt . "_";

    $unique = uniqid($Pre);
    return $unique;
}

//get formatted Date
function currentDate($date = '', $format = "Y-m-d", $zone = "Asia/Calcutta")
{
    $timezone = new DateTimeZone($zone);
    if ($date === '') {
        $date = new DateTime();
    } else {
        $date = new DateTime($date);
    }
    $date->setTimezone($timezone);

    return $date->format($format);
}

//get formatted DateTime
function currentDateTime($dateTime = '', $format = "Y-m-d H:i:s", $zone = "Asia/Calcutta")
{
    $timezone = new DateTimeZone($zone);
    if ($dateTime === '') {
        $datetime = new DateTime();
    } else {
        $datetime = new DateTime($dateTime);
    }
    $datetime->setTimezone($timezone);

    return $datetime->format($format);
}

//get days difference b/w 2 dates
function dateDiffInDays($date1, $date2)
{
    $diff = abs(strtotime($date1) - strtotime($date2));
    $days = intval($diff / 86400);
    return $days;
}

//Check if a password is valid
function validPassword($password, $hash)
{
    $delimiter = strpos($hash, '$');
    if ($delimiter === false) {
        return false;
    }

    $salt = base64_decode(substr($hash, 0, $delimiter));
    $sPasswordHash = securePassword($password, $salt);

    return $sPasswordHash === $hash;
}

//get random long key
function pseudoRandomKey($size)
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        $rnd = base64_encode(openssl_random_pseudo_bytes($size, $strong));
        if ($strong === true) {
            return substr($rnd, 0, $size);
        }
    }

    //fallback to mt_rand if php < 5.3 or no openssl available
    $sha = $rnd = "";

    for ($i = 0; $i < $size; $i++) {
        $sha = hash('sha256', $sha . mt_rand());
        $char = mt_rand(0, 62);
        $rnd .= chr(hexdec($sha[$char] . $sha[$char + 1]));
    }
    $rnd = base64_encode($rnd);
    return $rnd;
}

//get form field value
function getFormData($field_name, $key = "")
{
    if (isEmptyString($key)) {
        return filter(isset($field_name) ? $field_name : '');
    }
    return filter(isset($field_name[$key]) ? $field_name[$key] : '');
}

// create mail template
function generateMailTemplate($title, $body, $createBodyTable = false, $arrHeader = array())
{
    if ($createBodyTable && count($body) > 0) {
        $sData = "";
        if (count($arrHeader) > 0) {
            $sData = "<thead><tr>";
            foreach ($arrHeader as $header) {
                $sData .= "<th>$header</th>";
            }
            $sData .= "</tr></thead>";
        }

        $sData .= "<tbody>";
        foreach ($body as $arrData) {
            $sData .= "<tr>";
            foreach ($arrData as $data) {
                $sData .= "<td>$data</td>";
            }
            $sData .= "</tr>";
        }
        $sData .= "</tbody>";
    } else {
        $sData = $body;
    }

    return "<html>
              <head>
                <title>$title</title>
              </head>
              <body>
                $sData
                <br/>
                <p>Regards</p>
                <p><strong>" . constant("MAIL_REGARDS") . "</strong></p>
              </body>
            </html>";
}

//mail function w/o attachment
function sendMail($to, $subject, $message, $cc = array(), $from = MAIL_FROM, $bcc = array())
{
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=iso-8859-1';

    $headers[] = 'From: ' . $from;

    if (isNonEmptyArray($cc)) {
        $headers[] = 'Cc: ' . getStringFromArray($cc, true);
    }

    if (isNonEmptyArray($bcc)) {
        $headers[] = 'Bcc: ' . getStringFromArray($bcc, true);
    }

    if (isNonEmptyArray($to)) {
        $to = getStringFromArray($to, true);
    }

    return @mail($to, $subject, $message, implode("\r\n", $headers));
}

//generate password for temporary login
function generatePassword($l = 8, $c = 1, $n = 0, $s = 0)
{
    // get count of all required minimum special chars
    $count = $c + $n + $s;

    // all inputs clean, proceed to build password
    // change these strings if you want to include or exclude possible password characters
    $chars = "abcdefghijklmnopqrstuvwxyz";
    $caps = strtoupper($chars);
    $nums = "0123456789";
    $syms = "!@#$%^*()=+";
    $out = "";

    // build the base password of all lower-case letters
    for ($i = 0; $i < $l; $i++) {
        $out .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }

    // create arrays if special character(s) required
    if ($count) {
        // split base password to array; create special chars array
        $tmp1 = str_split($out);
        $tmp2 = array();

        // add required special character(s) to second array
        for ($i = 0; $i < $c; $i++) {
            array_push($tmp2, substr($caps, mt_rand(0, strlen($caps) - 1), 1));
        }
        for ($i = 0; $i < $n; $i++) {
            array_push($tmp2, substr($nums, mt_rand(0, strlen($nums) - 1), 1));
        }
        for ($i = 0; $i < $s; $i++) {
            array_push($tmp2, substr($syms, mt_rand(0, strlen($syms) - 1), 1));
        }

        // hack off a chunk of the base password array that is as big as the special chars array
        if (($l - $count) > 0) {
            $tmp1 = array_slice($tmp1, 0, $l - $count);
        }
        // mix the characters up
        shuffle($tmp1);
        shuffle($tmp2);
        // merge special character(s) array with base password array
        $tmp1 = array_merge($tmp1, $tmp2);
        // convert to string for output
        $out = implode('', $tmp1);

        // get desired length password if length exceeds
        if (strlen($out) > $l) {
            $out = substr($out, 0, $l);
        }
    }

    return $out;
}

//add salt to password
function securePassword($password, $salt)
{
    $hash = '';
    for ($i = 0; $i < constant("HASH_CYCLE_LIMIT"); $i++) {
        $hash = hash('sha512', $hash . $salt . $password);
    }

    return base64_encode($salt) . '$' . $hash;
}

//check if a strong password (min length 6, atleast 1 uppercase letter, 1 lowercase letter, 1 special char)
function strongPwdCheck($candidate)
{
    $r1 = "/[A-Z]/"; //Uppercase
    $r2 = "/[a-z]/"; //lowercase
    $r3 = "/[!@#$%^*()=+]/"; // whatever you mean by special char
    $r4 = "/[0-9]/"; //numbers
    $r5 = "/[\[\]\&\-\_\{\}\;\:\"\'\.\,\?\/\\\|]/"; // Don't match these characters

    if (preg_match_all($r1, $candidate, $o) < 1) {
        return false;
    }

    if (preg_match_all($r2, $candidate, $o) < 1) {
        return false;
    }

    if (preg_match_all($r3, $candidate, $o) < 1) {
        return false;
    }

    if (preg_match_all($r4, $candidate, $o) < 1) {
        return false;
    }

    if (preg_match_all($r5, $candidate, $o) > 1) {
        return false;
    }

    return true;
}

// get landing page list for project or user login
function getLandingPageList($dbConn, $fromListing)
{
    // Don't use dstatus = 0 if API call is from listing
    $cond = $fromListing ? "module_actioncode IN ('VIEW', 'ADD') AND module_id > 2" : "dstatus = 0 AND module_actioncode IN ('VIEW', 'ADD') AND module_id > 2";
    return getOptions($dbConn, $GLOBALS['TABLES']["MODULES_TABLE"], "module_name", "module_id", $cond);
}

// get year list
function getYearList()
{
    return array(
        array("label" => 2024, "value" => 2024),
        array("label" => 2025, "value" => 2025),
    );
}

// get year list
function getYearListNew()
{
    return array(
        array("label" => 2025, "value" => 2025),
    );
}

// get month list
function getMonthList()
{
    return array(
        array("label" => "January", "value" => "01"),
        array("label" => "February", "value" => "02"),
        array("label" => "March", "value" => "03"),
        array("label" => "April", "value" => "04"),
        array("label" => "May", "value" => "05"),
        array("label" => "June", "value" => "06"),
        array("label" => "July", "value" => "07"),
        array("label" => "August", "value" => "08"),
        array("label" => "September", "value" => "09"),
        array("label" => "October", "value" => "10"),
        array("label" => "November", "value" => "11"),
        array("label" => "December", "value" => "12"),
    );
}

//for clean csv data
function cleanCSVValue($value)
{
    $value = trim((string)($value ?? ''));
    $value = str_replace(["\n", "\r"], " ", $value);
    $value = str_replace('"', '""', $value);
    return '"' . $value . '"';
}

function calculateDistanceBwCoordinates($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $getDistanceInM = true)
{
    if ($getDistanceInM) {
        $earthRadius = 6371000; // in M
    } else {
        $earthRadius = 6371; // in KM
    }
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2);
    $angle = 2 * atan2(sqrt($angle), sqrt(1 - $angle));
    return $angle * $earthRadius;
}

// create log
function debug_log($stringData, $fileName = "logfile", $logFolderName = "", $logDirectory = "")
{
    $timezone = new DateTimeZone("Asia/Calcutta");
    $date = new DateTime();
    $date->setTimezone($timezone);

    $cdate = $date->format("Y-m-d");

    $Directory = $logDirectory ? $logDirectory : $GLOBALS["LOG_PATH"] . "/" . $cdate . $logFolderName;

    if ($Directory && !file_exists($Directory)) {
        mkdir($Directory, 0777, true);
    }

    $myFile = $Directory . "/" . $fileName . ".txt";
    $fh = fopen($myFile, 'a');
    fwrite($fh, $stringData);
    fwrite($fh, "\r\n");
    fclose($fh);
}

// get valid date
function getValidDate($date)
{
    if ($date && isset($date["year"]) && $date["year"]) {
        return date("Y-m-d", strtotime($date["year"] . "-" . $date["month"] . "-" . $date["day"]));
    }
    return null;
}

// get valid time
function getValidTime($time)
{
    if ($time) {
        return $time["hour"] . ":" . $time["minute"] . ":" . $time["second"];
    }
    return null;
}

function sortArrayByDate($a, $b)
{
    $timeStamp1 = strtotime($a["date"]);
    $timeStamp2 = strtotime($b["date"]);

    return $timeStamp1 - $timeStamp2;
}

function sortArrayByDateByNameKey($a, $b)
{
    $timeStamp1 = strtotime($a["name"]);
    $timeStamp2 = strtotime($b["name"]);

    return $timeStamp1 - $timeStamp2;
}

function getTimeDifferenceInString($startDatetime, $endDatetime, $getTotalSecondsOnly = false, $addSecondsInString = false, $getTotalMinutesOnly = false)
{
    if ($startDatetime && $endDatetime) {
        $first = date('Y-m-d H:i:s', strtotime($startDatetime));
        $last = date('Y-m-d H:i:s', strtotime($endDatetime));

        $firstDatetime = new DateTime($first, new DateTimeZone('Asia/Calcutta'));
        $secondDatetime = new DateTime($last, new DateTimeZone('Asia/Calcutta'));
        $diff = $secondDatetime->diff($firstDatetime);

        $hours = $diff->format('%h');
        $mins = $diff->format('%i');
        $sec = $diff->format('%s');

        if ($getTotalSecondsOnly) {
            return ($hours > 0 ? ($hours * 60 * 60) : 0) + ($mins > 0 ? ($mins * 60) : 0) + $sec;
        } elseif ($getTotalMinutesOnly) {
            return ($hours > 0 ? ($hours * 60) : 0) + ($mins > 0 ? ($mins) : 0);
        } else {
            return ($hours > 0 ? $hours . "h " : "") . ($mins > 0 ? $mins . "m " : "") . ($addSecondsInString || ($hours == 0 && $mins == 0) ? $sec : "");
        }
    } else {
        return $getTotalSecondsOnly ? 0 : "0";
    }
}

function getTimeDifferenceInMinutes($startDatetime, $endDatetime)
{
    // Convert datetime strings to DateTime objects
    $start = new DateTime($startDatetime);
    $end = new DateTime($endDatetime);

    // Calculate the time difference
    $interval = $start->diff($end);

    // Convert the difference to minutes
    $timeSpentInMin = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    return $timeSpentInMin;
}


// Convert no of seconds into time string
function getTimeStringFromSeconds($seconds)
{
    $seconds = round($seconds);
    $hours = $seconds > 0 ? floor($seconds / 3600) : 0;
    $secondsLeft = $seconds > 0 ? ($seconds - ($hours * 60 * 60)) : 0;
    $minutes = $seconds > 0 ? floor($secondsLeft / 60) : 0;
    $secondsLeft = $seconds > 0 ? ($secondsLeft - ($minutes * 60)) : 0;

    return ($hours > 0 ? $hours . "h " : "") . ($minutes > 0 ? $minutes . "m " : "") . $secondsLeft . "s";
}

function isDatetimeSmallerThanOther($targetDatetime, $actualDatetime)
{
    $actualDatetime = new DateTime($actualDatetime);
    $maxDatetime = new DateTime($targetDatetime);
    return $actualDatetime <= $maxDatetime;
}

// Get current week dates, week starts from Monday
function getCurrentWeekDates()
{
    $weekEndDate = currentDate();
    $day = date('w');   // return 0 = Sunday, 1= Monday ...
    $weekStartDate = null;

    // Sunday
    if ($day == 0) {
        $weekStartDate = date("Y-m-d", strtotime("-6 days"));
    } elseif ($day == 1) {
        // Monday
        $weekStartDate = $weekEndDate;
    } else {
        $weekStartDate = date("Y-m-d", strtotime("-" . ($day - 1) . " days"));
    }

    return array($weekStartDate, $weekEndDate);
}

// Send mail with CSV Or Xlsx attached
function sendMailWithCSVOrXlsxAttached($isCSV, $fileName, $arrHeader, $arrData, $sSubject, $arrTo, $arrCC = array(), $shareLink = false)
{
    global $SAVE_SPREADSHEET_PATH;

    $filename = "$SAVE_SPREADSHEET_PATH/$fileName";
    if ($isCSV) {
        //generate csv file
        $fh = fopen($filename, 'w') or die("Can't open $filename");

        array_unshift($arrData, $arrHeader);

        foreach ($arrData as $data) {
            if (fputcsv($fh, $data) === false) {
                die("Can't write CSV line");
            }
        }
        fclose($fh);
    } else {
        $filename = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";

        // Generate xlsx file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rowNo = 1;
        // create header
        if (isNonEmptyArray($arrHeader)) {
            $columnNo = 1;
            foreach ($arrHeader as $value) {
                $sheet->setCellValueByColumnAndRow($columnNo, $rowNo, $value);
                $columnNo++;
            }
            $rowNo++;
        }

        if (isNonEmptyArray($arrData)) {
            foreach ($arrData as $arrValue) {
                $columnNo = 1;
                foreach ($arrValue as $value) {
                    $sheet->setCellValueByColumnAndRow($columnNo, $rowNo, $value);
                    $columnNo++;
                }
                $rowNo++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
    }
    $fileLink = "";
    if ($shareLink) {
        $storeFolder = $GLOBALS["SAVE_CUMMULATIVE_SPREADSHEET_PATH"];
        $baseUrl = $GLOBALS["SITE_URL"];
        if (!file_exists($storeFolder)) {
            mkdir($storeFolder, 0777, true);
        }
        foreach (glob($storeFolder . "/Cumulative_*") as $oldFile) {
            if (is_file($oldFile)) {
                unlink($oldFile);
            }
        }
        $extension = $isCSV ? "csv" : "xlsx";
        $cumulativeFileName = "Cumulative_$filename" . $extension;
        $newPath = $storeFolder . "/" . $cumulativeFileName;
        copy($filename, $newPath);
        $fileLink = $baseUrl . "/email_xls/" . $cumulativeFileName;
    }
    $email = new PHPMailer(true);
    $mailFrom = constant("MAIL_FROM");
    $mailRegards = constant("MAIL_REGARDS");

    try {
        $email->setFrom($mailFrom, $mailRegards);

        if (count($arrTo) > 0) {
            foreach ($arrTo as $to) {
                $email->addAddress($to);
            }
        }
        if (count($arrCC) > 0) {
            foreach ($arrCC as $cc) {
                $email->addCC($cc);
            }
        }

        $email->Subject = $sSubject;
        $email->Body = $sSubject;
        if($shareLink){
         $email->Body.= "<a href='$fileLink' target='_blank'>$fileLink</a>";
        }else{
            $email->addAttachment($filename, $fileName);
        }

        $isMailSend = $email->send();

        if ($isMailSend) {
            echo "Mail send";
        } else {
            echo "Mail not send";
        }
    } catch (Exception $e) {
        echo "Mail could not be sent. Mailer Error: {$email->ErrorInfo}";
    }
}

// Replace special characters
function removeSpecialCharFromString($string, $replacement = " ", $pattern = "/[^a-zA-Z0-9 \,\.\/\-\_\@\#\$\&\*\(\)\[\]\+\=\?\:\'\"]/")
{
    return preg_replace($pattern, $replacement, $string);
}

// Validate OTP, returns
// 0: invalid data i.e mobile or OTP
// 1: miss call not found
// 2: Unknown
// 3: Duplicate
// 4: Call found but code not match
// 5: Valid
// 6: OTP expired
function getCallStatus($dbConn, $otpTable, $mobileNo, $otpCode, $skipMobileNoCheck = false, $checkAndAllowBackDateDuplicateCall = false, $minBackDateDurationInDays = 1, $canOTPExpire = false, $maxOtpValidTimeInSec = 1)
{
    $mobRegex = "/^[6789][0-9]{9}$/";
    $otpRegex = "/^[A-Za-z0-9]{4,6}$/";

    //mobile or code invalid
    if ((!$skipMobileNoCheck && (!$mobileNo || !preg_match($mobRegex, $mobileNo))) || !$otpCode || !preg_match($otpRegex, $otpCode)) {
        $dup_status = 0;
    } else {
        $otpCode = strtoupper($otpCode);
        $cond = "AND rec_who = ?";
        $arrParams = array($mobileNo);

        // skip mobile no check
        if ($skipMobileNoCheck) {
            $cond = "AND token = ?";
            $arrParams = array($otpCode);
        }

        //get all miss calls
        $rsAction = null;
        $iActionRows = 0;
        $sQuery = "SELECT token, dup_process, rdt, rec_who FROM $otpTable WHERE dstatus = 0 $cond ORDER BY dup_process DESC, rec_id DESC";
        $dbConn->ExecuteSelectQuery($sQuery, $rsAction, $iActionRows, $arrParams);

        // miss call not found
        if ($iActionRows == 0) {
            $dup_status = 1;
            $updateRecWho = null;
        } else {
            // miss call found
            $isCallFound = false;
            $updateRecWho = null;
            while ($row = $dbConn->GetData($rsAction)) {
                $tableOtpCode = strtoupper(trim($row['token']));
                $tableDupProcess = $row['dup_process'];
                $tableRdt = $row['rdt'];
                $tableMobile = strtoupper(trim($row['rec_who']));
                $updateRdt = $tableRdt;

                // Duplicate call
                if ($tableDupProcess == 1) {
                    $isCallFound = true;
                    $dup_status = 3;
                    $updateRecWho = $tableMobile;

                    if ($skipMobileNoCheck) {
                        // It maybe possible that this ($tableOtpCode) OTP was sent to some other mobile no or same mobile no but on other day
                        // So check if any record is found with this OTP having dup_process = 0, if found, then check if any record exists with found number having dup_process = 1. If found means duplicate call else valid call
                        $rsAction1 = null;
                        $iActionRows1 = 0;
                        $sQuery1 = "SELECT rec_who, rdt FROM $otpTable WHERE dstatus = 0 AND token = ? AND dup_process = '0' ORDER BY rec_id DESC";
                        $dbConn->ExecuteSelectQuery($sQuery1, $rsAction1, $iActionRows1, array($tableOtpCode));

                        if ($iActionRows1 > 0) {
                            $isValidCallFound = false;
                            while ($row1 = $dbConn->GetData($rsAction1)) {
                                $recWho = $row1["rec_who"];
                                $rdt = $row1["rdt"];

                                // Check if any call exist with $recWho mobile and dup_process = 1 means duplicate else valid
                                $iRecId = getRowColumn($otpTable, "rec_id", "AND dup_process = '1' AND rec_who = '$recWho'");

                                // valid call
                                if (!$iRecId) {
                                    $dup_status = 5;
                                    $isValidCallFound = true;
                                    $updateRecWho = $recWho;
                                    $updateRdt = $rdt;
                                }

                                // valid call found, exit
                                if ($isValidCallFound) {
                                    break;
                                }
                            }
                        }
                    }
                } elseif ($tableDupProcess == 0) {
                    // valid miss call
                    // Code match
                    if ($tableOtpCode == $otpCode) {
                        // valid call
                        $isCallFound = true;
                        $dup_status = 5;
                        $updateRecWho = $tableMobile;

                        if ($skipMobileNoCheck) {
                            // It maybe possible that user has given multiple missed calls from same mobile and receive new OTP every time
                            // so check if any record exists with this mobile number and having process = 1 which means duplicate call
                            $arrAnotherCall = getRowColumns($otpTable, "dup_process, rdt", "AND rec_who = ? ORDER BY dup_process DESC, rec_id DESC", array($updateRecWho));

                            // duplicate call
                            if (isset($arrAnotherCall) && $arrAnotherCall[0] == 1) {
                                $dup_status = 3;
                                $updateRdt = $arrAnotherCall[1];
                            }
                        }
                    } else {
                        // code not match
                        $dup_status = 4;
                    }
                } else {
                    // unknown
                    $dup_status = 2;
                }

                // allow duplicate call if previous call was made minimun days back
                if ($dup_status == 3 && $checkAndAllowBackDateDuplicateCall) {
                    $now = new DateTime(date("Y-m-d H:i:s"), new DateTimeZone('Asia/Calcutta'));
                    $past = new DateTime($updateRdt, new DateTimeZone('Asia/Calcutta'));
                    $diff = $now->diff($past);
                    $days = $diff->format('%d');

                    // Valid call if days past is min days
                    if ($days >= $minBackDateDurationInDays) {
                        $dup_status = 5;
                    }
                }

                // check if OTP is not expired i.e invalid after given duration
                if ($dup_status == 5 && $canOTPExpire) {
                    $nowDate = new DateTime(date("Y-m-d H:i:s"), new DateTimeZone('Asia/Calcutta'));
                    $now = $nowDate->getTimestamp();

                    $pastDate = new DateTime($updateRdt, new DateTimeZone('Asia/Calcutta'));
                    $past = $pastDate->getTimestamp();
                    $noOfSecPassed = abs($now - $past);

                    // OTP expired
                    if ($noOfSecPassed > $maxOtpValidTimeInSec) {
                        $dup_status = 6;
                    }
                }

                // exit if call found
                if ($isCallFound) {
                    break;
                }
            }

            if ($updateRecWho) {
                $currentDateTime = currentDateTime();
                $updateCond = "rec_who = ?";
                $arrParams = array("mobileNo" => $updateRecWho);

                updateRecord($dbConn, $otpTable, "dup_process = '1', dup_processed_on = '$currentDateTime', process = '1'", $updateCond, $arrParams);
            }
        }
    }

    return array($dup_status, $updateRecWho);
}
