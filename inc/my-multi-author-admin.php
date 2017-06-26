<?php
/**
 * PHP class that holds the admin
 * functionality for the plugin.
 *
 * @category    Class
 * @package     My Muli Author Plugin
 */
class My_Multi_Author_Admin {

	/**
	 * Holds the class instance.
	 *
	 * @access  private
	 * @var     My_Multi_Author_Admin
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return  My_Multi_Author_Admin
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

		// Remove default author meta box.
		add_action( 'admin_init', array( $this, 'remove_author_meta_box' ) );

		// Add meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

		// Add admin styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		// Save meta box data.
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 3 );

		// Add custom columns.
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_columns' ), 10, 2 );

		// Add multi authors to the author column.
		add_action( 'manage_posts_custom_column', array( $this, 'populate_posts_columns' ), 10, 2 );

	}

	/**
	 * Removes the default author meta box so
	 * we can replace with our custom meta box.
	 *
	 * @access  public
	 * @return  void
	 */
	public function remove_author_meta_box() {
		remove_meta_box( 'authordiv', my_multi_author()->get_multi_author_post_types(), 'normal' );
	}

	/**
	 * Adds our custom meta boxes.
	 *
	 * @access  public
	 * @param   $post_type - string - the current post type.
	 * @param   $post - WP_Post - the current post object.
	 * @return  void
	 */
	public function add_meta_boxes( $post_type, $post ) {

		// Add our custom author meta box.
		add_meta_box( 'my-multi-author-authors-mb', __( 'Author(s)', 'my-multi-author' ), array( $this, 'print_author_meta_box' ), my_multi_author()->get_multi_author_post_types(), 'normal', 'high' );

	}

	/**
	 * Enqueue the styles and scripts for the admin.
	 *
	 * @access  public
	 * @param   $hook_suffix - string - ID for the current page.
	 * @return  void
	 */
	public function enqueue_scripts_styles( $hook_suffix ) {
		global $post_type;

		// Only add when editing our post types.
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// Only for our post types.
		if ( ! in_array( $post_type, my_multi_author()->get_multi_author_post_types() ) ) {
			return;
		}

		// Register the select2 script.
		wp_register_script( 'select2', my_multi_author()->plugin_url . 'assets/js/select2.min.js', array(), false, true );

		// Enqueue our admin script.
		wp_enqueue_script( 'my-multi-authors-admin', my_multi_author()->plugin_url . 'assets/js/my-multi-authors-admin.min.js', array( 'jquery', 'select2' ), false, true );

		// Register the select2 styles.
		wp_register_style( 'select2', my_multi_author()->plugin_url . 'assets/css/select2.min.css' );

		// Enqueue our admin styles.
		wp_enqueue_style( 'my-multi-authors-admin', my_multi_author()->plugin_url . 'assets/css/my-multi-authors-admin.min.css', array( 'select2' ) );

	}

	/**
	 * Prints our custom author meta box.
	 *
	 * @access  public
	 * @param   $post - WP_Post - the current post object.
	 * @return  void
	 */
	public function print_author_meta_box( $post ) {
		global $user_ID;

		// Add a nonce field so we can check for it when saving the data.
		wp_nonce_field( 'my_multi_authors_save_authors', 'my_multi_authors_save_authors_nonce' );

		// Get primary author ID.
		$primary_author_id = empty( $post->post_author ) ? $user_ID : $post->post_author;

		// Get existing authors.
		$existing_my_multi_authors = get_post_meta( $post->ID, my_multi_author()->multi_author_meta_key, false );

		// Make sure it's an array.
		if ( ! is_array( $existing_my_multi_authors ) ) {
			$existing_my_multi_authors = implode( ',', $existing_my_multi_authors );
		}

		?>
		<p><strong><label for="post_author_override"><?php _e( 'Set the primary author:', 'my-multi-author' ); ?></label></strong></p>
		<?php

		wp_dropdown_users( apply_filters( 'my_multi_author_post_author_dropdown_args', array(
			'id'                => 'my-multi-authors-primary',
			'who'               => 'authors',
			'name'              => 'post_author_override',
			'selected'          => $primary_author_id,
			'include_selected'  => true,
			'show'              => 'display_name_with_login',
		), $post ));

		?>
		<p><strong><label><?php _e( 'Define additional authors:', 'my-multi-author' ); ?></label></strong></p>
		<div id="my-multi-authors-select">
			<?php

			$author_index = 0;

			do {

				// Get selected author.
				$selected = isset( $existing_my_multi_authors[ $author_index ] ) ? $existing_my_multi_authors[ $author_index ] : 0;

				?>
				<div class="my-multi-authors-author">
					<div class="my-multi-authors-author-delete"></div>
					<div class="my-multi-authors-author-select">
						<label for="my_multi_author_authors<?php echo $author_index; ?>" class="my-multi-authors-label screen-reader-text"><?php _e( 'Define an additional author', 'my-multi-author' ); ?></label>
						<?php

						wp_dropdown_users( array(
							'id'                => 'my-multi-authors-autocomplete' . $author_index,
							'class'             => 'my-multi-authors-autocomplete',
							'name'              => 'my_multi_authors[]',
							'selected'          => $selected,
							//'exclude'           => $primary_author_id,
							'include_selected'  => true,
							'show'              => 'display_name_with_login',
							'show_option_none'  => __( 'Select an author', 'my-multi-author' ),
							'orderby'           => 'display_name',
							'order'             => 'ASC',
						));

						?>
					</div>
				</div>
				<?php

				$author_index++;

			} while ( $author_index < count( $existing_my_multi_authors ) );

			?>
		</div>
		<div id="my-multi-authors-add-author" class="button"><?php _e( 'Add author', 'my-multi-author' ); ?></div>
		<?php
	}

