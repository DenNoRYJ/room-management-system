<?php
/**
 * Edit Course Page
 * Allows an admin to modify course details (name, code) and update the course map image.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";

// Fetch the existing course data
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Course not found.");
}
$course = $result->fetch_assoc();

// Handle the update form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_course'])) {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $map_image = $course['map_image'];

    // Process a new map image upload if provided
    if (!empty($_FILES['map_image']['name'])) {
        $targetDir = "../uploads/maps/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['map_image']['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['map_image']['tmp_name'], $targetFile)) {
            $map_image = "uploads/maps/" . $fileName;
        }
    }

    if (empty($course_name) || empty($course_code)) {
        $error = "Please fill in all required fields.";
    } else {
        $updateStmt = $conn->prepare("UPDATE courses SET course_name = ?, course_code = ?, map_image = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $course_name, $course_code, $map_image, $id);

        if ($updateStmt->execute()) {
            $success = "Course updated successfully.";
            // Update the local variables to reflect changes on the page instantly
            $course['course_name'] = $course_name;
            $course['course_code'] = $course_code;
            $course['map_image'] = $map_image;
        } else {
            $error = "Failed to update course.";
        }
    }
}

$pageTitle = "Edit Course";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card admin-card-md">
        <h2 class="section-title">Edit Course</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($course['course_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="course_code" class="form-control" value="<?= htmlspecialchars($course['course_code']); ?>" required>
                </div>

                <div class="form-group admin-grid-full">
                    <label>Update Course Map Image (Optional)</label>
                    <p class="admin-text-muted">Leave blank to keep the current image.</p>
                    <input type="file" name="map_image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="admin-btn-group">
                <button type="submit" name="update_course" class="btn">Update Course</button>
                <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>