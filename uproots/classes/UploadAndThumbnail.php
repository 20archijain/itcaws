<?php

// phpcs:ignore
class UploadAndThumbnail
{
    // A file looks like this:
    // [name] => name.gif
    // [type] => image/gif
    // [tmp_name] => C:\xampp\tmp\php7CB4.tmp
    // [error] => 0
    // [size] => 1160068

    public $messages = []; //Messages to report the status of uploads
    public $status = 0; //Status to report the status of uploads, 0 means success, 1 means error

    // Properties for Upload
    protected $filename; // File name to be return after upload
    protected $extension; // Extension of the uploaded file without dot
    protected $origFilename; // Original file name
    protected $destination; // set the destination of upload file without file name
    protected $typeCheckingOn = true; //control whether the MIME type should be checked
    protected $max; //Maximum file size in bytes
    protected $arrExtensionsAsPerMimetypes = array(
        "image/jpeg" => array(".jpg", ".jpeg"),
        "image/png" => array(".png"),
        "image/gif" => array(".gif"),
        "application/pdf" => array(".pdf"),
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => array(".xlsx"),
        "application/vnd.ms-excel" => array(".xls"),
        "text/csv" => array(".csv"),
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => array(".docx"),
        "application/msword" => array(".doc"),
    );

    // List of Permitted/allowable MIME types, Default is image only
    protected $permitted = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];
    protected $notTrusted = ['bin', 'cgi', 'exe', 'js', 'pl', 'php', 'py', 'sh', 'html', 'htm', 'css', 'mp3', 'mp4']; //defines an array of filename extensions that are potentially unsafe
    protected $suffix = '.upload'; //sets the default suffix that will be appended to the filename of risky files
    protected $newName; //store the file’s new name if it is changed
    protected $renameDuplicates; //Renaming duplicate files

    // Properties for watermark
    protected $showWatermark = false;
    protected $watermarkText = '';
    protected $watermarkPosition = 'top';   // can be top, bottom, left, right, centerHorizontal
    protected $arrTextcolor = array();
    protected $createAndfillRectangle = false;
    protected $arrRectangleBgColor = array();
    protected $createRedRectangularBorder = true;
    protected $maxFontSize = 40;

    // Properties for thumbnail
    protected $genThumb = false; //Generate Thumbnail or not
    protected $original;
    protected $originalwidth;
    protected $originalheight;
    protected $basename;
    protected $thumbwidth;
    protected $thumbheight;
    protected $maxSize; // maximum size (size means same height and width) of the thumbnail’s longer dimension
    protected $imageType;
    protected $thumbFilename; // thumbnail File name to be return after upload
    protected $thumbdestination;    // set the destination of upload thumbnail without file name
    protected $thumbPrefix = 'thumb_';

    // Checks for valid directory (folder) that is writable and set the max upload file size ig given
    public function __construct($path = "", $max = MAX_SIZE_IN_BYTES)
    {
        if ($path && !file_exists($path)) {
            @mkdir($path, 0777, true);
        }

        if ($path && (!is_dir($path) || !is_writable($path))) {
            throw new \Exception("Directory must be a valid, writable directory"); //backslash in front of Exception indicates that a core PHP command is to be used rather than one defined within the namespace.
        }
        $this->destination = $path;
        if (is_numeric($max) && $max > 0) {
            $this->max = (int) $max;
        }
    }

    // Set allowed types of files
    final public function setPermittedFileTypes($arrPermittedMimeTypes)
    {
        $this->permitted = is_array($arrPermittedMimeTypes) ? $arrPermittedMimeTypes : array($arrPermittedMimeTypes);
    }

    // Return file name of uploaded file
    final public function getFileName()
    {
        return $this->filename;
    }

    // Return original file name of uploaded file
    final public function getOrigFileName()
    {
        return $this->origFilename;
    }

    // Allow all mimetypes i.e set the value of $typeCheckingOn to false
    final public function allowAllTypes($suffix = true)
    {
        $this->typeCheckingOn = false;

        if (!$suffix) {
            $this->suffix = ''; // make the suffix optional
        }
    }

    // Set watermark properties
    final public function setWatermark($showWatermark, $watermarkText, $arrWatermarkConfig = array())
    {
        $this->showWatermark = $showWatermark;
        $this->watermarkText = $watermarkText;

        if (isset($arrWatermarkConfig) && $arrWatermarkConfig) {
            if (isset($arrWatermarkConfig["watermarkPosition"]) && $arrWatermarkConfig["watermarkPosition"]) {
                $this->watermarkPosition = $arrWatermarkConfig["watermarkPosition"];
            }
            if (isset($arrWatermarkConfig["arrTextcolor"]) && $arrWatermarkConfig["arrTextcolor"]) {
                $this->arrTextcolor = $arrWatermarkConfig["arrTextcolor"];
            }
            if (isset($arrWatermarkConfig["createAndfillRectangle"]) && $arrWatermarkConfig["createAndfillRectangle"]) {
                $this->createAndfillRectangle = $arrWatermarkConfig["createAndfillRectangle"];
            }
            if (isset($arrWatermarkConfig["arrRectangleBgColor"]) && $arrWatermarkConfig["arrRectangleBgColor"]) {
                $this->arrRectangleBgColor = $arrWatermarkConfig["arrRectangleBgColor"];
            }
            if (isset($arrWatermarkConfig["createRedRectangularBorder"]) && $arrWatermarkConfig["createRedRectangularBorder"]) {
                $this->createRedRectangularBorder = $arrWatermarkConfig["createRedRectangularBorder"];
            }
            if (isset($arrWatermarkConfig["maxFontSize"]) && $arrWatermarkConfig["maxFontSize"]) {
                $this->maxFontSize = $arrWatermarkConfig["maxFontSize"];
            }
        }
    }

    final public function getMessages()
    {
        return array("status" => $this->status, "messages" => $this->messages);
    }

    // Set to generate Thumbnail
    final public function genThumbnail($thumbdestination = '', $size = UPLOAD_THUMBNAIL_SIZE_IN_PX)
    {
        $allow = true;
        if (is_numeric($size) && $size > 0) {
            $this->maxSize = abs($size);
        } else {
            $this->status = 1;
            $this->messages[] = 'Thumbnail size is invalid';
            $allow = false;
        }

        // Set custom destination for thumbnail
        if ($thumbdestination) {
            if (is_dir($thumbdestination) && is_writable($thumbdestination)) {
                // get last character
                $last = substr($thumbdestination, -1);

                // add a trailing slash if missing
                if ($last == '/' || $last == '\\') {
                    $this->thumbdestination = $thumbdestination;
                } else {
                    $this->thumbdestination = $thumbdestination . DIRECTORY_SEPARATOR;
                }
            } else {
                $this->messages[] = "Cannot write thumbnail to $thumbdestination";
                $this->status = 1;
                $allow = false;
            }
        } else {
            // Set same destination as actual image for thumbnail
            $this->thumbdestination = $this->destination;
        }

        if ($allow) {
            $this->genThumb = true;
        }
    }

    // Do prechecks and Upload file
    final public function upload($files, $newNameWithoutExtension = "", $label = "file", $renameDuplicates = true)
    {
        // All valid
        if ($this->status == 0) {
            $this->renameDuplicates = $renameDuplicates; //sets whether to rename file if already exists, default is true

            $uploaded = $files;

            // Check for multiple uploads
            if (is_array($uploaded['name'])) {
                // deal with multiple uploads
                foreach ($uploaded['name'] as $key => $value) {
                    $currentFile['name'] = $uploaded['name'][$key];
                    $currentFile['type'] = $uploaded['type'][$key];
                    $currentFile['tmp_name'] = $uploaded['tmp_name'][$key];
                    $currentFile['error'] = $uploaded['error'][$key];
                    $currentFile['size'] = $uploaded['size'][$key];

                    if ($this->checkFile($currentFile, $label, $newNameWithoutExtension, true)) {
                        $this->moveFile($currentFile);
                    }
                }
            } else {
                if ($this->checkFile($uploaded, $label, $newNameWithoutExtension, true)) { //check the file to passes the series of tests before upload
                    $this->moveFile($uploaded); //Upload the file
                }
            }
        }
    }

    // Check the error level, the size of the file, and the file’s MIME type
    final public function checkFile($file, $label, $newNameWithoutExtension = "", $checkName = false)
    {
        $accept = true;

        // Check if no error in uploaded file, UPLOAD_ERR_OK i.e 0 means There is no error, the file uploaded with success
        if ($file['error'] != UPLOAD_ERR_OK) {
            $this->getErrorMessage($file);
            $accept = false;
        }

        // Check if uploaded file is not bigger than allowed size
        if ($accept && !$this->checkSize($file, $label)) {
            $accept = false;
        }

        // Check if uploaded file is not other than allowed mime types
        if ($accept && $this->typeCheckingOn && !$this->checkType($file, $label)) {
            $accept = false;
        }

        // Get new name of uploaded file
        if ($accept) {
            if ($checkName) {
                $this->checkName($file, $newNameWithoutExtension);
            }
        } else {
            $this->status = 1;
        }

        return $accept;
    }

    // Get error messages in a uploaded file
    protected function getErrorMessage($file)
    {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                // The uploaded file exceeds the upload_max_filesize directive in php.ini
            case UPLOAD_ERR_FORM_SIZE:
                // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
                $this->messages[] = $file['name'] . ' is too big: (max: ' . $this->getMaxSize() . ')';
                break;
            case UPLOAD_ERR_PARTIAL:
                // The uploaded file was only partially uploaded.
                $this->messages[] = $file['name'] . ' was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                // No file was uploaded.
                $this->messages[] = 'No file was uploaded';
                break;
            default:
                $this->messages[] = 'Sorry, there was a problem uploading ' . $file['name'];
                break;
        }
    }

    // Check for file size
    protected function checkSize($file, $label)
    {
        if ($file['size'] == 0) {
            $this->messages[] = $file['name'] . ' is an empty file';
            return false;
        } elseif ($file['size'] > $this->max) {
            $this->messages[] = "Maximum file size for $label is " . $this->getMaxSize();
            return false;
        } else {
            return true;
        }
    }

    // Get allowed filesize in KB/MB/GB/TB
    public function getMaxSize($decimals = 2)
    {
        // return round($this->max / (1024 * 1024), 2) . ' MB';
        $factor = floor((strlen($this->max) - 1) / 3);
        if ($factor > 0) {
            $sz = 'KMGT';
        }

        return sprintf("%.{$decimals}f", $this->max / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
    }

    // Check for file type
    protected function checkType($file, $label)
    {
        if (in_array($file['type'], $this->permitted)) {
            return true;
        } else {
            $this->messages[] = "Allowed file types for $label are " . $this->getAllowedExtensions();
            return false;
        }
    }

    // Get allowed mimetypes file extentions
    protected function getAllowedExtensions()
    {
        $arrAllowedExtensions = array();

        foreach ($this->permitted as $permitted) {
            $permittedMimetypeExtensions = $this->arrExtensionsAsPerMimetypes[$permitted];
            $arrAllowedExtensions = array_merge($arrAllowedExtensions, $permittedMimetypeExtensions);
        }

        return implode(", ", $arrAllowedExtensions);
    }

    // Get new name of uploaded file i.e replace space and rename if duplicate file
    protected function checkName($file, $newNameWithoutExtension)
    {
        $this->newName = null;

        $nospaces = str_replace(' ', '_', $file['name']);

        if ($nospaces != $file['name']) {
            $this->newName = $nospaces;
        }

        // extract the filename extension to determine if a file is potentially unsafe.
        $extension = $this->getExtension($nospaces); //Returns information about a file path either an associative array or a string, depending on options.
        $this->extension = $extension;

        // add the suffix only if the $typeCheckingOn property is false and the $suffix property is not an empty string.
        if (!$this->typeCheckingOn && !empty($this->suffix)) {
            if (in_array($extension, $this->notTrusted) || empty($extension)) {
                $this->newName = $nospaces . $this->suffix;
            }
        }

        // new name is used passed in upload()
        if ($newNameWithoutExtension) {
            $this->newName = $newNameWithoutExtension . "." . $extension;
        }

        // rename file if $renameDuplicates property is true
        if ($this->renameDuplicates) {
            $name = isset($this->newName) ? $this->newName : $file['name'];
            $existing = scandir($this->destination); //List files and directories inside the specified path

            if (in_array($name, $existing)) {
                $basename = pathinfo($name, PATHINFO_FILENAME); //get the base name/file name w/o extension
                $extension = $this->getExtension($name); //get the extension
                $i = 1;
                //builds the new name
                do {
                    $this->newName = $basename . '_' . $i++;
                    if (!empty($extension)) {
                        $this->newName .= ".$extension";
                    }
                } while (in_array($this->newName, $existing));
            }
        }
    }

    // gets the extension of the file
    protected function getExtension($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    // Upload file
    protected function moveFile($file)
    {
        // checks if the $newName property has been set by the checkName() method. If it has, the new name is used.
        $filename = isset($this->newName) ? $this->newName : $file['name'];

        // store original file name
        $this->origFilename = $file['name'];

        $success = move_uploaded_file($file['tmp_name'], $this->destination . $filename);
        if ($success) {
            $this->filename = $filename;

            // Add watermark
            if ($this->showWatermark) {
                $this->addWatermark($this->destination . $filename);
            }

            // Generate Thumbnail if $genThumb is true
            $thumbnailSuccess = true;
            if ($this->genThumb) {
                $thumbnailSuccess = $this->checkImg($this->destination . $filename);
            }

            if ($thumbnailSuccess) {
                $this->messages[] = 'File was uploaded successfully';
            } else {
                $this->messages[] = 'File was uploaded successfully but thumbnail was not uploaded';
            }
        } else {
            $this->status = 1;
            $this->messages[] = 'Could not upload ' . $file['name'];
        }
    }

    // Note: All Below methods are used for thumbnail

    // checks that $image is a file and is readable.
    protected function checkImg($image)
    {
        if (is_file($image) && is_readable($image)) {
            // getimagesize() returns an array containing the following elements:
            // Width (in pixels), 1: Height, 2: An integer indicating the type of image
            // A string containing the correct width and height attributes ready for insertion in an <img> tag
            // The image’s MIME type, channels: 3 for RGB and 4 for CMYK images, bits: The number of bits for each color
            // eg: Array
            // (
            //     [0] => 2818
            //     [1] => 1080
            //     [2] => 3
            //     [3] => width="2818" height="1080"
            //     [bits] => 8
            //     [mime] => image/png
            // )
            $details = getimagesize($image);
        } else {
            $this->messages[] = "Cannot open $image for thumbnail upload";
            return false;
        }

        // if getimagesize() returns an array, it looks like an image
        if (is_array($details)) {
            $this->original = $image;
            $this->originalwidth = $details[0];
            $this->originalheight = $details[1];
            $this->basename = pathinfo($image, PATHINFO_FILENAME);

            // check the MIME type
            return $this->checkImgType($details['mime']);
        } else {
            $this->messages[] = "Failed to get size of $image";
            return false;
        }
    }

    // Check if uploaded file is image or not and create thumbnail if image
    protected function checkImgType($mime)
    {
        $mimetypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($mime, $mimetypes)) {
            // extract the characters after 'image/'
            $this->imageType = substr($mime, 6);
            return $this->create();
        } else {
            $this->messages[] = "Cannot create thumbnail of {$this->original}";
            return false;
        }
    }

    // calculate size of thumbnail and create thumbnail
    protected function create()
    {
        if ($this->originalwidth != 0) {
            $this->calculateSize($this->originalwidth, $this->originalheight);
            return $this->createThumbnail();
        } elseif ($this->originalwidth == 0) {
            $this->messages[] = 'Cannot determine size of ' . $this->original;
            return false;
        }
    }

    // calculate size of thumbnail
    protected function calculateSize($width, $height)
    {
        if ($width <= $this->maxSize && $height <= $this->maxSize) {
            $ratio = 1;
        } elseif ($width > $height) {
            $ratio = $this->maxSize / $width;
        } else {
            $ratio = $this->maxSize / $height;
        }
        $this->thumbwidth = round($width * $ratio);
        $this->thumbheight = round($height * $ratio);
    }

    // create thumbnail
    protected function createThumbnail()
    {
        $success = false;
        $resource = false;

        // Create a new true color image i.e an image object representing a black image of the specified size
        $thumb = imagecreatetruecolor($this->thumbwidth, $this->thumbheight);

        try {
            $newname = $this->thumbPrefix . $this->basename;
            $this->thumbFilename = $newname;

            if ($this->imageType == 'jpeg') {
                $newname .= '.' . $this->getExtension($this->original);

                $imageType = 1;
                $resource = $this->createImageResource();

                // image not created, try png
                if ($resource === false) {
                    $imageType = 2;

                    $resource = @imagecreatefrompng($this->original);
                }

                if ($resource !== false) {
                    // Copy and resize part of an image with resampling i.e copies a rectangular portion of one image to another image
                    imagecopyresampled($thumb, $resource, 0, 0, 0, 0, $this->thumbwidth, $this->thumbheight, $this->originalwidth, $this->originalheight);

                    if ($imageType === 1) {
                        // saves a JPEG image from the given image
                        $success = imagejpeg($thumb, $this->thumbdestination . $newname, 100);
                    } else {
                        // saves a PNG image from the given image
                        $success = imagepng($thumb, $this->thumbdestination . $newname, 0);
                    }
                }
            } elseif ($this->imageType == 'png') {
                $newname .= '.png';
                $resource = $this->createImageResource();

                if ($resource !== false) {
                    // Copy and resize part of an image with resampling i.e copies a rectangular portion of one image to another image
                    imagecopyresampled($thumb, $resource, 0, 0, 0, 0, $this->thumbwidth, $this->thumbheight, $this->originalwidth, $this->originalheight);

                    // saves a PNG image from the given image
                    $success = imagepng($thumb, $this->thumbdestination . $newname, 0);
                }
            } elseif ($this->imageType == 'gif') {
                $newname .= '.gif';
                // Create animated gif if ImageMagick extension is installed and enabled
                if (extension_loaded('imagick')) {
                    $success = $this->createAnimatedGifThumbnail($this->thumbdestination . $newname);
                } else {
                    // Create static gif as ImageMagick extension is not installed or not enabled

                    $resource = $this->createImageResource();

                    if ($resource !== false) {
                        // Copy and resize part of an image with resampling i.e copies a rectangular portion of one image to another image
                        imagecopyresampled($thumb, $resource, 0, 0, 0, 0, $this->thumbwidth, $this->thumbheight, $this->originalwidth, $this->originalheight);

                        // saves a GIF image from the given image
                        $success = imagegif($thumb, $this->thumbdestination . $newname);
                    }
                }
            }

            if ($success) {
                $this->messages[] = "$newname created successfully";
            } else {
                $this->messages[] = "Couldn't create a thumbnail for " . basename($this->original);
            }
        } catch (Exception $e) {
        }

        if ($resource !== false) {
            imagedestroy($resource);
        }
        if ($thumb !== false) {
            imagedestroy($thumb);
        }

        return $success;
    }

    // get new image identifier
    protected function createImageResource()
    {
        // Create a new image i.e an image identifier representing the image obtained from the given filename

        if ($this->imageType == 'jpeg') {
            return @imagecreatefromjpeg($this->original);
        } elseif ($this->imageType == 'png') {
            return @imagecreatefrompng($this->original);
        } elseif ($this->imageType == 'gif') {
            return @imagecreatefromgif($this->original);
        }
    }

    // Create animated gif thumbnail
    protected function createAnimatedGifThumbnail($thumbnail)
    {
        // Create an Imagick object from the source GIF
        $imagick = new Imagick($this->original);
        $imagick = $imagick->coalesceImages();

        // Ensure that we are dealing with an animated GIF
        if (!$imagick->getNumberImages()) {
            throw new Exception('Source GIF does not contain any frames.');
        }

        do {
            $imagick->resizeImage($this->thumbwidth, $this->thumbheight, Imagick::FILTER_BOX, 1);
        } while ($imagick->nextImage());

        $imagick = $imagick->deconstructImages();
        $success = $imagick->writeImages($thumbnail, true);

        // Clean up resources
        $imagick->clear();
        $imagick->destroy();

        return $success;
    }

    // Add watermark
    protected function addWatermark($source)
    {
        global $WATERMARK_FONTS_PATH, $ARR_WATERMARK_POSITION;

        $watermarkText = $this->watermarkText;
        $watermarkPosition = $this->watermarkPosition;
        $arrTextcolor = $this->arrTextcolor;
        $createAndfillRectangle = $this->createAndfillRectangle;
        $arrRectangleBgColor = $this->arrRectangleBgColor;
        $createRedRectangularBorder = $this->createRedRectangularBorder;
        $maxFontSize = $this->maxFontSize;

        list($width_orig, $height_orig) = getimagesize($source);
        $imageType = 1;

        // Create a new image i.e an image identifier representing the image obtained from the given filename
        $image = @imagecreatefromjpeg($source);

        // Not a JPG or JPEG file, create png file
        if ($image === false) {
            $imageType = 2;
            // Create a new image i.e an image identifier representing the image obtained from the given filename
            $image = @imagecreatefrompng($source);
        }

        // Not a PNG file either, create gif file
        if ($image === false) {
            $imageType = 3;
            // Create a new image i.e an image identifier representing the image obtained from the given filename
            $image = @imagecreatefromgif($source);
        }

        if ($image !== false) {
            // Allocate a text color in an image
            if (!($arrTextcolor && isset($arrTextcolor[0]))) {
                // Default text color is red
                $arrTextcolor = array(255, 0, 0);
            }
            $textcolor = imagecolorallocate($image, $arrTextcolor[0], $arrTextcolor[1], $arrTextcolor[2]);
            $font_file = $WATERMARK_FONTS_PATH . "/monofont.ttf";
            $angle = 0;
            if (
                $watermarkPosition == $ARR_WATERMARK_POSITION["LEFT"] ||
                $watermarkPosition == $ARR_WATERMARK_POSITION["RIGHT"]
            ) {
                $angle = 270;
            }

            $fontSize = 0;
            // Calculate max font size as per image size and watermark text
            $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);
            while (($box['width'] < $width_orig) && ($box['height'] < $height_orig) && ($fontSize <= $maxFontSize)) {
                $fontSize++;
                $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);
            }

            // Reduce font by 2 to adjust properly
            $fontSize--;
            $fontSize--;
            $box = $this->calculateTextBox($fontSize, $angle, $font_file, $watermarkText);

            // Margin if any
            $leftMargin = 5;
            $topMargin = 5;
            $bottomMargin = 5;

            // TOP
            // Rectangle Box coordinates
            $boxX1 = $leftMargin;
            $boxY1 = $topMargin;
            $boxX2 = $box['width'] + $leftMargin + $leftMargin;
            $boxY2 = $box['height'] + $topMargin;

            // Text coordinates
            $textX = $leftMargin + $box['left'];
            $textY = $topMargin + $box['top'];

            // BOTTOM
            if ($watermarkPosition == $ARR_WATERMARK_POSITION["BOTTOM"]) {
                // Rectangle Box coordinates
                $boxY1 = $height_orig - $box['height'] - $bottomMargin;
                $boxY2 = $height_orig - $bottomMargin;

                // Text coordinates
                $textY = $height_orig + $box['top'] - $box['height'] - $bottomMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["LEFT"]) {
                // LEFT

                // Rectangle Box coordinates
                $boxX2 = $box['width'] + $leftMargin;
                $boxY2 = $topMargin + $box['height'] + $topMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["RIGHT"]) {
                // RIGHT
                // Rectangle Box coordinates
                $boxX1 = $width_orig - $box['width'] - $leftMargin;
                $boxX2 = $width_orig - $leftMargin;

                // Text coordinates
                $textX = $width_orig + $box['left'] - $box['width'] - $leftMargin;
            } elseif ($watermarkPosition == $ARR_WATERMARK_POSITION["CENTER_HORIZONTAL"]) {
                // Center Horizontal

                // Rectangle Box coordinates
                $boxX1 = ($width_orig - $box['width']) / 2;
                $boxY1 = ($height_orig / 2) - $box['height'] - $bottomMargin;
                $boxX2 = $boxX1 + $box['width'];
                $boxY2 = $boxY1 + $box['height'];

                // Text coordinates
                $textX = ($width_orig - $box['width']) / 2;
                $textY = ($height_orig / 2) - ($box['height'] / 2);
            }

            // Create rectangle around watermark text and fill it
            if ($createAndfillRectangle) {
                if ($createRedRectangularBorder) {
                    // Allocate a color for an image along with transparency
                    // create red rectangular border
                    $red = imagecolorallocatealpha($image, 255, 0, 0, 0);

                    // creates a rectangle starting at the specified coordinates.
                    imagerectangle($image, $boxX1, $boxY1, $boxX2, $boxY2, $red);
                }

                // fill bg color in rectange
                if (!($arrRectangleBgColor && isset($arrRectangleBgColor[0]))) {
                    // Default bg color is white
                    $arrRectangleBgColor = array(255, 255, 255, 20);
                }
                $rectangleBgColor = imagecolorallocatealpha(
                    $image,
                    $arrRectangleBgColor[0],
                    $arrRectangleBgColor[1],
                    $arrRectangleBgColor[2],
                    $arrRectangleBgColor[3]
                );

                // Creates a rectangle filled with color in the given image starting at point 1 and ending at point 2
                imagefilledrectangle($image, $boxX1, $boxY1, $boxX2, $boxY2, $rectangleBgColor);
            }

            // Writes the given text into the image (exclude .gif when ImageMagick extension is installed and enabled) using TrueType fonts
            if (!($imageType == 3 && extension_loaded('imagick'))) {
                imagettftext($image, $fontSize, $angle, $textX, $textY, $textcolor, $font_file, $watermarkText);
            }

            if ($imageType === 1) {
                // saves a JPG or JPEG image from the given image
                imagejpeg($image, $source);
            } elseif ($imageType === 2) {
                // saves a PNG image from the given image
                imagepng($image, $source);
            } else {
                // Add watermark on animated gif if ImageMagick extension is installed and enabled
                // To install ImageMagick on localhost, follow below steps:
                // 1. Open https://mlocati.github.io/articles/php-windows-imagick.html and download required zip file
                // 2. Extract zip in C:/, rename folder and copy "php_imagick.dll" in "C:\xampp\php\ext"
                // 3. Copy all files stating with CORE_RL and IM_MOD_RL in "C:\xampp\apache\bin"
                // 4. Open Environment variables and add "C:\<FOLDER_NAME>" in "Path" User variable
                // 5. Open php.ini and search "Dynamic Extensions" and add "extension=imagick" at the end of "Dynamic Extensions"
                // 6. To get support in VSC, open "Open Settings (UI)" and search "stubs" and add "imagick"
                if (extension_loaded('imagick')) {
                    // Creates an Imagick instance for a specified image
                    $imagick = new Imagick($source);
                    // Composites a set of images i.e this allows adding text in a gif
                    $imagick = $imagick->coalesceImages();

                    // Create a new drawing palette
                    $draw = new ImagickDraw();
                    $draw->setFont($font_file);
                    $draw->setFontSize($fontSize * 1.4);
                    $color = new ImagickPixel("rgb({$arrTextcolor[0]}, {$arrTextcolor[1]}, {$arrTextcolor[2]})");
                    $draw->setFillColor($color);
                    // Draw text on the image
                    $imagick->annotateImage($draw, $textX, $textY, 360 - $angle, $watermarkText);

                    // saves a GIF image from the given image
                    $imagick->writeImages($source, true);

                    // Clean up resources
                    $imagick->clear();
                    $imagick->destroy();
                } else {
                    // Add watermark on static gif if ImageMagick extension is not installed or not enabled
                    // saves a GIF image from the given image
                    imagegif($image, $source);
                }
            }

            // Destroy an image to freed any memory associated
            imagedestroy($image);
        }
    }

    // Calculates the *exact* bounding box of text to be written on image
    // returns an associative array with these keys:
    // left, top:  coordinates you will pass to imagettftext
    // width, height: dimension of the image you have to create
    protected function calculateTextBox($fontSize, $fontAngle, $fontFile, $text)
    {
        // calculates and returns the bounding box in pixels for a TrueType text
        $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

        return array(
            "left" => abs($minX),
            "top" => abs($minY),
            "width" => $maxX - $minX,
            "height" => $maxY - $minY,
        );
    }
}
