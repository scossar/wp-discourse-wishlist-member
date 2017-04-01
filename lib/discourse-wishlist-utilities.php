<?php

namespace WPDCWishList;

trait DiscourseWishlistUtilities {

	public function get_wishlist_levels() {

		return $this->get_levels_data();
	}

	protected function get_levels_data() {
		if ( function_exists( 'wlmapi_get_levels' ) ) {
			$response = wlmapi_get_levels();
			if ( ! empty( $response['levels']) && ! empty( $response['levels']['level'])) {

				return $response['levels']['level'];
			} else {

				return null;
			}
		}

		return null;
	}
}