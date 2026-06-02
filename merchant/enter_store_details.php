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

// Fetch existing store details if any
$details_sql = "SELECT * FROM merchant_details WHERE merchant_id = '$merchant_id'";
$details_result = mysqli_query($conn, $details_sql);
$existing_details = mysqli_fetch_assoc($details_result);

// Handle form submission
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_store_details'])) {
    $store_phone = mysqli_real_escape_string($conn, trim($_POST['store_phone']));
    $pickup_type = mysqli_real_escape_string($conn, $_POST['pickup_type']);
    $pickup_instructions = mysqli_real_escape_string($conn, trim($_POST['pickup_instructions']));
    $launch_option = mysqli_real_escape_string($conn, $_POST['launch_option']);
    $launch_date = $launch_option == 'set_date' ? mysqli_real_escape_string($conn, $_POST['launch_date']) : null;
    
    // Get selected cuisines
    $selected_cuisines = [];
    if (isset($_POST['cuisines']) && is_array($_POST['cuisines'])) {
        foreach ($_POST['cuisines'] as $cuisine) {
            $selected_cuisines[] = mysqli_real_escape_string($conn, trim($cuisine));
        }
    }
    $cuisine_json = json_encode($selected_cuisines);
    
    // Default pickup instructions if "Use default" is selected
    if ($pickup_type == 'default') {
        $pickup_instructions = "Enter the store and pick up at counter.";
    }
    
    // Store hours (default) - must be JSON format
    $store_hours = json_encode([
        "Monday" => "10:00 AM - 5:00 PM",
        "Tuesday" => "10:00 AM - 5:00 PM",
        "Wednesday" => "10:00 AM - 5:00 PM",
        "Thursday" => "10:00 AM - 5:00 PM",
        "Friday" => "10:00 AM - 5:00 PM",
        "Saturday" => "Closed",
        "Sunday" => "Closed"
    ]);
    
    // Validation
    if (empty($store_phone)) {
        $error = "Please enter your store phone number";
    } elseif (empty($selected_cuisines)) {
        $error = "Please select at least one cuisine";
    } elseif ($pickup_type == 'custom' && empty($pickup_instructions)) {
        $error = "Please enter custom pickup instructions";
    } elseif ($launch_option == 'set_date' && empty($launch_date)) {
        $error = "Please select a launch date";
    } else {
        // Insert or update store details
        if ($existing_details) {
            // Update existing details
            $update_sql = "UPDATE merchant_details SET 
                          store_phone = '$store_phone',
                          cuisine_types = '$cuisine_json',
                          pickup_instructions = '$pickup_instructions',
                          launch_date = " . ($launch_date ? "'$launch_date'" : "NULL") . ",
                          store_hours = '$store_hours',
                          updated_at = NOW()
                          WHERE merchant_id = '$merchant_id'";
        } else {
            // Insert new details
            $update_sql = "INSERT INTO merchant_details 
                          (merchant_id, store_phone, cuisine_types, pickup_instructions, launch_date, store_hours)
                          VALUES ('$merchant_id', '$store_phone', '$cuisine_json', '$pickup_instructions', 
                          " . ($launch_date ? "'$launch_date'" : "NULL") . ", '$store_hours')";
        }
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Store details saved successfully! Redirecting back to setup...";
            header("refresh:2;url=setup.php");
        } else {
            $error = "Failed to save store details: " . mysqli_error($conn);
        }
    }
}

// Pre-fill form data if exists
$store_phone = $existing_details['store_phone'] ?? $merchant['mobile_phone'] ?? '';
$pickup_instructions = $existing_details['pickup_instructions'] ?? 'Enter the store and pick up at counter.';
$pickup_type = !empty($existing_details['pickup_instructions']) ? 'custom' : 'default';
$launch_date = $existing_details['launch_date'] ?? date('Y-m-d', strtotime('+1 week'));

