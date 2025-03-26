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

// Admin Menu to Manage Patients (Now available for Editors too)
function prm_admin_menu() {
    $capability = 'edit_pages'; // Editors and Administrators can access
    add_menu_page('Patient Manager', 'Patient Manager', $capability, 'prm-patient-manager', 'prm_patient_manager_page');
    add_submenu_page('prm-patient-manager', 'View Patients', 'View & Edit Patients', $capability, 'prm-view-patients', 'prm_view_patients_page');
}
add_action('admin_menu', 'prm_admin_menu');
function prm_generate_random_filename($extension) {
    return date('Ymd') . '-' . bin2hex(random_bytes(15)) . '.' . $extension;
}
// Function to Display Add Patient Page
function prm_patient_manager_page() {
    global $wpdb;
    $message = '';

    if (isset($_POST['submit_patient'])) {
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $date = sanitize_text_field($_POST['date']);
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $sex = sanitize_text_field($_POST['sex']);
        $address = sanitize_textarea_field($_POST['address']);
        $phone = sanitize_text_field($_POST['phone']);
        $diagnosis = sanitize_textarea_field($_POST['diagnosis']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // File Upload Handling
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . "/prm_uploads/";

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true); // Create folder if not exists
        }

        $uploaded_files = [];
        if (!empty($_FILES['files']['name'][0])) {
            foreach ($_FILES['files']['name'] as $key => $filename) {
                $file_tmp = $_FILES['files']['tmp_name'][$key];
                $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

                if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'pdf'])) {
                    $new_filename = prm_generate_random_filename($file_ext);
                    move_uploaded_file($file_tmp, $target_dir . $new_filename);
                    $uploaded_files[] = $new_filename;
                }
            }
        }

        $files = implode(',', $uploaded_files); // Store file names in DB

        // Insert into Database
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'prm_patients',
            compact('patient_id', 'date', 'name', 'age', 'sex', 'address', 'phone', 'diagnosis', 'password', 'files')
        );

        if ($inserted) {
            $message = '<div class="prm-success">✅ Patient added successfully!</div>';
        } else {
            $message = '<div class="prm-error">❌ Failed to add patient. Try again!</div>';
        }
    }

    ?>
    <div class="prm-container">
        <h2>Add New Patient</h2>
        <div id="prm-message"><?php echo $message; ?></div>
        <form method="post" enctype="multipart/form-data" id="prm-patient-form">
            <input type="text" name="patient_id" placeholder="Patient ID" required>
            <input type="date" name="date" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="number" name="age" placeholder="Age" required>
            <select name="sex" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <textarea name="address" placeholder="Address" required></textarea>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <textarea name="diagnosis" placeholder="Diagnosis" required></textarea>
            <input type="password" name="password" placeholder="Password" required>

            <!-- File Upload Field -->
            <label for="files">Upload JPEG or PDF:</label>
            <input type="file" name="files[]" id="files" multiple accept=".jpg, .jpeg, .pdf">

            <button type="submit" name="submit_patient">➕ Add Patient</button>
        </form>
    </div>
<?php
	
	add_action('admin_footer', function() {
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let messageBox = document.getElementById("prm-message");
            if (messageBox) {
                setTimeout(() => {
                    messageBox.style.opacity = "0";
                    setTimeout(() => { messageBox.style.display = "none"; }, 500);
                }, 3000); // Hide message after 3 seconds
            }
        });
    </script>
<style>
	input[type="file"] {
    border: 1px solid #ccc;
    display: block;
    padding: 10px;
    width: 100%;
    border-radius: 5px;
    background: #f9f9f9;
}

input[type="file"]::file-selector-button {
    background: #0073aa;
    color: white;
    border: none;
    padding: 10px;
    cursor: pointer;
}

input[type="file"]::file-selector-button:hover {
    background: #005a87;
}

	.prm-container {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

.prm-container h2 {
    text-align: center;
    color: #333;
}

.prm-container input,
.prm-container select,
.prm-container textarea {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

.prm-container button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 5px;
    background: #0073aa;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

.prm-container button:hover {
    background: #005a87;
}

.prm-success,
.prm-error {
    text-align: center;
    font-size: 16px;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.prm-success {
    background: #dff0d8;
    color: #3c763d;
}

.prm-error {
    background: #f2dede;
    color: #a94442;
}
</style>
    <?php
});
}

