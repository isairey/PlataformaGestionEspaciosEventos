<?php
// Simple database connection test without external dependencies
// Parse .env file manually
$env_file = __DIR__ . '/../.env';
$env_vars = [];

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env_vars[trim($key)] = trim($value);
        }
    }
}

// Get credentials from .env
$hostname = $env_vars['DATABASE_HOSTNAME'] ?? 'localhost';
$username = $env_vars['DATABASE_USERNAME'] ?? 'root';
$password = $env_vars['DATABASE_PASSWORD'] ?? '';
$database = $env_vars['DATABASE_DB'] ?? 'ci4';
$port = (int) ($env_vars['DATABASE_PORT'] ?? 3306);

echo "<h1>Database Connection Test</h1>";
echo "<p><strong>Hostname:</strong> $hostname</p>";
echo "<p><strong>Database:</strong> $database</p>";
echo "<p><strong>Username:</strong> $username</p>";
echo "<p><strong>Password:</strong> " . ($password ? '***' : '(empty)') . "</p>";
echo "<p><strong>Port:</strong> $port</p>";

// Try to connect
$mysqli = new mysqli($hostname, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    echo "<p style='color: red;'><strong>❌ Connection FAILED:</strong> " . $mysqli->connect_error . "</p>";
} else {
    echo "<p style='color: green;'><strong>✅ Connection SUCCESSFUL!</strong></p>";
    
    // Try a query
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        echo "<p style='color: green;'>✅ Query executed successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Query failed: " . $mysqli->error . "</p>";
    }
    
    $mysqli->close();
}
?>

