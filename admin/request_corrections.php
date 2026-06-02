<?php
/**
 * Request Corrections from Merchant
 * Admin can specify which fields need to be corrected
 */

session_start();
require_once "admin_auth.php";
include "../includes/db.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_merchants.php");
    exit();
}

$merchant_id = mysqli_real_escape_string($conn, $_GET['id']);
$admin_id = $_SESSION['user_id'];

// Get merchant data
$merchant_sql = "SELECT m.*, u.first_name, u.last_name, u.email, u.phone
                 FROM merchants m
                 JOIN users u ON m.user_id = u.id
                 WHERE m.merchant_id = '$merchant_id'";
$merchant_result = mysqli_query($conn, $merchant_sql);

if (!$merchant_result || mysqli_num_rows($merchant_result) == 0) {
    header("Location: admin_merchants.php");
    exit();
}

$merchant = mysqli_fetch_assoc($merchant_result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_corrections'])) {
    $corrections = [];
    
    // Collect all requested corrections
    if (!empty($_POST['store_info'])) $corrections['store_info'] = mysqli_real_escape_string($conn, $_POST['store_info']);
    if (!empty($_POST['banking_info'])) $corrections['banking_info'] = mysqli_real_escape_string($conn, $_POST['banking_info']);
    if (!empty($_POST['tax_info'])) $corrections['tax_info'] = mysqli_real_escape_string($conn, $_POST['tax_info']);
    if (!empty($_POST['documents'])) $corrections['documents'] = mysqli_real_escape_string($conn, $_POST['documents']);
    if (!empty($_POST['menu'])) $corrections['menu'] = mysqli_real_escape_string($conn, $_POST['menu']);
    if (!empty($_POST['other'])) $corrections['other'] = mysqli_real_escape_string($conn, $_POST['other']);
    
    $general_notes = mysqli_real_escape_string($conn, $_POST['general_notes'] ?? '');
    
    if (empty($corrections)) {
        $error = "Please specify at least one correction needed.";
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // Update merchant status to needs_correction
            mysqli_query($conn, "UPDATE merchants SET status = 'under_review' WHERE merchant_id = '$merchant_id'");
            
            // Create or update review with corrections needed
            $corrections_json = json_encode($corrections);
            $review_check = mysqli_query($conn, "SELECT id FROM merchant_reviews WHERE merchant_id = '$merchant_id'");
            
            if (mysqli_num_rows($review_check) > 0) {
                mysqli_query($conn, "UPDATE merchant_reviews SET 
                    status = 'needs_info',
                    admin_comments = '$general_notes',
                    corrections_needed = '$corrections_json',
                    reviewed_at = NOW(),
                    reviewed_by = '$admin_id'
                    WHERE merchant_id = '$merchant_id'");
            } else {
                $review_id = 'REV' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                mysqli_query($conn, "INSERT INTO merchant_reviews 
                    (review_id, merchant_id, status, admin_comments, corrections_needed, reviewed_at, reviewed_by, submitted_at)
                    VALUES ('$review_id', '$merchant_id', 'needs_info', '$general_notes', '$corrections_json', NOW(), '$admin_id', NOW())");
            }
            
            // TODO: Send email notification to merchant
            
            mysqli_commit($conn);
            header("Location: admin_merchant_details.php?id=$merchant_id&success=corrections_requested");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Corrections - Admin Panel</title>
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
            width: 250px;
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
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .correction-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .correction-section:hover {
            border-color: #ffc107;
        }
        
        .correction-section.selected {
            border-color: #ffc107;
            background: #fffbf0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        
        textarea.form-control {
            min-height: 100px;
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Request Corrections</h2>
                <p class="text-muted mb-0">Merchant: <strong><?php echo htmlspecialchars($merchant['store_name']); ?></strong></p>
            </div>
            <a href="admin_merchant_details.php?id=<?php echo $merchant_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Details
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Note:</strong> Select the sections that need corrections and provide specific feedback for each.
            The merchant will be notified and can resubmit the corrected information.
        </div>

        <form method="POST" id="correctionsForm">
            <input type="hidden" name="submit_corrections" value="1">

            <!-- Store Information -->
            <div class="correction-section" id="section-store">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Store Information</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_store" onchange="toggleSection('store')">
                            <label class="form-check-label" for="check_store">
                                Needs correction
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-store" style="display: none;">
                    <textarea name="store_info" class="form-control" placeholder="Specify what needs to be corrected in store information (name, address, hours, etc.)"></textarea>
                </div>
            </div>

            <!-- Banking Information -->
            <div class="correction-section" id="section-banking">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Banking Information</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_banking" onchange="toggleSection('banking')">
                            <label class="form-check-label" for="check_banking">
                                Needs correction
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-banking" style="display: none;">
                    <textarea name="banking_info" class="form-control" placeholder="Specify what needs to be corrected in banking information (account number, routing number, etc.)"></textarea>
                </div>
            </div>

            <!-- Tax Information -->
            <div class="correction-section" id="section-tax">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Tax Information</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_tax" onchange="toggleSection('tax')">
                            <label class="form-check-label" for="check_tax">
                                Needs correction
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-tax" style="display: none;">
                    <textarea name="tax_info" class="form-control" placeholder="Specify what needs to be corrected in tax information (EIN, SSN, classification, etc.)"></textarea>
                </div>
            </div>

            <!-- Documents -->
            <div class="correction-section" id="section-documents">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-files"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Documents</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_documents" onchange="toggleSection('documents')">
                            <label class="form-check-label" for="check_documents">
                                Needs correction
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-documents" style="display: none;">
                    <textarea name="documents" class="form-control" placeholder="Specify what documents need to be resubmitted or corrected"></textarea>
                </div>
            </div>

            <!-- Menu -->
            <div class="correction-section" id="section-menu">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-menu-button-wide"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Menu</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_menu" onchange="toggleSection('menu')">
                            <label class="form-check-label" for="check_menu">
                                Needs correction
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-menu" style="display: none;">
                    <textarea name="menu" class="form-control" placeholder="Specify what needs to be corrected in the menu (images, items, pricing, etc.)"></textarea>
                </div>
            </div>

            <!-- Other Issues -->
            <div class="correction-section" id="section-other">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="section-title">Other Issues</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check_other" onchange="toggleSection('other')">
                            <label class="form-check-label" for="check_other">
                                Has other issues
                            </label>
                        </div>
                    </div>
                </div>
                <div id="content-other" style="display: none;">
                    <textarea name="other" class="form-control" placeholder="Specify any other issues or corrections needed"></textarea>
                </div>
            </div>

            <!-- General Notes -->
            <div class="correction-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-chat-text"></i>
                    </div>
                    <h5 class="section-title">General Notes (Optional)</h5>
                </div>
                <textarea name="general_notes" class="form-control" placeholder="Add any general notes or instructions for the merchant..."></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-warning btn-lg">
                    <i class="bi bi-send"></i> Send Correction Request
                </button>
                <a href="admin_merchant_details.php?id=<?php echo $merchant_id; ?>" class="btn btn-outline-secondary btn-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSection(section) {
            const checkbox = document.getElementById('check_' + section);
            const content = document.getElementById('content_' + section);
            const sectionDiv = document.getElementById('section_' + section);
            
            if (checkbox.checked) {
                content.style.display = 'block';
                sectionDiv.classList.add('selected');
                content.querySelector('textarea').focus();
            } else {
                content.style.display = 'none';
                sectionDiv.classList.remove('selected');
                content.querySelector('textarea').value = '';
            }
        }

        // Form validation
        document.getElementById('correctionsForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.form-check-input[type="checkbox"]');
            let anyChecked = false;
            
            checkboxes.forEach(cb => {
                if (cb.checked) anyChecked = true;
            });
            
            if (!anyChecked) {
                e.preventDefault();
                alert('Please select at least one section that needs correction.');
                return false;
            }
        });
    </script>
</body>
</html>
