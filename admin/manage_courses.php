<?php
/**
 * Manage Courses Page (Scope-Aware)
 * admin/manage_courses.php
 * Super Admins can add/edit/delete all courses.
 * Dept Admins can only view and edit their own assigned department.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$admin_id = $_SESSION['admin_id'];

// Determine Admin Scope
$scopeStmt = $conn->prepare("SELECT course_id FROM admins WHERE id = ?");
$scopeStmt->bind_param("i", $admin_id);
$scopeStmt->execute();
$adminScope = $scopeStmt->get_result()->fetch_assoc();
$admin_course_id = $adminScope['course_id'];

$is_dept_admin = (!is_null($admin_course_id) && $admin_course_id > 0);

$success = "";
$error = "";

// Handle the addition of a new course (ONLY ALLOWED FOR SUPER ADMINS)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_course'])) {
    if ($is_dept_admin) {
        $error = "Unauthorized: Only Super Administrators can add new departments.";
    } else {
        $course_name = trim($_POST['course_name']);
        $course_code = trim($_POST['course_code']);
        $map_image = "assets/images/default-map.jpg";

        if (!empty($_FILES['map_image']['name'])) {
            $targetDir = "../uploads/maps/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES['map_image']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['map_image']['tmp_name'], $targetFile)) {
                $map_image = "uploads/maps/" . $fileName;
            }
        }

        if (empty($course_name) || empty($course_code)) {
            $error = "Please fill in all required fields.";
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, map_image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $course_name, $course_code, $map_image);
            if ($stmt->execute()) {
                $success = "Course added successfully.";
            } else {
                $error = "Failed to add course. Course code may already exist.";
            }
        }
    }
}

// Fetch courses based on scope
$courseWhere = $is_dept_admin ? "WHERE id = " . intval($admin_course_id) : "";
$courses = $conn->query("SELECT * FROM courses $courseWhere ORDER BY course_name ASC");

$pageTitle = "Manage Departments";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    
    <?php if (!$is_dept_admin): ?>
    <div class="card">
        <h2 class="section-title">Add New Department</h2>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Department Name</label>
                    <input type="text" name="course_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Department Code</label>
                    <input type="text" name="course_code" class="form-control" required>
                </div>
                <div class="form-group admin-grid-full">
                    <label>Department Map Image</label>
                    <input type="file" name="map_image" class="form-control" accept="image/*">
                </div>
            </div> <div class="admin-btn-group">
                <button type="submit" name="add_course" class="btn">Add Department</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <h2 class="section-title">
        <?= $is_dept_admin ? "My Department" : "All Departments"; ?>
    </h2>
    
    <div class="grid grid-2">
        <?php while ($course = $courses->fetch_assoc()): ?>
            <div class="course-card">
                <img src="../<?= htmlspecialchars($course['map_image']); ?>" class="card-image" alt="Map">
                <div class="card-body">
                    <h3><?= htmlspecialchars($course['course_name']); ?></h3>
                    <p><strong>Code:</strong> <?= htmlspecialchars($course['course_code']); ?></p>
                    
                    <div class="admin-card-actions">
                        <a href="edit_course.php?id=<?= $course['id']; ?>" class="btn btn-secondary">Edit Settings</a>
                        <?php if (!$is_dept_admin): ?>
                            <a href="delete_course.php?id=<?= $course['id']; ?>" class="btn admin-card-actions-delete" onclick="return confirm('Are you sure you want to delete this department?');" style="background: var(--crimson-light); border-color: var(--crimson-light);">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <?php if ($is_dept_admin): ?>
        <div class="admin-spacer-top">
            <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>