<?php
/**
 * Fired when the plugin is deleted.
 *
 * The plugin stores nothing: no options, transients, user meta, post meta,
 * group meta, tables or cron events. Suggestions are generated per request and
 * its settings come only from the bp_gns_settings filter. There is nothing to
 * remove, so this file only guards against direct access.
 *
 * @package BP_Group_Name_Suggester
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
