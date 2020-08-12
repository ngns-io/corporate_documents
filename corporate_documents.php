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

function cdox_get_list_documents_shortcode( $atts, $content=null ) {
	global $wp_query,
	  	$post;

	$atts = shortcode_atts( array (
		'type' => '',
		'show_date' => 'false'
	), $atts, 'cdox_list_documents' );

	$atts['show_date'] = filter_var( $atts['show_date'], FILTER_VALIDATE_BOOLEAN );

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

	$loop = new WP_Query( array(
		'posts_per_page'    => -1,
		'post_type'         => 'corporate_document',
		'post_status'       => 'publish',
		// 'meta_key'          => 'publication_date',
		// 'orderby'           => 'meta_value_num',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'tax_query'         => $tax_query
	) );

	if( ! $loop->have_posts() ) {
			return false;
	}

	$temp_content = '';
	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $atts['show_date'] ) {
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
		$temp_content .= cdox_list_query( $args, $show_date=$atts['show_date'], $pub_date );
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
		'show_date' => 'false'
	), $atts, 'cdox_list_filtered_documents' );

	$show_date = $atts['show_date'];
	$atts['show_date'] = filter_var( $atts['show_date'], FILTER_VALIDATE_BOOLEAN );

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

	$loop = new WP_Query( array(
		'posts_per_page'    => -1,
		'post_type'         => 'corporate_document',
		'post_status'       => 'publish',
		// 'meta_key'          => 'publication_date',
		// 'orderby'           => 'meta_value_num',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'tax_query'         => $tax_query
	) );

	if( ! $loop->have_posts() ) {
			return false;
	}

	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $atts['show_date'] ) {
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
		$list_content .= cdox_list_query( $args, $show_date=$atts['show_date'], $pub_date );
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
	if( $terms = get_terms( $args ) ) : 
		$temp_content .= '<select name="cdoxfilter"><option value="">Select document type...</option>';
		foreach ( $terms as $term ) :
			$temp_content .= '<option value="' . $term->term_id . '">' . $term->name . '</option>'; // ID of the category as the value of an option
		endforeach;
		$temp_content .= '</select>';
	endif;
	//echo '<input type="text" name="price_min" placeholder="Min price" />';
	//echo '<input type="text" name="price_max" placeholder="Max price" />';
	$temp_content .= '<label><input type="radio" name="date" value="ASC" /> Date: Ascending</label>';
	$temp_content .= '<label><input type="radio" name="date" value="DESC" selected="selected" /> Date: Descending</label>';
	//echo '<label><input type="checkbox" name="featured_image" /> Only posts with featured images</label>';
	$temp_content .= '<button>Apply filter</button>';
	$temp_content .= '<input type="hidden" name="showpubdate" value="'. $show_date .'">';
	$temp_content .= '<input type="hidden" name="action" value="cdox_filter">';
	$temp_content .= '</form>';
	$temp_content .= '</div><!-- end cdox-filter-form-wrapper -->';
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

	if( isset( $_POST['cdoxfilter'] ) )
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'document_type',
				'field' => 'id',
				'terms' => $_POST['cdoxfilter']
			)
		);

	$show_date = true;
	if( isset( $_POST['showpubdate'] ) )
		$show_date = (bool) $_POST['showpubdate'];


	$wrapper_class = 'cdox-list-col-wrapper';
	if ( $show_date ) {
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
			$temp_content .= cdox_list_query( $attachment_args, $show_date, $pub_date );
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

function cdox_list_query ( $args, $show_date, $pub_date ) {

	$output = '';
	$attachment = new WP_Query( $args );
	foreach ( $attachment->posts as $file) {

		$mime_type = $file->post_mime_type;
		$icon_class = get_fa_icon_class( $mime_type );
		$parsed = parse_url( $file->guid );
		$url = dirname( $parsed [ 'path' ] ) . '/' . rawurlencode( basename( $parsed[ 'path' ] ) );

		if ( $show_date ) {
			$output .= '<div class="cdox-list-col-item">'. $pub_date .'</div>';
		} 
		$output .= '<div class="cdox-list-col-item"><i class="fa ' . $icon_class .'"></i>&nbsp';
		$output .= '<a href="'. $url .'" target="blank">' .get_the_title(). '</a></div>';

	}
	return $output;

}


