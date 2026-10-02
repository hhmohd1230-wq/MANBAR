<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', (getenv('MANBAR_ENV') ?: 'local') === 'production' ? '0' : '1');

require_once __DIR__ . '/helpers.php';
date_default_timezone_set(cfg('timezone'));
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gamify.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/catalog.php';
foreach (glob(__DIR__ . '/controllers/*.php') as $f) require $f;
