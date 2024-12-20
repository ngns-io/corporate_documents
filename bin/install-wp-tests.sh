#!/bin/bash

if [ $# -lt 3 ]; then
    echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

# Set paths
WP_TESTS_DIR="$PWD/tests/wordpress-tests-lib"
WP_CORE_DIR="$PWD/tests/wordpress"
DEVELOP_DIR="$PWD/tests/wordpress-develop"

# Use LocalWP MySQL path and socket
MYSQL_PATH="/Users/dougevenhouse/Library/Application Support/Local/lightning-services/mysql-8.0.16+6/bin/darwin/bin"
SOCKET_PATH="/Users/dougevenhouse/Library/Application Support/Local/run/Rtiso1yDD/mysql/mysqld.sock"

download() {
    if command -v curl >/dev/null 2>&1; then
        curl -s "$1" > "$2"
    elif command -v wget >/dev/null 2>&1; then
        wget -nv -O "$2" "$1"
    else
        echo "Neither curl nor wget found. Please install one of them."
        exit 1
    fi
}

setup_test_suite() {
    # Clean up any existing directories
    rm -rf "$WP_TESTS_DIR"
    rm -rf "$DEVELOP_DIR"
    mkdir -p "$WP_TESTS_DIR"

    echo "Cloning WordPress develop repository..."
    # Always clone master branch for tests
    git clone --depth=1 https://github.com/WordPress/wordpress-develop.git "$DEVELOP_DIR"

    if [ ! -d "$DEVELOP_DIR" ]; then
        echo "Error: Failed to clone WordPress develop repository"
        exit 1
    fi

    echo "Copying test files..."
    # Copy entire test framework
    cp -r "$DEVELOP_DIR/tests/phpunit/includes" "$WP_TESTS_DIR/"
    cp -r "$DEVELOP_DIR/tests/phpunit/data" "$WP_TESTS_DIR/"

    # Set up wp-tests-config.php
    cp "$DEVELOP_DIR/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
    
    # Configure test environment
    if [[ $OSTYPE == 'darwin'* ]]; then
        local ioption='-i .bak'
    else
        local ioption='-i'
    fi
    
    sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
    sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed $ioption "s|localhost|${DB_HOST}:${SOCKET_PATH}|" "$WP_TESTS_DIR/wp-tests-config.php"

    # Clean up
    rm -rf "$DEVELOP_DIR"
    echo "Test suite setup completed."
}

install_wp() {
    if [ -d $WP_CORE_DIR ]; then
        return;
    fi

    echo "Downloading WordPress..."
    mkdir -p $WP_CORE_DIR

    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        download https://wordpress.org/nightly-builds/wordpress-latest.zip $TMPDIR/wordpress-latest.zip
        unzip -q $TMPDIR/wordpress-latest.zip -d $WP_CORE_DIR
        rm $TMPDIR/wordpress-latest.zip
    else
        if [ $WP_VERSION == 'latest' ]; then
            local ARCHIVE_NAME='latest'
        else
            local ARCHIVE_NAME="wordpress-$WP_VERSION"
        fi
        download https://wordpress.org/${ARCHIVE_NAME}.tar.gz $TMPDIR/wordpress.tar.gz
        tar --strip-components=1 -zxf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
        rm $TMPDIR/wordpress.tar.gz
    fi

    echo "WordPress core files downloaded and extracted."
}

install_db() {
    if [ ${SKIP_DB_CREATE} = "true" ]; then
        return 0
    fi

    if [ ! -S "$SOCKET_PATH" ]; then
        echo "Error: MySQL socket not found at $SOCKET_PATH"
        echo "Is LocalWP running? Socket path may have changed."
        exit 1
    fi

    echo "Setting up test database..."
    # Create database using LocalWP's MySQL
    "$MYSQL_PATH/mysql" -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" --socket="$SOCKET_PATH" -e "DROP DATABASE IF EXISTS $DB_NAME"
    "$MYSQL_PATH/mysql" -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" --socket="$SOCKET_PATH" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME"

    echo "Test database created."
}

echo "Starting WordPress test environment setup..."

install_wp
setup_test_suite
install_db

echo "WordPress test environment setup completed!"