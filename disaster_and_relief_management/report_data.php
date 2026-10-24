<?php
/** Live report data (JSON). Called every few seconds by report.php. */
declare(strict_types=1);
define('DRMS_BACKGROUND_REQUEST', true);   // automatic refresh: does not keep a forgotten session alive
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!logged_in()) {
    http_response_code(401);
    echo '{"error":"login"}';
    exit;
}
session_write_close();      // the page polls every few seconds: do not hold the session lock while the queries run

echo json_encode([
    'time'      => date('d M Y, h:i:s A'),
    'stats'     => overall_stats(),
    'signals'   => signal_summary(),
    'paths'     => path_status(),
    'space'     => shelter_capacity(),
    'areas'     => area_points(),
    'relief_by' => relief_status(),
    'disasters' => disaster_summary(),
    'risk'      => risk_summary(),
    'shelters'  => array_map(fn($s) => array_diff_key($s, ['lat' => 0, 'lng' => 0]), shelter_summary()),
    'relief'    => relief_summary(),
], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
