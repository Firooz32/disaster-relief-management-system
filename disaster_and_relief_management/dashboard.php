<?php
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
require __DIR__ . '/inc/health.php';
guard();

$s = overall_stats();
$stat = [
    ['Disasters on record', $s['disasters'], 'disaster', 'disaster'],
    ['Families affected', $s['people'], 'affected_person', 'families'],
    ['Shelter locations', $s['shelters'], 'shelter', 'shelter'],
    ['Rescue organisations', $s['orgs'], 'rescue_org', 'organisations'],
    ['Early warnings issued', $s['warnings'], 'early_warning', 'warnings'],
    ['Total shelter places', $s['capacity'], 'shelter', 'capacity'],
    ['People sheltered now', $s['occupied'], 'shelter_assignment', 'occupancy'],
    ['Relief units remaining', $s['relief'] - $s['distributed'], 'relief_distribution', 'relief'],
    ['Estimated damage loss', 'Tk ' . number_format($s['loss']), 'damage', 'loss'],
];
$disasters = disaster_summary();
$risk      = risk_summary();
$shelters  = shelter_summary();
$alerts    = alerts();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'alert_ack') {
    if (!csrf_ok()) {
        flash('err', 'Session expired - please try again.');
        header('Location: dashboard.php#alerts');
        exit;
    }
    if (!can_edit()) {
        flash('err', 'Only an editor or administrator can acknowledge or assign alerts.');
        header('Location: dashboard.php#alerts');
        exit;
    }
    $alertKey = is_string($_POST['alert_key'] ?? null) ? $_POST['alert_key'] : '';
    $knownKeys = array_column($alerts, 3);
    $ownerValue = $_POST['owner_id'] ?? '0';
    $ownerId = is_scalar($ownerValue) && ctype_digit((string) $ownerValue) ? (int) $ownerValue : -1;
    if (!in_array($alertKey, $knownKeys, true)) {
        flash('err', 'That alert is no longer active; reload the dashboard.');
        header('Location: dashboard.php#alerts');
        exit;
    }
    if ($ownerId > 0 && !scalar("SELECT COUNT(*) FROM users WHERE User_ID = ? AND Role IN ('admin','editor')", [$ownerId])) {
        flash('err', 'Choose an active administrator or editor as the alert owner.');
        header('Location: dashboard.php#alerts');
        exit;
    }
    audit('alert_ack', 'alert', $alertKey, 'owner_id=' . $ownerId);
    flash('ok', 'Alert acknowledged and assigned.');
    header('Location: dashboard.php#alerts');
    exit;
}
$alertActions = [];
if ($alerts) {
    $alertKeys = array_column($alerts, 3);
    $placeholders = implode(',', array_fill(0, count($alertKeys), '?'));
    foreach (rows(
        "SELECT a.Record_Key, a.User_Name, a.Created_At, a.Details FROM audit_log a
         JOIN (SELECT Record_Key, MAX(Log_ID) AS Log_ID FROM audit_log
                WHERE Table_Name = 'alert' AND Record_Key IN ($placeholders) GROUP BY Record_Key) latest ON latest.Log_ID = a.Log_ID",
        $alertKeys
    ) as $action) {
        $alertActions[$action['Record_Key']] = $action;
    }
}
$alertOwners = can_edit() ? rows("SELECT User_ID, Username FROM users WHERE Role IN ('admin','editor') ORDER BY Username") : [];
$sigs      = signal_summary()['disasters'];
$healthChecks = health_checks();
$healthProblems = health_problem_count($healthChecks);
$fullSh    = array_values(array_filter(shelter_capacity(), fn($x) => $x['space']['cls'] === 'err'));
$shortAreas = [];                        // "Cyclone: Dacope, Mongla" for every active disaster that has a relief shortage
foreach (relief_status() as $rs) {
    $names = array_column(array_filter($rs['areas'], fn($a) => $a['rel']['cls'] === 'err'), 'name');
    if ($names) {
        $shortAreas[] = $rs['type'] . ' #' . $rs['id'] . ': ' . implode(', ', $names);
    }
}
$maxRisk   = max(1, ...array_map(fn($r) => (int) $r['n'], $risk ?: [['n' => 1]]));

$main = [];
$rel  = [];
foreach (all_tables() as $t) {
    $n = (int) scalar('SELECT COUNT(*) FROM `' . $t . '`');
    if (is_junction($t)) {
        $rel[$t] = $n;
    } else {
        $main[$t] = $n;
    }
}

