<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

global $wpdb;
$table_name = $wpdb->prefix . 'prm_patients';

// Function to create database table
function prm_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prm_patients';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id VARCHAR(50) NOT NULL UNIQUE,
        date DATE NOT NULL,
        name VARCHAR(100) NOT NULL,
        age INT NOT NULL,
        sex ENUM('Male', 'Female', 'Other') NOT NULL,
        address TEXT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        diagnosis TEXT NOT NULL,
        password VARCHAR(255) NOT NULL,
        files TEXT NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Function to generate secure random filename
function prm_generate_random_filename($extension) {
    return date('Ymd') . '-' . bin2hex(random_bytes(15)) . '.' . $extension;
}

// Function to validate and upload files
function prm_handle_file_upload($files) {
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . "/prm_uploads/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $uploaded_files = [];
    foreach ($files['name'] as $key => $filename) {
        $file_tmp = $files['tmp_name'][$key];
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'pdf'])) {
            $new_filename = prm_generate_random_filename($file_ext);
            move_uploaded_file($file_tmp, $target_dir . $new_filename);
            $uploaded_files[] = $new_filename;
        }
    }
    return implode(',', $uploaded_files);
}
