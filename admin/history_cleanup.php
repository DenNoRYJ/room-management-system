<?php
/**
 * Deep History Cleanup Script
 * admin/history_cleanup.php
 * Targets and destroys ghost records that were converted to NULL (Walk-in) 
 * after a student was manually deleted from the database.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

// Deep Clean Query: 
// Deletes any "Online" booking where the student_id is either NULL or no longer exists.
// Safely ignores true "Walk-in" bookings made by the Admin.
$cleanupQuery = "
    DELETE FROM bookings 
    WHERE booking_type = 'Online' 
    AND (student_id IS NULL OR student_id NOT IN (SELECT id FROM students))
";

if ($conn->query($cleanupQuery)) {
    $deletedCount = $conn->affected_rows;
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
            <h2 style='color: #1A6B38;'>Deep Cleanup Successful!</h2>
            <p>Deleted <strong>{$deletedCount}</strong> stubborn ghost record(s) from the system.</p>
            <a href='booking_history.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Back to History Log</a>
         </div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
            <h2 style='color: #8B0000;'>Cleanup Failed</h2>
            <p>Error: " . $conn->error . "</p>
            <a href='booking_history.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Back to History Log</a>
         </div>";
}
?>