	/**
	 * When the post is saved, saves our custom meta box data.
	 *
	 * @access  public
	 * @param   int - $post_id - the ID of the post being saved
	 * @param   WP_Post - $post - the post object
	 * @param   bool - $update - whether this is an existing post being updated or not
	 * @return  void
	 */
	function save_meta_box_data( $post_id, $post, $update ) {

		// Disregard on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Not for auto drafts.
		if ( 'auto-draft' == $post->post_status ) {
			return;
		}

		// Only for certain post types.
		if ( ! in_array( $post->post_type, my_multi_author()->get_multi_author_post_types() ) ) {
			return;
		}

		// Check if our nonce is set because the 'save_post' action can be triggered at other times.
		if ( ! isset( $_POST['my_multi_authors_save_authors_nonce'] ) ) {
			return;
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $_POST['my_multi_authors_save_authors_nonce'], 'my_multi_authors_save_authors' ) ) {
			return;
		}

		// Get the primary author ID.
		$primary_author_id = isset( $_POST['post_author'] ) ? $_POST['post_author'] : 0;

		// Get the new multi authors setting.
		$new_my_multi_authors = isset( $_POST['my_multi_authors'] ) ? $_POST['my_multi_authors'] : array();
		if ( ! empty( $new_my_multi_authors ) ) {

			// Make sure its an array.
			if ( ! is_array( $new_my_multi_authors ) ) {
				$new_my_multi_authors = explode( ',', $new_my_multi_authors );
			}

			// Make sure it has only IDs.
			$new_my_multi_authors = array_filter( $new_my_multi_authors, 'is_numeric' );

			// Convert to integers.
			$new_my_multi_authors = array_map( 'intval', $new_my_multi_authors );

			// Remove duplicates.
			$new_my_multi_authors = array_unique( $new_my_multi_authors );

			// Make sure only integers > 0.
			$new_my_multi_authors = array_filter( $new_my_multi_authors, function( $author_id ) {
				return $author_id > 0;
			});

			// Remove empty elements.
			$new_my_multi_authors = array_filter( $new_my_multi_authors );

			// Make sure primary author is not in multi authors.
			$primary_author_search = array_search( $primary_author_id, $new_my_multi_authors );
			if ( $primary_author_search !== false ) {
				unset( $new_my_multi_authors[ $primary_author_search ] );
			}
		}

		// Get the multi authors meta key.
		$multi_author_meta_key = my_multi_author()->multi_author_meta_key;

		// If no authors are set, delete all existing authors.
		if ( empty( $new_my_multi_authors ) ) {
			delete_post_meta( $post_id, $multi_author_meta_key );
		} else {

			// Get existing authors.
			$existing_my_multi_authors = get_post_meta( $post_id, $multi_author_meta_key, false );

			// Go through existing authors and update.
			foreach ( $existing_my_multi_authors as $author_id ) {

				/*
				 * If the existing author is not in
				 * the new author set, then delete.
				 *
				 * Otherwise, remove from new set.
				 */
				if ( ! in_array( $author_id, $new_my_multi_authors ) ) {
					delete_post_meta( $post_id, $multi_author_meta_key, $author_id );
				} else {
					unset( $new_my_multi_authors[ array_search( $author_id, $new_my_multi_authors ) ] );
				}
			}

			// Go through and add new authors.
			if ( ! empty( $new_my_multi_authors ) ) {
				foreach ( $new_my_multi_authors as $author_id ) {
					add_post_meta( $post_id, $multi_author_meta_key, $author_id, false );
				}
			}
		}
	}

	/**
	 * Add custom admin columns.
	 *
	 * @access  public
	 * @param   $columns - array - An array of column names.
	 * @param   $post_type - string - The post type slug.
	 * @return  array - the filtered column names.
	 */
	public function add_posts_columns( $columns, $post_type ) {

		// Only for these post types.
		if ( ! in_array( $post_type, my_multi_author()->get_multi_author_post_types() ) ) {
			return $columns;
		}

		// Store new columns.
		$new_columns = array();

		// Move over/modify columns.
		foreach ( $columns as $key => $value ) {

			// Replace the author column.
			if ( 'author' == $key ) {
				$new_columns['my-multi-authors'] = __( 'Author(s)', 'wpcampus' );
			} else {

				// Move over columns.
				$new_columns[ $key ] = $value;

			}
		}

		return $new_columns;
	}

	/**
	 * Populate our custom admin columns.
	 *
	 * @access  public
	 * @param   $column - string - The name of the column to display.
	 * @param   $post_id - int - The current post ID.
	 * @return  void
	 */
	public function populate_posts_columns( $column, $post_id ) {
		global $post_type;

		// Add data for our custom authors column.
		if ( 'my-multi-authors' == $column ) {

			// Get the post's author(s).
			$authors = my_multi_author()->get_authors();
			if ( empty( $authors ) ) {
				$authors = array( get_the_author_meta( 'ID' ) );
			}

			// Print list of authors.
			if ( ! empty( $authors ) ) {
				foreach( $authors as $author_id ) {

					// Build the URL.
					$url = add_query_arg( array(
						'post_type' => $post_type,
						'author'    => $author_id,
					), 'edit.php' );

					// Print the author URL.
					echo sprintf(
						'<a href="%s">%s</a><br />',
						esc_url( $url ),
						get_the_author_meta( 'display_name', $author_id )
					);
				}
			}
		}
	}
}

/**
 * Returns the instance of our main My_Multi_Author_Admin class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin
 * and other plugins and themes.
 *
 * @return object - My_Multi_Author_Admin
 */
function my_multi_author_admin() {
	return My_Multi_Author_Admin::instance();
}

// Let's get this party started.
my_multi_author_admin();
