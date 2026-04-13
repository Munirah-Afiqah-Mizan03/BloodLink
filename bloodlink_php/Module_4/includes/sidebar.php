<?php
// BloodLink - Sidebar Partial
// Include this at the top of every page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' — BloodLink' : 'BloodLink'; ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="bl-root">

  <!-- SIDEBAR -->
  <aside class="bl-sidebar">
    <a class="bl-logo" href="index.php">
      <div class="bl-logo-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 21C6.5 15.6 2 12 2 8.5C2 5.4 4.4 3 7.5 3C9.2 3 10.8 3.8 12 5C13.2 3.8 14.8 3 16.5 3C19.6 3 22 5.4 22 8.5C22 12 17.5 15.6 12 21Z" fill="#E57373"/>
          <rect x="11" y="7" width="2" height="7" rx="1" fill="white"/>
          <rect x="8.5" y="9.5" width="7" height="2" rx="1" fill="white"/>
        </svg>
      </div>
      <div class="bl-logo-text">
        <strong>BloodLink</strong>
        <span>Donation Management</span>
      </div>
    </a>

    <p class="bl-nav-label">Medical Officer</p>

    <nav class="bl-nav">
      <a class="bl-nav-item <?php echo ($activePage==='dashboard') ? 'active' : ''; ?>" href="dashboard.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor" opacity=".5"/>
          <rect x="14" y="3" width="7" height="7" rx="1" fill="currentColor" opacity=".5"/>
          <rect x="3" y="14" width="7" height="7" rx="1" fill="currentColor" opacity=".5"/>
          <rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor" opacity=".5"/>
        </svg>
        Dashboard
      </a>
      <a class="bl-nav-item <?php echo ($activePage==='events') ? 'active' : ''; ?>" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="4" width="18" height="17" rx="2" stroke="currentColor" stroke-width="1.5"/>
          <path d="M8 2v4M16 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Events
      </a>
      <a class="bl-nav-item <?php echo ($activePage==='records') ? 'active' : ''; ?>" href="index.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M17 20H7a2 2 0 01-2-2V6a2 2 0 012-2h7l5 5v9a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.5"/>
          <path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Donation Records
      </a>
      <a class="bl-nav-item <?php echo ($activePage==='attendance') ? 'active' : ''; ?>" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Attendance
      </a>
      <a class="bl-nav-item <?php echo ($activePage==='volunteers') ? 'active' : ''; ?>" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Volunteers
      </a>
    </nav>

    <div class="bl-nav-logout">
      <a class="bl-nav-item" href="logout.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
          <path d="M17 16l4-4m0 0l-4-4m4 4H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <rect x="3" y="4" width="8" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
        </svg>
        Logout
      </a>
    </div>
  </aside>
