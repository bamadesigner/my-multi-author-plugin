<?php
/**
 * Plugin Name:     My Multi Author Plugin
 * Plugin URI:      https://github.com/bamadesigner/my-multi-author-plugin
 * Description:     Allows multiple authors to be attributed to a single post.
 * Version:         1.0.0
 * Author:          Rachel Cherry
 * Author URI:      https://bamadesigner.com
 * Text Domain:     my-multi-author
 * Domain Path:     /languages
 *
 * @package         My Multi Author Plugin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// We only need admin functionality in the admin.
if ( is_admin() ) {
	require_once my_multi_author()->plugin_dir . 'inc/my-multi-author-admin.php';
}

/**
 * Main PHP class that holds the basic
 * functionality for the plugin.
 *
 * @category    Class
 * @package     My Multi Author Plugin
 */
class My_Multi_Author {

	/**
	 * Defines which post
	 * types to assign multi authors.
	 *
	 * @access  public
	 * @var     array
	 */
	public $multi_author_post_types = array( 'post', 'podcast', 'video' );

	/**
	 * Defines the meta key for the
	 * multi author authors.
	 *
	 * @access  public
	 * @var     string
	 */
	public $multi_author_meta_key = 'my_multi_author_authors';

	/**
	 * $plugin_dir holds the directory
	 * path to the main plugin directory.
	 * Used for loading files.
	 *
	 * $plugin_url holds the absolute URL
	 * to the main plugin directory.
	 * Used for loading assets.
	 *
	 * @access  public
	 * @var     string
	 */
	public $plugin_dir;
	public $plugin_url;

	/**
	 * Holds the class instance.
	 *
	 * @access  private
	 * @var     My_Multi_Author
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return  My_Multi_Author
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Constructing the class object.
	 *
	 * The constructor is protected to prevent
	 * creating a new instance from outside of this class.
	 *
	 * @access  protected
	 */
	protected function __construct() {

		// Store the plugin DIR and URL.
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		// Load our textdomain.
		add_action( 'plugins_loaded', array( $this, 'textdomain' ) );

		// Filter the user query so it gets users who are multi authors.
		add_action( 'pre_user_query', array( $this, 'filter_user_query_for_multi_authors' ) );

		// Filter the post query so it gets posts for multi authors.
		add_filter( 'posts_clauses', array( $this, 'filter_post_query_for_multi_authors' ), 10, 2 );

		// Set the post author data to the author being queried.
		add_action( 'the_post', array( $this, 'correct_post_author_data' ), 10, 2 );

		// Filter the author display name to get all authors.
		add_filter( 'the_author', array( $this, 'filter_the_author' ) );

	}

	/**
	 * Loads the plugin's text domain.
	 *
	 * @access  public
	 * @return  void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'my-multi-author', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Alphabetize authors by last name
	 * Takes on array of author ID's
	 * Returns array of author objects, alphabetized by author last name
	 *
	 * @access  public
	 * @param   $post_id - int - the post ID.
	 * @return  array - author information.
	 */
	public function get_authors( $post_id = 0 ) {

		// Make sure we have a post ID.
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return false;
		}

		// Get our multi authors.
		$authors = get_post_meta( $post_id, $this->multi_author_meta_key, false );

		// Make sure its an array.
		if ( empty( $authors ) ) {
			$authors = array();
		} elseif ( ! is_array( $authors ) ) {
			$authors = explode( ',', $authors );
		}

		// Make sure we include the default author at the top.
		$default_author = get_post_field( 'post_author', $post_id );
		if ( $default_author > 0 ) {
			array_unshift( $authors, $default_author );
		}

		// Filter the author IDs.
		$authors = apply_filters( 'my_multi_author_ids', $authors, $post_id );

		// Make sure it has only IDs.
		$authors = array_filter( $authors, 'is_numeric' );

		// Convert to integers.
		$authors = array_map( 'intval', $authors );

		// Remove duplicates.
		$authors = array_unique( $authors );

