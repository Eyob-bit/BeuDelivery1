<?php
session_start();
require_once "admin_auth.php";
include "../includes/db.php";

$admin_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current admin data
$admin_sql = "SELECT * FROM users WHERE id = $admin_id";
$admin_result = mysqli_query($conn, $admin_sql);
$admin = mysqli_fetch_assoc($admin_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        // Handle profile image upload
        $profile_image = $admin['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/admin_profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if ($profile_image && file_exists('../' . $profile_image)) {
                        unlink('../' . $profile_image);
                    }
                    $profile_image = 'uploads/admin_profiles/' . $new_filename;
                }
            }
        }
        
        // Update admin profile
        $update_sql = "UPDATE users SET 
            first_name = '$first_name',
            last_name = '$last_name',
            email = '$email',
            phone = '$phone',
            profile_image = '$profile_image'
            WHERE id = $admin_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $success_message = 'Profile updated successfully!';
            // Refresh admin data
            $admin_result = mysqli_query($conn, $admin_sql);
            $admin = mysqli_fetch_assoc($admin_result);
        } else {
            $error_message = 'Error updating profile: ' . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $admin['password_hash'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE users SET password_hash = '$new_hash' WHERE id = $admin_id";
                    
                    if (mysqli_query($conn, $update_sql)) {
                        $success_message = 'Password changed successfully!';
                    } else {
                        $error_message = 'Error changing password: ' . mysqli_error($conn);
                    }
                } else {
                    $error_message = 'New password must be at least 6 characters long.';
                }
            } else {
                $error_message = 'New passwords do not match.';
            }
        } else {
            $error_message = 'Current password is incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #000000;
            --secondary: #06C167;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            width: 260px;
            background: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f0f0f0;
            margin-bottom: 20px;
        }
        
        .profile-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .btn-upload {
            position: relative;
            overflow: hidden;
        }
        
        .btn-upload input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Admin Settings</h2>
                <p class="text-muted mb-0">Manage your profile and account settings</p>
            </div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Profile Settings -->
        <div class="settings-card">
            <h4 class="section-title">
                <i class="bi bi-person-circle me-2"></i>Profile Information
            </h4>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="row">
                    <div class="col-md-12 text-center mb-4">
                        <?php if ($admin['profile_image']): ?>
                        <img src="../<?php echo htmlspecialchars($admin['profile_image']); ?>" 
                             class="profile-image-preview" 
                             id="imagePreview"
                             alt="Profile">
                        <?php else: 
                            $initials = strtoupper(substr($admin['first_name'] ?? 'A', 0, 1) . substr($admin['last_name'] ?? 'D', 0, 1));
                        ?>
                        <div class="profile-placeholder mx-auto" id="placeholderPreview">
                            <?php echo $initials; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="btn-upload btn btn-outline-primary">
                            <i class="bi bi-camera"></i> Change Photo
                            <input type="file" name="profile_image" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <p class="text-muted mt-2 mb-0">
                            <small>Allowed: JPG, PNG, GIF. Max 5MB</small>
                        </p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Password Settings -->
        <div class="settings-card">
            <h4 class="section-title">
                <i class="bi bi-shield-lock me-2"></i>Change Password
            </h4>
            
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" 
                               minlength="6" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               minlength="6" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Account Information -->
        <div class="settings-card">
            <h4 class="section-title">
                <i class="bi bi-info-circle me-2"></i>Account Information
            </h4>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">User ID</label>
                    <p class="mb-0"><strong><?php echo $admin['id']; ?></strong></p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Account Type</label>
                    <p class="mb-0"><span class="badge bg-danger">Administrator</span></p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Account Created</label>
                    <p class="mb-0"><strong><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></strong></p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Last Login</label>
                    <p class="mb-0"><strong><?php echo $admin['last_login'] ? date('F j, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?></strong></p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Account Status</label>
                    <p class="mb-0">
                        <?php if ($admin['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label text-muted">Email Verified</label>
                    <p class="mb-0">
                        <?php if ($admin['is_verified']): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>
                        <?php else: ?>
                        <span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> Not Verified</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    const placeholder = document.getElementById('placeholderPreview');
                    
                    if (preview) {
                        preview.src = e.target.result;
                    } else if (placeholder) {
                        // Replace placeholder with image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-image-preview';
                        img.id = 'imagePreview';
                        placeholder.parentNode.replaceChild(img, placeholder);
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
