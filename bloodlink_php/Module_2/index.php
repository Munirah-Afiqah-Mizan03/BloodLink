<?php
// BloodLink — Module 2: Event Management List
session_start();
require_once 'db.php';

$activePage = 'events';
$pageTitle  = 'Event Management';

// ── FILTERS ──────────────────────────────────────────────────
$search    = isset($_GET['search'])   ? trim($_GET['search'])     : '';
$filter_st = isset($_GET['status'])   ? $_GET['status']           : '';
$page      = isset($_GET['page'])     ? max(1, intval($_GET['page'])) : 1;
$per_page  = 8;
$offset    = ($page - 1) * $per_page;

// ── BUILD QUERY ───────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $where   .= " AND (e.event_name LIKE ? OR e.location LIKE ? OR e.partner LIKE ?)";
    $s        = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= "sss";
}
if ($filter_st) {
    $where   .= " AND e.status = ?";
    $params[] = $filter_st;
    $types   .= "s";
}

$base_sql = "FROM events e $where";

// Total count
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total $base_sql");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows  = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Records with volunteer count
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
    COUNT(*) AS total,
    SUM(status='Upcoming')  AS upcoming,
    SUM(status='Completed') AS completed,
    SUM(status='Cancelled') AS cancelled
    FROM events")->fetch_assoc();

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <?php if ($flash): ?>
    <div class="bl-notice bl-notice-<?php echo $flash['type']; ?>" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <?php if($flash['type']==='success'): ?>
        <path d="M9 12l2 2 4-4" stroke="#7ecf93" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="#7ecf93" stroke-width="1.5"/>
        <?php else: ?>
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/><path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
        <?php endif; ?>
      </svg>
      <p><?php echo htmlspecialchars($flash['msg']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Event Management</h1>
        <p>View, manage and organise all blood donation events</p>
      </div>
      <a href="add_event.php" class="bl-btn bl-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Add new event
      </a>
    </div>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Total Events</p>
        <p><?php echo number_format($stats['total']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Upcoming</p>
        <p><?php echo number_format($stats['upcoming']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
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
          <div class="bl-search-wrap">
            <svg class="bl-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" stroke="#ccc" stroke-width="1.5"/>
              <path d="M16.5 16.5L21 21" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <input class="bl-input" type="text" name="search"
                   placeholder="Search by event name, location or partner..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <select class="bl-select" name="status">
            <option value="">All statuses</option>
            <?php foreach(['Upcoming','Completed','Cancelled'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo ($filter_st===$st)?'selected':''; ?>>
              <?php echo $st; ?>
            </option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="bl-btn bl-btn-primary" style="white-space:nowrap">Search</button>
          <a href="index.php" class="bl-btn bl-btn-ghost">Reset</a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="bl-card">
      <div class="bl-table-wrap">
        <table class="bl-table">
          <thead>
            <tr>
              <th>Event ID</th>
              <th>Event name</th>
              <th>Date</th>
              <th>Location</th>
              <th>Partner</th>
              <th>Status</th>
              <th>Volunteers</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($events->num_rows === 0): ?>
            <tr>
              <td colspan="8">
                <div class="bl-empty">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="4" width="18" height="17" rx="2" stroke="#555" stroke-width="1.5"/>
                    <path d="M3 9h18M8 2v4M16 2v4" stroke="#555" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  <p>No events found</p>
                  <small>Try adjusting your search or filter</small>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php while ($row = $events->fetch_assoc()): ?>
            <?php
              $initials = strtoupper(substr($row['event_name'], 0, 2));
              $st_class = [
                'Upcoming'  => 'bl-badge-upcoming',
                'Completed' => 'bl-badge-completed',
                'Cancelled' => 'bl-badge-cancelled',
              ][$row['status']] ?? 'bl-badge-pending';
            ?>
            <tr>
              <td><span class="bl-record-id">#<?php echo htmlspecialchars($row['event_id']); ?></span></td>
              <td>
                <div class="bl-event-cell">
                  <div class="bl-avatar bl-avatar-ev"><?php echo $initials; ?></div>
                  <div>
                    <div style="font-weight:500"><?php echo htmlspecialchars($row['event_name']); ?></div>
                    <?php if($row['description']): ?>
                    <div class="sub" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                      <?php echo htmlspecialchars(substr($row['description'], 0, 60)); ?>...
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="color:#aaa;font-size:12px;white-space:nowrap">
                <?php echo date('d M Y', strtotime($row['event_date'])); ?>
              </td>
              <td style="color:#aaa;font-size:12px;max-width:160px">
                <?php echo htmlspecialchars($row['location']); ?>
              </td>
              <td style="color:#aaa;font-size:12px">
                <?php echo $row['partner'] ? htmlspecialchars($row['partner']) : '<span style="color:#444">—</span>'; ?>
              </td>
              <td>
                <span class="bl-badge <?php echo $st_class; ?>"><?php echo $row['status']; ?></span>
              </td>
              <td>
                <div class="bl-vol-count">
                  <span class="bl-vol-count-dot"><?php echo $row['vol_count']; ?></span>
                  <span><?php echo $row['vol_count'] == 1 ? 'volunteer' : 'volunteers'; ?></span>
                </div>
              </td>
              <td>
                <div class="bl-action-group">
                  <a href="edit_event.php?id=<?php echo $row['id']; ?>"
                     class="bl-btn bl-btn-sm bl-btn-edit">Edit</a>
                  <a href="assign_volunteers.php?id=<?php echo $row['id']; ?>"
                     class="bl-btn bl-btn-sm bl-btn-vol">Volunteers</a>
                  <a href="delete_event.php?id=<?php echo $row['id']; ?>"
                     class="bl-btn bl-btn-sm bl-btn-del">Delete</a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
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

  </div><!-- /bl-main -->

<div class="bl-toast" id="bl-toast">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
    <circle cx="12" cy="12" r="9" fill="#7ecf93"/>
    <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
  </svg>
  <span id="bl-toast-msg"></span>
</div>

<?php include 'footer.php'; ?>
