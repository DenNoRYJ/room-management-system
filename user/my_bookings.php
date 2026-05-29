<?php
/**
 * My Bookings History (Grouped by Room)
 * user/my_bookings.php
 * Groups all schedules by Room Name. Displays the next upcoming booking 
 * on the accordion header. Allows students to cancel specific dates to free up rooms, 
 * and selectively auto-restore them.
 */
include '../includes/db.php';
include '../includes/auth.php';
requireStudent();

date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d');

$student_id = $_SESSION['student_id'];
$success = "";
$error = "";

// ══════════════════════════════════════════════════
// ACTION: CANCEL BOOKING
// ══════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_booking_id'])) {
    $cancel_id = intval($_POST['cancel_booking_id']);
    
    $cancelStmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'Cancelled', admin_remarks = 'Cancelled by Student (Room Freed Up)' 
        WHERE id = ? AND student_id = ? AND status IN ('Pending', 'Approved')
    ");
    $cancelStmt->bind_param("ii", $cancel_id, $student_id);
    
    if ($cancelStmt->execute() && $cancelStmt->affected_rows > 0) {
        $success = "Schedule cancelled successfully. The room is now free for others to book.";
    } else {
        $error = "Failed to cancel booking. It may have already passed or been processed.";
    }
}

// ══════════════════════════════════════════════════
// ACTION: RESTORE BOOKING
// ══════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['restore_booking_id'])) {
    $restore_id = intval($_POST['restore_booking_id']);
    
    // 1. Get the booking details
    $getStmt = $conn->prepare("SELECT room_id, booking_date, start_time, end_time FROM bookings WHERE id = ? AND student_id = ? AND status = 'Cancelled'");
    $getStmt->bind_param("ii", $restore_id, $student_id);
    $getStmt->execute();
    $result = $getStmt->get_result();

    if ($result->num_rows === 1) {
        $bookingToRestore = $result->fetch_assoc();
        
        // 2. Check if another section already took or requested this slot
        $conflictStmt = $conn->prepare("
            SELECT id FROM bookings
            WHERE room_id = ? 
              AND booking_date = ? 
              AND status IN ('Pending', 'Approved') 
              AND id != ? 
              AND ((? < end_time) AND (? > start_time))
        ");
        $conflictStmt->bind_param("isiss", 
            $bookingToRestore['room_id'], 
            $bookingToRestore['booking_date'], 
            $restore_id, 
            $bookingToRestore['start_time'], 
            $bookingToRestore['end_time']
        );
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();

        if ($conflictResult->num_rows > 0) {
            // CONFLICT FOUND: Someone else claimed it. Send to Pending for Admin.
            $restoreStmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'Pending', admin_remarks = 'Restored by Student (Conflict - Awaiting Admin Resolution)' 
                WHERE id = ?
            ");
            $restoreStmt->bind_param("i", $restore_id);
            if ($restoreStmt->execute()) {
                $success = "Schedule restored, but another section has already requested or claimed this slot. It is now Pending admin resolution.";
            }
        } else {
            // NO CONFLICT: The room is still empty. Auto-Approve it!
            $restoreStmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'Approved', admin_remarks = 'Restored by Student (Auto-Approved)' 
                WHERE id = ?
            ");
            $restoreStmt->bind_param("i", $restore_id);
            if ($restoreStmt->execute()) {
                $success = "Schedule restored and auto-approved successfully!";
            }
        }
    }
}

