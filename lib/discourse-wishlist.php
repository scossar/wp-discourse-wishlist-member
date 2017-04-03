<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseWishlist {

	protected $options;

	protected $dcwl_groups = array(
		'dcwl_group_associations' => array(),
	);

	public function __construct() {
	}

	public function init() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_action( 'wishlistmember_add_user_levels', array( $this, 'add_discourse_groups' ), 10, 2 );
	}

	public function initialize_plugin() {
		add_option( 'dcwl_groups', $this->dcwl_groups );
		$this->options = DiscourseUtilities::get_options();
	}

	public function add_discourse_groups( $user_id, $levels ) {
		$dcwl_groups      = get_option( 'dcwl_groups' );
		$user             = get_user_by( 'id', $user_id );
		$discourse_groups = null;

		foreach ( $levels as $level_id ) {
			if ( array_key_exists( $level_id, $dcwl_groups ) ) {
				$discourse_groups = $dcwl_groups[ $level_id ];
			}
		}

		if ( $discourse_groups ) {
			$discourse_user_id = $this->lookup_or_create_discourse_user( $user_id, $user );

			if ( $discourse_user_id ) {
				write_log( 'user id', $discourse_user_id );

				foreach ( $discourse_groups as $discourse_group ) {

					$this->add_user_to_group( $user->user_login, $discourse_group );
				}
			}

		}


//		write_log( 'discourse_group_names', $discourse_group_names );
//		write_log( 'discourse wishlist groups', $dcwl_groups );
//		write_log( 'user', $user );
	}

	protected function lookup_or_create_discourse_user( $user_id, $user ) {
		$connection_options = get_option( 'discourse_connect' );
		$base_url           = $connection_options['url'];
		if ( $base_url ) {
			// Try to get the user by external_id.
			$external_user_url = esc_url_raw( $base_url . "/users/by-external/$user_id.json" );
			$response          = wp_remote_get( $external_user_url );

			if ( DiscourseUtilities::validate( $response ) ) {
				$user_data = json_decode( wp_remote_retrieve_body( $response ), true );

				return $user_data['user']['id'];
			}

			// Try to get the user by email from active.json.
			$users_url = esc_url_raw( $base_url . '/admin/users/list/active.json' );

			// Todo: are these parameters correct?
			$users_url = add_query_arg( array(
				'filter'       => rawurlencode( $user->user_email ),
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

			write_log( 'still here' );

			// Try to get the user from new.json?

			// User not found. Try to create the Discourse user.
			$create_user_url = $base_url . '/users';
			$api_key         = $connection_options['api-key'];
			$api_username    = $connection_options['publish-username'];

			if ( empty( $api_key ) && empty( $api_username ) ) {

				return new \WP_Error( 'discourse_configuration_options_not_set', 'The Discourse configuration options have not been set.' );
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
				return new \WP_Error( 'discourse_unable_to_create_user', 'An error was returned when trying to create the Discourse user for a WishList membership' );
			}

			$user_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $user_data[0] ) && isset( $user_data[0]['user_id'] ) ) {

				$discourse_user_id = $user_data[0]['user_id'];

				// Todo: make this optional.
				update_user_meta( $user_id, 'discourse_email_not_verified', 1 );

				return $discourse_user_id;
			}
		}

		return new \WP_Error( 'discourse_unable_to_create_user', 'The wp-discourse plugin is not configured.' );
	}

	protected function add_user_to_group( $username, $discourse_group_id ) {
		$connection_options = get_option( 'discourse_connect' );
		$base_url           = $connection_options['url'];
		$api_key            = $connection_options['api-key'];
		$api_username       = $connection_options['publish-username'];
		if ( $base_url && $api_key && $api_username ) {
			$add_to_group_url = $base_url . "/admin/groups/$discourse_group_id/members.json";
			$response         = wp_remote_post( $add_to_group_url, array(
				'method' => 'PUT',
				'body'   => array(
					'usernames'    => $username,
					'api_key'      => $api_key,
					'api_username' => $api_username,
				),
			) );
		}
	}
}