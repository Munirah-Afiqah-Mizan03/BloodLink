<?php
// BloodLink — Module 1 Sidebar wrapper
if (!isset($_SESSION)) {
    require_once __DIR__ . '/../config.php';
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$base_url = '../';
include __DIR__ . '/../includes/sidebar.php';
