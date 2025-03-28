<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

function prm_patient_login_form() {
    if (isset($_POST['prm_login'])) {
        return prm_handle_login();
    }

    ob_start(); ?>
    <div class="prm-login-container">
        <h2>Patient Login</h2>
        <form method="post">
            <input type="text" name="patient_id" placeholder="Enter Patient ID" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit" name="prm_login">Login</button>
        </form>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('prm_patient_login', 'prm_patient_login_form');

function prm_handle_login() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prm_patients';

    $patient_id = sanitize_text_field($_POST['patient_id']);
    $password = $_POST['password'];

    $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE patient_id = %s", $patient_id));

    if ($patient && password_verify($password, $patient->password)) {
        return prm_display_patient_records($patient);
    } else {
        return '<div class="prm-error">Invalid Patient ID or Password</div>' . prm_patient_login_form();
    }
}

function prm_display_patient_records($patient) {
    ob_start(); ?>
    <div class="prm-patient-record">
        <h2>Welcome, <?php echo esc_html($patient->name); ?></h2>
        <p><strong>Patient ID:</strong> <?php echo esc_html($patient->patient_id); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($patient->date); ?></p>
        <p><strong>Age:</strong> <?php echo esc_html($patient->age); ?></p>
        <p><strong>Sex:</strong> <?php echo esc_html($patient->sex); ?></p>
        <p><strong>Diagnosis:</strong> <?php echo esc_html($patient->diagnosis); ?></p>

        <h3>Uploaded Files</h3>
        <?php 
        $files = explode(',', $patient->files);
        foreach ($files as $file) {
            if (!empty($file)) {
                echo '<a href="' . esc_url(wp_upload_dir()['baseurl'] . '/prm_uploads/' . $file) . '" target="_blank">📄 View File</a><br>';
            }
        }
        ?>
    </div>
    <?php return ob_get_clean();
}
