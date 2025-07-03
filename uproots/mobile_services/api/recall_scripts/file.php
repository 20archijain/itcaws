<?php

$url = 'https://upimg2.btlmonitor.com/mobile_services/api/file.php';

// Fallback for getallheaders()
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
}

// Forward the request method and input data
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// Prepare headers
$curlHeaders = ['Host: upimg2.btlmonitor.com'];  // Host header
foreach ($headers as $key => $value) {
    $curlHeaders[] = "$key: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

// Handle POST request with file upload
if ($method === 'POST' && isset($_FILES['file'])) {
    $postData = [
        'file' => new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']),
        'uniqId' => $_POST['uniqId'],
        'surveyId' => $_POST['surveyId'],
        'pageId' => $_POST['pageId'],
        'quesId' => $_POST['quesId'],
        'surveyUniqId' => $_POST['surveyUniqId'],
        'lt' => $_POST['lt'],
        'lg' => $_POST['lg'],
        'dt' => $_POST['dt'],
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
} elseif ($method === 'GET') {
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($_GET)); // GET parameters
}

// Set a timeout for the request
curl_setopt($ch, CURLOPT_TIMEOUT, 50);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Error handling
if (curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));  // Log the error to the error log
    $response = json_encode(['error' => 'Internal Server Error']);  // Return a generic error message to the client
    $httpCode = 500;  // Set HTTP status to 500 in case of cURL error
}

// Close cURL
curl_close($ch);

// Output the response
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;
