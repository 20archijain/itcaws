<?php

// phpcs:ignore
class UploadAndThumbnail
{
    //Properties for Upload
    protected $filename; //File name to be return after upload
    protected $origFilename; //Original file name
    protected $destination; //set the destination of upload file
    protected $typeCheckingOn = true; //control whether the MIME type should be checked
    protected $max; //Maximum file size in bytes
    protected $messages = []; //Messages to report the status of uploads
    protected $status = 0; //Status to report the status of uploads, 0 means success

    //List of Permitted/allowable MIME types
    protected $permitted = [
        'image/gif',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    protected $notTrusted = ['bin', 'cgi', 'exe', 'js', 'pl', 'php', 'py', 'sh', 'html', 'htm', 'css', 'mp3', 'mp4']; //defines an array of filename extensions that are potentially unsafe
    protected $suffix = '.upload'; //sets the default suffix that will be appended to the filename of risky files
    protected $newName; //store the file’s new name if it is changed
    protected $renameDuplicates; //Renaming duplicate files

    //Properties for thumnail
    protected $genThumb = false; //Generate Thumbnail or not
    protected $original;
    protected $originalwidth;
    protected $originalheight;
    protected $basename;
    protected $thumbwidth;
    protected $thumbheight;
    protected $maxSize; //maximum size of the thumbnail’s longer dimension
    protected $canProcess = false; //prevent the script from attempting to process a file that isn’t an image
    protected $imageType;
    protected $thumbdestination;
    protected $thumbsuffix = 'thumb_';

    //Checks for valid directory (folder) that is writable and set the max upload file size ig given
    public function __construct($path, $max = MAX_SIZE_IN_BYTES)
    {
        if (!is_dir($path) || !is_writable($path)) {
            throw new \Exception("Directory must be a valid, writable directory. Provided directoy is: $path"); //backslash in front of Exception indicates that a core PHP command is to be used rather than one defined within the namespace.
        }
        $this->destination = $path;
        if (is_numeric($max) && $max > 0) {
            $this->max = (int) $max;
        }
    }

    public function setPermittedFileTypes($permitted)
    {
        $this->permitted = $permitted;
    }

    //Return file name of uploaded file
    public function getFileName()
    {
        return $this->filename;
    }

    //Return original file name of uploaded file
    public function getOrigFileName()
    {
        return $this->origFilename;
    }

    //Generate Thumbnail or not
    public function genThumbnail($thumbdestination = '', $size = UPLOAD_THUMBNAIL_SIZE_IN_PX)
    {
        $allow = true;
        if (is_numeric($size) && $size > 0) {
            $this->maxSize = abs($size);
        } else {
            $this->messages[] = 'Thumbnail size is invalid.';
            $allow = false;
            $this->canProcess = false;
        }

        if (!empty($thumbdestination)) {
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
                $this->messages[] = "Cannot write to $thumbdestination.";
            }
        } else {
            $this->thumbdestination = $this->destination;
        }

        if ($allow) {
            $this->genThumb = true;
        }
    }

    //set the value of $typeCheckingOn to false
    public function allowAllTypes($suffix = true)
    {
        $this->typeCheckingOn = false;

        if (!$suffix) {
            $this->suffix = ''; // make the suffix optional
        }
    }

    //Upload file
    public function upload($files, $newName = "", $renameDuplicates = true)
    {
        $this->renameDuplicates = $renameDuplicates; //sets whether to rename file if already exists, default is true

        $uploaded = $files;

        //Check for multiple uploads
        if (is_array($uploaded['name'])) {
            // deal with multiple uploads
            foreach ($uploaded['name'] as $key => $value) {
                $currentFile['name'] = $uploaded['name'][$key];
                $currentFile['type'] = $uploaded['type'][$key];
                $currentFile['tmp_name'] = $uploaded['tmp_name'][$key];
                $currentFile['error'] = $uploaded['error'][$key];
                $currentFile['size'] = $uploaded['size'][$key];

                if ($this->checkFile($currentFile, $newName)) {
                    $this->moveFile($currentFile);
                }
            }
        } else {
            if ($this->checkFile($uploaded, $newName)) { //check the file to passes the series of tests before upload
                $this->moveFile($uploaded); //Upload the file
            }
        }
    }

    //Check the error level, the size of the file, and the file’s MIME type
    protected function checkFile($file, $newName)
    {
        $accept = true;

        //Check if no error in uploaded file, 0 means upload success
        if ($file['error'] != 0) {
            $this->getErrorMessage($file);

            // stop checking if no file submitted
            if ($file['error'] == 4) {
                $this->status = 1;
                return false;
            } else {
                $accept = false;
            }
        }

        if (!$this->checkSize($file)) {
            $accept = false;
        }

        if ($this->typeCheckingOn) {
            if (!$this->checkType($file)) {
                $accept = false;
            }
        }

        if ($accept) {
            $this->checkName($file, $newName);
        } else {
            $this->status = 1;
        }
        return $accept;
    }

    protected function getErrorMessage($file)
    {
        switch ($file['error']) {
            case 1:
            case 2:
                $this->messages[] = $file['name'] . ' is too big: (max: ' . $this->getMaxSize() . ').';
                break;
            case 3:
                $this->messages[] = $file['name'] . ' was only partially uploaded.';
                break;
            case 4:
                $this->messages[] = 'No file submitted.';
                break;
            default:
                $this->messages[] = 'Sorry, there was a problem uploading ' . $file['name'];
                break;
        }
    }

