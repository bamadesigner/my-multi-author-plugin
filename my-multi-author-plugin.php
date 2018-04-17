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

defined( 'ABSPATH' ) or die();

require_once my_multi_author()->plugin_dir . 'inc/class-my-multi-author-global.php';

if ( is_admin() ) {
	require_once my_multi_author()->plugin_dir . 'inc/class-my-multi-author-admin.php';
}

/**
 * Main PHP class that holds the basic
 * functionality for the plugin.
 *
 * @category    Class
 * @package     My Multi Author Plugin
 */
final class My_Multi_Author {

	/**
	 * Defines which post
	 * types to assign multi authors.
	 *
	 * Access via get_multi_author_post_types()
	 * inside this class.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array
	 */
	private $multi_author_post_types;

	/**
	 * Defines the meta key for the
	 * multi author authors.
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
	 * @access  public
	 * @var     string
	 */
	public $plugin_dir;
	public $plugin_url;

	/**
	 * Holds the class instance.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     My_Multi_Author
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
	 * @access  protected
	 */
	protected function __construct() {

		// Store the plugin DIR and URL.
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

	}

	/**
	 * Get the post types we want to
	 * set as multi author.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @return  array - the post types.
	 */
	public function get_multi_author_post_types() {

		// If set, return the settings
		if ( isset( $this->multi_author_post_types ) ) {
			return $this->multi_author_post_types;
		}

		// Define the post types. 'post' is the default.
		$multi_author_post_types = apply_filters( 'my_multi_author_post_types', array( 'post' ) );

		// Make sure it's an array.
		if ( ! is_array( $multi_author_post_types ) ) {
			$multi_author_post_types = explode( ',', str_replace( ' ', '', $multi_author_post_types ) );
		}

		// Set/return post types.
		return $this->multi_author_post_types = $multi_author_post_types;
	}

	/**
	 * Alphabetize authors by last name
	 * Takes on array of author ID's
	 * Returns array of author objects, alphabetized by author last name
	 *
	 * @since   1.0.0
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
		$authors = apply_filters( 'my_multi_authors', $authors, $post_id );

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
	 * @since   1.0.0
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
		if ( empty( $authors ) ) {
			return false;
		}

		// Build array of author markup.
		$authors_list = array();

		// Add each author.
		foreach ( $authors as $author_id ) {
			$authors_list[] = '<a href="' . esc_url( get_author_posts_url( $author_id ) ) . '">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
		}

		if ( empty( $authors_list ) ) {
			return false;
		}

		return implode( ', ', $authors_list );
	}
}

/**
 * Returns the instance of our main My_Multi_Author class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin
 * and other plugins and themes.
 *
 * @since   1.0.0
 * @return object - My_Multi_Author
 */
function my_multi_author() {
	return My_Multi_Author::instance();
}

// Let's get this party started.
my_multi_author();
