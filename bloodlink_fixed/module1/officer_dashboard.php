<?php
// BloodLink — Module 1: Officer Dashboard (Donor List)
require_once 'auth.php';
require_role('medical_officer');
require_once 'db.php';

$activePage = 'dashboard';
$pageTitle  = 'Donor Management';
$u = current_user();

// ── FILTERS ──────────────────────────────────────────────────
$search    = trim($_GET['search']      ?? '');
$filter_bt = $_GET['blood_type']       ?? '';
$filter_hs = $_GET['health_status']    ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));
$per_page  = 10;
$offset    = ($page - 1) * $per_page;

// ── BUILD QUERY ───────────────────────────────────────────────
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $s = "%$search%";
    $where   .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR d.donor_id LIKE ? OR d.ic_number LIKE ?)";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= "ssss";
}
if ($filter_bt) {
    $where   .= " AND d.blood_type = ?";
    $params[] = $filter_bt;
    $types   .= "s";
}
if ($filter_hs) {
    $where   .= " AND d.health_status = ?";
    $params[] = $filter_hs;
    $types   .= "s";
}

$base_sql = "FROM bl_donors d $where";

// Total count
$cnt = $conn->prepare("SELECT COUNT(*) AS total $base_sql");
if ($types) $cnt->bind_param($types, ...$params);
$cnt->execute();
$total_rows  = $cnt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

// Rows with last donation date
$sql = "SELECT d.*,
    (SELECT donation_date FROM bl_donation_records dr
     WHERE dr.donor_id = d.id ORDER BY donation_date DESC LIMIT 1) AS last_donation
    $base_sql
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params = array_merge($params, [$per_page, $offset]);
$all_types  = $types . "ii";
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$donors = $stmt->get_result();

// Stats
$stats = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(blood_type='A+') + SUM(blood_type='A-') AS type_a,
    SUM(blood_type='B+') + SUM(blood_type='B-') AS type_b,
    SUM(blood_type='O+') + SUM(blood_type='O-') AS type_o,
    SUM(blood_type='AB+')+ SUM(blood_type='AB-')AS type_ab,
    SUM(health_status='Healthy')           AS healthy,
    SUM(health_status='Under Medication')  AS medicated,
    SUM(health_status='Not Eligible')      AS ineligible
    FROM bl_donors")->fetch_assoc();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$blood_types = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
