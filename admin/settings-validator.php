<?php

namespace WPDCWishList;

class SettingsValidator {
	public function __construct() {
		add_filter( 'wpdc_validate_dcwl_enabled', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_dcwl_groups', array( $this, 'validate_groups'));
	}

	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	// Todo: do something here.
	public function validate_groups( $input ) {
		return $input;
	}

}