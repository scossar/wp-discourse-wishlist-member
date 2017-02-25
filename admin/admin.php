<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Admin {
	protected $options;
	protected $options_page;
	protected $form_helper;

	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'admin_init', array( $this, 'initialize_plugin' ) );
		add_action( 'admin_menu', array( $this, 'add_groups_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ) );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'discourse_wishlist_settings_fields' ) );
	}

	public function initialize_plugin() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'wpdcwl_settings_section', __( 'Discourse Wishlist Group Associations' ), array(
			$this,
			'settings_page_details',
		), 'wpdcwl_options' );

		add_settings_field( 'wpdcwl_enabled', __( 'Enable Discourse WishList Groups', 'wpdc-wishlist' ), array(
			$this,
			'setting_enabled_checkbox',
		), 'wpdcwl_options', 'wpdcwl_settings_section' );

		add_settings_field( 'wpdcwl_group_associations', __( 'Discourse Wishlist Group Associations', 'wpdc-wishlist' ), array(
			$this,
			'discourse_wishlist_group_options',
		), 'wpdcwl_options', 'wpdcwl_settings_section' );

		register_setting( 'wpdcwl_options', 'wpdcwl_options', array( $this->form_helper, 'validate_options' ) );
	}

	public function add_groups_page() {
		$groups_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'WishList Groups', 'wpdc-wishlist' ),
			__( 'WishList Groups', 'wpdc-wishlist' ),
			'manage_options',
			'wpdc_wishlist_options',
			array( $this, 'wpdcwl_options_tab' ) );
		// Todo: maybe move the connection_status_notice to FormHelper, so that it can be accessed here.
	}

	public function discourse_wishlist_settings_fields( $tab ) {
		if ( 'wpdc_wishlist_options' === $tab ) {
			settings_fields( 'wpdcwl_options' );
			do_settings_sections( 'wpdcwl_options' );
		}
	}

	public function wpdcwl_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'wpdc_wishlist_options' );
		}
	}

	public function settings_page_details() {
		?>
        <p>Discourse Wishlist groups.</p>
		<?php
	}

	public function settings_tab( $tab ) {
		$active = 'wpdc_wishlist_options' === $tab;
		?>
        <a href="?page=wp_discourse_options&tab=wpdc_wishlist_options"
           class="nav-tab <?php echo $active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'WishList Groups', 'wpdc-wishlist' ); ?>
        </a>
		<?php
	}


	public function setting_enabled_checkbox() {
		?>
        <h3>setting enabled</h3>
		<?php
	}

	public function discourse_wishlist_group_options() {
		?>
        <h3>group options</h3>
		<?php
	}

	// Todo: set a transient so this can be cached.
	protected function get_discourse_groups() {
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

		$group_names = [];

		foreach ( $groups as $group ) {
			$group_names[] = $group['name'];
		}
		write_log( 'group names', $group_names );

		return $group_names;
	}

	protected function get_wishlist_levels() {

    }
}