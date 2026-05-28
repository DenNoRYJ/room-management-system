<?php
/**
 * ============================================================
 * Authentication Utilities
 * includes/auth.php
 * Handles session checks for role-based access control.
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures only logged-in students can access the page.
 * Redirects to the public login page if unauthorized.
 */
function requireStudent() {
    if (!isset($_SESSION['student_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Ensures only logged-in administrators can access the page.
 * Redirects to the admin login page if unauthorized.
 */
function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>