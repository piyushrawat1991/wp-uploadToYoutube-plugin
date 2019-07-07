<?php
/*
Plugin Name: Upload To Youtube
Description: Upload videos directly to your Youtube Channel and also display them via widget anywhere in your website
Version: 1.0
Author: Piyush Rawat
Text Domain: upload-to-youtube
License: GPLv2 or later
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Including neccesary files and initiating the main class object

define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( PLUGIN_DIR . 'class.functions.php' );
require_once( PLUGIN_DIR . 'widget.php' );
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

$uty_object = new uploadToYoutube();

//Attach function to plugin activation
register_activation_hook( __FILE__ , array( $uty_object , 'uty_plugin_activation') );

//Register plugin settings
add_action( 'admin_init' , array( $uty_object , 'uty_register_settings') );

//Enqueue scripts and styles for admin
add_action( 'admin_enqueue_scripts' , array( $uty_object , 'uty_admin_enqueue') );

//Create plugin menu page
add_action( 'admin_menu' , array( $uty_object , 'register_uty_menu_page' ) );

//Include tabs jQuery if the user has been authenticated succesfully
if(get_option( 'uty_refresh_token' ) != ''){
	add_action( 'admin_enqueue_scripts' , array( $uty_object , 'uty_admin_enqueue_after_authorization' ) );
}

//Shortcode for displaying videos on page or posts
add_shortcode('display_uty_videos' , array( $uty_object , 'uty_video_shortcode' ) );

//Ajax implementation on admin dashboard
add_action( 'wp_ajax_deletevideo', array( $uty_object , 'deleteVideo' ) );
