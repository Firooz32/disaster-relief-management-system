<?php
/** Situation report for one disaster: everything the database knows about it on a single printable page. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
require __DIR__ . '/inc/rules.php';
guard();

$all = rows('SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster ORDER BY Disaster_Date DESC, Disaster_ID DESC');
$id  = (int) ($_GET['id'] ?? ($all[0]['Disaster_ID'] ?? 0));
$dis = row1('SELECT d.*, u.Username AS Incident_Lead FROM disaster d LEFT JOIN users u ON u.User_ID = d.Incident_Lead_ID WHERE d.Disaster_ID = ?', [$id]);
if ($dis === null) {
    flash('err', 'That disaster was not found.');
    header('Location: dashboard.php');
    exit;
}

$fieldError = '';
$fieldValues = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'field_update') {
    if (!can_edit()) {
        flash('err', 'Only an editor or administrator can submit a field update.');
        header('Location: disaster.php?id=' . $id);
        exit;
    }
    if (!csrf_ok()) {
        flash('err', 'Session expired - please try again.');
        header('Location: disaster.php?id=' . $id);
        exit;
    }
    foreach (['Area_ID', 'Update_Type', 'Summary', 'Details', 'Observed_At'] as $field) {
        $value = $_POST[$field] ?? '';
        $fieldValues[$field] = is_string($value) ? trim($value) : '';
    }
    try {
        db()->begin_transaction();
        $fieldError = field_update_rules($id, $fieldValues) ?? '';
        if ($fieldError !== '') {
            db()->rollback();
        } else {
            $observed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fieldValues['Observed_At']);
            if ($observed === false) {
                throw new RuntimeException('The observation time is invalid.');
            }
            q('INSERT INTO incident_update (Disaster_ID, Area_ID, Update_Type, Summary, Details, Observed_At, Reported_By)
               VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$id, $fieldValues['Area_ID'] === '' ? null : (int) $fieldValues['Area_ID'], $fieldValues['Update_Type'],
                 $fieldValues['Summary'], $fieldValues['Details'], $observed->format('Y-m-d H:i:s'), (int) $_SESSION['uid']]);
            $updateId = (int) db()->insert_id;
            audit('add', 'incident_update', 'Update_ID=' . $updateId, $fieldValues['Update_Type'] . ': ' . $fieldValues['Summary']);
            db()->commit();
            flash('ok', 'Field update recorded.');
            header('Location: disaster.php?id=' . $id . '#field-updates');
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        db()->rollback();
        error_log('[DRMS] Field update failed: ' . $e->getMessage());
        $fieldError = 'The field update could not be saved. Check the values and try again.';
    } catch (RuntimeException $e) {
        db()->rollback();
        $fieldError = $e->getMessage();
    }
}

$areas = rows('SELECT a.Area_ID, a.Area_Name, a.Location, a.Risk_Level FROM disaster_area da JOIN area a ON a.Area_ID = da.Area_ID WHERE da.Disaster_ID = ? ORDER BY a.Area_Name', [$id]);
$fieldUpdates = rows('SELECT u.Update_Type, u.Summary, u.Details, u.Observed_At, a.Area_Name, reporter.Username AS Reported_By
                        FROM incident_update u LEFT JOIN area a ON a.Area_ID = u.Area_ID
                        JOIN users reporter ON reporter.User_ID = u.Reported_By
                       WHERE u.Disaster_ID = ? ORDER BY u.Observed_At DESC, u.Update_ID DESC', [$id]);
$warns = rows('SELECT Alert_Type, Signal_Level, Alert_DateTime, Message FROM early_warning WHERE Disaster_ID = ? ORDER BY Alert_DateTime', [$id]);
$dmg   = rows('SELECT m.Damage_Type, a.Area_Name, m.Description, m.Quantity, m.Estimated_Loss FROM damage m LEFT JOIN area a ON a.Area_ID = m.Area_ID WHERE m.Disaster_ID = ? ORDER BY m.Estimated_Loss DESC', [$id]);
$shel  = rows(
    'SELECT s.Shelter_Name, s.Services, s.Accessibility_Notes, s.Capacity, s.Capacity_Verified_At, verifier.Username AS Capacity_Verified_By, a.Area_Name,
            (SELECT COUNT(*) FROM shelter_assignment x WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID) AS families,
            CAST(COALESCE((SELECT SUM(p.Family_Size) FROM shelter_assignment x JOIN affected_person p ON p.Person_ID = x.Person_ID
                            WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID), 0) AS SIGNED) AS people,
            CAST(COALESCE((SELECT SUM(p.Family_Size) FROM shelter_assignment x JOIN affected_person p ON p.Person_ID = x.Person_ID
                            WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID AND x.Check_Out IS NULL), 0) AS SIGNED) AS now_here
       FROM disaster_shelter ds JOIN shelter s ON s.Shelter_ID = ds.Shelter_ID LEFT JOIN area a ON a.Area_ID = s.Area_ID
       LEFT JOIN users verifier ON verifier.User_ID = s.Capacity_Verified_By
      WHERE ds.Disaster_ID = ? ORDER BY s.Shelter_Name', [$id]);
$orgs  = rows('SELECT o.Org_Name, o.Org_Type, o.Contact FROM disaster_rescue_org r JOIN rescue_org o ON o.Org_ID = r.Org_ID WHERE r.Disaster_ID = ? ORDER BY o.Org_Name', [$id]);
$relief = rows(
    'SELECT d.Distribution_Date, i.Item_Name, d.Quantity, s.Shelter_Name, o.Org_Name
       FROM relief_distribution d JOIN relief_item i ON i.Relief_ID = d.Relief_ID JOIN shelter s ON s.Shelter_ID = d.Shelter_ID
       LEFT JOIN rescue_org o ON o.Org_ID = d.Org_ID WHERE d.Disaster_ID = ? ORDER BY d.Distribution_Date, d.Distribution_ID', [$id]);
$people = rows(
    'SELECT p.Name, p.Family_Size, p.Contact, s.Shelter_Name, x.Check_In, x.Check_Out
       FROM disaster_affected_person dp JOIN affected_person p ON p.Person_ID = dp.Person_ID
       LEFT JOIN shelter_assignment x ON x.Disaster_ID = dp.Disaster_ID AND x.Person_ID = dp.Person_ID
       LEFT JOIN shelter s ON s.Shelter_ID = x.Shelter_ID WHERE dp.Disaster_ID = ? ORDER BY p.Name', [$id]);
$tasks = rows(
    'SELECT t.Task_Title, t.Details, t.Status, t.Due_At, assignee.Username AS Assigned_To, creator.Username AS Created_By, t.Updated_At
       FROM incident_task t
       LEFT JOIN users assignee ON assignee.User_ID = t.Assigned_To
       JOIN users creator ON creator.User_ID = t.Created_By
      WHERE t.Disaster_ID = ?
      ORDER BY FIELD(t.Status, "Blocked", "In progress", "Open", "Done"), t.Due_At, t.Task_ID', [$id]);

$latest   = null;                       // latest warning that has a signal number
foreach ($warns as $w) {
    $latest = $w['Signal_Level'] !== null ? signal_info((int) $w['Signal_Level']) : $latest;
}
$track    = rows('SELECT Point_Time, Point_Type, Latitude, Longitude, Radius_Km, Wind_Speed_Kmh FROM disaster_track WHERE Disaster_ID = ? ORDER BY Point_Time', [$id]);
$hasRelief = scalar('SELECT COUNT(*) FROM relief_recipient r JOIN relief_distribution d ON d.Distribution_ID = r.Distribution_ID WHERE d.Disaster_ID = ?', [$id]) > 0;
$relief2  = $hasRelief ? relief_for_disaster($dis) : null;
$loss     = array_sum(array_map(fn($r) => (float) $r['Estimated_Loss'], $dmg));
$members  = array_sum(array_map(fn($r) => (int) $r['Family_Size'], $people));
$nowHere  = array_sum(array_map(fn($r) => (int) $r['now_here'], $shel));
$given    = array_sum(array_map(fn($r) => (int) $r['Quantity'], $relief));
$days     = (int) ((strtotime(date('Y-m-d')) - strtotime((string) $dis['Disaster_Date'])) / 86400);

function section(string $title, int $n): void
{
    echo '<h2>' . h($title) . ' <span class="muted small">(' . $n . ')</span></h2>';
}

page_start('Disaster report', 'disaster_report');
?>
<div class="live noprint">
  <form method="get" class="inline">
    <label>Disaster:
      <select name="id" data-autosubmit>
        <?php foreach ($all as $o): ?>
          <option value="<?= (int) $o['Disaster_ID'] ?>"<?= (int) $o['Disaster_ID'] === $id ? ' selected' : '' ?>><?= h($o['Disaster_Date'] . ' - ' . $o['Disaster_Type'] . ' (#' . $o['Disaster_ID'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
  <button class="btn btn-sm btn-ghost" type="button" data-print>Print report</button>
</div>

<section class="panel">
  <h2 class="big"><?= h($dis['Disaster_Type']) ?> - <?= h($dis['Disaster_Date']) ?> <span class="muted small">(<?= $days ?> days ago, report #<?= $id ?>)</span></h2>
  <p><?= h($dis['Description']) ?></p>
  <p><span class="badge"><?= h($dis['Status']) ?></span>
     <?php if ($dis['Incident_Lead'] !== null): ?>Incident lead: <b><?= h($dis['Incident_Lead']) ?></b>.<?php else: ?>No incident lead assigned.<?php endif; ?>
     <?php if ($latest !== null): ?>Latest warning signal: <span class="sig sig-<?= h($latest['cls']) ?>">Signal <?= (int) $latest['level'] ?> - <?= h($latest['label']) ?></span><?php endif; ?></p>
  <p class="muted small">
    Status changed: <?= h($dis['Status_Changed_At'] ?? 'not recorded') ?>
    <?php if ($dis['Status'] === 'Closed'): ?> | Closed: <?= h($dis['Closed_At'] ?? 'legacy close time not recorded') ?><?php endif; ?>
  </p>
  <div class="cards tight">
    <div class="card stat"><div class="num"><?= count($areas) ?></div><div class="lbl">Areas hit</div></div>
    <div class="card stat"><div class="num"><?= count($people) ?></div><div class="lbl">Families affected</div></div>
    <div class="card stat"><div class="num"><?= number_format($members) ?></div><div class="lbl">People affected</div></div>
    <div class="card stat"><div class="num"><?= number_format($nowHere) ?></div><div class="lbl">Still in shelters</div></div>
    <div class="card stat"><div class="num">Tk <?= number_format($loss) ?></div><div class="lbl">Estimated loss</div></div>
    <div class="card stat"><div class="num"><?= number_format($given) ?></div><div class="lbl">Relief units given</div></div>
  </div>
</section>

<div class="grid2 eq">
  <section class="panel"><?php section('Areas affected', count($areas)); ?>
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Area</th><th>District</th><th>Risk</th></tr></thead><tbody>
      <?php foreach ($areas as $r): ?><tr><td><?= h($r['Area_Name']) ?></td><td><?= h($r['Location']) ?></td><td><?= h($r['Risk_Level'] ?? '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
  <section class="panel"><?php section('Early warnings', count($warns)); ?>
    <?php foreach ($warns as $r): ?><p class="warn-item"><b><?= h($r['Alert_Type']) ?></b><?php if ($r['Signal_Level'] !== null): $si = signal_info((int) $r['Signal_Level']); ?> <span class="sig sig-<?= h($si['cls']) ?>">Signal <?= (int) $si['level'] ?></span><?php endif; ?> <span class="muted small"><?= h($r['Alert_DateTime']) ?></span><br><?= h($r['Message']) ?></p><?php endforeach; ?>
    <?php if (!$warns): ?><p class="muted">No warning was recorded.</p><?php endif; ?></section>
</div>

<?php if ($track): ?>
<section class="panel"><?php section('Disaster path', count($track)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Time</th><th>Type</th><th class="r">Latitude</th><th class="r">Longitude</th><th class="r">Impact radius (km)</th><th class="r">Wind (km/h)</th></tr></thead><tbody>
    <?php foreach ($track as $r): ?><tr><td><?= h($r['Point_Time']) ?></td><td><?= h($r['Point_Type']) ?></td><td class="r"><?= h($r['Latitude']) ?></td><td class="r"><?= h($r['Longitude']) ?></td><td class="r"><?= (int) $r['Radius_Km'] ?></td><td class="r"><?= h($r['Wind_Speed_Kmh'] ?? '-') ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <p class="muted small">Positions as recorded; the Live Report draws them on a map for Active and Under control incidents.</p></section>
<?php endif; ?>

<section class="panel"><?php section('Relief coverage by home area', $relief2 ? count($relief2['areas']) : 0); ?>
  <?php if ($relief2): ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Home area</th><th>Status</th><th class="r">People</th><th class="r">Received relief</th><th class="r">Not received</th><th class="r">Coverage</th></tr></thead><tbody>
    <?php foreach ($relief2['areas'] as $r): ?><tr><td><?= h($r['name']) ?><?= $r['flagged'] ? ' <span class="muted small">(flagged)</span>' : '' ?></td>
      <td><span class="sig sig-<?= h($r['rel']['cls']) ?>"><?= h($r['rel']['label']) ?></span></td><td class="r"><?= (int) $r['people'] ?></td><td class="r"><?= (int) $r['received'] ?></td><td class="r"><?= (int) $r['not_received'] ?></td><td class="r"><?= $r['rel']['pct'] === null ? '-' : (int) $r['rel']['pct'] . '%' ?></td></tr><?php endforeach; ?>
    <tr><td colspan="2"><b>All areas</b></td><td class="r"><b><?= (int) $relief2['people'] ?></b></td><td class="r"><b><?= (int) $relief2['received'] ?></b></td><td class="r"><b><?= (int) $relief2['not_received'] ?></b></td><td class="r"><b><?= $relief2['rel']['pct'] === null ? '-' : (int) $relief2['rel']['pct'] . '%' ?></b></td></tr>
  </tbody></table></div>
  <?php else: ?><p class="muted">No family-level relief records exist for this disaster (add them in Relief Recipient).</p><?php endif; ?>
</section>

<section class="panel"><?php section('Response tasks', count($tasks)); ?>
  <?php if ($tasks): ?>
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Task</th><th>Status</th><th>Owner</th><th>Due</th><th>Last updated</th></tr></thead><tbody>
      <?php foreach ($tasks as $task): ?>
        <tr>
          <td><b><?= h($task['Task_Title']) ?></b><?php if ($task['Details']): ?><br><span class="muted small"><?= h($task['Details']) ?></span><?php endif; ?></td>
          <td><?= h($task['Status']) ?></td>
          <td><?= h($task['Assigned_To'] ?? 'Unassigned') ?></td>
          <td><?= h($task['Due_At'] ?? '-') ?></td>
          <td><?= h($task['Updated_At']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="muted">No response tasks have been recorded for this incident.</p><?php endif; ?>
  <?php if (can_edit()): ?><p><a class="btn btn-sm btn-ghost" href="table.php?t=incident_task&amp;col=Disaster_ID&amp;q=<?= $id ?>">Manage response tasks</a></p><?php endif; ?>
</section>

<section class="panel" id="field-updates"><?php section('Field updates', count($fieldUpdates)); ?>
  <?php if (can_edit()): ?>
    <div class="field-update-entry noprint">
      <h3>Record an update from the field</h3>
      <p class="muted small">Online submission only. Do not enter sensitive personal details; updates are visible to signed-in users.</p>
      <?php if ($fieldError !== ''): ?><div class="alert alert-err"><?= h($fieldError) ?></div><?php endif; ?>
      <form method="post" action="disaster.php?id=<?= $id ?>#field-updates">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <input type="hidden" name="op" value="field_update">
        <div class="field-update-form">
          <label class="fld"><span>Category <em>*</em></span>
            <select name="Update_Type" required>
              <?php foreach (['Situation', 'Shelter', 'Medical', 'Supplies', 'Safety'] as $type): ?>
                <option value="<?= h($type) ?>"<?= ($fieldValues['Update_Type'] ?? 'Situation') === $type ? ' selected' : '' ?>><?= h($type) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="fld"><span>Affected area</span>
            <select name="Area_ID"><option value="">Not specified</option>
              <?php foreach ($areas as $area): ?>
                <option value="<?= (int) $area['Area_ID'] ?>"<?= (string) ($fieldValues['Area_ID'] ?? '') === (string) $area['Area_ID'] ? ' selected' : '' ?>><?= h($area['Area_Name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="fld"><span>Observed at <em>*</em></span><input type="datetime-local" name="Observed_At" required value="<?= h($fieldValues['Observed_At'] ?? date('Y-m-d\TH:i')) ?>"></label>
          <label class="fld wide"><span>Summary <em>*</em></span><input name="Summary" maxlength="160" required value="<?= h($fieldValues['Summary'] ?? '') ?>"></label>
          <label class="fld wide"><span>Details <em>*</em></span><textarea name="Details" rows="4" maxlength="5000" required><?= h($fieldValues['Details'] ?? '') ?></textarea></label>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">Submit field update</button></div>
      </form>
    </div>
  <?php endif; ?>
  <?php if ($fieldUpdates): ?>
    <div class="field-update-list">
      <?php foreach ($fieldUpdates as $update): ?>
        <article class="field-update">
          <div class="field-update-meta"><b><?= h($update['Update_Type']) ?></b><time><?= h($update['Observed_At']) ?></time></div>
          <h3><?= h($update['Summary']) ?></h3>
          <p><?= nl2br(h($update['Details'])) ?></p>
          <div class="muted small"><?= h($update['Area_Name'] ?? 'Area not specified') ?> · Reported by <?= h($update['Reported_By']) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?><p class="muted">No field updates have been recorded for this incident.</p><?php endif; ?>
</section>

<section class="panel"><?php section('Damage', count($dmg)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Type</th><th>Area</th><th>Description</th><th class="r">Quantity</th><th class="r">Loss (Tk)</th></tr></thead><tbody>
    <?php foreach ($dmg as $r): ?><tr><td><?= h($r['Damage_Type']) ?></td><td><?= h($r['Area_Name'] ?? '-') ?></td><td><?= h($r['Description']) ?></td><td class="r"><?= number_format((int) $r['Quantity']) ?></td><td class="r"><?= number_format((float) $r['Estimated_Loss']) ?></td></tr><?php endforeach; ?>
    <?php if ($dmg): ?><tr><td colspan="4"><b>Total</b></td><td class="r"><b><?= number_format($loss) ?></b></td></tr><?php endif; ?>
  </tbody></table></div></section>

<section class="panel"><?php section('Shelters used', count($shel)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Shelter / services</th><th>Area</th><th class="r">Capacity</th><th class="r">Families hosted</th><th class="r">People hosted</th><th class="r">Staying now</th><th>Capacity last confirmed</th></tr></thead><tbody>
    <?php foreach ($shel as $r): ?><tr><td><b><?= h($r['Shelter_Name']) ?></b>
      <?php if ($r['Services']): ?><br><span class="muted small">Services: <?= h($r['Services']) ?></span><?php endif; ?>
      <?php if ($r['Accessibility_Notes']): ?><br><span class="muted small">Accessibility: <?= h($r['Accessibility_Notes']) ?></span><?php endif; ?>
    </td><td><?= h($r['Area_Name'] ?? '-') ?></td><td class="r"><?= number_format((int) $r['Capacity']) ?></td><td class="r"><?= (int) $r['families'] ?></td><td class="r"><?= (int) $r['people'] ?></td><td class="r"><?= (int) $r['now_here'] ?></td>
      <td><?= h($r['Capacity_Verified_At'] ?? 'Not confirmed') ?><?= $r['Capacity_Verified_By'] ? ' by ' . h($r['Capacity_Verified_By']) : '' ?></td></tr><?php endforeach; ?>
  </tbody></table></div></section>

<div class="grid2 eq">
  <section class="panel"><?php section('Rescue organisations', count($orgs)); ?>
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Organisation</th><th>Type</th><th>Contact</th></tr></thead><tbody>
      <?php foreach ($orgs as $r): ?><tr><td><?= h($r['Org_Name']) ?></td><td><?= h($r['Org_Type'] ?? '-') ?></td><td><?= h($r['Contact'] ?? '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
  <section class="panel"><?php section('Relief given out', count($relief)); ?>
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Item</th><th class="r">Qty</th><th>Shelter</th><th>By</th></tr></thead><tbody>
      <?php foreach ($relief as $r): ?><tr><td><?= h($r['Distribution_Date']) ?></td><td><?= h($r['Item_Name']) ?></td><td class="r"><?= number_format((int) $r['Quantity']) ?></td><td><?= h($r['Shelter_Name']) ?></td><td><?= h($r['Org_Name'] ?? '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
</div>

<section class="panel"><?php section('Affected families', count($people)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Name</th><th class="r">Family</th><th>Contact</th><th>Shelter</th><th>Check in</th><th>Check out</th></tr></thead><tbody>
    <?php foreach ($people as $r): ?><tr><td><?= h($r['Name']) ?></td><td class="r"><?= (int) $r['Family_Size'] ?></td><td><?= h($r['Contact'] ?? '-') ?></td><td><?= h($r['Shelter_Name'] ?? 'not in a shelter') ?></td><td><?= h($r['Check_In'] ?? '-') ?></td><td><?= $r['Shelter_Name'] === null ? '-' : h($r['Check_Out'] ?? 'staying') ?></td></tr><?php endforeach; ?>
  </tbody></table></div></section>
<?php page_end(); ?>
