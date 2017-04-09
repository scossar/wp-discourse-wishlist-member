<?php

namespace WPDCWishList;

class DiscourseUser {
	use DiscourseWishlistUtilities;

	public function __construct() {
	}

	public function init() {
		add_action( 'rest_api_init', array( $this, 'initialize_discourse_user_route' ) );
	}

	/**
	 * The webhook URL to be set on Discourse is `http://example.com/wp-json/wp-discourse/v1/discourse-user`
	 *
	 */
	public function initialize_discourse_user_route() {
		register_rest_route( 'wp-discourse/v1', 'discourse-user', array(
			array(
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'update_discourse_user_info' ),
			),
		) );
	}

	public function update_discourse_user_info( $data ) {
		$data = $this->verify_wordpress_api_request( $data );

		if ( is_wp_error( $data ) ) {

			return $data;
		}

		$user_data = ! empty( $data['user'] ) ? $data['user'] : null;

		if ( ! $user_data ) {

			return new \WP_Error( 'discourse_webhook_invalid_request', "A Discourse user wasn't returned with the request payload." );
		}

		$discourse_id = $user_data['id'];
		$discourse_username = $user_data['username'];
		$discourse_email = $user_data['email'];

		// Update WordPress user_meta for the Discourse user.
		if ( ! empty( $user_data['external_id'] ) ) {

			$user_id = $user_data['external_id'];
		} else {

			$wp_user_by_login = get_user_by( 'login', $discourse_username );
			$wp_user_by_email = get_user_by( 'email', $discourse_email );

			if ( $wp_user_by_login && $wp_user_by_email ) {

				if ( $wp_user_by_login->user_login === $wp_user_by_email->user_login ) {
					$user_id = $wp_user_by_login->ID;

				} else {
					$user_id = null;
				}

			} else {
				$user_id = null;
			}
		}

		if ( $user_id ) {
//			update_user_meta( $user_id, 'discourse_username', $discourse_username );
			// This should never be changed, use `add_user_meta` instead of `update_user_meta`.
//			add_user_meta( $user_id, 'discourse_sso_external_id', $discourse_id );
		}
	}
}