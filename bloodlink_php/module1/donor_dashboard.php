<?php
// BloodLink — Module 1: Donor Dashboard
require_once 'auth.php';
require_role('donor');
require_once 'db.php';

$activePage = 'dashboard';
$pageTitle  = 'Dashboard';
$u = current_user();

// ── Fetch donor record ────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT d.*, u.username AS email
     FROM bl_donors d
     JOIN bl_users u ON u.ic_number = d.ic_number
     WHERE d.id = ?"
);
$stmt->bind_param("i", $u['donor_id']);
$stmt->execute();
$donor = $stmt->get_result()->fetch_assoc();

// ── Last donation date ────────────────────────────────────────
$last_don = $conn->prepare(
    "SELECT donation_date FROM bl_donation_records
     WHERE donor_id = ? ORDER BY donation_date DESC LIMIT 1"
);
$last_don->bind_param("i", $u['donor_id']);
$last_don->execute();
$last_donation_row = $last_don->get_result()->fetch_assoc();
$last_donation_date = $last_donation_row['donation_date'] ?? null;

// ── Eligibility: 56-day gap ───────────────────────────────────
$eligible = true;
$days_until_eligible = 0;
if ($last_donation_date) {
    $diff = (new DateTime())->diff(new DateTime($last_donation_date))->days;
    if ($diff < 56) {
        $eligible = false;
        $days_until_eligible = 56 - $diff;
    }
}
if ($donor['health_status'] !== 'Healthy') $eligible = false;

// ── Total donation count ──────────────────────────────────────
$cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM bl_donation_records WHERE donor_id = ?");
$cnt_stmt->bind_param("i", $u['donor_id']);
$cnt_stmt->execute();
$total_donations = $cnt_stmt->get_result()->fetch_assoc()['c'];

// ── Upcoming bookings ─────────────────────────────────────────
$bk_stmt = $conn->prepare(
    "SELECT b.*, s.event_name, s.event_date, s.start_time, s.end_time, s.event_location
     FROM bl_bookings b
     JOIN bl_slots s ON s.id = b.slot_id
     WHERE b.donor_id = ? AND b.status = 'pending'
     ORDER BY s.event_date ASC LIMIT 3"
);
$bk_stmt->bind_param("i", $u['donor_id']);
$bk_stmt->execute();
$bookings = $bk_stmt->get_result();

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <?php if ($flash): ?>
    <div class="bl-notice bl-notice-<?php echo $flash['type']; ?>" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <?php if($flash['type']==='success'): ?>
        <path d="M9 12l2 2 4-4" stroke="#7ecf93" stroke-width="2" stroke-linecap="round"/>
        <circle cx="12" cy="12" r="9" stroke="#7ecf93" stroke-width="1.5"/>
        <?php else: ?>
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
        <?php endif; ?>
      </svg>
      <p><?php echo htmlspecialchars($flash['msg']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Good morning, <?php echo htmlspecialchars($donor['first_name']); ?></h1>
        <p>Here's an overview of your donor profile and upcoming activities</p>
      </div>
      <a href="manage_profile.php" class="bl-btn bl-btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        Manage Profile
      </a>
    </div>

    <!-- Status Cards -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Blood Type</p>
        <p><?php echo htmlspecialchars($donor['blood_type']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Health Status</p>
        <p style="font-size:16px"><?php echo htmlspecialchars($donor['health_status']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Last Donation</p>
        <p style="font-size:16px">
          <?php echo $last_donation_date ? date('d M Y', strtotime($last_donation_date)) : '—'; ?>
        </p>
      </div>
      <div class="bl-stat <?php echo $eligible ? 'bl-stat-green' : 'bl-stat-grey'; ?>">
        <p>Donor Status</p>
        <p style="font-size:16px">
          <?php if ($eligible): ?>
          <span class="bl-badge bl-badge-completed">Eligible</span>
          <?php else: ?>
          <span class="bl-badge bl-badge-cancelled">Not Eligible</span>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Eligibility Notice -->
    <?php if (!$eligible && $days_until_eligible > 0): ?>
    <div class="bl-notice bl-notice-warn" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>You need to wait <strong><?php echo $days_until_eligible; ?> more day(s)</strong> before your next donation (minimum 56-day gap required).</p>
    </div>
    <?php elseif (!$eligible): ?>
    <div class="bl-notice bl-notice-warn" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>Your current health status marks you as ineligible to donate. Please update your profile once you recover.</p>
    </div>
    <?php endif; ?>

    <!-- Summary row -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

      <!-- Profile Summary -->
      <div class="bl-card">
        <div class="bl-section">
          <div class="bl-section-title" style="margin-bottom:1.25rem">
            <div class="bl-bar"></div><h3>Profile summary</h3>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php
            $rows = [
              ['Donor ID',    $donor['donor_id']],
              ['Full Name',   $donor['first_name'].' '.$donor['last_name']],
              ['IC Number',   $donor['ic_number']],
              ['Phone',       $donor['phone'] ?? '—'],
              ['Email',       $donor['email']],
              ['Total Donations', $total_donations . ' donation(s)'],
            ];
            foreach ($rows as [$label, $val]):
            ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:12.5px">
              <span style="color:var(--text-muted)"><?php echo $label; ?></span>
              <span style="font-weight:500;text-align:right;max-width:60%"><?php echo htmlspecialchars($val); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:1rem">
            <a href="manage_profile.php" class="bl-btn bl-btn-ghost" style="width:100%;justify-content:center">
              Edit Profile
            </a>
          </div>
        </div>
      </div>

      <!-- Upcoming Bookings -->
      <div class="bl-card">
        <div class="bl-section">
          <div class="bl-section-title" style="margin-bottom:1.25rem">
            <div class="bl-bar"></div><h3>Upcoming bookings</h3>
          </div>
          <?php if ($bookings->num_rows === 0): ?>
          <div class="bl-empty" style="padding:2rem">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="4" width="18" height="17" rx="2" stroke="#555" stroke-width="1.5"/>
              <path d="M3 9h18M8 2v4M16 2v4" stroke="#555" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <p>No upcoming bookings</p>
            <small>Book a slot from the Attendance page</small>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php while ($bk = $bookings->fetch_assoc()): ?>
            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px">
              <div style="font-weight:500;font-size:13px;margin-bottom:3px">
                <?php echo htmlspecialchars($bk['event_name']); ?>
              </div>
              <div style="color:var(--text-muted);font-size:11.5px">
                <?php echo date('d M Y', strtotime($bk['event_date'])); ?>
                &nbsp;·&nbsp;
                <?php echo date('H:i', strtotime($bk['start_time'])); ?>–<?php echo date('H:i', strtotime($bk['end_time'])); ?>
              </div>
              <div style="color:var(--text-muted);font-size:11.5px;margin-top:2px">
                <?php echo htmlspecialchars($bk['event_location']); ?>
              </div>
              <div style="margin-top:6px">
                <span class="bl-badge bl-badge-pending">Pending</span>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /grid -->

  </div><!-- /bl-main -->

<div class="bl-toast" id="bl-toast">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
    <circle cx="12" cy="12" r="9" fill="#7ecf93"/>
    <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
  </svg>
  <span id="bl-toast-msg"></span>
</div>

<?php include 'footer.php'; ?>
