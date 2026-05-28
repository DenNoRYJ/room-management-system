<?php
/**
 * ============================================================
 * Student Login Portal
 * login.php
 * Handles student authentication, validates passwords, and 
 * strictly enforces the administrator account approval check.
 * ============================================================
 */
include 'includes/db.php';
session_start();

$pageTitle = "Student Login";
$error = "";

// Handle authentication request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);

    // Lookup student in database
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();

        // Verify the secure hash
        if (password_verify($password, $student['password'])) {
            
            // Account Status Gateway Check
            if ($student['account_status'] === 'Approved') {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['fullname'];
                header("Location: user/dashboard.php");
                exit();
            } elseif ($student['account_status'] === 'Pending') {
                $error = "Your account is currently pending admin approval.";
            } else {
                $error = "Your account registration was rejected by the administration.";
            }
            
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Student ID not found.";
    }
}

$extraCSS = ['main/auth.css'];
include 'includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="card auth-card">
        <h2 class="section-title">Student Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="auth-button-group">
                <button type="submit" class="btn">Login</button>
                <a href="register.php" class="btn btn-secondary">Register</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>