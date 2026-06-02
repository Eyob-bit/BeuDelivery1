<?php
// diagnose_tables.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Table Diagnostic</h1>";
echo "<pre>";

// Connect to database
$conn = new mysqli("localhost", "root", "", "beu_delivery_v2");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database: beu_delivery_v2\n\n";

// 1. Show all tables
echo "=== ALL TABLES IN DATABASE ===\n";
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

// 2. Look for store/merchant related tables
echo "\n=== LOOKING FOR STORE/MERCHANT TABLES ===\n";
$tables_result = $conn->query("SHOW TABLES");
$possible_tables = [];

while ($row = $tables_result->fetch_array()) {
    $table = $row[0];
    
    if (stripos($table, 'store') !== false || 
        stripos($table, 'merchant') !== false || 
        stripos($table, 'shop') !== false ||
        stripos($table, 'vendor') !== false ||
        stripos($table, 'user') !== false) {
        
        $possible_tables[] = $table;
        
        echo "\nTable: $table\n";
        echo "Columns:\n";
        
        $columns = $conn->query("DESCRIBE $table");
        while ($col = $columns->fetch_assoc()) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            if ($col['Key'] == 'PRI') {
                echo "    ^ PRIMARY KEY ^\n";
            }
        }
        
        // Show sample data
        $sample = $conn->query("SELECT * FROM $table LIMIT 3");
        echo "Sample data (first 3 rows):\n";
        while ($data = $sample->fetch_assoc()) {
            print_r($data);
        }
    }
}

// 3. Check if there's data for ID 7
echo "\n=== CHECKING FOR ID 7 IN VARIOUS TABLES ===\n";
foreach ($possible_tables as $table) {
    // First find the primary key column
    $primary_key = '';
    $columns = $conn->query("SHOW COLUMNS FROM $table WHERE `Key` = 'PRI'");
    if ($col = $columns->fetch_assoc()) {
        $primary_key = $col['Field'];
    } else {
        // Try common ID columns
        $common_ids = ['id', 'ID', $table . '_id', 'user_id', 'merchant_id', 'store_id'];
        foreach ($common_ids as $col_name) {
            $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$col_name'");
            if ($check->num_rows > 0) {
                $primary_key = $col_name;
                break;
            }
        }
    }
    
    if ($primary_key) {
        $check_query = "SELECT * FROM $table WHERE $primary_key = 7";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            echo "\nFOUND ID 7 in table: $table (using column: $primary_key)\n";
            $data = $check_result->fetch_assoc();
            print_r($data);
            
            // Check for photos
            echo "\nLooking for photos related to this record...\n";
            $photo_tables = $conn->query("SHOW TABLES LIKE '%photo%'");
            while ($photo_table = $photo_tables->fetch_array()) {
                $photo_table = $photo_table[0];
                // Check if this photo table has a reference to our main table
                $ref_columns = $conn->query("SHOW COLUMNS FROM $photo_table");
                while ($ref_col = $ref_columns->fetch_assoc()) {
                    if (stripos($ref_col['Field'], $table) !== false || 
                        stripos($ref_col['Field'], 'merchant') !== false ||
                        stripos($ref_col['Field'], 'store') !== false ||
                        stripos($ref_col['Field'], 'user') !== false) {
                        
                        $photo_query = "SELECT * FROM $photo_table WHERE {$ref_col['Field']} = 7";
                        $photos = $conn->query($photo_query);
                        if ($photos->num_rows > 0) {
                            echo "Photos found in table: $photo_table\n";
                            while ($photo = $photos->fetch_assoc()) {
                                print_r($photo);
                            }
                        }
                    }
                }
            }
        }
    }
}

echo "\n=== CHECK COMPLETE ===\n";
echo "</pre>";

$conn->close();
?>