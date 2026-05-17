<?php
// BloodLink — Module 2 DB bridge
require_once __DIR__ . '/../config.php';

// ── Security helpers (ISO 25010: Security/Reliability) ─────────
function bl_require_role(string $role): void {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ../login.php');
        exit;
    }
}

function bl_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function bl_verify_csrf(?string $token): void {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
?>
