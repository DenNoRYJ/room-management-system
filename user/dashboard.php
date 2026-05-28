<?php
/**
 * Student Dashboard
 * user/dashboard.php
 * The main landing page for students. Displays available courses/departments
 * so students can browse room maps and initiate bookings.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireStudent();

// Fetch all available courses to display as cards
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$pageTitle = "Student Dashboard";
$extraCSS = ['user/user.css'];
include '../includes/header.php';
?>

<div class="container">
    
    <div class="card">
        <h2 class="section-title">Welcome, <?= htmlspecialchars($_SESSION['student_name']); ?></h2>
        <p>Select a course to view the room map and available rooms, or check your submitted bookings.</p>

        <div class="user-mt-15">
            <a href="my_bookings.php" class="btn">My Bookings</a>
        </div>
    </div>

    <div class="grid grid-2">
        <?php while ($course = $courses->fetch_assoc()): ?>
            <div class="course-card">
                <img src="../<?= htmlspecialchars($course['map_image']); ?>" class="card-image" alt="Map">
                <div class="card-body">
                    <h3><?= htmlspecialchars($course['course_name']); ?></h3>
                    <p>View the room map and rooms under this course.</p>
                    <br>
                    <a href="view_rooms.php?course_id=<?= $course['id']; ?>" class="btn">View Rooms</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
</div>

<?php include '../includes/footer.php'; ?>