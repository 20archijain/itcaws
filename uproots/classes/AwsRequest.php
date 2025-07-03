<?php

// Included aws/aws-sdk-php via Composer's autoloader
require $AWS_SDK_LIB_PATH;

use Aws\S3\S3Client;

// phpcs:ignore
class AwsRequest
{
    private $arrConfig = array();
    private $activeConfig;
    private $logFilename = "debug_AwsRequest_connect_error_log";
    private $commonFunctions;
    public $client;

    public function __construct($commonFunctions)
    {
        $this->commonFunctions = $commonFunctions;
        $this->arrConfig = array(
            "digitaloceanspaces" => array(
                "config1" => array(
                    "region" => "sgp1",
                    "region_endpoint" => "https://sgp1.digitaloceanspaces.com",
                    // bataitraining@gmail.com => AppiLary@C406
                    "credentials" => array(
                        "key"    => "3VOYR42PWU644TFZFNPD",
                        "secret" => "ClwFfWLi4h8EmLgvm/2pZB1ratSHtW3FDlvf21E/rDE",
                    ),
                    "bucket_endpoint" => "https://{BUCKET_NAME}.sgp1.digitaloceanspaces.com",
                    "bucket_names_list" => array(
                        "default" => "itccampaign",
                    ),
                ),
                "config2" => array(
                    "region" => "sgp1",
                    "region_endpoint" => "https://sgp1.digitaloceanspaces.com",
                    // meavnish@gmail.com => DigiAppiLary@20225
                    "credentials" => array(
                        "key"    => "DO00R4HJYZJ476V9BM3R",
                        "secret" => "UezloG6mp813/TkzSKmH1J9S0k0CgQzk8ZVZAlbbSHk",
                    ),
                    "bucket_endpoint" => "https://{BUCKET_NAME}.sgp1.digitaloceanspaces.com",
                    "bucket_names_list" => array(
                        "default" => "itccampaign-new",
                        $GLOBALS["IMPACT_DB"] => "impact-new",
                        $GLOBALS["NOVICEMARCOM_DB"] => "novicemarcom-new",
                        $GLOBALS["WONDER_DB"] => "wonder-new",
                        $GLOBALS["ZX_DB"] => "zx-new",
                        $GLOBALS["DELHI_DB"] => "delhi-new",
                        $GLOBALS["ITC_DB"] => "itc-new",
                        $GLOBALS["ITCNEW_DB"] => "itcnew-new",
                        $GLOBALS["ITCPH2_DB"] => "itcph2-new",
                        $GLOBALS["JAIPUR_DB"] => "jaipur-new",
                        $GLOBALS["SNPL_DB"] => "snpl-new",
                        $GLOBALS["SOUTH_DB"] => "south-new",
                    ),
                ),
            ),
            "backblazeb2" => array(
                "config1" => array(
                    "region" => "us-west-002",
                    "region_endpoint" => "https://s3.us-west-002.backblazeb2.com",
                    // bataitraining@gmail.com => AppiLary@C406
                    "credentials" => array(
                        "key"    => "00258881b3e6f390000000006",
                        "secret" => "K002qe75xfPmG365Wn8HmzpnkITW4Z8",
                    ),
                    "bucket_endpoint" => "https://{BUCKET_NAME}.s3.us-west-002.backblazeb2.com",
                    "bucket_names_list" => array(
                        "default" => "itccampaign",
                    ),
                ),
            ),
        );
    }

