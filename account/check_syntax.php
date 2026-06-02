<?php
// Simple syntax checker
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Syntax Checker</h2>";

$files_to_check = [
    'orders.php',
    'reports.php', 
    'settings.php',
    'customer_feedback.php'
];

foreach ($files_to_check as $file) {
    echo "<h3>Checking $file:</h3>";
    
    if (!file_exists($file)) {
        echo "❌ File does not exist<br><br>";
        continue;
    }
    
    // Check syntax using php -l
    $output = [];
    $return_code = 0;
    exec("php -l $file 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "✅ Syntax OK<br>";
    } else {
        echo "❌ Syntax Error:<br>";
        echo "<pre style='color: red;'>" . implode("\n", $output) . "</pre>";
    }
    
    // Also check for common issues
    $content = file_get_contents($file);
    
    // Check for unclosed PHP tags
    if (substr_count($content, '<?php') !== substr_count($content, '?>') && !str_ends_with(trim($content), '?>')) {
        // This is actually OK for PHP files that end with PHP code
    }
    
    // Check for missing semicolons (basic check)
    $lines = explode("\n", $content);
    foreach ($lines as $line_num => $line) {
        $line = trim($line);
        if (!empty($line) && 
            !str_starts_with($line, '//') && 
            !str_starts_with($line, '/*') && 
            !str_starts_with($line, '*') && 
            !str_starts_with($line, '<?') && 
            !str_starts_with($line, '?>') && 
            !str_ends_with($line, '{') && 
            !str_ends_with($line, '}') && 
            !str_ends_with($line, ';') && 
            !str_ends_with($line, ':') &&
            !str_contains($line, 'if (') &&
            !str_contains($line, 'while (') &&
            !str_contains($line, 'foreach (') &&
            !str_contains($line, 'function ') &&
            !str_contains($line, 'class ') &&
            str_contains($line, '=')) {
            echo "⚠️ Possible missing semicolon on line " . ($line_num + 1) . ": " . htmlspecialchars($line) . "<br>";
        }
    }
    
    echo "<br>";
}

echo "<h3>Test Links:</h3>";
foreach ($files_to_check as $file) {
    echo "<a href='$file' target='_blank'>Test $file</a><br>";
}
?>