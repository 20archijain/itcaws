<?php

// Local
// $folder = realpath(__DIR__ . '/../../../uproots/upload_xls');

// Production
$folder = '/home/itcawsportal/public_html/uproots/upload_xls';

if ($folder && is_dir($folder)) {
    $files = glob($folder . '/*');
    $deletedCount = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            if (unlink($file)) {
                $deletedCount++;
            } else {
                echo "Failed to delete: $file\n";
            }
        }
    }
    echo "Deleted $deletedCount file(s) from: $folder\n";
} else {
    echo "Directory not found: $folder\n";
}
