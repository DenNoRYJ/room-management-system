<?php
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

// Run the query to delete orphaned bookings
$cleanupQuery = "DELETE FROM bookings WHERE student_id NOT IN (SELECT id FROM students) AND student_id IS NOT NULL";

if ($conn->query($cleanupQuery)) {
    $deletedCount = $conn->affected_rows;
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
            <h2 style='color: #1A6B38;'>Cleanup Successful!</h2>
            <p>Deleted <strong>{$deletedCount}</strong> orphaned booking request(s) from the system.</p>
            <a href='dashboard.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a>
         </div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
            <h2 style='color: #8B0000;'>Cleanup Failed</h2>
            <p>Error: " . $conn->error . "</p>
         </div>";
}
?>