// Function to Display the Patient List in Admin Dashboard
function prm_view_patients_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'prm_patients';
    $patients_per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $patients_per_page;

    $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_patients / $patients_per_page);

    $patients = $wpdb->get_results("SELECT * FROM $table_name LIMIT $patients_per_page OFFSET $offset");

    echo '<div class="wrap"><h2>View & Edit Patients</h2>';

    // Handle File Uploads
    if (isset($_POST['update_files'])) {
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $existing_files = sanitize_text_field($_POST['existing_files']);

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . "/prm_uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $new_files = [];
        if (!empty($_FILES['additional_files']['name'][0])) {
            foreach ($_FILES['additional_files']['name'] as $key => $filename) {
                $file_tmp = $_FILES['additional_files']['tmp_name'][$key];
                $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

                if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'pdf'])) {
                    $new_filename = date('Ymd') . '-' . bin2hex(random_bytes(15)) . '.' . $file_ext;
                    move_uploaded_file($file_tmp, $target_dir . $new_filename);
                    $new_files[] = $new_filename;
                }
            }
        }

        // Combine existing and new files
        $updated_files = trim($existing_files . ',' . implode(',', $new_files), ',');

        // Update the database
        $wpdb->update(
            $table_name,
            ['files' => $updated_files],
            ['patient_id' => $patient_id]
        );

        echo '<div class="updated"><p>✅ Files updated successfully!</p></div>';
    }

    // Handle Patient Deletion
    if (isset($_POST['delete_patient']) && current_user_can('manage_options')) {
        $patient_id = sanitize_text_field($_POST['delete_patient']);
        
        // Get file names to delete
        $patient = $wpdb->get_row($wpdb->prepare("SELECT files FROM $table_name WHERE patient_id = %s", $patient_id));
        if ($patient) {
            $files = explode(',', $patient->files);
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . "/prm_uploads/";

            // Delete associated files
            foreach ($files as $file) {
                $file_path = $target_dir . $file;
                if (!empty($file) && file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete the patient record
            $wpdb->delete($table_name, ['patient_id' => $patient_id]);

            echo '<div class="updated"><p>❌ Patient record deleted successfully!</p></div>';
        }
    }

    // Display Table without ID column
    echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Diagnosis</th>
                    <th>Files</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($patients as $patient) {
        $files = explode(',', $patient->files);
        $file_links = '';
        foreach ($files as $file) {
            if (!empty($file)) {
                $file_links .= '<a href="' . esc_url(wp_upload_dir()['baseurl'] . '/prm_uploads/' . $file) . '" target="_blank">📄 View File</a><br>';
            }
        }

        echo '<tr>
                <td>' . esc_html($patient->patient_id) . '</td>
                <td>' . esc_html($patient->name) . '</td>
                <td>' . esc_html($patient->age) . '</td>
                <td>' . esc_html($patient->sex) . '</td>
                <td>' . esc_html($patient->address) . '</td>
                <td>' . esc_html($patient->phone) . '</td>
                <td>' . esc_html($patient->diagnosis) . '</td>
                <td>' . $file_links . '</td>
              </tr>
              <tr>
                <td colspan="8">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="patient_id" value="' . esc_attr($patient->patient_id) . '">
                        <input type="hidden" name="existing_files" value="' . esc_attr($patient->files) . '">
                        <input type="file" name="additional_files[]" multiple accept=".jpg, .jpeg, .pdf">
                        <button type="submit" name="update_files">📤 Upload</button>
                    </form>
                    <form method="post" onsubmit="return confirm(\'⚠️ Are you sure you want to delete this patient record? This action cannot be undone.\');">
                        <input type="hidden" name="delete_patient" value="' . esc_attr($patient->patient_id) . '">
<button type="submit" class="delete-button">❌ Delete Patient</button>
</form>
                </td>
              </tr>
              <tr><td colspan="8"><hr></td></tr>';
    }

    echo '</tbody></table>';

    // Pagination
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        if ($current_page > 1) {
            echo '<a href="?page=prm-view-patients&paged=' . ($current_page - 1) . '">⬅️ Previous</a>';
        }
        if ($current_page < $total_pages) {
            echo '<a href="?page=prm-view-patients&paged=' . ($current_page + 1) . '">Next ➡️</a>';
        }
        echo '</div>';
    }

    echo '</div>';
}
// Patient Login Shortcode
function prm_patient_login_form() {
    if (isset($_POST['prm_login'])) {
        return prm_handle_login();
    }

    ob_start();
    ?>
    <div class="prm-login-container">
        <h2>Patient Login</h2>
        <form method="post">
            <input type="text" name="patient_id" placeholder="Enter Patient ID" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit" name="prm_login">Login</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
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
    ob_start();
    ?>
    <div class="prm-patient-record">
        <h2>Welcome, <?php echo esc_html($patient->name); ?></h2>
        <p><strong>Patient ID:</strong> <?php echo esc_html($patient->patient_id); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($patient->date); ?></p>
        <p><strong>Age:</strong> <?php echo esc_html($patient->age); ?></p>
        <p><strong>Sex:</strong> <?php echo esc_html($patient->sex); ?></p>
        <p><strong>Address:</strong> <?php echo esc_html($patient->address); ?></p>
        <p><strong>Phone:</strong> <?php echo esc_html($patient->phone); ?></p>
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
    <?php
    return ob_get_clean();
}
