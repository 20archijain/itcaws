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
        // For local
        $baseFolder = realpath(__DIR__ . '/../../../uproots/pdf');

        // For production
        // $baseFolder = '/home/itcawsportal/public_html/uproots/pdf';
        $daysToKeep = 5;

        $today = strtotime(date('Y-m-d'));

        if ($baseFolder && is_dir($baseFolder)) {

            $folders = glob($baseFolder . '/*', GLOB_ONLYDIR);
            $deletedCount = 0;

            foreach ($folders as $folderPath) {

                $folderName = basename($folderPath);

                // Try to parse date from folder name
                // Supports: YYYY-MM-DD | YYYY_MM_DD | YYYYMMDD
                $folderDate = false;

                if (preg_match('/^\d{4}[-_]\d{2}[-_]\d{2}$/', $folderName)) {
                    $folderDate = strtotime(str_replace('_', '-', $folderName));
                } elseif (preg_match('/^\d{8}$/', $folderName)) {
                    $folderDate = strtotime(
                        substr($folderName, 0, 4) . '-' .
                            substr($folderName, 4, 2) . '-' .
                            substr($folderName, 6, 2)
                    );
                }

                if ($folderDate === false) {
                    continue; // Skip non-date folders
                }

                $ageInDays = ($today - $folderDate) / 86400;

                if ($ageInDays > $daysToKeep) {

                    // Delete all files inside folder
                    $files = glob($folderPath . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }

                    // Remove the folder
                    if (rmdir($folderPath)) {
                        $deletedCount++;
                    }
                    $iStatus = updateRecord($this->_dbConn, "tblapp_notification", "dstatus = 1", " notification_date < DATE_SUB(CURDATE(), INTERVAL 5 DAY)");
                }
            }

            echo "Deleted $deletedCount folder(s) older than $daysToKeep days.\n";
        } else {
            echo "Directory not found: $baseFolder\n";
        }
    }
}

$deletePDFCronjob = new PDFDeleter($dbConn);
$deletePDFCronjob->deletePDFForTeam();
