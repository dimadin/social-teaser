<?php

/**
 * The Social Teaser Plugin
 *
 * Publish to your social accounts, what you want.
 *
 * @package Social_Teaser
 * @subpackage Social_Teaser_Service
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Parent class for service supported by Social Teaser.
 *
 * A Service is a remote site/service/system for which Social Teaser
 * is capable of managing authentication. Each Service should have
 * a name and method for publishing to it.
 */
abstract class Social_Teaser_Service {
	/**
	 * Name of the service.
	 */
	const NAME = '';

	/**
	 * Publish to this service using data provided.
	 *
	 * @access public
	 *
	 * @param Keyring_Token         $token    Token of service that should be publishe to.
	 * @param Social_Teaser_Service $instance Object of Social_Teaser_Service child class.
	 * @param array                 $args     An array with data related to publishing.
	 * @return string $request Response from service.
	 */
	abstract public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args );

	/**
	 * Initialize Social_Teaser_Service object.
	 *
	 * Look if service was registered, if it was not,
	 * register it before returning.
	 *
	 * @access public
	 *
	 * @return Social_Teaser_Service $instance Object of Social_Teaser_Service child class.
	 */
	public static function &init() {
		static $instance = false;

		if ( !$instance ) {
			$class = get_called_class();
			$services = Social_Teaser::get_registered_services();
			if ( in_array( $class::NAME, array_keys( $services ) ) ) {
				$instance = $services[ $class::NAME ];
			} else {
				$instance = new $class;
				Social_Teaser::register_service( $instance );
			}
		}

		return $instance;
	}

	/**
	 * Get tokens for service.
	 *
	 * @access public
	 *
	 * @return array $tokens An array of service tokens for current user.
	 */
	public static function get_tokens() {
		$class = get_called_class();
		$keyring_service_class = Keyring::get_service_by_name( $class::NAME );

		return $keyring_service_class->get_tokens();
	}

	/**
	 * Publish to selected token.
	 *
	 * @access public
	 *
	 * @param Keyring_Token $token Token of service that should be publishe to.
	 * @param array         $args  An array with data related to publishing.
	 * @return string|bool $request Response from service if succesfull or false.
	 */
	public static function publish_to_token( $token, $args ) {
		// Get class for service in Keyring from token
		$keyring_service_class = Keyring::get_service_by_name( $token->name );

		// Get class for service in Social Teaser from token's name
		$social_teaser_service_class = Social_Teaser::get_service_by_name( $token->name );

		// Initialize class for service in Keyring
		$keyring_service = $keyring_service_class::init();

		// Set token for class for service in Keyring
		$keyring_service->set_token( $token );

		// If there is a Keyring service, publish using Social Teaser's service
		if ( $keyring_service && $keyring_service->get_token() ) {
			$request = $social_teaser_service_class::publish( $token, $keyring_service, $args );

			// If there was error return false
			if ( Keyring_Util::is_error( $request ) ) {
				return false;
			} else {
				return $request;
			}
		}

		return false;
	}
}
