<?php
/**
 * Uninstall the plugin.
 *
 * @package WPDiscourse
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'dcwl_groups' );
delete_site_option( 'dcwl_groups' );

delete_transient( 'wpdc_groups_data' );
