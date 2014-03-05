<?php
/*
 Brew Posts are created with information about abv, color, IBUs and more
 */

function custom_post_brew() {
    $labels = array(
		'name'               => _x( 'Brews', 'post type general name' ),
		'singular_name'      => _x( 'Brew', 'post type singular name' ),
		'add_new'            => _x( 'Add New', 'Brew' ),
		'add_new_item'       => __( 'Add New Brew' ),
		'edit_item'          => __( 'Edit Brew' ),
		'new_item'           => __( 'New ProdBrewuct' ),
		'all_items'          => __( 'All Brews' ),
		'view_item'          => __( 'View Brew' ),
		'search_items'       => __( 'Search Brews' ),
		'not_found'          => __( 'No brews found' ),
		'not_found_in_trash' => __( 'No brews found in the Trash' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Brews'
	);
	$args = array(
		'labels'        => $labels,
		'description'   => 'Holds our brews and their info',
		'public'        => true,
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
		'has_archive'   => true,
	);
		register_post_type( 'brew', $args );	
}

add_action( 'init', 'custom_post_brew' );

function brew_messages( $messages ) {
	global $post, $post_ID;
	$messages['product'] = array(
		0 => '', 
		1 => sprintf( __('Brew updated. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Brew updated.'),
		5 => isset($_GET['revision']) ? sprintf( __('Brew restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Brew published. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Brew saved.'),
		8 => sprintf( __('Brew submitted. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Brew scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Brew draft updated. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);
	return $messages;
}

add_filter( 'post_updated_messages', 'brew_messages' );