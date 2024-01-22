<?php
/*
Plugin Name: CamINKu Simple Manage
Plugin URI:
Description: CamINKu simple manager api
Version: 1.0
Author: CamINKu
Author URI: https://github.com/caminkunick
License: GPLv2 or later
Text Domain: cmk-spmn
*/

require plugin_dir_path(__FILE__) . 'puc/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	// 'https://example.com/path/to/details.json',
    // plugin dir url
    plugin_dir_url(__FILE__) . 'plugin.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'cmk-spmn'
);

require_once plugin_dir_path(__FILE__) . 'menu.php';

// allow svg
function cc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

add_action('admin_menu', array('cmk\spmn\manu', 'add_menu'));
add_action('wp_ajax_cmk_spmn', array('cmk\spmn\api', 'main'));
add_action('wp_ajax_nopriv_cmk_spmn', array('cmk\spmn\api', 'main'));