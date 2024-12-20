<?php
// tests/Feature/PostTypeTest.php
declare(strict_types=1);

namespace CorporateDocuments\Tests\Feature;

use CorporateDocuments\Admin\PostType;
use CorporateDocuments\Document\DocumentRepository;
use CorporateDocuments\Tests\TestCase;
use WP_Query;
use function test;
use function expect;
use function beforeEach;

beforeEach(function() {
    $this->document_repository = new DocumentRepository();
    $this->post_type = new PostType($this->document_repository);
});

test('registers corporate document post type', function() {
    $this->post_type->register_post_type();

    expect(post_type_exists('corporate_document'))->toBeTrue()
        ->and(get_post_type_object('corporate_document'))
        ->toHaveProperty('labels')
        ->toHaveProperty('public', true)
        ->toHaveProperty('show_ui', true);
});

test('registers document type taxonomy', function() {
    $this->post_type->register_taxonomy();

    expect(taxonomy_exists('document_type'))->toBeTrue()
        ->and(get_taxonomy('document_type'))
        ->toHaveProperty('labels')
        ->toHaveProperty('hierarchical', false)
        ->toHaveProperty('show_ui', true);
});

test('sets custom admin columns', function() {
    $columns = $this->post_type->set_columns([]);

    expect($columns)
        ->toHaveKey('title')
        ->toHaveKey('document_type')
        ->toHaveKey('file_size')
        ->toHaveKey('downloads')
        ->toHaveKey('date');
});

test('renders column content', function() {
    $post_id = TestCase::factory()->post->create([
        'post_type' => 'corporate_document',
        'post_title' => 'Test Document'
    ]);

    wp_insert_term('Annual Report', 'document_type');
    wp_set_object_terms($post_id, 'Annual Report', 'document_type');

    ob_start();
    $this->post_type->render_column('document_type', $post_id);
    $output = ob_get_clean();

    expect($output)->toContain('Annual Report');

    wp_delete_post($post_id, true);
});

test('adds document type filter to admin', function() {
    // Create test term first
    wp_insert_term('Test Type', 'document_type');

    ob_start();
    $this->post_type->add_admin_filters('corporate_document');
    $output = ob_get_clean();

    expect($output)
        ->toContain('document_type')
        ->toContain('All Document Types');

    // Clean up
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

test('filters query by document type', function() {
    // Register post type and taxonomy first
    $this->post_type->register_post_type();
    $this->post_type->register_taxonomy();

    // Create a test document
    $post_id = TestCase::factory()->post->create([
        'post_type' => 'corporate_document'
    ]);

    // Create and assign term
    $term = wp_insert_term('Test Type', 'document_type');
    if (is_wp_error($term)) {
        throw new \Exception('Failed to create term: ' . $term->get_error_message());
    }

    wp_set_object_terms($post_id, $term['term_id'], 'document_type');

    // Set up the request
    $_GET = [
        'post_type' => 'corporate_document',
        'document_type' => (string)$term['term_id']  // Convert to string as $_GET values are strings
    ];

    // Create and filter query
    $query = new WP_Query();
    $query->is_main_query = true;  // Set this to ensure filter runs
    $query->set('post_type', 'corporate_document');

    $this->post_type->filter_query($query);

    expect($query->get('tax_query'))
        ->toBeArray()
        ->and($query->get('tax_query')[0])
        ->toMatchArray([
            'taxonomy' => 'document_type',
            'field'    => 'term_id',
            'terms'    => $term['term_id']
        ]);

    // Clean up
    $_GET = [];
    wp_delete_term($term['term_id'], 'document_type');
    wp_delete_post($post_id, true);
});
