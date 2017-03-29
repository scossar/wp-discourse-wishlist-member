<?php

namespace WPDCWishList;

class DiscourseWishlist {
	protected $options;
	protected $option_name = 'dcwl_options';

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
	}

	public function initialize_plugin() {
		add_option( $this->option_name, array());
	}

	public function add_options( $wpdc_options ) {
		static $merged_options = [];

		if ( empty( $merged_options ) ) {
			$plugin_options = get_option( $this->option_name );
			if ( is_array( $plugin_options ) ) {
				$merged_options = array_merge( $wpdc_options, $plugin_options );
			} else {
				$merged_options = $wpdc_options;
			}
		}
		$this->options = $merged_options;

		return $merged_options;
	}

//	protected function get_wishlist_levels() {
//		$levels = null;
//		if ( function_exists( 'wlmapi_get_levels' ) ) {
//			$levels_data = wlmapi_get_levels();
//			if ( ! empty( $levels_data['levels'] ) && ! empty( $levels_data['levels']['level'] ) ) {
//				$levels = $levels_data['levels']['level'];
//			}
//		}
//
//		write_log( 'levels', $levels );
//		return $levels;
//	}
}