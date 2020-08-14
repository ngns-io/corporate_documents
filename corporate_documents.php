<?php
/*
 * Plugin Name:       Corporate Documents
 * Plugin URI:        https://ngns.io/wordpress/plugins/corporate_documents/
 * Description:       Plugin to manage corporate documents
 * Version:           0.1
 * Author:            Evenhouse Consulting, Inc.
 * Author URI:        https://evenhouseconsulting.com
 * Textdomain:        cdox
 * License:           GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Version constant
define("CDOX_VERSION", ".1");

include_once( plugin_dir_path( __FILE__ ) . 'includes/functions.php');

//----------------------
# Advanced Custom Fields
//----------------------

// Define path and URL to the ACF plugin.
define( 'CDOX_ACF_PATH', plugin_dir_path( __FILE__ ) . 'lib/acf/' );
define( 'CDOX_ACF_URL', plugin_dir_url( __FILE__ ) . 'lib/acf/' );

// Include the ACF plugin.
include_once( CDOX_ACF_PATH . 'acf.php' );

// Customize the url setting to fix incorrect asset URLs.
add_filter('acf/settings/url', 'cdox_acf_settings_url');
function cdox_acf_settings_url( $url ) {
    return CDOX_ACF_URL;
}

// (Optional) Hide the ACF admin menu item.
add_filter('acf/settings/show_admin', '__return_false');
// add_filter('acf/settings/show_admin', 'cdox_acf_settings_show_admin');
// function cdox_acf_settings_show_admin( $show_admin ) {
//     return false;
// }

//-----------
# HOOKS
//-----------

add_action('init', 'cdox_register_shortcodes');

add_action('wp_ajax_cdox_filter', 'cdox_apply_filter');
add_action('wp_ajax_nopriv_cdox_filter', 'cdox_apply_filter');

add_filter('manage_edit-corporate_document_columns','cdox_document_column_headers');
add_filter('manage_corporate_document_posts_custom_column','cdox_document_column_data',1,2);

register_activation_hook( __FILE__, function() {
  require_once(plugin_dir_path( __FILE__ ) . 'includes/Activation.php');
  Activation::activate();
});

register_deactivation_hook( __FILE__, function() {
  require_once(plugin_dir_path( __FILE__ ) . 'includes/Deactivation.php');
  Deactivation::deactivate();
});

add_action( 'wp_enqueue_scripts', 'cdox_load_frontend_styles' );
add_action( 'wp_enqueue_scripts', 'cdox_load_frontend_js' );

//-----------
# SHORTCODES
//-----------

function cdox_register_shortcodes() {

	add_shortcode('cdox_list_documenttypes', 'cdox_get_list_documenttypes_shortcode');
	add_shortcode('cdox_list_years', 'cdox_get_years_shortcode');
	add_shortcode('cdox_list_documents', 'cdox_get_list_documents_shortcode');
	add_shortcode('cdox_list_filtered_documents', 'cdox_get_list_documents_filtered_shortcode');

}

function cdox_get_list_documenttypes_shortcode() {

	$args = array (
    'taxonomy' => 'document_type',
    'hide_empty' => false,
  );

	$terms = get_terms( $args );

  ob_start();
  ?>
	<ul class="cdox-ul">
  <?php
  foreach ( $terms as $term ) {
    echo '<li>'.$term->name.'</li>';
  }
  ?>
  </ul>
  <?php
  $temp_content = ob_get_contents();
  ob_end_clean();
  return $temp_content;			
  
}

function cdox_get_years_shortcode() {

	$years = cdox_get_years_array();

  ob_start();
  ?>
	<ul class="cdox-ul">
  <?php
  foreach ( $years as $year ) {
    echo '<li>'.$year.'</li>';
  }
  ?>
  </ul>
  <?php
  $temp_content = ob_get_contents();
  ob_end_clean();
  return $temp_content;			
  
}

function cdox_get_list_documents_shortcode( $atts, $content=null ) {
	global $wp_query,
	  	$post;

	$atts = shortcode_atts( array (
		'type' => '',
		'show_date_column' => 'false'
	), $atts, 'cdox_list_documents' );

	$atts['show_date_column'] = filter_var( $atts['show_date_column'], FILTER_VALIDATE_BOOLEAN );
	$show_date_column = $atts['show_date_column'];

  $tax_query = [];	

	if ( $atts['type'] ) {
		// Parse type into an array. Whitespace will be stripped.
		$sanitized_types = preg_replace( '/\s*,\s*/', ',', filter_var( $atts['type'], FILTER_SANITIZE_STRING ) );
		$atts['type'] = explode( ',', $sanitized_types );
		$tax_query = array( array(
				'taxonomy'  => 'document_type',
				'field'     => 'slug',
				'terms'     => $atts['type']
			) );
	}

	$args = array(
		'posts_per_page'    => -1,
		'post_type'         => 'corporate_document',
		'post_status'       => 'publish',
		// 'meta_key'          => 'publication_date',
		// 'orderby'           => 'meta_value_num',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'tax_query'         => $tax_query
	);
	
	$loop = new WP_Query( $args );

	if( ! $loop->have_posts() ) {
			return false;
	}

	$temp_content = '';
	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $atts['show_date_column'] ) {
		$wrapper_class = 'cdox-list-col-wrapper-2';
	}
	$temp_content .= '<div class="'.$wrapper_class.'">';

	while( $loop->have_posts() ) {
		$loop->the_post();
		$postid = get_the_ID();
		// $pub_date = get_post_meta($postid, 'publication_date', true);
		// $pub_dateTime = DateTime::createFromFormat('Ymd', $pub_date);
		// $pub_date = date_format($pub_dateTime,'F j, Y');
		$pub_date = get_the_date( 'M d, Y' );
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'posts_per_page' => -1,
			'post_parent'   => $postid
		);
		$temp_content .= cdox_list_query( $args, $show_date_column, $pub_date );
	}
	$temp_content .= '</div>';
  wp_reset_postdata();
  return $temp_content;			

}

