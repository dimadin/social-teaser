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
	 * @param Keyring_Token         $token    Token of service that should be publishe to.
	 * @param Social_Teaser_Service $instance Object of Social_Teaser_Service child class.
	 * @param array                 $args     An array with data related to publishing.
	 * @return string $request Response from service.
	 */
	public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$message   = '';
		$permalink = '';

		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$permalink = get_permalink( $args['post_id'] );

			$message = $title;
		}

		// Prepare actual body of request
		$body = array( 'message' => $message, 'link' => $permalink );

		/**
		 * Filter Facebook request's body.
		 *
		 * @param array $body Data passed used in Facebook request.
		 * @param array $args An array with data related to publishing.
		 */
		$body = (array) apply_filters( 'social_teaser_service_facebook_body', $body, $args );

		$request = $keyring_service->request(
			'https://graph.facebook.com/me/feed',
			array(
				'method'  => 'POST',
				'timeout' => 100,
				'body'    => $body,
			)
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
