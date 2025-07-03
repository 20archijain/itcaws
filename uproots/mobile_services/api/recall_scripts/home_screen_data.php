<?php

$url = 'https://upimg2.btlmonitor.com/mobile_services/api/home_screen_data.php';

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
$body = file_get_contents('php://input');

// Prepare the headers for cURL
$curlHeaders = ['Host: upimg2.btlmonitor.com'];  // Host header
foreach ($headers as $key => $value) {
    $curlHeaders[] = "$key: $value";
}

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// Handle POST request data if method is POST
if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
} elseif ($method === 'GET') {
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($_GET)); // GET parameters are appended
}

// Set a timeout for the request
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set a 30-second timeout

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
echo $response;  // Return the response to the client
