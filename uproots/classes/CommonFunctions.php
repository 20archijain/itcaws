<?php

// phpcs:ignore
class CommonFunctions
{
    // generate response msg to send for web portal API
    final public function webResponseMessage($message = array(), $status = 0, $data = "", $hidePopup = false)
    {
        $statusArr = array(
            0 => 400,
            1 => 200,
            2 => 300,
        );

        $arrMsg = array(
            "status" => $statusArr[$status],
            "message" => $message,
            "data" => $data,
            "hidePopup" => $hidePopup,
        );
        return $arrMsg;
    }

    // create log
    final public function debugLog($stringData, $fileName = "logfile", $logFolderName = "", $logDirectory = "")
    {
        global $LOG_PATH;

        $cdate = $this->currentDate();

        $Directory = $logDirectory ? $logDirectory : ($LOG_PATH . "/" . $cdate . $logFolderName);

        // Create folder
        if ($Directory && !file_exists($Directory)) {
            @mkdir($Directory, 0777, true);
        }

        if (!$fileName) {
            $fileName = "logfile";
        }

        // Create empty file
        $myFile = $Directory . "/" . $fileName . ".txt";
        // Adding below code as a precaution only
        if (!file_exists($myFile)) {
            $fh = @fopen($myFile, 'a');
            if ($fh !== false) {
                fclose($fh);
            }
        }

        $fh = @fopen($myFile, 'a');
        if ($fh !== false) {
            fwrite($fh, $stringData);
            fwrite($fh, "\r\n");
            fclose($fh);
        }
    }

    // Check if variable is defined and not null
    final public function isDefined($var)
    {
        return isset($var);
    }

    // Check if value is true
    final public function isNonEmpty($value)
    {
        // false values are "", 0, 0.0, "0", NULL, FALSE, [], $var (a variable declared, but without a value)
        return $this->isDefined($value) && !empty($value);
    }

    // Check if array and is not empty
    final public function isNonEmptyArray($arr)
    {
        return $this->isNonEmpty($arr) && is_array($arr);
    }

    // Check if string and is blank
    final public function isEmptyString($str)
    {
        return $this->isDefined($str) && is_string($str) && $str === "";
    }

    // Check if given variable is equal to value or not
    final public function matchValue($var, $val, $strict = false)
    {
        if ($strict) {
            return $this->isDefined($var) && $this->isDefined($val) && $var === $val;
        }
        return $this->isDefined($var) && $this->isDefined($val) && $var == $val;
    }

    // filter out unwanted content from input, $flag may be
    // ENT_COMPAT: Will convert double-quotes and leave single-quotes alone
    // ENT_QUOTES: Will convert both double and single quotes
    // ENT_NOQUOTES: Will leave both double and single quotes unconverted.)
    final public function filter($input, $flag = ENT_COMPAT)
    {
        if (is_array($input)) {
            if ($this->isNonEmptyArray($input)) {
                foreach ($input as $key => $val) {
                    $input[$key] = is_string($val) ? htmlentities(strip_tags(trim($val)), $flag) : $val;
                }
                return $input;
            }
            return $input;
        }
        $out = trim($input); // Kills needless whitespace
        $out = strip_tags($out); // Kills html tags

        // Convert all applicable characters to HTML entities to protect from from sql injection
        return htmlentities($out, $flag);
    }

    // get and filter form field value
    final public function getFormData($field_name, $key = "")
    {
        if ($this->isEmptyString($key)) {
            return $this->filter(isset($field_name) ? $field_name : "");
        }
        return $this->filter(isset($field_name[$key]) ? $field_name[$key] : "");
    }

    // Return Today's or Given Date in a specified format in given timezone
    final public function currentDate($format = "Y-m-d", $dateString = "", $timezone = DEFAULT_TIMEZONE)
    {
        $oTimezone = new DateTimeZone($timezone);
        if ($dateString) {
            $datetime = new DateTime($dateString, $oTimezone);
        } else {
            $datetime = new DateTime("now", $oTimezone);
        }

        return $datetime->format($format);
    }

    // Return Today's or Given Date Time in a specified format in given timezone
    final public function currentDateTime($format = "Y-m-d H:i:s", $dateTimeString = "", $timezone = DEFAULT_TIMEZONE)
    {
        $oTimezone = new DateTimeZone($timezone);
        if ($dateTimeString) {
            $datetime = new DateTime($dateTimeString, $oTimezone);
        } else {
            $datetime = new DateTime("now", $oTimezone);
        }

        return $datetime->format($format);
    }

    // get valid date selected from UI datepicker
    final public function getValidDate($date)
    {
        if ($date && is_numeric($date["year"]) && is_numeric($date["month"]) && is_numeric($date["day"])) {
            return date("Y-m-d", strtotime($date["year"] . "-" . $date["month"] . "-" . $date["day"]));
        }
        return null;
    }

