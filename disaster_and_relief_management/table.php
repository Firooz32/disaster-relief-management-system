<?php
/** One page for every table: list (sortable) + search + CSV export + add / edit / delete (editors and admins only). */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/rules.php';
guard();

$t = (string) ($_GET['t'] ?? '');
if (!in_array($t, all_tables(), true)) {      // only real table names ever reach SQL
    header('Location: dashboard.php');
    exit;
}
$qt       = '`' . $t . '`';
$cols     = table_cols($t);
$names    = array_column($cols, 'Field');
$pks      = pk_cols($t);
$fks      = fk_map($t);
$canWrite = can_edit() && !in_array($t, READONLY_TABLES, true);
$canDelete = $canWrite && can_delete($t);
$action   = (string) ($_GET['a'] ?? 'list');
if (!in_array($action, ['list', 'add', 'edit', 'delete', 'export'], true)) {
    $action = 'list';
}

/* list state kept in every link: search text, search column, sort column / direction, page */
$q       = trim((string) ($_GET['q'] ?? ''));
$col     = in_array((string) ($_GET['col'] ?? ''), $names, true) ? (string) $_GET['col'] : '';
$sortCol = in_array((string) ($_GET['s'] ?? ''), $names, true) ? (string) $_GET['s'] : '';
$desc    = $sortCol !== '' && ($_GET['d'] ?? '') === 'desc';
if ($t === 'audit_log' && $sortCol === '') {      // newest log entries first
    $sortCol = 'Log_ID';
    $desc    = true;
}
$pg     = max(1, (int) ($_GET['p'] ?? 1));
$state   = ['t' => $t, 'q' => $q, 'col' => $col, 's' => $sortCol, 'd' => $desc ? 'desc' : '', 'p' => $pg > 1 ? (string) $pg : ''];
$url     = fn(array $over = []) => 'table.php?' . http_build_query(array_filter($over + $state, fn($v) => $v !== ''));
$back    = $url();

function is_auto(array $c): bool
{
    return stripos((string) $c['Extra'], 'auto_increment') !== false;
}

function kind(array $c): string
{
    $ty = strtolower((string) $c['Type']);
    if (str_starts_with($ty, 'enum')) return 'enum';
    if (str_starts_with($ty, 'datetime') || str_starts_with($ty, 'timestamp')) return 'datetime';
    if (str_starts_with($ty, 'date')) return 'date';
    if (str_starts_with($ty, 'decimal') || str_starts_with($ty, 'float') || str_starts_with($ty, 'double')) return 'decimal';
    if (preg_match('/^(tiny|small|medium|big)?int/', $ty)) return 'int';
    if (str_contains($ty, 'text')) return 'textarea';
    return 'text';
}

/** Digits after the decimal point of decimal(p,s); 2 for float / double. */
function scale_of(array $c): int
{
    return preg_match('/,\s*(\d+)\)/', (string) $c['Type'], $m) ? (int) $m[1] : 2;
}

/** Form value -> value stored in MySQL. */
function to_db(array $c, string $raw): string
{
    if (kind($c) === 'datetime') {
        $raw = str_replace('T', ' ', $raw);
        if (strlen($raw) === 16) {
            $raw .= ':00';
        }
    }
    return $raw;
}

/** Stored value -> value shown inside the form input. */
function to_form(array $c, mixed $v): string
{
    if ($v === null) return '';
    $v = (string) $v;
    if (kind($c) === 'datetime') {
        return str_replace(' ', 'T', substr($v, 0, 16));
    }
    return $v;
}

/** Plain-language message for the user; the technical text only goes to the PHP error log. */
function friendly_error(mysqli_sql_exception $e): string
{
    error_log('[DRMS] ' . $e->getMessage());
    switch ($e->getCode()) {
        case 1062: return 'Duplicate entry: a record with the same key/unique value already exists.';
        case 1451: return 'This record cannot be deleted/changed because other records still depend on it.';
        case 1452: return 'The selected related record does not exist (foreign key).';
        case 1048:
        case 1364: return 'A required field is empty.';
        case 1366:
        case 1292: return 'A value has the wrong format for its column.';
        case 3819: return 'A value breaks a rule of this table (for example a negative quantity, a coordinate out of range or a check-out before the check-in).';   // CHECK constraint
        case 1644: return $e->getMessage();     // our own trigger text (inc/triggers.sql), written for users
        default:   return 'Database error. Nothing was saved.';
    }
}