page_start('Dashboard', 'dashboard');
?>
<div class="cards dashboard-options">
  <?php foreach ($stat as [$label, $val, $t, $icon]): ?>
    <a class="card stat dashboard-card dashboard-card-<?= h($icon) ?>" href="table.php?t=<?= h(urlencode($t)) ?>">
      <span class="dashboard-icon dashboard-icon-<?= h($icon) ?>" aria-hidden="true">
        <?php if ($icon === 'disaster'): ?>
          <svg viewBox="0 0 48 48"><path d="M24 5 44 40H4L24 5Z"/><path d="M24 17v11m0 6h.01"/></svg>
        <?php elseif ($icon === 'families' || $icon === 'organisations' || $icon === 'occupancy'): ?>
          <svg viewBox="0 0 48 48"><circle cx="18" cy="16" r="6"/><circle cx="33" cy="18" r="5"/><path d="M6 39v-3a12 12 0 0 1 24 0v3H6Zm24-12a9 9 0 0 1 12 9v3h-8"/></svg>
        <?php elseif ($icon === 'shelter'): ?>
          <svg viewBox="0 0 48 48"><path d="m5 22 19-16 19 16M10 19v23h28V19M20 42V28h8v14"/></svg>
        <?php elseif ($icon === 'warnings'): ?>
          <svg viewBox="0 0 48 48"><path d="M24 6 44 41H4L24 6Z"/><path d="M24 18v11m0 6h.01"/></svg>
        <?php elseif ($icon === 'capacity'): ?>
          <svg viewBox="0 0 48 48"><path d="M8 40V10h32v30M8 24h32M14 18h6m8 0h6M16 40V30m16 10V30"/></svg>
        <?php elseif ($icon === 'relief'): ?>
          <svg viewBox="0 0 48 48"><path d="m5 15 19-9 19 9-19 10-19-10Zm0 0v20l19 9 19-9V15M24 25v19m-9-24 19-10"/></svg>
        <?php else: ?>
          <svg viewBox="0 0 48 48"><path d="M7 40V9m0 31h35M13 33l9-10 7 5 12-15"/><path d="M33 13h8v8"/></svg>
        <?php endif; ?>
      </span>
      <span class="dashboard-card-copy">
        <span class="num"><?= h($val) ?></span>
        <span class="lbl"><?= h($label) ?></span>
        <span class="dashboard-card-link">Explore records <span aria-hidden="true">&rarr;</span></span>
      </span>
    </a>
  <?php endforeach; ?>
  <a class="card stat dashboard-card weather-launch" href="weather.php">
    <span class="dashboard-icon weather-icon" aria-hidden="true">
      <svg viewBox="0 0 48 48" focusable="false">
        <circle cx="32" cy="15" r="8" fill="#f3b63f"/>
        <path d="M13 36h22a8 8 0 0 0 .4-16 12 12 0 0 0-22.8 3.2A6.5 6.5 0 0 0 13 36Z" fill="#fff" stroke="#1684ad" stroke-width="2.5" stroke-linejoin="round"/>
      </svg>
    </span>
    <span class="dashboard-card-copy">
      <span class="num">Live Weather</span>
      <span class="lbl">Khulna conditions, forecast &amp; map</span>
      <span class="dashboard-card-link">Check the forecast <span aria-hidden="true">&rarr;</span></span>
    </span>
  </a>
</div>

<section class="panel" id="liveSummary">
  <h2>Live status <span class="muted small">(<a href="report.php">open the Live Report</a> for the map and details)</span>
    <a class="btn btn-sm btn-ghost" href="stock.php">Manage inventory batches</a></h2>
  <div class="sig-cards">
    <div class="path-card" style="border-left-color:#0f6b8f;background:#fff">
      <h4 style="margin-top:0">Warning signals</h4>
      <?php foreach ($sigs as $g): ?>
        <div><b><?= h($g['type']) ?></b> <span class="muted small">#<?= (int) $g['id'] ?></span>
          <span class="sig sig-<?= h($g['sig']['cls']) ?>"><?= $g['sig']['level'] === null ? '' : 'Signal ' . (int) $g['sig']['level'] . ' - ' ?><?= h($g['sig']['label']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$sigs): ?><span class="muted small">No active disaster.</span><?php endif; ?>
    </div>
    <div class="path-card" style="border-left-color:#0f6b8f;background:#fff">
      <h4 style="margin-top:0">Shelter space</h4>
      <?php if ($fullSh): ?>
        <span class="sig sig-err">No space available</span>
        <div class="small"><?= h(implode(', ', array_column($fullSh, 'name'))) ?></div>
      <?php else: ?><span class="sig sig-ok">Space available everywhere</span><?php endif; ?>
    </div>
    <div class="path-card" style="border-left-color:#0f6b8f;background:#fff">
      <h4 style="margin-top:0">Relief distribution</h4>
      <?php if ($shortAreas): ?>
        <span class="sig sig-err">Shortage</span>
        <?php foreach ($shortAreas as $line): ?><div class="small"><?= h($line) ?></div><?php endforeach; ?>
      <?php else: ?><span class="sig sig-ok">Relief on track</span><?php endif; ?>
    </div>
    <div class="path-card" style="border-left-color:#0f6b8f;background:#fff">
      <h4 style="margin-top:0">Data health</h4>
      <?php if ($healthProblems): ?>
        <span class="sig sig-err"><?= $healthProblems ?> problem<?= $healthProblems === 1 ? '' : 's' ?></span>
        <div class="small"><a href="health.php">See what needs fixing</a></div>
      <?php else: ?><span class="sig sig-ok">No problems found</span><div class="small"><a href="health.php">View the checks</a></div><?php endif; ?>
    </div>
  </div>
