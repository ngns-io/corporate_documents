# Testing Documentation

This document describes how to set up and run tests for the Corporate Documents plugin.

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB
- Composer
- Git
- SVN (for WordPress test library)

## Setting Up the Test Environment

1. Install dependencies:
```bash
composer install
```

2. Set up WordPress test installation:
```bash
# Usage: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
./bin/install-wp-tests.sh wordpress_test root root localhost latest
```

Or use the Composer script:
```bash
composer run-script prepare-tests
```

## Running Tests

### All Tests
To run all tests with coverage report:
```bash
composer test
```

### Unit Tests Only
```bash
composer test:unit
```

### Integration Tests Only
```bash
composer test:integration
```

### Code Style Checks
```bash
# Check coding standards
composer phpcs

# Fix coding standards automatically
composer phpcs:fix
```

## Test Structure

```
tests/
├── Unit/                     # Unit tests
│   ├── Document/            # Document-related tests
│   │   ├── DocumentTest.php
│   │   └── DocumentRepositoryTest.php
│   └── Frontend/            # Frontend-related tests
│       └── Shortcode/
│           └── DocumentListShortcodeTest.php
├── Integration/             # Integration tests
├── TestCase.php            # Base test case class
└── bootstrap.php           # Test bootstrap file
```

## Static Analysis

The plugin uses PHPStan for static analysis to catch potential issues:

```bash
# Run PHPStan analysis
composer phpstan

# Run all checks (PHPCS, PHPStan, and PHPUnit)
composer test:all
```

PHPStan is configured in `phpstan.neon` and is set to level 8 (most strict). It checks:

* Type safety
* Dead code
* Logic errors
* Best practices

The configuration includes WordPress-specific rules through `szepeviktor/phpstan-wordpress`.

### PHPStan Configuration

The `phpstan.neon` file is configured to:

1. Use maximum strictness (level 8)
2. Scan all plugin PHP files:
   * src/
   * admin/
   * frontend/
   * includes/
3. Ignore vendor and test files
4. Include WordPress-specific function definitions
5. Handle WordPress dynamic constants

### Running Static Analysis

You can run static analysis in several ways:

1. Basic analysis:
```bash
composer phpstan
```

2. With detailed error reporting:
```bash
vendor/bin/phpstan analyse --error-format=table
```

3. With baseline generation:
```bash
vendor/bin/phpstan analyse --generate-baseline
```

### Common Issues and Solutions

1. **Unknown WordPress Functions**
  * Issue: PHPStan doesn't recognize WordPress functions
  * Solution: Ensure wordpress-stubs is properly configured in phpstan.neon

2. **Dynamic Properties**
  * Issue: WordPress objects often have dynamic properties
  * Solution: Use property annotations or configure in phpstan.neon

3. **False Positives**
  * Issue: PHPStan reports valid code as problematic
  * Solution: Add specific ignores to phpstan.neon for well-understood cases

### Improving Code Quality

Use static analysis results to:

1. Identify potential bugs before they occur
2. Maintain consistent type safety
3. Remove dead code
4. Enforce coding standards
5. Document code properly

### Integration with CI/CD

Static analysis is integrated into the GitHub Actions workflow and:

1. Runs on every push and pull request
2. Must pass before merge is allowed
3. Generates reports for review
4. Maintains code quality standards

## Writing Tests

### Unit Tests
Unit tests should be small, focused, and test a single unit of functionality. Example:

```php
public function test_creates_document_from_post(): void 
{
    $document_id = $this->create_test_document([
        'post_title' => 'Test Document'
    ]);

    $post = get_post($document_id);
    $document = Document::from_post($post);

    $this->assertEquals('Test Document', $document->get_title());
}
```

### Integration Tests
Integration tests should test how different parts of the system work together. Example:

```php
public function test_shortcode_integration_with_document_repository(): void 
{
    // Create test data
    $this->create_test_document_type();
    $this->create_test_document();

    // Test shortcode output
    $output = do_shortcode('[cdox_list_documents]');
    $this->assertNotEmpty($output);
}
```

