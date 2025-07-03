<?php

require_once $PHP_PRESENTATION_PATH;
require_once $PHP_OFFICE_PATH;
\PhpOffice\PhpPresentation\Autoloader::register();
\PhpOffice\Common\Autoloader::register();

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\Shape\Drawing;
use PhpOffice\PhpPresentation\Style\Fill;

// phpcs:ignore
class Ppt
{
    private $_objPHPPowerPoint = null;
    private $_currentSlide = null;

    // Define constants
    private $_slideWidth = 1920; // Slide width
    private $_slideHeight = 1080; // Slide height
    private $_noOfImagesInRow;   // No of images in a row
    private $_noOfRowsInEachSlide;   // No of rows in a slide
    private $_headerText = "";    // Header text
    private $_headingWidth = 1100;    // Heading Width
    private $_headingheight = 90;    // Heading Height
    private $_hasTextBelowImage = true;    // Text below image
    private $_textBelowImageHeight = 50; // Height of text below image if $textBelowImage = true
    private $_textBelowImageMarign = 5;  // text below Image bottom marign if $textBelowImage = true
    private $_slideEdgeMarginX = 20; // X-axis slide margin on both sides
    private $_slideEdgeOffsetY = 10; // Y-axis slide margin on both sides
    private $_marginXBwImage = 10;   // Margin between each image on X-Axis
    private $_marginYBwImage = 5;    // Margin between each image on Y-Axis

    // Calculated width and height of image required
    private $_imageWidth;    // Width of image
    private $_imageheight;    // Height of image
    private $_noOfImagesInSlide; // No of images in each slide
    private $_arrImageCoordinates = [];    // Calculated each image X and Y position

    public function __construct(int $noOfImagesInRow = 1, int $noOfRowsInEachSlide = 1)
    {
        $this->_objPHPPowerPoint = new PhpPresentation();

        // Remove first slide
        $this->_objPHPPowerPoint->removeSlideByIndex(0);

        // set Layout as 16:9 ratio
        $layout = $this->_objPHPPowerPoint->getLayout();
        $layout->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        $layout->setCX($this->_slideWidth, DocumentLayout::UNIT_PIXEL);
        $layout->setCY($this->_slideHeight, DocumentLayout::UNIT_PIXEL);

        $this->_noOfImagesInRow = $noOfImagesInRow ? abs($noOfImagesInRow) : 1;
        $this->_noOfRowsInEachSlide = $noOfRowsInEachSlide ? abs($noOfRowsInEachSlide) : 1;
    }

    final public function setHeadingWidth(int $headingWidth)
    {
        $this->_headingWidth = $headingWidth;
    }

    final public function setHeadingHeight(int $headingheight)
    {
        $this->_headingheight = $headingheight;
    }

    final public function setTextBelowImage(bool $hasTextBelowImage)
    {
        $this->_hasTextBelowImage = $hasTextBelowImage;
    }

    final public function setTextBelowImageHeight(int $textBelowImageHeight)
    {
        $this->_textBelowImageHeight = $textBelowImageHeight;
    }

    final public function setTextBelowImageMarign(int $textBelowImageMarign)
    {
        $this->_textBelowImageMarign = $textBelowImageMarign;
    }

    final public function setSlideEdgeMarginX(int $slideEdgeMarginX)
    {
        $this->_slideEdgeMarginX = $slideEdgeMarginX;
    }

    final public function setSlideEdgeMarginY(int $slideEdgeMarginY)
    {
        $this->_slideEdgeOffsetY = $slideEdgeMarginY;
    }

    final public function setMarginXBwImage(int $marginXBwImage)
    {
        $this->_marginXBwImage = $marginXBwImage;
    }

    final public function setMarginYBwImage(int $marginYBwImage)
    {
        $this->_marginYBwImage = $marginYBwImage;
    }

    private function calculateWidthAndHeightOfImage()
    {
        $this->_imageWidth = floor(
            ($this->_slideWidth -
                ((2 * $this->_slideEdgeMarginX) +
                    ($this->_marginXBwImage * ($this->_noOfImagesInRow - 1)))) / $this->_noOfImagesInRow
        );

        $remainingheightOfSlide = $this->_slideHeight - $this->_headingheight;
        $textBelowImageSpace = $this->_hasTextBelowImage ? $this->_textBelowImageHeight + $this->_textBelowImageMarign : 0;

        $this->_imageheight = floor(
            ($remainingheightOfSlide -
                ((2 * $this->_slideEdgeOffsetY) +
                    (($this->_marginYBwImage + $textBelowImageSpace) * $this->_noOfRowsInEachSlide))) / $this->_noOfRowsInEachSlide
        );

        $this->getArrImageCoordinates($textBelowImageSpace);
    }

