<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — BloodLink' : 'BloodLink'; ?></title>
  <link rel="stylesheet" href="/SQA Project/ProjectDraft/module2/style.css">
</head>
<body>

<div class="bl-layout">

  <!-- SIDEBAR -->
  <aside class="bl-sidebar">
    <div class="bl-logo">
      <div class="bl-logo-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M12 2C12 2 5 9.5 5 14a7 7 0 0 0 14 0C19 9.5 12 2 12 2z" fill="#fff" opacity=".9"/>
          <path d="M9 14.5a3 3 0 0 0 6 0" stroke="#C0392B" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </div>
      <span>BloodLink</span>
    </div>

    <nav class="bl-nav">
      <p class="bl-nav-label">Main</p>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='dashboard')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>
        Dashboard
      </a>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='donors')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Donors
      </a>
      <a href="index.php" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='events')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="17" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 9h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Events
      </a>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='records')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Donation Records
      </a>

      <p class="bl-nav-label" style="margin-top:1.5rem">Management</p>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='volunteers')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 11l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Volunteers
      </a>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='reports')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M18 20V10M12 20V4M6 20v-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Reports
      </a>
      <a href="#" class="bl-nav-item <?php echo (isset($activePage) && $activePage==='settings')?'active':''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Settings
      </a>
    </nav>

    <div class="bl-sidebar-user">
      <div class="bl-user-avatar">SA</div>
      <div class="bl-user-info">
        <strong>Dr. Siti Aminah</strong>
        <small>Medical Officer</small>
      </div>
    </div>
  </aside>
