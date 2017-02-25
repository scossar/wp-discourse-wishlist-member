<?php

namespace WPDCWishList;

class DiscourseWishlist {
	protected $options;
	protected $wpdcwl_options = array(
		'wpdcwl_enabled' => 0,
		'wpdcwl_groups' => array(),
	);

	protected $option_name = 'wpdcwl_options';

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
	}

	public function initialize_plugin() {
		add_option( 'wpdcwl_options', $this->wpdcwl_options );
	}

	public function add_options( $wpdc_options ) {
		static $merged_options = [];

		if ( empty( $merged_options ) ) {
			$plugin_options = get_option( $this->option_name );
			// Todo: is $plugin_options an array when the plugin is first initialized? Maybe this should be set
			// when the plugin is activated?
			$merged_options = array_merge( $wpdc_options, $plugin_options );
		}
		$this->options = $merged_options;

		return $merged_options;
	}
}