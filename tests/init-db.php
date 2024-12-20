<?php
// tests/init-db.php

$socket_path = '/Users/dougevenhouse/Library/Application Support/Local/run/Rtiso1yDD/mysql/mysqld.sock';
$mysql_path = '/Users/dougevenhouse/Library/Application Support/Local/lightning-services/mysql-8.0.16+6/bin/darwin/bin/mysql';

$db_name = 'wordpress_test';
$db_user = 'root';
$db_pass = 'root';

// Build MySQL command with socket path
$mysql_cmd = sprintf(
	'"%s" -u%s -p%s --socket="%s"',
	$mysql_path,
	escapeshellarg( $db_user ),
	escapeshellarg( $db_pass ),
	$socket_path
);

// Drop existing database if it exists
exec( $mysql_cmd . ' -e "DROP DATABASE IF EXISTS ' . $db_name . ';"' );

// Create fresh database
exec( $mysql_cmd . ' -e "CREATE DATABASE IF NOT EXISTS ' . $db_name . ' CHARACTER SET utf8 COLLATE utf8_general_ci;"' );

echo "Database initialized successfully!\n";