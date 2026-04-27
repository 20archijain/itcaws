<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = '../';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'class/DBConnection.php';

// phpcs:ignore
class VanDswhatsAppSummary
{
    private $_dbConn = null;
    private $_tables = [];
    private $_productSaleVolumeFormula = "";
    private $_resolvedSansFont = null;

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
        $this->_productSaleVolumeFormula = $this->buildProductSaleVolumeFormula();
    }

    public function sendTeamSummary()
    {
        $debugStep = "start";
        try {
            $currentDate = currentDate();
            // $currentDate = "2026-02-23"; // For testing
            // $sectionCond = " AND b.section = 'JPU002'"; // For testing
            $this->clearOldImageDateFolders($currentDate);
            $monthStartDate = date("Y-m-01", strtotime($currentDate));
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $constantsTable = $this->_tables["CONSTANTS_TABLE"];

            $minTotalShops = (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minTotalShops'");
            $minQualifiedAttendanceTimeInMin = (int) getRowColumn($this->_dbConn, $constantsTable, "con_value", "con_name = 'minWorkingTimeInMin'");
            $minQualifiedAttendanceTimeInSec = $minQualifiedAttendanceTimeInMin * 60;

            $sAction = null;
            $iRows = 0;
            // Reset flags for previous dates so current-date batching can run cleanly.
            $debugStep = "reset_summary_sent";
            $sAction10 = null;
            $iRows10 = 0;
            $resetQuery = "UPDATE $projectTeamTable SET summary_sent = 0 WHERE dstatus = 0 AND is_type IN (0,2,5)  AND (summary_sent_date != '$currentDate' OR summary_sent_date IS NULL)";
            $this->_dbConn->ExecuteSelectQuery($resetQuery, $sAction10, $iRows10);

            $debugStep = "fetch_sections";
            $sQuery = "SELECT b.section, ae_name AS ae_name, ae_number AS ae_number, wd_code AS wd_code " .
            "FROM $projectTeamTable AS b WHERE b.dstatus = 0 AND b.s_id = 99 AND b.section IS NOT NULL AND b.section != '' AND b.ae_number IS NOT NULL AND b.ae_number != '' AND b.is_type IN (0,2,5) " .
            "AND COALESCE(b.summary_sent, 0) = 0 GROUP BY b.section ORDER BY b.section LIMIT 15";
            $this->_dbConn->ExecuteSelectQuery($sQuery, $sAction, $iRows);

            $createdImages = array();
            $processedSections = array();
            if ($iRows > 0) {
            while ($row = $this->_dbConn->GetData($sAction)) {
                $aeName = $row["ae_name"];
                $phoneNumber = $row["ae_number"];
                // $phoneNumber = '6397329039'; // For Testing
                $wdCode = $row["wd_code"];
                $section = $row["section"];
                $aeCondition = "dstatus = 0 AND s_id = 99 AND ae_number = '$phoneNumber' AND section = '$section' AND is_type IN (0,2,5)";
                $totalSalesmen = (int) getRowColumn($this->_dbConn, $projectTeamTable, "COUNT(team_id)", $aeCondition);

                $dailyRows = $this->getAeSummaryRows($phoneNumber, $section, $currentDate, $currentDate);
                $mtdRows = $this->getAeSummaryRows($phoneNumber, $section, $monthStartDate, $currentDate);

                $teamDetails = $this->getSectionTeamDetails($phoneNumber, $section);
                $sectionTypeFlags = $this->getSectionTypeFlags($phoneNumber, $section);
                $typeBifurcation = $this->getSectionTypeBifurcationCounts($phoneNumber, $section);
                $teamStrength = $this->getTeamStrengthData($phoneNumber, $section, $dailyRows, $totalSalesmen, $minTotalShops, $minQualifiedAttendanceTimeInSec);
                $npsrToday = $this->getSnapshotTodayData($dailyRows, 5, 2, $teamDetails);
                $npsrMtd = $this->getSnapshotMtdData($mtdRows, 5, $currentDate, $phoneNumber, $section);
                $vanDsToday = $this->getSnapshotTodayData($dailyRows, 0, 20, $teamDetails);
                $vanDsMtd = $this->getSnapshotMtdData($mtdRows, 0, $currentDate, $phoneNumber, $section);
                if (!empty($phoneNumber)) {
                    $imagePath = $this->createAeSummaryImage(
                        $currentDate,
                        $aeName,
                        $wdCode,
                        $section,
                        $teamStrength,
                        $npsrToday,
                        $npsrMtd,
                        $vanDsToday,
                        $vanDsMtd,
                        $sectionTypeFlags,
                        $typeBifurcation
                    );
                    if ($imagePath) {
                        $imageUrl = $this->buildPublicImageUrl($imagePath);
                        $whatsAppResponse = $this->sendWhatsAppMessage('91' . $phoneNumber, $imageUrl, $aeName, 'vnsai');
                        $processedSections[$section] = true;
                        $createdImages[] = array(
                            "ae_name" => $aeName,
                            "ae_number" => $phoneNumber,
                            "section" => $section,
                            "image_path" => $imagePath,
                            "image_url" => $imageUrl,
                            "whatsapp_response" => $whatsAppResponse
                        );
                    }
                }
            }
            }

            if (count($processedSections) > 0) {
            $sectionList = array();
            foreach (array_keys($processedSections) as $sentSection) {
                $sectionList[] = "'" . addslashes($sentSection) . "'";
            }
            $sectionInClause = implode(",", $sectionList);
            $debugStep = "mark_sent_sections";
            $sAction5 = null;
            $iRows = 0;
            $markSentQuery = "UPDATE $projectTeamTable SET summary_sent = 1, summary_sent_date = '$currentDate' WHERE dstatus = 0 AND s_id = 99 AND section IN ($sectionInClause)";
            $this->_dbConn->ExecuteSelectQuery($markSentQuery, $sAction5, $iRows);
            }

            // If nothing is pending now, remove all generated images for this date.
            $debugStep = "pending_count_check";
            $pendingCount = (int) getRowColumn(
            $this->_dbConn,
            $projectTeamTable,
            "COUNT(DISTINCT section)",
            "dstatus = 0 AND s_id = 99 AND section IS NOT NULL AND section != '' " .
                "AND (COALESCE(summary_sent, 0) = 0 OR summary_sent_date IS NULL OR summary_sent_date != '$currentDate')"
            );
            if ($pendingCount === 0) {
                $this->clearGeneratedImagesForDate($currentDate);
            }

            echo json_encode(array(
            "status" => 1,
            "capture_date" => $currentDate,
            "total_images" => count($createdImages),
            "images" => $createdImages
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => 400,
                "message" => array($e->getMessage()),
                "debug_step" => $debugStep,
                "data" => "",
                "hidePopup" => false
            ));
        }
    }

    private function clearGeneratedImagesForDate($currentDate)
    {
        $targetDir = $GLOBALS["CUST_FOLDER_PATH"] . "/ae_summary_images/" . $currentDate;
        if (!is_dir($targetDir)) {
            return;
        }
        $files = @glob($targetDir . "/*.png");
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $filePath) {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        $remaining = @glob($targetDir . "/*");
        if (is_array($remaining) && count($remaining) === 0) {
            @rmdir($targetDir);
        }
    }

    private function buildPublicImageUrl($imagePath)
    {
        $path = str_replace("\\", "/", (string) $imagePath);
        $basePath = isset($GLOBALS["CUST_FOLDER_PATH"]) ? str_replace("\\", "/", (string) $GLOBALS["CUST_FOLDER_PATH"]) : "";
        $baseUrl = isset($GLOBALS["CUST_FOLDER_URL"]) ? rtrim((string) $GLOBALS["CUST_FOLDER_URL"], "/") : "";
        if ($basePath !== "" && $baseUrl !== "" && stripos($path, $basePath) === 0) {
            $relative = ltrim(substr($path, strlen($basePath)), "/");
            return $baseUrl . "/" . $relative;
        }
        return $path;
    }

    // Send WhatsApp PDF
    private function sendWhatsAppMessage($phoneNumber, $FilePath, $team_name, $apiType = 'vnsai')
    {
        if ($apiType === 'wab') {
            // WAB API Configuration
            $apiUrl = 'https://api.wab.ai/whatsapp-api/v1.0/customer/95755/bot/b9b57bd0131f43fb/template';
            $authorizationToken = '6f088510-93ef-41a2-b9d4-cf7570fbcbe1-Hswi54Q';
            $namespace = 'a7a46341_4176_4bd7_b74f_bf560970e605';

            $qrImageLink = $FilePath;
            $payload = [
                'payload' => [
                    'name' => 'qr_utility',
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => [
                                        'link' => $qrImageLink
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $team_name
                                ]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'quick_reply',
                            'index' => 0,
                            'parameters' => [
                                [
                                    'type' => 'payload',
                                    'payload' => 'flow_3A4EBA3C81F543A7BD3F25C680C68A10'
                                ]
                            ]
                        ]
                    ],
                    'language' => [
                        'code' => 'en_US',
                        'policy' => 'deterministic'
                    ],
                    'namespace' => $namespace
                ],
                'phoneNumber' => $phoneNumber
            ];

            $headers = [
                'Authorization: Basic ' . $authorizationToken,
                'Content-Type: application/json'
            ];
            $postFields = json_encode($payload);
        } elseif ($apiType === 'vnsai') {
            // VNSAI API Configuration
            $apiUrl = 'https://api.vnsai.com/WAApi/send';
            $headers = ['Cookie: SERVERID=webC1'];
            $postFields = [
                'userid'  => 'Appilary',
                'password'  => 'Uyf6wtH0',
                'wabaNumber' => '919289854142',
                'output' => 'json',
                'mobile' => $phoneNumber,
                'sendMethod' => 'quick',
                'msgType' => 'Media',
                'templateName' => 'radar20',
                'msg' => "Dear Sir,

Below are the Team Summary of your Section.",
                'mediaType' => 'Image',
                'mediaUrl' => $FilePath
            ];
        } else {
            throw new InvalidArgumentException('Invalid API type provided.');
        }

        // Convert data to query string
        $postData = http_build_query($postFields);

        // Create stream context
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Cookie: SERVERID=webC1\r\n",
                'method'  => 'POST',
                'content' => $postData,
                'timeout' => 30
            ]
        ];

        $context = stream_context_create($options);

        // Send request
        $response = file_get_contents($apiUrl, false, $context);

        if ($response === FALSE) {
            return "Error sending request";
        }
        echo "WhatsApp API Response: " . $response; // Log the response for debugging

        return json_decode($response, true);

        // Initialize cURL
        // $ch = curl_init($apiUrl);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        // // Execute the cURL request
        // $response = curl_exec($ch);

        // // Check for errors
        // $success = false;
        // if (curl_errno($ch)) {
        //     error_log('WhatsApp API Error: ' . curl_error($ch));
        // } else {
        //     $success = true;
        // }

        // curl_close($ch);
        // return $success;
    }

    private function clearOldImageDateFolders($currentDate)
    {
        $baseDir = $GLOBALS["CUST_FOLDER_PATH"] . "/ae_summary_images";
        if (!is_dir($baseDir)) {
            return;
        }
        $entries = @glob($baseDir . "/*");
        if (!is_array($entries)) {
            return;
        }
        foreach ($entries as $entryPath) {
            if (!is_dir($entryPath)) {
                continue;
            }
            $entryName = basename($entryPath);
            if ($entryName === $currentDate) {
                continue;
            }
            $this->deleteDirectoryRecursive($entryPath);
        }
    }

    private function deleteDirectoryRecursive($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }
        $items = @glob($dirPath . "/*");
        if (is_array($items)) {
            foreach ($items as $itemPath) {
                if (is_dir($itemPath)) {
                    $this->deleteDirectoryRecursive($itemPath);
                } elseif (is_file($itemPath)) {
                    @unlink($itemPath);
                }
            }
        }
        @rmdir($dirPath);
    }

    private function createAeSummaryImage($currentDate, $aeName, $wdCode, $section, $teamStrength, $npsrToday, $npsrMtd, $vanDsToday, $vanDsMtd, $sectionTypeFlags = array(), $typeBifurcation = array())
    {
        if (!function_exists('imagecreatetruecolor')) {
            return "";
        }

        $targetDir = $GLOBALS["CUST_FOLDER_PATH"] . "/ae_summary_images/" . $currentDate;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $fileName = $this->sanitizeFileName($aeName . "_" . $wdCode . "_" . $section) . ".png";
        $absolutePath = $targetDir . "/" . $fileName;

        $hasNpsr = isset($sectionTypeFlags["has_npsr"]) ? (bool) $sectionTypeFlags["has_npsr"] : true;
        $hasVan = isset($sectionTypeFlags["has_van"]) ? (bool) $sectionTypeFlags["has_van"] : true;
        if (!$hasNpsr && !$hasVan) {
            $hasNpsr = true;
            $hasVan = true;
        }

        $canvasW = 1080;
        $contentBottom = 538;
        if ($hasNpsr) {
            $contentBottom += 516; // NPSR today panel + gap
            $contentBottom += 316; // NPSR MTD panel + gap
        }
        if ($hasVan) {
            $contentBottom += 496; // VAN today panel + gap
            $contentBottom += 216; // VAN MTD panel + gap
        }
        $canvasH = max(980, $contentBottom + 90);
        $img = imagecreatetruecolor($canvasW, $canvasH);
        imageantialias($img, true);
        $bg = imagecolorallocate($img, 246, 249, 252);
        imagefill($img, 0, 0, $bg);

        $blue = imagecolorallocate($img, 10, 65, 140);
        $green = imagecolorallocate($img, 26, 150, 74);
        $orange = imagecolorallocate($img, 226, 122, 33);
        $red = imagecolorallocate($img, 206, 53, 57);
        $purple = imagecolorallocate($img, 95, 53, 153);
        $dark = imagecolorallocate($img, 34, 49, 63);
        $white = imagecolorallocate($img, 255, 255, 255);
        $line = imagecolorallocate($img, 214, 225, 239);
        $muted = imagecolorallocate($img, 94, 113, 136);

        // Header
        imagefilledrectangle($img, 12, 12, $canvasW - 12, 100, $blue);
        $this->drawCenterText($img, 12, 20, $canvasW - 12, 94, "TEAM SUMMARY", 5, $white);
        $this->drawHeaderMeta($img, $aeName, $section, $currentDate, $dark, $typeBifurcation);

        // Team strength cards in mobile-friendly 2x2 grid
        $vanCount = isset($typeBifurcation["van_ds_count"]) ? (int) $typeBifurcation["van_ds_count"] : 0;
        $npsrCount = isset($typeBifurcation["npsr_count"]) ? (int) $typeBifurcation["npsr_count"] : 0;
        $totalSalesmenValue = "Total " . (string) $teamStrength["total"] . " | VAN DS: " . $vanCount . " | NPSR: " . $npsrCount;
        $this->drawStrengthCard($img, 16, 150, 514, 120, "SALESMEN", $totalSalesmenValue, $blue, "T", "");
        $this->drawStrengthCard($img, 550, 150, 514, 120, "QUALIFIED TODAY", (string) $teamStrength["qualified"], $green, "Q", "");
        $this->drawStrengthCard($img, 16, 284, 514, 230, "UNQUALIFIED", (string) $teamStrength["unqualified"], $orange, "U", $teamStrength["unqualified_names"]);
        $this->drawStrengthCard($img, 550, 284, 514, 230, "ABSENT", (string) $teamStrength["absent"], $red, "A", $teamStrength["absent_names"]);

        $cursorY = 522;

        // NPSR blocks (only if section has NPSR)
        $npsrTodayRows = array(
            array("Average Outlets Visited", $this->formatCompactNumber($npsrToday["avg_outlets_visited"]), $blue),
            array("Average Outlets Billed", $this->formatCompactNumber($npsrToday["avg_outlets_billed"]), $blue),
            array("Average Strike Rate", $npsrToday["avg_strike_rate"], $blue),
            array("Lowest Strike Rate", $npsrToday["lowest_strike_rate"], $orange, $npsrToday["lowest_strike_team"]),
            array("Total Infra Volume", $npsrToday["infra_volume"], $purple),
            array("Infra Below 2 Ms", $npsrToday["infra_below_limit"], $red, $npsrToday["infra_below_limit_names"]),
            array("Average Line Cut", $this->formatCompactNumber($npsrToday["avg_line_cut"]), $green),
            array("Average Time Spent", $npsrToday["avg_time_spent"], $blue),
            array("Below 6 Hours", $npsrToday["below_6_hours"], $red, $npsrToday["below_6_hours_names"])
        );
        if ($hasNpsr) {
            $this->drawMetricsPanel($img, 16, $cursorY, 1048, 500, "NPSR SNAPSHOT (TODAY)", $blue, $npsrTodayRows, $line, $dark, "N");
            $cursorY += 508;
        }

        // NPSR MTD
        $npsrMtdRows = array(
            array("Average Daily Outlets Billed", $this->formatCompactNumber($npsrMtd["avg_daily_outlets_billed"]), $green),
            array("Average Infra Volume", $npsrMtd["avg_daily_volume"], $green),
            array($npsrMtd["incentive_brand_1_label"], $npsrMtd["incentive_brand_1"], $purple),
            array($npsrMtd["incentive_brand_2_label"], $npsrMtd["incentive_brand_2"], $orange)
        );
        if ($hasNpsr) {
            $this->drawMetricsPanel($img, 16, $cursorY, 1048, 300, "NPSR SNAPSHOT (MONTH TILL DATE)", $green, $npsrMtdRows, $line, $dark, "N");
            $cursorY += 308;
        }

        // VAN Today
        $vanTodayRows = array(
            array("Average Outlets Visited", $this->formatCompactNumber($vanDsToday["avg_outlets_visited"]), $blue),
            array("Average Outlets Billed", $this->formatCompactNumber($vanDsToday["avg_outlets_billed"]), $blue),
            array("Average Strike Rate", $vanDsToday["avg_strike_rate"], $blue),
            array("Total Infra Volume", $vanDsToday["infra_volume"], $purple),
            array("Infra Below 20 Ms", $vanDsToday["infra_below_limit"], $red, $vanDsToday["infra_below_limit_names"]),
            array("Average Line Cut", $this->formatCompactNumber($vanDsToday["avg_line_cut"]), $green),
            array("Average Time Spent", $vanDsToday["avg_time_spent"], $blue),
            array("Below 6 Hours", $vanDsToday["below_6_hours"], $red, $vanDsToday["below_6_hours_names"])
        );
        if ($hasVan) {
            $this->drawMetricsPanel($img, 16, $cursorY, 1048, 480, "VAN DS SNAPSHOT (TODAY)", $blue, $vanTodayRows, $line, $dark, "V");
            $cursorY += 488;
        }

        // VAN MTD
        $vanMtdRows = array(
            array("Average Daily Outlets Billed", $this->formatCompactNumber($vanDsMtd["avg_daily_outlets_billed"]), $purple),
            array("Average Daily Volume", $vanDsMtd["avg_daily_volume"], $purple)
        );
        if ($hasVan) {
            $this->drawMetricsPanel($img, 16, $cursorY, 1048, 200, "VAN DS SNAPSHOT (MONTH TILL DATE)", $purple, $vanMtdRows, $line, $dark, "V");
            $cursorY += 208;
        }

        // Footer insight strip
        $footerY1 = $cursorY + 10;
        $footerY2 = $footerY1 + 56;
        imagefilledrectangle($img, 16, $footerY1, $canvasW - 16, $footerY2, $white);
        imagerectangle($img, 16, $footerY1, $canvasW - 16, $footerY2, $line);
        $npsrStrike = isset($npsrToday["avg_strike_rate"]) ? trim((string) $npsrToday["avg_strike_rate"]) : "";
        $vanStrike = isset($vanDsToday["avg_strike_rate"]) ? trim((string) $vanDsToday["avg_strike_rate"]) : "";
        if ($hasNpsr && $npsrStrike !== "" && strtoupper($npsrStrike) !== "NA") {
            $strikeText = $npsrStrike;
        } elseif ($hasVan && $vanStrike !== "" && strtoupper($vanStrike) !== "NA") {
            $strikeText = $vanStrike;
        } else {
            $strikeText = "NA";
        }
        $npsrInfraSum = $hasNpsr && isset($npsrToday["infra_volume_sum"]) ? (float) $npsrToday["infra_volume_sum"] : 0;
        $vanInfraSum = $hasVan && isset($vanDsToday["infra_volume_sum"]) ? (float) $vanDsToday["infra_volume_sum"] : 0;
        $npsrInfraCount = $hasNpsr && isset($npsrToday["infra_volume_count"]) ? (int) $npsrToday["infra_volume_count"] : 0;
        $vanInfraCount = $hasVan && isset($vanDsToday["infra_volume_count"]) ? (int) $vanDsToday["infra_volume_count"] : 0;
        $infraCount = $npsrInfraCount + $vanInfraCount;
        if ($infraCount > 0) {
            $infraText = (string) round(($npsrInfraSum + $vanInfraSum) / $infraCount) . " Ms";
        } else {
            $infraText = "NA";
        }
        $timeText = $hasVan ? $vanDsToday["avg_time_spent"] : "NA";
        $belowText = $hasVan ? $vanDsToday["infra_below_limit"] : "NA";
        $insightText = "Overall Strike: " . $strikeText .
            "  |  Avg Infra Volume: " . $infraText .
            "  |  Avg Time: " . $timeText .
            "  |  Infra Below Target: " . $belowText;
        $this->drawCenterText($img, 24, $footerY1 + 4, $canvasW - 24, $footerY2 - 4, $insightText, 4, $muted);

        imagepng($img, $absolutePath);
        imagedestroy($img);
        return $absolutePath;
    }

    private function drawHeaderMeta($img, $aeName, $section, $currentDate, $color, $typeBifurcation = array())
    {
        $aeNameText = isset($aeName) ? strtoupper(trim((string) $aeName)) : "";
        $sectionText = isset($section) ? strtoupper(trim((string) $section)) : "";
        $meta = "AE: " . $aeNameText . " | SEC: " . $sectionText . " | DATE: " . (string) $currentDate;
        $this->drawCenterText($img, 12, 102, 1068, 140, $meta, 4, $color);
    }

    private function drawStrengthCard($img, $x, $y, $w, $h, $label, $value, $valueColor, $iconText, $note = "")
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        $line = imagecolorallocate($img, 214, 225, 239);
        $dark = imagecolorallocate($img, 34, 49, 63);
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $white);
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $line);
        $iconCx = $x + 38;
        $iconCy = $y + 34;
        $this->drawIconBadge($img, $iconCx, $iconCy, $valueColor, $iconText, null, 14);
        $this->drawCenterText($img, $x + 12, $y + 20, $x + $w - 12, $y + 64, $label, 4, $dark);
        $valueFont = strlen((string) $value) > 16 ? 4 : 5;
        $this->drawCenterText($img, $x + 12, $y + 62, $x + $w - 12, $y + 110, $value, $valueFont, $valueColor);
        if ($note !== "") {
            $this->drawWrappedTextInBox($img, $x + 12, $y + 102, $x + $w - 12, $y + $h - 8, $note, 5, $dark, 1);
        }
    }

    private function drawWrappedTextInBox($img, $x1, $y1, $x2, $y2, $text, $font, $color, $lineSpacing = 1)
    {
        $text = trim((string) $text);
        if ($text === "") {
            return;
        }
        $maxWidth = max(20, $x2 - $x1);
        $lines = array();
        $paragraphs = preg_split("/\\r\\n|\\n|\\r/", $text);
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === "") {
                $lines[] = "";
                continue;
            }
            $words = preg_split('/\s+/', $paragraph);
            $current = "";
            foreach ($words as $word) {
                $candidate = $current === "" ? $word : ($current . " " . $word);
                $candidateWidth = $this->measureTextWidth($candidate, $font);
                if ($candidateWidth <= $maxWidth) {
                    $current = $candidate;
                } else {
                    if ($current !== "") {
                        $lines[] = $current;
                    }
                    $current = $word;
                }
            }
            if ($current !== "") {
                $lines[] = $current;
            }
        }

        $lineHeight = $this->getLineHeightForFont($font) + $lineSpacing;
        $maxLines = max(1, (int) floor(($y2 - $y1) / max(1, $lineHeight)));
        if (count($lines) > $maxLines && $font > 2) {
            $this->drawWrappedTextInBox($img, $x1, $y1, $x2, $y2, $text, $font - 1, $color, $lineSpacing);
            return;
        }
        $lines = array_slice($lines, 0, $maxLines);
        if (count($lines) > 0 && count($lines) >= $maxLines) {
            $lastIdx = count($lines) - 1;
            $last = $lines[$lastIdx];
            while ($last !== "" && $this->measureTextWidth($last, $font) > $maxWidth) {
                $last = substr($last, 0, -1);
            }
            $lines[$lastIdx] = rtrim($last);
        }
        foreach ($lines as $i => $line) {
            $y = $y1 + ($i * $lineHeight);
            $this->drawTextInBox($img, $x1, $y, $x2, $y + $lineHeight, $line, $font, $color, false);
        }
    }

    private function drawMetricsPanel($img, $x, $y, $w, $h, $title, $titleColor, $rows, $line, $dark, $panelIconText)
    {
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $white);
        imagerectangle($img, $x, $y, $x + $w, $y + $h, $line);
        imagefilledrectangle($img, $x, $y, $x + $w, $y + 50, $titleColor);
        $this->drawIconBadge($img, $x + 30, $y + 29, imagecolorallocate($img, 255, 255, 255), $panelIconText, $titleColor, 14);
        $this->drawCenterText($img, $x + 60, $y + 6, $x + $w - 14, $y + 46, $title, 5, imagecolorallocate($img, 255, 255, 255));

        $contentTop = $y + 56;
        $contentBottom = $y + $h - 8;
        $count = count($rows) > 0 ? count($rows) : 1;
        $availableHeight = max(10, ($contentBottom - $contentTop));
        $weights = array();
        $totalWeight = 0;
        for ($i = 0; $i < $count; $i++) {
            $note = isset($rows[$i][3]) ? trim((string) $rows[$i][3]) : "";
            $weight = $note === "" ? 1 : 2;
            $weights[$i] = $weight;
            $totalWeight += $weight;
        }
        if ($totalWeight <= 0) {
            $totalWeight = $count;
        }
        $midX = $x + (int) floor($w * 0.58);
        imageline($img, $midX, $contentTop, $midX, $contentBottom, $line);
        $rowBoundaries = array();
        $cursor = $contentTop;
        for ($i = 0; $i < $count; $i++) {
            $rowHeight = (int) floor(($availableHeight * $weights[$i]) / $totalWeight);
            if ($i === $count - 1) {
                $rowBottom = $contentBottom;
            } else {
                $rowBottom = $cursor + max(28, $rowHeight);
            }
            $rowBoundaries[$i] = array($cursor, $rowBottom);
            if ($i < $count - 1) {
                imageline($img, $x + 8, $rowBottom, $x + $w - 8, $rowBottom, $line);
            }
            $cursor = $rowBottom;
        }
        for ($i = 0; $i < $count; $i++) {
            $row = $rows[$i];
            $top = $rowBoundaries[$i][0];
            $bottom = $rowBoundaries[$i][1];
            $label = isset($row[0]) ? (string) $row[0] : "";
            $value = isset($row[1]) ? (string) $row[1] : "";
            $valueColor = isset($row[2]) ? $row[2] : $dark;
            $note = isset($row[3]) ? (string) $row[3] : "";
            $iconCx = $x + 24;
            $iconCy = (int) (($top + $bottom) / 2);
            $this->drawIconBadge($img, $iconCx, $iconCy, $valueColor, "", imagecolorallocate($img, 255, 255, 255), 15);
            $this->drawCenterText($img, $x + 44, $top + 2, $midX - 10, $bottom - 2, $label, 4, $dark);
            if ($note === "") {
                $this->drawCenterText($img, $midX + 8, $top + 2, $x + $w - 12, $bottom - 2, $value, 5, $valueColor);
            } else {
                $midY = (int) ($top + (($bottom - $top) * 0.42));
                $this->drawCenterText($img, $midX + 8, $top + 2, $x + $w - 12, $midY, $value, 5, $valueColor);
                $this->drawWrappedTextInBox($img, $midX + 10, $midY, $x + $w - 14, $bottom - 2, $note, 5, $dark, 1);
            }
        }
    }

    private function drawIconBadge($img, $cx, $cy, $bgColor, $text = "", $textColor = null, $radius = 10)
    {
        if ($textColor === null) {
            $textColor = imagecolorallocate($img, 255, 255, 255);
        }
        imagefilledellipse($img, $cx, $cy, $radius * 2, $radius * 2, $bgColor);
        if ($text !== "") {
            $this->drawTextInBox(
                $img,
                $cx - $radius,
                $cy - $radius,
                $cx + $radius,
                $cy + $radius,
                $text,
                3,
                $textColor,
                false
            );
        }
    }

    //  drawTextAtCenter
    // private function drawTextAtCenter($img, $xCenter, $yCenter, $text, $font, $color)
    // {
    //     $x1 = (int) ($xCenter - 300);
    //     $x2 = (int) ($xCenter + 300);
    //     $y1 = (int) ($yCenter - 18);
    //     $y2 = (int) ($yCenter + 18);
    //     $this->drawTextInBox($img, $x1, $y1, $x2, $y2, (string) $text, $font, $color, false);
    // }

    private function drawCenterText($img, $x1, $y1, $x2, $y2, $text, $font, $color)
    {
        $this->drawTextInBox($img, (int) $x1, (int) $y1, (int) $x2, (int) $y2, (string) $text, $font, $color, false);
    }

    private function drawTextInBox($img, $x1, $y1, $x2, $y2, $text, $font, $color, $bold = false)
    {
        $text = (string) $text;
        $font = $this->getScaledFont($font);
        $ttf = $this->getReadableSansFont();
        if ($ttf !== "" && function_exists('imagettftext') && function_exists('imagettfbbox')) {
            $size = $this->mapGdToTtfSize($font);
            $bbox = imagettfbbox($size, 0, $ttf, $text);
            if (is_array($bbox)) {
                $txtWidth = abs($bbox[2] - $bbox[0]);
                $txtHeight = abs($bbox[7] - $bbox[1]);
                $x = (int) (($x1 + $x2 - $txtWidth) / 2);
                $y = (int) (($y1 + $y2 + $txtHeight) / 2) - 2;
                imagettftext($img, $size, 0, $x, $y, $color, $ttf, $text);
                return;
            }
        }
        $txtWidth = imagefontwidth($font) * strlen($text);
        $txtHeight = imagefontheight($font);
        $x = (int) (($x1 + $x2 - $txtWidth) / 2);
        $y = (int) (($y1 + $y2 - $txtHeight) / 2);
        $x = max($x1 + 2, $x);
        $y = max($y1 + 2, $y);
        imagestring($img, $font, $x, $y, $text, $color);
        if ($bold) {
            imagestring($img, $font, $x + 1, $y, $text, $color);
        }
    }

    private function getReadableSansFont()
    {
        if ($this->_resolvedSansFont !== null) {
            return $this->_resolvedSansFont;
        }
        $projectFont = dirname(__FILE__) . "/../../assets/fonts/team_summary.ttf";
        if (file_exists($projectFont)) {
            $this->_resolvedSansFont = $projectFont;
            return $this->_resolvedSansFont;
        }
        $candidates = array(
            // Server-friendly fallbacks when bundled font is unavailable.
            "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf",
            "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            // Windows fallbacks for local development.
            "C:/Windows/Fonts/segoeui.ttf",
            "C:/Windows/Fonts/calibri.ttf",
            "C:/Windows/Fonts/arial.ttf"
        );
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $this->_resolvedSansFont = $path;
                return $this->_resolvedSansFont;
            }
        }
        $this->_resolvedSansFont = "";
        return $this->_resolvedSansFont;
    }

    private function mapGdToTtfSize($font)
    {
        $font = (int) $font;
        if ($font <= 1) {
            return 10;
        }
        if ($font === 2) {
            return 12;
        }
        if ($font === 3) {
            return 15;
        }
        if ($font === 4) {
            return 18;
        }
        return 22;
    }

    private function getLineHeightForFont($font)
    {
        $font = $this->getScaledFont($font);
        $ttf = $this->getReadableSansFont();
        if ($ttf !== "" && function_exists('imagettfbbox')) {
            $size = $this->mapGdToTtfSize($font);
            $bbox = imagettfbbox($size, 0, $ttf, "Ag");
            if (is_array($bbox)) {
                return max(1, (int) ceil(abs($bbox[7] - $bbox[1]) * 1.15));
            }
        }
        return imagefontheight($font);
    }

    private function measureTextWidth($text, $font)
    {
        $font = $this->getScaledFont($font);
        $ttf = $this->getReadableSansFont();
        if ($ttf !== "" && function_exists('imagettfbbox')) {
            $size = $this->mapGdToTtfSize($font);
            $bbox = imagettfbbox($size, 0, $ttf, (string) $text);
            if (is_array($bbox)) {
                return abs($bbox[2] - $bbox[0]);
            }
        }
        return imagefontwidth($font) * strlen((string) $text);
    }

    private function getScaledFont($font)
    {
        $font = (int) $font;
        $scaled = $font;
        if ($scaled < 1) {
            $scaled = 1;
        }
        if ($scaled > 5) {
            $scaled = 5;
        }
        return $scaled;
    }

    private function formatCompactNumber($value)
    {
        if ($value === null || $value === "") {
            return "0";
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }
        return (string) round((float) $value);
    }

    private function sanitizeFileName($name)
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        return trim($safeName, '_');
    }

    private function getTeamStrengthData($aeNumber, $section, $dailyRows, $totalSalesmen, $minTotalShops, $minQualifiedAttendanceTimeInSec)
    {
        $qualified = 0;
        $presentTeams = array();
        $unqualifiedTeams = array();
        foreach ($dailyRows as $row) {
            $teamName = isset($row["team_name"]) ? trim((string) $row["team_name"]) : "";
            $wdCode = isset($row["wd_code"]) ? trim((string) $row["wd_code"]) : "";
            $teamId = isset($row["team_id"]) ? trim((string) $row["team_id"]) : "";
            if ($teamId !== "" && $teamName !== "") {
                $presentTeams[$teamId] = $teamName . ($wdCode !== "" ? " (" . $wdCode . ")" : "");
            }
            if ($this->isQualified($row, $minTotalShops, $minQualifiedAttendanceTimeInSec)) {
                $qualified++;
            } elseif ($teamName !== "") {
                $key = $teamId !== "" ? $teamId : ($teamName . "|" . $wdCode);
                $unqualifiedTeams[$key] = $teamName . ($wdCode !== "" ? " (" . $wdCode . ")" : "");
            }
        }

        $present = count($dailyRows);
        $unqualified = max(0, $present - $qualified);
        $absent = max(0, $totalSalesmen - $present);
        $allTeams = $this->getSectionTeamDetails($aeNumber, $section);
        $absentTeams = array();
        foreach ($allTeams as $team) {
            $teamId = isset($team["team_id"]) ? trim((string) $team["team_id"]) : "";
            $entry = isset($team["entry"]) ? trim((string) $team["entry"]) : "";
            if ($teamId !== "" && $entry !== "" && !isset($presentTeams[$teamId])) {
                $absentTeams[$teamId] = $entry;
            }
        }

        return array(
            "total" => $totalSalesmen,
            "qualified" => $qualified,
            "unqualified" => $unqualified,
            "absent" => $absent,
            "unqualified_names" => $this->formatTeamNames(array_values($unqualifiedTeams)),
            "absent_names" => $this->formatTeamNames(array_values($absentTeams))
        );
    }

    private function getSnapshotTodayData($dailyRows, $teamType, $infraLimit, $teamDetails = array())
    {
        $rows = $this->filterByType($dailyRows, $teamType);
        $count = count($rows);
        if ($count === 0) {
            return $this->emptyTodaySnapshot();
        }

        $sumVisited = 0.0;
        $sumBilled = 0.0;
        $sumStrikeRate = 0.0;
        $sumInfraVolume = 0.0;
        $sumLineCut = 0.0;
        $sumMarketMins = 0.0;
        $infraBelow = 0;
        $below6Hours = 0;
        $infraBelowNames = array();
        $below6HoursNames = array();
        $lowestStrikeRate = null;
        $lowestTeamName = "";

        foreach ($rows as $row) {
            $visited = (float) $row["total_sales_deliveries"] + (float) $row["total_other_shops"];
            $billed = (float) $row["total_sellin_shops"];
            $strike = $visited > 0 ? ($billed / $visited) * 100 : 0;
            $infraVolume = (float) $row["infra_volume"];
            $lineCut = isset($row["line_cut_count"]) ? (float) $row["line_cut_count"] : 0;
            $marketMins = (float) getTimeDifferenceInString($row["resp_startdatetime"], $row["resp_enddatetime"], false, false, true);

            $sumVisited += $visited;
            $sumBilled += $billed;
            $sumStrikeRate += $strike;
            $sumInfraVolume += $infraVolume;
            $sumLineCut += $lineCut;
            $sumMarketMins += $marketMins;

            if ($infraVolume < $infraLimit) {
                $infraBelow++;
                $entry = trim((string) $row["team_name"]);
                $wdCode = isset($row["wd_code"]) ? trim((string) $row["wd_code"]) : "";
                $teamId = isset($row["team_id"]) ? trim((string) $row["team_id"]) : "";
                $display = $this->buildTeamDisplay($entry, $wdCode, $teamId, $teamDetails);
                if ($display !== "") {
                    $infraBelowNames[$display] = true;
                }
            }
            if ($marketMins < 360) {
                $below6Hours++;
                $entry = trim((string) $row["team_name"]);
                $wdCode = isset($row["wd_code"]) ? trim((string) $row["wd_code"]) : "";
                $teamId = isset($row["team_id"]) ? trim((string) $row["team_id"]) : "";
                $display = $this->buildTeamDisplay($entry, $wdCode, $teamId, $teamDetails);
                if ($display !== "") {
                    $below6HoursNames[$display] = true;
                }
            }

            if ($lowestStrikeRate === null || $strike < $lowestStrikeRate) {
                $lowestStrikeRate = $strike;
                $lowestTeamName = $row["team_name"];
            }
        }

        return array(
            "avg_outlets_visited" => (string) round($sumVisited / $count),
            "avg_outlets_billed" => (string) round($sumBilled / $count),
            "avg_strike_rate" => (string) round($sumStrikeRate / $count) . "%",
            "lowest_strike_rate" => $this->formatNumber((float) $lowestStrikeRate, 2) . "%",
            "lowest_strike_team" => $lowestTeamName,
            "infra_volume" => (string) round($sumInfraVolume) . " Ms",
            "infra_volume_sum" => $sumInfraVolume,
            "infra_volume_count" => $count,
            "infra_below_limit" => (string) $infraBelow,
            "infra_below_limit_names" => $this->formatTeamNames(array_keys($infraBelowNames)),
            "avg_line_cut" => (string) round($sumLineCut / $count),
            "avg_time_spent" => $this->formatDurationFromMinutes($sumMarketMins / $count),
            "below_6_hours" => (string) $below6Hours,
            "below_6_hours_names" => $this->formatTeamNames(array_keys($below6HoursNames))
        );
    }

    private function getSnapshotMtdData($mtdRows, $teamType, $currentDate, $aeNumber, $section)
    {
        $rows = $this->filterByType($mtdRows, $teamType);
        $month = date("m", strtotime($currentDate));
        $year = date("Y", strtotime($currentDate));
        $branchIdList = $this->getSectionBranchIdList($rows, $aeNumber, $section);
        $focusBrandProducts = $this->getFocusBrandOneProducts($branchIdList, $month, $year);
        $brandMeta1 = isset($focusBrandProducts[0]) ? $focusBrandProducts[0] : array("columns" => array(), "label" => "Incentive Brand 1");
        $brandMeta2 = isset($focusBrandProducts[1]) ? $focusBrandProducts[1] : array("columns" => array(), "label" => "Incentive Brand 2");
        $teamIdList = $this->getSectionTeamIdList($rows, $aeNumber, $section);
        $incentiveBrand1Percent = $this->calculateIncentivePercentByColumns($teamIdList, $currentDate, $brandMeta1["columns"]);
        $incentiveBrand2Percent = $this->calculateIncentivePercentByColumns($teamIdList, $currentDate, $brandMeta2["columns"]);
        if (count($rows) === 0) {
            return array(
                "avg_daily_outlets_billed" => "0",
                "avg_daily_volume" => "0 Ms",
                "incentive_brand_1_label" => $brandMeta1["label"],
                "incentive_brand_1" => $incentiveBrand1Percent,
                "incentive_brand_2_label" => $brandMeta2["label"],
                "incentive_brand_2" => $incentiveBrand2Percent
            );
        }

        $dailyBilled = array();
        $dailyVolume = array();
        foreach ($rows as $row) {
            $date = $row["activity_date"];
            if (!isset($dailyBilled[$date])) {
                $dailyBilled[$date] = 0;
                $dailyVolume[$date] = 0;
            }
            $dailyBilled[$date] += (float) $row["total_sellin_shops"];
            $dailyVolume[$date] += (float) $row["infra_volume"];
        }

        $days = count($dailyBilled);
        $avgDailyBilled = $days > 0 ? array_sum($dailyBilled) / $days : 0;
        $avgDailyVolume = $days > 0 ? array_sum($dailyVolume) / $days : 0;
        return array(
            "avg_daily_outlets_billed" => (string) round($avgDailyBilled),
            "avg_daily_volume" => (string) round($avgDailyVolume) . " Ms",
            "incentive_brand_1_label" => $brandMeta1["label"],
            "incentive_brand_1" => $incentiveBrand1Percent,
            "incentive_brand_2_label" => $brandMeta2["label"],
            "incentive_brand_2" => $incentiveBrand2Percent
        );
    }

    private function getSectionTeamIdList($rows, $aeNumber, $section)
    {
        $teamIds = array();
        foreach ($rows as $row) {
            $teamId = isset($row["team_id"]) ? (int) $row["team_id"] : 0;
            if ($teamId > 0) {
                $teamIds[$teamId] = true;
            }
        }
        if (count($teamIds) === 0) {
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $action = null;
            $count = 0;
            $query = "SELECT DISTINCT team_id FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99 AND ae_number = '$aeNumber' AND section = '$section' AND team_id IS NOT NULL";
            $this->_dbConn->ExecuteSelectQuery($query, $action, $count);
            if ($count > 0) {
                while ($data = $this->_dbConn->GetData($action)) {
                    $teamId = isset($data["team_id"]) ? (int) $data["team_id"] : 0;
                    if ($teamId > 0) {
                        $teamIds[$teamId] = true;
                    }
                }
            }
        }
        return count($teamIds) > 0 ? implode(",", array_keys($teamIds)) : "";
    }

    private function calculateIncentivePercentByColumns($teamIdList, $currentDate, $columns)
    {
        if ($teamIdList === "" || !is_array($columns) || count($columns) === 0) {
            return "0%";
        }
        $targetTable = "tblassign_target";
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $monthStartDate = date("Y-m-01", strtotime($currentDate));

        $targetExprParts = array();
        $saleExprParts = array();
        foreach ($columns as $col) {
            $targetExprParts[] = "COALESCE(SUM(COALESCE($col,0)),0)";
            $saleExprParts[] = "COALESCE(SUM(COALESCE($col,0)),0)";
        }
        $targetExpr = implode(" + ", $targetExprParts);
        $saleExpr = implode(" + ", $saleExprParts);

        $targetValue = 0.0;
        $targetAction = null;
        $targetRows = 0;
        $targetQuery = "SELECT ($targetExpr) AS total_target FROM $targetTable WHERE team_id IN ($teamIdList)";
        $this->_dbConn->ExecuteSelectQuery($targetQuery, $targetAction, $targetRows);
        if ($targetRows > 0) {
            $targetData = $this->_dbConn->GetData($targetAction);
            $targetValue = isset($targetData["total_target"]) ? (float) $targetData["total_target"] : 0;
        }

        if ($targetValue <= 0) {
            return "0%";
        }

        $saleValue = 0.0;
        $saleAction = null;
        $saleRows = 0;
        $saleQuery = "SELECT ($saleExpr) AS total_sale FROM $summaryTable WHERE dstatus = 0 AND team_id IN ($teamIdList) AND activity_date BETWEEN '$monthStartDate' AND '$currentDate'";
        $this->_dbConn->ExecuteSelectQuery($saleQuery, $saleAction, $saleRows);
        if ($saleRows > 0) {
            $saleData = $this->_dbConn->GetData($saleAction);
            $saleValue = isset($saleData["total_sale"]) ? (float) $saleData["total_sale"] : 0;
        }

        $percent = ($saleValue / $targetValue) * 100;
        return $this->formatNumber($percent, 2) . "%";
    }

    private function getFocusBrandOneProducts($branchIdList, $month, $year)
    {
        if ($branchIdList === "") {
            return array();
        }
        $brandProductsTable = "tblbranch_products_month_wise";
        $action = null;
        $rowsCount = 0;
        $query = "SELECT summary_column_name, product_name FROM $brandProductsTable " .
            "WHERE month = '$month' AND year = '$year' AND branch_id IN ($branchIdList) AND is_focusbrand = 1 " .
            "AND summary_column_name IS NOT NULL AND summary_column_name != '' ORDER BY sort_order, rec_id";
        $this->_dbConn->ExecuteSelectQuery($query, $action, $rowsCount);

        $products = array();
        if ($rowsCount > 0) {
            while ($row = $this->_dbConn->GetData($action)) {
                $col = isset($row["summary_column_name"]) ? trim((string) $row["summary_column_name"]) : "";
                $name = isset($row["product_name"]) ? trim((string) $row["product_name"]) : "";
                if (!preg_match('/^total_sale_product[0-9]+$/i', $col)) {
                    continue;
                }
                $label = $name !== "" ? ($name . " (Incentive Brand) ") : "NA";
                if (!isset($products[$label])) {
                    $products[$label] = array("columns" => array(), "label" => $label);
                }
                $products[$label]["columns"][$col] = true;
            }
        }
        $result = array();
        foreach ($products as $meta) {
            $result[] = array(
                "columns" => array_keys($meta["columns"]),
                "label" => $meta["label"]
            );
            if (count($result) >= 2) {
                break;
            }
        }
        return $result;
    }

    private function calculateIncentiveBrandMeta($rows, $teamType, $currentDate, $focusBrand, $aeNumber, $section)
    {
        $teamIds = array();
        foreach ($rows as $row) {
            $teamId = isset($row["team_id"]) ? (int) $row["team_id"] : 0;
            if ($teamId > 0) {
                $teamIds[$teamId] = true;
            }
        }
        if (count($teamIds) === 0) {
            return array("label" => "Incentive Brand " . $focusBrand, "percent" => "0%");
        }

        $month = date("m", strtotime($currentDate));
        $year = date("Y", strtotime($currentDate));
        $teamIdList = implode(",", array_keys($teamIds));
        $branchIdList = $this->getSectionBranchIdList($rows, $aeNumber, $section);
        if ($branchIdList === "") {
            return array("label" => "Incentive Brand " . $focusBrand, "percent" => "0%");
        }

        $brandMeta = $this->getIncentiveBrandColumnsAndNames($branchIdList, $month, $year, $teamType, $focusBrand);
        $columns = $brandMeta["columns"];
        $label = $brandMeta["label"];
        if (count($columns) === 0) {
            return array("label" => $label, "percent" => "0%");
        }

        $targetTable = "tblassign_target";
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $monthStartDate = date("Y-m-01", strtotime($currentDate));

        $targetExprParts = array();
        $saleExprParts = array();
        foreach ($columns as $col) {
            $targetExprParts[] = "COALESCE(SUM(COALESCE($col,0)),0)";
            $saleExprParts[] = "COALESCE(SUM(COALESCE($col,0)),0)";
        }
        $targetExpr = implode(" + ", $targetExprParts);
        $saleExpr = implode(" + ", $saleExprParts);

        $targetValue = 0.0;
        $targetAction = null;
        $targetRows = 0;
        $targetQuery = "SELECT ($targetExpr) AS total_target FROM $targetTable " .
            "WHERE team_id IN ($teamIdList)";
        $this->_dbConn->ExecuteSelectQuery($targetQuery, $targetAction, $targetRows);
        if ($targetRows > 0) {
            $targetData = $this->_dbConn->GetData($targetAction);
            $targetValue = isset($targetData["total_target"]) ? (float) $targetData["total_target"] : 0;
        }

        $saleValue = 0.0;
        $saleAction = null;
        $saleRows = 0;
        $saleQuery = "SELECT ($saleExpr) AS total_sale FROM $summaryTable " .
            "WHERE dstatus = 0 AND team_id IN ($teamIdList) AND activity_date BETWEEN '$monthStartDate' AND '$currentDate'";
        $this->_dbConn->ExecuteSelectQuery($saleQuery, $saleAction, $saleRows);
        if ($saleRows > 0) {
            $saleData = $this->_dbConn->GetData($saleAction);
            $saleValue = isset($saleData["total_sale"]) ? (float) $saleData["total_sale"] : 0;
        }

        if ($targetValue <= 0) {
            return array("label" => $label, "percent" => "0%");
        }
        $percent = ($saleValue / $targetValue) * 100;
        return array(
            "label" => $label,
            "percent" => $this->formatNumber($percent, 2) . "%"
        );
    }

    private function getSectionBranchIdList($rows, $aeNumber, $section)
    {
        $branchIds = array();
        foreach ($rows as $row) {
            $branchId = isset($row["branch_id"]) ? (int) $row["branch_id"] : 0;
            if ($branchId > 0) {
                $branchIds[$branchId] = true;
            }
        }
        if (count($branchIds) === 0) {
            $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
            $action = null;
            $count = 0;
            $query = "SELECT DISTINCT branch_id FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99 AND ae_number = '$aeNumber' AND section = '$section' AND branch_id IS NOT NULL";
            $this->_dbConn->ExecuteSelectQuery($query, $action, $count);
            if ($count > 0) {
                while ($data = $this->_dbConn->GetData($action)) {
                    $branchId = isset($data["branch_id"]) ? (int) $data["branch_id"] : 0;
                    if ($branchId > 0) {
                        $branchIds[$branchId] = true;
                    }
                }
            }
        }
        return count($branchIds) > 0 ? implode(",", array_keys($branchIds)) : "";
    }

    private function getIncentiveBrandColumnsAndNames($branchIdList, $month, $year, $teamType, $focusBrand)
    {
        $brandProductsTable = "tblbranch_products_month_wise";
        $action = null;
        $rowsCount = 0;
        $query = "SELECT DISTINCT summary_column_name, product_name FROM $brandProductsTable " .
            "WHERE month = '$month' AND year = '$year' AND branch_id IN ($branchIdList) " .
            "AND is_focusbrand = '$focusBrand' " .
            "AND summary_column_name IS NOT NULL AND summary_column_name != '' ORDER BY product_name";
        $this->_dbConn->ExecuteSelectQuery($query, $action, $rowsCount);

        $columns = array();
        $names = array();
        if ($rowsCount > 0) {
            while ($row = $this->_dbConn->GetData($action)) {
                $col = isset($row["summary_column_name"]) ? trim((string) $row["summary_column_name"]) : "";
                $name = isset($row["product_name"]) ? trim((string) $row["product_name"]) : "";
                if (preg_match('/^total_sale_product[0-9]+$/i', $col)) {
                    $columns[$col] = true;
                    if ($name !== "") {
                        $names[$name] = true;
                    }
                }
            }
        }
        $nameList = array_keys($names);
        $label = count($nameList) > 0 ? implode(", ", $nameList) : ("Incentive Brand " . $focusBrand);
        return array(
            "columns" => array_keys($columns),
            "label" => $label
        );
    }

    private function getAeSummaryRows($aeNumber, $section, $dateFrom, $dateTo)
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $summaryTable = $this->_tables["VANDS_SUMMARY_TABLE"];
        $formula = $this->_productSaleVolumeFormula;
        $lineCutFormula = $this->buildLineCutCountFormula();
        $summaryAction = null;
        $summaryRows = 0;
        $query = "SELECT a.activity_date, a.start_datetime, a.end_datetime, a.resp_startdatetime, a.resp_enddatetime, a.dayend_datetime, a.total_sales_deliveries, a.total_sellin_shops, a.total_other_shops, a.is_qualified, " .
            "$formula AS infra_volume, $lineCutFormula AS line_cut_count, b.team_id, b.team_name, b.wd_code, b.is_type, b.branch_id FROM $summaryTable AS a, $projectTeamTable AS b WHERE a.dstatus = 0 AND b.dstatus = 0 AND a.team_id = b.team_id AND b.is_type IN (0,2,5)" .
            " AND b.ae_number = '$aeNumber' AND b.section = '$section' AND a.activity_date BETWEEN '$dateFrom' AND '$dateTo'";
        $this->_dbConn->ExecuteSelectQuery($query, $summaryAction, $summaryRows);

        $rows = array();
        if ($summaryRows > 0) {
            while ($summaryRow = $this->_dbConn->GetData($summaryAction)) {
                $rows[] = $summaryRow;
            }
        }
        return $rows;
    }

    private function getSectionTeamDetails($aeNumber, $section)
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $action = null;
        $rows = 0;
        $query = "SELECT DISTINCT team_id, team_name, wd_code FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99 AND ae_number = '$aeNumber' AND section = '$section' AND is_type IN (0,2,5)";
        $this->_dbConn->ExecuteSelectQuery($query, $action, $rows);
        $teams = array();
        if ($rows > 0) {
            while ($row = $this->_dbConn->GetData($action)) {
                $teamId = isset($row["team_id"]) ? trim((string) $row["team_id"]) : "";
                $name = isset($row["team_name"]) ? trim((string) $row["team_name"]) : "";
                $wdCode = isset($row["wd_code"]) ? trim((string) $row["wd_code"]) : "";
                if ($teamId !== "" && $name !== "") {
                    $teams[$teamId] = array(
                        "team_id" => $teamId,
                        "team_name" => $name,
                        "wd_code" => $wdCode,
                        "entry" => $name . ($wdCode !== "" ? " (" . $wdCode . ")" : "")
                    );
                }
            }
        }
        return array_values($teams);
    }

    private function getSectionTypeFlags($aeNumber, $section)
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $action = null;
        $rows = 0;
        $query = "SELECT DISTINCT is_type FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99 AND ae_number = '$aeNumber' AND section = '$section' AND is_type IN (0,2,5)";
        $this->_dbConn->ExecuteSelectQuery($query, $action, $rows);
        $hasNpsr = false;
        $hasVan = false;
        if ($rows > 0) {
            while ($row = $this->_dbConn->GetData($action)) {
                $type = isset($row["is_type"]) ? (int) $row["is_type"] : -1;
                if ($type === 5) {
                    $hasNpsr = true;
                } elseif ($type === 0 || $type === 2) {
                    $hasVan = true;
                }
            }
        }
        return array(
            "has_npsr" => $hasNpsr,
            "has_van" => $hasVan
        );
    }

    private function getSectionTypeBifurcationCounts($aeNumber, $section)
    {
        $projectTeamTable = $this->_tables["PROJECT_TEAM_TABLE"];
        $action = null;
        $rows = 0;
        $query = "SELECT " .
            "COUNT(DISTINCT CASE WHEN is_type IN (0,2) THEN team_id END) AS van_ds_count, " .
            "COUNT(DISTINCT CASE WHEN is_type = 5 THEN team_id END) AS npsr_count " .
            "FROM $projectTeamTable WHERE dstatus = 0 AND s_id = 99 AND ae_number = '$aeNumber' AND section = '$section' AND is_type IN (0,2,5)";
        $this->_dbConn->ExecuteSelectQuery($query, $action, $rows);
        $result = array("van_ds_count" => 0, "npsr_count" => 0);
        if ($rows > 0) {
            $data = $this->_dbConn->GetData($action);
            $result["van_ds_count"] = isset($data["van_ds_count"]) ? (int) $data["van_ds_count"] : 0;
            $result["npsr_count"] = isset($data["npsr_count"]) ? (int) $data["npsr_count"] : 0;
        }
        return $result;
    }

    private function formatTeamNames($names)
    {
        if (!is_array($names) || count($names) === 0) {
            return "";
        }
        $names = array_values(array_unique(array_filter($names)));
        return implode(", ", $names);
    }

    private function buildTeamDisplay($teamName, $wdCode, $teamId, $teamDetails)
    {
        $teamName = trim((string) $teamName);
        $wdCode = trim((string) $wdCode);
        $teamId = trim((string) $teamId);
        if ($wdCode !== "" && $teamName !== "") {
            return $teamName . " (" . $wdCode . ")";
        }
        if ($teamId !== "" && is_array($teamDetails)) {
            foreach ($teamDetails as $team) {
                if ((string) $team["team_id"] === $teamId) {
                    $fallbackName = isset($team["team_name"]) ? trim((string) $team["team_name"]) : "";
                    $fallbackWd = isset($team["wd_code"]) ? trim((string) $team["wd_code"]) : "";
                    if ($fallbackName !== "") {
                        return $fallbackName . ($fallbackWd !== "" ? " (" . $fallbackWd . ")" : "");
                    }
                }
            }
        }
        return $teamName;
    }

    private function filterByType($rows, $teamType)
    {
        $result = array();
        foreach ($rows as $row) {
            $type = isset($row["is_type"]) ? (int) $row["is_type"] : -1;
            if ((int) $teamType === 0) {
                if ($type === 0 || $type === 2) {
                    $result[] = $row;
                }
            } elseif ($type === (int) $teamType) {
                $result[] = $row;
            }
        }
        return $result;
    }

    private function isQualified($row, $minTotalShops, $minQualifiedAttendanceTimeInSec)
    {
        if (isset($row["is_qualified"]) && $row["is_qualified"] !== "" && $row["is_qualified"] !== null) {
            return (string) $row["is_qualified"] === "1";
        }
        $totalShopsDone = (float) $row["total_sales_deliveries"] + (float) $row["total_other_shops"];
        $timeSpentInSec = getTimeDifferenceInString($row["start_datetime"], $row["end_datetime"], true);
        return ($totalShopsDone >= $minTotalShops && $timeSpentInSec >= $minQualifiedAttendanceTimeInSec);
    }

    private function buildProductSaleVolumeFormula()
    {
        $formulaParts = array();
        for ($index = 1; $index <= 145; $index++) {
            $formulaParts[] = "COALESCE(a.total_sale_product$index,0)";
        }
        return implode(" + ", $formulaParts);
    }

    private function buildLineCutCountFormula()
    {
        $formulaParts = array();
        for ($index = 1; $index <= 145; $index++) {
            $formulaParts[] = "CASE WHEN COALESCE(a.total_sale_product$index,0) > 0 THEN 1 ELSE 0 END";
        }
        return "(" . implode(" + ", $formulaParts) . ")";
    }

    private function formatNumber($value, $decimals = 2)
    {
        return number_format((float) $value, $decimals, '.', '');
    }

    private function formatDurationFromMinutes($minutes)
    {
        $minutes = (int) round($minutes);
        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;
        return $hours . "h " . $remMinutes . "m";
    }

    private function extractNumericValue($value)
    {
        $text = trim((string) $value);
        if ($text === "" || strtoupper($text) === "NA") {
            return null;
        }
        if (preg_match('/-?\d+(\.\d+)?/', $text, $matches)) {
            return (float) $matches[0];
        }
        return null;
    }

    private function emptyTodaySnapshot()
    {
        return array(
            "avg_outlets_visited" => "0",
            "avg_outlets_billed" => "0",
            "avg_strike_rate" => "0%",
            "lowest_strike_rate" => "0%",
            "lowest_strike_team" => "",
            "infra_volume" => "0 Ms",
            "infra_volume_sum" => 0,
            "infra_volume_count" => 0,
            "infra_below_limit" => "0",
            "infra_below_limit_names" => "",
            "avg_line_cut" => "0",
            "avg_time_spent" => "0h 0m",
            "below_6_hours" => "0",
            "below_6_hours_names" => ""
        );
    }
}

// Create instance of VanDswhatsAppSummary class
$vanDswhatsAppSummary = new VanDswhatsAppSummary($dbConn);

// Send team summary
$vanDswhatsAppSummary->sendTeamSummary();
