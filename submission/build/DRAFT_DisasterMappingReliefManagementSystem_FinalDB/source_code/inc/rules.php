<?php
/** Business rules that foreign keys and CHECK constraints cannot express. Used by table.php before every add / edit. */
declare(strict_types=1);

class RuleViolation extends Exception
{
}

function field_update_rules(int $disasterId, array $fields): ?string
{
    $type = trim((string) ($fields['Update_Type'] ?? ''));
    $summary = trim((string) ($fields['Summary'] ?? ''));
    $details = trim((string) ($fields['Details'] ?? ''));
    $observedAt = trim((string) ($fields['Observed_At'] ?? ''));
    $areaId = trim((string) ($fields['Area_ID'] ?? ''));

    if (!in_array($type, ['Situation', 'Shelter', 'Medical', 'Supplies', 'Safety'], true)) {
        return 'Choose a valid update category.';
    }
    if ($summary === '' || mb_strlen($summary) > 160) {
        return 'Enter a short summary of up to 160 characters.';
    }
    if ($details === '' || mb_strlen($details) > 5000) {
        return 'Enter field details of up to 5,000 characters.';
    }
    $observed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $observedAt);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if ($observed === false || $observed->format('Y-m-d\TH:i') !== $observedAt
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
        return 'Enter a valid observation date and time.';
    }
    if ($areaId !== '' && (!ctype_digit($areaId)
        || row1('SELECT Area_ID FROM disaster_area WHERE Disaster_ID = ? AND Area_ID = ? FOR UPDATE', [$disasterId, (int) $areaId]) === null)) {
        return 'Choose an area affected by this incident, or leave the area blank.';
    }
    return null;
}

/**
 * Locks the rows that limit a record, so two people saving at the same moment cannot both pass the capacity / stock check.
 * Call it inside a transaction, before check_rules(); the locks are released by commit / rollback.
 * Lock order is always person, then shelter / item, so two saves cannot block each other forever.
 */
function lock_for_rules(string $t, array $f): void
{
    if ($t === 'shelter_assignment') {
        q('SELECT Person_ID FROM affected_person WHERE Person_ID = ? FOR UPDATE', [(int) ($f['Person_ID'] ?? 0)]);
        q('SELECT Shelter_ID FROM shelter WHERE Shelter_ID = ? FOR UPDATE', [(int) ($f['Shelter_ID'] ?? 0)]);
    } elseif ($t === 'relief_distribution') {
        q('SELECT Relief_ID FROM relief_item WHERE Relief_ID = ? FOR UPDATE', [(int) ($f['Relief_ID'] ?? 0)]);
        if ((int) ($f['Batch_ID'] ?? 0) > 0) {
            q('SELECT Batch_ID FROM relief_batch WHERE Batch_ID = ? FOR UPDATE', [(int) $f['Batch_ID']]);
        }
    }
}

/**
 * @param array<string,mixed> $f  submitted form values (column => text)
 * @param array<string,string>|null $pk  key of the row being edited (null when adding)
 * @return string|null a plain-language error, or null when the row is fine
 */
