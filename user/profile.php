<?php
session_start();
include "../includes/db.php";
include "includes/auth_check.php";

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Get user addresses if you have addresses table
$addresses_sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$stmt = mysqli_prepare($conn, $addresses_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$addresses_result = mysqli_stmt_get_result($stmt);

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssi", $first_name, $last_name, $phone, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $message = "Profile updated successfully!";
        } else {
            $error = "Error updating profile. Please try again.";
        }
    }
    
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error = "New passwords don't match!";
        } else {
            // Check current password (if you have password hashing)
            // For now, we'll just update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $password_sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Password updated successfully!";
            } else {
                $error = "Error updating password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #00B26A 0%, #009956 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #00B26A;
            margin: 0 auto 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include "../partials/navbar.php"; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
            <div class="profile-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p class="mb-0">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h4 class="mb-4">
                        <i class="bi bi-person me-2"></i> Personal Information
                    </h4>
                    
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Password Change -->
            <div class="col-lg-6">
                <div class="profile-card">
                    <h4 class="mb-4">
                        <i class="bi bi-shield-lock me-2"></i> Security
                    </h4>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-primary">
                            <i class="bi bi-key me-2"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Account Stats -->
                <div class="profile-card mt-4">
                    <h4 class="mb-4">
                        <i class="bi bi-graph-up me-2"></i> Account Statistics
                    </h4>
                    
                    <?php
                    // Get order stats
                    $order_stats_sql = "SELECT 
                                            COUNT(*) as total_orders,
                                            SUM(total) as total_spent
                                        FROM orders 
                                        WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $order_stats_sql);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $stats_result = mysqli_stmt_get_result($stmt);
                    $stats = mysqli_fetch_assoc($stats_result);
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $stats['total_orders'] ?? 0; ?></h3>
                            <p class="text-muted">Total Orders</p>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                            <p class="text-muted">Total Spent</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Addresses Section -->
        <div class="profile-card mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-geo-alt me-2"></i> Saved Addresses
                </h4>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="bi bi-plus me-2"></i> Add New Address
                </button>
            </div>
            
            <div class="row">
                <?php if (mysqli_num_rows($addresses_result) > 0): ?>
                    <?php while($address = mysqli_fetch_assoc($addresses_result)): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                            <div class="card-body">
                                <?php if ($address['is_default']): ?>
                                <span class="badge bg-primary mb-2">Default</span>
                                <?php endif; ?>
                                
                                <h6><?php echo htmlspecialchars($address['address_label'] ?? 'Home'); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($address['street_address']); ?></p>
                                <p class="text-muted mb-0">
                                    <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                </p>
                                
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                    <?php if (!$address['is_default']): ?>
                                    <button class="btn btn-sm btn-outline-secondary">Set as Default</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-4">
                        <i class="bi bi-geo display-4 text-muted"></i>
                        <p class="mt-3">No saved addresses</p>
                        <p class="text-muted">Add your delivery addresses for faster checkout</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAddressForm">
                        <div class="mb-3">
                            <label class="form-label">Address Label</label>
                            <input type="text" class="form-control" placeholder="e.g., Home, Work, Office">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Street Address</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="setDefault">
                            <label class="form-check-label" for="setDefault">
                                Set as default delivery address
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Address</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>