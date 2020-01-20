<?php
/**
 * The class that sets up
 * global plugin functionality.
 *
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @category    Class
 * @class       My_Multi_Author_Global
 * @package     My Muli Author Plugin
 */
final class My_Multi_Author_Global {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		// Load our textdomain.
		add_action( 'plugins_loaded', array( $plugin, 'textdomain' ) );

		// Filter the user query so it gets users who are multi authors.
		add_action( 'pre_user_query', array( $plugin, 'filter_user_query_for_multi_authors' ) );

		// Filter the post query so it gets posts for multi authors.
		add_filter( 'posts_clauses', array( $plugin, 'filter_post_query_for_multi_authors' ), 10, 2 );

		// Set the post author data to the author being queried.
		add_action( 'the_post', array( $plugin, 'correct_post_author_data' ), 10, 2 );

		// Filter the author display name to get all authors.
		add_filter( 'the_author', array( $plugin, 'filter_the_author' ) );

		// Filters the REST API response so multi authors are added to post queries.
		$post_types = my_multi_author()->get_multi_author_post_types();
		foreach ( $post_types as $post_type ) {
			add_filter( 'rest_prepare_' . $post_type, [ $plugin, 'filter_rest_prepare_post' ], 10, 3 );
		}
	}

	/**
	 * Loads the plugin's text domain.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @return  void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'my-multi-author', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
	}

	/**
	 * Add multi author list to REST API requests.
	 *
	 * @param $response - WP_REST_Response - The response object.
	 * @param $post - WP_Post - Post object.
	 * @param $request - WP_REST_Request - Request object.
	 *
	 * @return mixed
	 */
	public function filter_rest_prepare_post( $response, $post, $request ) {

		$authors = my_multi_author()->get_authors( $post->ID );

		if ( empty( $authors ) ) {
			return $response;
		}

		$response->data['author'] = $authors;

		return $response;
	}

	/**
	 * When retrieving users, make sure we get users
	 * who are assigned as a multi author.
	 *
	 * Fires after the WP_User_Query has been parsed, and before
	 * the query is executed.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $query - WP_User_Query - The current WP_User_Query instance, passed by reference.
	 */
	public function filter_user_query_for_multi_authors( $query ) {
		global $wpdb;

		// Don't filter for search.
		if ( ! empty( $query->get( 'search' ) ) ) {
			return;
		}

		// Make sure we only get distinct users.
		$query->query_fields = preg_replace( '/(wp\_users\.(\*|(ID)))/i', 'DISTINCT $1', $query->query_fields );

		// Add to the "where" to make sure we get users who are multi authors.
		$query->query_where .= $wpdb->prepare( " OR wp_users.ID IN ( SELECT DISTINCT postmeta.meta_value FROM {$wpdb->postmeta} postmeta INNER JOIN {$wpdb->posts} userposts ON userposts.ID = postmeta.post_id AND userposts.post_status = 'publish' WHERE postmeta.meta_key = %s AND postmeta.meta_value IS NOT NULL AND postmeta.meta_value != '' ) ", my_multi_author()->multi_author_meta_key );

	}

	/**
	 * When retrieving posts for an author,
	 * make sure you retrieve posts where they
	 * are assigned as a multi author.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $pieces - array - The pieces of the query.
	 * @param   $query - WP_Query - The WP_Query instance (passed by reference).
	 * @return  array - the filtered pieces.
	 */
	public function filter_post_query_for_multi_authors( $pieces, $query ) {
		global $wpdb;

		/*
		 * If querying an author's posts,
		 * we have to make sure it includes
		 * posts where they were assigned as
		 * a multiple author.
		 */
		$get_author_id = $query->get( 'author' );
		if ( empty( $get_author_id ) ) {
			return $pieces;
		}

		// Create array version of author IDs.
		$get_author_id_array = $get_author_id;
		if ( ! is_array( $get_author_id_array ) ) {
			$get_author_id_array = explode( ',', str_replace( ' ', '', $get_author_id_array ) );
		}

		// Add a LEFT JOIN to get multi author post meta.
		$pieces['join'] .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} mameta ON mameta.post_id = {$wpdb->posts}.ID AND mameta.meta_key = %s AND mameta.meta_value IN (" . implode( ',', $get_author_id_array ) . ')', my_multi_author()->multi_author_meta_key );

		/*
		 * Add to where to get posts from post meta.
		 *
		 * We need to first look for the posts table
		 * post author check because if it exists we need our clause
		 * to be added as an "OR" because the results would be
		 * blank if a multi author had no default author posts.
		 */
		$post_author_where1 = count( $get_author_id_array ) == 1 ? $wpdb->prepare( "{$wpdb->posts}.post_author = %d", $get_author_id ) : null;
		if ( $post_author_where1 && false !== strpos( $pieces['where'], $post_author_where1 ) ) {
			$pieces['where'] = str_replace( $post_author_where1, $post_author_where1 . ' OR mameta.post_id IS NOT NULL', $pieces['where'] );
			return $pieces;
		}

		// Check another post author request.
		$post_author_where2 = "{$wpdb->posts}.post_author IN (" . implode( ',', $get_author_id_array ) . ')';
		if ( false !== strpos( $pieces['where'], $post_author_where2 ) ) {
			$pieces['where'] = str_replace( $post_author_where2, "( {$post_author_where2} OR mameta.post_id IS NOT NULL )", $pieces['where'] );
		} else {

			// Use the default "where".
			$pieces['where'] .= ' AND mameta.post_id IS NOT NULL';
		}

		return $pieces;
	}

	/**
	 * Runs when post data has been setup
	 * to set the post author data to the
	 * author being queried.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $post - WP_Post - The Post object (passed by reference).
	 * @param   $query - WP_Query - The current Query object (passed by reference).
	 * @return  void
	 */
	public function correct_post_author_data( $post, $query ) {
		global $authordata;

		// Only need to run on author pages.
		if ( ! is_author() ) {
			return;
		}

		// Get the queried author.
		$author = get_queried_object();
		if ( empty( $author ) ) {
			return;
		}

		// Set the post author data to the author being queried.
		$authordata = $author;

	}

	/**
	 * Filters the display name of the current post's author.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $author - string - The author's display name.
	 * @return  string - the filtered author name.
	 */
	public function filter_the_author( $author ) {
		global $post;

		// Get the multi authors.
		$authors = my_multi_author()->get_authors( $post->ID );
		if ( empty( $authors ) ) {
			return $author;
		}

		// Convert author ID to display name.
		$authors = array_map( function( $author_id ) {
			return get_the_author_meta( 'display_name', $author_id );
		}, $authors );

		if ( empty( $authors ) ) {
			return $author;
		}

		return implode( ', ', $authors );
	}
}
My_Multi_Author_Global::register();
