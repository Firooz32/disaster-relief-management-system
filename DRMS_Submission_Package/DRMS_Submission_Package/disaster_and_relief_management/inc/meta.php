<?php
/** Table / column information read straight from the database, so every table works without extra code. */
declare(strict_types=1);

/** Never listed, searched or edited through the generic table pages (users has its own admin page). */
const HIDDEN_TABLES = ['users'];
/** Listed and searchable, but nobody can add / edit / delete rows by hand. */
const READONLY_TABLES = ['audit_log', 'incident_update', 'relief_batch'];
/** Set by the application when an incident moves through its lifecycle. */
const SYSTEM_MANAGED_COLUMNS = ['Status_Changed_At', 'Closed_At', 'Capacity_Verified_At', 'Capacity_Verified_By', 'Created_At', 'Updated_At'];

function is_system_managed(string $table, string $column): bool
{
    return in_array($column, SYSTEM_MANAGED_COLUMNS, true)
        || ($table === 'relief_item' && $column === 'Quantity');
}

/** Only an administrator may delete rows of these tables (deleting a disaster removes everything recorded under it). */
const ADMIN_DELETE_TABLES = ['disaster'];

function can_delete(string $t): bool
{
    return can_edit() && (!in_array($t, ADMIN_DELETE_TABLES, true) || is_admin());
}

/** table => list of column rows (Field, Type, Null, Key, Default, Extra). One query for the whole schema. */
function schema(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        $sql = 'SELECT TABLE_NAME AS tn, COLUMN_NAME AS Field, COLUMN_TYPE AS Type, IS_NULLABLE AS `Null`,
                       COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra
                  FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 ORDER BY TABLE_NAME, ORDINAL_POSITION';
        foreach (rows($sql) as $r) {
            $s[(string) $r['tn']][] = $r;
        }
    }
    return $s;
}

function all_tables(): array
{
    static $t = null;
    if ($t === null) {
        $t = array_values(array_diff(array_keys(schema()), HIDDEN_TABLES));
        sort($t, SORT_STRING | SORT_FLAG_CASE);
    }
    return $t;
}

function table_cols(string $t): array
{
    return schema()[$t] ?? [];
}

function pk_cols(string $t): array
{
    $out = [];
    foreach (table_cols($t) as $c) {
        if ($c['Key'] === 'PRI') {
            $out[] = $c['Field'];
        }
    }
    return $out;
}

/** A relationship table = a primary key of two or more columns that are all foreign keys (extra fields such as disaster_area.Relief_Shortage are allowed). */
function is_junction(string $t): bool
{
    $pks = pk_cols($t);
    return count($pks) >= 2 && !array_diff($pks, array_keys(fk_map($t)));
}

/** All foreign keys of the database, read once: ['fk' => table => col => [rt, rc], 'child' => parent table => list of referencing columns]. */
function fk_all(): array
{
    static $m = null;
    if ($m === null) {
        $m = ['fk' => [], 'child' => []];
        $sql = 'SELECT k.TABLE_NAME AS tn, k.COLUMN_NAME AS col, k.REFERENCED_TABLE_NAME AS rt,
                       k.REFERENCED_COLUMN_NAME AS rc, r.DELETE_RULE AS dr
                  FROM information_schema.KEY_COLUMN_USAGE k
                  JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                    ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
                 WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL';
        foreach (rows($sql) as $r) {
            $m['fk'][(string) $r['tn']][(string) $r['col']] = [(string) $r['rt'], (string) $r['rc']];
            $m['child'][(string) $r['rt']][] = ['table' => (string) $r['tn'], 'col' => (string) $r['col'], 'rc' => (string) $r['rc'], 'rule' => (string) $r['dr']];
        }
    }
    return $m;
}

/** column => [referenced table, referenced column] */
function fk_map(string $t): array
{
    return fk_all()['fk'][$t] ?? [];
}

/** Text-like columns used as readable search values when another table references this table. */
function related_search_columns(string $t): array
{
    $out = [];
    foreach (table_cols($t) as $c) {
        $type = strtolower((string) $c['Type']);
        if ($c['Key'] !== 'PRI' && preg_match('/^(char|varchar|tinytext|text|mediumtext|longtext|enum)\b/', $type)) {
            $out[] = (string) $c['Field'];
        }
    }
    return $out;
}

/**
 * Search direct values and readable labels from referenced rows. Identifiers come only from database schema metadata.
 * Returns a WHERE clause without the WHERE keyword and the matching parameter list.
 *
 * @return array{0:string,1:array<int,string>}
 */
