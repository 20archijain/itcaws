<?php

require_once $include_path . "defined_index.php";
require $PHP_FPDF_PATH;

// phpcs:ignore
class Pdf extends FPDF
{
    private $_pageWidth = 297;  // A4 Landscape width in mm
    private $_pageHeight = 210; // A4 Landscape height in mm
    private $_margin = 10;      // Page margins
    private $_currentY = 0;     // Current Y position

    public function __construct()
    {
        // A4 Landscape orientation
        parent::__construct('L', 'mm', 'A4');
        $this->SetAutoPageBreak(false); // Manual page breaks for better control
        $this->SetMargins($this->_margin, $this->_margin, $this->_margin);
    }

    /**
     * Create a new page
     */
    final public function createPage()
    {
        $this->AddPage();
        $this->_currentY = $this->_margin;
    }

    /**
     * Add title text to the page
     */
    final public function addTitle($text, $fontSize = 24, $color = [192, 0, 0])
    {
        $this->SetFont('Arial', 'B', $fontSize);
        $this->SetTextColor($color[0], $color[1], $color[2]);
        $this->SetXY($this->_margin, $this->_margin + 40);
        $this->Cell($this->_pageWidth - (2 * $this->_margin), 10, $text, 0, 1, 'C');
        $this->_currentY = $this->GetY() + 10;
    }

    /**
     * Add logo image
     */
    final public function addLogo($logoPath, $x, $y, $width, $height)
    {
        if (file_exists($logoPath) && is_file($logoPath) && is_readable($logoPath)) {
            try {
                // Determine image type
                $imageType = $this->getImageType($logoPath);
                if ($imageType) {
                    $this->Image($logoPath, $x, $y, $width, $height, $imageType);
                }
            } catch (Exception $e) {
                // Silently skip problematic images
            }
        }
    }

    /**
     * Add table to the page with dynamic column widths
     */
    final public function addTable(
        array $tableData,
        int $rows,
        int $cols,
        $x = null,
        $y = null,
        $tableWidth = 277,
        $fontSize = 11,
        $headerBgColor = [255, 87, 51],
        $headerTextColor = [255, 255, 255],
        $bodyTextColor = [0, 0, 0],
        $columnWidths = []
    ) {
        if ($x === null) {
            $x = $this->_margin;
        }
        if ($y === null) {
            $y = $this->_currentY;
        }

        $this->SetXY($x, $y);

        // Calculate dynamic column widths if not provided
        $cellWidths = [];
        if (!empty($columnWidths) && count($columnWidths) === $cols) {
            $cellWidths = $columnWidths;
        } else {
            // Calculate max width needed for each column based on content
            $maxWidths = [];
            $this->SetFont('Arial', 'B', $fontSize); // Set font for measurement

            for ($colIndex = 0; $colIndex < $cols; $colIndex++) {
                $maxWidth = 0;
                for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
                    $cellText = isset($tableData[$rowIndex][$colIndex]) ? $tableData[$rowIndex][$colIndex] : '';
                    $textWidth = $this->GetStringWidth($this->convertText($cellText));
                    if ($textWidth > $maxWidth) {
                        $maxWidth = $textWidth;
                    }
                }
                // Add padding (4mm on each side = 8mm total)
                $maxWidths[$colIndex] = $maxWidth + 8;
            }

            // Calculate total width needed
            $totalNeeded = array_sum($maxWidths);

            // If total exceeds available width, proportionally scale down
            if ($totalNeeded > $tableWidth) {
                $scale = $tableWidth / $totalNeeded;
                foreach ($maxWidths as $width) {
                    $cellWidths[] = $width * $scale;
                }
            } else {
                // If we have extra space, distribute it proportionally
                $scale = $tableWidth / $totalNeeded;
                foreach ($maxWidths as $width) {
                    $cellWidths[] = $width * $scale;
                }
            }
        }

        $cellHeight = 10;
        $lineHeight = 6; // Height per line for wrapped text

