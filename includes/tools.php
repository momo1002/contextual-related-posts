<?php
/**
 * Tool functions
 *
 * @package   Contextual_Related_Posts
 * @author    Ajay D'Souza <me@ajaydsouza.com>
 * @license   GPL-2.0+
 * @link      https://webberzone.com
 * @copyright 2009-2015 Ajay D'Souza
 */

/**
 * Function to create an excerpt for the post.
 *
 * @since 1.6
 *
 * @param int $id Post ID
 * @param int|string $excerpt_length Length of the excerpt in words
 * @return string Excerpt
 */
function crp_excerpt( $id, $excerpt_length = 0, $use_excerpt = true ) {
	$content = $excerpt = '';

	if ( $use_excerpt ) {
		$content = get_post( $id )->post_excerpt;
	}
	if ( '' == $content ) {
		$content = get_post( $id )->post_content;
	}

	$output = strip_tags( strip_shortcodes( $content ) );

	if ( $excerpt_length > 0 ) {
		$output = wp_trim_words( $output, $excerpt_length );
	}

	/**
	 * Filters excerpt generated by CRP.
	 *
	 * @since	1.9
	 *
	 * @param	array	$output			Formatted excerpt
	 * @param	int		$id				Post ID
	 * @param	int		$excerpt_length	Length of the excerpt
	 * @param	boolean	$use_excerpt	Use the excerpt?
	 */
	return apply_filters( 'crp_excerpt', $output, $id, $excerpt_length, $use_excerpt );
}


/**
 * Function to limit content by characters.
 *
 * @since 1.8.4
 *
 * @param	string 	$content 	Content to be used to make an excerpt
 * @param	int 	$no_of_char	Maximum length of excerpt in characters
 * @return 	string				Formatted content
 */
function crp_max_formatted_content( $content, $no_of_char = -1 ) {
	$content = strip_tags( $content );  // Remove CRLFs, leaving space in their wake

	if ( ( $no_of_char > 0 ) && ( strlen( $content ) > $no_of_char ) ) {
		$aWords = preg_split( "/[\s]+/", substr( $content, 0, $no_of_char ) );

		// Break back down into a string of words, but drop the last one if it's chopped off
		if ( substr( $content, $no_of_char, 1 ) == " " ) {
		  $content = implode( " ", $aWords );
		} else {
		  $content = implode( " ", array_slice( $aWords, 0, -1 ) ) .'&hellip;';
		}
	}

	/**
	 * Filters formatted content after cropping.
	 *
	 * @since	1.9
	 *
	 * @param	string	$content	Formatted content
	 * @param	int		$no_of_char	Maximum length of excerpt in characters
	 */
	return apply_filters( 'crp_max_formatted_content' , $content, $no_of_char );
}


/**
 * Delete the CRP cache.
 *
 * @param	array	$meta_keys
 */
function crp_cache_delete( $meta_keys = array() ) {

	$default_meta_keys = crp_cache_get_keys();

	if ( ! empty( $meta_keys ) ) {
		$meta_keys = array_intersect( $default_meta_keys, (array) $meta_keys );
	} else {
		$meta_keys = $default_meta_keys;
	}

	foreach ( $meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}
}

/**
 * Get the default meta keys used for the cache
 *
 */
function crp_cache_get_keys() {

	$meta_keys = array(
		'crp_related_posts',
		'crp_related_posts_widget',
		'crp_related_posts_feed',
		'crp_related_posts_widget_feed',
		'crp_related_posts_manual',
	);

	/**
	 * Filters the array containing the various cache keys.
	 *
	 * @since	1.9
	 *
	 * @param	array	$default_meta_keys	Array of meta keys
	 */
	return apply_filters( 'crp_cache_keys', $meta_keys );
}

/**
 * Create the FULLTEXT index.
 *
 * @since	2.2.1
 *
 */
function crp_create_index() {
	global $wpdb;

    $wpdb->hide_errors();

	// If we're running mySQL v5.6, convert the WPDB posts table to InnoDB, since InnoDB supports FULLTEXT from v5.6 onwards
	if ( version_compare( 5.6, $wpdb->db_version(), '<=' ) ) {
		$wpdb->query( 'ALTER TABLE ' . $wpdb->posts . ' ENGINE = InnoDB;' );
	} else {
		$wpdb->query( 'ALTER TABLE ' . $wpdb->posts . ' ENGINE = MYISAM;' );
	}

	$wpdb->query( 'ALTER TABLE ' . $wpdb->posts . ' ADD FULLTEXT crp_related (post_title, post_content);' );
    $wpdb->query( 'ALTER TABLE ' . $wpdb->posts . ' ADD FULLTEXT crp_related_title (post_title);' );
    $wpdb->query( 'ALTER TABLE ' . $wpdb->posts . ' ADD FULLTEXT crp_related_content (post_content);' );

    $wpdb->show_errors();

}


/**
 * Delete the FULLTEXT index.
 *
 * @since	2.2.1
 *
 */
function crp_delete_index() {
	global $wpdb;

    $wpdb->hide_errors();

	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related'" ) ) {
		$wpdb->query( "ALTER TABLE " . $wpdb->posts . " DROP INDEX crp_related" );
	}
	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_title'" ) ) {
		$wpdb->query( "ALTER TABLE " . $wpdb->posts . " DROP INDEX crp_related_title" );
	}
	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_content'" ) ) {
		$wpdb->query( "ALTER TABLE " . $wpdb->posts . " DROP INDEX crp_related_content" );
	}

    $wpdb->show_errors();

}


