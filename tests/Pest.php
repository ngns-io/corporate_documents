<?php
// tests/Pest.php

use CorporateDocuments\Tests\TestCase;
use function Pest\describe;

/*
|--------------------------------------------------------------------------
| Bootstrap WordPress Test Environment
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/
uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBePublished', function () {
    return $this->value->post_status === 'publish';
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/
function wp_factory(): WP_UnitTest_Factory {
    return TestCase::factory();
}

uses()->group('wordpress')->in('Feature');
uses()->group('wordpress')->in('Unit');