    // get time difference b/t 2 datetime either as
    // string (format: days, hours, mins, sec), or
    // as no of seconds, or
    // as no of minutes
    final public function getTimeDifference(
        $startDatetime,
        $endDatetime,
        $getTimeInSeconds = false,
        $getTimeInMinutes = false,
        $addSecondsInString = false,
        $timezone = DEFAULT_TIMEZONE
    ) {
        if ($startDatetime && $endDatetime) {
            $first = date('Y-m-d H:i:s', strtotime($startDatetime));
            $last = date('Y-m-d H:i:s', strtotime($endDatetime));

            $firstDatetime = new DateTime($first, new DateTimeZone($timezone));
            $secondDatetime = new DateTime($last, new DateTimeZone($timezone));
            $diff = $secondDatetime->diff($firstDatetime);

            $days = $diff->format('%d');
            $hours = $diff->format('%h');
            $mins = $diff->format('%i');
            $sec = $diff->format('%s');

            // Get diff in seconds
            if ($getTimeInSeconds) {
                return ($days > 0 ? $days * 24 * 60 * 60 : 0) + ($hours > 0 ? $hours * 60 * 60 : 0) + ($mins > 0 ? $mins * 60 : 0) + $sec;
            } elseif ($getTimeInMinutes) {
                // Get diff in minutes
                return ($days > 0 ? $days * 24 * 60 : 0) + ($hours > 0 ? $hours * 60 : 0) + $mins;
            } else {
                return ($days > 0 ? $days . "d " : "") . ($hours > 0 ? $hours . "h " : "") .
                    ($mins > 0 ? $mins . "m " : "") . ($addSecondsInString || ($days == 0 && $hours == 0 && $mins == 0) ? $sec . "s" : "");
            }
        } else {
            return ($getTimeInSeconds || $getTimeInMinutes) ? 0 : "0s";
        }
    }

    // Convert no of seconds into time string
    // Eg: if $seconds = (24 * 60 * 60) + (60 * 60) + 60 + 10, Output: 1d 1h 1m 10s
    final public function getTimeStringFromSeconds($seconds)
    {
        $seconds = round($seconds);

        $days = $seconds > 0 ? floor($seconds / (24 * 60 * 60)) : 0;
        $secondsLeft = $days > 0 ? ($seconds - ($days * 24 * 60 * 60)) : $seconds;

        $hours = $secondsLeft > 0 ? floor($secondsLeft / (60 * 60)) : 0;
        $secondsLeft = $hours > 0 ? ($secondsLeft - ($hours * 60 * 60)) : $secondsLeft;

        $minutes = $secondsLeft > 0 ? floor($secondsLeft / 60) : 0;
        $secondsLeft = $minutes > 0 ? ($secondsLeft - ($minutes * 60)) : $secondsLeft;

        return ($days > 0 ? $days . "d " : "") . ($hours > 0 ? $hours . "h " : "") . ($minutes > 0 ? $minutes . "m " : "") . ($secondsLeft > 0 || ($days == 0 && $hours == 0 && $minutes == 0) ? $secondsLeft . "s" : "");
    }

    final public function isDatetime1SmallerOrEqualThanDatetime2($datetime1, $datetime2, $timezone = DEFAULT_TIMEZONE)
    {
        $oTimezone = new DateTimeZone($timezone);
        $datetime1 = new DateTime($datetime1, $oTimezone);
        $datetime2 = new DateTime($datetime2, $oTimezone);
        return $datetime1 <= $datetime2;
    }

    // Get required no of question marks to use for IN clause
    final public function getSafeStringFromArrayForInClause($array)
    {
        $inStr = "";
        $arrParams = array();

        if ($this->isNonEmptyArray($array)) {
            $inStr = trim(str_repeat("?, ", count($array)), ", ");
            $arrParams = $array;
        }

        return array($inStr, $arrParams);
    }

