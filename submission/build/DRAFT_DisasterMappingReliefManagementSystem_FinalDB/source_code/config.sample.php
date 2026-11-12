<?php
/**
 * Copy this file to config.php. Every value can be set with an environment variable
 * (DRMS_DB_HOST, DRMS_DB_PORT, DRMS_DB_USER, DRMS_DB_PASS, DRMS_DB_NAME); otherwise the default after ?: is used.
 * For anything beyond a lab PC, run create_db_user.sql and use that restricted user instead of root.
 */
return [
    'host' => getenv('DRMS_DB_HOST') ?: '127.0.0.1',
    'user' => getenv('DRMS_DB_USER') ?: 'root',
    'pass' => getenv('DRMS_DB_PASS') ?: '',
    'name' => getenv('DRMS_DB_NAME') ?: 'disaster_and_relief_management',
    'port' => (int) (getenv('DRMS_DB_PORT') ?: 3306),
];
