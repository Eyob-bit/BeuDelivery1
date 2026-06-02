<?php
session_start();
include "../includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: getStarted.php");
    exit();
}

$merchant_id = $_SESSION['merchant_id'];
$user_id = $_SESSION['user_id'];

// Fetch merchant details
$merchant_sql = "SELECT m.*, u.email FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: setup.php");
    exit();
}

// Handle form submission
$error = "";
$success = "";
$menu_submitted = false;

// Check if menu already exists
$menu_check_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id' 
                   AND document_type IN ('menu_pdf', 'menu_photo', 'menu_link')";
$menu_check_result = mysqli_query($conn, $menu_check_sql);
$existing_menu = mysqli_fetch_assoc($menu_check_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_menu'])) {
    $menu_type = mysqli_real_escape_string($conn, $_POST['menu_type']);
    $store_hours = mysqli_real_escape_string($conn, trim($_POST['store_hours']));
    $additional_notes = mysqli_real_escape_string($conn, trim($_POST['additional_notes'] ?? ''));
    
    // Validation
    if (empty($store_hours)) {
        $error = "Please enter your store hours";
    } elseif ($menu_type === 'link') {
        $menu_url = mysqli_real_escape_string($conn, trim($_POST['menu_url']));
        if (empty($menu_url)) {
            $error = "Please enter a valid menu URL";
        } elseif (!filter_var($menu_url, FILTER_VALIDATE_URL)) {
            $error = "Please enter a valid URL (include http:// or https://)";
        } else {
            // Save menu link
            $document_type = 'menu_link';
            $file_path = '';
            $file_url = $menu_url;
            
            // Proceed with saving (single entry for link)
            saveMenuToDatabase($existing_menu, $merchant_id, $document_type, $file_path, $file_url, $store_hours, $conn);
        }
    } elseif ($menu_type === 'file') {
        // Handle file uploads - MULTIPLE FILES
        if (isset($_FILES['menu_files']) && !empty($_FILES['menu_files']['name'][0])) {
            $uploaded_files = [];
            $total_size = 0;
            $upload_errors = [];
            
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . "/uploads/menus";
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $error = "Failed to create upload directory. Please check permissions.";
                }
            }
            
            // Check if directory is writable
            if (empty($error) && !is_writable($upload_dir)) {
                $error = "Upload directory is not writable. Please check permissions.";
            }
            
            if (empty($error)) {
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                $max_total_size = 10 * 1024 * 1024; // 10MB total
                $max_file_size = 5 * 1024 * 1024; // 5MB per file
                
                // Process each file
                foreach ($_FILES['menu_files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['menu_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['menu_files']['name'][$key];
                        $file_tmp = $_FILES['menu_files']['tmp_name'][$key];
                        $file_size = $_FILES['menu_files']['size'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Validate individual file
                        if (!in_array($file_ext, $allowed_extensions)) {
                            $upload_errors[] = "$file_name: Only PDF, JPG, JPEG, PNG, and GIF files are allowed.";
                            continue;
                        }
                        
                        if ($file_size > $max_file_size) {
                            $upload_errors[] = "$file_name: File size must be less than 5MB";
                            continue;
                        }
                        
                        $total_size += $file_size;
                        if ($total_size > $max_total_size) {
                            $upload_errors[] = "Total file size exceeds 10MB limit";
                            break;
                        }
                        
                        // Generate unique filename
                        $new_filename = "menu_" . $merchant_id . "_" . time() . "_" . $key . "." . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $document_type = ($file_ext === 'pdf') ? 'menu_pdf' : 'menu_photo';
                            $uploaded_files[] = [
                                'type' => $document_type,
                                'path' => "uploads/menus" . $new_filename,
                                'url' => ''
                            ];
                        } else {
                            $upload_errors[] = "$file_name: Failed to upload file";
                        }
                    } elseif ($_FILES['menu_files']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        // Handle upload errors
                        $error_messages = [
                            UPLOAD_ERR_INI_SIZE => "$file_name: File exceeds upload_max_filesize directive in php.ini",
                            UPLOAD_ERR_FORM_SIZE => "$file_name: File exceeds MAX_FILE_SIZE directive in HTML form",
                            UPLOAD_ERR_PARTIAL => "$file_name: File was only partially uploaded",
                            UPLOAD_ERR_NO_TMP_DIR => "$file_name: Missing temporary folder",
                            UPLOAD_ERR_CANT_WRITE => "$file_name: Failed to write file to disk",
                            UPLOAD_ERR_EXTENSION => "$file_name: File upload stopped by extension"
                        ];
                        $upload_errors[] = $error_messages[$_FILES['menu_files']['error'][$key]] ?? "$file_name: Unknown upload error";
                    }
                }
                
                // Check if any files were uploaded successfully
                if (!empty($uploaded_files) && empty($upload_errors)) {
                    // Save all uploaded files to database
                    saveMultipleMenusToDatabase($existing_menu, $merchant_id, $uploaded_files, $store_hours, $conn);
                } elseif (!empty($uploaded_files) && !empty($upload_errors)) {
                    // Some files uploaded, some failed
                    saveMultipleMenusToDatabase($existing_menu, $merchant_id, $uploaded_files, $store_hours, $conn);
                    $error = "Some files uploaded successfully, but had errors:<br>" . implode("<br>", $upload_errors);
                } elseif (empty($uploaded_files) && !empty($upload_errors)) {
                    // All files failed
                    $error = "Failed to upload files:<br>" . implode("<br>", $upload_errors);
                } else {
                    $error = "Please select at least one file to upload";
                }
            }
        } else {
            $error = "Please select at least one file to upload";
        }
    }
}

