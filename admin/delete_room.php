<?php
/**
 * Delete Room Script
 * Removes a specific room from the database based on the ID passed in the URL.
 * Redirects back to the room management page upon completion.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: manage_rooms.php");
exit();
?>