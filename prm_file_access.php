<?php
// Secure File Access for Patient Records
session_start();
require_once('../../../wp-load.php');

if (!isset($_SESSION['prm_logged_in']) || !$_SESSION['prm_logged_in']) {
    die("Unauthorized access");
}

if (!isset($_GET['file'])) {
    die("No file specified.");
}

$file_path = wp_upload_dir()['basedir'] . "/prm_uploads/" . basename($_GET['file']);

if (!file_exists($file_path)) {
    die("File not found.");
}

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($file_path) . "\"");
readfile($file_path);
exit;