## Test Helpers

The `TestCase` class provides several helper methods:

```php
// Create a test document
$document_id = $this->create_test_document([
    'post_title' => 'Test Document',
    'post_date' => '2024-01-21 12:00:00'
]);

// Create a test document type
$type_id = $this->create_test_document_type([
    'name' => 'Annual Report',
    'slug' => 'annual-report'
]);

// Create a test attachment
$attachment_id = $this->create_test_attachment('test.pdf');
```

## Coverage Reports

After running tests with coverage, you can find the HTML coverage report in the `coverage` directory. Open `coverage/index.html` in your browser to view it.

## Continuous Integration

The plugin uses GitHub Actions for CI. The workflow:
1. Runs on each push and pull request
2. Sets up PHP and MySQL
3. Installs WordPress test suite
4. Runs PHPUnit tests
5. Runs coding standards checks
6. Generates and stores coverage reports

### GitHub Actions Workflow

The workflow configuration is in `.github/workflows/tests.yml`. It:
- Tests against multiple PHP versions (7.4, 8.0, 8.1, 8.2)
- Tests against multiple WordPress versions (latest, nightly)
- Caches dependencies for faster builds
- Runs both unit and integration tests
- Checks coding standards
- Uploads coverage reports as artifacts

## Best Practices

### Writing Tests

1. **Test Names**
- Use descriptive test method names
- Follow the pattern: test_[what_is_being_tested]_[expected_behavior]
```php
public function test_document_creation_with_invalid_post_type_throws_exception(): void
```

2. **Arrange, Act, Assert**
- Organize tests into three sections
```php
public function test_filters_documents_by_type(): void 
{
    // Arrange
    $type_id = $this->create_test_document_type();
    $doc_id = $this->create_test_document();
    wp_set_post_terms($doc_id, [$type_id], 'document_type');

    // Act
    $documents = $this->repository->get_filtered_documents(['annual-report']);

    // Assert
    $this->assertCount(1, $documents);
}
```

3. **Data Providers**
- Use data providers for testing multiple scenarios
```php
/**
 * @dataProvider provide_document_types
 */
public function test_document_type_creation(string $name, string $slug): void 
{
    $type_id = $this->create_test_document_type([
        'name' => $name,
        'slug' => $slug
    ]);
    
    $this->assertGreaterThan(0, $type_id);
}

public function provide_document_types(): array 
{
    return [
        ['Annual Report', 'annual-report'],
        ['Press Release', 'press-release'],
        ['Tax Filing', 'tax-filing']
    ];
}
```

### Test Performance

1. **Database Operations**
- Use transactions where possible
- Clean up test data after tests
- Use fixtures for complex data setups

2. **Mocking**
- Mock external services and APIs
- Use data providers instead of repeating setup code
- Cache expensive operations

## Troubleshooting

### Common Issues

1. **MySQL Connection Issues**
```bash
# Check MySQL is running
mysql.server status

# Verify credentials
mysql -u root -p
```

2. **WordPress Test Library Issues**
```bash
# Reinstall WordPress test library
rm -rf /tmp/wordpress-tests-lib
rm -rf /tmp/wordpress
./bin/install-wp-tests.sh wordpress_test root root localhost latest
```

3. **Permission Issues**
```bash
# Fix file permissions
chmod +x bin/install-wp-tests.sh
chmod +x bin/run-tests.sh
```

### Getting Help

1. Check the GitHub repository issues
2. Join the WordPress.org Slack team
3. Contribute to the documentation

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add or update tests
4. Make your changes
5. Run tests and ensure they pass
6. Submit a pull request

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Documentation](https://make.wordpress.org/core/handbook/testing/)
- [WP Mock Documentation](https://github.com/10up/wp_mock)
- [Brain Monkey Documentation](https://giuseppe-mazzapica.gitbook.io/brain-monkey/)