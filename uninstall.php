<?php
/**
 * Clean up plugin options on uninstall.
 *
 * Posts and their meta are left in place on purpose, so removing the plugin
 * never deletes content a site owner may still want.
 *
 * @package WP_GitHub_Sync
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'repobridgeforge_settings' );
delete_option( 'repobridgeforge_token' );
delete_option( 'repobridgeforge_log' );
delete_option( 'repobridgeforge_last_sync' );

$repobridgeforge_timestamp = wp_next_scheduled( 'repobridgeforge_scheduled_sync' );
if ( $repobridgeforge_timestamp ) {
	wp_unschedule_event( $repobridgeforge_timestamp, 'repobridgeforge_scheduled_sync' );
}
