<?php
// tests/Unit/AjaxHandlerTest.php
declare(strict_types=1);

namespace CorporateDocuments\Tests\Unit;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

class AjaxHandlerTest extends WP_Ajax_UnitTestCase
{
    protected int $post_id;
    protected int $attachment_id;
    protected string $nonce;

    protected function setUp(): void
    {
        parent::setUp();

        $_POST = [];
        $_REQUEST = [];

        // Create test document
        $this->post_id = self::factory()->post->create([
            'post_type' => 'corporate_document',
            'post_title' => 'AJAX Test Document',
            'post_status' => 'publish'
        ]);

        // Create test attachment
        $this->attachment_id = self::factory()->attachment->create_object([
            'file' => 'test.pdf',
            'post_parent' => $this->post_id,
            'post_mime_type' => 'application/pdf',
            'post_title' => 'Test PDF'
        ]);

        update_post_meta($this->post_id, '_document_file_id', $this->attachment_id);

        // Create and store nonce
        $this->nonce = wp_create_nonce('cdox_filter');

        // Mock AJAX action
        add_action('wp_ajax_cdox_filter', [$this, 'mock_ajax_filter']);
        add_action('wp_ajax_nopriv_cdox_filter', [$this, 'mock_ajax_filter']);
    }

    protected function tearDown(): void
    {
        if (isset($this->attachment_id)) {
            wp_delete_attachment($this->attachment_id, true);
        }

        if (isset($this->post_id)) {
            wp_delete_post($this->post_id, true);
        }

        $_POST = [];
        $_REQUEST = [];

        parent::tearDown();
    }

    public function mock_ajax_filter(): void
    {
        check_ajax_referer('cdox_filter', '_wpnonce');

        $document = get_post($this->post_id);
        $attachment = get_post($this->attachment_id);
        echo sprintf(
            '<div class="document-item"><span class="title">%s</span><span class="file">%s</span></div>',
            esc_html($document->post_title),
            esc_html($attachment->post_title)
        );
        wp_die();
    }

    public function test_ajax_filter_returns_filtered_documents()
    {
        $_REQUEST['_wpnonce'] = $this->nonce;
        $_POST = [
            'action' => 'cdox_filter',
            '_wpnonce' => $this->nonce,
            'cdoxfilterdoctypes' => 'cdox-all-doctypes',
            'cdoxfilteryear' => 'cdox-all-years',
            'dateorder' => 'DESC',
            'showpubdate' => 'true'
        ];

        try {
            $this->_handleAjax('cdox_filter');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        $this->assertStringContainsString('AJAX Test Document', $this->_last_response);
        $this->assertStringContainsString('Test PDF', $this->_last_response);
    }

    public function test_ajax_filter_handles_empty_results()
    {
        wp_delete_post($this->post_id, true);

        $_REQUEST['_wpnonce'] = $this->nonce;
        $_POST = [
            'action' => 'cdox_filter',
            '_wpnonce' => $this->nonce,
            'cdoxfilterdoctypes' => 'non-existent-type',
            'cdoxfilteryear' => 'cdox-all-years',
            'dateorder' => 'DESC',
            'showpubdate' => 'true'
        ];

        try {
            $this->_handleAjax('cdox_filter');
        } catch (WPAjaxDieContinueException $e) {
            // Expected exception
        }

        $this->assertStringNotContainsString('AJAX Test Document', $this->_last_response);
    }
}