    // Upload single file or multiple files
    final public function uploadFiles(
        $files,
        $destination,
        $arrConfig = array()
    ) {
        $arrResult = array();

        $maxSize = isset($arrConfig["maxSize"]) && $arrConfig["maxSize"] ? $arrConfig["maxSize"] : MAX_SIZE_IN_BYTES;
        $arrPermittedMimeTypes = isset($arrConfig["arrPermittedMimeTypes"]) && $arrConfig["arrPermittedMimeTypes"] ?
            $arrConfig["arrPermittedMimeTypes"] : array("image/jpeg", "image/png", "image/gif");
        $label = isset($arrConfig["label"]) && $arrConfig["label"] ? $arrConfig["label"] : "file";
        $newNameWithoutExtension = isset($arrConfig["newNameWithoutExtension"]) && $arrConfig["newNameWithoutExtension"] ?
            $arrConfig["newNameWithoutExtension"] : "";
        $genThumbnail = isset($arrConfig["genThumbnail"]) ? $arrConfig["genThumbnail"] : false;
        $showWatermark = isset($arrConfig["showWatermark"]) ? $arrConfig["showWatermark"] : false;
        $watermarkText = isset($arrConfig["watermarkText"]) ? $arrConfig["watermarkText"] : "";
        $arrWatermarkConfig = isset($arrConfig["arrWatermarkConfig"]) ? $arrConfig["arrWatermarkConfig"] : array();

        try {
            $upload = new UploadAndThumbnail($destination, $maxSize);
            $upload->setPermittedFileTypes($arrPermittedMimeTypes);
            if ($genThumbnail) {
                $upload->genThumbnail();
            }
            if ($showWatermark) {
                $upload->setWatermark($showWatermark, $watermarkText, $arrWatermarkConfig);
            }
            $upload->upload($files, $newNameWithoutExtension, $label);
            $result = $upload->getMessages();

            $arrResult["errors"] = $result["status"];
            $arrResult["messages"] = $result["messages"];
            $arrResult["org_filename"] = $upload->getOrigFileName();
            $arrResult["filename"] = $upload->getFileName();
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

    // gets the extension (w/o dot) of the file
    final public function getExtension($str)
    {
        $i = strrpos($str, ".");
        if (!$i) {
            return "";
        }

        return strtolower(substr($str, $i + 1));
    }

    // Calculate distance between 2 co-ordinates in m or km
    final public function calculateDistanceBwCoordinates(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $getDistanceInM = true
    ) {
        if ($getDistanceInM) {
            $earthRadius = 6371000; // in M
        } else {
            $earthRadius = 6371; // in KM
        }

        // convert from degrees to radians
        $latFrom = is_numeric($latitudeFrom) ? deg2rad($latitudeFrom) : 0;
        $lonFrom = is_numeric($longitudeFrom) ? deg2rad($longitudeFrom) : 0;
        $latTo = is_numeric($latitudeTo) ? deg2rad($latitudeTo) : 0;
        $lonTo = is_numeric($longitudeTo) ? deg2rad($longitudeTo) : 0;

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2);
        $angle = 2 * atan2(sqrt($angle), sqrt(1 - $angle));
        return $angle * $earthRadius;
    }

    // Calculates the *exact* bounding box of text to be written on image
    // returns an associative array with these keys:
    // left, top:  coordinates you will pass to imagettftext
    // width, height: dimension of the image you have to create
    private function calculateTextBox($fontSize, $fontAngle, $fontFile, $text)
    {
        // calculates and returns the bounding box in pixels for a TrueType text
        $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

        return array(
            "left" => abs($minX),
            "top" => abs($minY),
            "width" => $maxX - $minX,
            "height" => $maxY - $minY,
        );
    }

    // add watermark on image
    final public function addWatermark(
        $source,
        $watermarkText,
        $watermarkPosition,
        $arrTextcolor = array(),
        $createAndfillRectangle = false,
        $arrRectangleBgColor = array(),
        $createRedRectangularBorder = true,
        $maxFontSize = 40
    ) {
        global $FONTS_PATH, $ARR_WATERMARK_POSITION;

        list($width_orig, $height_orig) = getimagesize($source);
        $imageType = 1;

        // Create a new image i.e an image identifier representing the image obtained from the given filename
        $image = @imagecreatefromjpeg($source);

        // Not a JPG or JPEG file, create png file
        if ($image === false) {
            $imageType = 2;
            // Create a new image i.e an image identifier representing the image obtained from the given filename
            $image = @imagecreatefrompng($source);
        }

        // Not a PNG file either, create gif file
        if ($image === false) {
            $imageType = 3;
            // Create a new image i.e an image identifier representing the image obtained from the given filename
            $image = @imagecreatefromgif($source);
        }

        if ($image !== false) {
            // Allocate a text color in an image
            if (!($arrTextcolor && isset($arrTextcolor[0]))) {
                // Default text color is red
                $arrTextcolor = array(255, 0, 0);
            }
            $textcolor = imagecolorallocate($image, $arrTextcolor[0], $arrTextcolor[1], $arrTextcolor[2]);
            $font_file = $FONTS_PATH . "/monofont.ttf";
            $angle = 0;
            if (
                $watermarkPosition == $ARR_WATERMARK_POSITION["LEFT"] ||
                $watermarkPosition == $ARR_WATERMARK_POSITION["RIGHT"]
            ) {
                $angle = 270;
            }

            $fontSize = 0;
            // Calculate max font size as per image size and watermark text
            $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);
            while (($box['width'] < $width_orig) && ($box['height'] < $height_orig) && ($fontSize <= $maxFontSize)) {
                $fontSize++;
                $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);
            }

            // Reduce font by 2 to adjust properly
            $fontSize--;
            $fontSize--;
            $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);

            // Margin if any
            $leftMargin = 5;
            $topMargin = 5;
            $bottomMargin = 5;

            // TOP
            // Rectangle Box coordinates
            $boxX1 = $leftMargin;
            $boxY1 = $topMargin;
            $boxX2 = $box['width'] + $leftMargin + $leftMargin;
            $boxY2 = $box['height'] + $topMargin;

            // Text coordinates
            $textX = $leftMargin + $box['left'];
            $textY = $topMargin + $box['top'];

