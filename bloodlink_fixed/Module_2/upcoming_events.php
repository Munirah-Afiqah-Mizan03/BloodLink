<?php
// BloodLink — Module 2: Upcoming Events (Donor View) — NEW FEATURE
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: ../login.php');
    exit;
}

$activePage = 'upcoming_events';
$pageTitle  = 'Upcoming Events';
$user_id    = $_SESSION['user_id'];

// ── Filter ────────────────────────────────────────────────────
$filter_month = $_GET['month'] ?? '';

// ── Fetch upcoming events (today and after, status Upcoming) ──
$today = date('Y-m-d');
$sql = "SELECT e.*,
               (SELECT COUNT(*) FROM bl_slots s WHERE s.event_id = e.id) AS slot_count,
               (SELECT COUNT(*) FROM bl_slots s
                LEFT JOIN bl_bookings b ON b.slot_id = s.id
                WHERE s.event_id = e.id) AS total_bookings
        FROM bl_events e
        WHERE e.status = 'Upcoming' AND e.event_date >= ?";
$params = [$today];
$types  = "s";

if ($filter_month) {
    $sql     .= " AND DATE_FORMAT(e.event_date, '%Y-%m') = ?";
    $params[] = $filter_month;
    $types   .= "s";
}
$sql .= " ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$upcoming = $stmt->get_result();

// Fetch distinct months for filter dropdown (upcoming only)
$months_res = $conn->prepare("
    SELECT DISTINCT DATE_FORMAT(event_date,'%Y-%m') AS ym,
                    DATE_FORMAT(event_date,'%M %Y') AS label
    FROM bl_events
    WHERE status = 'Upcoming' AND event_date >= ?
    ORDER BY ym ASC
");
$months_res->bind_param("s", $today);
$months_res->execute();
$months_list = $months_res->get_result();

// Count totals for stats
$stats = $conn->prepare("SELECT
    COUNT(*)                                               AS total,
    SUM(event_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY))  AS this_week,
    SUM(event_date BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)) AS this_month
    FROM bl_events
    WHERE status = 'Upcoming' AND event_date >= ?");
$stats->bind_param("sssss", $today, $today, $today, $today, $today);
$stats->execute();
$s = $stats->get_result()->fetch_assoc();
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Hero -->
    <div class="bl-event-hero">
      <h2>Find your next donation opportunity</h2>
      <p>Browse upcoming blood donation events happening near you. Every donation can save up to three lives — thank you for being part of BloodLink.</p>
    </div>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Upcoming Events</p>
        <p><?php echo number_format($s['total'] ?? 0); ?></p>
      </div>
      <div class="bl-stat bl-stat-blue">
        <p>This Week</p>
        <p><?php echo number_format($s['this_week'] ?? 0); ?></p>
      </div>
      <div class="bl-stat bl-stat-green">
        <p>Next 30 Days</p>
        <p><?php echo number_format($s['this_month'] ?? 0); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Your Blood Type</p>
        <p style="font-size:22px"><?php echo htmlspecialchars($_SESSION['blood_type'] ?: '—'); ?></p>
      </div>
    </div>

    <!-- Filter -->
    <div class="bl-card">
      <form method="GET" action="upcoming_events.php">
        <div class="bl-search-bar">
          <div style="flex:1;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <span style="font-size:13px;color:var(--text-muted);font-weight:500">Filter by month:</span>
            <select class="bl-select" name="month" onchange="this.form.submit()">
              <option value="">All upcoming months</option>
              <?php while ($m = $months_list->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($m['ym']); ?>" <?php echo ($filter_month===$m['ym'])?'selected':''; ?>>
                  <?php echo htmlspecialchars($m['label']); ?>
                </option>
              <?php endwhile; ?>
            </select>
            <?php if ($filter_month): ?>
              <a href="upcoming_events.php" class="bl-btn bl-btn-ghost bl-btn-sm">Clear filter</a>
            <?php endif; ?>
          </div>
          <a href="<?php echo '../donor/dashboard.php'; ?>" class="bl-btn bl-btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <path d="M9 11l3 3 8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            Book a slot
          </a>
        </div>
      </form>
    </div>

    <!-- Events Grid -->
    <?php if ($upcoming->num_rows === 0): ?>
      <div class="bl-card">
        <div class="bl-empty">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/>
            <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
          <p>No upcoming events</p>
          <small>Check back soon — new events are added regularly</small>
        </div>
      </div>
    <?php else: ?>
      <div class="bl-event-grid">
        <?php while ($ev = $upcoming->fetch_assoc()):
          $d       = strtotime($ev['event_date']);
          $day_num = date('d', $d);
          $mon     = date('M', $d);
          $weekday = date('l', $d);
          $is_soon = (strtotime($ev['event_date']) - strtotime($today)) / 86400 <= 7;
        ?>
          <div class="bl-event-card">
            <div class="bl-event-card-banner">
              <div class="bl-event-card-date">
                <span class="day"><?php echo $day_num; ?></span>
                <span class="mon"><?php echo $mon; ?></span>
              </div>
              <?php if ($is_soon): ?>
                <span style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.22);backdrop-filter:blur(6px);color:#fff;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;letter-spacing:.04em;text-transform:uppercase">
                  Soon
                </span>
              <?php endif; ?>
            </div>

            <div class="bl-event-card-body">
              <h3><?php echo htmlspecialchars($ev['event_name']); ?></h3>
              <div class="bl-event-card-meta">
                <div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                  <?php echo $weekday . ', ' . date('d M Y', $d); ?>
                </div>
                <div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <path d="M12 22s-8-7.5-8-13a8 8 0 0 1 16 0c0 5.5-8 13-8 13z" stroke="currentColor" stroke-width="1.6"/>
                    <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.6"/>
                  </svg>
                  <?php echo htmlspecialchars($ev['location'] ?: 'TBA'); ?>
                </div>
                <?php if ($ev['slot_count'] > 0): ?>
                <div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <path d="M9 11l3 3 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                  <?php echo $ev['slot_count']; ?> slot<?php echo $ev['slot_count']>1?'s':''; ?> available
                </div>
                <?php endif; ?>
              </div>

              <?php if (!empty($ev['description'])): ?>
                <p class="bl-event-card-desc"><?php echo htmlspecialchars($ev['description']); ?></p>
              <?php endif; ?>
            </div>

            <div class="bl-event-card-footer">
              <div class="bl-event-card-partner">
                <?php if (!empty($ev['partner'])): ?>
                  <strong><?php echo htmlspecialchars($ev['partner']); ?></strong>
                <?php else: ?>
                  <span style="color:var(--text-dim)">BloodLink</span>
                <?php endif; ?>
              </div>
              <?php if ($ev['slot_count'] > 0): ?>
                <a href="<?php echo '../donor/dashboard.php?event=' . urlencode($ev['event_name']); ?>" class="bl-btn bl-btn-sm bl-btn-primary">
                  Book slot
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              <?php else: ?>
                <span class="bl-badge bl-badge-pending" title="Slots will be opened soon">Slots pending</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

  </div><!-- /bl-main -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
