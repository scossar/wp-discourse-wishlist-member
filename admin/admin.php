<?php

namespace WPDCWishList;

class Admin {
	use DiscourseWishlistUtilities;

	protected $options_page;

	protected $dcwl_options;

	public function __construct( $options_page ) {
		$this->options_page = $options_page;
	}

	public function init() {
		add_action( 'admin_init', array( $this, 'initialize_plugin' ) );
		add_action( 'admin_menu', array( $this, 'add_groups_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ), 5, 1 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'discourse_wishlist_settings_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function enqueue_admin_scripts() {
		wp_register_style( 'dcwl_admin_styles', WPDC_WISHLIST_URL . '/admin/css/admin-styles.css' );
		wp_enqueue_style( 'dcwl_admin_styles' );
	}

	public function initialize_plugin() {
		$this->dcwl_options = get_option( 'dcwl_groups' ) ? get_option( 'dcwl_groups' ) : array();

		add_settings_section( 'dcwl_settings_section', __( 'Discourse WishList Groups', 'wpdc-wishlist' ), array(
			$this,
			'settings_page_details',
		), 'dcwl_groups' );

		add_settings_field( 'dcwl_groups', __( 'Levels and Groups', 'wpdc-wishlist' ), array(
			$this,
			'discourse_wishlist_group_options',
		), 'dcwl_groups', 'dcwl_settings_section' );

		add_settings_field( 'dcwl_update_groups', __( 'Update Discourse Groups', 'wpdc-wishlist' ), array(
			$this,
			'update_discourse_groups_checkbox',
		), 'dcwl_groups', 'dcwl_settings_section' );

		register_setting( 'dcwl_groups', 'dcwl_groups', array( $this, 'validate_options' ) );
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
		<p>
			<em>
				<?php esc_html_e( 'Discourse groups can be associated with WishList levels. When groups are associated
                with a level, users will be automatically added to the Discourse groups when then sign-up, or are added to
                the WishList level.', 'wpdc-wishlist' ); ?>
			</em>
		</p>
        <p>
            <em>
				<?php esc_html_e( "If the 'Auto Remove Users' option is selected for a level, users will be automatically
				removed from the associated Discourse group when they are removed from a WishList level.", 'wpdc-wishlist' ); ?>
            </em>
        </p>
		<p>
			<em>
				<strong><?php esc_html_e( 'Note: ', 'wpdc-wishlist' ); ?></strong>
				<?php esc_html_e( "when using the WP Discourse plugin and the WishList plugin together, there is a
                confilict with the WP Discourse 'auto create user' setting. Please disable that setting.", 'wpdc-wishlist' ); ?>
			</em>
		</p>
		<p>
			<em>
				<strong><?php esc_html_e( 'This plugin is in the development stage.', 'wpdc-wishlist' ); ?></strong>
					<?php esc_html_e( "Don't install it on a huge site quite yet. There are a few issues with email verification that need to be sorted out.
                    The safest way to use it at the moment is to either require email confirmation for a WishList level, or to enable the setting
                    'Require Email Verification' on this page.", 'wp-discourse' ); ?>
			</em>
		</p>
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

	public function update_discourse_groups_checkbox() {
		?>
		<input type="checkbox" name="dcwl_groups[dcwl_update_discourse_groups]" value="1">
		<p><?php esc_html_e( 'Update Discourse groups list (normally set to refresh every hour.)', 'wpdc-wishlist' ); ?></p>
		<?php
	}

	public function discourse_wishlist_group_options() {
		$levels           = $this->get_wishlist_levels();
		$discourse_groups = $this->get_discourse_groups();
		$dcwl_groups      = $this->dcwl_options;
		?>

		<tr>
			<th>WishList Level</th>
			<th>Discourse Groups</th>
			<th>Require Email Verification</th>
			<th>Auto Remove Users</th>
		</tr>

		<?php if ( $levels && ! is_wp_error( $discourse_groups ) ) : ?>
			<?php foreach ( $levels as $level ) : ?>
				<?php
				$level_name = $level['name'];
				$level_id   = $level['id'];
				$level_key  = "dcwl_groups[dcwl_group_associations][$level_id]";
				?>
				<tr class="dcwl-options-row">
					<td><?php echo $level_name; ?></td>
					<td>
						<select multiple
								name="<?php echo $level_key; ?>[dc_group_ids][]"
								class="widefat">
							<?php foreach ( $discourse_groups as $discourse_group ) : ?>
								<?php
								if ( array_key_exists( $level_id, $dcwl_groups['dcwl_group_associations'] ) &&
								     array_key_exists( 'dc_group_ids', $dcwl_groups['dcwl_group_associations'][ $level_id ] ) &&
								     in_array( $discourse_group['id'], $dcwl_groups['dcwl_group_associations'][ $level_id ]['dc_group_ids'], false )
								) {
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
						$checked = $dcwl_groups['dcwl_group_associations'][ $level_id ]['require_activation'];
						?>
						<input type="hidden" value="0" name="<?php echo $level_key; ?>[require_activation]">
						<input type="checkbox" name="<?php echo $level_key; ?>[require_activation]"
							   value="1" <?php checked( $checked ); ?>>

					</td>
					<td>
						<?php
						$checked = $dcwl_groups['dcwl_group_associations'][ $level_id ]['auto_remove'];
						?>
						<input type="hidden" value="0" name="<?php echo $level_key; ?>[auto_remove]">
						<input type="checkbox" name="<?php echo $level_key; ?>[auto_remove]"
							   value="1" <?php checked( $checked ); ?>>

					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php
	}

	public function validate_options( $input_array ) {
		$output = [];

		$group_associations = $input_array['dcwl_group_associations'];

		foreach ( $group_associations as $wl_group_id => $sub_array ) {
			$output_key = sanitize_key( $wl_group_id );
			if ( array_key_exists( 'dc_group_ids', $sub_array ) ) {
				$output['dcwl_group_associations'][ $output_key ]['dc_group_ids'] = $sub_array['dc_group_ids'];
			}

			if ( array_key_exists( 'require_activation', $sub_array ) ) {
				$output['dcwl_group_associations'][ $output_key ]['require_activation'] = intval( $sub_array['require_activation'] );
			}

			if ( array_key_exists( 'auto_remove', $sub_array ) ) {
				$output['dcwl_group_associations'][ $output_key ]['auto_remove'] = intval( $sub_array['auto_remove'] );
			}
		}

		$update_groups = isset( $input_array['dcwl_update_discourse_groups'] ) ? $input_array['dcwl_update_discourse_groups'] : 0;

		$output['dcwl_update_discourse_groups'] = intval( $update_groups );

		return $output;
	}
}
