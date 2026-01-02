<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Page</h1>";
echo "<pre>";

$CONFIG = require __DIR__ . '/config.php';
$dbPath = $CONFIG['db']['sqlite_path'];
echo "DB Path: $dbPath\n";

$db = new SQLite3($dbPath);
$db->enableExceptions(true);

// Check if plans table exists
echo "\n--- Checking tables ---\n";
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
echo "Tables in DB:\n";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "  - " . $row['name'] . "\n";
}

// Try to create plans table manually
echo "\n--- Testing plans table creation ---\n";
try {
    $db->exec('
        CREATE TABLE IF NOT EXISTS plans(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            duration_days INTEGER NOT NULL,
            price_minor INTEGER NOT NULL,
            currency TEXT NOT NULL DEFAULT "GBP",
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime("now"))
        );
    ');
    echo "Plans table created/exists OK\n";
} catch (Exception $e) {
    echo "Plans table error: " . $e->getMessage() . "\n";
}

// Try prepare statement
echo "\n--- Testing prepare statement ---\n";
try {
    $stmt = $db->prepare("INSERT INTO plans(name,duration_days,price_minor,currency,active) VALUES(:n,:d,:p,:c,1)");
    if ($stmt === false) {
        echo "Prepare returned false. Error: " . $db->lastErrorMsg() . "\n";
    } else {
        echo "Prepare OK\n";
        $stmt->bindValue(':n', 'Test Plan', SQLITE3_TEXT);
        $stmt->bindValue(':d', 30, SQLITE3_INTEGER);
        $stmt->bindValue(':p', 1999, SQLITE3_INTEGER);
        $stmt->bindValue(':c', 'GBP', SQLITE3_TEXT);
        $stmt->execute();
        echo "Insert OK\n";
    }
} catch (Exception $e) {
    echo "Prepare/Insert error: " . $e->getMessage() . "\n";
}

// Count plans
echo "\n--- Counting plans ---\n";
try {
    $count = $db->querySingle("SELECT COUNT(*) FROM plans");
    echo "Plans count: $count\n";
} catch (Exception $e) {
    echo "Count error: " . $e->getMessage() . "\n";
}

echo "</pre>";
