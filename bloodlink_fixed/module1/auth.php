<?php
// BloodLink — Auth Helper
require_once __DIR__ . '/../config.php';

function require_role(string $role = '') {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/../login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/../login.php?error=access');
        exit;
    }
}

function current_user(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? 0,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'blood_type'=> $_SESSION['blood_type']?? '',
        'ic_number' => $_SESSION['ic_number'] ?? '',
        'donor_id'  => $_SESSION['donor_id']  ?? 0,
    ];
}

function user_initials(string $name): string {
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    return $i;
}
