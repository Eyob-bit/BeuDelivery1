<?php
session_start();
$page_title = "Menu Manager";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['merchant_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include "../includes/db.php";
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

// Get all menu categories for this merchant
$categories_sql = "SELECT * FROM menu_categories WHERE merchant_id = ? ORDER BY display_order, category_name";
$stmt = mysqli_prepare($conn, $categories_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$categories_result = mysqli_stmt_get_result($stmt);

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
                $is_available = isset($_POST['is_available']) ? 1 : 0;
                
                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/menu_items/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['image']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $file_name = 'item_' . $merchant_id . '_' . time() . '.' . $file_ext;
                        $image_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                            // Image uploaded successfully
                        } else {
                            $error = "Failed to upload image.";
                            break;
                        }
                    } else {
                        $error = "Invalid file type. Please upload JPG, PNG, or GIF images only.";
                        break;
                    }
                }
                
                $sql = "INSERT INTO menu_items (merchant_id, category_id, name, description, price, image, is_available) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissdsi", $merchant_id, $category_id, $name, $description, $price, $image_path, $is_available);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Menu item added successfully!";
                    if ($image_path) {
                        $success .= " Image uploaded to: " . $image_path;
                    }
                } else {
                    $error = "Error adding menu item: " . mysqli_error($conn);
                }
                break;
                
            case 'edit_item':
                $item_id = intval($_POST['item_id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
                $is_available = isset($_POST['is_available']) ? 1 : 0;
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/menu_items/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['image']['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $file_name = 'item_' . $merchant_id . '_' . time() . '.' . $file_ext;
                        $image_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                            $sql = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, image=?, is_available=? 
                                    WHERE id=? AND merchant_id=?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ssdisiii", $name, $description, $price, $category_id, $image_path, $is_available, $item_id, $merchant_id);
                        } else {
                            $error = "Failed to upload image.";
                            break;
                        }
                    } else {
                        $error = "Invalid file type. Please upload JPG, PNG, or GIF images only.";
                        break;
                    }
                } else {
                    $sql = "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, is_available=? 
                            WHERE id=? AND merchant_id=?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssdiiii", $name, $description, $price, $category_id, $is_available, $item_id, $merchant_id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Menu item updated successfully!";
                } else {
                    $error = "Error updating menu item: " . mysqli_error($conn);
                }
                break;
                
            case 'delete_item':
                $item_id = intval($_POST['item_id']);
                $sql = "DELETE FROM menu_items WHERE id=? AND merchant_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $item_id, $merchant_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Menu item deleted successfully!";
                } else {
                    $error = "Error deleting menu item: " . mysqli_error($conn);
                }
                break;
                
            case 'add_category':
                $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
                $sql = "INSERT INTO menu_categories (merchant_id, category_name) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $merchant_id, $category_name);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Category added successfully!";
                } else {
                    $error = "Error adding category: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get all menu items with category information
$items_sql = "SELECT mi.*, mc.category_name 
              FROM menu_items mi 
              LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id 
              WHERE mi.merchant_id = ? 
              ORDER BY mc.category_name, mi.name";
$stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Manager - BeU Delivery</title>
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
        
        /* Sidebar Styles - Same as Dashboard */
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar .nav-text,
            .store-info,
            .sidebar-header h4 {
                display: none;
            }
            
            .sidebar-header {
                padding: 20px 15px;
                text-align: center;
            }
            
            .nav-link {
                padding: 15px;
                text-align: center;
                border-left: none;
                border-right: 3px solid transparent;
            }
            
            .nav-link:hover,
            .nav-link.active {
                border-left: none;
                border-right-color: var(--secondary-color);
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar - Same as Dashboard -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0">BEU</h4>
                <small class="text-muted">Merchant Portal</small>
            </div>
            
            <div class="store-info">
                <div class="store-name"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                <span class="store-status">
                    <i class="bi bi-check-circle me-1"></i> Active
                </span>
            </div>
            
            <nav class="nav flex-column">
                <a href="merchant_dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="bi bi-bag-check"></i>
                    <span class="nav-text">Orders</span>
                </a>
                <a href="menu_manager.php" class="nav-link active">
                    <i class="bi bi-menu-button-wide"></i>
                    <span class="nav-text">Menu Manager</span>
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i>
                    <span class="nav-text">Reports</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Settings</span>
                </a>
                
                <hr class="nav-divider">
                
                <a href="customer_feedback.php" class="nav-link">
                    <i class="bi bi-chat-dots"></i>
                    <span class="nav-text">Customer Feedback</span>
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
        </div>

<div class="dashboard-wrapper">
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Menu Manager</h2>
                <p class="text-muted mb-0">Add, edit, and manage your menu items and categories</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-folder-plus me-2"></i> Add Category
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="bi bi-plus-circle me-2"></i> Add Menu Item
                </button>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Menu Items Grid -->
        <div class="row g-4">
            <?php if (mysqli_num_rows($items_result) > 0): ?>
                <?php 
                $current_category = '';
                while ($item = mysqli_fetch_assoc($items_result)): 
                    // Show category header if it's a new category
                    if ($item['category_name'] !== $current_category) {
                        if ($current_category !== '') {
                            echo '</div></div>'; // Close previous category
                        }
                        $current_category = $item['category_name'];
                        echo '<div class="col-12"><h4 class="mt-4 mb-3">' . 
                             ($current_category ? htmlspecialchars($current_category) : 'Uncategorized') . 
                             '</h4><div class="row g-4">';
                    }
                ?>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <?php if ($item['image'] && file_exists($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                             class="img-fluid rounded mb-3" 
                             style="height: 200px; width: 100%; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="bg-light rounded mb-3 align-items-center justify-content-center" 
                             style="height: 200px; display: none;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <?php else: ?>
                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h5 class="mb-2"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                        <h4 class="text-primary mb-3">$<?php echo number_format($item['price'], 2); ?></h4>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge <?php echo $item['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick='editItem(<?php echo json_encode($item); ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div></div> <!-- Close last category -->
            <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-menu-button-wide display-1 text-muted mb-3"></i>
                    <h4>No menu items yet</h4>
                    <p class="text-muted">Start by adding your first menu item</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus-circle me-2"></i> Add First Item
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_item">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category (Optional)</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): 
                            ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_available" class="form-check-input" id="addAvailable" checked>
                        <label class="form-check-label" for="addAvailable">Available for order</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_item">
                <input type="hidden" name="item_id" id="edit_item_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="edit_category_id" class="form-select">
                            <option value="">Select Category (Optional)</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): 
                            ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price *</label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_available" class="form-check-input" id="edit_available">
                        <label class="form-check-label" for="edit_available">Available for order</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="item_id" id="delete_item_id">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_item_name"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" required 
                               placeholder="e.g., Appetizers, Main Courses, Desserts">
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Categories help organize your menu items and make it easier for customers to browse.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editItem(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_price').value = item.price;
    document.getElementById('edit_category_id').value = item.category_id || '';
    document.getElementById('edit_available').checked = item.is_available == 1;
    
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
}

function deleteItem(id, name) {
    document.getElementById('delete_item_id').value = id;
    document.getElementById('delete_item_name').textContent = name;
    
    new bootstrap.Modal(document.getElementById('deleteItemModal')).show();
}
</script>
</body>
</html>
