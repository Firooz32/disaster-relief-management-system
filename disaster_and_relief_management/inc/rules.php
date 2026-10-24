<?php
/** Business rules that foreign keys and CHECK constraints cannot express. Used by table.php before every add / edit. */
declare(strict_types=1);

class RuleViolation extends Exception
{
}

/**
 * @param array<string,mixed> $f  submitted form values (column => text)
 * @param array<string,string>|null $pk  key of the row being edited (null when adding)
 * @return string|null a plain-language error, or null when the row is fine
 */
function check_rules(string $t, array $f, ?array $pk): ?string
{
    $v = fn(string $k): string => trim((string) ($f[$k] ?? ''));

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
        $stock = (int) scalar('SELECT Quantity FROM relief_item WHERE Relief_ID = ?', [$r]);
        $given = (int) scalar('SELECT COALESCE(SUM(Quantity), 0) FROM relief_distribution WHERE Relief_ID = ? AND Distribution_ID <> ?', [$r, $self]);
        if ($given + $qty > $stock) {
            return 'Not enough stock: only ' . max(0, $stock - $given) . " left, but $qty were entered.";
        }
    }

    return null;
}
