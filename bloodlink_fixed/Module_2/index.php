<?php
// BloodLink — Module 2: Event Management (Main List)
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php');
    exit;
}

$activePage = 'events';
$pageTitle  = 'Event Management';
$base_url   = '../';

// ── FILTERS ──────────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$filter_st  = $_GET['status'] ?? '';
$page       = max(1, intval($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $s = "%$search%";
    $where   .= " AND (e.event_name LIKE ? OR e.event_id LIKE ? OR e.location LIKE ? OR e.partner LIKE ?)";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= "ssss";
}
if ($filter_st) {
    $where   .= " AND e.status = ?";
    $params[] = $filter_st;
    $types   .= "s";
}

$base_sql = "FROM bl_events e $where";

// Total count
$cnt = $conn->prepare("SELECT COUNT(*) AS total $base_sql");
if ($types) $cnt->bind_param($types, ...$params);
$cnt->execute();
$total_rows  = $cnt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

// Rows with volunteer count
$sql = "SELECT e.*,
    (SELECT COUNT(*) FROM event_volunteers ev WHERE ev.event_id = e.id) AS vol_count
    $base_sql
    ORDER BY e.event_date DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params = array_merge($params, [$per_page, $offset]);
$all_types  = $types . "ii";
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$events = $stmt->get_result();

// Stats
$stats = $conn->query("SELECT
    COUNT(*)                                 AS total,
    SUM(status='Upcoming')                   AS upcoming,
    SUM(status='Completed')                  AS completed,
    SUM(status='Cancelled')                  AS cancelled
    FROM bl_events")->fetch_assoc();

// Flash message from add/edit/delete
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="bl-main">

    <?php if ($flash): ?>
    <div class="bl-notice bl-notice-<?php echo $flash['type']==='success'?'success':'warn'; ?>" style="margin-bottom:1.25rem">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;margin-top:1px">
        <?php if ($flash['type']==='success'): ?>
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <?php else: ?>
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
        <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        <?php endif; ?>
      </svg>
      <p><?php echo htmlspecialchars($flash['msg']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Hero (match Upcoming Events layout) -->
    <div class="bl-event-hero">
      <h2>Manage blood donation events</h2>
      <p>Create, update, and monitor events. Assign volunteers, track status, and keep schedules up to date.</p>
    </div>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Total Events</p>
        <p><?php echo number_format($stats['total']); ?></p>
      </div>
      <div class="bl-stat bl-stat-blue">
        <p>Upcoming</p>
        <p><?php echo number_format($stats['upcoming']); ?></p>
      </div>
      <div class="bl-stat bl-stat-green">
        <p>Completed</p>
        <p><?php echo number_format($stats['completed']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Cancelled</p>
        <p><?php echo number_format($stats['cancelled']); ?></p>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="bl-card">
      <form method="GET" action="index.php">
        <div class="bl-search-bar">
          <div style="flex:1;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <div class="bl-search-wrap" style="flex:1;min-width:240px">
              <svg class="bl-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/>
                <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
              <input class="bl-input" type="text" name="search"
                     placeholder="Search by event name, ID, location, or partner..."
                     value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <select class="bl-select" name="status">
              <option value="">All statuses</option>
              <?php foreach(['Upcoming','Completed','Cancelled'] as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo ($filter_st===$s)?'selected':''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="bl-btn bl-btn-primary">Search</button>
            <a href="index.php" class="bl-btn bl-btn-ghost">Reset</a>
          </div>

          <a href="add_event.php" class="bl-btn bl-btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Add Event
          </a>
        </div>
      </form>
    </div>

    <!-- Events Grid (match Upcoming Events layout) -->
    <?php if ($events->num_rows === 0): ?>
      <div class="bl-card">
        <div class="bl-empty">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/>
            <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
          <p>No events found</p>
          <small>Try adjusting your search or create a new event</small>
        </div>
      </div>
    <?php else: ?>
      <div class="bl-event-grid">
        <?php while ($row = $events->fetch_assoc()):
          $d       = strtotime($row['event_date']);
          $day_num = date('d', $d);
          $mon     = date('M', $d);
          $weekday = date('l', $d);
          $days_until = (strtotime($row['event_date']) - strtotime(date('Y-m-d'))) / 86400;
          $is_soon = ($days_until >= 0 && $days_until <= 7);
          $st_class = [
              'Upcoming'  => 'bl-badge-upcoming',
              'Completed' => 'bl-badge-completed',
              'Cancelled' => 'bl-badge-cancelled',
          ][$row['status']] ?? 'bl-badge-pending';
        ?>
          <div class="bl-event-card">
            <div class="bl-event-card-banner">
              <div class="bl-event-card-date">
                <span class="day"><?php echo $day_num; ?></span>
                <span class="mon"><?php echo $mon; ?></span>
              </div>

              <span style="position:absolute;bottom:10px;left:10px">
                <span class="bl-badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
              </span>

              <?php if ($is_soon && $row['status']==='Upcoming'): ?>
                <span style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,.22);backdrop-filter:blur(6px);color:#fff;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;letter-spacing:.04em;text-transform:uppercase">
                  Soon
                </span>
              <?php endif; ?>
            </div>

            <div class="bl-event-card-body">
              <h3><?php echo htmlspecialchars($row['event_name']); ?></h3>
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
                  <?php echo htmlspecialchars($row['location'] ?: 'TBA'); ?>
                </div>
                <div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                  <?php echo intval($row['vol_count']); ?> volunteer<?php echo intval($row['vol_count'])===1?'':'s'; ?>
                </div>
                <div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <path d="M9 11l3 3 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                  ID: <?php echo htmlspecialchars($row['event_id']); ?>
                </div>
              </div>

              <?php if (!empty($row['description'])): ?>
                <p class="bl-event-card-desc"><?php echo htmlspecialchars($row['description']); ?></p>
              <?php endif; ?>
            </div>

            <div class="bl-event-card-footer" style="gap:.75rem;align-items:center;justify-content:space-between">
              <div class="bl-event-card-partner">
                <?php if (!empty($row['partner'])): ?>
                  <strong><?php echo htmlspecialchars($row['partner']); ?></strong>
                <?php else: ?>
                  <span style="color:var(--text-dim)">BloodLink</span>
                <?php endif; ?>
              </div>
              <div class="bl-action-group">
                <a href="edit_event.php?id=<?php echo $row['id']; ?>" class="bl-btn bl-btn-sm bl-btn-edit" title="Edit">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Edit
                </a>
                <a href="assign_volunteers.php?id=<?php echo $row['id']; ?>" class="bl-btn bl-btn-sm bl-btn-vol" title="Assign volunteers">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                  </svg>
                  Volunteers
                </a>
                <a href="delete_event.php?id=<?php echo $row['id']; ?>" class="bl-btn bl-btn-sm bl-btn-del" title="Delete" onclick="return confirm('Delete this event?');">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="bl-card" style="margin-top:1rem">
        <div class="bl-pagination">
          <span>Showing <?php echo min($offset+1,$total_rows); ?>–<?php echo min($offset+$per_page,$total_rows); ?> of <?php echo number_format($total_rows); ?> events</span>
          <div class="bl-page-btns">
            <?php
            $qs = http_build_query(array_merge($_GET, ['page'=>$page-1]));
            echo "<a href='index.php?$qs' class='bl-page-btn" . ($page<=1?' disabled':'') . "'>Prev</a>";
            for ($p=1; $p<=$total_pages; $p++) {
              $qs = http_build_query(array_merge($_GET, ['page'=>$p]));
              echo "<a href='index.php?$qs' class='bl-page-btn" . ($p==$page?' active':'') . "'>$p</a>";
            }
            $qs = http_build_query(array_merge($_GET, ['page'=>$page+1]));
            echo "<a href='index.php?$qs' class='bl-page-btn" . ($page>=$total_pages?' disabled':'') . "'>Next</a>";
            ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div><!-- /bl-main -->

<?php include 'footer.php'; ?>