/** Primary-key values taken from $src ($_GET['pk'] or $_POST['pk']). Null when any is missing. */
function pk_from(array $src, array $pks): ?array
{
    $pk = $src['pk'] ?? null;
    if (!is_array($pk)) return null;
    $out = [];
    foreach ($pks as $p) {
        if (!isset($pk[$p]) || is_array($pk[$p])) return null;
        $out[$p] = (string) $pk[$p];
    }
    return $out;
}

function where_pk(array $pk): array
{
    $parts = [];
    foreach (array_keys($pk) as $p) {
        $parts[] = '`' . $p . '` = ?';
    }
    return [implode(' AND ', $parts), array_values($pk)];
}

/** "Person_ID=3, Item_ID=2" - how a record is named in the audit log. */
function key_text(array $pk): string
{
    return implode(', ', array_map(fn($k, $v) => "$k=$v", array_keys($pk), $pk));
}

$formErr  = '';
$formVals = null;

/* ----------------------------------------------------------------- POST: add / edit / delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = (string) ($_POST['op'] ?? '');
    if (!csrf_ok()) {
        flash('err', 'Session expired - please try again.');
        header('Location: ' . $back);
        exit;
    }
    if (!$canWrite || ($op === 'delete' && !$canDelete)) {
        flash('err', $canWrite ? 'Only an administrator can delete records of ' . pretty($t) . '.' : 'Your account is read-only for this table.');
        header('Location: ' . $back);
        exit;
    }
    $f = is_array($_POST['f'] ?? null) ? $_POST['f'] : [];
    try {
        db()->begin_transaction();            // check + write + audit entry succeed or fail together
        lock_for_rules($t, $f);               // capacity / stock cannot be passed by two people at once
        if ($op === 'add' || $op === 'edit') {
            $viol = check_rules($t, $f, $op === 'edit' ? pk_from($_POST, $pks) : null);   // capacity, stock and "belongs to this disaster" rules
            if ($viol !== null) {
                throw new RuleViolation($viol);
            }
        }
        if ($op === 'add') {
            $fields = [];
            $vals   = [];
            foreach ($cols as $c) {
                if (is_auto($c)) continue;
                $raw = trim((string) ($f[$c['Field']] ?? ''));
                if ($raw === '') continue;                     // let MySQL use the default / NULL
                $fields[] = '`' . $c['Field'] . '`';
                $vals[]   = to_db($c, $raw);
            }
            $sql = $fields
                ? "INSERT INTO $qt (" . implode(', ', $fields) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')'
                : "INSERT INTO $qt () VALUES ()";
            q($sql, $vals);
            $newId = db()->insert_id;
            audit('add', $t, $newId ? "$pks[0]=$newId" : key_text(array_combine($pks, array_map(fn($p) => (string) ($f[$p] ?? ''), $pks))),
                  implode('; ', array_map(fn($fl, $v) => trim($fl, '`') . ' = ' . $v, $fields, $vals)));
            db()->commit();
            flash('ok', 'Record added to ' . pretty($t) . '.');
            header('Location: ' . $back);
            exit;
        }
        if ($op === 'edit') {
            $pk = pk_from($_POST, $pks);
            if ($pk === null) throw new RuntimeException('Record key is missing.');
            $sets = [];
            $vals = [];
            foreach ($cols as $c) {
                if (is_auto($c)) continue;
                $raw    = trim((string) ($f[$c['Field']] ?? ''));
                $sets[] = '`' . $c['Field'] . '` = ?';
                $vals[] = $raw === '' ? null : to_db($c, $raw);
            }
            [$w, $wv] = where_pk($pk);
            $before = row1("SELECT * FROM $qt WHERE $w", $wv) ?? [];
            q("UPDATE $qt SET " . implode(', ', $sets) . " WHERE $w", array_merge($vals, $wv));
            $changes = [];
            foreach ($cols as $c) {
                if (is_auto($c)) continue;
                $fn  = (string) $c['Field'];
                $new = trim((string) ($f[$fn] ?? ''));
                $new = $new === '' ? null : to_db($c, $new);
                $old = $before[$fn] ?? null;
                $same = ($old === null || $new === null) ? $old === $new
                      : (is_numeric($old) && is_numeric($new) ? (float) $old === (float) $new : (string) $old === (string) $new);
                if (!$same) {
                    $changes[] = $fn . ': ' . ($old === null ? '(empty)' : $old) . ' -> ' . ($new === null ? '(empty)' : $new);
                }
            }
            audit('edit', $t, key_text($pk), $changes ? implode('; ', $changes) : 'no field changed');
            db()->commit();
            flash('ok', 'Record updated.');
            header('Location: ' . $back);
            exit;
        }
        if ($op === 'delete') {
            $pk = pk_from($_POST, $pks);
            if ($pk === null) throw new RuntimeException('Record key is missing.');
            [$w, $wv] = where_pk($pk);
            $gone = row1("SELECT * FROM $qt WHERE $w", $wv) ?? [];
            q("DELETE FROM $qt WHERE $w", $wv);
            audit('delete', $t, key_text($pk), implode('; ', array_map(fn($k, $v) => $k . ' = ' . ($v ?? '(empty)'), array_keys($gone), $gone)));
            db()->commit();
            flash('ok', 'Record deleted.');
            header('Location: ' . $back);
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        db()->rollback();
        $msg = friendly_error($e);
        if ($op === 'delete') {
            flash('err', $msg);
            header('Location: ' . $back);
            exit;
        }
        $formErr  = $msg;                     // stay on the form and keep what was typed
        $formVals = $f;
        $action   = $op === 'edit' ? 'edit' : 'add';
    } catch (RuleViolation $e) {
        db()->rollback();
        $formErr  = $e->getMessage();         // same as a database error: stay on the form, keep what was typed
        $formVals = $f;
        $action   = $op === 'edit' ? 'edit' : 'add';
    } catch (RuntimeException $e) {
        db()->rollback();
        flash('err', $e->getMessage());
        header('Location: ' . $back);
        exit;
    }
}

if (in_array($action, ['add', 'edit', 'delete'], true) && !$canWrite) {
    flash('err', 'Your account is read-only for this table.');
    header('Location: ' . $back);
    exit;
}
if ($action === 'delete' && !$canDelete) {
    flash('err', 'Only an administrator can delete records of ' . pretty($t) . '.');
    header('Location: ' . $back);
    exit;
}

/* ----------------------------------------------------------------- form field */
function render_field(array $c, string $val, bool $editing, array $fks): void
{
    $name = (string) $c['Field'];
    $auto = is_auto($c);
    $need = $c['Null'] === 'NO' && $c['Default'] === null && !$auto;
    $req  = $need ? ' required' : '';
    echo '<label class="fld"><span>' . h(str_replace('_', ' ', $name)) . ($need ? ' <em>*</em>' : '') . '</span>';
    $opts = isset($fks[$name]) ? fk_options($fks[$name][0], $fks[$name][1]) : null;
    if ($auto) {
        echo '<input type="text" value="' . h($editing ? $val : '') . '" placeholder="Auto" disabled>';
    } elseif ($opts !== null || kind($c) === 'enum') {
        $opts ??= array_combine(enum_values((string) $c['Type']), enum_values((string) $c['Type']));
        echo '<select name="f[' . h($name) . ']"' . $req . '>';
        echo '<option value="">- select -</option>';
        foreach ($opts as $v => $label) {
            echo '<option value="' . h($v) . '"' . ((string) $v === $val ? ' selected' : '') . '>' . h($label) . '</option>';
        }
        echo '</select>';
    } else {
        $k = kind($c);
        if ($k === 'textarea') {
            echo '<textarea name="f[' . h($name) . ']" rows="4"' . $req . '>' . h($val) . '</textarea>';
        } else {
            $isNum = isset($fks[$name]) || $k === 'int' || $k === 'decimal';       // a huge FK table falls back to typing the id
            $type  = $isNum ? 'number' : ['date' => 'date', 'datetime' => 'datetime-local', 'text' => 'text'][$k];
            $step  = '';
            if ($k === 'decimal') {
                $sc   = scale_of($c);
                $step = ' step="' . ($sc > 0 ? '0.' . str_repeat('0', $sc - 1) . '1' : '1') . '"';
            } elseif ($isNum) {
                $step = ' step="1"';
            }
            $ml = preg_match('/\((\d+)\)/', (string) $c['Type'], $m) && $k === 'text' ? ' maxlength="' . (int) $m[1] . '"' : '';
            echo '<input type="' . $type . '" name="f[' . h($name) . ']" value="' . h($val) . '"' . $step . $ml . $req . '>';
        }
    }
    echo '</label>';
}

