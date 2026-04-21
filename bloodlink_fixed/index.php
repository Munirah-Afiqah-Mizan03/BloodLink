<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'medical_officer') {
        header('Location: module1/officer_dashboard.php');
    } else {
        header('Location: module1/donor_dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>
