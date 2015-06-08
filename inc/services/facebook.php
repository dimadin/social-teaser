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
		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$shortlink = wp_get_shortlink( $args['post_id'] );

			$status = $title;
		} else {
			$status = '';
		}

		$status = apply_filters( 'social_teaser_service_facebook_content', $status, $args );

		$request = $keyring_service->request(
			'https://graph.facebook.com/me/feed',
			array(
				'method'  => 'POST',
				'timeout' => 100,
				'body'    => array( 'message' => $status, 'link' => $shortlink )
			)
		);

		return $request;
	}
}

add_action( 'keyring_load_services', array( 'Social_Teaser_Service_Facebook', 'init' ) );