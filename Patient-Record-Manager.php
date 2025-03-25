<?php
/**
 * Plugin Name: Patient Record Manager
 * Description: Manage patient records with file uploads and patient login to view their records.
 * Version: 1.0
 * Author: Abhishek_Ghosh
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Activation Hook to Create Database Table
function prm_activate() {
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
register_activation_hook(__FILE__, 'prm_activate');

// Admin Menu to Manage Patients
function prm_admin_menu() {
    add_menu_page('Patient Manager', 'Patient Manager', 'manage_options', 'prm-patient-manager', 'prm_patient_manager_page');
    add_submenu_page('prm-patient-manager', 'View Patients', 'View & Edit Patients', 'manage_options', 'prm-view-patients', 'prm_view_patients_page');
}
add_action('admin_menu', 'prm_admin_menu');

// Enqueue Styles and Scripts
function prm_enqueue_admin_assets() {
    echo '<style>
        .prm-container { max-width: 700px; margin: auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .prm-container input, .prm-container select, .prm-container textarea, .prm-container button { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .prm-container button { background: #0073aa; color: white; cursor: pointer; }
        .prm-container button:hover { background: #005177; }
        .prm-message { padding: 10px; margin-top: 10px; border-radius: 5px; }
        .prm-success { background: #d4edda; color: #155724; }
        .prm-error { background: #f8d7da; color: #721c24; }
    </style>';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("#prm-form");
            form.addEventListener("submit", function() {
                document.querySelector("#prm-message").innerHTML = "Processing...";
            });
        });
    </script>';
}
add_action('admin_head', 'prm_enqueue_admin_assets');

// Admin Page Content
function prm_patient_manager_page() {
    echo '<div class="wrap"><h2>Patient Manager</h2>';
    echo '<div class="prm-container">
            <form id="prm-form" method="post" enctype="multipart/form-data">
                <input type="text" name="patient_id" placeholder="Patient ID" required>
                <input type="date" name="date" required>
                <input type="text" name="name" placeholder="Name" required>
                <input type="number" name="age" placeholder="Age" required>
                <select name="sex"><option>Male</option><option>Female</option><option>Other</option></select>
                <textarea name="address" placeholder="Address" required></textarea>
                <input type="text" name="phone" placeholder="Phone" required>
                <textarea name="diagnosis" placeholder="Diagnosis" required></textarea>
                <input type="password" name="password" placeholder="Password" required>
                <input type="file" name="file" accept=".jpg,.jpeg,.pdf" required>
                <button type="submit" name="prm_add_patient">Add Patient</button>
            </form>
            <div id="prm-message"></div>
          </div>';
    
    // Handle form submission
    if (isset($_POST['prm_add_patient'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'prm_patients';
        
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $date = sanitize_text_field($_POST['date']);
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $sex = sanitize_text_field($_POST['sex']);
        $address = sanitize_textarea_field($_POST['address']);
        $phone = sanitize_text_field($_POST['phone']);
        $diagnosis = sanitize_textarea_field($_POST['diagnosis']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $upload_dir = wp_upload_dir()['basedir'] . '/prm_uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        
        $result = $wpdb->insert($table_name, [
            'patient_id' => $patient_id,
            'date' => $date,
            'name' => $name,
            'age' => $age,
            'sex' => $sex,
            'address' => $address,
            'phone' => $phone,
            'diagnosis' => $diagnosis,
            'password' => $password,
            'files' => $file_name
        ]);
        
        if ($result) {
            echo '<div class="prm-message prm-success">Patient added successfully!</div>';
        } else {
            echo '<div class="prm-message prm-error">Failed to add patient.</div>';
        }
    }
    echo '</div>';
}

// Shortcode for Patient Login
function prm_patient_login_shortcode() {
    ob_start();
    echo '<div class="prm-container">
            <form method="post">
                <input type="text" name="patient_id" placeholder="Patient ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="prm_login">Login</button>
            </form>';

    if (isset($_POST['prm_login'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'prm_patients';
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $password = $_POST['password'];
        
        $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE patient_id = %s", $patient_id));
        
        if ($patient && password_verify($password, $patient->password)) {
            echo '<div class="prm-success">Login successful! <a href="' . esc_url(wp_upload_dir()['baseurl'] . '/prm_uploads/' . $patient->files) . '" target="_blank">View File</a></div>';
        } else {
            echo '<div class="prm-error">Invalid credentials.</div>';
        }
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('prm_patient_login', 'prm_patient_login_shortcode');