# filterable document list shortcode
function cdox_get_list_documents_filtered_shortcode( $atts, $content=null ) {
	global $wp_query,
	  	$post;

	$atts = shortcode_atts( array (
		'type' => '',
		'show_year_filter' => 'false',
		'show_date_column' => 'false',
		'initial_current_year' => 'false'
	), $atts, 'cdox_list_filtered_documents' );

	$atts['show_date_column'] = filter_var( $atts['show_date_column'], FILTER_VALIDATE_BOOLEAN );
	$show_date_column = $atts['show_date_column'];
	$atts['show_year_filter'] = filter_var( $atts['show_year_filter'], FILTER_VALIDATE_BOOLEAN );
	$show_year_filter = $atts['show_year_filter'];
	$atts['initial_current_year'] = filter_var( $atts['initial_current_year'], FILTER_VALIDATE_BOOLEAN );
	$initial_current_year = $atts['initial_current_year'];

	$tax_query = [];
	$doc_type_array = [];

	if ( $atts['type'] ) {
		// Parse type into an array. Whitespace will be stripped.
		$sanitized_types = preg_replace( '/\s*,\s*/', ',', filter_var( $atts['type'], FILTER_SANITIZE_STRING ) );
		$atts['type'] = explode( ',', $sanitized_types );
		$tax_query = array( array(
				'taxonomy'  => 'document_type',
				'field'     => 'slug',
				'terms'     => $atts['type']
			) );
		$doc_type_array = $atts['type'];
	}

	$args = array(
		'posts_per_page'    => -1,
		'post_type'         => 'corporate_document',
		'post_status'       => 'publish',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'tax_query'         => $tax_query
	);

	if ( $initial_current_year ) {
		$args['year'] = date("Y");
	}

	$loop = new WP_Query( $args );

	// if( ! $loop->have_posts() ) {
	// 		return false;
	// }

	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $show_date_column ) {
		$wrapper_class = 'cdox-list-col-wrapper-2';
	}

	$list_content = '<div class="'.$wrapper_class.'">';

	while( $loop->have_posts() ) {
		$loop->the_post();
		$postid = get_the_ID();
		$pub_date = get_the_date( 'M d, Y' );
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'posts_per_page' => -1,
			'post_parent'   => $postid
		);
		$list_content .= cdox_list_query( $args, $show_date_column, $pub_date );
	}
	$list_content .= '</div>';

	wp_reset_postdata();

	$temp_content = '';

	$temp_content .= '<div class="cdox-filter-form-wrapper">';
	$temp_content .= '<form action="'. site_url() .'/wp-admin/admin-ajax.php" method="POST" id="filter">';
	$args = array (
		'taxonomy' => 'document_type',
		'hide_empty' => false,
		'orderby' => 'name',
	);
	if ( !empty($doc_type_array) ) :
		$args['slug'] = $doc_type_array;
	endif;
	$terms = get_terms ( $args );
	if ( !empty($terms) && (count($terms) > 1) ) :
		// Document type
		$temp_content .= '<fieldset>';
		$temp_content .= '<legend>Select Document Type</legend>';
		$temp_content .= '<div class="cdox-filter-form-select">';
		$temp_content .= '<select name="cdoxfilter"><option value="cdox_all" selected="selected">All Documents</option>';
		foreach ( $terms as $term ) :
			$temp_content .= '<option value="' . $term->term_id . '">' . $term->name . '</option>'; // ID of the category as the value of an option
		endforeach;
		$temp_content .= '</select>';
		$temp_content .= '</div>';
		$temp_content .= '</fieldset>';
	else:
		$temp_content .= '<input type="hidden" name="cdoxfilter" value="'. current($terms)->term_id .'">';
	endif;
	// Year
	if ( $show_year_filter ) :
		$temp_content .= '<fieldset>';
		$temp_content .= '<legend>Select Year</legend>';
		$temp_content .= '<div class="cdox-filter-form-select">';
		$temp_content .= '<select name="cdoxfilteryear"><option value="cdox_all" selected="selected">All Years</option>';
		if( $years = cdox_get_years_array() ) : 
			foreach ( $years as $year ) :
				$temp_content .= '<option value="' . $year . '">' . $year . '</option>';
			endforeach;
		endif;
		$temp_content .= '</select>';
		$temp_content .= '</div>';
		$temp_content .= '</fieldset>';
	else :
		$temp_content .= '<input type="hidden" name="cdoxfilteryear" value="cdox_all">';
	endif;
	// Published date
	$temp_content .= '<fieldset>';
	$temp_content .= '<legend>Sort by Publication Date</legend>';
	$temp_content .= '<div class="toggle">';
	$temp_content .= '<input type="radio" name="date" value="DESC" id="cdox_desc" checked="checked" />';
	$temp_content .= '<label for="cdox_desc">Descending (newest first)</label>';
	$temp_content .= '<input type="radio" name="date" value="ASC" id="cdox_asc" />';
	$temp_content .= '<label for="cdox_asc">Ascending (oldest first)</label>';
	$temp_content .= '</div>';
	$temp_content .= '</fieldset>';
  // submit button
	$temp_content .= '<div class="cdox-filter-form-button"><button>Apply filter</button></div>';
	$temp_content .= '<input type="hidden" name="showpubdate" value="'. $show_date_column .'">';
	$temp_content .= '<input type="hidden" name="action" value="cdox_filter">';
	$temp_content .= '</form>';
	$temp_content .= '</div><!-- end cdox-filter-form-wrapper -->';
	// the response list of documents
	$temp_content .= '<div id="response">';
	$temp_content .= $list_content;
	$temp_content .= '</div><!-- end response -->';

	return $temp_content;
	
}

