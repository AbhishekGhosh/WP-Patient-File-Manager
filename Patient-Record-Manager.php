<?php
/**
 * Plugin Name: Patient Record Manager
 * Description: Manage patient records with file uploads and login functionality.
 * Version: 1.1
 * Author: Abhishek_Ghosh
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// Define plugin path
define('PRM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include necessary files
require_once PRM_PLUGIN_PATH . 'includes/functions.php';
require_once PRM_PLUGIN_PATH . 'includes/admin-pages.php';
require_once PRM_PLUGIN_PATH . 'includes/patient-login.php';

// Activation Hook
function prm_activate() {
    prm_create_tables();
}
register_activation_hook(__FILE__, 'prm_activate');

// Enqueue scripts & styles
function prm_enqueue_assets() {
    wp_enqueue_style('prm-styles', plugins_url('includes/assets/styles.css', __FILE__));
    wp_enqueue_script('prm-scripts', plugins_url('includes/assets/scripts.js', __FILE__), ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'prm_enqueue_assets');