// ══════════════════════════════════════════════════
// DATA FETCH & STRICT ROOM GROUPING LOGIC
// ══════════════════════════════════════════════════
$stmt = $conn->prepare("
    SELECT bookings.*, rooms.room_name, rooms.room_type, courses.course_name
    FROM bookings
    INNER JOIN rooms ON bookings.room_id = rooms.id
    INNER JOIN courses ON rooms.course_id = courses.id
    WHERE bookings.student_id = ?
    ORDER BY bookings.room_id ASC, bookings.booking_date ASC, bookings.start_time ASC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$bookingsResult = $stmt->get_result();

// Group ALL bookings purely by Room ID
$grouped_bookings = [];
while ($row = $bookingsResult->fetch_assoc()) {
    $group_key = $row['room_id'];
    
    if (!isset($grouped_bookings[$group_key])) {
        $grouped_bookings[$group_key] = [
            'room_name' => $row['room_name'],
            'course_name' => $row['course_name'],
            'dates' => []
        ];
    }
    $grouped_bookings[$group_key]['dates'][] = $row;
}

$pageTitle = "My Bookings";
$extraCSS = ['user/user.css'];
include '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <h2 class="section-title">My Schedule & Bookings</h2>
        <p style="color: var(--gray-600); margin-bottom: 20px;">
            Manage your reservations below, and free up unused rooms by clicking <strong>Cancel</strong>.
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <?php if (empty($grouped_bookings)): ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed var(--gray-200); border-radius: var(--radius-md);">
                <p style="color: var(--gray-400);">No bookings found.</p>
            </div>
        <?php else: ?>
            
            <!-- Generate Grouped Accordion Cards -->
            <?php foreach ($grouped_bookings as $key => $group): 
                $group_id = md5($key); 
                
                // Determine Upcoming Booking
                $upcoming_text = "No upcoming active bookings";
                foreach ($group['dates'] as $d) {
                    if ($d['booking_date'] >= $current_date && in_array($d['status'], ['Pending', 'Approved'])) {
                        $upcoming_text = date('M d, Y', strtotime($d['booking_date'])) . " at " . date('h:i A', strtotime($d['start_time']));
                        break;
                    }
                }
            ?>
                
                <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 15px;">
                    <!-- Accordion Header -->
                    <div style="background: var(--off-white); padding: 18px 24px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid transparent;" 
                         onclick="toggleAccordion('<?= $group_id ?>')">
                        <div>
                            <h3 style="color: var(--crimson); font-family: var(--font-display); font-size: 20px; margin-bottom: 4px; font-weight: 700;">
                                <?= htmlspecialchars($group['room_name']); ?>
                            </h3>
                            <p style="font-size: 13.5px; color: var(--gray-600);">
                                <strong style="color: var(--gray-800);">Upcoming Booking:</strong> 
                                <?= $upcoming_text; ?>
                                &nbsp;|&nbsp; 
                                <span style="color: var(--crimson); font-weight: 600;"><?= count($group['dates']); ?> Date(s) Booked</span>
                            </p>
                        </div>
                        
                        <!-- Toggle Arrow -->
                        <svg id="arrow_<?= $group_id ?>" style="width: 24px; height: 24px; color: var(--crimson); transition: transform var(--transition);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <!-- Accordion Hidden Content (The Table of Dates) -->
                    <div id="content_<?= $group_id ?>" style="display: none; padding: 20px; border-top: 1px solid var(--gray-100);">
                        <div class="table-wrapper" style="box-shadow: none; border: 1px solid var(--gray-100);">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Admin Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['dates'] as $dateRow): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($dateRow['booking_date'])); ?></td>
                                            <td><?= date('h:i A', strtotime($dateRow['start_time'])) . ' - ' . date('h:i A', strtotime($dateRow['end_time'])); ?></td>
                                            <td><?= htmlspecialchars($dateRow['purpose']); ?></td>
                                            
                                            <!-- Status Badge -->
                                            <td>
                                                <?php
                                                    $statusClass = 'badge-available'; // default for Approved
                                                    if ($dateRow['status'] === 'Pending') $statusClass = 'badge-maintenance';
                                                    if ($dateRow['status'] === 'Rejected' || $dateRow['status'] === 'Cancelled') $statusClass = 'badge-unavailable';
                                                ?>
                                                <span class="badge <?= $statusClass; ?>"><?= htmlspecialchars($dateRow['status']); ?></span>
                                            </td>
                                            
                                            <td><?= htmlspecialchars($dateRow['admin_remarks'] ?? '-'); ?></td>
                                            
                                            <!-- Action Buttons -->
                                            <td>
                                                <?php if ($dateRow['booking_date'] >= $current_date): ?>
                                                    
                                                    <!-- Show Cancel if active -->
                                                    <?php if ($dateRow['status'] === 'Pending' || $dateRow['status'] === 'Approved'): ?>
                                                        <form method="POST" style="margin:0;">
                                                            <input type="hidden" name="cancel_booking_id" value="<?= $dateRow['id']; ?>">
                                                            <button type="submit" class="btn" style="background: var(--gray-600); border-color: var(--gray-600); padding: 4px 12px; font-size: 12px; min-width: 0; height: 32px !important;" onclick="return confirm('Are you sure you want to cancel this specific date? The room will be immediately freed up for other sections.');">Cancel Date</button>
                                                        </form>
                                                    
                                                    <!-- Show Restore if previously cancelled -->
                                                    <?php elseif ($dateRow['status'] === 'Cancelled'): ?>
                                                        <form method="POST" style="margin:0;">
                                                            <input type="hidden" name="restore_booking_id" value="<?= $dateRow['id']; ?>">
                                                            <button type="submit" class="btn btn-secondary" style="padding: 4px 12px; font-size: 12px; min-width: 0; height: 32px !important;" onclick="return confirm('Attempt to restore this schedule? If no one else has claimed the room, it will be automatically approved.');">Restore Date</button>
                                                        </form>
                                                    <?php endif; ?>

                                                <?php else: ?>
                                                    <span style="color: var(--gray-400); font-size: 12px; font-weight: 600;">PAST DATE</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

        <div class="user-mt-25">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
// Simple toggle script for the Accordion Grouping
function toggleAccordion(groupId) {
    const content = document.getElementById('content_' + groupId);
    const arrow = document.getElementById('arrow_' + groupId);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}
</script>

<?php include '../includes/footer.php'; ?>