// Function to save single menu (for link or single file)
function saveMenuToDatabase($existing_menu, $merchant_id, $document_type, $file_path, $file_url, $store_hours, $conn) {
    global $success, $menu_submitted, $error;
    
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete any existing menu files for this merchant
        $delete_sql = "DELETE FROM merchant_documents WHERE merchant_id = '$merchant_id' 
                      AND document_type IN ('menu_pdf', 'menu_photo', 'menu_link')";
        if (!mysqli_query($conn, $delete_sql)) {
            throw new Exception("Failed to clear existing menus: " . mysqli_error($conn));
        }
        
        // Insert new menu
        $menu_sql = "INSERT INTO merchant_documents 
                    (merchant_id, document_type, document_path, document_url, uploaded_at)
                    VALUES ('$merchant_id', '$document_type', 
                    " . ($file_path ? "'$file_path'" : "NULL") . ",
                    " . ($file_url ? "'$file_url'" : "NULL") . ",
                    NOW())";
        
        if (!mysqli_query($conn, $menu_sql)) {
            throw new Exception("Failed to save menu: " . mysqli_error($conn));
        }
        
        // Update merchant_details table
        updateMerchantDetails($merchant_id, $store_hours, $conn);
        
        // Update progress in session
        $_SESSION['menu_uploaded'] = true;
        
        // Commit transaction
        mysqli_commit($conn);
        
        $success = "Menu uploaded successfully! Redirecting back to setup...";
        $menu_submitted = true;
        
        // Redirect back to setup.php after 2 seconds
        header("refresh:2;url=setup.php");
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $error = "Failed to save: " . $e->getMessage();
    }
}

// Function to save multiple menu files
function saveMultipleMenusToDatabase($existing_menu, $merchant_id, $uploaded_files, $store_hours, $conn) {
    global $success, $menu_submitted, $error;
    
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete any existing menu files for this merchant
        $delete_sql = "DELETE FROM merchant_documents WHERE merchant_id = '$merchant_id' 
                      AND document_type IN ('menu_pdf', 'menu_photo', 'menu_link')";
        if (!mysqli_query($conn, $delete_sql)) {
            throw new Exception("Failed to clear existing menus: " . mysqli_error($conn));
        }
        
        // Insert each uploaded file
        foreach ($uploaded_files as $file) {
            $menu_sql = "INSERT INTO merchant_documents 
                        (merchant_id, document_type, document_path, uploaded_at)
                        VALUES ('$merchant_id', '{$file['type']}', 
                        '{$file['path']}', NOW())";
            
            if (!mysqli_query($conn, $menu_sql)) {
                throw new Exception("Failed to save menu file: " . mysqli_error($conn));
            }
        }
        
        // Update merchant_details table
        updateMerchantDetails($merchant_id, $store_hours, $conn);
        
        // Update progress in session
        $_SESSION['menu_uploaded'] = true;
        
        // Commit transaction
        mysqli_commit($conn);
        
        $success = count($uploaded_files) . " menu file(s) uploaded successfully! Redirecting back to setup...";
        $menu_submitted = true;
        
        // Redirect back to setup.php after 2 seconds
        header("refresh:2;url=setup.php");
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $error = "Failed to save: " . $e->getMessage();
        
        // Clean up uploaded files on error
        foreach ($uploaded_files as $file) {
            if (file_exists("../" . $file['path'])) {
                unlink("../" . $file['path']);
            }
        }
    }
}

