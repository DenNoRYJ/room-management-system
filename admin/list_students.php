<?php
/**
 * Student Directory Page (Scope-Aware & Cascade Delete)
 * admin/list_students.php
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove_student_id'])) {
    $remove_id = intval($_POST['remove_student_id']);
    
    // 1. Cascade Delete: Remove all bookings associated with this student first
    $deleteBookingsStmt = $conn->prepare("DELETE FROM bookings WHERE student_id = ?");
    $deleteBookingsStmt->bind_param("i", $remove_id);
    $deleteBookingsStmt->execute();

    // 2. Delete the student record
    $deleteStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $deleteStmt->bind_param("i", $remove_id);
    
    if ($deleteStmt->execute()) {
        $success = "Student and all their associated booking requests have been removed completely.";
    } else {
        $error = "Failed to remove student.";
    }
}

// Establish filtering criteria
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$whereClause = "WHERE students.account_status = 'Approved'";

if ($is_dept_admin) {
    // Lock list strictly to this admin's department
    $whereClause .= " AND students.course_id = " . intval($admin_course_id);
} elseif ($course_filter > 0) {
    // Apply Super Admin dropdown filter
    $whereClause .= " AND students.course_id = $course_filter";
}

$query = "
    SELECT students.*, courses.course_name, courses.course_code 
    FROM students 
    INNER JOIN courses ON students.course_id = courses.id 
    $whereClause
    ORDER BY students.fullname ASC
";
$studentsList = $conn->query($query);
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$pageTitle = "Student Directory";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">Approved Student Directory</h2>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <?php if (!$is_dept_admin): ?>
            <form method="GET" class="admin-filter-flex">
                <div class="form-group admin-filter-group-sm">
                    <label>Filter by Department</label>
                    <select name="course_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">All Departments</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['id']; ?>" <?= ($course['id'] == $course_filter) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <a href="list_students.php" class="btn btn-secondary admin-clear-btn">Clear Filter</a>
            </form>
        <?php else: ?>
            <p style="color: var(--gray-600); margin-bottom: 20px;">Displaying all approved students registered under your department.</p>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <?php if (!$is_dept_admin): ?><th>Department</th><?php endif; ?>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($studentsList->num_rows > 0): ?>
                        <?php while ($row = $studentsList->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['student_id']); ?></strong></td>
                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <?php if (!$is_dept_admin): ?>
                                    <td><span class="badge badge-maintenance"><?= htmlspecialchars($row['course_code']); ?></span></td>
                                <?php endif; ?>
                                <td style="text-align: right;">
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to completely remove this student? All their booking requests will also be deleted. This action cannot be undone.');">
                                        <input type="hidden" name="remove_student_id" value="<?= $row['id']; ?>">
                                        <button type="submit" class="btn admin-remove-btn" style="padding: 6px 12px; font-size: 12px; height: 32px !important;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= $is_dept_admin ? '4' : '5'; ?>" style="text-align: center; padding: 20px; color: var(--gray-400);">No approved students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-spacer-top">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>