</section>

<?php if ($alerts): ?>
<section class="panel" id="alerts">
  <h2>Needs attention <span class="muted small">(<?= count($alerts) ?>)</span></h2>
  <p class="muted small">Editors and administrators can acknowledge and assign alerts. Alerts disappear automatically when the underlying issue is fixed.</p>
  <ul class="alerts">
    <?php foreach ($alerts as [$lvl, $txt, $href, $alertKey]):
        $action = $alertActions[$alertKey] ?? null;
        $assignedId = $action && preg_match('/(?:^|;)owner_id=(\d+)/', (string) $action['Details'], $m) ? (int) $m[1] : 0; ?>
      <li class="lv-<?= h($lvl) ?>">
        <a href="<?= h($href) ?>"><?= h($txt) ?></a>
        <?php if ($action): ?>
          <div class="small">Acknowledged by <?= h($action['User_Name']) ?> at <?= h($action['Created_At']) ?>
            <?php if ($assignedId): $owner = current(array_filter($alertOwners, fn($u) => (int) $u['User_ID'] === $assignedId)); ?>
              <?php if ($owner): ?> · Assigned to <?= h($owner['Username']) ?><?php endif; ?>
            <?php endif; ?>
          </div>
        <?php else: ?><div class="small">Open · not yet acknowledged</div><?php endif; ?>
        <?php if (can_edit()): ?>
          <form method="post" class="inline alert-action-form">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="op" value="alert_ack">
            <input type="hidden" name="alert_key" value="<?= h($alertKey) ?>">
            <label class="small">Assign to
              <select name="owner_id">
                <option value="0">Unassigned</option>
                <?php foreach ($alertOwners as $owner): ?>
                  <option value="<?= (int) $owner['User_ID'] ?>"<?= (int) $owner['User_ID'] === $assignedId ? ' selected' : '' ?>><?= h($owner['Username']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <button class="btn btn-sm btn-ghost" type="submit"><?= $action ? 'Update assignment' : 'Acknowledge &amp; assign' ?></button>
          </form>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<div class="grid2">
  <section class="panel">
    <h2>Disasters at a glance</h2>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Report</th><th>Type</th><th>Date</th><th>Areas affected</th><th class="r">People</th><th class="r">Loss (Tk)</th></tr></thead>
        <tbody>
        <?php foreach ($disasters as $d): ?>
          <tr>
            <td><a href="disaster.php?id=<?= (int) $d['id'] ?>"><?= h($d['id']) ?></a></td>
            <td><b><?= h($d['type']) ?></b></td>
            <td><?= h($d['date']) ?></td>
            <td><?= h($d['areas'] ?? '-') ?></td>
            <td class="r"><?= h($d['people']) ?></td>
            <td class="r"><?= number_format((float) $d['loss']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$disasters): ?><tr><td colspan="6" class="muted">No disasters yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <h2>Areas by risk level</h2>
    <?php foreach ($risk as $r): ?>
      <div class="bar-row">
        <span class="bar-l"><?= h($r['lvl']) ?></span>
        <span class="bar"><i style="width:<?= (int) round(((int) $r['n'] / $maxRisk) * 100) ?>%"></i></span>
        <span class="bar-n"><?= h($r['n']) ?></span>
      </div>
    <?php endforeach; ?>
  </section>
</div>

<section class="panel">
  <h2>Shelter occupancy <span class="muted small">(people currently checked in vs capacity)</span></h2>
  <?php foreach ($shelters as $sh):
      $pct = $sh['capacity'] > 0 ? (int) min(100, round($sh['occupied'] / $sh['capacity'] * 100)) : 0; ?>
    <div class="bar-row">
      <span class="bar-l wide"><?= h($sh['name']) ?></span>
      <span class="bar<?= $pct >= 90 ? ' hot' : '' ?>"><i style="width:<?= $pct ?>%"></i></span>
      <span class="bar-n wide"><?= number_format($sh['occupied']) ?> / <?= number_format($sh['capacity']) ?></span>
    </div>
  <?php endforeach; ?>
</section>

<section class="panel">
  <h2>Database tables</h2>
  <p class="muted">Click a table to view, search, add, edit or delete its records.</p>
  <h3>Main tables</h3>
  <div class="chips">
    <?php foreach ($main as $t => $n): ?>
      <a class="chip" href="table.php?t=<?= h(urlencode($t)) ?>"><?= h(pretty($t)) ?> <b><?= $n ?></b></a>
    <?php endforeach; ?>
  </div>
  <h3>Relationship tables</h3>
  <div class="chips">
    <?php foreach ($rel as $t => $n): ?>
      <a class="chip" href="table.php?t=<?= h(urlencode($t)) ?>"><?= h(pretty($t)) ?> <b><?= $n ?></b></a>
    <?php endforeach; ?>
  </div>
</section>
<?php page_end(); ?>
