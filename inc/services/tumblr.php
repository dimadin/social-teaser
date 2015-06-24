<?php

/**
 * The Social Teaser Plugin
 *
 * Publish to your social accounts, what you want.
 *
 * @package Social_Teaser
 * @subpackage Social_Teaser_Service_Tumblr
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tumblr service definition for Social_Teaser.
 */
class Social_Teaser_Service_Tumblr extends Social_Teaser_Service {
	/**
	 * Name of the service.
	 */
	const NAME  = 'tumblr';

	/**
	 * Publish to Tumblr using data provided.
	 *
	 * @access public
	 *
	 * @param Keyring_Token   $token           Token of service that should be published to.
	 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
	 * @param array           $args            An array with data related to publishing.
	 * @return string|Keyring_Error $request Response from service, error on failure.
	 */
	public static function publish( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$title     = '';
		$permalink = '';

		if ( isset( $args['post_id'] ) && $args['post_id'] ) {
			$title     = get_the_title( $args['post_id'] );
			$permalink = get_permalink( $args['post_id'] );
		}

		// Get all blogs
		$blogs = static::get_blogs( $token, $keyring_service, $args );

		// Prepare URL of request
		$url = $blogs ? 'https://api.tumblr.com/v2/blog/' . $blogs[0] . '/post' : '';

		// Include base URL in $args
		$args['tumblr_base_url'] = $url;

		/**
		 * Filter Tumblr request's API endpoint.
		 *
		 * @param string          $url             API endpoint used in Tumblr request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$url = (string) apply_filters( 'social_teaser_service_tumblr_url', $url, $args, $token, $keyring_service );

		// Prepare actual body of request
		$body = array( 'type' => 'link', 'title' => $title, 'url' => $permalink );

		/**
		 * Filter Tumblr request's body.
		 *
		 * @param array           $body            Data passed used in Tumblr request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$body = (array) apply_filters( 'social_teaser_service_tumblr_body', $body, $args, $token, $keyring_service );

		// Prepare actual requests parameters
		$request_params = array(
			'method'  => 'POST',
			'timeout' => 100,
			'body'    => $body,
		);

		/**
		 * Filter Tumblr request's parameters.
		 *
		 * @param array           $request_params  Request parameters used in Tumblr request.
		 * @param array           $args            An array with data related to publishing.
		 * @param Keyring_Token   $token           Token of service that should be published to.
		 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
		 */
		$request_params = (array) apply_filters( 'social_teaser_service_tumblr_request_params', $request_params, $args, $token, $keyring_service );

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

	/**
	 * Get blogs for Tumblr user.
	 *
	 * @access public
	 *
	 * @param Keyring_Token   $token           Token of service that should be published to.
	 * @param Keyring_Service $keyring_service Object of Keyring_Service child class.
	 * @param array           $args            An array with data related to publishing.
	 * @return string|Keyring_Error $request Response from service, error on failure.
	 */
	public static function get_blogs( Keyring_Token $token, Keyring_Service $keyring_service, array $args ) {
		$base_urls = array();

		$request = $keyring_service->request(
			'http://api.tumblr.com/v2/user/info',
			array(
				'method'  => 'GET',
				'timeout' => 100,
			)
		);

		// If there was error return empty array
		if ( Keyring_Util::is_error( $request ) ) {
			return $base_urls;
		}

		$blogs = $request->response->user->blogs;

		foreach ( $blogs as $blog ) {
			$full_url = $blog->url;
			$base_urls[] = parse_url( $full_url, PHP_URL_HOST );
		}

		return (array) apply_filters( 'social_teaser_service_tumblr_get_blogs', $base_urls );
	}
}

add_action( 'keyring_load_services', array( 'Social_Teaser_Service_Tumblr', 'init' ) );