// Get selected cuisines from existing data
$selected_cuisines = [];
if (!empty($existing_details['cuisine_types'])) {
    $cuisine_data = json_decode($existing_details['cuisine_types'], true);
    if (is_array($cuisine_data)) {
        $selected_cuisines = $cuisine_data;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Store Details - BeU Delivery</title>
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
        
        .setup-container {
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

        /* Form Sections */
        .form-section {
            margin-bottom: 40px;
        }

        .form-section h4 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-label-custom {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
            display: block;
        }

        /* Phone Number Input */
        .phone-input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 6px;
            overflow: hidden;
            background-color: #fff;
        }

        .country-flag {
            padding: 0 10px;
            font-size: 1.5rem;
            border-right: 1px solid #eee;
            line-height: 1;
        }

        .phone-input {
            border: none;
            box-shadow: none;
            flex-grow: 1;
            height: 45px;
            padding: 0 10px;
            font-size: 1rem;
        }
        
        .phone-input:focus {
            outline: none;
            box-shadow: none;
        }

        .phone-clear-btn {
            background: none;
            border: none;
            color: #6c757d;
            padding: 0 10px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* Cuisine Chips */
        .cuisine-selector {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            min-height: 50px;
        }

        .cuisine-chip {
            background-color: #f0f0f0;
            color: #333;
            border-radius: 15px;
            padding: 5px 12px;
            margin-right: 8px;
            margin-bottom: 5px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .cuisine-chip.selected {
            background-color: #000;
            color: white;
        }
        
        .chip-remove {
            background: none;
            border: none;
            color: #6c757d;
            margin-left: 5px;
            padding: 0;
            font-size: 0.8rem;
            line-height: 1;
            cursor: pointer;
        }
        
        .cuisine-chip.selected .chip-remove {
            color: white;
        }

        .cuisine-search {
            display: flex;
            align-items: center;
            margin-left: 5px;
        }
        
        .cuisine-search input {
            border: none;
            outline: none;
            padding: 5px;
            flex-grow: 1;
            min-width: 150px;
            font-size: 0.9rem;
        }
        
        .search-icon {
            color: #6c757d;
            margin-right: 5px;
        }

        /* Radio Group Styling */
        .radio-group-container {
            margin-top: 15px;
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-label {
            font-weight: 500;
            cursor: pointer;
        }

        .custom-textarea {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
            resize: none;
            width: 100%;
            height: 80px;
            font-size: 0.9rem;
            color: #333;
            transition: all 0.3s;
        }
        
        .custom-textarea:focus {
            outline: none;
            border-color: #000;
            background-color: white;
        }
        
        .custom-textarea.hidden {
            display: none;
        }

        /* Date Picker */
        .date-picker-group {
            max-width: 250px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .date-picker-group.hidden {
            display: none;
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
            background-color: black;
            color: white;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-submit:hover {
            background-color: #333;
        }
        
        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
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
        
        @media (max-width: 768px) {
            .page-content {
                padding: 15px;
            }
            
            .setup-container {
                max-width: 100%;
            }
            
            .footer-buttons {
                flex-direction: column;
            }
            
            .btn-cancel, .btn-submit {
                width: 100%;
                text-align: center;
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
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="setup-container">
            <a href="setup.php" class="back-arrow">&larr;</a>
            <h1 class="h3 fw-bold mb-4">Enter store details</h1>
            
            <form method="POST" action="" id="storeDetailsForm">
                <input type="hidden" name="submit_store_details" value="1">

                <div class="form-section">
                    <h4 class="fw-bold">What's your store's phone number?</h4>
                    <p class="form-label-custom">We'll call this number or share it with a user if there's an issue with an active order.</p>
                    <div class="phone-input-group">
                        <span class="country-flag">🇺🇸</span>
                        <input type="tel" class="phone-input" name="store_phone" id="storePhone" 
                               placeholder="+1 3546565334" value="<?php echo htmlspecialchars($store_phone); ?>" required>
                        <button type="button" class="phone-clear-btn" id="clearPhone"><i class="bi bi-x"></i></button>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="fw-bold">What kinds of food do you offer at your store?</h4>
                    <p class="form-label-custom">Select up to 3 cuisines</p>
                    
                    <div class="cuisine-selector" id="cuisineSelector">
                        <!-- Cuisine chips will be added here by JavaScript -->
                    </div>
                    <input type="hidden" name="cuisines[]" id="selectedCuisines" value='<?php echo json_encode($selected_cuisines); ?>'>
                    
                    <div class="cuisine-search d-flex align-items-center mt-3">
                         <span class="search-icon"><i class="bi bi-search"></i></span>
                         <input type="text" id="cuisineSearch" placeholder="Search or add cuisine..." 
                                onkeypress="addCuisineOnEnter(event)">
                         <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="addCustomCuisine()">Add</button>
                    </div>
                    
                    <div class="mt-2 small text-muted">
                        Selected: <span id="selectedCount">0</span>/3
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="fw-bold">Set up your store</h4>
                    <p class="form-label-custom">Let delivery people know how to pickup the order.</p>
                    
                    <div class="radio-group-container">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pickup_type" id="useDefault" 
                                   value="default" <?php echo ($pickup_type == 'default') ? 'checked' : ''; ?> 
                                   onchange="togglePickupInstructions()">
                            <label class="form-check-label" for="useDefault">
                                Use default
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pickup_type" id="setCustom" 
                                   value="custom" <?php echo ($pickup_type == 'custom') ? 'checked' : ''; ?> 
                                   onchange="togglePickupInstructions()">
                            <label class="form-check-label" for="setCustom">
                                Set custom
                            </label>
                            <textarea class="custom-textarea <?php echo ($pickup_type == 'default') ? 'hidden' : ''; ?>" 
                                      name="pickup_instructions" id="pickupInstructions" 
                                      placeholder="Enter custom pickup instructions..."><?php echo htmlspecialchars($pickup_instructions); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="fw-bold">First day on BeU Delivery</h4>
                    <p class="form-label-custom">Stores usually open around a week after finishing setup. If you need extra time, you can set a date.</p>
                    
                    <div class="radio-group-container">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="launch_option" id="openAsSoonAsPossible" 
                                   value="asap" <?php echo empty($launch_date) ? 'checked' : ''; ?> 
                                   onchange="toggleLaunchDate()">
                            <label class="form-check-label" for="openAsSoonAsPossible">
                                Open as soon as possible
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="launch_option" id="setDate" 
                                   value="set_date" <?php echo !empty($launch_date) ? 'checked' : ''; ?> 
                                   onchange="toggleLaunchDate()">
                            <label class="form-check-label" for="setDate">
                                Set a date
                            </label>
                            <div class="date-picker-group <?php echo empty($launch_date) ? 'hidden' : ''; ?>">
                                <input type="date" class="form-control" name="launch_date" id="launchDate" 
                                       value="<?php echo htmlspecialchars($launch_date); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer-buttons">
                    <a href="setup.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-submit" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Available cuisines
        const availableCuisines = [
            "African", "Ethiopian", "Vegetarian", "Italian", "Chinese", "Mexican", 
            "Indian", "Japanese", "American", "Mediterranean", "Thai", "Vietnamese",
            "Korean", "Middle Eastern", "Greek", "French", "Spanish", "Brazilian"
        ];
        
        // Selected cuisines from PHP
        let selectedCuisines = <?php echo json_encode($selected_cuisines); ?>;
        const MAX_CUISINES = 3;
        
        // Initialize cuisine selector
        function initializeCuisineSelector() {
            const selector = document.getElementById('cuisineSelector');
            selector.innerHTML = '';
            
            // Add selected cuisines first
            selectedCuisines.forEach(cuisine => {
                addCuisineChip(cuisine, true);
            });
            
            // Add available cuisines that aren't selected
            availableCuisines.forEach(cuisine => {
                if (!selectedCuisines.includes(cuisine)) {
                    addCuisineChip(cuisine, false);
                }
            });
            
            updateCuisineCount();
        }
        
        // Add a cuisine chip
        function addCuisineChip(cuisine, isSelected) {
            const selector = document.getElementById('cuisineSelector');
            const chip = document.createElement('div');
            chip.className = `cuisine-chip ${isSelected ? 'selected' : ''}`;
            chip.innerHTML = `
                ${cuisine}
                ${isSelected ? '<button type="button" class="chip-remove" onclick="toggleCuisine(\'' + cuisine + '\')"><i class="bi bi-x"></i></button>' : ''}
            `;
            
            if (!isSelected) {
                chip.onclick = () => toggleCuisine(cuisine);
            }
            
            selector.appendChild(chip);
        }
        
        // Toggle cuisine selection
        function toggleCuisine(cuisine) {
            const index = selectedCuisines.indexOf(cuisine);
            
            if (index === -1) {
                // Add cuisine if under limit
                if (selectedCuisines.length < MAX_CUISINES) {
                    selectedCuisines.push(cuisine);
                } else {
                    alert(`You can only select up to ${MAX_CUISINES} cuisines.`);
                    return;
                }
            } else {
                // Remove cuisine
                selectedCuisines.splice(index, 1);
            }
            
            // Update hidden input
            document.getElementById('selectedCuisines').value = JSON.stringify(selectedCuisines);
            
            // Re-initialize selector
            initializeCuisineSelector();
        }
        
        // Add custom cuisine
        function addCustomCuisine() {
            const searchInput = document.getElementById('cuisineSearch');
            const customCuisine = searchInput.value.trim();
            
            if (customCuisine && customCuisine.length > 0) {
                if (!selectedCuisines.includes(customCuisine) && !availableCuisines.includes(customCuisine)) {
                    if (selectedCuisines.length < MAX_CUISINES) {
                        selectedCuisines.push(customCuisine);
                        document.getElementById('selectedCuisines').value = JSON.stringify(selectedCuisines);
                        initializeCuisineSelector();
                        searchInput.value = '';
                    } else {
                        alert(`You can only select up to ${MAX_CUISINES} cuisines.`);
                    }
                } else if (selectedCuisines.includes(customCuisine)) {
                    alert('This cuisine is already selected.');
                }
            }
        }
        
        // Add cuisine on Enter key
        function addCuisineOnEnter(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addCustomCuisine();
            }
        }
        
        // Update cuisine count
        function updateCuisineCount() {
            document.getElementById('selectedCount').textContent = selectedCuisines.length;
            
            // Enable/disable submit button based on selection
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = selectedCuisines.length === 0;
        }
        
        // Toggle pickup instructions textarea
        function togglePickupInstructions() {
            const customTextarea = document.getElementById('pickupInstructions');
            const useDefaultRadio = document.getElementById('useDefault');
            
            if (useDefaultRadio.checked) {
                customTextarea.classList.add('hidden');
                customTextarea.required = false;
            } else {
                customTextarea.classList.remove('hidden');
                customTextarea.required = true;
                customTextarea.focus();
            }
        }
        
        // Toggle launch date picker
        function toggleLaunchDate() {
            const datePicker = document.querySelector('.date-picker-group');
            const setDateRadio = document.getElementById('setDate');
            
            if (setDateRadio.checked) {
                datePicker.classList.remove('hidden');
                document.getElementById('launchDate').required = true;
            } else {
                datePicker.classList.add('hidden');
                document.getElementById('launchDate').required = false;
            }
        }
        
        // Clear phone number
        document.getElementById('clearPhone').addEventListener('click', function() {
            document.getElementById('storePhone').value = '';
            document.getElementById('storePhone').focus();
        });
        
        // Form validation
        document.getElementById('storeDetailsForm').addEventListener('submit', function(e) {
            if (selectedCuisines.length === 0) {
                e.preventDefault();
                alert('Please select at least one cuisine.');
                return false;
            }
            
            if (selectedCuisines.length > MAX_CUISINES) {
                e.preventDefault();
                alert(`You can only select up to ${MAX_CUISINES} cuisines.`);
                return false;
            }
            
            return true;
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeCuisineSelector();
            togglePickupInstructions();
            toggleLaunchDate();
        });
    </script>
</body>
</html>