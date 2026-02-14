<?php
/**
 * Copy to config.php and set your database credentials.
 * config.php is gitignored – never commit credentials.
 *
 * cp config.example.php config.php
 * Then edit config.php with your DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */
putenv('DB_HOST=localhost');
putenv('DB_NAME=your_database');
putenv('DB_USER=your_user');
putenv('DB_PASS=your_password');
