<?php
	
add_action( 'init', 'cdox_register_corporate_document' );
function cdox_register_corporate_document() {

    /**
     * Post Type: Corporate Documents.
     */

  $labels = [
    "name" => __( "Corporate Documents", "cdox" ),
    "singular_name" => __( "Corporate Document", "cdox" ),
    "menu_name" => __( "Corporate Documents", "cdox" ),
    "all_items" => __( "All Corporate Documents", "cdox" ),
    "add_new" => __( "Add new", "cdox" ),
    "add_new_item" => __( "Add new Corporate Document", "cdox" ),
    "edit_item" => __( "Edit Corporate Document", "cdox" ),
    "new_item" => __( "New Corporate Document", "cdox" ),
    "view_item" => __( "View Corporate Document", "cdox" ),
    "view_items" => __( "View Corporate Documents", "cdox" ),
    "search_items" => __( "Search Corporate Documents", "cdox" ),
    "not_found" => __( "No Corporate Documents found", "cdox" ),
    "not_found_in_trash" => __( "No Corporate Documents found in trash", "cdox" ),
    "parent" => __( "Parent Corporate Document:", "cdox" ),
    "featured_image" => __( "Featured image for this Corporate Document", "cdox" ),
    "set_featured_image" => __( "Set featured image for this Corporate Document", "cdox" ),
    "remove_featured_image" => __( "Remove featured image for this Corporate Document", "cdox" ),
    "use_featured_image" => __( "Use as featured image for this Corporate Document", "cdox" ),
    "archives" => __( "Corporate Document archives", "cdox" ),
    "insert_into_item" => __( "Insert into Corporate Document", "cdox" ),
    "uploaded_to_this_item" => __( "Upload to this Corporate Document", "cdox" ),
    "filter_items_list" => __( "Filter Corporate Documents list", "cdox" ),
    "items_list_navigation" => __( "Corporate Documents list navigation", "cdox" ),
    "items_list" => __( "Corporate Documents list", "cdox" ),
    "attributes" => __( "Corporate Documents attributes", "cdox" ),
    "name_admin_bar" => __( "Corporate Document", "cdox" ),
    "item_published" => __( "Corporate Document published", "cdox" ),
    "item_published_privately" => __( "Corporate Document published privately.", "cdox" ),
    "item_reverted_to_draft" => __( "Corporate Document reverted to draft.", "cdox" ),
    "item_scheduled" => __( "Corporate Document scheduled", "cdox" ),
    "item_updated" => __( "Corporate Document updated.", "cdox" ),
    "parent_item_colon" => __( "Parent Corporate Document:", "cdox" ),
  ];

  $rewrite = array(
    "slug"                  => "documents",
    "with_front"            => true,
    "pages"                 => true,
    "feeds"                 => true,
  );

  $args = [
    "label" => __( "Corporate Documents", "cdox" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "has_archive" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "delete_with_user" => false,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => $rewrite,
    "query_var" => true,
    "menu_icon" => "dashicons-media-document",
    "supports" => [ "title", "custom_fields" ],
    "taxonomies" => [ ],
  ];

  register_post_type( "corporate_document", $args );

  /**
   * Taxonomy: Document Types.
   */

  $labels = [
    "name" => __( "Document Types", "cdox" ),
    "singular_name" => __( "Document Types", "cdox" ),
    "menu_name" => __( "Document Types", "cdox" ),
    "all_items" => __( "All Document Types", "cdox" ),
    "edit_item" => __( "Edit Document Type", "cdox" ),
    "view_item" => __( "View Document Type", "cdox" ),
    "update_item" => __( "Update Document Type name", "cdox" ),
    "add_new_item" => __( "Add new Document Type", "cdox" ),
    "new_item_name" => __( "New Document Type name", "cdox" ),
    "parent_item" => __( "Parent Document Type", "cdox" ),
    "parent_item_colon" => __( "Parent Document Type:", "cdox" ),
    "search_items" => __( "Search Document Types", "cdox" ),
    "popular_items" => __( "Popular Document Types", "cdox" ),
    "separate_items_with_commas" => __( "Separate Document Types with commas", "cdox" ),
    "add_or_remove_items" => __( "Add or remove Document Types", "cdox" ),
    "choose_from_most_used" => __( "Choose from the most used Document Types", "cdox" ),
    "not_found" => __( "No Document Types found", "cdox" ),
    "no_terms" => __( "No Document Types", "cdox" ),
    "items_list_navigation" => __( "Document Types list navigation", "cdox" ),
    "items_list" => __( "Document Types list", "cdox" ),
  ];

  $args = [
    "label" => __( "Document Types", "cdox" ),
    "labels" => $labels,
    "public" => true,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'document_type', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "rest_base" => "document_type",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "show_in_quick_edit" => false,
    ];

  register_taxonomy( "document_type", [ "corporate_document" ], $args );

}

if( function_exists('register_field_group') ):

  register_field_group(array(
    'key' => 'group_5f29c3257032c',
    'title' => 'Corporate Document Details',
    'fields' => array(
      array(
        'key' => 'field_5f29c32e843d6',
        'label' => 'Document File',
        'name' => 'document_file',
        'type' => 'file',
        'instructions' => '',
        'required' => 1,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'return_format' => 'id',
        'library' => 'uploadedTo',
        'min_size' => '',
        'max_size' => '',
        'mime_types' => '',
      ),
      // array(
      //   'key' => 'field_5f29c4cfebfb8',
      //   'label' => 'Publication Date',
      //   'name' => 'publication_date',
      //   'type' => 'date_picker',
      //   'instructions' => '',
      //   'required' => 1,
      //   'conditional_logic' => 0,
      //   'wrapper' => array(
      //     'width' => '',
      //     'class' => '',
      //     'id' => '',
      //   ),
      //   'display_format' => 'm/d/Y',
      //   'return_format' => 'm/d/Y',
      //   'first_day' => 1,
      // ),
      array(
        'key' => 'field_5f2afeaff58cf',
        'label' => 'Document Types',
        'name' => 'document_types',
        'type' => 'taxonomy',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'taxonomy' => 'document_type',
        'field_type' => 'checkbox',
        'add_term' => 1,
        'save_terms' => 1,
        'load_terms' => 1,
        'return_format' => 'id',
        'multiple' => 0,
        'allow_null' => 0,
      ),
    ),
    'location' => array(
      array(
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'corporate_document',
        ),
      ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => array(
      0 => 'permalink',
      1 => 'the_content',
      2 => 'excerpt',
      3 => 'discussion',
      4 => 'comments',
      5 => 'revisions',
      6 => 'slug',
      7 => 'author',
      8 => 'format',
      9 => 'page_attributes',
      10 => 'featured_image',
      11 => 'categories',
      12 => 'tags',
      13 => 'send-trackbacks',
    ),
    'active' => true,
    'description' => '',
  ));
      
endif;

// called from Activation hook
function cdox_create_document_types()	{
		// Check if they already exist
		$types = get_terms('document_type', 'hide_empty=0');

    if(empty($types))
		{
			wp_insert_term( 'Investor Relations', 'document_type' );
			wp_insert_term( 'Corporate Governance', 'document_type' );
			wp_insert_term( 'Press Release', 'document_type' );
			wp_insert_term( 'Annual Filing', 'document_type' );
			wp_insert_term( 'Quarterly Filing', 'document_type' );
			wp_insert_term( '8-K Filing', 'document_type' );
			wp_insert_term( 'Forms 3 and 4 Filing', 'document_type' );
			wp_insert_term( 'Management Information Circulars Proxy Filing', 'document_type' );
			wp_insert_term( 'Material Change Report', 'document_type' );
			wp_insert_term( 'Registration Statement', 'document_type' );
			wp_insert_term( 'Shareholder Letter', 'document_type' );
			wp_insert_term( 'Tax Filing', 'document_type' );
		}

}
