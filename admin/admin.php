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
		), 'dcwl_groups' );

		add_settings_field( 'dcwl_enabled', __( 'Enable Discourse WishList Groups', 'wpdc-wishlist' ), array(
			$this,
			'setting_enabled_checkbox',
		), 'dcwl_groupd', 'dcwl_settings_section' );

		add_settings_field( 'dcwl_group_associations', __( 'Discourse Wishlist Group Associations', 'wpdc-wishlist' ), array(
			$this,
			'discourse_wishlist_group_options',
		), 'dcwl_groups', 'dcwl_settings_section' );

		register_setting( 'dcwl_groups', 'dcwl_groups', array( $this->form_helper, 'validate_options' ) );
	}

	public function add_groups_page() {
		add_submenu_page(
			'wp_discourse_options',
			__( 'WishList Groups', 'wpdc-wishlist' ),
			__( 'WishList Groups', 'wpdc-wishlist' ),
			'manage_options',
			'wpdc_wishlist_options',
			array( $this, 'dcwl_options_tab' ) );
	}

	public function discourse_wishlist_settings_fields( $tab ) {
		if ( 'wpdc_wishlist_options' === $tab ) {
			settings_fields( 'dcwl_groups' );
			do_settings_sections( 'dcwl_groups' );
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


	// Todo: use this setting!
	public function setting_enabled_checkbox() {
		$this->form_helper->checkbox_input( 'dcwl_enabled', 'dcwl_options', __( 'Enable Discourse WishList groups.', 'wpdc-wishlist' ) );
	}

	public function discourse_wishlist_group_options() {
		$levels           = $this->get_wishlist_levels();
		$discourse_groups = $this->get_discourse_groups();
		?>
        <tr>
            <th>WishList Level</th>
            <th>Discourse Group</th>
            <th>Require Email Activation</th>
        </tr>
		<?php if ( $levels && ! is_wp_error( $discourse_groups ) ) : ?>
			<?php foreach ( $levels as $level ) : ?>
                <tr>
                    <td><?php echo $level['name'] ?></td>
                    <td>
						<?php
						$dcwl_groups = get_option( 'dcwl_groups' ) ? get_option( 'dcwl_groups' ) : array();
						?>
                        <select multiple name="dcwl_groups[<?php echo esc_attr( $level['id'] ); ?>][id][]" class="widefat">
							<?php foreach ( $discourse_groups as $discourse_group ) : ?>
                                <?php write_log('discourse group', $discourse_group); ?>
								<?php
								if ( array_key_exists( $level['id'], $dcwl_groups ) && in_array( $discourse_group['id'], $dcwl_groups[ $level['id'] ]['id'], false ) ) {
									$selected = 'selected';
								} else {
									$selected = '';
								}

								?>
                                <option <?php echo esc_attr( $selected ); ?>
                                        value="<?php echo esc_attr( $discourse_group['id'] ); ?>"><?php echo esc_attr( $discourse_group['name'] ); ?></option>
							<?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <?php
                        $checked = $dcwl_groups[ $level['id']]['act'];
                        ?>
                        <input type="checkbox" name="dcwl_groups[<?php echo esc_attr( $level['id'] ); ?>][act]" value="1" <?php checked( $checked ); ?>>

                    </td>
                </tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
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
