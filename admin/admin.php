<?php

namespace WPDCWishList;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Admin {
    use DiscourseWishlistUtilities;

	protected $options;
	protected $options_page;
	protected $form_helper;

	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'admin_init', array( $this, 'initialize_plugin' ) );
		add_action( 'admin_menu', array( $this, 'add_groups_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ), 5, 1 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'discourse_wishlist_settings_fields' ) );
	}

	public function initialize_plugin() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'dcwl_settings_section', __( 'Discourse Wishlist Group Associations' ), array(
			$this,
			'settings_page_details',
		), 'dcwl_options' );

		add_settings_field( 'dcwl_enabled', __( 'Enable Discourse WishList Groups', 'wpdc-wishlist' ), array(
			$this,
			'setting_enabled_checkbox',
		), 'dcwl_options', 'dcwl_settings_section' );

		add_settings_field( 'dcwl_group_associations', __( 'Discourse Wishlist Group Associations', 'wpdc-wishlist' ), array(
			$this,
			'discourse_wishlist_group_options',
		), 'dcwl_options', 'dcwl_settings_section' );

		register_setting( 'dcwl_options', 'dcwl_options', array( $this->form_helper, 'validate_options' ) );
	}

	public function add_groups_page() {
		$groups_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'WishList Groups', 'wpdc-wishlist' ),
			__( 'WishList Groups', 'wpdc-wishlist' ),
			'manage_options',
			'wpdc_wishlist_options',
			array( $this, 'dcwl_options_tab' ) );
		// Todo: maybe move the connection_status_notice to FormHelper, so that it can be accessed here.
	}

	public function discourse_wishlist_settings_fields( $tab ) {
		if ( 'wpdc_wishlist_options' === $tab ) {
			settings_fields( 'dcwl_options' );
			do_settings_sections( 'dcwl_options' );
		}
	}

	public function dcwl_options_tab() {
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
		$this->form_helper->checkbox_input( 'dcwl_enabled', 'dcwl_options', __( 'Enable Discourse WishList groups.', 'wpdc-wishlist' ) );
	}

	public function discourse_wishlist_group_options() {
		$levels           = $this->get_wishlist_levels();
		$discourse_groups = $this->get_discourse_group_names();
		?>
        <tr>
            <th>WishList Level</th>
            <th>Discourse Group</th>
        </tr>
		<?php if ( $levels && ! is_wp_error( $discourse_groups ) ) : ?>
			<?php foreach ( $levels as $level ) : ?>
                <tr>
                    <td><?php echo $level['name'] ?></td>
                    <td>
                        <?php
                        $dcwl_groups = ! empty( $this->options['dcwl_groups'] ) ? $this->options['dcwl_groups'] : null;
                        ?>
                        <select multiple name="dcwl_options[dcwl_groups][<?php echo esc_attr( $level['id'] ); ?>][]" class="widefat">
                            <option value="none">None</option>
							<?php foreach ( $discourse_groups as $discourse_group ) : ?>
                                <?php
                                write_log('level id', $level['id']);
                                write_log( 'discourse_group', $discourse_group);
                                if ( array_key_exists( $level['id'], $dcwl_groups  ) && in_array( $discourse_group, $dcwl_groups[ $level['id']],  true ) ) {
                                    $selected = 'selected';
                                } else {
                                    $selected = '';
                                }

                                ?>
                                <option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $discourse_group ); ?>"><?php echo esc_attr( $discourse_group ); ?></option>
							<?php endforeach; ?>
                        </select>


                    </td>
                </tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
	}

	// Todo: set a transient so this can be cached.
	protected function get_discourse_group_names() {
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

		return $group_names;
	}

	protected function get_wishlist_levels() {
		$levels = null;
		if ( function_exists( 'wlmapi_get_levels' ) ) {
			$levels_data = wlmapi_get_levels();
			if ( ! empty( $levels_data['levels'] ) && ! empty( $levels_data['levels']['level'] ) ) {
				$levels = $levels_data['levels']['level'];
			}
		}

		return $levels;
	}

	protected function group_select() {
		$levels = $this->get_wishlist_levels();
		if ( $levels ) {
			foreach ( $levels as $level ) {
				?>
                <input type="text" name="dcwp_groups[<?php echo $level['id']; ?>][]"
                       value="<?php echo $level['name']; ?>">

				<?php
			}
		}
	}

}
