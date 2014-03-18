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
	$messages['Brew'] = array(
		0 => '', 
		1 => sprintf( __('Brew updated. <a href="%s">View Brew</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Brew updated.'),
		5 => isset($_GET['revision']) ? sprintf( __('Brew restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Brew published. <a href="%s">View Brew</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Brew saved.'),
		8 => sprintf( __('Brew submitted. <a target="_blank" href="%s">Preview Brew</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Brew scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Brew draft updated. <a target="_blank" href="%s">Preview Brew</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);
	return $messages;
}

add_filter( 'post_updated_messages', 'brew_messages' );

function show_brewtable() {
	echo '<legend>Brewtable</legend><table class="table table-condensed">
		<thead>
      <tr>
       <td>Name</td>
       <td>Symbol</td>
       <td>Style</td>
       <td>ABV</td>
       <td>Description</td>
      </tr>
     </thead>
    <tbody>';
  
  $the_query = new WP_Query( array( 'post_type' => 'brew' ) );

	while ( $the_query->have_posts() ) : $the_query->the_post();
		echo '<tr><td><a href="';
		echo the_permalink();
		echo '">';
		the_title();
		echo '</a></td><td>';
		the_field('label');
		echo '</td><td>';
		the_field('style');
		echo '</td><td>';		
		the_field('abv');
		echo ' %</td><td>';
		the_field('description');
		echo '</td></tr>';
	endwhile;
	
  echo '</tbody></table>';
	wp_reset_postdata();	
}

add_shortcode( 'brewtable', 'show_brewtable' );

?>