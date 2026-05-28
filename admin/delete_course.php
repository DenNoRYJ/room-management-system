<?php
/**
 * Delete Course Script
 * Removes a specific course from the database based on the ID passed in the URL.
 * Redirects back to the course management page upon completion.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Note: Associated rooms/students should rely on ON DELETE CASCADE in the DB schema
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: manage_courses.php");
exit();
?>