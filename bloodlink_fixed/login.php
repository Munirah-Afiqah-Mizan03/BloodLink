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
            $_SESSION['blood_type']= $user['blood_type'] ?? '';
            $_SESSION['ic_number'] = $user['ic_number'] ?? '';

            if ($user['role'] === 'medical_officer') {
                header('Location: module1/officer_dashboard.php');
            } else {
                $ds = $pdo->prepare("SELECT id FROM bl_donors WHERE ic_number = ? LIMIT 1");
                $ds->execute([$user['ic_number']]);
                $donor_row = $ds->fetch();
                $_SESSION['donor_id'] = $donor_row['id'] ?? 0;

                header('Location: module1/donor_dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'access') {
    $error = 'You do not have access to that page. Please sign in with the correct account.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BloodLink — Sign in</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* ── RESET & BASE ── */
    *, *::before, *::after { box-sizing: border-box; }

    body {
      background: linear-gradient(135deg, #FEF2F2 0%, #FFFFFF 50%, #FEE2E2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
    }

    /* ── FLOATING BACKGROUND BUBBLES ── */
    .bubble {
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
      opacity: 0;
      z-index: 0;
    }
    @keyframes floatUp {
      0%   { transform: translateY(0) scale(1);    opacity: 0; }
      10%  { opacity: 1; }
      85%  { opacity: .65; }
      100% { transform: translateY(-110vh) scale(1.1); opacity: 0; }
    }

    /* ── LAYOUT ── */
    .auth-wrap {
      display: grid;
      grid-template-columns: 1fr 1fr;
      max-width: 980px;
      width: 100%;
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(15, 23, 42, .14);
      border: 1px solid var(--border);
      position: relative;
      z-index: 2;
    }

    /* ── HERO PANEL ── */
    .auth-hero {
      background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
      padding: 3rem 2.5rem;
      color: #fff;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 520px;
    }

    /* hero decorative static bubbles */
    .hero-bubble {
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
    }
    .hb1 { width: 220px; height: 220px; top: -70px;  right: -70px;  background: rgba(255,255,255,.09); }
    .hb2 { width: 140px; height: 140px; bottom: 50px; left: -40px;  background: rgba(255,255,255,.07); }
    .hb3 { width: 80px;  height: 80px;  bottom:170px; right: 24px;  background: rgba(255,255,255,.06); }
    .hb4 { width: 50px;  height: 50px;  top: 130px;  left: 32px;   background: rgba(255,255,255,.11); }
    .hb5 { width: 200px; height: 200px; bottom:-90px; right: 50px;  background: rgba(255,255,255,.05); }
    .hb6 { width: 30px;  height: 30px;  top: 220px;  right: 80px;  background: rgba(255,255,255,.14); }
    .hb7 { width: 60px;  height: 60px;  top: 60px;   left: 80px;   background: rgba(255,255,255,.06); }

    .auth-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      margin-bottom: 2rem;
    }
    .auth-logo-icon {
      width: 44px;
      height: 44px;
      background: rgba(255,255,255,.18);
      backdrop-filter: blur(12px);
      border-radius: 12px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(255,255,255,.32);
      box-shadow: 0 10px 26px rgba(15,23,42,.22);
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }
    .auth-logo-icon::before {
      content: "";
      position: absolute;
      inset: -30%;
      background: linear-gradient(120deg, rgba(255,255,255,0) 30%, rgba(255,255,255,.22) 45%, rgba(255,255,255,0) 60%);
      transform: rotate(18deg);
    }
    .auth-logo-icon svg { position: relative; filter: drop-shadow(0 2px 10px rgba(0,0,0,.25)); }
    .auth-logo span {
      font-size: 22px;
      font-weight: 850;
      letter-spacing: -.7px;
      position: relative;
      text-shadow: 0 6px 18px rgba(0,0,0,.22);
    }
    .auth-hero h1 {
      font-size: 32px;
      font-weight: 800;
      letter-spacing: -.8px;
      line-height: 1.15;
      position: relative;
      margin-bottom: 1rem;
    }
    .auth-hero p {
      font-size: 15px;
      line-height: 1.6;
      opacity: .92;
      position: relative;
      max-width: 360px;
    }
    .auth-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      position: relative;
      padding-top: 2rem;
      border-top: 1px solid rgba(255,255,255,.18);
    }
    .auth-stat strong {
      display: block;
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -.5px;
    }
    .auth-stat small {
      font-size: 11px;
      opacity: .85;
      letter-spacing: .06em;
      text-transform: uppercase;
      font-weight: 600;
    }

    /* ── FORM PANEL ── */
    .auth-form {
      padding: 3rem 2.75rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    /* bobbing bubble row above heading */
    .bubble-row {
      display: flex;
      align-items: center;
      gap: 7px;
      margin-bottom: 1.4rem;
    }
    .mini-bub {
      border-radius: 50%;
      background: rgba(220, 38, 38, .16);
      border: 1px solid rgba(220, 38, 38, .25);
      animation: bobble 2.4s ease-in-out infinite;
      flex-shrink: 0;
    }
    .mini-bub:nth-child(1) { width: 10px; height: 10px; }
    .mini-bub:nth-child(2) { width: 15px; height: 15px; animation-delay: .3s; background: rgba(220,38,38,.11); }
    .mini-bub:nth-child(3) { width:  8px; height:  8px; animation-delay: .6s; }
    .mini-bub:nth-child(4) { width: 13px; height: 13px; animation-delay: .9s; background: rgba(220,38,38,.22); }
    .mini-bub:nth-child(5) { width:  9px; height:  9px; animation-delay:1.2s; }
    .mini-bub:nth-child(6) { width: 11px; height: 11px; animation-delay:1.5s; background: rgba(220,38,38,.14); }
    @keyframes bobble {
      0%, 100% { transform: translateY(0); }
      50%       { transform: translateY(-5px); }
    }

    .auth-form h2 {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -.5px;
      color: var(--text);
      margin-bottom: 6px;
    }
    .auth-form > p.sub {
      font-size: 13.5px;
      color: var(--text-muted);
      margin-bottom: 2rem;
    }
    .auth-form label {
      display: block;
      font-size: 12.5px;
      font-weight: 500;
      color: var(--text-2);
      margin-bottom: 7px;
    }
    .auth-form .form-group { margin-bottom: 1rem; }
    .auth-form input {
      width: 100%;
      padding: 11px 14px;
      border: 1px solid var(--border2);
      border-radius: 8px;
      font-size: 14px;
      font-family: 'Inter', sans-serif;
      background: #fff;
      color: var(--text);
      outline: none;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .auth-form input:focus {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(220,38,38,.1);
    }
    .auth-btn {
      width: 100%;
      padding: 12px;
      background: var(--grad-red);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      margin-top: .5rem;
      transition: transform .18s ease, box-shadow .18s ease;
      box-shadow: var(--shadow-red);
    }
    .auth-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(220,38,38,.34); }

    .auth-error {
      background: var(--red-soft);
      color: var(--red-dark);
      border-radius: 8px;
      padding: 11px 14px;
      font-size: 13px;
      margin-bottom: 1rem;
      border: 1px solid rgba(220,38,38,.22);
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }
    .auth-alt {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 13px;
      color: var(--text-muted);
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
    }
    .auth-alt a { color: var(--red); font-weight: 600; }
    .auth-alt a:hover { text-decoration: underline; }

    /* ── RESPONSIVE ── */
    @media (max-width: 780px) {
      .auth-wrap { grid-template-columns: 1fr; max-width: 440px; }
      .auth-hero  { min-height: auto; padding: 2rem; }
      .auth-hero h1 { font-size: 24px; }
      .auth-stats { padding-top: 1.5rem; }
      .auth-form  { padding: 2rem; }
    }
  </style>
</head>
<body>

<!-- ── FLOATING BUBBLES (injected by JS) ── -->

<div class="auth-wrap">

  <!-- LEFT: HERO -->
  <div class="auth-hero">
    <!-- static decorative bubbles inside hero -->
    <div class="hero-bubble hb1"></div>
    <div class="hero-bubble hb2"></div>
    <div class="hero-bubble hb3"></div>
    <div class="hero-bubble hb4"></div>
    <div class="hero-bubble hb5"></div>
    <div class="hero-bubble hb6"></div>
    <div class="hero-bubble hb7"></div>

    <div>
      <div class="auth-logo">
        <div class="auth-logo-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M12 2C12 2 5 9.5 5 14a7 7 0 0 0 14 0C19 9.5 12 2 12 2z" fill="#fff" opacity=".95"/>
            <path d="M9 14.5a3 3 0 0 0 6 0" stroke="#DC2626" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </div>
        <span>BloodLink</span>
      </div>
      <h1>Connecting donors,<br>saving lives.</h1>
      <p>Every drop counts. Join BloodLink to register as a donor, find nearby donation drives, and manage your donation journey — all in one place.</p>
    </div>

    <div class="auth-stats">
      <div class="auth-stat">
        <strong>3+</strong>
        <small>Lives per donation</small>
      </div>
      <div class="auth-stat">
        <strong>56</strong>
        <small>Day cycle</small>
      </div>
      <div class="auth-stat">
        <strong>8</strong>
        <small>Blood types</small>
      </div>
    </div>
  </div>

  <!-- RIGHT: FORM -->
  <div class="auth-form">

    <!-- bobbing bubble row -->
    <div class="bubble-row">
      <div class="mini-bub"></div>
      <div class="mini-bub"></div>
      <div class="mini-bub"></div>
      <div class="mini-bub"></div>
      <div class="mini-bub"></div>
      <div class="mini-bub"></div>
    </div>

    <h2>Welcome back</h2>
    <p class="sub">Sign in to continue to your BloodLink account</p>

    <?php if ($error): ?>
      <div class="auth-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;margin-top:1px">
          <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
          <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username or email" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="auth-btn">Sign in</button>
    </form>

    <div class="auth-alt">
      Don't have an account? <a href="module1/register.php">Register as donor</a>
    </div>
  </div>
</div>

<script>
  // ── FLOATING BACKGROUND BUBBLES ──
  const COLORS = [
    'rgba(220,38,38,.16)',
    'rgba(185,28,28,.12)',
    'rgba(239,68,68,.10)',
    'rgba(220,38,38,.20)',
    'rgba(252,165,165,.18)',
    'rgba(254,202,202,.22)',
  ];

  function spawnBubble() {
    const b   = document.createElement('div');
    b.className = 'bubble';
    const size  = 14 + Math.random() * 60;        // 14–74 px
    const left  = Math.random() * 100;             // % across viewport
    const dur   = 7 + Math.random() * 9;           // 7–16 s
    const delay = Math.random() * 1.5;
    const color = COLORS[Math.floor(Math.random() * COLORS.length)];
    b.style.cssText = [
      `width:${size}px`,
      `height:${size}px`,
      `left:${left}%`,
      `bottom:-${size + 10}px`,
      `background:${color}`,
      `border:1px solid rgba(220,38,38,.18)`,
      `animation:floatUp ${dur}s ${delay}s linear forwards`,
    ].join(';');
    document.body.appendChild(b);
    setTimeout(() => b.remove(), (dur + delay + 0.5) * 1000);
  }

  // stagger initial burst
  for (let i = 0; i < 12; i++) {
    setTimeout(spawnBubble, i * 300);
  }
  // continuous stream
  setInterval(spawnBubble, 800);
</script>

</body>
</html>