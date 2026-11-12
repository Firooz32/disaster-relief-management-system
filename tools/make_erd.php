<?php
/**
 * Draws the ERD and writes the relational schema from a SCRATCH database loaded from submission/database/*.sql:
 *     DRMS_DB_NAME=drms_sample_check php tools/make_erd.php
 * Output (submission/diagrams/): ERD_Full.html, ERD_Overview.html (Mermaid, rendered to PNG/PDF by tools/render_erd.ps1)
 * and Relational_Schema.md. Everything is read from information_schema, so the drawing cannot drift from the schema.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__ . '/../disaster_and_relief_management/db.php';
if (cfg('name') === 'disaster_and_relief_management') {
    fwrite(STDERR, "Use a scratch copy (DRMS_DB_NAME).\n");
    exit(1);
}

$cols = [];
foreach (rows('SELECT TABLE_NAME t, COLUMN_NAME c, DATA_TYPE dt, COLUMN_TYPE ct, IS_NULLABLE n, COLUMN_KEY k FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION') as $r) {
    $cols[$r['t']][] = $r;
}
$fks = rows('SELECT k.TABLE_NAME t, k.COLUMN_NAME c, k.REFERENCED_TABLE_NAME rt, k.REFERENCED_COLUMN_NAME rc, r.DELETE_RULE dr
               FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                 ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
              WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME, k.COLUMN_NAME');
$fkOf = [];
foreach ($fks as $f) {
    $fkOf[$f['t']][$f['c']] = $f;
}
$uniq = [];
foreach (rows("SELECT TABLE_NAME t, INDEX_NAME i, GROUP_CONCAT(COALESCE(COLUMN_NAME, EXPRESSION) ORDER BY SEQ_IN_INDEX) cs FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND NON_UNIQUE = 0 AND INDEX_NAME <> 'PRIMARY' GROUP BY TABLE_NAME, INDEX_NAME") as $u) {
    $uniq[$u['t']][] = $u['cs'];
}
$checks = rows('SELECT tc.TABLE_NAME t, cc.CHECK_CLAUSE x FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc
                  ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                 WHERE tc.TABLE_SCHEMA = DATABASE() AND tc.CONSTRAINT_TYPE = \'CHECK\' ORDER BY tc.TABLE_NAME');
$checkOf = [];
foreach ($checks as $c) {
    $checkOf[$c['t']][] = $c['x'];
}

/** Mermaid ER source. $full = with attributes. */
function mermaid(array $cols, array $fks, array $fkOf, bool $full): string
{
    $m = "erDiagram\n";
    foreach ($fks as $f) {
        $nullable = false;
        foreach ($cols[$f['t']] as $c) {
            $nullable = $nullable || ($c['c'] === $f['c'] && $c['n'] === 'YES');
        }
        // parent side: exactly one (||) or zero-or-one (|o) when the FK may be empty; child side: zero or many
        $label = $full ? $f['c'] . ' ' . $f['dr'] : $f['dr'];
        $m .= sprintf("    %s %s--o{ %s : \"%s\"\n", $f['rt'], $nullable ? '|o' : '||', $f['t'], $label);
    }
    foreach ($cols as $t => $cs) {
        if (!$full) {
            $m .= "    $t\n";                     // overview: entity boxes only
            continue;
        }
        $m .= "    $t {\n";
        foreach ($cs as $c) {
            $tags = [];
            if ($c['k'] === 'PRI') {
                $tags[] = 'PK';
            }
            if (isset($fkOf[$t][$c['c']])) {
                $tags[] = 'FK';
            }
            if ($c['k'] === 'UNI') {
                $tags[] = 'UK';
            }
            $m .= '        ' . $c['dt'] . ' ' . $c['c'] . ($tags ? ' ' . implode(', ', $tags) : '') . ($c['n'] === 'NO' && $c['k'] !== 'PRI' ? ' "NOT NULL"' : '') . "\n";
        }
        $m .= "    }\n";
    }
    return $m;
}

function page(string $title, string $src, int $width): string
{
    return "<!DOCTYPE html>\n<html lang=\"en\"><head><meta charset=\"utf-8\"><title>$title</title>\n"
        . "<style>body{margin:0;background:#fff;font-family:Segoe UI,Arial,sans-serif}h1{font-size:28px;margin:18px 24px 0}p{margin:4px 24px 0;color:#444}"
        . ".mermaid{width:{$width}px;padding:12px}</style></head><body>\n<h1>$title</h1>\n"
        . "<p>Disaster Mapping &amp; Relief Management System - 25 tables. ||--o{ one-to-many (parent required), |o--o{ optional parent. Line label: foreign key and its ON DELETE rule.</p>\n"
        . "<pre class=\"mermaid\">\n" . htmlspecialchars($src) . "</pre>\n"
        . "<script type=\"module\">\n"
        . "import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11.6.0/dist/mermaid.esm.min.mjs';\n"
        . "import elk from 'https://cdn.jsdelivr.net/npm/@mermaid-js/layout-elk@0.1.7/dist/mermaid-layout-elk.esm.min.mjs';\n"
        . "mermaid.registerLayoutLoaders(elk);\n"
        . "mermaid.initialize({startOnLoad:true, theme:'default', layout:'elk', elk:{mergeEdges:false, nodePlacementStrategy:'NETWORK_SIMPLEX'}, er:{useMaxWidth:false, fontSize:14}, maxTextSize:200000});\n"
        . "</script>\n"
        . "</body></html>\n";
}

$dir = __DIR__ . '/../submission/diagrams';
@mkdir($dir, 0777, true);
file_put_contents("$dir/ERD_Full.html", page('Entity-Relationship Diagram (full, with attributes)', mermaid($cols, $fks, $fkOf, true), 6000));
file_put_contents("$dir/ERD_Overview.html", page('Entity-Relationship Diagram (overview)', mermaid($cols, $fks, $fkOf, false), 3400));

// relational schema in text form
$rules = array_count_values(array_column($fks, 'dr'));
$md = "# Relational Schema\n\nGenerated from the database by `tools/make_erd.php` (do not edit by hand).\n\n"
    . count($cols) . ' tables, ' . count($fks) . ' foreign keys (' . implode(', ', array_map(fn($k, $v) => "$v ON DELETE $k", array_keys($rules), $rules))
    . '), ' . count($checks) . " CHECK constraints, 8 triggers. Notation: underlined = primary key, *italic* = foreign key.\n\n";
foreach ($cols as $t => $cs) {
    $parts = [];
    foreach ($cs as $c) {
        $n = $c['c'];
        if ($c['k'] === 'PRI') {
            $n = "<u>$n</u>";
        }
        if (isset($fkOf[$t][$c['c']])) {
            $n = "*$n*";
        }
        $parts[] = $n;
    }
    $md .= "### $t\n\n$t(" . implode(', ', $parts) . ")\n\n";
    foreach ($fkOf[$t] ?? [] as $f) {
        $md .= "- FK `{$f['c']}` -> `{$f['rt']}({$f['rc']})` ON DELETE {$f['dr']}\n";
    }
    foreach ($uniq[$t] ?? [] as $u) {
        $md .= "- UNIQUE ($u)\n";
    }
    foreach ($checkOf[$t] ?? [] as $x) {
        $md .= '- CHECK ' . str_replace('`', '', $x) . "\n";
    }
    $md .= "\n";
}
file_put_contents("$dir/Relational_Schema.md", $md);
echo 'Tables: ' . count($cols) . ', FKs: ' . count($fks) . ' ' . json_encode($rules) . ', CHECKs: ' . count($checks) . "\n";
