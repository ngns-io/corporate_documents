<?php
declare(strict_types=1);

namespace CorporateDocuments\Admin;

use CorporateDocuments\Document\DocumentRepository;
use WP_Post;

/**
 * Handles admin functionality for the Corporate Document post type
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Admin
 */
class PostType {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	private const POST_TYPE = 'corporate_document';

	/**
	 * Taxonomy name.
	 *
	 * @var string
	 */
	private const TAXONOMY = 'document_type';

	/**
	 * Document repository instance.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Initialize the class.
	 *
	 * @param DocumentRepository $document_repository Document repository instance.
	 */
	public function __construct( DocumentRepository $document_repository ) {
		$this->document_repository = $document_repository;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Post type registration.
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		// Admin columns.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'set_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'set_sortable_columns' ) );

		// Filters and sorting.
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_query' ) );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

		// ACF field groups.
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
	}

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Corporate Documents', 'cdox' ),
			'singular_name'      => __( 'Corporate Document', 'cdox' ),
			'add_new'            => __( 'Add New', 'cdox' ),
			'add_new_item'       => __( 'Add New Corporate Document', 'cdox' ),
			'edit_item'          => __( 'Edit Corporate Document', 'cdox' ),
			'new_item'           => __( 'New Corporate Document', 'cdox' ),
			'view_item'          => __( 'View Corporate Document', 'cdox' ),
			'search_items'       => __( 'Search Corporate Documents', 'cdox' ),
			'not_found'          => __( 'No Corporate Documents found', 'cdox' ),
			'not_found_in_trash' => __( 'No Corporate Documents found in trash', 'cdox' ),
			'menu_name'          => __( 'Corporate Documents', 'cdox' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'documents' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-media-document',
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => true,
			'rest_base'          => 'documents',
			'map_meta_cap'       => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the document type taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'                  => __( 'Document Types', 'cdox' ),
			'singular_name'         => __( 'Document Type', 'cdox' ),
			'search_items'          => __( 'Search Document Types', 'cdox' ),
			'all_items'             => __( 'All Document Types', 'cdox' ),
			'parent_item'           => __( 'Parent Document Type', 'cdox' ),
			'parent_item_colon'     => __( 'Parent Document Type:', 'cdox' ),
			'edit_item'             => __( 'Edit Document Type', 'cdox' ),
			'update_item'           => __( 'Update Document Type', 'cdox' ),
			'add_new_item'          => __( 'Add New Document Type', 'cdox' ),
			'new_item_name'         => __( 'New Document Type Name', 'cdox' ),
			'menu_name'             => __( 'Document Types', 'cdox' ),
			'not_found'             => __( 'No Document Types found', 'cdox' ),
			'no_terms'              => __( 'No Document Types', 'cdox' ),
			'items_list_navigation' => __( 'Document Types list navigation', 'cdox' ),
			'items_list'            => __( 'Document Types list', 'cdox' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => $labels,
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => 'document-type',
					'with_front' => true,
				),
				'capabilities'      => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts',
				),
			)
		);
	}

	/**
	 * Set custom admin columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public function set_columns( array $columns ): array {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Title', 'cdox' ),
			'document_type' => __( 'Document Type', 'cdox' ),
			'file_size'     => __( 'File Size', 'cdox' ),
			'downloads'     => __( 'Downloads', 'cdox' ),
			'date'          => __( 'Date', 'cdox' ),
		);

		return $columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		try {
			$document = $this->document_repository->get_document( $post_id );

			switch ( $column ) {
				case 'document_type':
					$terms = get_the_terms( $post_id, 'document_type' );
					if ( $terms && ! is_wp_error( $terms ) ) {
						$term_names = array_map(
							function ( $term ) {
								return esc_html( $term->name );
							},
							$terms
						);
						echo implode( ', ', $term_names );
					}
					break;

				case 'file_size':
					echo esc_html( $document->get_formatted_file_size() );
					break;

				case 'downloads':
					echo esc_html( number_format_i18n( $document->get_download_count() ) );
					break;
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Set sortable columns.
	 *
	 * @param array<string, string> $columns Sortable columns.
	 * @return array<string, string>
	 */
	public function set_sortable_columns( array $columns ): array {
		$columns['downloads'] = 'downloads';
		return $columns;
	}

	/**
	 * Add admin filters above the documents list.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function add_admin_filters( string $post_type ): void {
		if ( $post_type !== self::POST_TYPE ) {
			return;
		}

		// Document type filter
		$selected = $_GET['document_type'] ?? '';
		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'All Document Types', 'cdox' ),
				'taxonomy'        => 'document_type',
				'name'            => 'document_type',
				'selected'        => $selected,
				'hierarchical'    => true,
				'show_count'      => true,
				'hide_empty'      => false,
			)
		);

		// Year filter
		$selected_year = $_GET['document_year'] ?? '';
		$years         = $this->document_repository->get_document_years();
		?>
		<select name="document_year">
			<option value=""><?php esc_html_e( 'All Years', 'cdox' ); ?></option>
			<?php foreach ( $years as $year => $count ) : ?>
				<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>>
					<?php echo esc_html( "$year ($count)" ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter the admin query based on selected filters.
	 *
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function filter_query( \WP_Query $query ): void {
		if ( ! is_admin() ||
			! $query->is_main_query() ||
			$query->get( 'post_type' ) !== self::POST_TYPE
		) {
			return;
		}

		// Document type filter.
		if ( ! empty( $_GET['document_type'] ) ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'document_type',
						'field'    => 'term_id',
						'terms'    => (int) $_GET['document_type'],
					),
				)
			);
		}

		// Year filter.
		if ( ! empty( $_GET['document_year'] ) ) {
			$query->set( 'year', (int) $_GET['document_year'] );
		}

		// Download count sorting.
		if ( $query->get( 'orderby' ) === 'downloads' ) {
			$query->set( 'meta_key', '_document_downloads' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Add meta boxes to the document edit screen.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'document_file',
			__( 'Document File', 'cdox' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		// Add nonce for security.
		wp_nonce_field( 'save_document_meta', 'document_meta_nonce' );

		// Get current values.
		$file_id = get_post_meta( $post->ID, '_document_file_id', true );

		// Output the form fields.
		?>
		<p>
			<label for="document_file_id"><?php esc_html_e( 'Select Document File:', 'cdox' ); ?></label>
			<input type="hidden" id="document_file_id" name="document_file_id" value="<?php echo esc_attr( $file_id ); ?>">
			<button type="button" class="button" id="upload_document_button">
				<?php esc_html_e( 'Choose File', 'cdox' ); ?>
			</button>
		</p>
		<div id="document_file_info">
			<?php
			if ( $file_id ) {
				$file_url  = wp_get_attachment_url( $file_id );
				$file_name = basename( get_attached_file( $file_id ) );
				echo '<p>' . esc_html( $file_name ) . ' (<a href="' . esc_url( $file_url ) . '" target="_blank">' .
					esc_html__( 'View', 'cdox' ) . '</a>)</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta_boxes( int $post_id, WP_Post $post ): void {
		// Security checks.
		if ( ! isset( $_POST['document_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['document_meta_nonce'], 'save_document_meta' )
		) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save document file ID.
		if ( isset( $_POST['document_file_id'] ) ) {
			$file_id = (int) $_POST['document_file_id'];
			if ( $file_id > 0 ) {
				update_post_meta( $post_id, '_document_file_id', $file_id );
			} else {
				delete_post_meta( $post_id, '_document_file_id' );
			}
		}
	}

	/**
	 * Register ACF fields.
	 *
	 * @return void
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_document_details',
				'title'                 => __( 'Document Details', 'cdox' ),
				'fields'                => array(
					array(
						'key'           => 'field_document_file',
						'label'         => __( 'Document File', 'cdox' ),
						'name'          => 'document_file',
						'type'          => 'file',
						'required'      => 1,
						'return_format' => 'id',
						'library'       => 'uploadedTo',
						'mime_types'    => '',
					),
					array(
						'key'           => 'field_document_types',
						'label'         => __( 'Document Types', 'cdox' ),
						'name'          => 'document_types',
						'type'          => 'taxonomy',
						'taxonomy'      => 'document_type',
						'field_type'    => 'checkbox',
						'return_format' => 'id',
						'add_term'      => 1,
						'save_terms'    => 1,
						'load_terms'    => 1,
						'multiple'      => 1,
						'allow_null'    => 0,
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => self::POST_TYPE,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => array(
					'permalink',
					'the_content',
					'excerpt',
					'discussion',
					'comments',
					'revisions',
					'author',
					'format',
					'featured_image',
					'categories',
					'tags',
				),
			)
		);
	}
}