//-----------
# ACTIONS
//-----------

function cdox_apply_filter() {

	$args = array(
		'posts_per_page'    => -1,
		'post_type'         => 'corporate_document',
		'post_status'       => 'publish',
		'orderby'           => 'date', // we will sort posts by date
		'order'	            => $_POST['date'] // ASC or DESC
	);

	if( isset( $_POST['cdoxfilter'] ) ):
		if ( $_POST['cdoxfilter'] === "cdox_all" ):
		else:
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'document_type',
					'field' => 'id',
					'terms' => $_POST['cdoxfilter']
				)
			);
		endif;
	endif;

	if( isset( $_POST['cdoxfilteryear'] ) ) {
		$args['year'] = (int) $_POST['cdoxfilteryear'];
	}

	$show_date_column = true;
	if( isset( $_POST['showpubdate'] ) )
		$show_date_column = (bool) $_POST['showpubdate'];

	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $show_date_column ) {
		$wrapper_class = 'cdox-list-col-wrapper-2';
	}

	$temp_content .= '<div class="'.$wrapper_class.'">';

	$query = new WP_Query( $args );
 
	if( $query->have_posts() ) :
		while( $query->have_posts() ): $query->the_post();
			$postid = get_the_ID();
			$pub_date = get_the_date( 'M d, Y' );
			$attachment_args = array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'numberposts' => 1,
				'posts_per_page' => -1,
				'post_parent'   => $postid
			);
			$temp_content .= cdox_list_query( $attachment_args, $show_date_column, $pub_date );
		endwhile;
		wp_reset_postdata();
	else :
		$temp_content .= '<span>No documents found</span>';
	endif;
 
	$temp_content .= '</div>';

	echo $temp_content;

	die();
}

