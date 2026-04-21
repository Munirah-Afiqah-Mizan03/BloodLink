<?php
// BloodLink — Unified Sidebar
// Requires: $activePage, $pageTitle set before including
// Requires: session already started via config.php
// Requires: $base_url set before including (e.g. '../')

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ($base_url ?? '../') . 'login.php');
    exit;
}

$u_full_name = $_SESSION['full_name'] ?? '';
$u_role      = $_SESSION['role'] ?? '';
$is_donor    = $u_role === 'donor';

$parts = explode(' ', trim($u_full_name));
$initials = strtoupper(substr($parts[0] ?? '', 0, 1));
if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
if (!$initials) $initials = '?';

if (!isset($base_url)) $base_url = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — BloodLink' : 'BloodLink'; ?></title>
  <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
</head>
<body>

<div class="bl-layout">

  <aside class="bl-sidebar">
    <div class="bl-logo">
      <div class="bl-logo-row">
        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
          <div class="bl-logo-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M12 2C12 2 5 9.5 5 14a7 7 0 0 0 14 0C19 9.5 12 2 12 2z" fill="#fff" opacity=".95"/>
              <path d="M9 14.5a3 3 0 0 0 6 0" stroke="#DC2626" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </div>
          <span>BloodLink</span>
        </div>

        <button class="bl-sidebar-toggle" type="button" aria-label="Toggle sidebar" aria-expanded="true">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </div>

    <nav class="bl-nav">
      <p class="bl-nav-label">Main</p>

      <?php if ($is_donor): ?>
      <a href="<?php echo $base_url; ?>module1/donor_dashboard.php" class="bl-nav-item <?php echo ($activePage==='dashboard')?'active':''; ?>" title="Dashboard">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
          <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
          <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
          <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
        </svg>
        <span class="bl-nav-text">Dashboard</span>
      </a>
      <a href="<?php echo $base_url; ?>Module_2/upcoming_events.php" class="bl-nav-item <?php echo ($activePage==='upcoming_events')?'active':''; ?>" title="Upcoming Events">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/>
          <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          <circle cx="12" cy="15" r="1.5" fill="currentColor"/>
        </svg>
        <span class="bl-nav-text">Upcoming Events</span>
      </a>
      <a href="<?php echo $base_url; ?>donor/dashboard.php" class="bl-nav-item <?php echo ($activePage==='booking')?'active':''; ?>" title="Book Donation">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M9 11l3 3 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">Book Donation</span>
      </a>
      <a href="<?php echo $base_url; ?>module1/manage_profile.php" class="bl-nav-item <?php echo ($activePage==='profile')?'active':''; ?>" title="My Profile">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">My Profile</span>
      </a>

      <?php else: /* Medical Officer */ ?>
      <a href="<?php echo $base_url; ?>module1/officer_dashboard.php" class="bl-nav-item <?php echo ($activePage==='donors')?'active':''; ?>" title="Donors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">Donors</span>
      </a>
      <a href="<?php echo $base_url; ?>Module_2/index.php" class="bl-nav-item <?php echo ($activePage==='events')?'active':''; ?>" title="Events">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/>
          <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">Events</span>
      </a>
      <a href="<?php echo $base_url; ?>Module_3/dashboard.php" class="bl-nav-item <?php echo ($activePage==='attendance')?'active':''; ?>" title="Attendance">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="1.6"/>
          <path d="M16 11l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">Attendance</span>
      </a>
      <a href="<?php echo $base_url; ?>Module_4/index.php" class="bl-nav-item <?php echo ($activePage==='records')?'active':''; ?>" title="Donation Records">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
          <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span class="bl-nav-text">Donation Records</span>
      </a>
      <?php endif; ?>

    </nav>

    <div class="bl-sidebar-user">
      <div class="bl-user-avatar"><?php echo htmlspecialchars($initials); ?></div>
      <div class="bl-user-info">
        <strong><?php echo htmlspecialchars($u_full_name); ?></strong>
        <small><?php echo $is_donor ? 'Donor' : 'Medical Officer'; ?></small>
      </div>
    </div>
    <a href="<?php echo $base_url; ?>logout.php" class="bl-signout" title="Sign out">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Sign out</span>
    </a>

    <script>
      (function () {
        var layout = document.querySelector(".bl-layout");
        var toggle = document.querySelector(".bl-sidebar-toggle");
        if (!layout || !toggle) return;

        var storageKey = "bl_sidebar_collapsed";

        function setCollapsed(collapsed) {
          layout.classList.toggle("bl-layout--sidebar-collapsed", collapsed);
          toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
          var iconPath = toggle.querySelector("path");
          if (iconPath) {
            iconPath.setAttribute("d", collapsed ? "M9 6l6 6-6 6" : "M15 18l-6-6 6-6");
          }
          try {
            localStorage.setItem(storageKey, collapsed ? "1" : "0");
          } catch (e) {}
        }

        var initial = false;
        try {
          initial = localStorage.getItem(storageKey) === "1";
        } catch (e) {}
        setCollapsed(initial);

        toggle.addEventListener("click", function () {
          setCollapsed(!layout.classList.contains("bl-layout--sidebar-collapsed"));
        });
      })();
    </script>

  </aside>
