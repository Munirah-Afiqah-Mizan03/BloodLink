<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM bl_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'medical_officer') {
                header('Location: attendance/dashboard.php');
            } else {
                header('Location: donor/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodLink — Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Lato', sans-serif; background: #fff5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-box { background: white; border-radius: 16px; border: 0.5px solid #f5c4c4; padding: 40px 36px; width: 380px; }
    .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; justify-content: center; }
    .logo-icon { width: 32px; height: 32px; background: #e85d75; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .logo-text { font-size: 20px; font-weight: 700; color: #c94060; }
    h2 { font-size: 17px; font-weight: 700; color: #2a2a2a; margin-bottom: 6px; text-align: center; }
    p.sub { font-size: 13px; color: #aaa; text-align: center; margin-bottom: 24px; }
    .form-group { margin-bottom: 16px; }
    label { font-size: 12px; font-weight: 600; color: #888; display: block; margin-bottom: 5px; }
    input { width: 100%; padding: 10px 14px; border: 1px solid #f5c4c4; border-radius: 8px; font-size: 13px; font-family: 'Lato', sans-serif; background: #fffafa; color: #2a2a2a; outline: none; transition: border 0.15s; }
    input:focus { border-color: #e85d75; }
    .btn { width: 100%; padding: 11px; background: #e85d75; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'Lato', sans-serif; margin-top: 8px; transition: opacity 0.15s; }
    .btn:hover { opacity: 0.88; }
    .error { background: #fce4e8; color: #c94060; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
  </style>
</head>
<body>
<div class="login-box">
  <div class="logo">
    <div class="logo-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/></svg>
    </div>
    <span class="logo-text">BloodLink</span>
  </div>
  <h2>Welcome back</h2>
  <p class="sub">Sign in to your BloodLink account</p>
  <?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter your username" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn">Sign In</button>
  </form>
</div>
</body>
</html>