<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

$slot_id   = intval($_GET['id'] ?? 0);
$event_get = trim($_GET['event'] ?? '');

if ($slot_id) {
    $stmt = $pdo->prepare("DELETE FROM bl_slots WHERE id = ?");
    $stmt->execute([$slot_id]);
}

header("Location: dashboard.php?event=" . urlencode($event_get) . "&success=Slot deleted successfully.");
exit;
?>