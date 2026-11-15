<?php
// Copy to config.php (which is git-ignored) and fill in real values.
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'fincore',
    'db_user' => 'fincore_app',  // a dedicated user, not root
    'db_pass' => 'change-me',

    'admin_user' => 'admin',
    // Generate with: php -r 'echo password_hash("your-password", PASSWORD_DEFAULT), "\n";'
    'admin_password_hash' => '',
];
