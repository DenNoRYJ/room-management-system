<?php
/**
 * ============================================================
 * Student Registration Form
 * register.php
 * Allows new students to create an account. Integrates a QR scanner
 * for quick ID input. Accounts are set to 'Pending' by database default.
 * ============================================================
 */
include 'includes/db.php';
session_start();

$pageTitle = "Student Registration";
$message = "";
$error = "";

// Process new account registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST['student_id']);
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $course_id  = intval($_POST['course_id']);
    $qr_data    = trim($_POST['qr_data']);

    // Basic validation
    if (empty($student_id) || empty($fullname) || empty($email) || empty($password) || empty($course_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Ensure student ID and email are completely unique
        $check = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
        $check->bind_param("ss", $student_id, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Student ID or Email is already registered.";
        } else {
            // Hash password before insertion for security
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // account_status defaults to 'Pending' inside the MySQL schema
            $stmt = $conn->prepare("
                INSERT INTO students (student_id, fullname, email, password, course_id, qr_data)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssis", $student_id, $fullname, $email, $hashed, $course_id, $qr_data);

            if ($stmt->execute()) {
                $message = "Registration successful! Please wait for an Administrator to approve your account before logging in.";
            } else {
                $error = "Something went wrong while registering.";
            }
        }
    }
}

// Fetch departments for the dropdown selection
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$extraCSS = ['main/auth.css'];
include 'includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="card auth-card" style="max-width: 600px;"> 
        <h2 class="section-title">Student Registration</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <div class="qr-box">
            <h3>Scan TUPV ID QR Code</h3>
            <p>Use your camera if supported, or manually enter your Student ID below.</p>
            <button type="button" class="btn" id="startScannerBtn" style="margin: 10px auto; display: block;">Use Camera Scanner</button>
            <div id="reader"></div>
            <p class="small-text">If camera scanning is not supported, you can type your Student ID manually.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="qr_data" id="qr_data">

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php while ($row = $courses->fetch_assoc()): ?>
                            <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['course_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <div class="auth-button-group">
                <button type="submit" class="btn">Register</button>
                <a href="login.php" class="btn btn-secondary">Go to Login</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/qr.js" defer></script>

<?php include 'includes/footer.php'; ?>