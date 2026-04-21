<?php
// BloodLink — Module 2 Sidebar wrapper
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}
$base_url = '../';
include __DIR__ . '/../includes/sidebar.php';
