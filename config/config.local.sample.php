<?php
// Copy to config/config.local.php (git-ignored) and edit.
return [
    'env' => 'local',
    'db' => [
        'driver' => 'mysql',
        'host'   => '127.0.0.1',
        'name'   => 'manbar',
        'user'   => 'root',
        'pass'   => '',
    ],
    'google_client_id' => '',          // paste your OAuth Web client ID here
    'dev_login'        => true,        // set to false when you go live
    'admin_emails'     => ['202020280@aau.ac.ae'],
    'anthropic_api_key' => '',         // optional
];
