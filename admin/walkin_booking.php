<?php
/**
 * Walk-in Booking Page
 * Allows administrators to manually enter and bypass the approval process 
 * for immediate or in-person (walk-in) booking requests.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireAdmin();

$success = "";
$error = "";

// Fetch all available rooms for the dropdown
$rooms = $conn->query("
    SELECT rooms.*, courses.course_name
    FROM rooms
    INNER JOIN courses ON rooms.course_id = courses.id
    WHERE rooms.status = 'Available'
    ORDER BY courses.course_name ASC, rooms.room_name ASC
");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $room_id = intval($_POST['room_id']);
    $booking_date = trim($_POST['booking_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $purpose = trim($_POST['purpose']);
    $remarks = trim($_POST['admin_remarks']);
    $admin_id = $_SESSION['admin_id'];

    if (empty($room_id) || empty($booking_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        $error = "Please fill in all required fields.";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be later than start time.";
    } else {
        // Check for conflicting approved or pending schedules
        $conflictStmt = $conn->prepare("
            SELECT id FROM bookings
            WHERE room_id = ?
              AND booking_date = ?
              AND status IN ('Pending', 'Approved')
              AND ((? < end_time) AND (? > start_time))
        ");
        $conflictStmt->bind_param("isss", $room_id, $booking_date, $start_time, $end_time);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();

        if ($conflictResult->num_rows > 0) {
            $error = "This room already has a conflicting schedule.";
        } else {
            // Insert the booking as automatically 'Approved' since it's an admin action
            $stmt = $conn->prepare("
                INSERT INTO bookings (student_id, room_id, booking_type, booking_date, start_time, end_time, purpose, status, admin_remarks, approved_by)
                VALUES (NULL, ?, 'Walk-in', ?, ?, ?, ?, 'Approved', ?, ?)
            ");
            $stmt->bind_param("isssssi", $room_id, $booking_date, $start_time, $end_time, $purpose, $remarks, $admin_id);

            if ($stmt->execute()) {
                $success = "Walk-in booking recorded successfully.";
            } else {
                $error = "Failed to save walk-in booking.";
            }
        }
    }
}

$pageTitle = "Walk-in Booking";
$extraCSS = ['admin/admin.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">Walk-in Booking</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Select Room</label>
                    <select name="room_id" class="form-control" required>
                        <option value="">Select Room</option>
                        <?php
                        mysqli_data_seek($rooms, 0);
                        while ($room = $rooms->fetch_assoc()):
                        ?>
                            <option value="<?= $room['id']; ?>">
                                <?= htmlspecialchars($room['course_name'] . ' - ' . $room['room_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Booking Date</label>
                    <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>

                <div class="form-group admin-grid-full">
                    <label>Purpose</label>
                    <input type="text" name="purpose" class="form-control" required>
                </div>

                <div class="form-group admin-grid-full">
                    <label>Remarks</label>
                    <textarea name="admin_remarks" class="form-control" rows="4" placeholder="Optional remarks"></textarea>
                </div>
                
                <button type="submit" class="btn">Save Walk-in Booking</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Admin Portal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>