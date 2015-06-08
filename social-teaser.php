<?php

/**
 * The Social Teaser Plugin
 *
 * Publish to your social accounts, what you want.
 *
 * @package Social_Teaser
 * @subpackage Main
 */

/**
 * Plugin Name: Social Teaser
 * Plugin URI:  http://blog.milandinic.com/wordpress/plugins/
 * Description: Publish to your social accounts, what you want.
 * Author:      Milan Dinić
 * Author URI:  http://blog.milandinic.com/
 * Version:     0.1-alpha-1
 * License:     GPL
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Initialize a plugin.
 *
 * Load class when all plugins are loaded
 * so that other plugins can overwrite it.
 */
add_action( 'plugins_loaded', array( 'Social_Teaser', 'plugins_loaded' ), 15 );

if ( ! class_exists( 'Social_Teaser' ) ) :
/**
 * Social Teaser main class.
 *
 * Publish to your social accounts, what you want.
 */
class Social_Teaser {
	/**
	 * An array with registered services.
	 *
	 * @access protected
	 */
	protected $registered_services = array();

	/**
	 * Set class properties and add main methods to appropriate hooks.
	 *
	 * @access public
	 */
	public function __construct() {
		// Set path
		$this->path = rtrim( plugin_dir_path( __FILE__ ), '/' );

		// Register meta boxes
		add_filter( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' )        );

		// Publish when post is transitioned
		add_action( 'wp_insert_post',  array( $this, 'tease'               ), 10, 2 );
	}

	/**
	 * Initialize Social_Teaser object.
	 *
	 * @access public
	 */
	public static function &init() {
		static $instance = false;

		if ( !$instance ) {
			$instance = new Social_Teaser;
		}

		return $instance;
	}

	/**
	 * Load plugin.
	 *
	 * Load if there are Keyring and Meta Box plugins,
	 * otherwise use TGM Plugin Activation.
	 *
	 * @access public
	 */
	public static function plugins_loaded() {
		if ( ! defined( 'KEYRING__VERSION' ) || ! defined( 'RWMB_VER' ) ) {
			require __DIR__ . '/vendor/autoload.php';
			add_action( 'tgmpa_register', array( 'Social_Teaser', 'register_required_plugins' ) );
			return;
		}

		// Initialize class
		$social_teaser = Social_Teaser::init();

		// Load service defined by Social Teaser
		require_once $social_teaser->path . '/inc/service.php';

		// Load all packaged services in the ./inc/services/ directory by including all PHP files
		$social_teaser_services = glob( $social_teaser->path . "/inc/services/*.php" );

		// Remove a Service (prevent it from loading at all) by filtering on 'social_teaser_services'
		//$social_teaser_services = apply_filters( 'social_teaser_services', $social_teaser_services );

		foreach ( $social_teaser_services as $social_teaser_service ) {
			// TODO: check if corresponding Keyring sevice was loaded
			require $social_teaser_service;
		}
	}

	/**
	 * Register the required plugins for this plugin.
	 *
	 * @access public
	 */
	public static function register_required_plugins() {
		if ( ! function_exists( 'tgmpa' ) ) {
			return;
		}

		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(
			array(
				'name'             => 'Keyring',
				'slug'             => 'keyring',
				'required'         => true,
				'force_activation' => true,
			),
			array(
				'name'             => 'Meta Box',
				'slug'             => 'meta-box',
				'required'         => true,
				'force_activation' => true,
			),
		);

		tgmpa( $plugins );
	}

	/**
	 * Check if its object of Social_Teaser_Service class.
	 *
	 * @access public
	 *
	 * @param Social_Teaser_Service $service Object of Social_Teaser_Service child class.
	 * @return bool $status True if it's object, false if it's not.
	 */
	public static function is_service( $service ) {
		if ( is_object( $service ) && is_subclass_of( $service, 'Social_Teaser_Service' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register object of Social_Teaser_Service child class.
	 *
	 * @access public
	 *
	 * @param Social_Teaser_Service $service Object of Social_Teaser_Service child class.
	 * @return bool $status True if registered service, false if not.
	 */
	public static function register_service( Social_Teaser_Service $service ) {
		if ( Social_Teaser::is_service( $service ) ) {
			Social_Teaser::init()->registered_services[ $service::NAME ] = $service;
			return true;
		}

		return false;
	}

	/**
	 * Get all available services.
	 *
	 * @access public
	 *
	 * @return array $services An array with all active services.
	 */
	public static function get_registered_services() {
		return Social_Teaser::init()->registered_services;
	}

	/**
	 * Get service by name.
	 *
	 * @access public
	 *
	 * @param string $name Name of the service.
	 * @return Social_Teaser_Service $service Object of Social_Teaser_Service child class.
	 */
	public static function get_service_by_name( $name ) {
		$social_teaser = Social_Teaser::init();

		if ( !isset( $social_teaser->registered_services[ $name ] ) ) {
			return null;
		}

		return $social_teaser->registered_services[ $name ];
	}

	/**
	 * Publish to all selected accounts when post is transitioned.
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $_post   Post object.
	 */
	public function tease( $post_ID, $_post ) {
		// If new is 'publish' and old is not 'publish'
		if ( 'publish' != $_post->post_status ) {
			return;
		}

		// Was teasing already done
		if ( 1 == get_post_meta( $post_ID, '_social_teaser_done', true ) ) {
			return;
		}

		// Get all available services
		$services = Social_Teaser::get_registered_services();

		// Loop through all available services
		foreach ( $services as $name => $service ) {
			// Setup basic meta key
			$base_key = '_social_teaser_' . $name . '_';

			// Get Social Teaser service class
			$social_teaser_service_class = Social_Teaser::get_service_by_name( $name );

			// Get class for service in Keyring from token's name
			$keyring_service_class = Keyring::get_service_by_name( $name );

			// Get all meta for post
			$meta = get_post_meta( $_post->ID );

			// Loop though all meta and check do keys exist
			foreach ( $meta as $mid => $met ) {
				// Stringify meta key
				$meta_key = (string) $mid;

				// Check if there is a key that starts with corresponding name
				if ( false !== strpos( $meta_key, $base_key ) ) {
					// Extract post ID from meta key
					$token_id = str_replace( $base_key, '', $meta_key );

					// Get token from ID
					$token_args = array(
						'id'      => absint( $token_id ),
						'service' => $name,
					);
					$token = Keyring::get_token_store()->get_token( $token_args );

					// If there is a token, publish to it
					if ( $token ) {
						$args = array( 'post_id' => $_post->ID );
						$response = $social_teaser_service_class::publish_to_token( $token, $args );

						// Save response to meta
						add_post_meta( $_post->ID, $meta_key . '_response', $response );
					}
				}
			}
		}

		// Save to meta that teasing was done
		add_post_meta( $_post->ID, '_social_teaser_done', 1 );
	}

	/**
	 * Register meta boxes.
	 *
	 * Show all accounts on all service that are
	 * available to this user and save its values.
	 *
	 * @access public
	 *
	 * @param array $meta_boxes List of meta boxes.
	 * @return array $meta_boxes List of new meta boxes.
	 */
	public function register_meta_boxes( $meta_boxes ) {
		// Prefix of meta keys
		$prefix = '_social_teaser_';

		// Prepare checkboxes for all accounts of all services
		$fields = array();

		$services = Social_Teaser::get_registered_services();

		foreach ( $services as $name => $service ) {
			$label = Keyring::get_service_by_name( $name )->get_label();

			// Get tokens for this service
			// TODO: merge with global ones
			$tokens = $service->get_tokens();

			foreach ( $tokens as $token ) {
				$account_name = $token->get_meta( 'name' );
				// TODO: sprintify name
				$field_name = $label . ' (' . $account_name . ')';

				$field_id = $prefix . $name . '_' . $token->get_uniq_id();

				$fields[] = array(
					'name' => $field_name,
					'id'   => $field_id,
					'type' => 'checkbox',
					'std'  => 1,
				);
			}
		}

		// 1st meta box
		$meta_boxes[] = array(
			// Meta box title
			'title' => __( 'Social Teaser', 'social-teaser' ),
			// Post types that this appears on
			'post_types' => array( 'post', 'page' ),
			// Where the meta box appear
			'context' => 'normal',
			// Order of meta box
			'priority' => 'high',
			// Auto save
			'autosave' => false,
			// List of meta fields
			'fields' => $fields,
		);

		return $meta_boxes;
	}
}
endif;
