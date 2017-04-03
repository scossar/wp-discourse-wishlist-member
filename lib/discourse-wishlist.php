<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseWishlist {
	use DiscourseWishlistUtilities;

	protected $options;

	protected $dcwl_groups = array(
		'dcwl_group_associations' => array(),
	);

	public function __construct() {
	}

	public function init() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_action( 'wishlistmember_add_user_levels', array(
			$this,
			'add_unconfirmed_user_to_discourse_groups'
		), 10, 2 );
		add_action( 'wishlistmember_confirm_user_levels', array( $this, 'confirm_wishlist_level' ), 10, 2 );
	}

	public function confirm_wishlist_level( $user_id, $levels ) {
		foreach ( $levels as $level_id ) {
			$this->add_discourse_group( $user_id, $level_id );
		}
	}

	public function initialize_plugin() {
		add_option( 'dcwl_groups', $this->dcwl_groups );
		$this->options = DiscourseUtilities::get_options();
	}

	public function add_unconfirmed_user_to_discourse_groups( $user_id, $levels ) {

		// If an admin is registering the user, don't require confirmation.
		$admin_registration = current_user_can( 'administrator' ) && is_admin();

		foreach ( $levels as $level_id ) {
			$level_data = wlmapi_get_level( $level_id );
			$require_email_confirmation = isset( $level_data['level'] ) &&
			                              isset( $level_data['level']['require_email_confirmation'] ) &&
			                              1 === intval( $level_data['level']['require_email_confirmation'] ) &&
			                              ! $admin_registration;

			if ( ! $require_email_confirmation ) {
				$this->add_discourse_group( $user_id, $level_id );
			}
		}
	}

	public function add_discourse_group( $user_id, $level_id ) {
		$dcwl_groups             = get_option( 'dcwl_groups' );
		$dcwl_group_associations = $dcwl_groups['dcwl_group_associations'];
		if ( array_key_exists( $level_id, $dcwl_group_associations ) ) {
			$discourse_groups = $dcwl_group_associations[ $level_id ]['dc_group_ids'];

			if ( $discourse_groups ) {
				$discourse_user_id = $this->lookup_or_create_discourse_user( $user_id );

				if ( $discourse_user_id && ! is_wp_error( $discourse_user_id ) ) {

					foreach ( $discourse_groups as $discourse_group ) {
						$this->add_user_to_group( $discourse_user_id, $discourse_group );
					}
				}
			}
		}
	}

	protected function add_user_to_group( $user_id, $discourse_group_id ) {
		$connection_options = get_option( 'discourse_connect' );
		$base_url           = $connection_options['url'];
		$api_key            = $connection_options['api-key'];
		$api_username       = $connection_options['publish-username'];
		if ( $base_url && $api_key && $api_username ) {
			$add_to_group_url = $base_url . "/admin/groups/$discourse_group_id/members.json";
			$response         = wp_remote_post( $add_to_group_url, array(
				'method' => 'PUT',
				'body'   => array(
					'user_ids'     => $user_id,
					'api_key'      => $api_key,
					'api_username' => $api_username,
				),
			) );
		}
	}
}