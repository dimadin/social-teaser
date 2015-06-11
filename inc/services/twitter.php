<?php

/**
 * The Social Teaser Plugin
 *
 * Publish to your social accounts, what you want.
 *
 * @package Social_Teaser
 * @subpackage Social_Teaser_Service_Twitter
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Twitter service definition for Social_Teaser.
 */
class Social_Teaser_Service_Twitter extends Social_Teaser_Service {
	/**
	 * Name of the service.
	 */
	const NAME  = 'twitter';

	/**
	 * Publish to Twitter using data provided.
	 *
	 * @access public
	 *
	 * @param Keyring_Token   $token           Token of service that should be published to.
	 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
	 * @param array           $args            An array with data related to publishing.
	 * @return string|Keyring_Error $request Response from service, error on failure.
	 */
	public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$status = '';

		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$shortlink = wp_get_shortlink( $args['post_id'] );

			// TODO: sprintify string
			$status = $title . ' ' . $shortlink;
		}

		// Prepare URL of request
		$url = 'https://api.twitter.com/1.1/statuses/update.json';

		/**
		 * Filter Twitter request's API endpoint.
		 *
		 * @param string          $url             API endpoint used in Twitter request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$url = (string) apply_filters( 'social_teaser_service_twitter_url', $url, $args, $token, $keyring_service );

		// Prepare actual body of request
		$body = array( 'status' => $status );

		/**
		 * Filter Twitter request's body.
		 *
		 * @param array           $body            Data passed used in Twitter request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$body = (array) apply_filters( 'social_teaser_service_twitter_body', $body, $args, $token, $keyring_service );

		// Prepare actual requests parameters
		$request_params = array(
			'method'  => 'POST',
			'timeout' => 100,
			'body'    => $body,
		);

		/**
		 * Filter Twitter request's parameters.
		 *
		 * @param array           $request_params  Request parameters used in Twitter request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$request_params = (array) apply_filters( 'social_teaser_service_twitter_request_params', $request_params, $args, $token, $keyring_service );

		// Check that URL is not empty
		if ( ! $url ) {
			return new Keyring_Error( 'social-teaser-empty-url', __( 'URL is empty.', 'social-teaser' ) );
		}

		$request = $keyring_service->request(
			$url,
			$request_params
		);

		return $request;
	}
}

add_action( 'keyring_load_services', array( 'Social_Teaser_Service_Twitter', 'init' ) );
