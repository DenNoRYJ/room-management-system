<?php
/**
 * Create Admin Account
 * admin/create_admin.php
 * Allows a SUPER ADMIN to create a new administrator profile.
 * Access is strictly blocked for standard Department Admins.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$admin_id = $_SESSION['admin_id'];

// 1. SCOPE CHECK: Ensure only Super Admins can access this page
$scopeStmt = $conn->prepare("SELECT course_id FROM admins WHERE id = ?");
$scopeStmt->bind_param("i", $admin_id);
$scopeStmt->execute();
$adminScope = $scopeStmt->get_result()->fetch_assoc();

if (!is_null($adminScope['course_id']) && $adminScope['course_id'] > 0) {
    die("<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
            <h2 style='color: #8B0000;'>Access Denied</h2>
            <p>Only the Administrators have the authority to create new admin accounts.</p>
            <a href='dashboard.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a>
         </div>");
}

$success = "";
$error = "";

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Assign NULL if no specific department is selected (Super Admin)
    $course_id = empty($_POST['course_id']) ? NULL : intval($_POST['course_id']);
    
    if (empty($fullname) || empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        // Ensure the chosen username is unique
        $check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "This username is already taken. Please choose another.";
        } else {
            // Hash the password for security before saving
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO admins (fullname, username, password, course_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $username, $hashed_password, $course_id);
            
            if ($stmt->execute()) {
                $success = "New administrator account created successfully.";
            } else {
                $error = "Something went wrong. Failed to create admin.";
            }
        }
    }
}

// Fetch departments for the dropdown selection
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$pageTitle = "Add New Admin";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card admin-card-sm">
        <h2 class="section-title">Create Administrator Account</h2>
        <p class="admin-grid-full admin-mb-20">Register a new admin to manage specific departments or handle system operations.</p>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" required placeholder="e.g. John Doe">
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Unique username">
            </div>

            <div class="form-group">
                <label>Department (Optional)</label>
                <select name="course_id" class="form-control">
                    <option value="">None (Super Admin - All Departments)</option>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?= $course['id']; ?>">
                            <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Temporary Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Assign a secure password">
            </div>

            <div class="admin-btn-group">
                <button type="submit" class="btn">Create Account</button>
                <a href="list_admins.php" class="btn btn-secondary">Back to Admin List</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>