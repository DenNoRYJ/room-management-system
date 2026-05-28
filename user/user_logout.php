<?php
/**
 * User Logout Script
 * user/user_logout.php
 * Destroys the student's session data and securely redirects 
 * them back to the login screen.
 */
session_start();
session_unset();
session_destroy();
header("Location: ../login.php");
exit();
?>