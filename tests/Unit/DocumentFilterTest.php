<?php
// tests/Unit/DocumentFilterTest.php
declare(strict_types=1);

namespace CorporateDocuments\Tests\Unit;

use CorporateDocuments\Tests\TestCase;
use WP_Query;
use WP_Term;
use function test;
use function expect;
use function beforeEach;
use function afterEach;

beforeEach(function() {
    // Create test documents
    $this->post_ids = [
        TestCase::factory()->post->create([
            'post_type' => 'corporate_document',
            'post_title' => 'Test Document 2023',
            'post_status' => 'publish',
            'post_date' => '2023-01-01 00:00:00'
        ]),
        TestCase::factory()->post->create([
            'post_type' => 'corporate_document',
            'post_title' => 'Test Document 2024',
            'post_status' => 'publish',
            'post_date' => '2024-01-01 00:00:00'
        ])
    ];

    // Create test document types
    wp_insert_term('Annual Report', 'document_type');
    wp_insert_term('Press Release', 'document_type');
});

afterEach(function() {
    // Clean up test documents
    foreach ($this->post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }

    // Clean up taxonomies
    $terms = get_terms([
        'taxonomy' => 'document_type',
        'hide_empty' => false
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'document_type');
        }
    }
});

test('filters documents by year', function() {
    $args = [
        'post_type' => 'corporate_document',
        'year' => 2023
    ];

    $query = new WP_Query($args);

    expect($query->post_count)->toBe(1)
        ->and($query->posts[0]->post_title)
        ->toBe('Test Document 2023');
});

test('filters documents by type', function() {
    wp_set_object_terms($this->post_ids[0], 'Annual Report', 'document_type');

    $args = [
        'post_type' => 'corporate_document',
        'tax_query' => [[
            'taxonomy' => 'document_type',
            'field' => 'slug',
            'terms' => 'annual-report'
        ]]
    ];

    $query = new WP_Query($args);

    expect($query->post_count)->toBe(1)
        ->and($query->posts[0]->ID)->toBe($this->post_ids[0]);
});

test('returns empty result for non-existent year', function() {
    $args = [
        'post_type' => 'corporate_document',
        'year' => 2022
    ];

    $query = new WP_Query($args);
    expect($query->post_count)->toBe(0);
});

test('combines year and type filters', function() {
    wp_set_object_terms($this->post_ids[0], 'Annual Report', 'document_type');
    wp_set_object_terms($this->post_ids[1], 'Annual Report', 'document_type');

    $args = [
        'post_type' => 'corporate_document',
        'year' => 2023,
        'tax_query' => [[
            'taxonomy' => 'document_type',
            'field' => 'slug',
            'terms' => 'annual-report'
        ]]
    ];

    $query = new WP_Query($args);
    expect($query->post_count)->toBe(1)
        ->and($query->posts[0]->ID)->toBe($this->post_ids[0]);
});
