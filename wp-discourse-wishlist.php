<?php
/**
 * Plugin Name: WP Discourse WishList
 * Description: Extends the WP Discourse plugin to allow WishList members to be added to Discourse groups.
 * Version: 0.16
 * Text Domain: wpdc-wishlist
 * GitHub Plugin URI: https://github.com/scossar/wp-discourse-wishlist-member
 *
 * @packageWPDCWishList
 */

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

namespace WPDCWishList;

use \WPDiscourse\Admin\OptionsPage as OptionsPage;

define( 'WPDC_WISHLIST_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPDC_WISHLIST_URL', plugins_url( '',  __FILE__ ) );
define( 'WPDC_WISHLIST_VERSION', '0.1' );

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {

		require_once( __DIR__ . '/lib/discourse-wishlist-utilities.php' );
		require_once( __DIR__ . '/lib/discourse-wishlist.php' );

		$wpdc_wishlist = new DiscourseWishlist();
		$wpdc_wishlist->init();

		if ( is_admin() ) {
			require_once( __DIR__ . '/admin/admin.php' );

			$options_page = OptionsPage::get_instance();

			$wpdc_wishlist_admin = new Admin( $options_page );
			$wpdc_wishlist_admin->init();
		}
	}
}




