<?php
/**
 * FoodBridge — config.php
 * Central configuration + database connection (MySQLi).
 * Include this at the top of every PHP page:  require_once __DIR__ . '/includes/config.php';
 */

// ---- Error reporting (turn off display in production) ----
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---- Session --   function that returns the current session state.--
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Database credentials (XAMPP defaults) ----
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default root password is empty
define('DB_NAME', 'foodbridge');
define('DB_PORT', 3306);

// ---- App settings ----
define('APP_NAME', 'FoodBridge');
define('APP_TAGLINE', 'Connecting Surplus Food with Those Who Need It');

/**
 * Base URL of the project. Adjust if your folder name differs.
 * Example for XAMPP:  http://localhost/foodbridge
 */
define('BASE_URL', '/foodbridge');

// ---- Connect to MySQL ----
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Database connection failed. Please make sure MySQL is running and the "foodbridge" database is imported. Error: ' . $e->getMessage());
}

// Load shared helpers
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';