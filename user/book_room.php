<?php
/**
 * Book Room Form
 * user/book_room.php
 * Allows a student to submit a reservation request for a specific room.
 * Includes logic to prevent double-booking conflicts, handles recurring weekly/everyday schedules,
 * and auto-approves weekly class schedules.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireStudent();

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$error = "";
$success = "";

// Fetch the requested room and its associated course
$roomStmt = $conn->prepare("
    SELECT rooms.*, courses.course_name
    FROM rooms
    INNER JOIN courses ON rooms.course_id = courses.id
    WHERE rooms.id = ?
");
$roomStmt->bind_param("i", $room_id);
$roomStmt->execute();
$roomResult = $roomStmt->get_result();

if ($roomResult->num_rows === 0) {
    die("Room not found.");
}

$room = $roomResult->fetch_assoc();

// Process the booking form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $booking_date        = trim($_POST['booking_date']);
    $start_time          = trim($_POST['start_time']);
    $end_time            = trim($_POST['end_time']);
    $purpose             = trim($_POST['purpose']);
    $student_id          = $_SESSION['student_id'];
    
    // Recurring fields
    $recurrence_type     = $_POST['recurrence_type'] ?? 'none';
    $recurrence_end_date = trim($_POST['recurrence_end_date']);

    if (empty($booking_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        $error = "Please fill in all basic fields.";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be later than start time.";
    } elseif ($recurrence_type !== 'none' && empty($recurrence_end_date)) {
        $error = "Please provide an end date for the recurring schedule.";
    } elseif ($recurrence_type !== 'none' && $recurrence_end_date < $booking_date) {
        $error = "Recurrence end date cannot be earlier than the start date.";
    } else {
        
        // 1. Generate all dates for this booking based on recurrence
        $booking_dates = [];
        $current_date_ts = strtotime($booking_date);
        $end_date_ts = ($recurrence_type !== 'none') ? strtotime($recurrence_end_date) : $current_date_ts;

        while ($current_date_ts <= $end_date_ts) {
            $booking_dates[] = date('Y-m-d', $current_date_ts);
            
            if ($recurrence_type === 'everyday') {
                $current_date_ts = strtotime('+1 day', $current_date_ts);
            } elseif ($recurrence_type === 'weekly') {
                $current_date_ts = strtotime('+1 week', $current_date_ts);
            } else {
                break; // Break immediately if 'none'
            }
        }

        // 2. Check for conflicts across ALL generated dates
        $conflictStmt = $conn->prepare("
            SELECT id FROM bookings
            WHERE room_id = ?
              AND booking_date = ?
              AND status IN ('Pending', 'Approved')
              AND ((? < end_time) AND (? > start_time))
        ");

        $has_conflict = false;
        $conflict_dates = [];

        foreach ($booking_dates as $date) {
            $conflictStmt->bind_param("isss", $room_id, $date, $start_time, $end_time);
            $conflictStmt->execute();
            $conflictResult = $conflictStmt->get_result();

            if ($conflictResult->num_rows > 0) {
                $has_conflict = true;
                $conflict_dates[] = date('M d, Y', strtotime($date));
            }
        }

        // 3. Evaluate Auto-Approve Rule (Must be Weekly AND exactly "Class Schedule")
        $is_class_schedule = (strtolower($purpose) === 'class schedule');
        $initial_status = ($recurrence_type === 'weekly' && $is_class_schedule) ? 'Approved' : 'Pending';
        $auto_remarks = ($initial_status === 'Approved') ? 'Auto-Approved (Weekly Class Schedule)' : NULL;

        // 4. Insert bookings if NO conflicts were found anywhere
        if ($has_conflict) {
            $error = "Conflicts found on the following dates: " . implode(", ", $conflict_dates) . ". Please adjust your schedule.";
        } else {
            $insertStmt = $conn->prepare("
                INSERT INTO bookings (student_id, room_id, booking_type, booking_date, start_time, end_time, purpose, status, admin_remarks)
                VALUES (?, ?, 'Online', ?, ?, ?, ?, ?, ?)
            ");

            $success_count = 0;
            foreach ($booking_dates as $date) {
                $insertStmt->bind_param("iissssss", $student_id, $room_id, $date, $start_time, $end_time, $purpose, $initial_status, $auto_remarks);
                if ($insertStmt->execute()) {
                    $success_count++;
                }
            }

            if ($success_count > 0) {
                if ($initial_status === 'Approved') {
                    $success = "$success_count booking(s) generated and AUTO-APPROVED for your class schedule.";
                } else {
                    $success = "$success_count booking request(s) submitted successfully. Please wait for admin approval.";
                }
            } else {
                $error = "Failed to submit booking request.";
            }
        }
    }
}

$pageTitle = "Book Room";
$extraCSS = ['user/user.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">Book Room</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <!-- Room Information Preview -->
        <div class="grid grid-2">
            <div>
                <img src="../<?= htmlspecialchars($room['room_image']); ?>" class="map-banner user-room-preview-img" alt="Room Image">
            </div>
            <div>
                <h3 class="user-room-title"><?= htmlspecialchars($room['room_name']); ?></h3>
                <p><strong>Course:</strong> <?= htmlspecialchars($room['course_name']); ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($room['room_type']); ?></p>
                <p><strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($room['status']); ?></p>
                <p class="user-mt-10"><?= htmlspecialchars($room['description']); ?></p>
            </div>
        </div>

        <!-- Booking Form -->
        <form method="POST" class="user-mt-25">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Start / Single Booking Date</label>
                    <input type="date" name="booking_date" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Purpose</label>
                    <input type="text" name="purpose" class="form-control" placeholder="Type 'Class Schedule' to auto-approve weekly setups" required>
                </div>

                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>

                <!-- Recurrence Options -->
                <div class="form-group admin-grid-full" style="margin-bottom: 0;">
                    <label>Recurrence Options</label>
                    <select name="recurrence_type" id="recurrenceType" class="form-control">
                        <option value="none">Does Not Repeat (Single Booking)</option>
                        <option value="everyday">Repeats Everyday (e.g., University Week Event)</option>
                        <option value="weekly">Repeats Weekly (Auto-approved if Purpose is "Class Schedule")</option>
                    </select>
                </div>

                <!-- Hidden End Date Field -->
                <div id="recurringOptions" class="admin-grid-full" style="display: none; background: var(--crimson-pale); padding: 20px; border-radius: var(--radius-md); border: 1px dashed var(--crimson-light);">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Repeat Until (End Date)</label>
                        <input type="date" name="recurrence_end_date" id="recurrenceEndDate" class="form-control" min="<?= date('Y-m-d'); ?>">
                        <p style="font-size: 12.5px; color: var(--gray-600); margin-top: 8px;">
                            The system will automatically generate a booking for the chosen pattern up until this date.
                        </p>
                    </div>
                </div>

            </div> <!-- End of grid-2 -->
            
            <!-- Actions Container -->
            <div class="booking-actions">
                <button type="submit" class="btn">Submit Booking Request</button>
                <a href="view_rooms.php?course_id=<?= $room['course_id']; ?>" class="btn btn-secondary">Back to Rooms</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('recurrenceType').addEventListener('change', function() {
        const recurringBox = document.getElementById('recurringOptions');
        const endDateInput = document.getElementById('recurrenceEndDate');
        
        if (this.value !== 'none') {
            recurringBox.style.display = 'block';
            endDateInput.setAttribute('required', 'required');
        } else {
            recurringBox.style.display = 'none';
            endDateInput.removeAttribute('required');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>