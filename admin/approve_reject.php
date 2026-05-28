<?php
/**
 * Review Booking Request
 * admin/approve_reject.php
 * Allows administrators to approve, reject, or Force-Approve bookings.
 * Strictly enforces department scope so admins cannot approve requests outside their domain.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$admin_id = $_SESSION['admin_id'];
$error = "";
$success = "";
$has_conflict = false;

// 1. Determine Admin Scope
$scopeStmt = $conn->prepare("SELECT course_id FROM admins WHERE id = ?");
$scopeStmt->bind_param("i", $admin_id);
$scopeStmt->execute();
$adminScope = $scopeStmt->get_result()->fetch_assoc();
$admin_course_id = $adminScope['course_id'];

// 2. Fetch the specific booking details
$stmt = $conn->prepare("
    SELECT bookings.*, students.fullname, students.student_id AS stud_no, rooms.room_name, rooms.course_id, courses.course_name
    FROM bookings
    LEFT JOIN students ON bookings.student_id = students.id
    INNER JOIN rooms ON bookings.room_id = rooms.id
    INNER JOIN courses ON rooms.course_id = courses.id
    WHERE bookings.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();

// 3. SECURE SCOPE VALIDATION: Block if this room does not belong to the admin's department
if (!is_null($admin_course_id) && $admin_course_id > 0) {
    if ($booking['course_id'] != $admin_course_id) {
        die("<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
                <h2 style='color: #8B0000;'>Unauthorized Action</h2>
                <p>You do not have permission to manage room bookings for the <strong>" . htmlspecialchars($booking['course_name']) . "</strong> department.</p>
                <a href='pending_bookings.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #8B0000; color: white; text-decoration: none; border-radius: 5px;'>Back to List</a>
             </div>");
    }
}

// Process the form action (Approve, Reject, or Force Approve)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];
    $remarks = trim($_POST['admin_remarks']);

    if ($action === "approve") {
        $conflictStmt = $conn->prepare("
            SELECT id FROM bookings
            WHERE room_id = ?
              AND booking_date = ?
              AND status = 'Approved'
              AND id != ?
              AND ((? < end_time) AND (? > start_time))
        ");
        $conflictStmt->bind_param("isiss", $booking['room_id'], $booking['booking_date'], $booking_id, $booking['start_time'], $booking['end_time']);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();

        if ($conflictResult->num_rows > 0) {
            $error = "Conflict detected! Another section already holds an approved schedule for this room at this time. If you must prioritize this request, use the Force Approve option below.";
            $has_conflict = true;
        } else {
            $update = $conn->prepare("UPDATE bookings SET status = 'Approved', admin_remarks = ?, approved_by = ? WHERE id = ?");
            $update->bind_param("sii", $remarks, $admin_id, $booking_id);

            if ($update->execute()) {
                $success = "Booking approved successfully.";
                $booking['status'] = 'Approved';
                $booking['admin_remarks'] = $remarks;
            } else {
                $error = "Failed to approve booking.";
            }
        }
    }

    if ($action === "force_approve") {
        $conflictStmt = $conn->prepare("
            SELECT id FROM bookings
            WHERE room_id = ?
              AND booking_date = ?
              AND status = 'Approved'
              AND id != ?
              AND ((? < end_time) AND (? > start_time))
        ");
        $conflictStmt->bind_param("isiss", $booking['room_id'], $booking['booking_date'], $booking_id, $booking['start_time'], $booking['end_time']);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();

        $revoke_remarks = "Revoked by Administrator: Schedule priority was overridden and reassigned to another section.";
        $revokeStmt = $conn->prepare("UPDATE bookings SET status = 'Rejected', admin_remarks = ?, approved_by = ? WHERE id = ?");

        while ($conflict = $conflictResult->fetch_assoc()) {
            $revokeStmt->bind_param("sii", $revoke_remarks, $admin_id, $conflict['id']);
            $revokeStmt->execute();
        }

        $update = $conn->prepare("UPDATE bookings SET status = 'Approved', admin_remarks = ?, approved_by = ? WHERE id = ?");
        $update->bind_param("sii", $remarks, $admin_id, $booking_id);

        if ($update->execute()) {
            $success = "Override successful! Conflicting schedules were automatically revoked, and this booking is now Approved.";
            $booking['status'] = 'Approved';
            $booking['admin_remarks'] = $remarks;
            $has_conflict = false;
            $error = ""; 
        } else {
            $error = "Failed to force-approve the booking.";
        }
    }

    if ($action === "reject") {
        $update = $conn->prepare("UPDATE bookings SET status = 'Rejected', admin_remarks = ?, approved_by = ? WHERE id = ?");
        $update->bind_param("sii", $remarks, $admin_id, $booking_id);

        if ($update->execute()) {
            $success = "Booking rejected successfully.";
            $booking['status'] = 'Rejected';
            $booking['admin_remarks'] = $remarks;
        } else {
            $error = "Failed to reject booking.";
        }
    }
}

$pageTitle = "Review Booking";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">Review Booking Request</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-2">
            <div>
                <p><strong>Student:</strong> <?= htmlspecialchars($booking['fullname'] ?? 'Walk-in'); ?></p>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($booking['stud_no'] ?? '-'); ?></p>
                <p><strong>Room:</strong> <?= htmlspecialchars($booking['room_name']); ?></p>
                <p><strong>Course:</strong> <?= htmlspecialchars($booking['course_name']); ?></p>
            </div>
            <div>
                <p><strong>Date:</strong> <?= htmlspecialchars($booking['booking_date']); ?></p>
                <p><strong>Time:</strong> <?= htmlspecialchars(date('h:i A', strtotime($booking['start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($booking['end_time']))); ?></p>
                <p><strong>Purpose:</strong> <?= htmlspecialchars($booking['purpose']); ?></p>
                <p><strong>Current Status:</strong> <?= htmlspecialchars($booking['status']); ?></p>
            </div>
        </div>

        <?php if ($booking['status'] === 'Pending'): ?>
            <form method="POST" class="admin-spacer-top">
                <div class="form-group">
                    <label>Admin Remarks</label>
                    <textarea name="admin_remarks" class="form-control" rows="4" placeholder="Optional remarks..."><?= htmlspecialchars($_POST['admin_remarks'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <?php if ($has_conflict): ?>
                        <button type="submit" name="action" value="force_approve" class="btn" style="background: var(--gold); border-color: var(--gold); color: #000; flex: 1;">
                            Force Approve & Revoke Conflicts
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-secondary" style="flex: 1;">Reject Request</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="approve" class="btn" style="flex: 1;">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-secondary" style="flex: 1;">Reject</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php else: ?>
            <div class="admin-spacer-top">
                <p><strong>Final Remarks:</strong> <?= htmlspecialchars($booking['admin_remarks'] ?? '-'); ?></p>
            </div>
        <?php endif; ?>

        <div class="admin-spacer-top">
            <a href="pending_bookings.php" class="btn btn-secondary">Back to Pending Bookings</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>