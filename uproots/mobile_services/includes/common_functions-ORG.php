<?php

// generate response msg to send
function responseMessage($responseData = array(), $status = 0, $logResponse = array(), $customResp = array())
{
    global $commonFunctions;

    // If array
    if ($commonFunctions->isNonEmptyArray($responseData)) {
        $message = isset($responseData["message"]) && $responseData["message"] ? $responseData["message"] : "";
        $response = $responseData["response"] ? $responseData["response"] : null;
    } else {
        $message = $responseData;
        $response = null;
    }

    $statusArr = array(
        0 => constant('FAILED'),
        1 => constant('SUCCESS'),
        2 => constant('WARNING'),
    );

    $arrMsg = array(
        "status" => $statusArr[$status],
        "message" => $message,
        "response" => $response,
    );

    if ($commonFunctions->isNonEmptyArray($customResp)) {
        $arrMsg["custom"] = $customResp;
    }

    $res = json_encode($arrMsg);
    if ($logResponse && $logResponse["log"]) {
        $commonFunctions->debugLog(
            $res,
            isset($logResponse["fileName"]) && $logResponse["fileName"] ? $logResponse["fileName"] : "",
            isset($logResponse["folderName"]) && $logResponse["folderName"] ? $logResponse["folderName"] : "",
            isset($logResponse["directory"]) && $logResponse["directory"] ? $logResponse["directory"] : ""
        );
    }

    header('Content-Type: application/json');
    echo $res;
    return $res;
}

// Get summary from grid data as array
function getSummaryFromGridDataAsArray($arrSalesSummary, $arrData, $arrLabels, $noOfColumns = 1, $noOfRows = 1)
{
    global $commonFunctions;
    if ($commonFunctions->isNonEmptyArray($arrData)) {
        $arrFormattedData = array();
        foreach ($arrData as $data) {
            $arrFormattedData[$data["rowNo"] . "-" . $data["colNo"]] = $data["ans"];
        }
        for ($row = 1; $row <= $noOfRows; $row++) {
            $sales = 0;
            for ($column = 1; $column <= $noOfColumns; $column++) {
                if (isset($arrFormattedData[$row . "-" . $column]) && $arrFormattedData[$row . "-" . $column] > 0) {
                    $sales += $arrFormattedData[$row . "-" . $column];
                }
            }

            $index = array_search($arrLabels[$row - 1], array_column($arrSalesSummary, "label"));

            if ($index === false) {
                $arrSalesSummary[] = array(
                    "label" => $arrLabels[$row - 1],
                    "value" => $sales,
                );
            } else {
                $arrSalesSummary[$index]["value"] += $sales;
            }
        }
    }

    return $arrSalesSummary;
}

// get no days between 2 dates excluding particular week day like sunday,
// if dates are not provided, get no days in current month excluding particular week day
function getCountOfDaysExcluding($dayToExclude = "", $startDate = null, $endDate = null)
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

// Get Grid data as array
function getGridDataAsArray(
    $arrData,
    $noOfColumns = 1,
    $noOfRows = 1,
    $useZeroIfNotFound = false,
    $setArrayIfInvalid = false
) {
    global $commonFunctions;
    $arrValues = array();
    if ($commonFunctions->isNonEmptyArray($arrData) || (!$arrData && $setArrayIfInvalid)) {
        $arrFormattedData = array();
        if ($commonFunctions->isNonEmptyArray($arrData)) {
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

// define getallheaders() to get all the headers if not defined
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }
}
