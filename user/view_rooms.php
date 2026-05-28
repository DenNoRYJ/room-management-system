<?php
/**
 * View Rooms Page (Student Portal)
 * Displays a map and a grid of all rooms under a selected course/department.
 * Shows current room status and allows students to initiate a booking.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireStudent();

// Validate the course ID from the URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    die("Invalid course selected.");
}

// Fetch the details of the selected course
$courseStmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$courseStmt->bind_param("i", $course_id);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

if ($courseResult->num_rows === 0) {
    die("Course not found.");
}

$course = $courseResult->fetch_assoc();

// Fetch all rooms associated with this course
$roomStmt = $conn->prepare("
    SELECT * FROM rooms 
    WHERE course_id = ? 
    ORDER BY room_name ASC
");
$roomStmt->bind_param("i", $course_id);
$roomStmt->execute();
$rooms = $roomStmt->get_result();

$pageTitle = $course['course_name'] . " Rooms";
$extraCSS = ['user/user.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title"><?= htmlspecialchars($course['course_name']); ?> Room Map</h2>
        <img src="../<?= htmlspecialchars($course['map_image']); ?>" alt="Course Map" class="map-banner">
        <p class="user-mt-10">
            Below are the rooms under <strong><?= htmlspecialchars($course['course_name']); ?></strong>.
            You can view each room and submit a booking request if the room is available.
        </p>
    </div>

    <div class="card">
        <h2 class="section-title">Rooms Under <?= htmlspecialchars($course['course_name']); ?></h2>

        <?php if ($rooms->num_rows > 0): ?>
            <div class="grid grid-3">
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="room-card">
                        <img src="../<?= htmlspecialchars($room['room_image']); ?>" class="card-image" alt="Room Image">

                        <div class="card-body">
                            <h3><?= htmlspecialchars($room['room_name']); ?></h3>
                            <p><strong>Type:</strong> <?= htmlspecialchars($room['room_type']); ?></p>
                            <p><strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']); ?></p>
                            <p class="user-mt-8"><?= htmlspecialchars($room['description']); ?></p>

                            <br>

                            <?php
                            // Determine badge color based on room status
                            $status = $room['status'];
                            $badgeClass = 'badge-available';

                            if ($status === 'Unavailable') {
                                $badgeClass = 'badge-unavailable';
                            } elseif ($status === 'Under Maintenance') {
                                $badgeClass = 'badge-maintenance';
                            }
                            ?>

                            <span class="badge <?= $badgeClass; ?>">
                                <?= htmlspecialchars($status); ?>
                            </span>

                            <div class="user-mt-15">
                                <?php if ($room['status'] === 'Available'): ?>
                                    <a href="book_room.php?room_id=<?= $room['id']; ?>" class="btn">Book Now</a>
                                <?php else: ?>
                                    <button class="btn" disabled>Not Available</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                No rooms found under this course.
            </div>
        <?php endif; ?>

        <div class="user-mt-25">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>