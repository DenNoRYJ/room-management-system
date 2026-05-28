<?php
/**
 * Admin Directory Page
 * Displays a list of all administrators with filtering by department.
 * Admins can remove other admins (except themselves).
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$success = "";
$error = "";

// Handle admin removal requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove_admin_id'])) {
    $remove_id = intval($_POST['remove_admin_id']);
    
    // Prevent self-deletion
    if ($remove_id === $_SESSION['admin_id']) {
        $error = "You cannot remove your own administrator account.";
    } else {
        $deleteStmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $deleteStmt->bind_param("i", $remove_id);
        
        if ($deleteStmt->execute()) {
            $success = "Administrator removed successfully.";
        } else {
            $error = "Failed to remove administrator.";
        }
    }
}

// Establish filtering criteria
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : -1;
$whereClause = "";

if ($course_filter == 0) {
    // 0 represents Super Admins (no specific department assignment)
    $whereClause = "WHERE admins.course_id IS NULL";
} elseif ($course_filter > 0) {
    $whereClause = "WHERE admins.course_id = $course_filter";
}

// Query the admin list based on applied filters
$query = "
    SELECT admins.*, courses.course_name, courses.course_code 
    FROM admins 
    LEFT JOIN courses ON admins.course_id = courses.id 
    $whereClause
    ORDER BY admins.fullname ASC
";

$adminsList = $conn->query($query);
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

$pageTitle = "Admin Directory";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="admin-header-flex">
            <h2 class="section-title">Administrator Directory</h2>
            <a href="create_admin.php" class="btn">Add</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error; ?></div><?php endif; ?>

        <form method="GET" class="admin-filter-flex">
            <div class="form-group admin-filter-group">
                <label>Filter by Department</label>
                <select name="course_id" class="form-control" onchange="this.form.submit()">
                    <option value="-1">All Administrators</option>
                    <option value="0" <?= ($course_filter === 0) ? 'selected' : ''; ?>>Super Admins (No Specific Dept)</option>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?= $course['id']; ?>" <?= ($course['id'] == $course_filter) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <a href="list_admins.php" class="btn btn-secondary admin-clear-btn">Clear Filter</a>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Department</th>
                        <th>Date Added</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($adminsList->num_rows > 0): ?>
                        <?php while ($row = $adminsList->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <?php if ($row['course_code']): ?>
                                        <span class="badge badge-available"><?= htmlspecialchars($row['course_code']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-maintenance">Super Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['created_at']))); ?></td>
                                <td>
                                    <?php if ($row['id'] !== $_SESSION['admin_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove this administrator?');">
                                            <input type="hidden" name="remove_admin_id" value="<?= $row['id']; ?>">
                                            <button type="submit" class="btn admin-remove-btn">Remove</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-available">Current Session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No administrators found for this filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="admin-spacer-top">
            <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>