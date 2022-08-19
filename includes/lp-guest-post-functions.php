<?php
if (!defined('ABSPATH')) {
	exit;
}
if (!class_exists('LPGuestPostFunctions')) {
	/**
	 * This class is used to load general dependencies and common functions used by shortcodes.
	 *
	 * When loaded, it loads the CSS & JS files and add functions to hooks or filters. The class also handles the post type
	 *
	 * @since 1.0.0
	*/
	class LPGuestPostFunctions {
		public function __construct() {
			/**
			 * Add Hooks & Functions.
			 */
			add_action('plugins_loaded', array(&$this,'load_textdomain'));
			add_action('init', array($this, 'register_guest_post_type'));
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
			add_action('pre_get_posts', array($this, 'hide_attachments'));
		}

		/**
		 * Register required scripts and styles for front-end
		 * @since 1.0.0
		 * @return void
		 */
		public function wp_enqueue_scripts() {


			wp_register_script('lp-guest-post-submission-front-js', LP_GUEST_POST_SUBMIT_ASSETS_DIR . '/js/lp-guest-post-submission-front-js.js', array('jquery'), LP_GUEST_POST_SUBMIT_VER, true);
			wp_localize_script('lp-guest-post-submission-front-js', 'lpgp_obj', array(
				'admin_ajax_url' => admin_url('admin-ajax.php'),
				'submit_btn_caption' => __('Submit', LP_GUEST_POST_SUBMIT_TXTDOMAIN),
				'please_wait_caption' => __('Please wait..', LP_GUEST_POST_SUBMIT_TXTDOMAIN),
			));

			wp_register_style('bootstrap-min-css', LP_GUEST_POST_SUBMIT_ASSETS_DIR . '/css/bootstrap.min.css');
		}

		/**
		 * Register Post Type
		 * @since 1.0.0
		 * @return void
		 */
		public function register_guest_post_type(){
			$labels = array(
				'name'                => _x( 'Guest Post', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'singular_name'       => _x( 'Guest Post', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'menu_name'           => __( 'Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'parent_item_colon'   => __( 'Parent Guest Post', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'all_items'           => __( 'All Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'view_item'           => __( 'View Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'add_new_item'        => __( 'Add New Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'add_new'             => __( 'Add New', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'edit_item'           => __( 'Edit Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'update_item'         => __( 'Update Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'search_items'        => __( 'Search Guest Posts', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'not_found'           => __( 'Not Found', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'not_found_in_trash'  => __( 'Not found in Trash', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
			);
			
			$args = array(
				'label'               => __( 'Guest Post', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'description'         => __( 'Guest Post', LP_GUEST_POST_SUBMIT_TXTDOMAIN ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author'),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 20,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'menu_icon'  		  => 'dashicons-text-page',
				'capability_type'     => 'post',
			);
			
			register_post_type( 'guest-post', $args );
		}
		
		/**
		 * Load text domain
		 *
		 * @since 1.0.0
		 * @return void
		*/
		public function load_textdomain() {
			load_plugin_textdomain(LP_GUEST_POST_SUBMIT_TXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/language/');
		}
		
		/**
		 * Show only logged in user's attachment and hide other user's attachments
		 *
		 * @since 1.0.0
		 * @return void
		*/
		public function hide_attachments( $wp_query_obj ){
			global $current_user, $pagenow;
			if ( $pagenow == 'upload.php' || ( $pagenow == 'admin-ajax.php' && !empty( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'query-attachments' ) ) {
				$wp_query_obj->set( 'author', $current_user->ID );
			}
		}
	}
}
/**
 * Guest Post Functions Object to initialize constructor
 */
$LPGuestPostFunctionsObj = new LPGuestPostFunctions();
