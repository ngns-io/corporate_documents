#!/usr/bin/env bash

# Script to run plugin tests

# Exit if any command fails
set -e

# Set up environment variables
export WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
export WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}

# Check if tests are installed
if [ ! -d "$WP_TESTS_DIR" ]; then
    echo "WordPress tests not found. Please run bin/install-wp-tests.sh first."
    exit 1
fi

# Clean previous coverage reports
if [ -d "coverage" ]; then
    rm -rf coverage
fi

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    composer install
fi

# Run PHP Unit tests with coverage
echo "Running unit tests..."
./vendor/bin/phpunit --coverage-html coverage

# Check if any tests failed
if [ $? -eq 0 ]; then
    echo "All tests passed!"
    echo "Coverage report generated in coverage/index.html"
else
    echo "Tests failed!"
    exit 1
fi