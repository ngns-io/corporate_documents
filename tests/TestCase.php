<?php
declare(strict_types=1);

namespace CorporateDocuments\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;

class TestCase extends WP_UnitTestCase
{
    /**
     * Get the factory instance.
     *
     * @return WP_UnitTest_Factory Factory instance.
     */
    public static function factory(): WP_UnitTest_Factory
    {
        return parent::factory();
    }

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Your common setup code here
    }

    /**
     * Clean up test environment.
     */
    protected function tearDown(): void
    {
        // Your common cleanup code here
        parent::tearDown();
    }
}
