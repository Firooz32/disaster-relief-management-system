<?php
/**
 * Builds the course submission from submission/team.json:
 *     php tools/build_submission.php            -> submission/build/[GroupID]_[Title]_FinalDB/ + .zip  (needs group_id)
 *     php tools/build_submission.php --draft    -> same, named DRAFT_..., without the group_id / report checks
 * Layout:  database/schema.sql, database/sample_data.sql, source_code/ (git-tracked app files + short README.md),
 *          report/ (report PDF, ERD PDFs, relational schema, normalization notes, query log).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$draft = in_array('--draft', $argv, true);
$root  = realpath(__DIR__ . '/..');
$sub   = "$root/submission";
$team  = json_decode((string) file_get_contents("$sub/team.json"), true, 512, JSON_THROW_ON_ERROR);

$problems = [];
if (!$draft && trim((string) $team['group_id']) === '') {
    $problems[] = 'team.json: group_id is empty';
}
if (!$draft && trim((string) $team['youtube_url']) === '') {
    $problems[] = 'team.json: youtube_url is empty (the report cover needs it)';
}
$reportPdfs = glob("$sub/report/*Report*.pdf") ?: [];
if (!$draft && !$reportPdfs) {
    $problems[] = 'no report PDF in submission/report/';
}
if ($problems) {
    fwrite(STDERR, "Not built:\n - " . implode("\n - ", $problems) . "\n(use --draft for a trial build)\n");
    exit(1);
}

$base = ($draft ? 'DRAFT' : preg_replace('/[^A-Za-z0-9-]/', '', (string) $team['group_id'])) . '_' . $team['zip_title'] . '_FinalDB';
$out  = "$sub/build/$base";
if (is_dir($out)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? rmdir((string) $f) : unlink((string) $f);
    }
    rmdir($out);
}

function put(string $from, string $to): void
{
    if (!is_file($from)) {
        throw new RuntimeException("missing: $from");
    }
    @mkdir(dirname($to), 0777, true);
    copy($from, $to);
}

// source code = files git tracks in the app folder (never config.php, never the untracked stale copy inside it)
exec('git -C ' . escapeshellarg($root) . ' ls-files -z disaster_and_relief_management', $lines, $rc);
if ($rc !== 0) {
    throw new RuntimeException('git ls-files failed');
}
$files = array_filter(explode("\0", implode("\0", $lines)));
foreach ($files as $f) {
    $rel = substr($f, strlen('disaster_and_relief_management/'));
    $dest = $rel === 'README.md' ? 'APP_GUIDE.md' : $rel;           // the short README below takes the README.md name
    put("$root/$f", "$out/source_code/$dest");
}
put("$sub/source_code_README.md", "$out/source_code/README.md");
put("$sub/database/schema.sql", "$out/database/schema.sql");
put("$sub/database/sample_data.sql", "$out/database/sample_data.sql");
foreach (array_merge($reportPdfs, ["$sub/diagrams/ERD_Full.pdf", "$sub/diagrams/ERD_Overview.pdf", "$sub/diagrams/Relational_Schema.md",
         "$sub/diagrams/Normalization_and_Design.md", "$sub/report/Query_Log.md"]) as $f) {
    put($f, "$out/report/" . basename($f));
}

// nothing private or bulky may ship
$bad = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS));
$count = 0;
foreach ($it as $f) {
    $count++;
    $rel = str_replace('\\', '/', substr((string) $f, strlen($out) + 1));
    if (preg_match('#(^|/)config\.php$|(^|/)\.git(/|$)|\.(zip|mp4|mp3|log|bak)$|(^|/)backups?/#i', $rel)) {
        $bad[] = $rel;
    }
}
if ($bad) {
    fwrite(STDERR, "Forbidden files in the package:\n - " . implode("\n - ", $bad) . "\n");
    exit(1);
}

$zipPath = "$sub/build/$base.zip";
@unlink($zipPath);
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS)) as $f) {
    $zip->addFile((string) $f, $base . '/' . str_replace('\\', '/', substr((string) $f, strlen($out) + 1)));
}
$zip->close();
printf("Built %s (%d files, %.1f MB)\n", $zipPath, $count, filesize($zipPath) / 1048576);
