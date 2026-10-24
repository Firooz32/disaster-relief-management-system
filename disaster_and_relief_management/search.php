<?php
/** Search every table at once (names, phone numbers, places, descriptions...). */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
guard();

$q    = trim((string) ($_GET['q'] ?? ''));
$hits = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    foreach (all_tables() as $t) {
        $cols  = array_column(table_cols($t), 'Field');
        $where = implode(' OR ', array_map(fn($c) => 'CAST(`' . $c . '` AS CHAR) LIKE ?', $cols));
        $par   = array_fill(0, count($cols), $like);
        $n     = (int) scalar("SELECT COUNT(*) FROM `$t` WHERE $where", $par);
        if ($n === 0) {
            continue;
        }
        $label = null;          // first text column that is not the key: a readable name for the row
        foreach (table_cols($t) as $c) {
            if ($label === null && $c['Key'] !== 'PRI' && stripos((string) $c['Type'], 'varchar') === 0) {
                $label = $c['Field'];
            }
        }
        $pk = pk_cols($t)[0] ?? $cols[0];
        $hits[] = [$t, $n, rows('SELECT `' . $pk . '` AS k' . ($label ? ', `' . $label . '` AS l' : '') . " FROM `$t` WHERE $where ORDER BY `$pk` LIMIT 5", $par), $label !== null];
    }
}

page_start('Search', 'search');
?>
<section class="panel">
  <form method="get" class="search" action="search.php">
    <input type="search" name="q" value="<?= h($q) ?>" placeholder="Name, phone number, place, item..." autofocus>
    <button class="btn btn-primary" type="submit">Search all tables</button>
  </form>
  <?php if ($q === ''): ?>
    <p class="muted">Type a name, phone number, area, shelter, organisation or item. Every table is searched.</p>
  <?php elseif (!$hits): ?>
    <p class="muted">Nothing found for "<?= h($q) ?>".</p>
  <?php else: ?>
    <p class="muted small">Found matches in <?= count($hits) ?> table<?= count($hits) === 1 ? '' : 's' ?> for "<?= h($q) ?>".</p>
    <?php foreach ($hits as [$t, $n, $sample, $hasLabel]): ?>
      <h3><?= h(pretty($t)) ?> <span class="muted">- <?= $n ?> match<?= $n === 1 ? '' : 'es' ?></span></h3>
      <ul class="plain">
        <?php foreach ($sample as $s): ?><li>#<?= h($s['k']) ?><?= $hasLabel ? ' - ' . h($s['l']) : '' ?></li><?php endforeach; ?>
        <?php if ($n > count($sample)): ?><li class="muted">... and <?= $n - count($sample) ?> more</li><?php endif; ?>
      </ul>
      <a class="btn btn-sm btn-ghost" href="table.php?t=<?= h(urlencode($t)) ?>&amp;q=<?= h(urlencode($q)) ?>">Open in <?= h(pretty($t)) ?></a>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php page_end(); ?>
