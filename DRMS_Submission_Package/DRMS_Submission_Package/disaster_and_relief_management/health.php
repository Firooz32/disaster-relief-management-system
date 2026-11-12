<?php
/** Data health: checks the whole database for records that contradict each other or are incomplete. Read-only. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/health.php';
guard();

$checks   = health_checks();
$problems = health_problem_count($checks);
$labels   = ['err' => 'Error', 'warn' => 'Missing', 'info' => 'Note'];

page_start('Data health', 'health');
?>
<section class="panel">
  <?php if ($problems === 0): ?>
    <div class="alert alert-ok"><b>No problems found.</b> All <?= count($checks) ?> checks passed<?= array_filter($checks, fn($c) => $c['count'] > 0) ? ' (some notes below are only for your attention)' : '' ?>.</div>
  <?php else: ?>
    <div class="alert alert-err"><b><?= $problems ?> check<?= $problems === 1 ? '' : 's' ?> found a problem.</b> Open the table, fix the records, then reload this page.</div>
  <?php endif; ?>
  <p class="muted small">These checks read the whole database. <b>Error</b> = records contradict each other, <b>Missing</b> = information the live pages need is absent, <b>Note</b> = worth a look. Nothing here is changed automatically.</p>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Check</th><th>Result</th><th>Records</th></tr></thead>
    <tbody>
    <?php foreach ($checks as $c): ?>
      <tr>
        <td><b><?= h($c['title']) ?></b><br><span class="muted small"><?= h($c['help']) ?></span></td>
        <td class="nowrap">
          <?php if ($c['count'] === 0): ?><span class="sig sig-ok">OK</span>
          <?php else: ?><span class="sig sig-<?= $c['level'] === 'err' ? 'err' : ($c['level'] === 'warn' ? 'warn' : 'none') ?>"><?= h($labels[$c['level']]) ?>: <?= (int) $c['count'] ?></span><?php endif; ?>
        </td>
        <td class="small">
          <?php if ($c['count'] > 0): ?>
            <?php foreach ($c['sample'] as $s): ?><div><?= h($s) ?></div><?php endforeach; ?>
            <?php if ($c['count'] > count($c['sample'])): ?><div class="muted">... and <?= $c['count'] - count($c['sample']) ?> more</div><?php endif; ?>
            <a href="table.php?t=<?= h(urlencode($c['link'])) ?>">Open <?= h(pretty($c['link'])) ?></a>
          <?php else: ?><span class="muted">-</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php page_end(); ?>
