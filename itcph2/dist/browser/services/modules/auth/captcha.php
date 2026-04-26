<?php

require_once $include_path . "defined_index.php";
require_once $include_path . "class/Captcha.php";

if (!isEmptyString($requestAction)) {
    switch ($requestAction) {
        case $ACTION_LIST['CAPTCHA']:
            $captcha = new GenerateCaptcha(
                $sessionMgmt,
                $VALIDATOR_LENGTH["CAPTCHA_MINLENGTH"],
                $VALIDATOR_LENGTH["CAPTCHA_MAXLENGTH"]
            );
            $captcha->generateCaptcha();
            break;
        default:
            $arrMessage = responseMessage(array($INVALID_ACTION));
            echo json_encode($arrMessage);
    }
} else {
    $arrMessage = responseMessage(array($NO_ACTION_FOUND));
    echo json_encode($arrMessage);
}
