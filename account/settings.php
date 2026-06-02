<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include "../includes/db.php";

// Get or set merchant_id
if (!isset($_SESSION['merchant_id'])) {
    // Try to find merchant for this user
    $user_id = $_SESSION['user_id'];
    $merchant_sql = "SELECT merchant_id FROM merchants WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $merchant_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['merchant_id'] = $row['merchant_id'];
    } else {
        header("Location: ../merchant/getStarted.php");
        exit();
    }
}

$merchant_id = $_SESSION['merchant_id'];

// Get merchant details for sidebar
$merchant_sql = "SELECT m.*, u.first_name, u.last_name 
                 FROM merchants m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE m.merchant_id = ?";
$stmt = mysqli_prepare($conn, $merchant_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$merchant_result = mysqli_stmt_get_result($stmt);
$merchant = mysqli_fetch_assoc($merchant_result);

if (!$merchant) {
    header("Location: ../merchant/getStarted.php");
    exit();
}

// Get merchant details with extended info
$merchant_details_sql = "SELECT m.*, md.*, u.email, u.phone as user_phone 
                         FROM merchants m
                         LEFT JOIN merchant_details md ON m.merchant_id = md.merchant_id
                         JOIN users u ON m.user_id = u.id
                         WHERE m.merchant_id = ?";
$stmt = mysqli_prepare($conn, $merchant_details_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$merchant_details_result = mysqli_stmt_get_result($stmt);
$merchant_details = mysqli_fetch_assoc($merchant_details_result);

// Get store images
$images_sql = "SELECT * FROM store_images WHERE merchant_id = ? ORDER BY display_order, created_at";
$stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$images_result = mysqli_stmt_get_result($stmt);

// Get store hours
$hours_sql = "SELECT * FROM store_hours WHERE merchant_id = ? ORDER BY day_of_week";
$stmt = mysqli_prepare($conn, $hours_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$hours_result = mysqli_stmt_get_result($stmt);

// Days of week for store hours
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #ffffff;
            --accent-color: #f5f5f5;
            --border-color: #e0e0e0;
            --text-color: #333333;
            --success-color: #28a745;
        }
        
        body {
            background-color: var(--accent-color);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .store-info {
            padding: 20px;
            background: rgba(255,255,255,0.05);
            margin: 20px;
            border-radius: 8px;
        }
        
        .store-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .store-status {
            font-size: 12px;
            padding: 3px 10px;
            background: var(--success-color);
            color: white;
            border-radius: 15px;
            display: inline-block;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 25px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            display: block;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--secondary-color);
            background: rgba(255,255,255,0.05);
            border-left-color: var(--secondary-color);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .nav-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 10px 25px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .stat-card {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        /* Image upload styles */
        .image-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .image-upload-area:hover {
            border-color: var(--primary-color);
            background-color: var(--accent-color);
        }
        
        .image-preview {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .image-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .image-preview:hover .image-overlay {
            opacity: 1;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-text,
            .store-info,
            .sidebar-header h4 {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include "includes/sidebar_only.php"; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Store Settings</h2>
                    <p class="text-muted mb-0">Manage your store information and preferences</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="bi bi-save me-2"></i> Save Changes
                    </button>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button">
                        Basic Information
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images" type="button">
                        Store Images
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hours-tab" data-bs-toggle="tab" data-bs-target="#hours" type="button">
                        Store Hours
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="settingsTabContent">
                <!-- Basic Information Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="stat-card">
                        <form id="basicForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Store Name *</label>
                                    <input type="text" name="store_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($merchant['store_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Brand Name</label>
                                    <input type="text" name="brand_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($merchant_details['brand_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Business Type</label>
                                    <select name="business_type" class="form-select">
                                        <option value="">Select Type</option>
                                        <option value="Restaurant" <?php echo ($merchant_details['business_type'] ?? '') == 'Restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                                        <option value="Cafe" <?php echo ($merchant_details['business_type'] ?? '') == 'Cafe' ? 'selected' : ''; ?>>Cafe</option>
                                        <option value="Bakery" <?php echo ($merchant_details['business_type'] ?? '') == 'Bakery' ? 'selected' : ''; ?>>Bakery</option>
                                        <option value="Fast Food" <?php echo ($merchant_details['business_type'] ?? '') == 'Fast Food' ? 'selected' : ''; ?>>Fast Food</option>
                                        <option value="Grocery" <?php echo ($merchant_details['business_type'] ?? '') == 'Grocery' ? 'selected' : ''; ?>>Grocery</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cuisine Types</label>
                                    <input type="text" name="cuisine_types" class="form-control" 
                                           value="<?php echo htmlspecialchars($merchant_details['cuisine_types'] ?? ''); ?>"
                                           placeholder="e.g., Ethiopian, Italian, Chinese">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Store Address *</label>
                                <textarea name="store_address" class="form-control" rows="3" required><?php echo htmlspecialchars($merchant['store_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="mobile_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($merchant_details['mobile_phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Store Phone</label>
                                    <input type="tel" name="store_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($merchant_details['store_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($merchant_details['email'] ?? ''); ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Website / Social Media</label>
                                <input type="url" name="social_media_website" class="form-control" 
                                       value="<?php echo htmlspecialchars($merchant_details['social_media_website'] ?? ''); ?>"
                                       placeholder="https://">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" 
                                          placeholder="Tell customers about your store..."><?php echo htmlspecialchars($merchant_details['description'] ?? ''); ?></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Store Images Tab -->
                <div class="tab-pane fade" id="images" role="tabpanel">
                    <div class="stat-card">
                        <h5 class="mb-4">Store Images</h5>
                        <p class="text-muted mb-4">Upload images of your store that will be displayed to customers on the home page.</p>
                        
                        <!-- Image Upload Form -->
                        <div class="mb-4">
                            <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                                <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                                <h6>Click to Upload Store Images</h6>
                                <p class="text-muted mb-0">You can upload multiple images (JPG, PNG, max 5MB each)</p>
                                <input type="file" id="imageInput" name="store_images[]" multiple accept="image/*" style="display: none;" onchange="uploadImages()">
                            </div>
                        </div>
                        
                        <!-- Current Images -->
                        <div class="row" id="imagesContainer">
                            <?php if ($images_result && mysqli_num_rows($images_result) > 0): ?>
                                <?php while($image = mysqli_fetch_assoc($images_result)): ?>
                                <div class="col-md-4 mb-3" id="image-<?php echo $image['id']; ?>">
                                    <div class="image-preview">
                                        <img src="<?php echo $image['image_path']; ?>" alt="Store Image">
                                        <div class="image-overlay">
                                            <button class="btn btn-danger btn-sm" onclick="deleteImage(<?php echo $image['id']; ?>)">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5" id="noImagesMessage">
                                    <i class="bi bi-images display-4 text-muted"></i>
                                    <p class="mt-3 text-muted">No images uploaded yet</p>
                                    <p class="text-muted">Upload store images to showcase your business to customers</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Store Hours Tab -->
                <div class="tab-pane fade" id="hours" role="tabpanel">
                    <div class="stat-card">
                        <h5 class="mb-4">Store Hours</h5>
                        <p class="text-muted mb-4">Set your business hours so customers know when you're open.</p>
                        
                        <form id="hoursForm">
                            <?php 
                            // Prepare hours array
                            $hours_array = [];
                            if ($hours_result && mysqli_num_rows($hours_result) > 0) {
                                while($hour = mysqli_fetch_assoc($hours_result)) {
                                    $hours_array[$hour['day_of_week']] = $hour;
                                }
                            }
                            
                            for ($i = 0; $i < 7; $i++): 
                                $hour = $hours_array[$i] ?? ['is_closed' => 1, 'open_time' => '', 'close_time' => ''];
                            ?>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input day-checkbox" type="checkbox" 
                                               id="day-<?php echo $i; ?>" 
                                               data-day="<?php echo $i; ?>"
                                               <?php echo !$hour['is_closed'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="day-<?php echo $i; ?>">
                                            <?php echo $days[$i]; ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control open-time" 
                                           data-day="<?php echo $i; ?>"
                                           value="<?php echo $hour['open_time']; ?>"
                                           <?php echo $hour['is_closed'] ? 'disabled' : ''; ?>>
                                </div>
                                <div class="col-md-1 text-center">to</div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control close-time" 
                                           data-day="<?php echo $i; ?>"
                                           value="<?php echo $hour['close_time']; ?>"
                                           <?php echo $hour['is_closed'] ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle store hours checkboxes
        document.querySelectorAll('.day-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.dataset.day;
                const openTime = document.querySelector(`.open-time[data-day="${day}"]`);
                const closeTime = document.querySelector(`.close-time[data-day="${day}"]`);
                
                if (this.checked) {
                    openTime.disabled = false;
                    closeTime.disabled = false;
                    openTime.required = true;
                    closeTime.required = true;
                } else {
                    openTime.disabled = true;
                    closeTime.disabled = true;
                    openTime.required = false;
                    closeTime.required = false;
                    openTime.value = '';
                    closeTime.value = '';
                }
            });
        });

        // Upload store images
        function uploadImages() {
            const input = document.getElementById('imageInput');
            const files = input.files;
            
            if (files.length === 0) {
                alert('Please select images to upload');
                return;
            }
            
            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('store_images[]', files[i]);
            }
            formData.append('merchant_id', <?php echo $merchant_id; ?>);
            
            // Show loading
            const uploadArea = document.querySelector('.image-upload-area');
            uploadArea.innerHTML = '<i class="bi bi-hourglass-split display-4 text-muted mb-3"></i><h6>Uploading...</h6>';
            
            fetch('includes/upload_store_images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Images uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    // Reset upload area
                    uploadArea.innerHTML = '<i class="bi bi-cloud-upload display-4 text-muted mb-3"></i><h6>Click to Upload Store Images</h6><p class="text-muted mb-0">You can upload multiple images (JPG, PNG, max 5MB each)</p>';
                }
            })
            .catch(error => {
                alert('Upload failed: ' + error.message);
                // Reset upload area
                uploadArea.innerHTML = '<i class="bi bi-cloud-upload display-4 text-muted mb-3"></i><h6>Click to Upload Store Images</h6><p class="text-muted mb-0">You can upload multiple images (JPG, PNG, max 5MB each)</p>';
            });
        }

        // Delete store image
        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch(`includes/delete_store_image.php?image_id=${imageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`image-${imageId}`).remove();
                        
                        // Check if no images left
                        const container = document.getElementById('imagesContainer');
                        if (container.children.length === 0) {
                            container.innerHTML = '<div class="col-12 text-center py-5" id="noImagesMessage"><i class="bi bi-images display-4 text-muted"></i><p class="mt-3 text-muted">No images uploaded yet</p><p class="text-muted">Upload store images to showcase your business to customers</p></div>';
                        }
                        
                        alert('Image deleted successfully!');
                    } else {
                        alert('Error deleting image: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Delete failed: ' + error.message);
                });
            }
        }

        // Save all settings
        function saveSettings() {
            // Collect basic info
            const basicData = new FormData(document.getElementById('basicForm'));
            
            // Collect hours
            const hoursData = [];
            document.querySelectorAll('.day-checkbox').forEach(checkbox => {
                const day = checkbox.dataset.day;
                hoursData.push({
                    day_of_week: day,
                    is_closed: !checkbox.checked,
                    open_time: document.querySelector(`.open-time[data-day="${day}"]`).value,
                    close_time: document.querySelector(`.close-time[data-day="${day}"]`).value
                });
            });
            
            const data = {
                basic: Object.fromEntries(basicData),
                hours: hoursData,
                merchant_id: <?php echo $merchant_id; ?>
            };
            
            fetch('includes/save_store_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Save failed: ' + error.message);
            });
        }
    </script>
</body>
</html>