    protected function checkSize($file)
    {
        if ($file['error'] == 1 || $file['error'] == 2) {
            return false;
        } elseif ($file['size'] == 0) {
            $this->messages[] = $file['name'] . ' is an empty file.';
            return false;
        } elseif ($file['size'] > $this->max) {
            $this->messages[] = $file['name'] . ' exceeds the maximum size for a file (' . $this->getMaxSize() . ').';
            return false;
        } else {
            return true;
        }
    }

    public function getMaxSize()
    {
        return number_format($this->max / 1024, 1) . ' KB';
    }

    protected function checkType($file)
    {
        if (in_array($file['type'], $this->permitted)) {
            return true;
        } else {
            if (!empty($file['type'])) {
                $this->messages[] = $file['name'] . ' is not permitted type of file.';
            }
            return false;
        }
    }

    protected function checkName($file, $newName)
    {
        $this->newName = null;

        $nospaces = str_replace(' ', '_', $file['name']);

        if ($nospaces != $file['name']) {
            $this->newName = $nospaces;
        }

        //extract the filename extension to determine if a file is potentially unsafe.
        $extension = pathinfo($nospaces, PATHINFO_EXTENSION); //Returns information about a file path either an associative array or a string, depending on options.

        //add the suffix only if the $typeCheckingOn property is false and the $suffix property is not an empty string.
        if (!$this->typeCheckingOn && !empty($this->suffix)) {
            if (in_array($extension, $this->notTrusted) || empty($extension)) {
                $this->newName = $nospaces . $this->suffix;
            }
        }

        //new name is used passed in upload()
        if ($newName != "") {
            $this->newName = $newName . "." . $extension;
        }

        // rename file if $renameDuplicates property is true
        if ($this->renameDuplicates) {
            $name = isset($this->newName) ? $this->newName : $file['name'];
            $existing = scandir($this->destination); //List files and directories inside the specified path

            if (in_array($name, $existing)) {
                $basename = pathinfo($name, PATHINFO_FILENAME); //get the base name/file name w/o extension
                $extension = pathinfo($name, PATHINFO_EXTENSION); //get the extension
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

    protected function moveFile($file)
    {
        //checks if the $newName property has been set by the checkName() method. If it has, the new name is used.
        $filename = isset($this->newName) ? $this->newName : $file['name'];

        //store original file name
        $this->origFilename = $file['name'];

        $success = move_uploaded_file($file['tmp_name'], $this->destination . $filename);
        if ($success) {
            //Generate Thumbnail if $genThumb is true
            if ($this->genThumb) {
                $this->checkImg($this->destination . $filename);
            }
            $result = 'File was uploaded successfully';
            $this->messages[] = $result;
            $this->filename = $filename;
        } else {
            $this->status = 1;
            $this->messages[] = 'Could not upload ' . $file['name'];
        }
    }

    //checks that $image is a file and is readable.
    protected function checkImg($image)
    {
        if (is_file($image) && is_readable($image)) {
            // getimagesize() returns an array containing the following elements:
            // Width (in pixels), 1: Height, 2: An integer indicating the type of image
            // A string containing the correct width and height attributes ready for insertion in an <img> tag
            // The image’s MIME type, channels: 3 for RGB and 4 for CMYK images, bits: The number of bits for each color
            $details = getimagesize($image);
        } else {
            $details = null;
            $this->messages[] = "Cannot open $image.";
        }

        // if getimagesize() returns an array, it looks like an image
        if (is_array($details)) {
            $this->original = $image;
            $this->originalwidth = $details[0];
            $this->originalheight = $details[1];
            $this->basename = pathinfo($image, PATHINFO_FILENAME);
            // check the MIME type
            $this->checkImgType($details['mime']);
        } else {
            $this->messages[] = "$image doesn't appear to be an image.";
        }
    }

    protected function checkImgType($mime)
    {
        $mimetypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($mime, $mimetypes)) {
            $this->canProcess = true;
            // extract the characters after 'image/'
            $this->imageType = substr($mime, 6);
            $this->create();
        }
    }

    protected function create()
    {
        if ($this->canProcess && $this->originalwidth != 0) {
            $this->calculateSize($this->originalwidth, $this->originalheight);
            $this->createThumbnail();
        } elseif ($this->originalwidth == 0) {
            $this->messages[] = 'Cannot determine size of ' . $this->original;
        }
    }

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

    protected function createThumbnail()
    {
        $resource = $this->createImageResource();
        $thumb = imagecreatetruecolor($this->thumbwidth, $this->thumbheight);

        imagecopyresampled($thumb, $resource, 0, 0, 0, 0, $this->thumbwidth, $this->thumbheight, $this->originalwidth, $this->originalheight);
        $newname = $this->thumbsuffix . $this->basename;

        if ($this->imageType == 'jpeg') {
            $newname .= '.jpg';
            $success = imagejpeg($thumb, $this->thumbdestination . $newname, 100);
        } elseif ($this->imageType == 'png') {
            $newname .= '.png';
            $success = imagepng($thumb, $this->thumbdestination . $newname, 0);
        } elseif ($this->imageType == 'gif') {
            $newname .= '.gif';
            $success = imagegif($thumb, $this->thumbdestination . $newname);
        }
        if ($success) {
            $this->messages[] = "$newname created successfully.";
        } else {
            $this->messages[] = "Couldn't create a thumbnail for " .
                basename($this->original);
        }
        imagedestroy($resource);
        imagedestroy($thumb);
    }

    protected function createImageResource()
    {
        if ($this->imageType == 'jpeg') {
            return imagecreatefromjpeg($this->original);
        } elseif ($this->imageType == 'png') {
            return imagecreatefrompng($this->original);
        } elseif ($this->imageType == 'gif') {
            return imagecreatefromgif($this->original);
        }
    }

    public function getMessages()
    {
        return ["status" => $this->status, "messages" => $this->messages];
    }
}