            // BOTTOM
            if ($watermarkPosition == $ARR_WATERMARK_POSITION["BOTTOM"]) {
                // Rectangle Box coordinates
                $boxY1 = $height_orig - $box['height'] - $bottomMargin;
                $boxY2 = $height_orig - $bottomMargin;

                // Text coordinates
                $textY = $height_orig + $box['top'] - $box['height'] - $bottomMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["LEFT"]) {
                // LEFT

                // Rectangle Box coordinates
                $boxX2 = $box['width'] + $leftMargin;
                $boxY2 = $topMargin + $box['height'] + $topMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["RIGHT"]) {
                // RIGHT
                // Rectangle Box coordinates
                $boxX1 = $width_orig - $box['width'] - $leftMargin;
                $boxX2 = $width_orig - $leftMargin;

                // Text coordinates
                $textX = $width_orig + $box['left'] - $box['width'] - $leftMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["CENTER_HORIZONTAL"]) {
                // Center Horizontal

                // Rectangle Box coordinates
                $boxX1 = ($width_orig - $box['width']) / 2;
                $boxY1 = ($height_orig / 2) - $box['height'] - $bottomMargin;
                $boxX2 = $boxX1 + $box['width'];
                $boxY2 = $boxY1 + $box['height'];

                // Text coordinates
                $textX = ($width_orig - $box['width']) / 2;
                $textY = ($height_orig / 2) - ($box['height'] / 2);
            }

            // Create rectangle around watermark text and fill it
            if ($createAndfillRectangle) {
                if ($createRedRectangularBorder) {
                    // Allocate a color for an image along with transparency
                    // create red rectangular border
                    $red = imagecolorallocatealpha($image, 255, 0, 0, 0);

                    // creates a rectangle starting at the specified coordinates.
                    imagerectangle($image, $boxX1, $boxY1, $boxX2, $boxY2, $red);
                }

                // fill bg color in rectange
                if (!($arrRectangleBgColor && isset($arrRectangleBgColor[0]))) {
                    // Default bg color is white
                    $arrRectangleBgColor = array(255, 255, 255, 20);
                }
                $rectangleBgColor = imagecolorallocatealpha(
                    $image,
                    $arrRectangleBgColor[0],
                    $arrRectangleBgColor[1],
                    $arrRectangleBgColor[2],
                    $arrRectangleBgColor[3]
                );

                // Creates a rectangle filled with color in the given image starting at point 1 and ending at point 2
                imagefilledrectangle($image, $boxX1, $boxY1, $boxX2, $boxY2, $rectangleBgColor);
            }

            // Writes the given text into the image (exclude .gif when ImageMagick extension is installed and enabled) using TrueType fonts
            if (!($imageType == 3 && extension_loaded('imagick'))) {
                imagettftext($image, $fontSize, $angle, $textX, $textY, $textcolor, $font_file, $watermarkText);
            }

            if ($imageType === 1) {
                // saves a JPG or JPEG image from the given image
                imagejpeg($image, $source);
            } elseif ($imageType === 2) {
                // saves a PNG image from the given image
                imagepng($image, $source);
            } else {
                // Add watermark on animated gif if ImageMagick extension is installed and enabled
                // To install ImageMagick on localhost, follow below steps:
                // 1. Open https://mlocati.github.io/articles/php-windows-imagick.html and download required zip file
                // 2. Extract zip in C:/, rename folder and copy "php_imagick.dll" in "C:\xampp\php\ext"
                // 3. Copy all files stating with CORE_RL and IM_MOD_RL in "C:\xampp\apache\bin"
                // 4. Open Environment variables and add "C:\<FOLDER_NAME>" in "Path" User variable
                // 5. Open php.ini and search "Dynamic Extensions" and add "extension=imagick" at the end of "Dynamic Extensions"
                // 6. To get support in VSC, open "Open Settings (UI)" and search "stubs" and add "imagick"
                if (extension_loaded('imagick')) {
                    // Creates an Imagick instance for a specified image
                    $imagick = new Imagick($source);
                    // Composites a set of images i.e this allows adding text in a gif
                    $imagick = $imagick->coalesceImages();

                    // Create a new drawing palette
                    $draw = new ImagickDraw();
                    $draw->setFont($font_file);
                    $draw->setFontSize($fontSize * 1.4);
                    $color = new ImagickPixel("rgb({$arrTextcolor[0]}, {$arrTextcolor[1]}, {$arrTextcolor[2]})");
                    $draw->setFillColor($color);
                    // Draw text on the image
                    $imagick->annotateImage($draw, $textX, $textY, 360 - $angle, $watermarkText);

                    // saves a GIF image from the given image
                    $imagick->writeImages($source, true);

                    // Clean up resources
                    $imagick->clear();
                    $imagick->destroy();
                } else {
                    // Add watermark on static gif if ImageMagick extension is not installed or not enabled
                    // saves a GIF image from the given image
                    imagegif($image, $source);
                }
            }

