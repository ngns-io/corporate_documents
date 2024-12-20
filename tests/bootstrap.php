<?php
// tests/bootstrap.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = dirname(__DIR__) . '/tests/wordpress-tests-lib';
}

// Forward custom test configuration
$_tests_config = [
    'active_plugins' => [],
    'multisite' => false,
];

// Load the WordPress tests
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress tests directory at $_tests_dir\n";
    echo "Please run bin/install-wp-tests.sh first.\n";
    exit(1);
}

// Load WordPress test environment files
require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin being tested
 */
function _manually_load_plugin(): void
{
    require dirname(__DIR__) . '/corporate_documents.php';
}

// Ensure plugin is loaded
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Bootstrap the WordPress test environment
require $_tests_dir . '/includes/bootstrap.php';
