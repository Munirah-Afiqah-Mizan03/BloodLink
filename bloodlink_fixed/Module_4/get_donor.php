<?php
// BloodLink — AJAX endpoint: get donor by donor_id code
require_once 'includes/db.php';
header('Content-Type: application/json');

$donor_code = trim($_GET['donor_id'] ?? '');
if (!$donor_code) { echo json_encode(['found'=>false]); exit; }

$stmt = $conn->prepare("SELECT donor_id, first_name, last_name, ic_number, phone, blood_type, created_at
    FROM bl_donors WHERE donor_id = ?");
$stmt->bind_param("s", $donor_code);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    // Get last donation date
    $last_stmt = $conn->prepare("SELECT MAX(donation_date) AS last_date FROM bl_donation_records dr
        JOIN bl_donors d ON dr.donor_id=d.id WHERE d.donor_id=?");
    $last_stmt->bind_param("s", $donor_code);
    $last_stmt->execute();
    $last = $last_stmt->get_result()->fetch_assoc();

    echo json_encode([
        'found'      => true,
        'name'       => $row['first_name'] . ' ' . $row['last_name'],
        'initials'   => strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1)),
        'ic'         => $row['ic_number'],
        'phone'      => $row['phone'],
        'blood_type' => $row['blood_type'],
        'last_donation' => $last['last_date'] ? date('d M Y', strtotime($last['last_date'])) : 'No record',
    ]);
} else {
    echo json_encode(['found'=>false]);
}
