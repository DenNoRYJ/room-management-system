<?php
/**
 * Edit Room Page
 * Allows an admin to update classroom details, capacity, course assignment, status, and image.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";

// Fetch existing room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Room not found.");
}
$room = $result->fetch_assoc();

// Process the form update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_room'])) {
    $room_name = trim($_POST['room_name']);
    $room_type = trim($_POST['room_type']);
    $capacity = intval($_POST['capacity']);
    $description = trim($_POST['description']);
    $course_id = intval($_POST['course_id']);
    $status = trim($_POST['status']);
    $room_image = $room['room_image'];

    // Handle new room image upload if provided
    if (!empty($_FILES['room_image']['name'])) {
        $targetDir = "../uploads/rooms/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['room_image']['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $targetFile)) {
            $room_image = "uploads/rooms/" . $fileName;
        }
    }

    if (empty($room_name) || empty($room_type) || empty($capacity) || empty($course_id) || empty($status)) {
        $error = "Please fill in all required fields.";
    } else {
        $updateStmt = $conn->prepare("
            UPDATE rooms 
            SET room_name = ?, room_type = ?, capacity = ?, description = ?, room_image = ?, course_id = ?, status = ?
            WHERE id = ?
        ");
        $updateStmt->bind_param("ssissisi", $room_name, $room_type, $capacity, $description, $room_image, $course_id, $status, $id);

        if ($updateStmt->execute()) {
            $success = "Room updated successfully.";
            // Update local variables for immediate display
            $room['room_name'] = $room_name;
            $room['room_type'] = $room_type;
            $room['capacity'] = $capacity;
            $room['description'] = $description;
            $room['course_id'] = $course_id;
            $room['status'] = $status;
            $room['room_image'] = $room_image;
        } else {
            $error = "Failed to update room.";
        }
    }
}

// Fetch departments for the assignment dropdown
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$pageTitle = "Edit Room";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card admin-card-md">
        <h2 class="section-title">Edit Room</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Room Name</label>
                    <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars($room['room_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Room Type</label>
                    <input type="text" name="room_type" class="form-control" value="<?= htmlspecialchars($room['room_type']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($room['capacity']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['id']; ?>" <?= ($course['id'] == $room['course_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Available" <?= ($room['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Maintenance" <?= ($room['status'] === 'Under Maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="Unavailable" <?= ($room['status'] === 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Update Room Image (Optional)</label>
                    <input type="file" name="room_image" class="form-control" accept="image/*">
                </div>

                <div class="form-group admin-grid-full">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($room['description']); ?></textarea>
                </div>
            </div>

            <div class="admin-btn-group">
                <button type="submit" name="update_room" class="btn">Update Room</button>
                <a href="manage_rooms.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>