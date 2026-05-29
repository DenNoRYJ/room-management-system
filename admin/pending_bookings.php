<?php
/**
 * Pending Bookings Page (Filtered & Nested Grouping View)
 * admin/pending_bookings.php
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$admin_id = $_SESSION['admin_id'];

// 1. Determine Admin Scope
$scopeStmt = $conn->prepare("SELECT course_id FROM admins WHERE id = ?");
$scopeStmt->bind_param("i", $admin_id);
$scopeStmt->execute();
$adminScope = $scopeStmt->get_result()->fetch_assoc();
$admin_course_id = $adminScope['course_id'];
$is_dept_admin = (!is_null($admin_course_id) && $admin_course_id > 0);

// 2. Handle Filters
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;

$whereClause = "WHERE bookings.status = 'Pending'";

// Apply Course Scope / Filter
if ($is_dept_admin) {
    $whereClause .= " AND courses.id = " . intval($admin_course_id);
} elseif ($course_filter > 0) {
    $whereClause .= " AND courses.id = $course_filter";
}

// Apply Year Filter
if ($year_filter > 0) {
    $whereClause .= " AND YEAR(bookings.booking_date) = $year_filter";
}

// 3. Fetch Data
$bookingsQuery = $conn->query("
    SELECT bookings.*, students.fullname, students.student_id AS stud_no, 
           rooms.room_name, courses.course_name, courses.id AS course_id
    FROM bookings
    LEFT JOIN students ON bookings.student_id = students.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    INNER JOIN courses ON rooms.course_id = courses.id
    $whereClause
    ORDER BY courses.course_name ASC, rooms.room_name ASC, bookings.booking_date ASC, bookings.start_time ASC
");

// Group data: Course -> Room -> Array of Bookings
$grouped_bookings = [];
$total_pending = 0;

if ($bookingsQuery) {
    while ($row = $bookingsQuery->fetch_assoc()) {
        $course_name = $row['course_name'];
        $room_name = $row['room_name'];
        
        if (!isset($grouped_bookings[$course_name])) $grouped_bookings[$course_name] = [];
        if (!isset($grouped_bookings[$course_name][$room_name])) $grouped_bookings[$course_name][$room_name] = [];
        
        $grouped_bookings[$course_name][$room_name][] = $row;
        $total_pending++;
    }
}

// Fetch resources for dropdowns
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
$yearsResult = $conn->query("SELECT DISTINCT YEAR(booking_date) as year FROM bookings ORDER BY year DESC");

$pageTitle = "Pending Bookings";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="admin-header-flex" style="align-items: flex-start; margin-bottom: 25px;">
            <div>
                <h2 class="section-title" style="margin-bottom: 5px;">Pending Booking Requests</h2>
                <p style="color: var(--gray-600); font-size: 14px;">
                    <?php if (is_null($admin_course_id)): ?>
                        Super Admin View: Displaying requests across all departments.
                    <?php else: ?>
                        Department Admin View: Displaying requests strictly for your department.
                    <?php endif; ?>
                </p>
            </div>
            <span class="badge badge-maintenance responsive-badge">
                <?= $total_pending; ?> Total Pending
            </span>
        </div>

        <form method="GET" class="admin-filter-flex" style="margin-bottom: 30px;">
            
            <?php if (!$is_dept_admin): ?>
                <div class="form-group admin-filter-group-sm">
                    <label>Filter by Department</label>
                    <select name="course_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">All Departments</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['id']; ?>" <?= ($course['id'] == $course_filter) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($course['course_code']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group admin-filter-group-sm">
                <label>Filter by Year</label>
                <select name="year" class="form-control" onchange="this.form.submit()">
                    <option value="0">All Years</option>
                    <?php 
                    if ($yearsResult && $yearsResult->num_rows > 0) {
                        while ($y = $yearsResult->fetch_assoc()): ?>
                            <option value="<?= $y['year']; ?>" <?= ($y['year'] == $year_filter) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($y['year']); ?>
                            </option>
                        <?php endwhile; 
                    } else {
                        $currY = date('Y');
                        echo "<option value=\"$currY\">$currY</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div style="flex-shrink: 0; padding-bottom: 2px;">
                <a href="pending_bookings.php" class="btn btn-secondary admin-clear-btn" style="height: 44px !important; margin: 0;">Clear Filter</a>
            </div>
        </form>

        <?php if (empty($grouped_bookings)): ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed var(--gray-200); border-radius: var(--radius-md);">
                <p style="color: var(--gray-400);">No pending bookings match this criteria. The queue is clear!</p>
            </div>
        <?php else: ?>

            <?php foreach ($grouped_bookings as $course_name => $rooms): 
                $course_id = md5($course_name); 
                $course_total = 0;
                foreach ($rooms as $room_requests) {
                    $course_total += count($room_requests);
                }
            ?>
                
                <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 20px; border: 1.5px solid var(--crimson-light);">
                    <div style="background: var(--crimson); color: var(--white); padding: 16px 24px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" 
                         onclick="toggleAccordion('course_<?= $course_id ?>', 'arrow_course_<?= $course_id ?>')">
                        
                        <div>
                            <h3 style="font-family: var(--font-display); font-size: 22px; margin-bottom: 2px; font-weight: 700; color: var(--white);">
                                <?= htmlspecialchars($course_name); ?>
                            </h3>
                            <span style="font-size: 13px; color: rgba(255,255,255,0.8); font-weight: 600;">
                                <?= $course_total; ?> Request(s) in this Department
                            </span>
                        </div>
                        
                        <svg id="arrow_course_<?= $course_id ?>" style="width: 24px; height: 24px; color: var(--white); transition: transform var(--transition); transform: rotate(180deg);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <div id="course_<?= $course_id ?>" style="display: block; padding: 20px; background: var(--off-white);">
                        <?php foreach ($rooms as $room_name => $requests): 
                            $room_id = md5($course_name . $room_name); 
                        ?>
                            <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-md); margin-bottom: 12px; background: var(--white); overflow: hidden;">
                                
                                <div style="background: var(--white); padding: 14px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-100);" 
                                     onclick="toggleAccordion('room_<?= $room_id ?>', 'arrow_room_<?= $room_id ?>')">
                                    <div>
                                        <h4 style="font-size: 16px; font-weight: 700; color: var(--crimson);">
                                            <?= htmlspecialchars($room_name); ?>
                                        </h4>
                                        <span style="font-size: 12.5px; color: var(--gray-500);">
                                            <?= count($requests); ?> Pending Schedule(s)
                                        </span>
                                    </div>
                                    <svg id="arrow_room_<?= $room_id ?>" style="width: 20px; height: 20px; color: var(--gray-400); transition: transform var(--transition);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>

                                <div id="room_<?= $room_id ?>" style="display: none; padding: 15px;">
                                    <div class="table-wrapper" style="box-shadow: none; border: 1px solid var(--gray-100);">
                                        <table style="font-size: 13.5px;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Student / Booker</th>
                                                    <th>Purpose</th>
                                                    <th>Type</th>
                                                    <th style="text-align: right;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($requests as $row): ?>
                                                    <tr>
                                                        <td><strong><?= date('M d, Y', strtotime($row['booking_date'])); ?></strong></td>
                                                        <td><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                                        <td>
                                                            <?= htmlspecialchars($row['fullname'] ?? 'Walk-in'); ?><br>
                                                            <span style="font-size: 11.5px; color: var(--gray-400);"><?= htmlspecialchars($row['stud_no'] ?? '-'); ?></span>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['purpose']); ?></td>
                                                        <td><?= htmlspecialchars($row['booking_type']); ?></td>
                                                        <td style="text-align: right;">
                                                            <a href="approve_reject.php?id=<?= $row['id']; ?>" class="btn" style="padding: 6px 14px; min-width: 0; font-size: 12.5px; height: 32px !important;">Review</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div> 
                            </div>
                        <?php endforeach; ?> 
                    </div> 
                </div>
            <?php endforeach; ?> 
        <?php endif; ?>

        <div class="admin-spacer-top">
            <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
        </div>
    </div>
</div>

<script>
function toggleAccordion(contentId, arrowId) {
    const content = document.getElementById(contentId);
    const arrow = document.getElementById(arrowId);
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}
</script>

<?php include '../includes/footer.php'; ?>