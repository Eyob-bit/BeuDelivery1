<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include "../includes/db.php";

if (!isset($_SESSION['merchant_id'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings - BeU Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Store Settings</h1>
        <p>Merchant: <?php echo htmlspecialchars($merchant['store_name']); ?></p>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="settingsTab" role="tablist">
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
        <div class="tab-content mt-3" id="settingsTabContent">
            <!-- Basic Information Tab -->
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5>Basic Information</h5>
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Store Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($merchant['store_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Address</label>
                                <textarea class="form-control" rows="3"><?php echo htmlspecialchars($merchant['store_address'] ?? ''); ?></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Store Images Tab -->
            <div class="tab-pane fade" id="images" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5>Store Images</h5>
                        <p>Upload images of your store that will be displayed to customers.</p>
                        
                        <div class="border border-dashed p-4 text-center mb-3" style="border-color: #ddd;">
                            <i class="bi bi-cloud-upload display-4 text-muted mb-3"></i>
                            <h6>Click to Upload Store Images</h6>
                            <p class="text-muted">You can upload multiple images (JPG, PNG, max 5MB each)</p>
                            <input type="file" class="form-control" multiple accept="image/*">
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-center py-5">
                                <i class="bi bi-images display-4 text-muted"></i>
                                <p class="mt-3 text-muted">No images uploaded yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Store Hours Tab -->
            <div class="tab-pane fade" id="hours" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5>Store Hours</h5>
                        <p>Set your business hours so customers know when you're open.</p>
                        
                        <?php 
                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        for ($i = 0; $i < 7; $i++): 
                        ?>
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="day-<?php echo $i; ?>">
                                    <label class="form-check-label" for="day-<?php echo $i; ?>">
                                        <?php echo $days[$i]; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="time" class="form-control">
                            </div>
                            <div class="col-md-1 text-center">to</div>
                            <div class="col-md-4">
                                <input type="time" class="form-control">
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <button class="btn btn-primary">Save Changes</button>
            <a href="merchant_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>