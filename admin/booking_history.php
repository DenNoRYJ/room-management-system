<?php
/**
 * Booking History Log (Triple Nested Grouping View & Tracking)
 * admin/booking_history.php
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

// Handle Filters
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;

$whereClause = "WHERE bookings.status IN ('Approved', 'Rejected', 'Cancelled')";

if ($is_dept_admin) {
    $whereClause .= " AND courses.id = " . intval($admin_course_id);
} elseif ($course_filter > 0) {
    $whereClause .= " AND courses.id = $course_filter";
}

if ($year_filter > 0) {
    $whereClause .= " AND YEAR(bookings.booking_date) = $year_filter";
}

// NEW: Joining admins table to fetch the reviewer's name
$historyQuery = $conn->query("
    SELECT bookings.*, 
           students.fullname, students.student_id AS stud_no, 
           rooms.room_name, 
           courses.course_name, courses.course_code,
           student_courses.course_code AS student_dept,
           admins.fullname AS admin_name
    FROM bookings
    LEFT JOIN students ON bookings.student_id = students.id
    LEFT JOIN courses AS student_courses ON students.course_id = student_courses.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    INNER JOIN courses ON rooms.course_id = courses.id
    LEFT JOIN admins ON bookings.approved_by = admins.id
    $whereClause
    ORDER BY courses.course_name ASC, rooms.room_name ASC, students.fullname ASC, bookings.booking_date DESC
");

// Group data: Course -> Room -> User -> Array of Bookings
$grouped_history = [];
$total_records = 0;

if ($historyQuery && $historyQuery->num_rows > 0) {
    while ($row = $historyQuery->fetch_assoc()) {
        $course_name = $row['course_name'];
        $room_name = $row['room_name'];
        
        $student_name = $row['fullname'] ?? 'Walk-in';
        $stud_no = $row['stud_no'] ?? 'N/A';
        $stud_dept = $row['student_dept'] ?? 'Walk-in';
        $booking_year = date('Y', strtotime($row['booking_date']));
        
        $user_key = "$student_name|$stud_no|$stud_dept|$booking_year";
        
        if (!isset($grouped_history[$course_name])) $grouped_history[$course_name] = [];
        if (!isset($grouped_history[$course_name][$room_name])) $grouped_history[$course_name][$room_name] = [];
        if (!isset($grouped_history[$course_name][$room_name][$user_key])) $grouped_history[$course_name][$room_name][$user_key] = [];
        
        $grouped_history[$course_name][$room_name][$user_key][] = $row;
        $total_records++;
    }
}

$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
$yearsResult = $conn->query("SELECT DISTINCT YEAR(booking_date) as year FROM bookings WHERE status != 'Pending' ORDER BY year DESC");

$pageTitle = "Booking History";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        
        <div class="admin-header-flex" style="align-items: flex-start; margin-bottom: 25px;">
            <div>
                <h2 class="section-title" style="margin-bottom: 5px;">Booking History Log</h2>
                <p style="color: var(--gray-600); font-size: 14px;">
                    <?php if (is_null($admin_course_id)): ?>
                        Super Admin View: Displaying historical records across all departments.
                    <?php else: ?>
                        Department Admin View: Displaying historical records strictly for your department.
                    <?php endif; ?>
                </p>
            </div>
            <span class="badge badge-available" style="font-size: 14px; padding: 6px 14px; background: var(--gray-100); color: var(--gray-800);">
                <?= $total_records; ?> Records Found
            </span>
        </div>

        <form method="GET" class="admin-filter-flex" style="margin-bottom: 30px; background: var(--gray-50); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--gray-100);">
            
            <?php if (!$is_dept_admin): ?>
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
                <a href="booking_history.php" class="btn btn-secondary admin-clear-btn" style="height: 44px !important; margin: 0; background: var(--white);">Clear Filters</a>
            </div>
        </form>

        <?php if (empty($grouped_history)): ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed var(--gray-200); border-radius: var(--radius-md);">
                <p style="color: var(--gray-400);">No historical booking records match this criteria.</p>
            </div>
        <?php else: ?>

            <?php foreach ($grouped_history as $course_name => $rooms): 
                $course_id = md5($course_name); 
                $course_total = 0;
                foreach ($rooms as $users) {
                    foreach ($users as $user_requests) {
                        $course_total += count($user_requests);
                    }
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
                                <?= $course_total; ?> Historical Record(s)
                            </span>
                        </div>
                        <svg id="arrow_course_<?= $course_id ?>" style="width: 24px; height: 24px; color: var(--white); transition: transform var(--transition); transform: rotate(0deg);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <div id="course_<?= $course_id ?>" style="display: none; padding: 20px; background: var(--off-white);">
                        <?php foreach ($rooms as $room_name => $users): 
                            $room_id = md5($course_name . $room_name); 
                        ?>
                            <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-md); margin-bottom: 12px; background: var(--white); overflow: hidden;">
                                <div style="background: var(--white); padding: 14px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--gray-100);" 
                                     onclick="toggleAccordion('room_<?= $room_id ?>', 'arrow_room_<?= $room_id ?>')">
                                    <div>
                                        <h4 style="font-size: 16px; font-weight: 700; color: var(--crimson);">
                                            <?= htmlspecialchars($room_name); ?>
                                        </h4>
                                    </div>
                                    <svg id="arrow_room_<?= $room_id ?>" style="width: 20px; height: 20px; color: var(--gray-400); transition: transform var(--transition);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>

                                <div id="room_<?= $room_id ?>" style="display: none; padding: 15px; background: var(--white);">
                                    <?php foreach ($users as $user_key => $user_requests): 
                                        $user_id_hash = md5($course_name . $room_name . $user_key);
                                        list($student_name, $stud_no, $stud_dept, $booking_year) = explode('|', $user_key);
                                    ?>
                                        <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-sm); margin-bottom: 10px;">
                                            <div style="background: var(--gray-50); padding: 12px 18px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                                                 onclick="toggleAccordion('user_<?= $user_id_hash ?>', 'arrow_user_<?= $user_id_hash ?>')">
                                                <div>
                                                    <h5 style="font-size: 15px; font-weight: 600; color: var(--gray-800); margin: 0 0 4px 0;">
                                                        <?= htmlspecialchars($student_name); ?>
                                                    </h5>
                                                    <span style="font-size: 12.5px; color: var(--gray-500);">
                                                        ID: <strong><?= htmlspecialchars($stud_no); ?></strong> | 
                                                        Dept: <strong><?= htmlspecialchars($stud_dept); ?></strong> | 
                                                        Year: <strong><?= htmlspecialchars($booking_year); ?></strong>
                                                        <span style="color: var(--crimson); margin-left: 10px; font-weight: 600;">(<?= count($user_requests); ?> logs)</span>
                                                    </span>
                                                </div>
                                                <svg id="arrow_user_<?= $user_id_hash ?>" style="width: 18px; height: 18px; color: var(--gray-400); transition: transform var(--transition);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>

                                            <div id="user_<?= $user_id_hash ?>" style="display: none; padding: 15px;">
                                                <div class="table-wrapper" style="box-shadow: none; border: 1px solid var(--gray-100);">
                                                    <table style="font-size: 13.5px;">
                                                        <thead>
                                                            <tr>
                                                                <th>Date & Time</th>
                                                                <th>Purpose / Type</th>
                                                                <th>Status</th>
                                                                <th>Reviewed By / Remarks</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($user_requests as $row): ?>
                                                                <tr>
                                                                    <td style="white-space: nowrap;">
                                                                        <strong><?= date('M d, Y', strtotime($row['booking_date'])); ?></strong><br>
                                                                        <span style="font-size: 12px; color: var(--gray-600);"><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></span>
                                                                    </td>
                                                                    <td>
                                                                        <?= htmlspecialchars($row['purpose']); ?><br>
                                                                        <span style="font-size: 11.5px; color: var(--gray-400);"><?= htmlspecialchars($row['booking_type']); ?></span>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                            $statusClass = 'badge-available'; // Approved
                                                                            if ($row['status'] === 'Rejected' || $row['status'] === 'Cancelled') $statusClass = 'badge-unavailable';
                                                                        ?>
                                                                        <span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($row['status']); ?></span>
                                                                    </td>
                                                                    <td style="max-width: 250px;">
                                                                        <strong style="color: var(--gray-800); font-size: 12.5px;"><?= htmlspecialchars($row['admin_name'] ?? 'System / Pre-update'); ?></strong><br>
                                                                        <span style="font-size: 12.5px; color: var(--gray-500);"><?= htmlspecialchars($row['admin_remarks'] ?? '-'); ?></span>
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
                    </div> 
                </div>
            <?php endforeach; ?> 
        <?php endif; ?>

        <div class="admin-spacer-top">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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