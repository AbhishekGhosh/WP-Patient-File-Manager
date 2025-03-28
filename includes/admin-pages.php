<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

function prm_admin_menu() {
    add_menu_page('Patient Manager', 'Patient Manager', 'edit_pages', 'prm-patient-manager', 'prm_add_patient_page');
    add_submenu_page('prm-patient-manager', 'View Patients', 'View & Edit Patients', 'edit_pages', 'prm-view-patients', 'prm_view_patients_page');
    add_submenu_page('prm-patient-manager', 'Update Passwords', 'Update Passwords', 'manage_options', 'prm-update-passwords', 'prm_update_passwords_page');
}
add_action('admin_menu', 'prm_admin_menu');

function prm_add_patient_page() {
    require_once PRM_PLUGIN_PATH . 'includes/admin-add-patient.php';
}

function prm_view_patients_page() {
    require_once PRM_PLUGIN_PATH . 'includes/admin-view-patients.php';
}

function prm_update_passwords_page() {
    require_once PRM_PLUGIN_PATH . 'includes/admin-update-passwords.php';
}
