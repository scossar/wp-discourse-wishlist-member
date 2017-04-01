<?php

namespace WPDCWishList;

class DiscourseWishlist {
	use DiscourseWishlistUtilities;

	protected $options;
	protected $option_name = 'dcwl_options';

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
	}

	public function initialize_plugin() {
		add_option( $this->option_name, array());
		$levels = $this->get_wishlist_levels();
		write_log( 'levels', $levels );
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
}