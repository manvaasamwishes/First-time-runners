<?php
require_once 'config.php';

echo "<h2>Database Connection Diagnostic</h2>";
echo "Testing connection to: <b>" . DB_HOST . "</b><br>";
echo "Using database: <b>" . DB_NAME . "</b><br>";
echo "Using user: <b>" . DB_USER . "</b><br>";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<p style='color:green'>✅ SUCCESS: Connected to MySQL Server!</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($stmt->fetchColumn()) {
        echo "<p style='color:green'>✅ SUCCESS: Database <b>" . DB_NAME . "</b> exists!</p>";
        
        // Check if tables exist
        $pdo->exec("USE " . DB_NAME);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            echo "<p style='color:green'>✅ SUCCESS: Found " . count($tables) . " tables!</p>";
            echo "<ul>";
            foreach($tables as $t) echo "<li>$t</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:red'>❌ ERROR: No tables found in " . DB_NAME . ". Did you run setup.sql?</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ ERROR: Database <b>" . DB_NAME . "</b> does not exist. Please create it in phpMyAdmin.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ CONNECTION FAILED: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<b>Advice:</b> The password for user <b>" . DB_USER . "</b> is incorrect. Try changing DB_PASS in config.php.";
    } elseif (strpos($e->getMessage(), 'getaddrinfo failed') !== false || strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<b>Advice:</b> MySQL server is not running or on a different port. Make sure MySQL is green in XAMPP.";
    }
}
?>