        for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
            // Pre-process: wrap text for all cells in this row and calculate max lines
            $wrappedCells = [];
            $maxLines = 1;

            for ($colIndex = 0; $colIndex < $cols; $colIndex++) {
                $cellText = isset($tableData[$rowIndex][$colIndex]) ? $tableData[$rowIndex][$colIndex] : '';

                // Set font for wrapping calculation
                if ($rowIndex === 0) {
                    $this->SetFont('Arial', 'B', $fontSize);
                } else {
                    $this->SetFont('Arial', '', $fontSize);
                }

                // Wrap text to fit cell width (subtract 4mm for padding)
                $cellWidth = max($cellWidths[$colIndex] - 4, 1);
                $wrappedLines = $this->wrapText($cellText, $cellWidth, $fontSize);
                $wrappedCells[$colIndex] = $wrappedLines;

                if (count($wrappedLines) > $maxLines) {
                    $maxLines = count($wrappedLines);
                }
            }

            // Calculate row height based on max lines
            $currentRowHeight = max($cellHeight, $maxLines * $lineHeight);

            // Set starting position for this row
            $startY = $this->GetY();
            $startX = $x;

            for ($colIndex = 0; $colIndex < $cols; $colIndex++) {
                // Header row styling
                if ($rowIndex === 0) {
                    $this->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
                    $this->SetTextColor($headerTextColor[0], $headerTextColor[1], $headerTextColor[2]);
                    $this->SetFont('Arial', 'B', $fontSize);
                } else {
                    $this->SetFillColor(255, 255, 255);
                    $this->SetTextColor($bodyTextColor[0], $bodyTextColor[1], $bodyTextColor[2]);
                    $this->SetFont('Arial', '', $fontSize);
                }

                // Calculate X position for this column
                $cellX = $startX;
                for ($i = 0; $i < $colIndex; $i++) {
                    $cellX += $cellWidths[$i];
                }

                // Draw cell with wrapped text
                $wrappedLines = $wrappedCells[$colIndex];
                $cellWidth = $cellWidths[$colIndex];

                // Draw border and fill
                $this->Rect($cellX, $startY, $cellWidth, $currentRowHeight, 'DF');

                // Draw text lines centered vertically
                $numLines = count($wrappedLines);
                $textStartY = $startY + (($currentRowHeight - ($numLines * $lineHeight)) / 2);

                for ($lineIndex = 0; $lineIndex < $numLines; $lineIndex++) {
                    $lineText = $wrappedLines[$lineIndex];
                    $lineY = $textStartY + ($lineIndex * $lineHeight);
                    $this->SetXY($cellX, $lineY);
                    $this->Cell($cellWidth, $lineHeight, $this->convertText($lineText), 0, 0, 'C', false);
                }
            }