// Function to update merchant_details
function updateMerchantDetails($merchant_id, $store_hours, $conn) {
    // Convert store_hours to JSON format if it's a plain string
    if (!empty($store_hours) && !json_decode($store_hours)) {
        // It's a plain string, convert to JSON
        $store_hours_json = json_encode([
            "hours" => $store_hours
        ]);
    } else {
        $store_hours_json = $store_hours;
    }
    
    // Check if merchant_details exists
    $details_check = "SELECT * FROM merchant_details WHERE merchant_id = '$merchant_id'";
    $details_result = mysqli_query($conn, $details_check);
    
    if (mysqli_num_rows($details_result) > 0) {
        // Update existing
        $hours_sql = "UPDATE merchant_details SET 
                     store_hours = '$store_hours_json',
                     updated_at = NOW()
                     WHERE merchant_id = '$merchant_id'";
    } else {
        // Insert new
        $hours_sql = "INSERT INTO merchant_details 
                     (merchant_id, store_hours, created_at, updated_at)
                     VALUES ('$merchant_id', '$store_hours_json', NOW(), NOW())";
    }
    
    if (!mysqli_query($conn, $hours_sql)) {
        // Log but don't throw error - main data is in merchant_documents
        error_log("Failed to update merchant_details: " . mysqli_error($conn));
    }
}

// Get existing data
$store_hours = "";
$additional_notes = "";
$menu_type = "file";
$menu_url = "";

if ($existing_menu) {
    $menu_type = ($existing_menu['document_type'] === 'menu_link') ? 'link' : 'file';
    if ($menu_type === 'link') {
        $menu_url = $existing_menu['document_url'] ?? ''; // Fixed: use document_url
    }
    
    // Note: store_hours is NOT in merchant_documents table
    // We'll get it from merchant_details below
}

// Get store hours from merchant_details
$hours_sql = "SELECT store_hours FROM merchant_details WHERE merchant_id = '$merchant_id'";
$hours_result = mysqli_query($conn, $hours_sql);
if ($hours_row = mysqli_fetch_assoc($hours_result)) {
    $store_hours_data = $hours_row['store_hours'];
    
    // Check if it's JSON
    if (!empty($store_hours_data)) {
        $decoded = json_decode($store_hours_data, true);
        if ($decoded) {
            // It's JSON, convert to readable format
            if (isset($decoded['hours'])) {
                $store_hours = $decoded['hours'];
            } else {
                // It's a day-by-day JSON
                $hours_array = [];
                foreach ($decoded as $day => $hours) {
                    $hours_array[] = "$day: $hours";
                }
                $store_hours = implode("\n", $hours_array);
            }
        } else {
            // It's a plain string
            $store_hours = $store_hours_data;
        }
    }
}

// Set default store hours if still empty
if (empty($store_hours)) {
    $store_hours = "Monday - Friday: 10:00AM - 5:00PM\nSaturday: 11:00AM - 4:00PM\nSunday: Closed";
}

// Get all existing menu files for this merchant
$all_menu_files_sql = "SELECT * FROM merchant_documents WHERE merchant_id = '$merchant_id' 
                      AND document_type IN ('menu_pdf', 'menu_photo') 
                      ORDER BY uploaded_at";
