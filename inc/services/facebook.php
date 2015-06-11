<?php

/**
 * The Social Teaser Plugin
 *
 * Publish to your social accounts, what you want.
 *
 * @package Social_Teaser
 * @subpackage Social_Teaser_Service_Facebook
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Facebook service definition for Social_Teaser.
 */
class Social_Teaser_Service_Facebook extends Social_Teaser_Service {
	/**
	 * Name of the service.
	 */
	const NAME  = 'facebook';

	/**
	 * Add main method to appropriate hook.
	 *
	 * @access public
	 */
	public function __construct() {
		// Force Facebook to allow posting
		add_filter( 'keyring_facebook_scope', array( $this, 'force_scopes' ) );
	}

	/**
	 * Publish to Facebook using data provided.
	 *
	 * @access public
	 *
	 * @param Keyring_Token   $token           Token of service that should be published to.
	 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
	 * @param array           $args            An array with data related to publishing.
	 * @return string|Keyring_Error $request Response from service, error on failure.
	 */
	public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$message   = '';
		$permalink = '';

		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$permalink = get_permalink( $args['post_id'] );

			$message = $title;
		}

		// Prepare URL of request
		$url = 'https://graph.facebook.com/me/feed';

		/**
		 * Filter Facebook request's API endpoint.
		 *
		 * @param string          $url             API endpoint used in Facebook request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$url = (string) apply_filters( 'social_teaser_service_facebook_url', $url, $args, $token, $keyring_service );

		// Prepare actual body of request
		$body = array( 'message' => $message, 'link' => $permalink );

		/**
		 * Filter Facebook request's body.
		 *
		 * @param array           $body            Data used in Facebook request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$body = (array) apply_filters( 'social_teaser_service_facebook_body', $body, $args, $token, $keyring_service );

		// Prepare actual requests parameters
		$request_params = array(
			'method'  => 'POST',
			'timeout' => 100,
			'body'    => $body,
		);

		/**
		 * Filter Facebook request's parameters.
		 *
		 * @param array           $request_params  Request parameters used in Facebook request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$request_params = (array) apply_filters( 'social_teaser_service_facebook_request_params', $request_params, $args, $token, $keyring_service );

		// Check that URL is not empty
		if ( ! $url ) {
			return new Keyring_Error( 'social-teaser-empty-url', __( 'URL is empty.', 'social-teaser' ) );
		}

		// Make request
		$request = $keyring_service->request(
			$url,
			$request_params
		);

		return $request;
	}

	/**
	 * Request publishing permission from Facebook.
	 *
	 * @access public
	 *
	 * @param array $scopes An array of scopes used in request.
	 * @return array $scopes Modified array of scopes used in request.
	 */
	public function force_scopes( $scopes ) {
		$scopes[] = 'publish_actions';

		return $scopes;
	}
}

add_action( 'keyring_load_services', array( 'Social_Teaser_Service_Facebook', 'init' ) );
