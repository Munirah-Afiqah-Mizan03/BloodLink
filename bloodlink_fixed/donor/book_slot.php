<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: ../login.php'); exit;
}

$user_id   = $_SESSION['user_id'];
$action    = $_GET['action'] ?? '';
$event_get = $_GET['event'] ?? '';

// ── Book a slot ──────────────────────────────────────────
if ($action === 'book') {
    $slot_id = intval($_GET['slot_id'] ?? 0);
    if (!$slot_id) { header('Location: dashboard.php'); exit; }

    // Get slot info and donor info
    $slot = $pdo->prepare("SELECT * FROM bl_slots WHERE id = ?");
    $slot->execute([$slot_id]);
    $slot = $slot->fetch();

    $donor = $pdo->prepare("SELECT * FROM bl_users WHERE id = ?");
    $donor->execute([$user_id]);
    $donor = $donor->fetch();

    if (!$slot) {
        header("Location: dashboard.php?error=Slot not found."); exit;
    }

    // Check if already booked
    $existing = $pdo->prepare("SELECT id FROM bl_bookings WHERE slot_id = ? AND donor_id = ?");
    $existing->execute([$slot_id, $user_id]);
    if ($existing->fetch()) {
        header("Location: dashboard.php?event=" . urlencode($event_get) . "&error=You have already booked this slot."); exit;
    }

    // Check if slot is full
    $booked = $pdo->prepare("SELECT COUNT(*) FROM bl_bookings WHERE slot_id = ?");
    $booked->execute([$slot_id]);
    if ($booked->fetchColumn() >= $slot['capacity']) {
        header("Location: dashboard.php?event=" . urlencode($event_get) . "&error=Sorry, this slot is already full."); exit;
    }

    // Insert booking
    $stmt = $pdo->prepare("INSERT INTO bl_bookings (slot_id, donor_id, donor_name, ic_number, blood_type) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $slot_id,
        $user_id,
        $donor['full_name'],
        $donor['ic_number'] ?? 'N/A',
        $donor['blood_type'] ?? 'N/A'
    ]);

    header("Location: dashboard.php?event=" . urlencode($event_get) . "&success=Slot booked successfully!"); exit;
}

// ── Cancel a booking ─────────────────────────────────────
if ($action === 'cancel') {
    $booking_id = intval($_GET['booking_id'] ?? 0);
    if (!$booking_id) { header('Location: dashboard.php'); exit; }

    // Make sure the booking belongs to this donor
    $check = $pdo->prepare("SELECT * FROM bl_bookings WHERE id = ? AND donor_id = ? AND status = 'pending'");
    $check->execute([$booking_id, $user_id]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM bl_bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        header("Location: dashboard.php?event=" . urlencode($event_get) . "&success=Booking cancelled successfully."); exit;
    } else {
        header("Location: dashboard.php?event=" . urlencode($event_get) . "&error=Unable to cancel this booking."); exit;
    }
}

header('Location: dashboard.php');
exit;
?>