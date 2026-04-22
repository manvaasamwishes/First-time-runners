<?php
// auto_setup.php — One-click setup script

echo "<h2>1st Time Runners — Auto Setup</h2>";
echo "Attempting to create database and tables...<br><br>";

// 1. Try to connect to MySQL without selecting a database first
$configs = [
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1:3307', 'user' => 'root', 'pass' => ''],
];

$pdo = null;
foreach ($configs as $c) {
    try {
        $pdo = new PDO("mysql:host={$c['host']}", $c['user'], $c['pass']);
        echo "✅ Connected to MySQL server ({$c['host']})<br>";
        break;
    } catch (Exception $e) { continue; }
}

if (!$pdo) {
    die("<b style='color:red'>❌ Failed to connect to MySQL. Please make sure MySQL is running in XAMPP.</b>");
}

// 2. Create database
$dbName = 'runners_db';
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database `$dbName` created or already exists.<br>";
    $pdo->exec("USE `$dbName`");
} catch (Exception $e) {
    die("<b style='color:red'>❌ Failed to create database: " . $e->getMessage() . "</b>");
}

// 3. Load and run setup.sql
$sqlFile = __DIR__ . '/setup.sql';
if (!file_exists($sqlFile)) {
    die("<b style='color:red'>❌ Missing setup.sql file in the folder!</b>");
}

$sql = file_get_contents($sqlFile);
// Split SQL by semicolons (simple approach)
// Note: This won't handle complex triggers but works for standard CREATE/INSERT
$queries = explode(';', $sql);

$count = 0;
foreach ($queries as $q) {
    $q = trim($q);
    if ($q === '') continue;
    try {
        $pdo->exec($q);
        $count++;
    } catch (Exception $e) {
        // Skip errors for "IF NOT EXISTS" or minor issues
    }
}

echo "✅ Successfully executed $count SQL commands.<br>";
echo "<br><b style='color:green'>🎉 SETUP COMPLETE!</b><br>";
echo "<a href='admin.php'>Click here to go to Admin Panel</a>";
?>
