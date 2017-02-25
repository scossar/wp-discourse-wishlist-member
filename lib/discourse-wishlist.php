<?php

namespace WPDCWishList;

class DiscourseWishlist {
	protected $wpdcwl_enabled = 0;
	protected $wpdcwl_groups = array();

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
	}

	public function initialize_plugin() {
		add_option( 'wpdcwl_enabled', $this->wpdcwl_enabled );
		add_option( 'wpdcwl_groups', $this->wpdcwl_groups );
	}
}