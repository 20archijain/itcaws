<?php

// phpcs:ignore
class Response
{
    private $toCache = false;
    private $sendWebApiResponse;
    private $statusArr = array(
        0 => 400,
        1 => 200,
        2 => 300,
    );

    public function __construct($sendWebApiResponse = false)
    {
        $this->sendWebApiResponse = $sendWebApiResponse;
    }

    public function toCache($toCache)
    {
        $this->toCache = $toCache;
    }

    // generate response msg to send web App API
    private function webResponseMessage($responseData = array(), $status = 0, $hidePopup = false)
    {
        $message = $responseData && isset($responseData["message"]) && $responseData["message"] ?
            (is_array($responseData["message"]) ? $responseData["message"] : array($responseData["message"])) : array();
        $data = $responseData && isset($responseData["response"]) && $responseData["response"] ?
            $responseData["response"] : null;

        $arrMsg = array(
            "status" => $this->statusArr[$status],
            "message" => $message,
            "data" => $data,
            "hidePopup" => $hidePopup,
        );

        header('Content-Type: application/json');
        $res = json_encode($arrMsg);
        echo $res;
        return $res;
    }

    // generate response msg to send app App API
    private function appResponseMessage($responseData = array(), $status = 0, $customResp = array())
    {
        $message = $responseData && isset($responseData["message"]) && $responseData["message"] ?
            $responseData["message"] : "";
        $response = $responseData && isset($responseData["response"]) && $responseData["response"] ?
            $responseData["response"] : null;

        $arrMsg = array(
            "status" => $this->statusArr[$status],
            "message" => $message,
            "response" => $response,
        );

        if ($customResp) {
            $arrMsg["custom"] = $customResp;
        }

        header('Content-Type: application/json');
        $res = json_encode($arrMsg);
        echo $res;
        return $res;
    }

    public function sendResponse($responseData, $status = 0, $customResp = array(), $hidePopup = false)
    {
        // Cache Response
        if ($this->toCache) {
            // Cache the response for 60 seconds,
            // i.e if user tries to request again within 60 seconds of previous request, cache response will be returned
            header('Cache-Control: max-age=60');
        } else {
            // Don't cache and don't store
            // no-cache means revalidate with server before using any cached response you may have, on every request.
            // no-store is used to not store the response in any cache
            header('Cache-Control: no-cache, no-store');
        }

        // Send response for web API requests
        if ($this->sendWebApiResponse) {
            return $this->webResponseMessage($responseData, $status, $hidePopup);
        } else {
            // Send response for app API requests
            return $this->appResponseMessage($responseData, $status, $customResp);
        }
    }
}
