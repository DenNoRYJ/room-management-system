<?php
/**
 * Admin Dashboard
 * admin/dashboard.php
 * Displays system statistics, quick management links, and a preview of the latest pending bookings.
 * Automatically filters statistics based on the Admin's scope (Super Admin vs Dept Admin).
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$pageTitle = "Admin Dashboard";

$admin_id = $_SESSION['admin_id'];

// 1. Determine Admin Scope
$scopeStmt = $conn->prepare("SELECT course_id FROM admins WHERE id = ?");
$scopeStmt->bind_param("i", $admin_id);
$scopeStmt->execute();
$adminScope = $scopeStmt->get_result()->fetch_assoc();
$admin_course_id = $adminScope['course_id'];

$is_dept_admin = (!is_null($admin_course_id) && $admin_course_id > 0);

// Initialize stat counters
$adminsCount = 0;
$studentsCount = 0;
$roomsCount = 0;
$coursesCount = 0;
$pendingCount = 0;
$pendingStudentsCount = 0;

// Fetch aggregate counts filtered by scope
$adminWhere = $is_dept_admin ? "WHERE course_id = " . intval($admin_course_id) : "";
$adminsQuery = $conn->query("SELECT COUNT(*) AS total FROM admins $adminWhere");
if ($adminsQuery) $adminsCount = $adminsQuery->fetch_assoc()['total'];

$studentWhere = $is_dept_admin ? "AND course_id = " . intval($admin_course_id) : "";

// Count Approved Students
$studentsQuery = $conn->query("SELECT COUNT(*) AS total FROM students WHERE account_status = 'Approved' $studentWhere");
if ($studentsQuery) $studentsCount = $studentsQuery->fetch_assoc()['total'];

// Count Pending Students for the button
$pendingStudentsQuery = $conn->query("SELECT COUNT(*) AS total FROM students WHERE account_status = 'Pending' $studentWhere");
if ($pendingStudentsQuery) $pendingStudentsCount = $pendingStudentsQuery->fetch_assoc()['total'];

$roomWhere = $is_dept_admin ? "WHERE course_id = " . intval($admin_course_id) : "";
$roomsQuery = $conn->query("SELECT COUNT(*) AS total FROM rooms $roomWhere");
if ($roomsQuery) $roomsCount = $roomsQuery->fetch_assoc()['total'];

$courseWhere = $is_dept_admin ? "WHERE id = " . intval($admin_course_id) : "";
$coursesQuery = $conn->query("SELECT COUNT(*) AS total FROM courses $courseWhere");
if ($coursesQuery) $coursesCount = $coursesQuery->fetch_assoc()['total'];

$pendingWhere = $is_dept_admin ? "AND rooms.course_id = " . intval($admin_course_id) : "";
$pendingQuery = $conn->query("
    SELECT COUNT(bookings.id) AS total 
    FROM bookings 
    INNER JOIN rooms ON bookings.room_id = rooms.id 
    WHERE bookings.status = 'Pending' $pendingWhere
");
if ($pendingQuery) $pendingCount = $pendingQuery->fetch_assoc()['total'];

// Fetch the 5 most recent pending bookings for the summary table (Filtered by scope)
$previewQueryStr = "
    SELECT 
        bookings.*, 
        students.fullname, 
        students.student_id AS stud_no, 
        rooms.room_name
    FROM bookings
    LEFT JOIN students ON bookings.student_id = students.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    WHERE bookings.status = 'Pending'
    $pendingWhere
    ORDER BY bookings.created_at DESC
    LIMIT 5
";
$pendingBookings = $conn->query($previewQueryStr);

include '../includes/header.php';
?>

<div class="container">
    
    <div class="card">
        <h2 class="section-title">Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'System Administrator'); ?></h2>
        <p style="color: var(--gray-600); margin-bottom: 25px;">
            <?php if ($is_dept_admin): ?>
                Manage room reservations, monitor booking requests, and maintain records specifically for your department.
            <?php else: ?>
                Manage room reservations, monitor booking requests, and maintain system-wide course and room records.
            <?php endif; ?>
        </p>

        <!-- Management Links Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <div style="background: var(--gray-50); padding: 18px; border-radius: var(--radius-lg); border: 1px solid var(--gray-100);">
                <h4 style="font-family: var(--font-display); color: var(--crimson); font-size: 16px; margin-bottom: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Reservations &amp; Bookings
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="pending_bookings.php" class="btn" style="width: 100%; justify-content: flex-start; padding: 10px 16px; min-height: 44px;">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pending Bookings (<?= $pendingCount; ?>)
                    </a>
                    <a href="walkin_booking.php" class="btn btn-secondary" style="width: 100%; justify-content: flex-start; padding: 10px 16px; min-height: 44px;">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0V9a2 2 0 012-2h2a2 2 0 012 2v12m-6 0h6"/>
                        </svg>
                        New Walk-in Booking
                    </a>
                </div>
            </div>

            <div style="background: var(--gray-50); padding: 18px; border-radius: var(--radius-lg); border: 1px solid var(--gray-100);">
                <h4 style="font-family: var(--font-display); color: var(--crimson); font-size: 16px; margin-bottom: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    Facility Management
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="manage_courses.php" class="btn" style="width: 100%; justify-content: flex-start; padding: 10px 16px; min-height: 44px;">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Manage Departments
                    </a>
                    <a href="manage_rooms.php" class="btn btn-secondary" style="width: 100%; justify-content: flex-start; padding: 10px 16px; min-height: 44px;">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                        Manage Classrooms
                    </a>
                </div>
            </div>

            <div style="background: var(--gray-50); padding: 18px; border-radius: var(--radius-lg); border: 1px solid var(--gray-100);">
                <h4 style="font-family: var(--font-display); color: var(--crimson); font-size: 16px; margin-bottom: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    User Directories
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="pending_students.php" class="btn" style="width: 100%; justify-content: flex-start; padding: 10px 16px; min-height: 44px;">
                        <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM2 20a7 7 0 0112 0v1H2v-1z"/>
                        </svg>
                        Student Approvals (<?= $pendingStudentsCount; ?>)
                    </a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <a href="list_students.php" class="btn btn-secondary" style="min-width: 0; justify-content: flex-start; padding: 10px 12px; font-size: 13px; min-height: 44px;">
                            <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Students
                        </a>
                        <a href="list_admins.php" class="btn btn-secondary" style="min-width: 0; justify-content: flex-start; padding: 10px 12px; font-size: 13px; min-height: 44px;">
                            <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Admins
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- System Statistics Counters -->
    <div class="grid grid-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0; padding: 20px;">
            <p style="font-size: 13px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em;">Total Students</p>
            <h3 style="font-family: var(--font-display); font-size: 36px; color: var(--crimson); margin-top: 5px; font-weight: 700; line-height: 1;"><?= $studentsCount; ?></h3>
        </div>
        <div class="card" style="margin-bottom: 0; padding: 20px;">
            <p style="font-size: 13px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em;">Total Admins</p>
            <h3 style="font-family: var(--font-display); font-size: 36px; color: var(--crimson); margin-top: 5px; font-weight: 700; line-height: 1;"><?= $adminsCount; ?></h3>
        </div>
        <div class="card" style="margin-bottom: 0; padding: 20px;">
            <p style="font-size: 13px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em;">Total Courses</p>
            <h3 style="font-family: var(--font-display); font-size: 36px; color: var(--crimson); margin-top: 5px; font-weight: 700; line-height: 1;"><?= $coursesCount; ?></h3>
        </div>
        <div class="card" style="margin-bottom: 0; padding: 20px;">
            <p style="font-size: 13px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em;">Total Rooms</p>
            <h3 style="font-family: var(--font-display); font-size: 36px; color: var(--crimson); margin-top: 5px; font-weight: 700; line-height: 1;"><?= $roomsCount; ?></h3>
        </div>
        <div class="card" style="margin-bottom: 0; padding: 20px;">
            <p style="font-size: 13px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.05em;">Pending Bookings</p>
            <h3 style="font-family: var(--font-display); font-size: 36px; color: var(--crimson); margin-top: 5px; font-weight: 700; line-height: 1;"><?= $pendingCount; ?></h3>
        </div>
    </div>

    <!-- Latest Pending Bookings Table Preview -->
    <div class="card">
        <h2 class="section-title">Latest Pending Bookings</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Room</th>
                        <th>Date</th>
                        <th>Schedule Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingBookings && $pendingBookings->num_rows > 0): ?>
                        <?php while ($row = $pendingBookings->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fullname'] ?? 'Walk-in'); ?></td>
                                <td><?= htmlspecialchars($row['stud_no'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['room_name']); ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['booking_date']))); ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($row['start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($row['end_time']))); ?></td>
                                <td><span class="badge badge-maintenance"><?= htmlspecialchars($row['status']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--gray-400); padding: 20px;">No active pending bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <a href="pending_bookings.php" class="btn" style="font-size: 13.5px;">View All Pending Bookings</a>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>