$health_opts = ['Healthy','Under Medication','Not Eligible'];
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

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Donor Management</h2>
        <p>View and manage all registered blood donors</p>
      </div>
      <div class="bl-top-hero-actions">
        <span style="font-size:12px;color:rgba(255,255,255,.9)">
          Logged in as <strong style="color:#fff"><?php echo htmlspecialchars($u['full_name']); ?></strong>
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Total Donors</p>
        <p><?php echo number_format($stats['total']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Eligible (Healthy)</p>
        <p><?php echo number_format($stats['healthy']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Under Medication</p>
        <p><?php echo number_format($stats['medicated']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Not Eligible</p>
        <p><?php echo number_format($stats['ineligible']); ?></p>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="bl-card">
      <form method="GET" action="officer_dashboard.php">
        <div class="bl-search-bar">
          <div class="bl-search-wrap">
            <svg class="bl-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" stroke="#ccc" stroke-width="1.5"/>
              <path d="M16.5 16.5L21 21" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <input class="bl-input" type="text" name="search"
                   placeholder="Search by name, donor ID or IC number..."
                   value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <select class="bl-select" name="blood_type">
            <option value="">All blood types</option>
            <?php foreach($blood_types as $bt): ?>
            <option value="<?php echo $bt; ?>" <?php echo ($filter_bt===$bt)?'selected':''; ?>><?php echo $bt; ?></option>
            <?php endforeach; ?>
          </select>

          <select class="bl-select" name="health_status">
            <option value="">All statuses</option>
            <?php foreach($health_opts as $hs): ?>
            <option value="<?php echo $hs; ?>" <?php echo ($filter_hs===$hs)?'selected':''; ?>><?php echo $hs; ?></option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="bl-btn bl-btn-primary" style="white-space:nowrap">Search</button>
          <a href="officer_dashboard.php" class="bl-btn bl-btn-ghost">Reset</a>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="bl-card">
      <div class="bl-table-wrap">
        <table class="bl-table">
          <thead>
            <tr>
              <th>Donor ID</th>
              <th>Name</th>
              <th>IC Number</th>
              <th>Blood Type</th>
              <th>Phone</th>
              <th>Health Status</th>
              <th>Last Donation</th>
              <th>Eligible</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($donors->num_rows === 0): ?>
            <tr>
              <td colspan="8">
                <div class="bl-empty">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="#555" stroke-width="1.5"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#555" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                  <p>No donors found</p>
                  <small>Try adjusting your search or filter</small>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php while ($row = $donors->fetch_assoc()):
              $initials = strtoupper(substr($row['first_name'],0,1) . substr($row['last_name'],0,1));
              // Eligibility check
              $is_eligible = $row['health_status'] === 'Healthy';
              if ($row['last_donation']) {
                  $diff = (new DateTime())->diff(new DateTime($row['last_donation']))->days;
                  if ($diff < 56) $is_eligible = false;
              }
              $hs_class = [
                'Healthy'          => 'bl-badge-completed',
                'Under Medication' => 'bl-badge-pending',
                'Not Eligible'     => 'bl-badge-cancelled',
              ][$row['health_status']] ?? 'bl-badge-pending';
            ?>
            <tr>
              <td><span class="bl-record-id"><?php echo htmlspecialchars($row['donor_id']); ?></span></td>
              <td>
                <div class="bl-event-cell">
                  <div class="bl-avatar"><?php echo $initials; ?></div>
                  <div>
                    <div style="font-weight:500"><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></div>
                  </div>
                </div>
              </td>
              <td style="font-family:monospace;font-size:11.5px;color:var(--text-muted)"><?php echo htmlspecialchars($row['ic_number']); ?></td>
              <td><span class="bl-badge bl-badge-blood"><?php echo htmlspecialchars($row['blood_type']); ?></span></td>
              <td style="color:var(--text-muted);font-size:12px"><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
              <td><span class="bl-badge <?php echo $hs_class; ?>"><?php echo htmlspecialchars($row['health_status']); ?></span></td>
              <td style="color:var(--text-muted);font-size:12px">
                <?php echo $row['last_donation'] ? date('d M Y', strtotime($row['last_donation'])) : '—'; ?>
              </td>
              <td>
                <?php if ($is_eligible): ?>
                <span class="bl-badge bl-badge-completed">Yes</span>
                <?php else: ?>
                <span class="bl-badge bl-badge-cancelled">No</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bl-pagination">
        <span>Showing <?php echo min($offset+1,$total_rows); ?>–<?php echo min($offset+$per_page,$total_rows); ?> of <?php echo number_format($total_rows); ?> donors</span>
        <div class="bl-page-btns">
          <?php
          $qs = http_build_query(array_merge($_GET, ['page'=>$page-1]));
          echo "<a href='officer_dashboard.php?$qs' class='bl-page-btn" . ($page<=1?' disabled':'') . "'>Prev</a>";
          for ($p=1; $p<=$total_pages; $p++) {
            $qs = http_build_query(array_merge($_GET, ['page'=>$p]));
            echo "<a href='officer_dashboard.php?$qs' class='bl-page-btn" . ($p==$page?' active':'') . "'>$p</a>";
          }
          $qs = http_build_query(array_merge($_GET, ['page'=>$page+1]));
          echo "<a href='officer_dashboard.php?$qs' class='bl-page-btn" . ($page>=$total_pages?' disabled':'') . "'>Next</a>";
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
