<?php
/**
 * Pending Student Approvals (Scope-Aware)
 * admin/pending_students.php
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $student_id = intval($_POST['student_id']);
    $action = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';

    $updateStmt = $conn->prepare("UPDATE students SET account_status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $action, $student_id);
    
    if ($updateStmt->execute()) {
        $success = "Student account has been " . strtolower($action) . ".";
    } else {
        $error = "Failed to update student status.";
    }
}

// Fetch pending students based on scope
$whereClause = "WHERE students.account_status = 'Pending'";
if ($is_dept_admin) {
    $whereClause .= " AND students.course_id = " . intval($admin_course_id);
}

$pendingStudents = $conn->query("
    SELECT students.*, courses.course_name, courses.course_code 
    FROM students 
    INNER JOIN courses ON students.course_id = courses.id 
    $whereClause 
    ORDER BY students.created_at DESC
");

$total_pending_students = $pendingStudents->num_rows;

$pageTitle = "Pending Student Approvals";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        
        <div class="admin-header-flex" style="align-items: flex-start; margin-bottom: 25px;">
            <div>
                <h2 class="section-title" style="margin-bottom: 5px;">Pending Student Approvals</h2>
                <p style="color: var(--gray-600); font-size: 14px;">
                    <?= $is_dept_admin ? "Showing pending account registrations strictly for your department." : "Super Admin View: Displaying all pending registrations across the university."; ?>
                </p>
            </div>
            <span class="badge badge-maintenance" style="font-size: 14px; padding: 6px 14px;">
                <?= $total_pending_students; ?> Total Pending
            </span>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <?php if (!$is_dept_admin): ?><th>Department</th><?php endif; ?>
                        <th>Date Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_pending_students > 0): ?>
                        <?php while ($row = $pendingStudents->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_id']); ?></td>
                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <?php if (!$is_dept_admin): ?>
                                    <td><span class="badge badge-available"><?= htmlspecialchars($row['course_code']); ?></span></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['created_at']))); ?></td>
                                <td>
                                    <form method="POST" class="admin-inline-flex" style="margin: 0;">
                                        <input type="hidden" name="student_id" value="<?= $row['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn admin-compact-btn">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-secondary admin-compact-btn">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= $is_dept_admin ? '5' : '6'; ?>" style="text-align: center; padding: 20px; color: var(--gray-400);">No pending student registrations.</td></tr>
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