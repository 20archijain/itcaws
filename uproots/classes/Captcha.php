<?php

// phpcs:ignore
class GenerateCaptcha
{
    private $response;
    private $session = null;
    private $_fontSize = 20;
    private $_minTextLength;
    private $_maxTextLength;
    private $_includeSpecialChar = true;
    private $_specialCharLength = 1;
    private $_gapBetweenCharacter = 10;
    private $_calculatedCaptchaLength = 0;
    private $_calculatedImageHeight = 0;
    private $_captchaPath;
    private $_captchaUrl;

    public function __construct($session, $response, $minLength, $maxLength)
    {
        $this->session = $session;
        $this->response = $response;
        $this->_minTextLength = is_int($minLength) && $minLength > 0 ? $minLength : 5;
        $this->_maxTextLength = is_int($maxLength) && $maxLength >= $minLength ? $maxLength : 6;
        $this->_captchaPath = $GLOBALS["CAPTCHA_IMG_PATH"];
        $this->_captchaUrl = $GLOBALS["CAPTCHA_IMG_URL"];
    }

    final public function setFontSize($size)
    {
        $this->_fontSize = $size;
    }

    final public function setSpecialChar($isSpecialCharRequired, $specialCharLength = 1)
    {
        $this->_includeSpecialChar = $isSpecialCharRequired;

        // max 2 special chars allowed
        $this->_specialCharLength = $specialCharLength > 2 ? 2 : $specialCharLength;
    }

    final public function generateCaptcha()
    {
        $fonts = array(
            $GLOBALS["CAPTCHA_FONTS_PATH"] . "/monofont.ttf",
            $GLOBALS["CAPTCHA_FONTS_PATH"] . "/segoeprb.ttf",
            $GLOBALS["CAPTCHA_FONTS_PATH"] . "/georgiai.ttf",
        );

        $text = $this->getCaptchaText();
        $image = $this->getCaptchaImage();

        $textcolor = imagecolorallocate($image, 0x00, 0x00, 0x00);

        // store captcha value in session to validate
        $this->session->setSessionKey($GLOBALS["VARIABLES_NAMES"]["SESSION"]["CAPTCHA_VALUE"], $text);

        $arrText = str_split($text);

        $letterPos = $this->_fontSize;
        foreach ($arrText as $text) {
            // Write text to the image using TrueType fonts:
            // ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
            imagettftext($image, $this->_fontSize, rand(-10, 10), $letterPos, floor($this->_calculatedImageHeight / 2) + rand(1, 10), $textcolor, $fonts[array_rand($fonts)], $text);
            $letterPos += $this->_fontSize + $this->_gapBetweenCharacter;
        }

        $captchaFileName = "/" . $this->session->getSessionId() . ".png";
        $captchaUploadPath = $this->_captchaPath . $captchaFileName;
        $captchaDownloadPath = $this->_captchaUrl . $captchaFileName;

        // save image as png
        if ($this->_captchaPath && !file_exists($this->_captchaPath)) {
            @mkdir($this->_captchaPath, 0777, true);
        }
        imagepng($image, $captchaUploadPath);
        imagedestroy($image);

        $this->response->sendResponse(array("response" => $captchaDownloadPath), 1, array(), true);
    }

    private function getCaptchaImage()
    {
        $gapLength = $this->_gapBetweenCharacter * ($this->_calculatedCaptchaLength - 1);
        $imageWidth = ($this->_fontSize * $this->_calculatedCaptchaLength) + ($this->_fontSize * 3) + $gapLength;
        $imageHeight = $this->_fontSize + 30;
        $this->_calculatedImageHeight = $imageHeight;

        // Create a new true color image
        $image = imagecreatetruecolor($imageWidth, $this->_calculatedImageHeight);

        // Allocate a color for an image: ( resource $image , int $red , int $green , int $blue )
        $background = imagecolorallocate($image, 224, 224, 224);
        // Flood fill: ( resource $image , int $x , int $y , int $color )
        imagefill($image, 0, 0, $background);
        $linecolor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));

        // draw random lines on canvas
        for ($i = 0; $i < $this->_calculatedCaptchaLength; $i++) {
            // Set the thickness for line drawing: ( resource $image , int $thickness )
            imagesetthickness($image, rand(1, 2));

            // Draw a line: ( resource $image , int $x1 , int $y1 , int $x2 , int $y2 , int $color )
            imageline($image, rand(0, $imageWidth), 0, rand(0, $imageWidth), $this->_calculatedImageHeight, $linecolor);
        }

        return $image;
    }

    private function getCaptchaText()
    {
        $captchaLength = rand($this->_minTextLength, $this->_maxTextLength);
        $this->_calculatedCaptchaLength = $captchaLength;

        $lowerAlphas = implode("", range("a", "z"));
        $upperAlphas = implode("", range("A", "Z"));
        $numbers = implode("", range(0, 9));
        $specialCharacters = "@#$%";

        $arrCaptchaText = array();

        // get special characters
        if ($this->_includeSpecialChar) {
            $arrCaptchaText[] = $this->getRandomString($specialCharacters, $this->_specialCharLength);
        }

        $remainingCaptchaLength = $captchaLength - $this->_specialCharLength;
        // $length = rand(1, $remainingCaptchaLength);

        // // get lower case letters
        // if ($length > 0) {
        //     $arrCaptchaText[] = $this->getRandomString($lowerAlphas, $length);
        // }

        // $remainingCaptchaLength -= $length;
        // $length = rand(1, $remainingCaptchaLength);

        // // get upper case letters
        // if ($length > 0) {
        //     $arrCaptchaText[] = $this->getRandomString($upperAlphas, $length);
        // }

        // $remainingCaptchaLength -= $length;

        // get numbers
        if ($remainingCaptchaLength > 0) {
            $arrCaptchaText[] = $this->getRandomString($numbers, $remainingCaptchaLength);
        }

        return substr(str_shuffle(implode("", $arrCaptchaText)), 0, $captchaLength);
    }

    private function getRandomString($inputString, $length)
    {
        $arrString = array();

        while ($length > 0) {
            $randomChar = $inputString[mt_rand(0, strlen($inputString) - 1)];

            $arrString[] = $randomChar;
            $length--;
        }

        shuffle($arrString);
        return implode("", $arrString);
    }
}
