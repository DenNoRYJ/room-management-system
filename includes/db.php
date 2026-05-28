<?php
/**
 * ============================================================
 * Database Connection Configuration
 * includes/db.php
 * Establishes the MySQLi connection for the entire system.
 * ============================================================
 */
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "room_management";

$conn = new mysqli($host, $user, $pass, $dbname);

// Terminate script execution if the connection fails
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>