function table_search_condition(string $t, string $query, string $column = ''): array
{
    $columns = array_column(table_cols($t), 'Field');
    $use = $column !== '' && in_array($column, $columns, true) ? [$column] : $columns;
    $pattern = '%' . addcslashes($query, '%_\\') . '%';
    $terms = [];
    $params = [];
    $fks = fk_map($t);
    $relatedIndex = 0;

    foreach ($use as $name) {
        $terms[] = 'CAST(`' . $name . '` AS CHAR) LIKE ?';
        $params[] = $pattern;
        if (!isset($fks[$name])) {
            continue;
        }
        [$referencedTable, $referencedColumn] = $fks[$name];
        if (in_array($referencedTable, HIDDEN_TABLES, true)) {
            continue;
        }
        $labelColumns = related_search_columns($referencedTable);
        if (!$labelColumns) {
            continue;
        }
        $alias = 'search_fk_' . $relatedIndex++;
        $labels = [];
        foreach ($labelColumns as $labelColumn) {
            $labels[] = 'CAST(`' . $alias . '`.`' . $labelColumn . '` AS CHAR) LIKE ?';
            $params[] = $pattern;
        }
        $terms[] = 'EXISTS (SELECT 1 FROM `' . $referencedTable . '` AS `' . $alias . '`'
            . ' WHERE `' . $alias . '`.`' . $referencedColumn . '` = `' . $t . '`.`' . $name . '`'
            . ' AND (' . implode(' OR ', $labels) . '))';
    }

    return [$terms ? '(' . implode(' OR ', $terms) . ')' : '1 = 0', $params];
}

/** Columns of other tables that point at $t, with their ON DELETE rule. */
function child_refs(string $t): array
{
    return fk_all()['child'][$t] ?? [];
}

/** Values of an enum('a','b') column type. */
function enum_values(string $type): array
{
    preg_match_all("/'((?:[^']|'')*)'/", $type, $m);
    return array_map(fn($v) => str_replace("''", "'", $v), $m[1]);
}

const FK_DROPDOWN_MAX = 1000;

/**
 * value => "value - readable label" for a referenced table (used in dropdowns and cells).
 * Returns null for very large tables: callers then fall back to a plain number input / the raw id.
 */
function fk_options(string $rt, string $rc): ?array
{
    static $cache = [];
    $k = $rt . '.' . $rc;
    if (array_key_exists($k, $cache)) {
        return $cache[$k];
    }
    if (scalar("SELECT COUNT(*) FROM `$rt`") > FK_DROPDOWN_MAX) {
        return $cache[$k] = null;
    }
    if ($rt === 'relief_batch' && $rc === 'Batch_ID') {
        $out = [];
        foreach (rows(
            "SELECT b.Batch_ID AS v,
                    CONCAT(b.Batch_Code, ' - ', i.Item_Name, ' (', b.Quantity_Received -
                      (SELECT COALESCE(SUM(d.Quantity), 0) FROM relief_distribution d WHERE d.Batch_ID = b.Batch_ID),
                      ' available; expires ', COALESCE(b.Expiry_Date, 'not recorded'), ')') AS l
               FROM relief_batch b JOIN relief_item i ON i.Relief_ID = b.Relief_ID ORDER BY b.Batch_ID"
        ) as $r) {
            $out[(string) $r['v']] = (string) $r['l'];
        }
        return $cache[$k] = $out;
    }
    $name = null;
    $date = null;
    foreach (table_cols($rt) as $c) {
        if ($name === null && $c['Key'] !== 'PRI' && stripos((string) $c['Type'], 'varchar') === 0) {
            $name = $c['Field'];
        }
        if ($date === null && strcasecmp((string) $c['Type'], 'date') === 0) {
            $date = $c['Field'];
        }
    }
    $sel = '`' . $rc . '` AS v';
    if ($name) {
        $sel .= ', `' . $name . '` AS l';
    }
    if ($date) {
        $sel .= ', `' . $date . '` AS d';
    }
    $out = [];
    foreach (rows("SELECT $sel FROM `$rt` ORDER BY `$rc`") as $r) {
        $label = (string) $r['v'];
        if ($name) {
            $label .= ' - ' . $r['l'];
        }
        if ($date) {
            $label .= ' (' . $r['d'] . ')';
        }
        $out[(string) $r['v']] = $label;
    }
    return $cache[$k] = $out;
}
