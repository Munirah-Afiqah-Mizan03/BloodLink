<?php
// BloodLink — Module 1 Sidebar / Page Shell
// Requires: $activePage, $pageTitle, auth.php already included + session started
require_once __DIR__ . '/auth.php';
require_role(); // any logged-in user
$u = current_user();
$initials = user_initials($u['full_name']);
$is_donor  = $u['role'] === 'donor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — BloodLink' : 'BloodLink'; ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="bl-layout">

  <aside class="bl-sidebar">
    <div class="bl-logo">
      <div class="bl-logo-icon" style="background: transparent;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="11" fill="rgba(192, 57, 43, 0.15)"/>
          <path d="M12 18.5l-1.05-1.02C7.4 14.2 5 12.05 5 9.5 5 7.4 6.6 5.8 8.5 5.8c1.08 0 2.12.5 2.8 1.3.16.2.5.45.7.45.2 0 .54-.25.7-.45C13.38 6.3 14.42 5.8 15.5 5.8 17.4 5.8 19 7.4 19 9.5c0 2.55-2.4 4.7-5.95 7.98L12 18.5z" fill="#C0392B"/>
        </svg>
      </div>
      <span style="font-size: 18px; font-weight: 700; color: #C0392B; margin-left: 2px;">BloodLink</span>
    </div>

    <nav class="bl-nav">
      <p class="bl-nav-label">Main</p>

      <?php if ($is_donor): ?>
      <a href="donor_dashboard.php" class="bl-nav-item <?php echo ($activePage==='dashboard')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        Dashboard
      </a>
      <a href="manage_profile.php" class="bl-nav-item <?php echo ($activePage==='profile')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Manage Profile
      </a>
      <a href="#" class="bl-nav-item <?php echo ($activePage==='donation_history')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Donation History
      </a>
      <a href="#" class="bl-nav-item <?php echo ($activePage==='notifications')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Notifications
      </a>

      <?php else: ?>
      <a href="officer_dashboard.php" class="bl-nav-item <?php echo ($activePage==='dashboard')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        Dashboard
      </a>
      
      <a href="#" class="bl-nav-item <?php echo ($activePage==='events')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
          <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Events
      </a>
      
      <a href="#" class="bl-nav-item <?php echo ($activePage==='records')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Donation Records
      </a>

      <a href="#" class="bl-nav-item <?php echo ($activePage==='attendance')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M16 11l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Attendance
      </a>
      <?php endif; ?>

    </nav>

    <div class="bl-sidebar-user">
      <div class="bl-user-avatar"><?php echo htmlspecialchars($initials); ?></div>
      <div class="bl-user-info">
        <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
        <small><?php echo $is_donor ? 'Donor' : 'Medical Officer'; ?></small>
      </div>
    </div>
    <a href="logout.php" style="display:flex;align-items:center;gap:8px;padding:8px 10px;color:var(--text-muted);font-size:12px;border-radius:var(--radius-sm);transition:background .15s;text-decoration:none;margin-top:4px"
       onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background=''">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Sign out
    </a>

  </aside>
