<?php
/**
 * Admin Login Page
 * Authenticates administrators and sets session variables upon success.
 */
include '../includes/db.php';
session_start();

$pageTitle = "Admin Login";
$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the username exists in the database
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify the hashed password
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['fullname'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin account not found.";
    }
}

$extraCSS  = ['main/auth.css'];
include '../includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="card auth-card">
        <h2 class="section-title">Admin Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="auth-button-group">
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>