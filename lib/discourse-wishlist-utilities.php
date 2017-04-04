<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

trait DiscourseWishlistUtilities {

	public function get_wishlist_levels() {

		return $this->get_levels_data();
	}

	public function get_discourse_groups() {
		$dcwl_options = get_option( 'dcwl_groups' );

		if ( ! $dcwl_options ) {

			return new \WP_Error( 'discourse_configuration_error', "Unable to retrieve Discourse groups. The WP Discourse plugin isn't properly configured." );
		}

		$force_update = isset( $dcwl_options['dcwl_update_discourse_groups'] ) ? $dcwl_options['dcwl_update_discourse_groups'] : 0;

		$parsed_data = get_transient( 'wpdc_groups_data' );

		if ( empty( $parsed_data ) || $force_update ) {
			$raw_groups_data = $this->get_discourse_groups_data();
			$parsed_data     = [];

			foreach ( $raw_groups_data as $group ) {
				$parsed_data[] = array(
					'id'   => $group['id'],
					'name' => $group['name'],
				);
			}

			set_transient( 'wpdc_groups_data', $parsed_data, 10 * MINUTE_IN_SECONDS );

			// Reset the 'dcwl_update_discourse_groups' option so that the transient is used.
			$dcwl_options['dcwl_update_discourse_groups'] = 0;
			update_option( 'dcwl_groups', $dcwl_options );
		}

		return $parsed_data;
	}

	public function lookup_or_create_discourse_user( $user_id, $force_email_verification ) {
		$user = get_user_by( 'id', $user_id );

		$discourse_user_id = $this->lookup_discourse_user( $user );

		if ( ! $discourse_user_id || is_wp_error( $discourse_user_id ) ) {
			$discourse_user_id = $this->create_discourse_user( $user, $force_email_verification );
		}

		return $discourse_user_id;
	}

	protected function get_connection_option( $option ) {
		static $connection_options = null;

		if ( ! $connection_options ) {
			$connection_options = get_option( 'discourse_connect' );
		}

		if ( isset( $connection_options[ $option ] ) ) {

			return $connection_options[ $option ];
		}

		return false;
	}

	protected function get_discourse_groups_data() {
		$base_url = $this->get_connection_option( 'url' );

		if ( ! $base_url ) {

			return new \WP_Error( 'discourse_configuration_error', 'The Discourse URL has not been set.' );
		}

		$groups_url = $base_url . '/groups.json';
		$response   = wp_remote_get( esc_url( $groups_url ) );

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return new \WP_Error( 'discourse_invalid_response', 'Could not get a response from discourse/groups.json' );
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		$groups = ! empty( $response['groups'] ) ? $response['groups'] : null;

		if ( ! $groups ) {

			return new \WP_Error( 'discourse_unable_to_retrieve_discourse_groups', 'The groups key was not returned.' );
		}

		return $groups;
	}

	protected function get_levels_data() {

		if ( function_exists( 'wlmapi_get_levels' ) ) {
			$response = wlmapi_get_levels();
			if ( ! empty( $response['levels'] ) && ! empty( $response['levels']['level'] ) ) {

				return $response['levels']['level'];
			} else {

				return null;
			}
		}

		return null;
	}


	protected function lookup_discourse_user( $wp_user ) {
		$connection_options = get_option( 'discourse_connect' );
		$base_url           = $connection_options['url'];

		if ( $base_url ) {
			// Try to get the user by external_id.
			$external_user_url = esc_url_raw( $base_url . "/users/by-external/$wp_user->ID.json" );
			$response          = wp_remote_get( $external_user_url );

			if ( DiscourseUtilities::validate( $response ) ) {
				$user_data = json_decode( wp_remote_retrieve_body( $response ), true );

				return $user_data['user']['id'];
			}

			// Try to get the user by email from active.json.
			$users_url = esc_url_raw( $base_url . '/admin/users/list/active.json' );

			$users_url = add_query_arg( array(
				'filter'       => rawurlencode( $wp_user->user_email ),
				'api_key'      => $connection_options['api-key'],
				'api_username' => $connection_options['publish-username'],
			), $users_url );

			$response = wp_remote_get( $users_url );
			if ( DiscourseUtilities::validate( $response ) ) {
				$user_data = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $user_data[0] ) && isset( $user_data[0]['id'] ) ) {

					return $user_data[0]['id'];
				}
			}

			// The user doesn't exist yet.
			return null;
		}

		return new \WP_Error( 'discourse_configuration_error', 'Unable to retrieve user. The WP Discourse plugin is not properly configured.' );
	}

	protected function create_discourse_user( $user, $force_email_verification ) {

		$base_url        = $this->get_connection_option( 'url' );
		$create_user_url = $base_url . '/users';
		$api_key         = $this->get_connection_option( 'api-key' );
		$api_username    = $this->get_connection_option( 'publish-username' );

		if ( empty( $api_key ) && empty( $api_username ) ) {

			return new \WP_Error( 'discourse_configuration_error', 'Unable to create Discourse user. The Discourse configuration options have not been set.' );
		}
		$username = $user->user_login;
		$name     = $user->display_name;
		$email    = $user->user_email;
		$password = wp_generate_password( 20 );
		$response = wp_remote_post( $create_user_url, array(
			'method' => 'POST',
			'body'   => array(
				'api_key'      => $api_key,
				'api_username' => $api_username,
				'name'         => $name,
				'email'        => $email,
				'password'     => $password,
				'username'     => $username,
				'active'       => 'active',
			),
		) );

		if ( ! DiscourseUtilities::validate( $response ) ) {
			return new \WP_Error( 'discourse_unable_to_create_user', 'An error was returned when trying to create the Discourse user for a WishList membership.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $user_data['user_id'] ) ) {

			$discourse_user_id = $user_data['user_id'];

			// Force email verification for the initial SSO login.
			if ( $force_email_verification ) {

				update_user_meta( $user->ID, 'discourse_email_not_verified', 1 );
			} else {

				delete_user_meta( $user->ID, 'discourse_email_not_verified' );
			}

			return $discourse_user_id;
		}

		return new \WP_Error( 'discourse_user_not_created', 'The Disocourse user could not be created.' );
	}
}
