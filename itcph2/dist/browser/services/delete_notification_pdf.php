<?php

date_default_timezone_set("Asia/Calcutta");
$I_am_req_always = "I am req always";

$include_path = './';
require_once $include_path . 'includes/stdsettings.inc.php';
require_once $include_path . "includes/tables_list.php";
require_once $include_path . 'includes/common_actions.php';
require_once $include_path . 'includes/common_functions.php';
require_once $include_path . 'includes/reporting_functions.php';
require_once $include_path . 'includes/filters.php';
require_once $include_path . 'includes/project_process_info.php';
require_once $include_path . 'class/DBConnection.php';

// 2025-01-12
class PDFDeleter
{
    private $_dbConn = null;
    private $_tables = [];

    public function __construct($dbConn)
    {
        $this->_dbConn = $dbConn;
        $this->_tables = $GLOBALS['TABLES'];
    }

    final public function deletePDFForTeam()
    {
        // $baseFolder = realpath(__DIR__ . '/../../../uproots/pdf');

        // For production
        $baseFolder = '/home/itcawsportal/public_html/uproots/pdf';
        $daysToKeep = 5;

        if (!$baseFolder || !is_dir($baseFolder)) {
            echo "Directory not found: $baseFolder\n";
            return;
        }

        // Cutoff date in YYYY-MM-DD
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));

        $folders = glob($baseFolder . '/*', GLOB_ONLYDIR);
        $deletedCount = 0;

        foreach ($folders as $folderPath) {
            $folderName = basename($folderPath);
            $folderDate = null;

            // Normalize folder date to YYYY-MM-DD
            // YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderName)) {
                $folderDate = $folderName;
            } elseif (preg_match('/^\d{8}$/', $folderName)) {
                $folderDate =
                    substr($folderName, 0, 4) . '-' .
                    substr($folderName, 4, 2) . '-' .
                    substr($folderName, 6, 2);
            }

            // kip invalid folders SAFELY
            if (!$folderDate) {
                echo "Skipped (invalid): $folderName\n";
                continue;
            }

            echo "Folder: $folderName | Date: $folderDate\n"; // DEBUG

            // print_r($cutoffDate);
            // die;
            if (!$folderDate) {
                continue;
            }

            // DATE STRING COMPARISON
            if ($folderDate < $cutoffDate) {
                echo "Deleting: $folderName\n";

                foreach (glob($folderPath . '/*') as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                rmdir($folderPath);
            } else {
                echo "Keeping: $folderName\n";
            }
        }

        updateRecord(
            $this->_dbConn,
            "tblapp_notification",
            "dstatus = 1",
            "notification_date < DATE_SUB(CURDATE(), INTERVAL {$daysToKeep} DAY)"
        );

        echo "Deleted {$deletedCount} folder(s). Kept last {$daysToKeep} days.\n";
    }
}

$deletePDFCronjob = new PDFDeleter($dbConn);
$deletePDFCronjob->deletePDFForTeam();
