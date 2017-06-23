<?php
/*
 * Plugin Name: jfeed
 * Version: 0.2
 * Plugin URI: http://www.earthling.za.org/
 * Description: xml feed impoter.
 * Author: Jamie MacDonald
 * Author URI: http://www.earthling.za.org/
 * Requires at least: 4.8
 * Tested up to: 4.8
 *
 * Text Domain: jfeed
 * Domain Path: /lang/
 *
 * @package jFeed
 * @author Jamie MacDonald
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once('jfeed.class.php');

function jfeed_page() {
	if ( !current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	$jfeed = new jfeed('http://ttcms.365.co.za/storylisting/365za/4003');
	$jfeed->proccessIt(false);
	//var_dump(_get_cron_array());
}


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );
function add_action_links ( $links ) {
	return array_merge( $links, array('<a href="' . admin_url( 'admin.php?page=jfeed' ) . '">View</a>', ) );
}

add_action( 'admin_menu', 'jfeed_menu' );
function jfeed_menu() {
	add_menu_page( 'jFeed', 'jFeed Plugin', 'manage_options','jfeed', 'jfeed_page' ,''  ); 
}

if ( ! wp_next_scheduled( 'jfeed_hook' ) ) wp_schedule_event( time(), 'daily', 'jfeed_hook' );
add_action( 'jfeed_hook', 'cron_task' );
function cron_task() {
	$jfeed = new jfeed('http://ttcms.365.co.za/storylisting/365za/4003');
	$jfeed->proccessIt(true);
}