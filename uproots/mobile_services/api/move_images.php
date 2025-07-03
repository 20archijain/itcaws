<?php

function sendResponse($status, $message)
{
    header('Content-Type: application/json');
    echo json_encode(array("status" => $status, "message" => $message));
}

function uploadFile($imageLocation, $file)
{
    if (isset($file) && $file && isset($file["name"])) {
        $name = basename($file["name"]);
        if (move_uploaded_file($file["tmp_name"], "$imageLocation/$name")) {
            return 1;
        } else {
            return 0;
        }
    } else {
        return -1;
    }
}

$path = $_POST["path"];
$files = $_FILES;
$imageLocation = "/home3/ockxmzmy/public_html/uproots/prods/any" . $path;
if (!file_exists($imageLocation)) {
    mkdir($imageLocation, 0777, true);
}

if ($files && isset($files["file"])) {
    $iStatus = uploadFile($imageLocation, $files["file"]);
    uploadFile($imageLocation, $files && isset($files["thumb"]) ? $files["thumb"] : null);

    if ($iStatus == 1) {
        sendResponse(200, "Image uploaded");
    } else {
        sendResponse(400, "Image not uploaded");
    }
} else {
    sendResponse(400, "No image found");
}
