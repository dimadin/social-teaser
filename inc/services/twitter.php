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
	 * @param Keyring_Token         $token    Token of service that should be publishe to.
	 * @param Social_Teaser_Service $instance Object of Social_Teaser_Service child class.
	 * @param array                 $args     An array with data related to publishing.
	 * @return string $request Response from service.
	 */
	public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$status = '';

		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$shortlink = wp_get_shortlink( $args['post_id'] );

			// TODO: sprintify string
			$status = $title . ' ' . $shortlink;
		}

		// Prepare actual body of request
		$body = array( 'status' => $status );

		/**
		 * Filter Twitter request's body.
		 *
		 * @param array $body Data passed used in Twitter request.
		 * @param array $args An array with data related to publishing.
		 */
		$body = (array) apply_filters( 'social_teaser_service_twitter_body', $body, $args );

		$request = $keyring_service->request(
			'https://api.twitter.com/1.1/statuses/update.json',
			array(
				'method'  => 'POST',
				'timeout' => 100,
				'body'    => $body,
			)
		);

		return $request;
	}
}

add_action( 'keyring_load_services', array( 'Social_Teaser_Service_Twitter', 'init' ) );
