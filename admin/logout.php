<?php
/**
 * Admin Logout Script
 * Destroys the current session data and securely redirects the administrator 
 * back to the login screen.
 */
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>