<?php
// BloodLink — Module 4: Donation Records List
require_once 'includes/db.php';

$activePage = 'records';
$pageTitle  = 'Donation Records';

// ── FILTERS ──────────────────────────────────────────────────
$search     = isset($_GET['search'])     ? trim($_GET['search'])     : '';
$filter_bt  = isset($_GET['blood_type']) ? $_GET['blood_type']       : '';
$filter_ev  = isset($_GET['event_id'])   ? intval($_GET['event_id']) : 0;
$filter_mo  = isset($_GET['month'])      ? $_GET['month']            : '';
$page       = isset($_GET['page'])       ? max(1, intval($_GET['page'])) : 1;
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

// ── BUILD QUERY ───────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $where   .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR d.donor_id LIKE ?)";
    $s        = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= "sss";
}
if ($filter_bt) {
    $where   .= " AND dr.blood_type = ?";
    $params[] = $filter_bt;
    $types   .= "s";
}
if ($filter_ev) {
    $where   .= " AND dr.event_id = ?";
    $params[] = $filter_ev;
    $types   .= "i";
}
if ($filter_mo) {
    $where   .= " AND DATE_FORMAT(dr.donation_date, '%Y-%m') = ?";
    $params[] = $filter_mo;
    $types   .= "s";
}

$base_sql = "FROM donation_records dr
             JOIN donors d ON dr.donor_id = d.id
             JOIN events e ON dr.event_id = e.id
             $where";

// Total count
$count_sql  = "SELECT COUNT(*) AS total $base_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Records
$sql  = "SELECT dr.*, d.donor_id AS donor_code,
                CONCAT(d.first_name,' ',d.last_name) AS donor_name,
                CONCAT(LEFT(d.first_name,1), LEFT(d.last_name,1)) AS initials,
                e.event_name, e.event_date
         $base_sql
         ORDER BY dr.created_at DESC
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params   = array_merge($params, [$per_page, $offset]);
$all_types    = $types . "ii";
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$records = $stmt->get_result();

// Stats
$stats = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(MONTH(donation_date)=MONTH(NOW()) AND YEAR(donation_date)=YEAR(NOW())) AS this_month,
    COUNT(DISTINCT donor_id) AS unique_donors,
    ROUND(AVG(volume_ml)) AS avg_volume
    FROM donation_records")->fetch_assoc();

// Events for filter dropdown
$events = $conn->query("SELECT id, event_name FROM events ORDER BY event_date DESC");

// Success/error flash messages
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
session_start();
unset($_SESSION['flash']);
?>
<?php include 'includes/sidebar.php'; ?>

  <!-- MAIN -->
  <div class="bl-main">

    <?php if ($flash): ?>
    <div class="bl-notice bl-notice-<?php echo $flash['type']; ?>" style="margin-bottom:1.25rem">
      <p><?php echo htmlspecialchars($flash['msg']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Donation Records</h1>
        <p>View, search and manage all donation records</p>
      </div>
      <a href="add_record.php" class="bl-btn bl-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
          <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Add new record
      </a>
    </div>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Total Records</p>
        <p><?php echo number_format($stats['total']); ?></p>
      </div>
      <div class="bl-stat bl-stat-red">
        <p>This Month</p>
        <p><?php echo number_format($stats['this_month']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Unique Donors</p>
        <p><?php echo number_format($stats['unique_donors']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Avg Volume (ml)</p>
        <p><?php echo number_format($stats['avg_volume']); ?></p>
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
                   placeholder="Search by donor name or ID..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <select class="bl-select" name="blood_type">
            <option value="">All blood types</option>
            <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
            <option value="<?php echo $bt; ?>" <?php echo ($filter_bt===$bt)?'selected':''; ?>><?php echo $bt; ?></option>
            <?php endforeach; ?>
          </select>

          <select class="bl-select" name="event_id">
            <option value="">All events</option>
            <?php $events->data_seek(0); while($ev=$events->fetch_assoc()): ?>
            <option value="<?php echo $ev['id']; ?>" <?php echo ($filter_ev==$ev['id'])?'selected':''; ?>>
              <?php echo htmlspecialchars($ev['event_name']); ?>
            </option>
            <?php endwhile; ?>
          </select>

          <input class="bl-input" type="month" name="month" style="width:150px"
                 value="<?php echo htmlspecialchars($filter_mo); ?>">

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
              <th>Record ID</th>
              <th>Donor name</th>
              <th>Event</th>
              <th>Blood type</th>
              <th>Volume (ml)</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($records->num_rows === 0): ?>
            <tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa">No records found.</td></tr>
            <?php else: ?>
            <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
              <td><span class="bl-record-id">#<?php echo htmlspecialchars($row['record_id']); ?></span></td>
              <td>
                <div class="bl-donor-cell">
                  <div class="bl-avatar"><?php echo htmlspecialchars($row['initials']); ?></div>
                  <div>
                    <div><?php echo htmlspecialchars($row['donor_name']); ?></div>
                    <div class="sub"><?php echo htmlspecialchars($row['donor_code']); ?></div>
                  </div>
                </div>
              </td>
              <td style="color:#aaa;font-size:12px"><?php echo htmlspecialchars($row['event_name']); ?></td>
              <td><span class="bl-badge bl-badge-blood"><?php echo htmlspecialchars($row['blood_type']); ?></span></td>
              <td><?php echo htmlspecialchars($row['volume_ml']); ?></td>
              <td style="color:#aaa;font-size:12px"><?php echo date('d M Y', strtotime($row['donation_date'])); ?></td>
              <td>
                <?php
                  $sc = ['Verified'=>'bl-badge-verify','Pending'=>'bl-badge-pending','Rejected'=>'bl-badge-reject'];
                  $cls = $sc[$row['status']] ?? 'bl-badge-verify';
                ?>
                <span class="bl-badge <?php echo $cls; ?>"><?php echo $row['status']; ?></span>
              </td>
              <td>
                <a href="edit_record.php?id=<?php echo $row['id']; ?>" class="bl-btn bl-btn-sm bl-btn-edit">Edit</a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bl-pagination">
        <span>Showing <?php echo min($offset+1,$total_rows); ?>–<?php echo min($offset+$per_page,$total_rows); ?> of <?php echo number_format($total_rows); ?> records</span>
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

<?php include 'includes/footer.php'; ?>