$title = pretty($t);

/* ----------------------------------------------------------------- ADD / EDIT form */
if ($action === 'add' || $action === 'edit') {
    $editing = $action === 'edit';
    $pk      = null;
    $row     = null;
    if ($editing) {
        $pk = pk_from($_POST ?: $_GET, $pks);
        if ($pk !== null) {
            [$w, $wv] = where_pk($pk);
            $row = row1("SELECT * FROM $qt WHERE $w", $wv);
        }
        if ($row === null) {
            flash('err', 'That record was not found.');
            header('Location: ' . $back);
            exit;
        }
    }
    page_start(($editing ? 'Edit record - ' : 'Add record - ') . $title, $t);
    ?>
    <section class="panel narrow">
      <?php if ($formErr !== ''): ?><div class="alert alert-err"><?= h($formErr) ?></div><?php endif; ?>
      <form method="post" action="<?= h($url(['a' => $editing ? 'edit' : 'add'])) ?>">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <input type="hidden" name="op" value="<?= $editing ? 'edit' : 'add' ?>">
        <?php if ($editing): foreach ($pk as $p => $v): ?>
          <input type="hidden" name="pk[<?= h($p) ?>]" value="<?= h($v) ?>">
        <?php endforeach; endif; ?>
        <?php foreach ($cols as $c):
            $fn  = (string) $c['Field'];
            $val = $formVals !== null ? (string) ($formVals[$fn] ?? '') : ($editing ? to_form($c, $row[$fn]) : '');
            render_field($c, $val, $editing, $fks);
        endforeach; ?>
        <div class="actions">
          <button class="btn btn-primary" type="submit"><?= $editing ? 'Update record' : 'Save record' ?></button>
          <a class="btn btn-ghost" href="<?= h($back) ?>">Cancel</a>
        </div>
      </form>
    </section>
    <?php
    page_end();
    exit;
}

