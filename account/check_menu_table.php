<?php
include "../includes/db.php";

echo "<h1>Menu Items Table Structure</h1>";

// Check table structure
$result = mysqli_query($conn, "DESCRIBE menu_items");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check sample data
echo "<h2>Sample Menu Items</h2>";
$sample = mysqli_query($conn, "SELECT * FROM menu_items LIMIT 3");
if ($sample && mysqli_num_rows($sample) > 0) {
    echo "<table border='1'>";
    $first = true;
    while ($row = mysqli_fetch_assoc($sample)) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No menu items found or error: " . mysqli_error($conn);
}
?>