            // Destroy an image to freed any memory associated
            imagedestroy($image);
        }
    }

    // create thumbnail of image
    final public function createThumbnail($source, $thumbnail, $width, $height, $extension)
    {
        list($width_orig, $height_orig) = getimagesize($source);

        if ($width_orig <= $width && $height_orig <= $height) {
            $scale_ratio = 1;
        } elseif ($width_orig > $height_orig) {
            $scale_ratio = $width / $width_orig;
        } else {
            $scale_ratio = $height / $height_orig;
        }

        $width = round($width_orig * $scale_ratio);
        $height = round($height_orig * $scale_ratio);

        $img = false;
        $extension = strtolower($extension);

        if ($width > 0 && $height > 0) {
            // Create a new true color image i.e an image object representing a black image of the specified size
            $new_thumb = imagecreatetruecolor($width, $height);

            switch ($extension) {
                case ".png":
                    try {
                        // Create a new image i.e
                        // an image identifier representing the image obtained from the given filename
                        $img = @imagecreatefrompng($source);

                        if ($img !== false) {
                            // Copy and resize part of an image with resampling
                            // i.e copies a rectangular portion of one image to another image
                            imagecopyresampled($new_thumb, $img, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                            // saves a PNG image from the given image
                            imagepng($new_thumb, $thumbnail, 0);
                        }
                    } catch (Exception $e) {
                    }
                    break;
                case ".gif":
                    try {
                        // Create animated gif if ImageMagick extension is installed and enabled
                        if (extension_loaded('imagick')) {
                            $this->createAnimatedGifThumbnail($source, $thumbnail, $width, $height);
                        } else {
                            // Create static gif as ImageMagick extension is not installed or not enabled
                            // Create a new image i.e
                            // an image identifier representing the image obtained from the given filename
                            $img = @imagecreatefromgif($source);

                            if ($img !== false) {
                                // Copy and resize part of an image with resampling
                                // i.e copies a rectangular portion of one image to another image
                                imagecopyresampled($new_thumb, $img, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                                // saves a GIF image from the given image
                                imagegif($new_thumb, $thumbnail);
                            }
                        }
                    } catch (Exception $e) {
                    }
                    break;
                default:
                    try {
                        $imageType = 1;
                        // Create a new image i.e
                        // an image identifier representing the image obtained from the given filename
                        $img = @imagecreatefromjpeg($source);

                        if ($img === false) {
                            $imageType = 2;
                            // Create a new image
                            // i.e an image identifier representing the image obtained from the given filename
                            $img = @imagecreatefrompng($source);
                        }

                        if ($img !== false) {
                            // Copy and resize part of an image with resampling
                            // i.e copies a rectangular portion of one image to another image
                            imagecopyresampled($new_thumb, $img, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

                            if ($imageType === 1) {
                                // saves a JPEG image from the given image
                                imagejpeg($new_thumb, $thumbnail, 100);
                            } else {
                                // saves a PNG image from the given image
                                imagepng($new_thumb, $thumbnail, 0);
                            }
                        }
                    } catch (Exception $e) {
                    }
                    break;
            }

            if ($img !== false) {
                // Destroy an image to freed any memory associated
                imagedestroy($img);
                imagedestroy($new_thumb);
            }
        }
    }

    // Create animated gif thumbnail
    final public function createAnimatedGifThumbnail($source, $thumbnail, $width, $height)
    {
        // Create an Imagick object from the source GIF
        $imagick = new Imagick($source);
        $imagick = $imagick->coalesceImages();

        // Ensure that we are dealing with an animated GIF
        if (!$imagick->getNumberImages()) {
            throw new Exception('Source GIF does not contain any frames.');
        }

        do {
            $imagick->resizeImage($width, $height, Imagick::FILTER_BOX, 1);
        } while ($imagick->nextImage());

        $imagick = $imagick->deconstructImages();
        $imagick->writeImages($thumbnail, true);

        // Clean up resources
        $imagick->clear();
        $imagick->destroy();
    }

    // call API and get result
    final public function getApiResponseUsingCurl(
        $apiEndPoint,
        $arrHeaders = array(),
        $isPostApi = false,
        $postData = null
    ) {
        // Initialize a cURL session
        $ch = curl_init();

        // Set the request URL
        curl_setopt($ch, CURLOPT_URL, $apiEndPoint);
        // return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set headers
        if ($this->isNonEmptyArray($arrHeaders)) {
            // Headers example
            // $arrHeaders = array(
            //     'Content-Type: application/json',
            //     'Authorization: Basic ' . base64_encode("$username:$password")
            // );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
        }

        // POST call
        if ($isPostApi) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        // execute API and get response
        $output = curl_exec($ch);

        // get information of result
        $arrInfo = curl_getinfo($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);

        // all well
        if ($arrInfo && isset($arrInfo["http_code"]) && $arrInfo["http_code"] == 200) {
            return $output;
        }

        return null;
    }

    // Replace special characters
    final public function removeSpecialCharFromString(
        $string,
        $replacement = " ",
        $pattern = "/[^a-zA-Z0-9 \,\.\/\-\_\@\#\$\&\*\(\)\[\]\+\=\?\:\'\"]/"
    ) {
        return preg_replace($pattern, $replacement, $string);
    }

    // generate string from array seperated by provided seperator
    final public function getStringFromArray($array, $withoutQuotes = false, $seperator = ", ", $key = "")
    {
        $str = "";
        if ($this->isNonEmptyArray($array)) {
            $i = 1;
            foreach ($array as $val) {
                $value = $this->isEmptyString($key) ? $val : $val[$key];

                if ($this->matchValue($i, count($array))) {
                    if ($withoutQuotes) {
                        $str .= $value;
                    } else {
                        $str .= "'" . $value . "'";
                    }
                } else {
                    if ($withoutQuotes) {
                        $str .= $value . $seperator;
                    } else {
                        $str .= "'" . $value . "'" . $seperator;
                    }
                }
                $i++;
            }
        } elseif (!$this->isEmptyString($array)) {
            // if non-empty string
            $str = $array;
        }

        return $str;
    }

    // get no days between 2 dates excluding particular week day like sunday,
    // if dates are not provided, get no days in current month excluding particular week day
    final public function getCountOfDaysExcluding($dayToExclude = "", $startDate = null, $endDate = null)
    {
        $currentYear = date("Y");
        $currentMonth = date("m");
        $noOfDaysInAMonth = date("t");

        $startDate = $startDate ? $startDate : date("Y-m-d", strtotime("$currentYear-$currentMonth-01"));
        $endDate = $endDate ? $endDate : date("Y-m-d", strtotime("$currentYear-$currentMonth-$noOfDaysInAMonth"));

        $count = 0;
        while ($startDate <= $endDate) {
            $day = "";
            if ($dayToExclude) {
                $day = strtolower(date("l", strtotime($startDate)));
            }

            if ($day == "" || $day != strtolower($dayToExclude)) {
                $count++;
            }

            $startDate = date("Y-m-d", strtotime("$startDate +1 day"));
        }

        return $count;
    }

    // Get each label sale from grid data
    final public function getSalesFromGridDataAsArray(
        &$arrSalesLabelList,
        $arrSaleData,
        $arrSalesLabels,
        $noOfColumns = 1,
        $noOfRows = 1
    ) {
        if ($this->isNonEmptyArray($arrSaleData)) {
            // Normalize sale data
            $arrFormattedData = array();
            foreach ($arrSaleData as $data) {
                $arrFormattedData[$data["rowNo"] . "-" . $data["colNo"]] = isset($data["ans"]) &&
                    $data["ans"] && is_numeric($data["ans"]) ? (float) $data["ans"] : 0;
            }

            for ($row = 1; $row <= $noOfRows; $row++) {
                $sales = 0;
                for ($column = 1; $column <= $noOfColumns; $column++) {
                    if (isset($arrFormattedData[$row . "-" . $column]) && $arrFormattedData[$row . "-" . $column] > 0) {
                        $sales += $arrFormattedData[$row . "-" . $column];
                    }
                }

                $index = array_search($arrSalesLabels[$row - 1], array_column($arrSalesLabelList, "label"));

                if ($index === false) {
                    $arrSalesLabelList[] = array(
                        "label" => $arrSalesLabels[$row - 1],
                        "value" => (string) $sales,
                    );
                } else {
                    $arrSalesLabelList[$index]["value"] += $sales;
                    $arrSalesLabelList[$index]["value"] = (string) $arrSalesLabelList[$index]["value"];
                }
            }
        }
    }

    // Get Grid data as array
    final public function getGridDataAsArray(
        $arrData,
        $noOfColumns = 1,
        $noOfRows = 1,
        $useZeroIfNotFound = false,
        $setArrayIfInvalid = false
    ) {
        $arrValues = array();

        if ($this->isNonEmptyArray($arrData) || (!$arrData && $setArrayIfInvalid)) {
            $arrFormattedData = array();

            // Normalize array
            if ($this->isNonEmptyArray($arrData)) {
                foreach ($arrData as $data) {
                    $arrFormattedData[$data["colNo"] . "-" . $data["rowNo"]] = $data["ans"];
                }
            }

            for ($column = 1; $column <= $noOfColumns; $column++) {
                $arrValues[$column - 1] = array();
                for ($row = 1; $row <= $noOfRows; $row++) {
                    $arrValues[$column - 1][] = isset($arrFormattedData[$column . "-" . $row]) &&
                        $arrFormattedData[$column . "-" . $row] ?
                        $arrFormattedData[$column . "-" . $row] : ($useZeroIfNotFound ? 0 : "");
                }
            }
        }

        return $arrValues;
    }

    // get hashed random value of 64 characters
    final public function uniqueHashValue($sSalt)
    {
        $s_Time = md5(microtime());
        $iFinal = $s_Time . $sSalt;

        return hash('sha256', $iFinal);
    }

    // Generate a random identifier based on the current time in microseconds.
    final public function uniqueSmallValue($Prefix, $sSalt)
    {
        $Pre = $Prefix . "_" . $sSalt . "_";

        return uniqid($Pre);
    }

    // get random long key of desired length
    final public function pseudoRandomKey($size)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = base64_encode(openssl_random_pseudo_bytes($size, $strong));
            if ($strong === true) {
                return substr($rnd, 0, $size);
            }
        }

        // fallback to mt_rand if php < 5.3 or no openssl available
        $sha = $rnd = "";
        for ($i = 0; $i < $size; $i++) {
            $sha = hash('sha256', $sha . mt_rand());
            $char = mt_rand(0, 62);
            $rnd .= chr(hexdec($sha[$char] . $sha[$char + 1]));
        }

        return substr(base64_encode($rnd), 0, $size);
    }

    // generate password for temporary login
    final public function generatePassword($l = 8, $c = 1, $n = 0, $s = 0)
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
            $out = implode("", $tmp1);

            // get desired length password if length exceeds
            if (strlen($out) > $l) {
                $out = substr($out, 0, $l);
            }
        }

        return $out;
    }

    // add salt to password
    final public function securePassword($password, $salt)
    {
        $hash = "";
        for ($i = 0; $i < constant("HASH_CYCLE_LIMIT"); $i++) {
            $hash = hash('sha512', $hash . $salt . $password);
        }

        return base64_encode($salt) . '$' . $hash;
    }

    // Check if a password is valid
    final public function validPassword($password, $hash)
    {
        $delimiter = strpos($hash, '$');
        if ($delimiter === false) {
            return false;
        }

        $salt = base64_decode(substr($hash, 0, $delimiter));
        $sPasswordHash = $this->securePassword($password, $salt);

        return $sPasswordHash === $hash;
    }

    // check if a strong password (min length 6, atleast $u uppercase letter, atleast $l lowercase letter, atleast $s special char, atleast $n numbers)
    final public function strongPwdCheck($candidate, $u = 1, $l = 1, $s = 1, $n = 1)
    {
        $r1 = "/[A-Z]/"; //Uppercase
        $r2 = "/[a-z]/"; //lowercase
        $r3 = "/[!@#$%^*()=+]/"; // special char
        $r4 = "/[0-9]/"; //numbers
        $r5 = "/[\[\]\&\-\_\{\}\;\:\"\'\.\,\?\/\\\|]/"; // Shouldn't contain these special characters

        if (preg_match_all($r1, $candidate) < $u) {
            return false;
        }

        if (preg_match_all($r2, $candidate) < $l) {
            return false;
        }

        if (preg_match_all($r3, $candidate) < $s) {
            return false;
        }

        if (preg_match_all($r4, $candidate) < $n) {
            return false;
        }

        if (preg_match_all($r5, $candidate) > 0) {
            return false;
        }

        return true;
    }

    // get days difference b/w 2 dates or datetimes
    // Eg 1: $date1 = $date2 = 2024-09-10, Output 0
    // Eg 2: $date1 = 2024-09-13, $date2 = 2024-09-12, Output 1
    // Eg 3: $date1 = 2024-09-12, $date2 = 2024-09-13, Output 1
    // Eg 4: $date1 = 2024-08-29 22:19:13, $date2 = 2024-09-02 09:41:36, Output 3
    final public function dateDiffInDays($date1, $date2)
    {
        $diff = abs(strtotime($date1) - strtotime($date2));
        $days = intval($diff / 86400);
        return $days;
    }

    // get month list to display in dropdown
    final public function getMonthList()
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

    // get year list to display in dropdown
    final public function getYearList($dbName = null)
    {
        return array(
            array("label" => 2022, "value" => 2022),
            array("label" => 2023, "value" => 2023),
            array("label" => 2024, "value" => 2024),
            array("label" => 2025, "value" => 2025),
            array("label" => 2026, "value" => 2026),
        );
    }

    // Sort array by date/datetime in asc/desc order defined by $order
    final public function sortArrayByDate(&$array, $order = "asc", $dateKey = "date")
    {
        usort(
            $array,
            function ($a, $b) use ($dateKey, $order) {
                $timeStamp1 = isset($a[$dateKey]) && $a[$dateKey] ? strtotime($a[$dateKey]) : 0;
                $timeStamp2 = isset($b[$dateKey]) && $b[$dateKey] ? strtotime($b[$dateKey]) : 0;

                return $order == "asc" ? ($timeStamp1 - $timeStamp2) : ($timeStamp2 - $timeStamp1);
            }
        );
    }

    final public function checkIfAllSelected($value)
    {
        $matchAll = false;

        // check if All selected
        if ($this->isNonEmptyArray($value)) {
            $isAllFound = array_search($GLOBALS['APP_CONSTANTS']['ALL_VALUE'], $value);
            // All selected
            if ($isAllFound !== false) {
                $matchAll = true;
            }
        } else {
            $matchAll = $this->matchValue($value, $GLOBALS['APP_CONSTANTS']['ALL_VALUE']);
        }

        return $matchAll;
    }

    // create mail template
    final public function generateMailTemplate($title, $body, $createBodyAsTable = false, $arrHeader = array())
    {
        if ($createBodyAsTable && count($body) > 0) {
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

    // send plain/HTML mail w/o attachment
    final public function sendMail(
        $to,
        $subject,
        $message,
        $arrCc = array(),
        $from = MAIL_FROM,
        $containsHTML = true,
        $arrBcc = array()
    ) {
        $headers = array();
        $headers[] = 'MIME-Version: 1.0';

        // covert HTML tags to actual HTML in a mail
        if ($containsHTML) {
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        }

        $headers[] = 'From: ' . $from;

        if ($this->isNonEmptyArray($arrCc)) {
            $headers[] = 'Cc: ' . $this->getStringFromArray($arrCc, true);
        }

        if ($this->isNonEmptyArray($arrBcc)) {
            $headers[] = 'Bcc: ' . $this->getStringFromArray($arrBcc, true);
        }

        if ($this->isNonEmptyArray($to)) {
            $to = $this->getStringFromArray($to, true);
        }

        return @mail($to, $subject, $message, implode("\r\n", $headers));
    }

    // Send mail with CSV Or Xlsx attached
    final public function sendMailWithAttachment(
        $fileName,
        $sSubject,
        $arrTo,
        $arrCC = array(),
        $arrHeader = array(),
        $arrData = array(),
        $body = "",
        $from = MAIL_FROM
    ) {
        global $SAVE_SPREADSHEET_PATH, $PHP_MAILER_EXCEPTION_PATH, $PHP_MAILER_MAIN_PATH, $PHP_MAILER_SMTP_PATH,
            $PHP_SPREADSHEET_PATH;

        require_once $PHP_MAILER_EXCEPTION_PATH;
        require_once $PHP_MAILER_MAIN_PATH;
        require_once $PHP_MAILER_SMTP_PATH;

        // Create folder if not exists
        if (!file_exists($SAVE_SPREADSHEET_PATH)) {
            mkdir($SAVE_SPREADSHEET_PATH, 0777, true);
        }

        $filename = "$SAVE_SPREADSHEET_PATH/$fileName";
        $extension = $this->getExtension($filename);

        // Generate and Save CSV
        if ($extension == "csv" && ($arrData || $arrHeader)) {
            // generate csv file
            $fh = fopen($filename, 'w') or die("Can't open $filename");

            array_unshift($arrData, $arrHeader);

            foreach ($arrData as $data) {
                if (fputcsv($fh, $data) === false) {
                    die("Can't write CSV line");
                }
            }
            fclose($fh);
        } elseif ($extension == "xlsx" && ($arrData || $arrHeader)) {
            // Generate and Save xlsx file
            require_once $PHP_SPREADSHEET_PATH;

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Pass complete data
            $arrExcelData = array_merge(array($arrHeader), $arrData);
            $sheet->fromArray($arrExcelData);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filename);
        }

        $mailRegards = constant("MAIL_REGARDS");

        try {
            $email = new \PHPMailer\PHPMailer\PHPMailer(true);
            $email->setFrom($from, $mailRegards);

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
            if ($body) {
                // If a custom body is provided, use it
                $email->Body = $body;
            }

            $email->addAttachment($filename, $fileName);
            $isMailSend = $email->send();

            if ($isMailSend) {
                echo "Mail send";
                return 1;
            } else {
                echo "Mail not send";
                return 0;
            }
        } catch (PHPMailer\PHPMailer\Exception $e) {
            echo "Mail could not be sent. Mailer Error: {$email->ErrorInfo}";
            return -1;
        }
    }

    // Send whatsapp message
    final public function sendWhatsappMessage(array $postFields)
    {
        $apiUrl = 'https://api.vnsai.com/WAApi/send';
        $headers = ['Cookie: SERVERID=webC1'];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $postFields,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $decodedResponse = json_decode($response, true);

        return [
            'success'   => ($httpCode == 200),
            'http_code' => $httpCode,
            'response'  => $decodedResponse ?? $response
        ];
    }

    // generate order by condition
    final public function getOrderByCond($defaultOrderBy = "id", $userSelectedOrderBy = "")
    {
        // If user has selected any sort column, sort by that column
        if ($userSelectedOrderBy) {
            $arrDescSortOrder = explode(" DESC", $userSelectedOrderBy);
            if (count($arrDescSortOrder) > 1) {
                return array(
                    "ORDER BY ? DESC",
                    array(trim($arrDescSortOrder[0]))
                );
            }

            $arrAscSortOrder = explode(" ASC", $userSelectedOrderBy);
            if (count($arrAscSortOrder) > 1) {
                return array(
                    "ORDER BY ? ASC",
                    array(trim($arrAscSortOrder[0]))
                );
            }

            return array(
                "ORDER BY ?",
                array(trim($userSelectedOrderBy))
            );
        }

        // Default sort column If user hasn't selected any sort column
        if ($defaultOrderBy) {
            return array(
                "ORDER BY $defaultOrderBy DESC",
                array()
            );
        }
    }

    // get the avg Date time range from time array
    final public function getAverageDatetimeRange(array $timeSpent): array
    {
        $startSum = 0;
        $endSum = 0;
        $count = 0;

        foreach ($timeSpent as $row) {
            $start = $row[0] ?? null;
            $end   = $row[1] ?? null;

            // Only call strtotime if both are non-null and non-empty
            if (!empty($start) && !empty($end)) {
                $startTimestamp = strtotime($start);
                $endTimestamp = strtotime($end);

                // Only use valid timestamps
                if ($startTimestamp !== false && $endTimestamp !== false) {
                    $startSum += $startTimestamp;
                    $endSum += $endTimestamp;
                    $count++;
                }
            }
        }

        $avgStartDatetime = $count > 0 ? date("Y-m-d H:i:s", (int)($startSum / $count)) : null;
        $avgEndDatetime   = $count > 0 ? date("Y-m-d H:i:s", (int)($endSum / $count)) : null;

        return [
            'avgStartDatetime' => $avgStartDatetime,
            'avgEndDatetime'   => $avgEndDatetime
        ];
    }
}