/* ----------------------------------------------------------------- DELETE confirmation: shows what else would go */
if ($action === 'delete') {
    $pk  = pk_from($_GET, $pks);
    $row = null;
    if ($pk !== null) {
        [$w, $wv] = where_pk($pk);
        $row = row1("SELECT * FROM $qt WHERE $w", $wv);
    }
    if ($row === null) {
        flash('err', 'That record was not found.');
        header('Location: ' . $back);
        exit;
    }
    $deps    = [];
    $blocked = false;
    foreach (child_refs($t) as $ref) {
        $n = (int) scalar('SELECT COUNT(*) FROM `' . $ref['table'] . '` WHERE `' . $ref['col'] . '` = ?', [$row[$ref['rc']]]);
        if ($n === 0) continue;
        $rule = $ref['rule'] === 'CASCADE' ? 'will be deleted too' : ($ref['rule'] === 'SET NULL' ? 'will lose the link (field set empty)' : 'blocks the deletion - remove or move them first');
        $blocked = $blocked || !in_array($ref['rule'], ['CASCADE', 'SET NULL'], true);
        $deps[]  = [$ref['table'], $n, $rule];
    }
    page_start('Delete record - ' . $title, $t);
    ?>
    <section class="panel narrow">
      <p>You are about to delete this record from <b><?= h($title) ?></b>:</p>
      <ul class="plain">
        <?php foreach (array_slice($cols, 0, 5) as $c): ?>
          <li><span class="muted"><?= h(str_replace('_', ' ', (string) $c['Field'])) ?>:</span> <?= h($row[$c['Field']] ?? '-') ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($deps): ?>
        <div class="alert <?= $blocked ? 'alert-err' : 'alert-warn' ?>">
          Other records depend on it:
          <ul class="plain">
            <?php foreach ($deps as [$dt, $n, $rule]): ?><li><b><?= $n ?></b> in <?= h(pretty($dt)) ?> - <?= h($rule) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <p class="muted small">This cannot be undone.</p>
      <form method="post" action="<?= h($back) ?>">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <input type="hidden" name="op" value="delete">
        <?php foreach ($pk as $p => $v): ?><input type="hidden" name="pk[<?= h($p) ?>]" value="<?= h($v) ?>"><?php endforeach; ?>
        <div class="actions">
          <button class="btn btn-danger" type="submit"<?= $blocked ? ' disabled' : '' ?>>Yes, delete</button>
          <a class="btn btn-ghost" href="<?= h($back) ?>">Cancel</a>
        </div>
      </form>
    </section>
    <?php
    page_end();
    exit;
}

