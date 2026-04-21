<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

$slot_id    = intval($_POST['slot_id'] ?? 0);
$event_id   = intval($_POST['event_id'] ?? 0);
$start_time = trim($_POST['start_time'] ?? '');
$end_time   = trim($_POST['end_time'] ?? '');
$capacity   = intval($_POST['capacity'] ?? 5);
$event_get  = trim($_POST['event_get'] ?? '');

if (!$event_id || !$start_time || !$end_time) {
    header("Location: dashboard.php?event=" . urlencode($event_get) . "&error=Please fill in all fields.");
    exit;
}

// Pull event details from events table
$event = $pdo->prepare("SELECT * FROM bl_events WHERE id = ?");
$event->execute([$event_id]);
$event = $event->fetch();

if (!$event) {
    header("Location: dashboard.php?event=" . urlencode($event_get) . "&error=Event not found.");
    exit;
}

if ($slot_id) {
    $stmt = $pdo->prepare("UPDATE bl_slots SET event_id=?, event_name=?, event_date=?, event_location=?, start_time=?, end_time=?, capacity=? WHERE id=?");
    $stmt->execute([$event_id, $event['event_name'], $event['event_date'], $event['location'], $start_time, $end_time, $capacity, $slot_id]);
    $msg = "Slot updated successfully.";
} else {
    $stmt = $pdo->prepare("INSERT INTO bl_slots (event_id, event_name, event_date, event_location, start_time, end_time, capacity) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$event_id, $event['event_name'], $event['event_date'], $event['location'], $start_time, $end_time, $capacity]);
    $msg = "Slot created successfully.";
}

header("Location: dashboard.php?event=" . urlencode($event['event_name']) . "&success=" . urlencode($msg));
exit;
?>