		return $authors;
	}

	/**
	 * Returns an HTML list of the authors
	 * for a specified post, or the current post,
	 * with links to the author's archive.
	 *
	 * @access  public
	 * @param   $post_id - int - the post ID, current post otherwise.
	 * @return  string - the HTML list of authors.
	 */
	public function get_the_authors_list( $post_id = 0 ) {

		// Make sure we have a post ID.
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Get the authors.
		$authors = $this->get_authors( $post_id );
		if ( ! empty( $authors ) ) :

			// Build array of author markup.
			$authors_list = array();

			// Add each author.
			foreach( $authors as $author_id ) :
				$authors_list[] = '<a href="' . esc_url( get_author_posts_url( $author_id ) ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
			endforeach;

			// Return author list.
			if ( ! empty( $authors_list ) ) {
				return implode( ', ', $authors_list );
			}
		endif;

		return false;
	}

	/**
	 * When retrieving users, make sure we get users
	 * who are assigned as a multi author.
	 *
	 * Fires after the WP_User_Query has been parsed, and before
	 * the query is executed.
	 *
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
		$query->query_where .= $wpdb->prepare( " OR wp_users.ID IN ( SELECT DISTINCT postmeta.meta_value FROM {$wpdb->postmeta} postmeta INNER JOIN {$wpdb->posts} userposts ON userposts.ID = postmeta.post_id AND userposts.post_status = 'publish' WHERE postmeta.meta_key = %s AND postmeta.meta_value IS NOT NULL AND postmeta.meta_value != '' ) ", $this->multi_author_meta_key );

	}

	/**
	 * When retrieving posts for an author,
	 * make sure you retrieve posts where they
	 * are assigned as a multi author.
	 *
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
		if ( ! empty( $get_author_id ) ) {

			// Create array version of author IDs.
			$get_author_id_array = $get_author_id;
			if ( ! is_array( $get_author_id_array ) ) {
				$get_author_id_array = explode( ',', str_replace( ' ', '', $get_author_id_array ) );
			}

			// Add a LEFT JOIN to get multi author post meta.
			$pieces['join'] .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} mameta ON mameta.post_id = {$wpdb->posts}.ID AND mameta.meta_key = %s AND mameta.meta_value IN (" . implode( ',', $get_author_id_array ) . ")", $this->multi_author_meta_key );

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
			} else {

				// Check another post author request.
				$post_author_where2 = "{$wpdb->posts}.post_author IN (" . implode( ',', $get_author_id_array ) . ")";
				if ( false !== strpos( $pieces['where'], $post_author_where2 ) ) {
					$pieces['where'] = str_replace( $post_author_where2, "( {$post_author_where2} OR mameta.post_id IS NOT NULL )", $pieces['where'] );
				} else {

					// Use the default "where".
					$pieces['where'] .= ' AND mameta.post_id IS NOT NULL';
				}
			}
		}

		return $pieces;
	}

	/**
	 * Runs when post data has been setup
	 * to set the post author data to the
	 * author being queried.
	 *
	 * @access  public
	 * @param   $post - WP_Post - The Post object (passed by reference).
	 * @param   $query - WP_Query - The current Query object (passed by reference).
	 * @return  void
	 */
	public function correct_post_author_data( $post, $query ) {
		global $authordata;

		// Only need to run on author pages.
		if ( is_author() ) {

			// Get the queried author.
			$author = get_queried_object();
			if ( ! empty( $author ) ) {

				// Set the post author data to the author being queried.
				$authordata = $author;

			}
		}
	}

	/**
	 * Filters the display name of the current post's author.
	 *
	 * @access  public
	 * @param   $author - string - The author's display name.
	 * @return  string - the filtered author name.
	 */
	public function filter_the_author( $author ) {
		global $post;

		// Get the multi authors.
		$authors = $this->get_authors( $post->ID );
		if ( ! empty( $authors ) ) {

			// Convert author ID to display name.
			$authors = array_map( function( $author_id ) {
				return get_the_author_meta( 'display_name', $author_id );
			}, $authors );

			if ( ! empty( $authors ) ) {
				return implode( ', ', $authors );
			}
		}

		return $author;
	}
}

/**
 * Returns the instance of our main My_Multi_Author class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin
 * and other plugins and themes.
 *
 * @return object - My_Multi_Author
 */
function my_multi_author() {
	return My_Multi_Author::instance();
}

// Let's get this party started.
my_multi_author();
