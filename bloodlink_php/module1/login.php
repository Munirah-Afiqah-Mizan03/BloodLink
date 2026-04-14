<?php
// BloodLink — Module 1: Login
session_start();
require_once 'db.php';

// Already logged in → redirect to appropriate dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'donor' ? 'donor_dashboard.php' : 'officer_dashboard.php'));
    exit;
}

$error = '';

// ── HANDLE FORM SUBMIT ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM bl_users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Successful login — populate session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['blood_type']= $user['blood_type'];
            $_SESSION['ic_number'] = $user['ic_number'];

            // If donor, also fetch donor table id
            if ($user['role'] === 'donor') {
                $ds = $conn->prepare("SELECT id FROM bl_donors WHERE ic_number = ? LIMIT 1");
                $ds->bind_param("s", $user['ic_number']);
                $ds->execute();
                $donor_row = $ds->get_result()->fetch_assoc();
                $_SESSION['donor_id'] = $donor_row['id'] ?? 0;
                header('Location: donor_dashboard.php');
            } else {
                header('Location: officer_dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle  = 'Sign In';
$error_url  = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — BloodLink</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg); }
    .bl-login-wrap { width:100%; max-width:400px; padding:1rem; }
    .bl-login-card {
      background:var(--card);
      border:1px solid var(--border);
      border-radius:var(--radius);
      padding:2rem;
    }
    .bl-login-logo {
      display:flex; align-items:center; gap:10px;
      margin-bottom:1.75rem;
    }
    .bl-login-logo-icon {
      width:36px; height:36px;
      background:var(--red); border-radius:9px;
      display:grid; place-items:center;
    }
    .bl-login-logo span { font-size:17px; font-weight:700; letter-spacing:-.3px; }
    .bl-login-card h2 { font-size:18px; font-weight:700; margin-bottom:4px; }
    .bl-login-card p  { color:var(--text-muted); font-size:12.5px; margin-bottom:1.75rem; }
    .bl-login-field { margin-bottom:1rem; }
    .bl-login-field label { display:block; font-size:12px; font-weight:500; color:var(--text-muted); margin-bottom:6px; }
    .bl-login-field input {
      width:100%; background:var(--bg3); border:1px solid var(--border2);
      border-radius:var(--radius-sm); color:var(--text); font-size:13px;
      padding:9px 12px; outline:none; font-family:inherit;
      transition:border-color .15s;
    }
    .bl-login-field input:focus { border-color:var(--red); }
    .bl-login-btn {
      width:100%; padding:10px; background:var(--red); color:#fff;
      border:none; border-radius:var(--radius-sm); font-size:13px;
      font-weight:600; cursor:pointer; font-family:inherit;
      transition:opacity .15s; margin-top:.5rem;
    }
    .bl-login-btn:hover { opacity:.88; }
    .bl-login-footer { margin-top:1.25rem; text-align:center; font-size:12px; color:var(--text-muted); }
    .bl-login-footer a { color:var(--red-light); }
    .bl-login-footer a:hover { text-decoration:underline; }
    .bl-err { background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.25); color:var(--red-light);
               border-radius:var(--radius-sm); padding:9px 12px; font-size:12.5px; margin-bottom:1rem; }
  </style>
</head>
<body>
<div class="bl-login-wrap">
  <div class="bl-login-card">

    <div class="bl-login-logo">
      <div class="bl-login-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M12 2C12 2 5 9.5 5 14a7 7 0 0 0 14 0C19 9.5 12 2 12 2z" fill="#fff" opacity=".9"/>
          <path d="M9 14.5a3 3 0 0 0 6 0" stroke="#C0392B" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <span>BloodLink</span>
    </div>

    <h2>Welcome back</h2>
    <p>Sign in to your BloodLink account</p>

    <?php if ($error): ?>
    <div class="bl-err"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($error_url === 'access'): ?>
    <div class="bl-err">You do not have permission to access that page.</div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="bl-login-field">
        <label>Username</label>
        <input type="text" name="username" placeholder="e.g. donor1"
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username">
      </div>
      <div class="bl-login-field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password">
      </div>
      <button type="submit" class="bl-login-btn">Sign in</button>
    </form>

    <div class="bl-login-footer">
      Don't have an account? <a href="register.php">Register as donor</a>
    </div>

  </div>
</div>
</body>
</html>
