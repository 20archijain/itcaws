<?php

// Local
// $baseFolder = realpath(__DIR__ . '/../../../uproots/upload_xls');

// Production
$baseFolder = '/home/itcawsportal/public_html/uproots/upload_xls';

$daysToKeep = 5;
$today = strtotime(date('Y-m-d'));

if ($baseFolder && is_dir($baseFolder)) {

    $folders = glob($baseFolder . '/*', GLOB_ONLYDIR);
    $deletedCount = 0;

    foreach ($folders as $folderPath) {

        $folderName = basename($folderPath);

        // Expect folder name like: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderName)) {
            continue; // skip non-date folders
        }

        $folderDate = strtotime($folderName);
        $ageInDays = ($today - $folderDate) / 86400;

        if ($ageInDays > $daysToKeep) {

            // delete all files inside folder
            $files = glob($folderPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // delete the folder
            if (rmdir($folderPath)) {
                $deletedCount++;
            }
        }
    }

    echo "Deleted $deletedCount folder(s) older than $daysToKeep days.";
} else {
    echo "Directory not found: $baseFolder";
}