            // Move to next row position
            $this->SetXY($x, $startY + $currentRowHeight);
        }

        $this->_currentY = $this->GetY() + 5;
    }

    /**
     * Add images side by side
     */
    final public function addImages(array $images, $startX = null, $startY = null, $imgWidth = 120, $imgHeight = 120, $spacing = 10)
    {
        if (empty($images)) {
            return;
        }

        if ($startX === null) {
            $startX = $this->_margin;
        }
        if ($startY === null) {
            $startY = $this->_currentY;
        }

        $currentX = $startX;
        $maxHeight = 0;

        foreach ($images as $index => $imageData) {
            $imagePath = $imageData['path'];
            $label = isset($imageData['label']) ? $imageData['label'] : '';

            if (file_exists($imagePath) && is_file($imagePath) && is_readable($imagePath)) {
                $fileSize = @filesize($imagePath);
                if ($fileSize > 0 && $fileSize < 50 * 1024 * 1024) { // Max 50MB
                    try {
                        $imageType = $this->getImageType($imagePath);
                        if ($imageType) {
                            // Add image
                            $this->Image($imagePath, $currentX, $startY, $imgWidth, $imgHeight, $imageType);

                            // Add label below image
                            if ($label) {
                                $this->SetFont('Arial', 'B', 10);
                                $this->SetTextColor(192, 0, 192);
                                $this->SetXY($currentX, $startY + $imgHeight + 2);
                                $this->Cell($imgWidth, 5, $this->convertText($label), 0, 0, 'C');
                            }

                            $totalHeight = $imgHeight + ($label ? 7 : 0);
                            if ($totalHeight > $maxHeight) {
                                $maxHeight = $totalHeight;
                            }

                            $currentX += $imgWidth + $spacing;
                        }
                    } catch (Exception $e) {
                        // Silently skip problematic images
                        continue;
                    }
                }
            }
        }

        $this->_currentY = $startY + $maxHeight + 10;
    }

    /**
     * Get image type for FPDF
     */
    private function getImageType($imagePath)
    {
        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return 'JPG';
            case 'image/png':
                return 'PNG';
            case 'image/gif':
                return 'GIF';
            default:
                // Try to convert to JPEG for unsupported formats
                return 'JPG';
        }
    }

    /**
     * Convert UTF-8 text to FPDF compatible encoding
     */
    private function convertText($text)
    {
        if (empty($text)) {
            return '';
        }

        // Convert to ISO-8859-1 (Latin-1) encoding
        $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return $text ? $text : '';
    }

    /**
     * Wrap text to fit within specified width
     */
    private function wrapText($text, $maxWidth, $fontSize = 11)
    {
        if (empty($text)) {
            return [''];
        }

        $this->SetFont('Arial', '', $fontSize);
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $testWidth = $this->GetStringWidth($this->convertText($testLine));

            if ($testWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return empty($lines) ? [''] : $lines;
    }

    /**
     * Save PDF to file
     */
    final public function savePdf(string $fileName, bool $isDownloadFile = true)
    {
        $savePath = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadUrl = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        // Save PDF file
        $this->Output('F', $savePath);

        if ($isDownloadFile) {
            header("Location: $downloadUrl");
        } else {
            $fileDetails = [
                "downloadUrl" => $downloadUrl,
                "savePath" => $savePath,
            ];

            return $fileDetails;
        }
    }

    // for downloading image
    public function downloadMultipleImagesToTemp(array $urlMap, int $batchSize = 35): array
    {
        $results = [];

        if (empty($urlMap)) {
            return $results;
        }

        $chunks = array_chunk($urlMap, $batchSize, true);

        foreach ($chunks as $chunk) {
            $mh = curl_multi_init();
            $handles = [];

            foreach ($chunk as $key => $url) {
                $tmp = sys_get_temp_dir() . '/pdf_' . md5($url) . '.jpg';
                $fp  = fopen($tmp, 'w');

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_FILE => $fp,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FAILONERROR => true,
                ]);

                curl_multi_add_handle($mh, $ch);
                $handles[$key] = [$ch, $fp, $tmp, $url];
            }

            // run batch
            do {
                $status = curl_multi_exec($mh, $running);
                curl_multi_select($mh, 1.0);
            } while ($running && $status == CURLM_OK);

            // validate + retry failed
            foreach ($handles as $key => [$ch, $fp, $tmp, $url]) {
                $error  = curl_errno($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                fclose($fp);

                if ($error === 0 && $status == 200 && is_file($tmp) && filesize($tmp) > 2048) {
                    $data = file_get_contents($tmp);
                    if (@getimagesizefromstring($data) !== false) {
                        $results[$key] = $tmp;
                        continue;
                    }
                }

                @unlink($tmp);

                // retry once (important)
                $retry = $this->retryDownload($url);
                if ($retry) {
                    $results[$key] = $retry;
                }
            }

            curl_multi_close($mh);
        }

        return $results;
    }

    private function retryDownload(string $url): ?string
    {
        $tmp = sys_get_temp_dir() . '/pdf_retry_' . md5($url) . '.jpg';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $data = curl_exec($ch);
        curl_close($ch);

        if ($data && strlen($data) > 2048 && @getimagesizefromstring($data) !== false) {
            file_put_contents($tmp, $data);
            return $tmp;
        }

        return null;
    }
}
