<?php
/**
 * Plugin Name: Patient File Manager
 * Description: Manage patient records with file uploads and client login to view their files.
 * Version: 1.0
 * Author: Abhishek_Ghosh
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Activation Hook to Create Database Table
function cfm_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cfm_clients';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        age INT NOT NULL,
        sex ENUM('Male', 'Female', 'Other') NOT NULL,
        address TEXT NOT NULL,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        files TEXT NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'cfm_activate');

// Admin Menu to Manage Clients
function cfm_admin_menu() {
    add_menu_page('Client Manager', 'Client Manager', 'manage_options', 'cfm-client-manager', 'cfm_client_manager_page');
}
add_action('admin_menu', 'cfm_admin_menu');

// Admin Page Content
function cfm_client_manager_page() {
    echo '<div class="wrap"><h2>Client Manager</h2>';
    echo '<form method="post" enctype="multipart/form-data">
            <input type="text" name="patient_id" placeholder="Patient ID" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="number" name="age" placeholder="Age" required>
            <select name="sex"><option>Male</option><option>Female</option><option>Other</option></select>
            <textarea name="address" placeholder="Address" required></textarea>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="file" name="file" accept=".jpg,.jpeg,.pdf" required>
            <button type="submit" name="cfm_add_client">Add Client</button>
          </form>';
    
    // Handle form submission
    if (isset($_POST['cfm_add_client'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfm_clients';
        
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $sex = sanitize_text_field($_POST['sex']);
        $address = sanitize_textarea_field($_POST['address']);
        $phone = sanitize_text_field($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $upload_dir = wp_upload_dir()['basedir'] . '/cfm_uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        
        $wpdb->insert($table_name, [
            'patient_id' => $patient_id,
            'name' => $name,
            'age' => $age,
            'sex' => $sex,
            'address' => $address,
            'phone' => $phone,
            'password' => $password,
            'files' => $file_name
        ]);
    }
    echo '</div>';
}

// Shortcode for Client Login Form
function cfm_client_login_form() {
    ob_start();
    echo '<form method="post">
            <input type="text" name="patient_id" placeholder="Patient ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="cfm_client_login">Login</button>
          </form>';
    
    if (isset($_POST['cfm_client_login'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfm_clients';
        
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $password = $_POST['password'];
        
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE patient_id = %s", $patient_id));
        
        if ($client && password_verify($password, $client->password)) {
            $upload_dir = wp_upload_dir()['baseurl'] . '/cfm_uploads/';
            echo '<h3>Uploaded Files:</h3><a href="' . $upload_dir . $client->files . '" download>Download File</a>';
        } else {
            echo '<p>Invalid Patient ID or Password.</p>';
        }
    }
    return ob_get_clean();
}
add_shortcode('cfm_client_login', 'cfm_client_login_form');
