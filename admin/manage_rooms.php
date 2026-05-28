<?php
/**
 * Manage Rooms Page (Scope-Aware)
 * admin/manage_rooms.php
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

// Handle adding a new room
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_room'])) {
    $room_name = trim($_POST['room_name']);
    $room_type = trim($_POST['room_type']);
    $capacity = intval($_POST['capacity']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    
    // Auto-assign course ID if they are a Dept Admin
    $course_id = $is_dept_admin ? $admin_course_id : intval($_POST['course_id']);
    
    $room_image = "assets/images/default-room.jpg"; 

    if (!empty($_FILES['room_image']['name'])) {
        $targetDir = "../uploads/rooms/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['room_image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $targetFile)) {
            $room_image = "uploads/rooms/" . $fileName;
        }
    }

    if (empty($room_name) || empty($room_type) || empty($capacity) || empty($course_id) || empty($status)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (room_name, room_type, capacity, description, room_image, course_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissis", $room_name, $room_type, $capacity, $description, $room_image, $course_id, $status);
        if ($stmt->execute()) {
            $success = "Room added successfully.";
        } else {
            $error = "Failed to add room.";
        }
    }
}

// Establish filtering criteria
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$whereClause = "";

if ($is_dept_admin) {
    $whereClause = "WHERE rooms.course_id = " . intval($admin_course_id);
} elseif ($course_filter > 0) {
    $whereClause = "WHERE rooms.course_id = $course_filter";
}

$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
$rooms = $conn->query("
    SELECT rooms.*, courses.course_name
    FROM rooms
    INNER JOIN courses ON rooms.course_id = courses.id
    $whereClause
    ORDER BY courses.course_name ASC, rooms.room_name ASC
");

$pageTitle = "Manage Rooms";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">Add New Room</h2>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Room Name</label>
                    <input type="text" name="room_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Room Type</label>
                    <input type="text" name="room_type" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" required>
                </div>

                <?php if (!$is_dept_admin): ?>
                    <div class="form-group">
                        <label>Department Assignment</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php 
                            mysqli_data_seek($courses, 0);
                            while ($course = $courses->fetch_assoc()): 
                            ?>
                                <option value="<?= $course['id']; ?>"><?= htmlspecialchars($course['course_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room Image</label>
                    <input type="file" name="room_image" class="form-control" accept="image/*">
                </div>
                <div class="form-group admin-grid-full">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
            </div> <div class="admin-btn-group">
                <button type="submit" name="add_room" class="btn">Save Room</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
            </div>
        </form>
    </div>

    <h2 class="section-title">Managed Rooms List</h2>

    <?php if (!$is_dept_admin): ?>
        <form method="GET" class="admin-filter-flex">
            <div class="form-group admin-filter-group-sm">
                <label>Filter by Department</label>
                <select name="course_id" class="form-control" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php 
                    mysqli_data_seek($courses, 0);
                    while ($course = $courses->fetch_assoc()): 
                    ?>
                        <option value="<?= $course['id']; ?>" <?= ($course['id'] == $course_filter) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <a href="manage_rooms.php" class="btn btn-secondary">Clear Filter</a>
        </form>
    <?php endif; ?>

    <?php if ($rooms && $rooms->num_rows > 0): ?>
        <div class="grid grid-3">
            <?php while ($room = $rooms->fetch_assoc()): ?>
                <div class="room-card">
                    <img src="../<?= htmlspecialchars($room['room_image']); ?>" class="card-image" alt="Room">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($room['room_name']); ?></h3>
                        <?php if (!$is_dept_admin): ?>
                            <p><strong>Department:</strong> <?= htmlspecialchars($room['course_name']); ?></p>
                        <?php endif; ?>
                        <p><strong>Type:</strong> <?= htmlspecialchars($room['room_type']); ?></p>
                        <p><strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']); ?></p>
                        <p><strong>Status:</strong> <span class="badge badge-maintenance"><?= htmlspecialchars($room['status']); ?></span></p>
                        
                        <div class="admin-card-actions">
                            <a href="edit_room.php?id=<?= $room['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="delete_room.php?id=<?= $room['id']; ?>" class="btn admin-card-actions-delete" onclick="return confirm('Are you sure you want to delete this room?');" style="background: var(--crimson-light); border-color: var(--crimson-light);">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">No active rooms matched this filter condition.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>