/* ----------------------------------------------------------------- LIST + SEARCH + EXPORT */
$where  = '';
$params = [];
if ($q !== '') {
    [$condition, $params] = table_search_condition($t, $q, $col);
    $where = ' WHERE ' . $condition;
}
$orderCols = $pks ?: [$names[0]];
$order     = ' ORDER BY ' . ($sortCol !== '' ? '`' . $sortCol . '` ' . ($desc ? 'DESC' : 'ASC') . ', ' : '')
           . implode(', ', array_map(fn($p) => '`' . $p . '` ASC', $orderCols));

if ($action === 'export') {                   // every matching row (not just this page) as a CSV file Excel opens correctly
    session_write_close();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $t . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $names, ',', '"', '');
    $rs = q("SELECT * FROM $qt$where$order", $params);
    while ($r = $rs->fetch_assoc()) {
        // a cell starting with = + - @ would run as a formula in Excel: prefix text (not numbers) with an apostrophe
        fputcsv($out, array_map(function ($v) {
            $s = $v === null ? '' : (string) $v;
            return ($s !== '' && !is_numeric($s) && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;
        }, $r), ',', '"', '');
    }
    exit;
}

$per   = 20;
$total = (int) (row1("SELECT COUNT(*) AS n FROM $qt$where", $params)['n'] ?? 0);
$pages = max(1, (int) ceil($total / $per));
$page  = min($pg, $pages);
$data  = rows("SELECT * FROM $qt$where$order LIMIT " . $per . ' OFFSET ' . (($page - 1) * $per), $params);

