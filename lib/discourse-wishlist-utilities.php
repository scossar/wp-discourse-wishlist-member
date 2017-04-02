<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

trait DiscourseWishlistUtilities {

	public function get_wishlist_levels() {

		return $this->get_levels_data();
	}

	public function get_discourse_groups() {
		$groups      = $this->get_discourse_group_data();
		$group_names = [];

		foreach ( $groups as $group ) {
			$group_names[] = array(
				'id' => $group['id'],
				'name' => $group['name'],
			);
		}

		return $group_names;
	}

	protected function get_discourse_group_data() {
		$base_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;

		if ( ! $base_url ) {

			return new \WP_Error( 'Unable to retrieve groups.', 'The Discourse URL has not been set.' );
		}

		$groups_url = $base_url . '/groups.json';
		$response   = wp_remote_get( $groups_url );

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return new \WP_Error( 'Could not get a response from discourse/groups.json' );
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		$groups = ! empty( $response['groups'] ) ? $response['groups'] : null;

		if ( ! $groups ) {

			return new \WP_Error( 'The groups key was not returned' );
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
}