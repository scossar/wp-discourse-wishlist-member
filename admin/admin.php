<?php

namespace WPDCWishList;

class Admin {
	protected $options_page;
	protected $form_helper;

	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper = $form_helper;

		write_log( 'in the Admin constructor' );
	}
}