    private function getArrImageCoordinates(int $textBelowImageSpace)
    {
        $this->_noOfImagesInSlide = $this->_noOfImagesInRow * $this->_noOfRowsInEachSlide;

        for ($i = 1; $i <= $this->_noOfImagesInSlide; $i++) {
            $imageNoInRow = $i % $this->_noOfImagesInRow;
            $imageNoInColumn = ceil($i / $this->_noOfImagesInRow);

            $offsetX = $this->_slideEdgeMarginX +
                (
                    ($this->_imageWidth + $this->_marginXBwImage) *
                    (($imageNoInRow !== 0 ? $imageNoInRow : $this->_noOfImagesInRow) - 1));

            $offsetY = $this->_headingheight + $this->_slideEdgeOffsetY +
                (
                    ($this->_imageheight + $this->_marginYBwImage + $textBelowImageSpace) *
                    (($imageNoInColumn !== 0 ? $imageNoInColumn : $this->_noOfRowsInEachSlide) - 1));

            $this->_arrImageCoordinates[] = array(
                "offsetX" => $offsetX,
                "offsetY" => $offsetY,
            );
        }
    }

    final public function createSlide()
    {
        // Create slide
        $this->_currentSlide = $this->_objPHPPowerPoint->createSlide();
    }

    final public function addHeader(string $headerText)
    {
        $this->_headerText = $headerText;
        $this->addText($this->_headerText, $this->_headingheight, $this->_headingWidth);
    }

    final public function addText($text, $height = 100, $width = 700, $offsetX = 100, $offsetY = 0, $alignCenter = true, $fontSize = 24, $color = 'FFC00000')
    {
        $shape = $this->_currentSlide->createRichTextShape()
            ->setHeight($height)
            ->setWidth($width)
            ->setOffsetX($offsetX)
            ->setOffsetY($offsetY);

        if ($alignCenter) {
            $shape->getActiveParagraph()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $textRun = $shape->createTextRun($text);
        $textRun->getFont()->setBold(true)
            ->setSize($fontSize)
            ->setColor(new Color($color));
    }

    final public function addImages(array $arrImages, int $offsetX = 0, int $offsetY = 0, int $width = 0, int $height = 0, string $imageKey = "big", string $labelKey = "description")
    {
        if ($arrImages && count($arrImages) > 0) {
            $this->calculateWidthAndHeightOfImage();
            $count = 0;

            foreach ($arrImages as $index => $image) {
                $imagePath = $image[$imageKey];
                $label = $this->_hasTextBelowImage ? $image[$labelKey] : "";

                if (filter_var($imagePath, FILTER_VALIDATE_URL) || (file_exists($imagePath) && is_file($imagePath))) {
                    $this->addImage(
                        $imagePath,
                        $this->_arrImageCoordinates[$count]["offsetX"] + $offsetX,
                        $this->_arrImageCoordinates[$count]["offsetY"] + $offsetY,
                        $width > 0 ? $width : $this->_imageWidth,
                        $height > 0 ? $height : $this->_imageheight,
                        $label,
                        $this->_textBelowImageHeight,
                        $this->_textBelowImageMarign
                    );
                }

                $count++;

                // Create new slide if count reaches
                if ($this->_noOfImagesInSlide == $count && $index < (count($arrImages) - 1)) {
                    // Reset index to 0
                    $count = 0;

                    // Create new slide
                    $this->createSlide();

                    // Add header
                    if ($this->_headerText) {
                        $this->addHeader($this->_headerText);
                    }
                }
            }
        }
    }

    final public function addImage($file, $offsetX = 0, $imageOffsetY = 100, $width = 250, $imgHeight = 250, $textBelowImage = "", $textHeight = 0, $textMarginY = 0, $textAlignCenter = false, $fontSize = 20, $color = 'C000C0')
    {
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $this->addImageFromUrl($file, $offsetX, $imageOffsetY, $width, $imgHeight);
        } else {
            $shape = $this->_currentSlide->createDrawingShape();
            $shape->setPath($file)
                ->setWidth($width)
                ->setHeight($imgHeight)
                ->setOffsetX($offsetX)
                ->setOffsetY($imageOffsetY);
        }

        if ($textBelowImage) {
            $this->addText($textBelowImage, $textHeight, $width, $offsetX, $imageOffsetY + $imgHeight + $textMarginY, $textAlignCenter, $fontSize, $color);
        }
    }

