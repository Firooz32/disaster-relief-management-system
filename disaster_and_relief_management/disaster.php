<?php
/** Situation report for one disaster: everything the database knows about it on a single printable page. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
guard();

$all = rows('SELECT Disaster_ID, Disaster_Type, Disaster_Date FROM disaster ORDER BY Disaster_Date DESC, Disaster_ID DESC');
$id  = (int) ($_GET['id'] ?? ($all[0]['Disaster_ID'] ?? 0));
$dis = row1('SELECT * FROM disaster WHERE Disaster_ID = ?', [$id]);
if ($dis === null) {
    flash('err', 'That disaster was not found.');
    header('Location: dashboard.php');
    exit;
}

$areas = rows('SELECT a.Area_Name, a.Location, a.Risk_Level FROM disaster_area da JOIN area a ON a.Area_ID = da.Area_ID WHERE da.Disaster_ID = ? ORDER BY a.Area_Name', [$id]);
$warns = rows('SELECT Alert_Type, Signal_Level, Alert_DateTime, Message FROM early_warning WHERE Disaster_ID = ? ORDER BY Alert_DateTime', [$id]);
$dmg   = rows('SELECT m.Damage_Type, a.Area_Name, m.Description, m.Quantity, m.Estimated_Loss FROM damage m LEFT JOIN area a ON a.Area_ID = m.Area_ID WHERE m.Disaster_ID = ? ORDER BY m.Estimated_Loss DESC', [$id]);
$shel  = rows(
    'SELECT s.Shelter_Name, a.Area_Name, s.Capacity,
            (SELECT COUNT(*) FROM shelter_assignment x WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID) AS families,
            CAST(COALESCE((SELECT SUM(p.Family_Size) FROM shelter_assignment x JOIN affected_person p ON p.Person_ID = x.Person_ID
                            WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID), 0) AS SIGNED) AS people,
            CAST(COALESCE((SELECT SUM(p.Family_Size) FROM shelter_assignment x JOIN affected_person p ON p.Person_ID = x.Person_ID
                            WHERE x.Disaster_ID = ds.Disaster_ID AND x.Shelter_ID = s.Shelter_ID AND x.Check_Out IS NULL), 0) AS SIGNED) AS now_here
       FROM disaster_shelter ds JOIN shelter s ON s.Shelter_ID = ds.Shelter_ID LEFT JOIN area a ON a.Area_ID = s.Area_ID
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
     <?php if ($latest !== null): ?>Latest warning signal: <span class="sig sig-<?= h($latest['cls']) ?>">Signal <?= (int) $latest['level'] ?> - <?= h($latest['label']) ?></span><?php endif; ?></p>
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
  <p class="muted small">Positions as recorded; the Live Report draws them on a map for Active disasters.</p></section>
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

<section class="panel"><?php section('Damage', count($dmg)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Type</th><th>Area</th><th>Description</th><th class="r">Quantity</th><th class="r">Loss (Tk)</th></tr></thead><tbody>
    <?php foreach ($dmg as $r): ?><tr><td><?= h($r['Damage_Type']) ?></td><td><?= h($r['Area_Name'] ?? '-') ?></td><td><?= h($r['Description']) ?></td><td class="r"><?= number_format((int) $r['Quantity']) ?></td><td class="r"><?= number_format((float) $r['Estimated_Loss']) ?></td></tr><?php endforeach; ?>
    <?php if ($dmg): ?><tr><td colspan="4"><b>Total</b></td><td class="r"><b><?= number_format($loss) ?></b></td></tr><?php endif; ?>
  </tbody></table></div></section>

<section class="panel"><?php section('Shelters used', count($shel)); ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Shelter</th><th>Area</th><th class="r">Capacity</th><th class="r">Families hosted</th><th class="r">People hosted</th><th class="r">Staying now</th></tr></thead><tbody>
    <?php foreach ($shel as $r): ?><tr><td><?= h($r['Shelter_Name']) ?></td><td><?= h($r['Area_Name'] ?? '-') ?></td><td class="r"><?= number_format((int) $r['Capacity']) ?></td><td class="r"><?= (int) $r['families'] ?></td><td class="r"><?= (int) $r['people'] ?></td><td class="r"><?= (int) $r['now_here'] ?></td></tr><?php endforeach; ?>
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
