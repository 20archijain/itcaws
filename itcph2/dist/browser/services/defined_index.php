<?php

if (!isset($I_am_req_always) || $I_am_req_always != "I am req always") {
    $arrMsg = array(
        "status" => 400,
        "hidePopup" => false,
        "message" => array("Page not found."),
        "data" => "",
    );
    echo json_encode($arrMsg);
    die;
}