    public function connectDigitalOceanSpaces($configName = "config2")
    {
        try {
            $this->activeConfig = $this->arrConfig["digitaloceanspaces"][$configName];

            $this->client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => $this->activeConfig["region"],
                'endpoint' => $this->activeConfig["region_endpoint"],
                'credentials' => $this->activeConfig["credentials"],
            ]);
        } catch (Exception $e) {
            $currentDateTime = $this->commonFunctions->currentDateTime();
            $this->commonFunctions->debugLog(
                "\r\nTimestamp: $currentDateTime connectDigitalOceanSpaces failed" .
                    "\r\nMessage: " . $e->getMessage() . "\r\nTrace: " . $e->getTraceAsString(),
                $this->logFilename
            );
        }
    }

    public function connectBackblazeBuckets($configName = "config1")
    {
        try {
            $this->activeConfig = $this->arrConfig["backblazeb2"][$configName];

            $this->client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => $this->activeConfig["region"],
                'endpoint' => $this->activeConfig["region_endpoint"],
                'credentials' => $this->activeConfig["credentials"],
            ]);
        } catch (Exception $e) {
            $currentDateTime = $this->commonFunctions->currentDateTime();
            $this->commonFunctions->debugLog(
                "\r\nTimestamp: $currentDateTime connectBackblazeBuckets failed" .
                    "\r\nMessage: " . $e->getMessage() . "\r\nTrace: " . $e->getTraceAsString(),
                $this->logFilename
            );
        }
    }

    public function uploadS3Bucket(
        $serverName,
        $dbName,
        $org_img_path,
        $org_img_name,
        $anotherserver_img_path,
        $deleteOrgImage = true
    ) {
        // get bucket name as per DB if set else default bucket name
        $bucketName = isset($this->activeConfig["bucket_names_list"][$dbName]) ?
            $this->activeConfig["bucket_names_list"][$dbName] : null;
        if (!$bucketName) {
            $bucketName = $this->activeConfig["bucket_names_list"]["default"];
        }
        $sImageNewDomain = str_replace("{BUCKET_NAME}", $bucketName, $this->activeConfig["bucket_endpoint"]);

        $isImageMoved = false;
        $mainImg = $org_img_path . '/' . $org_img_name;
        $thumbImg = $org_img_path . '/thumb_' . $org_img_name;

        $mainClientPath = $anotherserver_img_path . '/' . $org_img_name;
        $thumbClientPath = $anotherserver_img_path . '/thumb_' . $org_img_name;
        $mainData = $thumbData = null;

        if ($this->client && file_exists($mainImg)) {
            try {
                $mainData =  $this->client->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $mainClientPath,
                    'Body'   => 'The contents of the file.',
                    'ACL'    => 'public-read',
                    'SourceFile' => $mainImg,
                ]);
                $isImageMoved = true;
            } catch (Exception $e) {
                $currentDateTime = $this->commonFunctions->currentDateTime();
                $this->commonFunctions->debugLog(
                    "\r\nTimestamp: $currentDateTime\r\nFull Image not moved on $serverName under bucket $bucketName" .
                        "\r\nActual Image Path: $org_img_path\r\nImage Name: $org_img_name\r\nAnother server Image Path: $anotherserver_img_path" .
                        "\r\nMessage: " . $e->getMessage() . "\r\nTrace: " . $e->getTraceAsString(),
                    $this->logFilename
                );
                $isImageMoved = false;
            }
        }
        if ($deleteOrgImage && file_exists($mainImg) && $mainData) {
            unlink($mainImg);
        }

        if ($this->client && file_exists($thumbImg)) {
            try {
                $thumbData =  $this->client->putObject([
                    'Bucket' => $bucketName,
                    'Key'    => $thumbClientPath,
                    'Body'   => 'The contents of the file.',
                    'ACL'    => 'public-read',
                    'SourceFile' => $thumbImg,
                ]);
            } catch (Exception $e) {
                $currentDateTime = $this->commonFunctions->currentDateTime();
                $this->commonFunctions->debugLog(
                    "\r\nTimestamp: $currentDateTime\r\nThumbnail Image not moved on $serverName under bucket $bucketName" .
                        "\r\nActual Image Path: $org_img_path\r\nImage Name: $org_img_name\r\nAnother server Image Path: $anotherserver_img_path" .
                        "\r\nMessage: " . $e->getMessage() . "\r\nTrace: " . $e->getTraceAsString(),
                    $this->logFilename
                );
            }
        }
        if ($deleteOrgImage && file_exists($thumbImg) && $thumbData) {
            unlink($thumbImg);
        }

        return array($isImageMoved, $sImageNewDomain);
    }
}
