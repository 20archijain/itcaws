<?php

// Included aws/aws-sdk-php via Composer's autoloader
// $awsAutoloadPath = __DIR__ . '/../../php_libs/aws-sdk-php/vendor/autoload.php';
// if (file_exists($awsAutoloadPath)) {
//     require $awsAutoloadPath;
// }
// require $sIncludePath . '/../php_libs/aws-sdk-php/vendor/autoload.php';
require $AWS_SDK_LIB_PATH;


use Aws\S3\S3Client;

// phpcs:ignore
class AwsRequest
{
    private $arrConfig = array();
    private $activeConfig;
    public $client;

    public function __construct()
    {
        $this->arrConfig = array(
            "digitaloceanspaces" => array(
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
                        "default" => "itcph2-new",
                    ),
                ),
            ),
        );

        $this->activeConfig = $this->arrConfig["digitaloceanspaces"]["config2"];

        $this->client = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $this->activeConfig["region"],
            'endpoint' => $this->activeConfig["region_endpoint"],
            'credentials' => $this->activeConfig["credentials"],
            'use_path_style_endpoint' => true,
        ]);
    }

    public function uploadS3Bucket($dbName, $org_img_path, $org_img_name, $digitalocean_img_path)
    {
        $mainImg = $org_img_path . '/' . $org_img_name;
        $thumbImg = $org_img_path . '/thumb_' . $org_img_name;

        // get bucket name as per DB if set else default bucket name
        $bucketName = isset($this->activeConfig["bucket_names_list"][$dbName]) ?
            $this->activeConfig["bucket_names_list"][$dbName] : null;
        if (!$bucketName) {
            $bucketName = $this->activeConfig["bucket_names_list"]["default"];
        }
        $sImageNewDomain = str_replace("{BUCKET_NAME}", $bucketName, $this->activeConfig["bucket_endpoint"]);
        $isImageMoved = false;

        $mainClientPath = $digitalocean_img_path . '/' . $org_img_name;
        $thumbClientPath = $digitalocean_img_path . '/thumb_' . $org_img_name;
        $mainData = $thumbData = null;

        if (file_exists($mainImg)) {
            try {
                $mainData =  $this->client->putObject([
                    'Bucket' => 'itcph2-new',
                    'Key'    => $mainClientPath,
                    'Body'   => 'The contents of the file.',
                    'ACL'    => 'public-read',
                    'SourceFile' => $mainImg,
                ]);
                $isImageMoved = true;
            } catch (Exception $e) {
                $isImageMoved = false;
                // print_r($e->getMessage());
                // print_r($e->getTraceAsString());
            }
        }

        if (file_exists($thumbImg)) {
            try {
                $thumbData =  $this->client->putObject([
                    'Bucket' => 'itcph2-new',
                    'Key'    => $thumbClientPath,
                    'Body'   => 'The contents of the file.',
                    'ACL'    => 'public-read',
                    'SourceFile' => $thumbImg,
                ]);
            } catch (Exception $e) {
            }
        }

        // system("rm -rf ".escapeshellarg($rmImgDir)); //remove dir with images
        // system("rm -rf " . escapeshellarg($mainImg)); //remove images from dir
        // system("rm -rf " . escapeshellarg($thumbImg)); //remove images thumb from dir
        // if ($mainData) {
        //     unlink($mainImg);
        // }
        // if ($thumbData) {
        //     unlink($thumbImg);
        // }
        return array($isImageMoved, $sImageNewDomain);
    }
}