function check_rules(string $t, array $f, ?array $pk): ?string
{
    $v = fn(string $k): string => trim((string) ($f[$k] ?? ''));

    if ($t === 'disaster') {
        $next = $v('Status');
        if ($pk === null) {
            return $next === 'Reported' ? null : 'New incidents must start in Reported status.';
        }
        $current = row1('SELECT Status FROM disaster WHERE Disaster_ID = ?', [(int) ($pk['Disaster_ID'] ?? 0)]);
        if ($current === null) {
            return 'That incident no longer exists.';
        }
        $from = (string) $current['Status'];
        if ($next === $from) {
            return null;
        }
        $allowed = [
            'Reported' => ['Active'],
            'Active' => ['Under control', 'Closed'],
            'Under control' => ['Active', 'Closed'],
            'Closed' => is_admin() ? ['Active'] : [],
        ];
        if (!in_array($next, $allowed[$from] ?? [], true)) {
            return $from === 'Closed' ? 'Only an administrator can reopen a closed incident.' : 'That status transition is not allowed.';
        }
    }

    if ($t === 'incident_task') {
        $assigned = $v('Assigned_To');
        if ($assigned !== '' && !scalar("SELECT COUNT(*) FROM users WHERE User_ID = ? AND Role IN ('admin','editor')", [(int) $assigned])) {
            return 'Assign the task to an administrator or editor who can update its status.';
        }
    }

    if ($t === 'shelter_assignment') {
        $d = (int) $v('Disaster_ID');
        $p = (int) $v('Person_ID');
        $s = (int) $v('Shelter_ID');
        if (!scalar('SELECT COUNT(*) FROM disaster_shelter WHERE Disaster_ID = ? AND Shelter_ID = ?', [$d, $s])) {
            return 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
        }
        if (!scalar('SELECT COUNT(*) FROM disaster_affected_person WHERE Disaster_ID = ? AND Person_ID = ?', [$d, $p])) {
            return 'That person is not recorded as affected by this disaster. Add them in "Disaster Affected Person" first.';
        }
        if ($v('Check_Out') === '') {                                   // an empty Check_Out means "staying now"
            $self = (int) ($pk['Assignment_ID'] ?? 0);
            if (scalar('SELECT COUNT(*) FROM shelter_assignment WHERE Person_ID = ? AND Check_Out IS NULL AND Assignment_ID <> ?', [$p, $self])) {
                return 'This person is already staying in a shelter. Enter a Check Out date on the old record first.';
            }
            $cap  = (int) scalar('SELECT Capacity FROM shelter WHERE Shelter_ID = ?', [$s]);
            $used = (int) scalar('SELECT COALESCE(SUM(p.Family_Size), 0) FROM shelter_assignment a JOIN affected_person p ON p.Person_ID = a.Person_ID
                                   WHERE a.Shelter_ID = ? AND a.Check_Out IS NULL AND a.Assignment_ID <> ?', [$s, $self]);
            $need = (int) scalar('SELECT Family_Size FROM affected_person WHERE Person_ID = ?', [$p]);
            if ($used + $need > $cap) {
                return "The shelter would be over capacity: $used staying now + $need new > $cap places.";
            }
        }
    }

    if ($t === 'relief_recipient') {
        $dist = (int) $v('Distribution_ID');
        $p    = (int) $v('Person_ID');
        if (!scalar('SELECT COUNT(*) FROM relief_distribution x JOIN disaster_affected_person dp ON dp.Disaster_ID = x.Disaster_ID
                      WHERE x.Distribution_ID = ? AND dp.Person_ID = ?', [$dist, $p])) {
            return 'That person is not recorded as affected by the disaster of this distribution. Add them in "Disaster Affected Person" first.';
        }
    }

    if ($t === 'relief_distribution') {
        $d   = (int) $v('Disaster_ID');
        $r   = (int) $v('Relief_ID');
        $batch = (int) $v('Batch_ID');
        $s   = (int) $v('Shelter_ID');
        $o   = (int) $v('Org_ID');
        $qty = (int) $v('Quantity');
        if (!scalar('SELECT COUNT(*) FROM disaster_shelter WHERE Disaster_ID = ? AND Shelter_ID = ?', [$d, $s])) {
            return 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
        }
        if ($o > 0) {
            if (!scalar('SELECT COUNT(*) FROM disaster_rescue_org WHERE Disaster_ID = ? AND Org_ID = ?', [$d, $o])) {
                return 'That organisation did not take part in this disaster. Add it in "Disaster Rescue Org" first.';
            }
            if (!scalar('SELECT COUNT(*) FROM rescue_org_relief_item WHERE Org_ID = ? AND Relief_ID = ?', [$o, $r])) {
                return 'That organisation does not supply this relief item (see "Rescue Org Relief Item").';
            }
        }
        $self  = (int) ($pk['Distribution_ID'] ?? 0);
        if ($self > 0 && scalar('SELECT COUNT(*) FROM relief_recipient r WHERE r.Distribution_ID = ?
                                  AND NOT EXISTS (SELECT 1 FROM disaster_affected_person dp WHERE dp.Disaster_ID = ? AND dp.Person_ID = r.Person_ID)', [$self, $d])) {
            return 'Some families that received this distribution are not affected by that disaster. Fix "Relief Recipient" first.';
        }
        $batchRow = row1('SELECT Relief_ID, Quantity_Received, Expiry_Date FROM relief_batch WHERE Batch_ID = ?', [$batch]);
        if ($batchRow === null || (int) $batchRow['Relief_ID'] !== $r) {
            return 'Choose a stock batch for the selected relief item.';
        }
        if ($batchRow['Expiry_Date'] !== null && $batchRow['Expiry_Date'] < date('Y-m-d')) {
            return 'This stock batch has expired and cannot be dispatched.';
        }
        $batchUsed = (int) scalar('SELECT COALESCE(SUM(Quantity), 0) FROM relief_distribution WHERE Batch_ID = ? AND Distribution_ID <> ?', [$batch, $self]);
        if ($batchUsed + $qty > (int) $batchRow['Quantity_Received']) {
            return 'Not enough stock in this batch: only ' . max(0, (int) $batchRow['Quantity_Received'] - $batchUsed) . " left, but $qty were entered.";
        }
        $stock = (int) scalar('SELECT Quantity FROM relief_item WHERE Relief_ID = ?', [$r]);
        $given = (int) scalar('SELECT COALESCE(SUM(Quantity), 0) FROM relief_distribution WHERE Relief_ID = ? AND Distribution_ID <> ?', [$r, $self]);
        if ($given + $qty > $stock) {
            return 'Not enough stock: only ' . max(0, $stock - $given) . " left, but $qty were entered.";
        }
    }

    return null;
}