$all_menu_files_result = mysqli_query($conn, $all_menu_files_sql);
$existing_menu_files = [];
if ($all_menu_files_result) {
    while ($row = mysqli_fetch_assoc($all_menu_files_result)) {
        $existing_menu_files[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Menu & Update Menu Hours - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* General styling */
        body {
            background-color: white;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .page-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .menu-container {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Header and Back Arrow */
        .header-section {
            padding-top: 20px;
            margin-bottom: 30px;
        }

        .back-arrow {
            font-size: 1.5rem;
            color: #333;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
        }

        /* Main Form Sections */
        .section-box {
            background-color: white;
            padding: 0;
            margin-bottom: 40px;
        }
        
        .section-box h3 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .section-box p {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 25px;
        }

        /* Menu Guidelines List */
        .guideline-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 30px;
        }
        
        .guideline-list li {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .guideline-icon {
            font-size: 1.5rem;
            color: #38c172;
            margin-right: 15px;
        }
        
        /* Tabs Styling */
        .nav-tabs {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            font-weight: 500;
            text-decoration: none;
            background: none;
            cursor: pointer;
        }

        .nav-link.active {
            color: black;
            border-bottom-color: black;
            background-color: transparent;
        }
        
        /* Upload Area */
        .upload-area {
            border: 2px dashed #ccc;
            background-color: #f7f7f7;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin-top: 20px;
            position: relative;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .upload-area p {
            color: #333;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .btn-browse {
            background-color: black;
            color: white;
            padding: 8px 25px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }
        
        .file-info {
            margin-top: 15px;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
            max-width: 90%;
        }
        
        .file-info strong {
            color: #333;
        }
        
        /* File list styling */
        .file-list {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        
        .file-item-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-item-size {
            color: #666;
            margin-left: 10px;
            white-space: nowrap;
        }
        
        .file-item-remove {
            color: #dc3545;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 10px;
            font-size: 1rem;
        }
        
        .total-size {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
            font-weight: bold;
        }
        
        /* Existing files section */
        .existing-files {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .existing-file-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .existing-file-item:last-child {
            border-bottom: none;
        }
        
        .existing-file-icon {
            font-size: 1.2rem;
            margin-right: 10px;
            color: #6c757d;
        }
        
        /* Hours Section */
        .hours-section {
            margin-top: 50px;
        }
        
        .hours-section h4 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .hours-textarea {
            background-color: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            resize: vertical;
            width: 100%;
            min-height: 120px;
            font-size: 0.9rem;
            color: #333;
            line-height: 1.5;
        }
        
        .hours-textarea:focus {
            outline: none;
            border-color: #000;
            background-color: white;
        }
        
        .char-count {
            text-align: right;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Existing menu preview */
        .existing-menu {
            background-color: #f0f8ff;
            border: 1px solid #cce7ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        .existing-menu strong {
            color: #0066cc;
        }
        
        /* Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        /* Footer Buttons */
        .footer-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .btn-cancel {
            background-color: white;
            color: black;
            border: 1px solid #ccc;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-cancel:hover {
            background-color: #f8f9fa;
            text-decoration: none;
            color: black;
        }

        .btn-submit {
            background-color: #e2e2e2;
            color: #999;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit.enabled {
            background-color: black;
            color: white;
            cursor: pointer;
        }
        
        .btn-submit.enabled:hover {
            background-color: #333;
        }
        
        .btn-submit.disabled {
            background-color: #e2e2e2;
            color: #999;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .page-content {
                padding: 15px;
            }
            
            .menu-container {
                max-width: 100%;
            }
            
            .footer-buttons {
                flex-direction: column;
            }
            
            .btn-cancel, .btn-submit {
                width: 100%;
                text-align: center;
            }
            
            .upload-area {
                padding: 30px 15px;
                min-height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center py-3">
            <h2 class="h5 fw-bold m-0">BeU Delivery <span class="fw-normal fs-6 text-muted">for Merchants</span></h2>
            <div>
                <span class="text-dark small fw-bold">
                    <?php echo htmlspecialchars($merchant['store_name']); ?> - 
                    <?php echo htmlspecialchars($merchant['store_address']); ?>
                </span>
                <span class="ms-3"><a href="#" class="text-dark text-decoration-none small">Help</a></span>
                <span class="ms-3"><a href="../index.html" class="text-dark text-decoration-none small">Log out</a></span>
            </div>
        </div>
        <hr>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($error)); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="menu-container">
            <a href="setup.php" class="back-arrow">&larr;</a>
            <h1 class="h3 fw-bold mb-4">Upload Menu & Update Menu Hours</h1>

            <div class="section-box">
                <h3>Add your menu</h3>
                <p>
                    We'll use it to build your online menu in 1 to 3 days. You'll be able to edit it once we are done.
                </p>

                <p class="fw-bold mb-3">To help us get it right:</p>
                <ul class="guideline-list">
                    <li><span class="guideline-icon"><i class="bi bi-file-earmark-text-fill"></i></span> Each item must have a price</li>
                    <li><span class="guideline-icon"><i class="bi bi-search-heart"></i></span> Images should be clear and readable</li>
                </ul>

                <?php if (!empty($existing_menu_files) || ($existing_menu && $existing_menu['document_type'] === 'menu_link')): ?>
                <div class="existing-menu">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong>
                        <?php 
                        if ($existing_menu && $existing_menu['document_type'] === 'menu_link') {
                            echo 'Online menu link already uploaded';
                        } elseif (!empty($existing_menu_files)) {
                            echo count($existing_menu_files) . ' menu file(s) already uploaded';
                        }
                        ?>
                    </strong>
                    <br>
                    <small>Uploading new files will replace all existing menu files.</small>
                    
                    <?php if (!empty($existing_menu_files)): ?>
                    <div class="existing-files mt-3">
                        <p class="small fw-bold mb-2">Current menu files:</p>
                        <?php foreach ($existing_menu_files as $file): ?>
                        <div class="existing-file-item">
                            <span class="existing-file-icon">
                                <?php if ($file['document_type'] === 'menu_pdf'): ?>
                                <i class="bi bi-file-earmark-pdf"></i>
                                <?php else: ?>
                                <i class="bi bi-file-image"></i>
                                <?php endif; ?>
                            </span>
                            <span class="small"><?php echo basename($file['document_path']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="menuForm">
                    <input type="hidden" name="submit_menu" value="1">
                    
                    <ul class="nav nav-tabs" id="menuTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" type="button" data-type="file" 
                                    onclick="switchTab('file')">PDF or photos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" type="button" data-type="link" 
                                    onclick="switchTab('link')">Link to menu</button>
                        </li>
                    </ul>
                    
                    <input type="hidden" name="menu_type" id="menuType" value="file">
                    
                    <div id="fileTab" class="tab-content">
                        <p class="mt-3">Upload multiple pages of your menu (PDF or images).</p>
                        <div class="upload-area" id="fileUploadArea">
                            <input type="file" name="menu_files[]" id="menuFiles" multiple 
                                   accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="handleFileSelect(event)">
                            <p id="uploadText">Drop files here or click to browse</p>
                            <button type="button" class="btn btn-browse" onclick="document.getElementById('menuFiles').click()">
                                Browse files
                            </button>
                            <div id="fileList" class="file-list"></div>
                            <div id="totalSize" class="total-size"></div>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle"></i> Maximum 10MB total, 5MB per file. Allowed: PDF, JPG, PNG, GIF
                        </div>
                    </div>
                    
                    <div id="linkTab" class="tab-content" style="display: none;">
                        <p class="mt-3">Enter the link to your online menu below.</p>
                        <input type="url" class="form-control" name="menu_url" id="menuUrl" 
                               placeholder="https://example.com/menu" value="<?php echo htmlspecialchars($menu_url); ?>">
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle"></i> Make sure the link is publicly accessible
                        </div>
                    </div>

                    <div class="hours-section">
                        <h4>Set store hours on BeU Delivery</h4>
                        <p class="form-label-custom">Customers will be able to order during these times.</p>
                        
                        <p class="fw-bold mb-2">Store hours</p>
                        <textarea class="hours-textarea" name="store_hours" id="storeHours" 
                                  placeholder="Example:
Monday - Friday: 10:00AM - 5:00PM
Saturday: 11:00AM - 4:00PM
Sunday: Closed" 
                                  maxlength="500" oninput="updateCharCount()" 
                                  required><?php echo htmlspecialchars($store_hours); ?></textarea>
                        <div class="char-count">
                            <span id="charCount"><?php echo strlen($store_hours); ?></span>/500 characters
                        </div>
                        
                        <p class="fw-bold mb-2 mt-4">Additional notes (optional)</p>
                        <textarea class="form-control" name="additional_notes" rows="3" 
                                  placeholder="Any special instructions or notes about your menu, preparation time, etc..."><?php echo htmlspecialchars($additional_notes); ?></textarea>
                    </div>

                    <div class="footer-buttons">
                        <a href="setup.php" class="btn btn-cancel">Cancel</a>
                        <button type="submit" class="btn btn-submit <?php echo ($existing_menu || !empty($existing_menu_files)) ? 'enabled' : 'disabled'; ?>" 
                                id="submitBtn" <?php echo $menu_submitted ? 'disabled' : ''; ?>>
                            <?php echo ($existing_menu || !empty($existing_menu_files)) ? 'Update Menu' : 'Submit'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        let currentTab = 'file';
        let files = [];
        let totalSize = 0;
        const MAX_TOTAL_SIZE = 10 * 1024 * 1024; // 10MB
        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB per file
        let urlEntered = <?php echo $existing_menu && $existing_menu['document_type'] === 'menu_link' ? 'true' : 'false'; ?>;
        
        function switchTab(tabType) {
            currentTab = tabType;
            document.getElementById('menuType').value = tabType;
            
            // Update tab styling
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('data-type') === tabType) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            // Show/hide tab content
            if (tabType === 'file') {
                document.getElementById('fileTab').style.display = 'block';
                document.getElementById('linkTab').style.display = 'none';
                updateSubmitButton(files.length > 0 && validateStoreHours());
            } else {
                document.getElementById('fileTab').style.display = 'none';
                document.getElementById('linkTab').style.display = 'block';
                updateSubmitButton(urlEntered && validateStoreHours());
            }
        }
        
        function handleFileSelect(event) {
            const fileList = document.getElementById('fileList');
            const totalSizeDiv = document.getElementById('totalSize');
            const uploadText = document.getElementById('uploadText');
            
            // Reset
            files = [];
            totalSize = 0;
            fileList.innerHTML = '';
            
            const selectedFiles = Array.from(event.target.files);
            
            // Validate and process files
            selectedFiles.forEach((file, index) => {
                // Check file size
                if (file.size > MAX_FILE_SIZE) {
                    alert(`"${file.name}" is too large. Maximum file size is 5MB.`);
                    return;
                }
                
                // Check total size
                if (totalSize + file.size > MAX_TOTAL_SIZE) {
                    alert(`Cannot add "${file.name}". Total size would exceed 10MB limit.`);
                    return;
                }
                
                // Check file type
                const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type) && !file.name.toLowerCase().match(/\.(pdf|jpg|jpeg|png|gif)$/)) {
                    alert(`"${file.name}" is not a valid file type. Allowed: PDF, JPG, PNG, GIF`);
                    return;
                }
                
                // Add to files array
                files.push(file);
                totalSize += file.size;
                
                // Create file item in UI
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-item-name">${file.name}</div>
                    <div class="file-item-size">${formatFileSize(file.size)}</div>
                    <button type="button" class="file-item-remove" onclick="removeFile(${index})">
                        <i class="bi bi-x-circle"></i>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
            
            // Update total size display
            if (files.length > 0) {
                totalSizeDiv.innerHTML = `Total: ${formatFileSize(totalSize)} / ${formatFileSize(MAX_TOTAL_SIZE)}`;
                uploadText.textContent = `${files.length} file(s) selected`;
                uploadText.style.color = "#28a745";
            } else {
                totalSizeDiv.innerHTML = '';
                uploadText.textContent = "Drop files here or click to browse";
                uploadText.style.color = "#333";
            }
            
            updateSubmitButton();
        }
        
        function removeFile(index) {
            // Remove file from array
            totalSize -= files[index].size;
            files.splice(index, 1);
            
            // Update file list UI
            const fileList = document.getElementById('fileList');
            const totalSizeDiv = document.getElementById('totalSize');
            const uploadText = document.getElementById('uploadText');
            
            fileList.innerHTML = '';
            files.forEach((file, newIndex) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-item-name">${file.name}</div>
                    <div class="file-item-size">${formatFileSize(file.size)}</div>
                    <button type="button" class="file-item-remove" onclick="removeFile(${newIndex})">
                        <i class="bi bi-x-circle"></i>
                    </button>
                `;
                fileList.appendChild(fileItem);
            });
            
            // Update total size display
            if (files.length > 0) {
                totalSizeDiv.innerHTML = `Total: ${formatFileSize(totalSize)} / ${formatFileSize(MAX_TOTAL_SIZE)}`;
                uploadText.textContent = `${files.length} file(s) selected`;
                uploadText.style.color = "#28a745";
            } else {
                totalSizeDiv.innerHTML = '';
                uploadText.textContent = "Drop files here or click to browse";
                uploadText.style.color = "#333";
                // Reset file input
                document.getElementById('menuFiles').value = '';
            }
            
            updateSubmitButton();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function updateCharCount() {
            const textarea = document.getElementById('storeHours');
            const charCount = document.getElementById('charCount');
            charCount.textContent = textarea.value.length;
            updateSubmitButton();
        }
        
        function validateStoreHours() {
            const storeHours = document.getElementById('storeHours').value.trim();
            return storeHours.length > 0;
        }
        
        function validateUrl() {
            if (currentTab === 'link') {
                const urlInput = document.getElementById('menuUrl');
                const url = urlInput.value.trim();
                urlEntered = url.length > 0;
                
                // Basic URL validation
                if (url && !url.startsWith('http://') && !url.startsWith('https://')) {
                    urlInput.style.borderColor = '#dc3545';
                    return false;
                } else {
                    urlInput.style.borderColor = '';
                    return urlEntered;
                }
            }
            return true;
        }
        
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            const storeHoursValid = validateStoreHours();
            let menuValid = false;
            
            if (currentTab === 'file') {
                menuValid = files.length > 0;
            } else {
                menuValid = validateUrl();
            }
            
            if (storeHoursValid && menuValid) {
                submitBtn.classList.remove('disabled');
                submitBtn.classList.add('enabled');
                submitBtn.disabled = false;
            } else {
                submitBtn.classList.remove('enabled');
                submitBtn.classList.add('disabled');
                submitBtn.disabled = true;
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set up tab buttons with data-type attribute
            document.querySelectorAll('.nav-link').forEach((link, index) => {
                if (index === 0) {
                    link.setAttribute('data-type', 'file');
                } else {
                    link.setAttribute('data-type', 'link');
                }
            });
            
            // Check if we have existing data
            <?php if ($existing_menu): ?>
                const menuType = "<?php echo $menu_type; ?>";
                switchTab(menuType);
            <?php endif; ?>
            
            // Initialize validation
            updateCharCount();
            updateSubmitButton();
            
            // Add event listeners
            document.getElementById('storeHours').addEventListener('input', updateSubmitButton);
            document.getElementById('menuUrl')?.addEventListener('input', function() {
                validateUrl();
                updateSubmitButton();
            });
        });
        
        // Form submission
        document.getElementById('menuForm').addEventListener('submit', function(e) {
            if (!validateStoreHours()) {
                e.preventDefault();
                alert('Please enter store hours.');
                return false;
            }
            
            if (currentTab === 'file' && files.length === 0) {
                e.preventDefault();
                alert('Please select at least one file to upload.');
                return false;
            }
            
            if (currentTab === 'link' && !validateUrl()) {
                e.preventDefault();
                alert('Please enter a valid URL (include http:// or https://).');
                return false;
            }
            
            // Check if total size exceeds limit (double-check)
            if (currentTab === 'file' && totalSize > MAX_TOTAL_SIZE) {
                e.preventDefault();
                alert('Total file size exceeds 10MB limit. Please remove some files.');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Uploading...';
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds if something goes wrong
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
            
            return true;
        });
        
        // Drag and drop functionality
        const uploadArea = document.getElementById('fileUploadArea');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#000';
                this.style.backgroundColor = '#f0f0f0';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ccc';
                this.style.backgroundColor = '#f7f7f7';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ccc';
                this.style.backgroundColor = '#f7f7f7';
                
                const droppedFiles = e.dataTransfer.files;
                if (droppedFiles.length > 0) {
                    // Combine with existing files
                    const dataTransfer = new DataTransfer();
                    
                    // Add existing files
                    files.forEach(file => {
                        dataTransfer.items.add(file);
                    });
                    
                    // Add new files (up to reasonable limit)
                    const filesToAdd = Math.min(droppedFiles.length, 20 - files.length);
                    for (let i = 0; i < filesToAdd; i++) {
                        dataTransfer.items.add(droppedFiles[i]);
                    }
                    
                    // Update file input
                    document.getElementById('menuFiles').files = dataTransfer.files;
                    
                    // Trigger change event
                    const event = new Event('change', { bubbles: true });
                    document.getElementById('menuFiles').dispatchEvent(event);
                }
            });
        }
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>