page_start($title, $t);
?>
<section class="panel">
  <div class="toolbar">
    <form class="search" method="get" action="table.php" autocomplete="off">
      <input type="hidden" name="t" value="<?= h($t) ?>">
      <?php if ($sortCol !== ''): ?><input type="hidden" name="s" value="<?= h($sortCol) ?>"><input type="hidden" name="d" value="<?= $desc ? 'desc' : '' ?>"><?php endif; ?>
      <select name="col" id="col" title="Search in">
        <option value="">All columns</option>
        <?php foreach ($names as $n): ?>
          <option value="<?= h($n) ?>"<?= $n === $col ? ' selected' : '' ?>><?= h(str_replace('_', ' ', $n)) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="sg-wrap">
        <input type="search" name="q" id="q" data-table="<?= h($t) ?>" value="<?= h($q) ?>" placeholder="Type to search - suggestions appear as you type">
        <ul class="sg-list" id="sugg" hidden></ul>
      </div>
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn btn-ghost" href="table.php?t=<?= h(urlencode($t)) ?>">Clear</a><?php endif; ?>
    </form>
    <div class="actions" style="margin:0">
      <a class="btn btn-ghost" href="<?= h($url(['a' => 'export', 'p' => ''])) ?>">Export CSV</a>
      <?php if ($canWrite): ?><a class="btn btn-primary" href="<?= h($url(['a' => 'add'])) ?>">+ Add record</a><?php endif; ?>
    </div>
  </div>
  <p class="muted small"><?= $total ?> record<?= $total === 1 ? '' : 's' ?><?= $q !== '' ? ' matching "' . h($q) . '"' : '' ?> - sorted <?= $desc ? 'descending' : 'ascending' ?> by <?= h($sortCol !== '' ? $sortCol : implode(', ', $orderCols)) ?> (click a column title to sort)</p>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <?php foreach ($cols as $c):
              $fn = (string) $c['Field'];
              $on = $fn === $sortCol; ?>
            <th><a class="sort" href="<?= h($url(['s' => $fn, 'd' => ($on && !$desc) ? 'desc' : '', 'p' => ''])) ?>"><?= h(str_replace('_', ' ', $fn)) ?><?= $on ? ($desc ? ' &#9660;' : ' &#9650;') : '' ?></a></th>
          <?php endforeach; ?>
          <?php if ($canWrite || $t === 'disaster'): ?><th class="r">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($data as $r): ?>
        <?php $pkq = []; foreach ($pks as $p) { $pkq[$p] = (string) $r[$p]; } ?>
        <tr>
          <?php foreach ($cols as $c):
              $fn = (string) $c['Field'];
              $v  = $r[$fn];
              if ($v === null) {
                  echo '<td class="muted">-</td>';
                  continue;
              }
              $opts = isset($fks[$fn]) ? fk_options($fks[$fn][0], $fks[$fn][1]) : null;
              if ($opts !== null) {
                  $txt = $opts[(string) $v] ?? (string) $v;
              } elseif (kind($c) === 'decimal') {
                  $txt = number_format((float) $v, scale_of($c));
              } else {
                  $txt = (string) $v;
              }
              $num = in_array(kind($c), ['int', 'decimal'], true) && !isset($fks[$fn]);
              echo '<td' . ($num ? ' class="r"' : '') . ' title="' . h((string) $v) . '"><span class="cell">' . h($txt) . '</span></td>';
          endforeach; ?>
          <?php if ($canWrite || $t === 'disaster'): ?>
          <td class="r nowrap">
            <?php if ($t === 'disaster'): ?><a class="btn btn-sm btn-ghost" href="disaster.php?id=<?= (int) $r['Disaster_ID'] ?>">Report</a><?php endif; ?>
            <?php if ($canWrite): ?>
            <a class="btn btn-sm btn-ghost" href="<?= h($url(['a' => 'edit']) . '&' . http_build_query(['pk' => $pkq])) ?>">Edit</a>
            <?php if ($canDelete): ?><a class="btn btn-sm btn-danger" href="<?= h($url(['a' => 'delete']) . '&' . http_build_query(['pk' => $pkq])) ?>">Delete</a><?php endif; ?>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$data): ?>
        <tr><td colspan="<?= count($cols) + ($canWrite || $t === 'disaster' ? 1 : 0) ?>" class="muted center">No records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?><a class="btn btn-sm btn-ghost" href="<?= h($url(['p' => $page > 2 ? (string) ($page - 1) : ''])) ?>">&laquo; Prev</a><?php endif; ?>
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?><a class="btn btn-sm btn-ghost" href="<?= h($url(['p' => (string) ($page + 1)])) ?>">Next &raquo;</a><?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php page_end(); ?>
