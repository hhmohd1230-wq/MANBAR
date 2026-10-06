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
    'gemini_api_key'   => '',         // optional; free-tier language review (keep private)
    'gemini_model'     => 'gemini-3.5-flash-lite',
    'groq_api_key'     => '',         // optional; free-tier fallback (keep private)
    'groq_model'       => 'openai/gpt-oss-120b',
    'openai_api_key'   => '',         // optional; preferred for deep writing review
    'openai_model'     => 'gpt-6.1-sol',
    'anthropic_api_key' => '',         // optional
    // Email for sign-in codes. Gmail example: turn on 2-Step Verification, create an App password
    // (myaccount.google.com/apppasswords) and paste the 16 letters as 'pass'.
    'mail' => [
        'host'   => '',              // smtp.gmail.com
        'port'   => 587,
        'secure' => 'tls',
        'user'   => '',              // you@gmail.com
        'pass'   => '',              // App password (keep this file private; it is git-ignored)
        'from'   => '',              // you@gmail.com
    ],
];
