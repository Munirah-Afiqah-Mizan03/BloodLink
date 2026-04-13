<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'medical_officer') {
        header('Location: Module_2/index.php');
    } else {
        header('Location: donor/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>