function cdox_load_frontend_styles() {
	wp_enqueue_style(
		'cdox-plugin-frontend',
		plugins_url( 'css/frontend.css', __FILE__ ),
		array(),
		CDOX_VERSION,
		'screen'
	);
	wp_enqueue_style( 
		'cdox-fa',
		plugins_url( 'css/fa/all.min.css', __FILE__ ),
		array(),
		CDOX_VERSION,
		'screen'
	);
}
function cdox_load_frontend_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script(
			'cdox-plugin-js',
			plugins_url( 'js/filter.js', __FILE__ )
	);
}


//-----------
# FILTERS
//-----------

function cdox_document_column_headers( $columns ) {
	
	$columns = array(
		'cb'=>'<input type="checkbox" />',
		'title'=>__('Document Name'),
		'date'=>__('Date Published'),	
	);
	
	return $columns;
	
}

function cdox_document_column_data( $column, $post_id ) {
	
	$output = '';
	
	switch( $column ) {
		
		case 'title':
			$output .= get_field('title', $post_id );
			break;
		case 'date':
			$output .= get_field('date', $post_id );
			break;
		
	}
	
	echo $output;
	
}


//-----------
# CUSTOM POST TYPES
//-----------

include_once( plugin_dir_path( __FILE__ ) . 'cpt/cdox_document.php');

//-----------
# MISC
//-----------

function cdox_list_query ( $args, $show_date_column, $pub_date ) {

	$output = '';
	$attachment = new WP_Query( $args );
	foreach ( $attachment->posts as $file) {

		$mime_type = $file->post_mime_type;
		$icon_class = get_fa_icon_class( $mime_type );
		$parsed = parse_url( $file->guid );
		$url = dirname( $parsed [ 'path' ] ) . '/' . rawurlencode( basename( $parsed[ 'path' ] ) );

		if ( $show_date_column ) {
			$output .= '<div class="cdox-list-col-item">'. $pub_date .'</div>';
		} 
		$output .= '<div class="cdox-list-col-item"><i class="fa ' . $icon_class .'"></i>&nbsp';
		$output .= '<a href="'. $url .'" target="blank">' .get_the_title(). '</a></div>';

	}
	return $output;

}

function cdox_get_years_array() {
	global $wpdb;
	$result = array();
	$select = "SELECT YEAR(post_date) FROM {$wpdb->posts} ";
	$select .= "WHERE post_status = '%s' AND post_type = '%s' ";
	$select .= "GROUP BY YEAR(post_date) ";
	$select .= "ORDER BY YEAR(post_date) DESC";
	$select_args = array("publish", "corporate_document");
	$years = $wpdb->get_results(
			$wpdb->prepare(
					$select, $select_args
			),
			ARRAY_N
	);
	if ( is_array( $years ) && count( $years ) > 0 ) {
			foreach ( $years as $year ) {
					$result[] = $year[0];
			}
	}
	return $result;
}
