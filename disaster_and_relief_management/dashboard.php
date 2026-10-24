<?php
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
require __DIR__ . '/inc/health.php';
guard();

$s = overall_stats();
$stat = [
    ['Disasters recorded',    $s['disasters'], 'disaster'],
    ['Affected families',     $s['people'],    'affected_person'],
    ['Shelters',              $s['shelters'],  'shelter'],
    ['Rescue organisations',  $s['orgs'],      'rescue_org'],
    ['Early warnings',        $s['warnings'],  'early_warning'],
    ['Total shelter capacity', $s['capacity'], 'shelter'],
    ['People in shelters now', $s['occupied'], 'shelter_assignment'],
    ['Relief units left',     $s['relief'] - $s['distributed'], 'relief_distribution'],
];
$disasters = disaster_summary();
$risk      = risk_summary();
$shelters  = shelter_summary();
$alerts    = alerts();
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
<div class="cards">
  <?php foreach ($stat as [$label, $val, $t]): ?>
    <a class="card stat" href="table.php?t=<?= h(urlencode($t)) ?>"><div class="num"><?= number_format($val) ?></div><div class="lbl"><?= h($label) ?></div></a>
  <?php endforeach; ?>
  <div class="card stat"><div class="num">Tk <?= number_format($s['loss']) ?></div><div class="lbl">Total estimated loss</div></div>
</div>

<section class="panel" id="liveSummary">
  <h2>Live status <span class="muted small">(<a href="report.php">open the Live Report</a> for the map and details)</span></h2>
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
<section class="panel">
  <h2>Needs attention <span class="muted small">(<?= count($alerts) ?>)</span></h2>
  <ul class="alerts">
    <?php foreach ($alerts as [$lvl, $txt, $href]): ?>
      <li class="lv-<?= h($lvl) ?>"><a href="<?= h($href) ?>"><?= h($txt) ?></a></li>
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
