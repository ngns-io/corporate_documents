<?php
// tests/wp-tests-config.php

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/wordpress/' );

// ** MySQL settings for LocalWP ** //
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost:/Users/dougevenhouse/Library/Application Support/Local/run/Rtiso1yDD/mysql/mysqld.sock' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_TESTS_MULTISITE', false );
define( 'WP_DEBUG', true );

define( 'WPLANG', '' );