    final public function addImageFromUrl($file, $offsetX = 0, $imageOffsetY = 100, $width = 250, $imgHeight = 250)
    {
        $shape = new Drawing\Base64();
        $imageData = "data:image/jpeg;base64," . base64_encode(file_get_contents($file));

        $shape->setData($imageData)
            ->setResizeProportional(false)
            ->setHeight($imgHeight)
            ->setWidth($width)
            ->setOffsetX($offsetX)
            ->setOffsetY($imageOffsetY);
        $this->_currentSlide->addShape($shape);
    }

    final public function addTable(
        array $tableData,
        int $rows,
        int $cols,
        int $offsetX = 0,
        int $offsetY = 0,
        int $tableWidth = 1000,
        int $tableHeight = 300,
        array $columnWidths = [],
        int $fontSize = 14,
        string $textColor = 'FF000000',
        string $headerBackgroundColor = 'FF5733' // Replace this with the actual color code from the Fena Dish Bar cover
    ) {
        // Create table shape
        $table = $this->_currentSlide->createTableShape($cols);

        // Set table position
        $table->setHeight($tableHeight)
            ->setWidth($tableWidth)
            ->setOffsetX($offsetX)
            ->setOffsetY($offsetY);

        // Set custom column widths if provided
        if (!empty($columnWidths) && count($columnWidths) === $cols) {
            foreach ($columnWidths as $index => $width) {
                $table->getColumn($index)->setWidth($width);
            }
        }

        // Add rows and populate cells with data
        for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
            $row = $table->createRow();

            for ($colIndex = 0; $colIndex < $cols; $colIndex++) {
                $cellText = isset($tableData[$rowIndex][$colIndex]) ? $tableData[$rowIndex][$colIndex] : '';
                $cell = $row->getCell($colIndex);

                // Set the text for the cell
                $textRun = $cell->createTextRun($cellText);
                $textRun->getFont()->setSize($fontSize);

                // Check if it's the first row (header)
                if ($rowIndex === 0) {
                    // Set background color for the header row (Fena Dish Bar cover color)
                    $cell->getFill()->setFillType(Fill::FILL_SOLID)
                        ->setStartColor(new Color($headerBackgroundColor));

                    // Set text color to white
                    $textRun->getFont()->setColor(new Color('FFFFFFFF')); // White text
                } else {
                    // Set normal text color for other rows
                    $textRun->getFont()->setColor(new Color($textColor));
                }

                // Center align text in each cell
                $cell->getActiveParagraph()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
    }

    // Get correct image from array of images
    final public function getCorrectImage($arrImages, $imgId)
    {
        $imgIndex = array_search($imgId, array_column($arrImages, "mob_img_id"));
        if ($imgIndex !== false) {
            return $arrImages[$imgIndex];
        }
        return null;
    }

    final public function savePpt(string $fileName, bool $isDownloadFile = true)
    {
        if (!file_exists($GLOBALS["SAVE_SPREADSHEET_PATH"])) {
            mkdir($GLOBALS["SAVE_SPREADSHEET_PATH"], 0777, true);
        }
        $savePath = $GLOBALS["SAVE_SPREADSHEET_PATH"] . "/$fileName";
        $downloadUrl = $GLOBALS["SAVE_SPREADSHEET_URL"] . "/$fileName";

        $oWriterPPTX = IOFactory::createWriter($this->_objPHPPowerPoint, 'PowerPoint2007');
        $oWriterPPTX->save($savePath);

        if ($isDownloadFile) {
            header("Location: $downloadUrl");
        } else {
            $fileDetails = array(
                "downloadUrl" => $downloadUrl,
                "savePath" => $savePath,
            );

            return $fileDetails;
        }
    }
}
